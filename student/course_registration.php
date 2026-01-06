<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: ../index.php");
    exit;
}

$student_id = $_SESSION["id"];
$message = "";

// Get current active registration session with academic calendar info
$current_session = null;
$current_academic_calendar = null;
$sql = "SELECT rs.*, ac.academic_year, ac.semester, ac.start_date as academic_start, ac.end_date as academic_end
        FROM registration_sessions rs
        LEFT JOIN academic_calendar ac ON rs.calendar_id = ac.calendar_id
        WHERE rs.is_active = 1 
        ORDER BY rs.session_id DESC LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $current_session = $result->fetch_assoc();
    if ($current_session['academic_year']) {
        $current_academic_calendar = [
            'academic_year' => $current_session['academic_year'],
            'semester' => $current_session['semester'],
            'academic_start' => $current_session['academic_start'],
            'academic_end' => $current_session['academic_end']
        ];
    }
}

// Check if registration is currently allowed
$registration_allowed = false;
$registration_status = "";
$academic_info = "";

if ($current_session) {
    // Show academic calendar information if available
    if ($current_academic_calendar) {
        $academic_info = "Academic Year: " . $current_academic_calendar['academic_year'] .
            " - Semester " . $current_academic_calendar['semester'] .
            " (Academic Period: " . date('M d, Y', strtotime($current_academic_calendar['academic_start'])) .
            " - " . date('M d, Y', strtotime($current_academic_calendar['academic_end'])) . ")";
    }

    // Allow registration regardless of date when academic year is active
    // Check if student has already registered for this session
    $sql = "SELECT COUNT(*) as count FROM student_courses WHERE student_id = ? AND session_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $current_session['session_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $registration_count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($registration_count == 0) {
        $registration_allowed = true;
        $registration_status = "Course registration is open for " .
            ($current_academic_calendar ? $current_academic_calendar['academic_year'] . " - Semester " . $current_academic_calendar['semester'] : "current session") .
            ". You can register for courses now!";
    } else {
        // Allow editing of registration instead of blocking
        $registration_allowed = true;
        $registration_status = "You have registered for courses in " .
            ($current_academic_calendar ? $current_academic_calendar['academic_year'] . " - Semester " . $current_academic_calendar['semester'] : "this session") .
            ". You can modify your course selection below.";
    }
} else {
    $registration_status = "No active registration session found. Please contact the administrator.";
}

// Get student's program and level
$program_id = $level = "";
$sql = "SELECT program_id, level FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($program_id, $level);
    $stmt->fetch();
    $stmt->close();
}

// Get available courses for student's program and level
$courses = [];
if ($program_id && $level) {
    $sql = "SELECT course_id, course_code, course_name, credits FROM courses WHERE program_id = ? AND level = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $program_id, $level);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $stmt->close();
    }
}

// Get current session registered courses
$current_registered_courses = [];
if ($current_session) {
    $sql = "SELECT c.course_id, c.course_code, c.course_name, c.credits 
            FROM courses c 
            JOIN student_courses sc ON c.course_id = sc.course_id 
            WHERE sc.student_id = ? AND sc.session_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $student_id, $current_session['session_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $current_registered_courses[$row['course_id']] = $row;
        }
        $stmt->close();
    }
}

// Get registration history with academic calendar info
$registration_history = [];
$sql = "SELECT rs.session_name, rs.start_date, rs.end_date, 
               ac.academic_year, ac.semester,
               GROUP_CONCAT(CONCAT(c.course_code, ' - ', c.course_name) SEPARATOR ', ') as courses,
               COUNT(sc.course_id) as course_count,
               MIN(sc.registration_date) as registered_on
        FROM registration_sessions rs
        LEFT JOIN academic_calendar ac ON rs.calendar_id = ac.calendar_id
        LEFT JOIN student_courses sc ON rs.session_id = sc.session_id AND sc.student_id = ?
        LEFT JOIN courses c ON sc.course_id = c.course_id
        WHERE rs.session_id IN (SELECT DISTINCT session_id FROM student_courses WHERE student_id = ? AND session_id IS NOT NULL)
        GROUP BY rs.session_id
        ORDER BY rs.session_id DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $registration_history[] = $row;
    }
    $stmt->close();
}

// Process course registration (both new and modifications)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_courses'])) {
    if (!$registration_allowed) {
        $message = "Course registration is not allowed at this time.";
    } else {
        $selected_courses = isset($_POST['courses']) ? $_POST['courses'] : [];

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Remove all existing registrations for this session
            $sql = "DELETE FROM student_courses WHERE student_id = ? AND session_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $student_id, $current_session['session_id']);
            $stmt->execute();
            $stmt->close();

            // Add new registrations for current session
            if (!empty($selected_courses)) {
                $sql = "INSERT INTO student_courses (student_id, course_id, session_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);

                foreach ($selected_courses as $course_id) {
                    $stmt->bind_param("iii", $student_id, $course_id, $current_session['session_id']);
                    $stmt->execute();
                }
                $stmt->close();
            }

            // Commit transaction
            $conn->commit();

            if (!empty($selected_courses)) {
                $message = "Course registration updated successfully! You are now registered for " . count($selected_courses) . " course(s).";
            } else {
                $message = "All course registrations removed successfully.";
            }

            // Refresh current registered courses
            $current_registered_courses = [];
            $sql = "SELECT c.course_id, c.course_code, c.course_name, c.credits 
                    FROM courses c 
                    JOIN student_courses sc ON c.course_id = sc.course_id 
                    WHERE sc.student_id = ? AND sc.session_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ii", $student_id, $current_session['session_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $current_registered_courses[$row['course_id']] = $row;
                }
                $stmt->close();
            }

            // Update registration status
            $registration_allowed = false;
            $registration_status = "You have successfully registered for courses in this session. You cannot modify your registration until the next registration period.";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error registering courses: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Course Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../assets/js/script.js"></script>
</head>

<body>
    <div class="container">
        <?php include '../includes/student_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <h2>Course Registration</h2>
            </header>
            <main>
                <div class="course-registration">
                    <?php if (!empty($message)) : ?>
                        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Status -->
                    <div class="registration-status">
                        <h3><i class="fas fa-info-circle"></i> Registration Status</h3>

                        <!-- Academic Calendar Information -->
                        <?php if ($current_academic_calendar): ?>
                            <div class="academic-calendar-info">
                                <h4><i class="fas fa-calendar-alt"></i> Current Academic Calendar</h4>
                                <div class="calendar-details">
                                    <div class="calendar-item">
                                        <strong>Academic Year:</strong> <?php echo $current_academic_calendar['academic_year']; ?>
                                    </div>
                                    <div class="calendar-item">
                                        <strong>Semester:</strong> <?php echo $current_academic_calendar['semester']; ?>
                                    </div>
                                    <div class="calendar-item">
                                        <strong>Academic Period:</strong>
                                        <?php echo date('M d, Y', strtotime($current_academic_calendar['academic_start'])); ?> -
                                        <?php echo date('M d, Y', strtotime($current_academic_calendar['academic_end'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="status-message <?php echo $registration_allowed ? 'status-open' : 'status-closed'; ?>">
                            <?php echo $registration_status; ?>
                        </div>

                        <?php if ($current_session): ?>
                            <div class="session-info">
                                <strong>Registration Period:</strong>
                                <?php echo date('M d, Y', strtotime($current_session['start_date'])); ?> -
                                <?php echo date('M d, Y', strtotime($current_session['end_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Current Registration -->
                    <?php if (!empty($current_registered_courses)): ?>
                        <div class="current-courses">
                            <h3><i class="fas fa-book-open"></i> Your Current Registration</h3>
                            <div class="course-list">
                                <?php $total_credits = 0; ?>
                                <?php foreach ($current_registered_courses as $course) : ?>
                                    <div class="course-item registered">
                                        <div class="course-info">
                                            <strong><?php echo $course['course_code']; ?>:</strong>
                                            <?php echo $course['course_name']; ?>
                                            <span class="credits"><?php echo $course['credits']; ?> credits</span>
                                        </div>
                                    </div>
                                    <?php $total_credits += $course['credits']; ?>
                                <?php endforeach; ?>
                                <div class="total-credits">
                                    <strong>Total Credits: <?php echo $total_credits; ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Course Registration Form -->
                    <?php if ($registration_allowed): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="registration-form">
                                <h3>
                                    <i class="fas fa-edit"></i>
                                    <?php echo !empty($current_registered_courses) ? 'Edit Your Course Registration' : 'Register for Courses'; ?>
                                    (Level <?php echo $level; ?>)
                                </h3>
                                <div class="course-list">
                                    <?php if (empty($courses)) : ?>
                                        <p>No courses available for your program and level at the moment.</p>
                                    <?php else : ?>
                                        <?php foreach ($courses as $course) : ?>
                                            <?php $is_registered = isset($current_registered_courses[$course['course_id']]); ?>
                                            <div class="course-item <?php echo $is_registered ? 'registered' : ''; ?>">
                                                <label class="course-checkbox">
                                                    <input type="checkbox" name="courses[]" value="<?php echo $course['course_id']; ?>"
                                                        <?php echo $is_registered ? 'checked' : ''; ?>>
                                                    <div class="course-info">
                                                        <strong><?php echo $course['course_code']; ?>:</strong>
                                                        <?php echo $course['course_name']; ?>
                                                        <span class="credits"><?php echo $course['credits']; ?> credits</span>
                                                        <?php if ($is_registered): ?>
                                                            <span class="status-registered">Currently Registered</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($courses)): ?>
                                    <button type="submit" name="register_courses" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?php echo !empty($current_registered_courses) ? 'Update Registration' : 'Register Selected Courses'; ?>
                                    </button>
                                    <?php if (!empty($current_registered_courses)): ?>
                                        <p class="help-text">
                                            <i class="fas fa-lightbulb"></i>
                                            <strong>Note:</strong> You can add or remove courses by checking/unchecking them above, then click "Update Registration".
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Registration History -->
                    <?php if (!empty($registration_history)): ?>
                        <div class="registration-history">
                            <h3><i class="fas fa-history"></i> Registration History</h3>
                            <div class="history-list">
                                <?php foreach ($registration_history as $history) : ?>
                                    <div class="history-item">
                                        <div class="history-header">
                                            <div class="session-title">
                                                <strong><?php echo $history['session_name']; ?></strong>
                                                <?php if ($history['academic_year']): ?>
                                                    <span class="academic-badge">
                                                        <?php echo $history['academic_year']; ?> - Semester <?php echo $history['semester']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="registration-date">
                                                Registered on: <?php echo date('M d, Y', strtotime($history['registered_on'])); ?>
                                            </span>
                                        </div>
                                        <div class="history-details">
                                            <strong>Registration Period:</strong> <?php echo date('M d, Y', strtotime($history['start_date'])); ?> -
                                            <?php echo date('M d, Y', strtotime($history['end_date'])); ?><br>
                                            <strong>Courses (<?php echo $history['course_count']; ?>):</strong>
                                            <?php echo $history['courses'] ?: 'No courses registered'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>