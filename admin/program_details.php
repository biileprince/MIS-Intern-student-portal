<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Get program ID from URL
$program_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($program_id <= 0) {
    header("location: dashboard.php");
    exit;
}

// Get program details
$sql = "SELECT * FROM programs WHERE program_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("location: dashboard.php");
    exit;
}

$program = $result->fetch_assoc();
$stmt->close();

// Get courses for this program grouped by level
$sql = "SELECT course_id, course_code, course_name, credits, level 
        FROM courses 
        WHERE program_id = ? 
        ORDER BY level, course_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();

$courses_by_level = [];
while ($row = $result->fetch_assoc()) {
    $courses_by_level[$row['level']][] = $row;
}
$stmt->close();

// Get program statistics
$sql = "SELECT 
            COUNT(DISTINCT s.student_id) as total_students,
            COUNT(DISTINCT c.course_id) as total_courses,
            SUM(c.credits) as total_credits
        FROM programs p
        LEFT JOIN student s ON p.program_id = s.program_id
        LEFT JOIN courses c ON p.program_id = c.program_id
        WHERE p.program_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// Get students in this program
$sql = "SELECT student_id, reg_no, first_name, last_name, level, email, phone, created_at
        FROM student 
        WHERE program_id = ?
        ORDER BY level, last_name, first_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($program['program_name']); ?> - Program Details</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <div class="header-title">
                        <h2>
                            <i class="fas fa-graduation-cap"></i>
                            <?php echo htmlspecialchars($program['program_name']); ?>
                        </h2>
                        <p class="header-subtitle">Program Details & Management</p>
                    </div>
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </header>

            <main class="program-details-main">
                <!-- Program Overview -->
                <section class="program-overview">
                    <div class="overview-header">
                        <h3><i class="fas fa-chart-bar"></i> Program Overview</h3>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h4><?php echo $stats['total_students']; ?></h4>
                                <p>Total Students</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h4><?php echo $stats['total_courses']; ?></h4>
                                <p>Total Courses</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="stat-content">
                                <h4><?php echo $stats['total_credits']; ?></h4>
                                <p>Total Credits</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Courses Section -->
                <section class="courses-section">
                    <div class="section-header">
                        <h3><i class="fas fa-list-alt"></i> Courses by Level</h3>
                        <a href="manage_courses.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Course
                        </a>
                    </div>

                    <div class="courses-content">
                        <?php if (empty($courses_by_level)): ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open"></i>
                                <h4>No Courses Found</h4>
                                <p>No courses have been added to this program yet.</p>
                                <a href="manage_courses.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add First Course
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($courses_by_level as $level => $courses): ?>
                                <div class="level-section">
                                    <div class="level-header">
                                        <h4><i class="fas fa-layer-group"></i> Level <?php echo $level; ?></h4>
                                        <span class="course-count"><?php echo count($courses); ?> courses</span>
                                    </div>
                                    <div class="courses-grid">
                                        <?php foreach ($courses as $course): ?>
                                            <div class="course-card">
                                                <div class="course-header">
                                                    <h5><?php echo htmlspecialchars($course['course_code']); ?></h5>
                                                    <span class="credits"><?php echo $course['credits']; ?> credits</span>
                                                </div>
                                                <p class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                                <div class="course-actions">
                                                    <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Students Section -->
                <section class="students-section">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> Students in Program</h3>
                        <a href="add_student.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Student
                        </a>
                    </div>

                    <div class="students-content">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <h4>No Students Enrolled</h4>
                                <p>No students are currently enrolled in this program.</p>
                                <a href="add_student.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Add First Student
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="responsive-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Reg No</th>
                                            <th>Name</th>
                                            <th>Level</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td data-label="Reg No"><?php echo htmlspecialchars($student['reg_no']); ?></td>
                                                <td data-label="Name">
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </td>
                                                <td data-label="Level">
                                                    <span class="level-badge level-<?php echo $student['level']; ?>">
                                                        Level <?php echo $student['level']; ?>
                                                    </span>
                                                </td>
                                                <td data-label="Email"><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td data-label="Phone"><?php echo htmlspecialchars($student['phone']); ?></td>
                                                <td data-label="Joined"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                                <td data-label="Actions">
                                                    <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>

</html>