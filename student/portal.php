<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
  header("location: ../index.php");
  exit;
}

$student_id = $_SESSION["id"];
$phone = $address = $ghana_card = "";
$phone_err = $address_err = $ghana_card_err = $update_success = $password_err = $confirm_password_err = "";

// Fetch student data
$sql = "SELECT reg_no, first_name, last_name, date_of_birth, gender, email, phone, address, program_id, level, ghana_card FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $param_id);
  $param_id = $student_id;

  if ($stmt->execute()) {
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
      $stmt->bind_result($reg_no, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address, $program_id, $level, $ghana_card);
      $stmt->fetch();
    } else {
      header("location: error.php");
      exit();
    }
  } else {
    echo "Oops! Something went wrong. Please try again later.";
  }
  $stmt->close();
}

// Get program name
$program_name = "";
if ($program_id) {
  $sql_program = "SELECT program_name FROM programs WHERE program_id = ?";
  if ($stmt_program = $conn->prepare($sql_program)) {
    $stmt_program->bind_param("i", $program_id);
    $stmt_program->execute();
    $stmt_program->bind_result($program_name);
    $stmt_program->fetch();
    $stmt_program->close();
  }
}

// Get registered courses count
$registered_courses_count = 0;
$sql_courses = "SELECT COUNT(*) FROM student_courses WHERE student_id = ?";
if ($stmt_courses = $conn->prepare($sql_courses)) {
  $stmt_courses->bind_param("i", $student_id);
  $stmt_courses->execute();
  $stmt_courses->bind_result($registered_courses_count);
  $stmt_courses->fetch();
  $stmt_courses->close();
}

// Get student's registered courses with course details
$student_courses = [];
$sql_student_courses = "SELECT c.course_code, c.course_name, c.credits, c.level, rs.session_name, sc.registration_date 
                       FROM student_courses sc 
                       JOIN courses c ON sc.course_id = c.course_id 
                       LEFT JOIN registration_sessions rs ON sc.session_id = rs.session_id 
                       WHERE sc.student_id = ? 
                       ORDER BY sc.registration_date DESC";
if ($stmt_student_courses = $conn->prepare($sql_student_courses)) {
  $stmt_student_courses->bind_param("i", $student_id);
  $stmt_student_courses->execute();
  $result_student_courses = $stmt_student_courses->get_result();
  while ($row = $result_student_courses->fetch_assoc()) {
    $student_courses[] = $row;
  }
  $stmt_student_courses->close();
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['update_info'])) {
    // Update personal information
    if (empty(trim($_POST["phone"]))) {
      $phone_err = "Please enter a phone number.";
    } else {
      $phone = trim($_POST["phone"]);

      // Validate phone format: 0XXXXXXXXX (10 digits starting with 0)
      if (!preg_match('/^0\d{9}$/', $phone)) {
        $phone_err = "Please enter a valid phone number format: 0XXXXXXXXX (10 digits starting with 0)";
      }
    }

    if (empty(trim($_POST["address"]))) {
      $address_err = "Please enter an address.";
    } else {
      $address = trim($_POST["address"]);
    }

    if (empty($phone_err) && empty($address_err)) {
      $sql = "UPDATE student SET phone = ?, address = ? WHERE student_id = ?";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssi", $param_phone, $param_address, $param_id);

        $param_phone = $phone;
        $param_address = $address;
        $param_id = $student_id;

        if ($stmt->execute()) {
          $update_success = "Information updated successfully!";
        } else {
          echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
      }
    }
  } elseif (isset($_POST['update_ghana_card'])) {
    // Update Ghana Card
    if (empty(trim($_POST["ghana_card"]))) {
      $ghana_card_err = "Please enter your Ghana Card number.";
    } else {
      $ghana_card = trim($_POST["ghana_card"]);

      // Validate Ghana Card format: GHA-XXXXXX-XX
      if (!preg_match('/^GHA-\d{6}-\d{2}$/', $ghana_card)) {
        $ghana_card_err = "Please enter a valid Ghana Card format: GHA-XXXXXX-XX (e.g., GHA-724556-56)";
      }
    }

    if (empty($ghana_card_err)) {
      $sql = "UPDATE student SET ghana_card = ? WHERE student_id = ?";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $param_ghana_card, $param_id);

        $param_ghana_card = $ghana_card;
        $param_id = $student_id;

        if ($stmt->execute()) {
          $update_success = "Ghana Card number updated successfully!";
        } else {
          echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
      }
    }
  } elseif (isset($_POST['reset_password'])) {
    // Reset password
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if (empty($new_password)) {
      $password_err = "Please enter a new password.";
    } elseif (strlen($new_password) < 6) {
      $password_err = "Password must have at least 6 characters.";
    }

    if (empty($confirm_password)) {
      $confirm_password_err = "Please confirm the password.";
    } elseif ($new_password != $confirm_password) {
      $confirm_password_err = "Password did not match.";
    }

    if (empty($password_err) && empty($confirm_password_err)) {
      $sql = "UPDATE student SET password = ? WHERE student_id = ?";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $param_password, $param_id);

        $param_password = password_hash($new_password, PASSWORD_DEFAULT);
        $param_id = $student_id;

        if ($stmt->execute()) {
          $update_success = "Password updated successfully!";
        } else {
          echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
      }
    }
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Portal</title>
  <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="../assets/js/script.js"></script>
</head>

<body>
  <div class="container">
    <?php include '../includes/student_sidebar.php'; ?>
    <div class="main-content">
      <header class="main-header">
        <h2>Dashboard</h2>
      </header>

      <main>
        <?php if (!empty($update_success)) : ?>
          <div class="success"><?php echo $update_success; ?></div>
        <?php endif; ?>

        <div class="cards">
          <div class="card">
            <i class="fas fa-id-card"></i>
            <div class="card-content">
              <h3><?php echo $reg_no; ?></h3>
              <p>Registration Number</p>
            </div>
          </div>
          <div class="card">
            <i class="fas fa-graduation-cap"></i>
            <div class="card-content">
              <h3><?php echo $program_name; ?></h3>
              <p>Program</p>
            </div>
          </div>
          <div class="card">
            <i class="fas fa-layer-group"></i>
            <div class="card-content">
              <h3><?php echo $level; ?></h3>
              <p>Level</p>
            </div>
          </div>
          <div class="card">
            <i class="fas fa-book"></i>
            <div class="card-content">
              <h3><?php echo $registered_courses_count; ?></h3>
              <p>Courses Registered</p>
            </div>
          </div>
        </div>

        <!-- Student's Registered Courses -->
        <div class="table-container">
          <div class="table-header">
            <h3>My Registered Courses</h3>
          </div>
          <div class="responsive-table">
            <?php if (count($student_courses) > 0): ?>
              <table>
                <thead>
                  <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Credits</th>
                    <th>Level</th>
                    <th>Session</th>
                    <th>Registration Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($student_courses as $course): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                      <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                      <td><?php echo htmlspecialchars($course['credits']); ?></td>
                      <td><?php echo htmlspecialchars($course['level']); ?></td>
                      <td><?php echo htmlspecialchars($course['session_name'] ?? 'N/A'); ?></td>
                      <td><?php echo date('Y-m-d', strtotime($course['registration_date'])); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div style="padding: 20px; text-align: center;">
                <p>You haven't registered for any courses yet.</p>
                <a href="course_registration.php" class="btn" style="margin-top: 10px;">Register for Courses</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </main>
    </div>
</body>

</html>