<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
  header("location: ../index.php");
  exit;
}

// Fetch all students with program information
$sql = "SELECT s.student_id, s.reg_no, s.first_name, s.last_name, s.email, s.phone, 
               s.address, s.date_of_birth, s.gender, s.level, p.program_name,
               s.created_at
        FROM student s 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);

// Get total students count
$total_students = $result->num_rows;

// Get programs for filtering
$programs = [];
$sql_programs = "SELECT program_id, program_name FROM programs";
$result_programs = $conn->query($sql_programs);
if ($result_programs->num_rows > 0) {
  while ($row = $result_programs->fetch_assoc()) {
    $programs[] = $row;
  }
}

// Get students by program with program IDs
$students_by_program = [];
$sql_program_count = "SELECT p.program_id, p.program_name, COUNT(s.student_id) as count 
                     FROM programs p 
                     LEFT JOIN student s ON p.program_id = s.program_id 
                     GROUP BY p.program_id";
$result_program_count = $conn->query($sql_program_count);
if ($result_program_count->num_rows > 0) {
  while ($row = $result_program_count->fetch_assoc()) {
    $students_by_program[] = $row;
  }
}

// Get total courses
$sql_courses = "SELECT COUNT(*) as total_courses FROM courses";
$result_courses = $conn->query($sql_courses);
$total_courses = $result_courses->fetch_assoc()['total_courses'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="container">
    <?php include '../includes/admin_sidebar.php'; ?>
    <div class="main-content">
      <header class="main-header">
        <h2>Dashboard</h2>
      </header>

      <main>
        <div class="cards">
          <div class="card">
            <i class="fas fa-users"></i>
            <div class="card-content">
              <h3><?php echo $total_students; ?></h3>
              <p>Total Students</p>
            </div>
          </div>
          <div class="card">
            <i class="fas fa-graduation-cap"></i>
            <div class="card-content">
              <h3><?php echo count($programs); ?></h3>
              <p>Programs Offered</p>
            </div>
          </div>
          <div class="card">
            <i class="fas fa-book"></i>
            <div class="card-content">
              <h3><?php echo $total_courses; ?></h3>
              <p>Total Courses</p>
            </div>
          </div>
        </div>

        <!-- Students by Program -->
        <div class="table-container">
          <div class="table-header">
            <h3>Students by Program</h3>
          </div>
          <div class="program-stats">
            <?php foreach ($students_by_program as $program_stat) : ?>
              <div class="stat-item">
                <a href="program_details.php?id=<?php echo $program_stat['program_id']; ?>" class="program-link">
                  <span class="program-name"><?php echo $program_stat['program_name']; ?></span>
                  <span class="count"><?php echo $program_stat['count']; ?> students</span>
                  <i class="fas fa-arrow-right"></i>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="table-container">
          <div class="table-header">
            <h3>All Students Information</h3>
            <a href="add_student.php" class="add-student-btn">Add New Student</a>
          </div>
          <div class="responsive-table">
            <table>
              <thead>
                <tr>
                  <th>Reg No</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Program</th>
                  <th>Level</th>
                  <th>Gender</th>
                  <th>Date of Birth</th>
                  <th>Address</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Re-run the query since it was closed
                $sql = "SELECT s.student_id, s.reg_no, s.first_name, s.last_name, s.email, s.phone, 
                               s.address, s.date_of_birth, s.gender, s.level, p.program_name
                        FROM student s 
                        LEFT JOIN programs p ON s.program_id = p.program_id 
                        ORDER BY s.created_at DESC";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) : ?>
                  <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                      <td data-label="Reg No"><?php echo $row['reg_no']; ?></td>
                      <td data-label="Name"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                      <td data-label="Email"><?php echo $row['email']; ?></td>
                      <td data-label="Phone"><?php echo $row['phone']; ?></td>
                      <td data-label="Program"><?php echo $row['program_name'] ?? 'Not Assigned'; ?></td>
                      <td data-label="Level"><?php echo $row['level']; ?></td>
                      <td data-label="Gender"><?php echo $row['gender']; ?></td>
                      <td data-label="DOB"><?php echo date('M d, Y', strtotime($row['date_of_birth'])); ?></td>
                      <td data-label="Address"><?php echo $row['address']; ?></td>
                      <td data-label="Actions" class="actions">
                        <a href="edit_student.php?id=<?php echo $row['student_id']; ?>" title="Edit Student">
                          <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_student.php?id=<?php echo $row['student_id']; ?>"
                          class="delete"
                          title="Delete Student"
                          onclick="return confirm('Are you sure you want to delete this student?');">
                          <i class="fas fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else : ?>
                  <tr>
                    <td colspan="10">No students found.</td>
                  </tr>
                <?php endif;
                $conn->close();
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
</body>

</html>