<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
  header("location: index.php");
  exit;
}

$student_id = $_SESSION["id"];
$phone = $address = "";
$phone_err = $address_err = $update_success = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty(trim($_POST["phone"]))) {
    $phone_err = "Please enter a phone number.";
  } else {
    $phone = trim($_POST["phone"]);
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
}

// Fetch student data
$sql = "SELECT reg_no, first_name, last_name, date_of_birth, gender, email, phone, address FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $param_id);
  $param_id = $student_id;

  if ($stmt->execute()) {
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
      $stmt->bind_result($reg_no, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address);
      $stmt->fetch();
    } else {
      // URL doesn't contain valid id. Redirect to error page
      header("location: error.php");
      exit();
    }
  } else {
    echo "Oops! Something went wrong. Please try again later.";
  }
  $stmt->close();
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Portal - My Information</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Reset & Font */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      display: flex;
      min-height: 100vh;
      background: #f1f1f1;
    }

    /* Sidebar */
    .sidebar {
      width: 220px;
      background: #9b5cff;
      color: #fff;
      display: flex;
      flex-direction: column;
      padding: 20px;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      color: #fff;
    }

    .sidebar a {
      color: #fff;
      text-decoration: none;
      padding: 12px;
      border-radius: 5px;
      display: block;
      margin-bottom: 10px;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: #fff;
      color: #9b5cff;
    }

    /* Main */
    .main {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    /* Header */
    header {
      background: #fff;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #ddd;
    }

    header h2 {
      color: #111;
    }

    .logout-btn {
      background: #9b5cff;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
    }

    /* Content */
    .content {
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .content h2 {
      margin-bottom: 20px;
      color: #111;
    }

    .student-info {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
    }

    .student-info form {
      display: flex;
      flex-direction: column;
    }

    .student-info label {
      margin-top: 10px;
      font-size: 14px;
      color: #555;
    }

    .student-info input {
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .student-info input:disabled {
      background: #f1f1f1;
      color: #666;
      cursor: not-allowed;
    }

    .student-info button {
      margin-top: 20px;
      padding: 12px;
      background: #9b5cff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    .student-info button:hover {
      opacity: 0.9;
    }

    .error {
      color: #ff4757;
      font-size: 12px;
    }

    .success {
      color: #27ae60;
      font-size: 14px;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Portal</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="#">My Courses</a>
    <a href="#">Grades</a>
    <a href="portal.php" class="active">Profile</a>
    <a href="#">Settings</a>
  </div>

  <!-- Main -->
  <div class="main">
    <header>
      <h2>My Information</h2>
      <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- Main Content -->
    <main class="content">
      <div class="student-info">
        <?php
        if (!empty($update_success)) {
          echo '<div class="success">' . $update_success . '</div>';
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <label>Registration No</label>
          <input type="text" value="<?php echo $reg_no; ?>" disabled>

          <label>First Name</label>
          <input type="text" value="<?php echo $first_name; ?>" disabled>

          <label>Last Name</label>
          <input type="text" value="<?php echo $last_name; ?>" disabled>

          <label>Date of Birth</label>
          <input type="date" value="<?php echo $date_of_birth; ?>" disabled>

          <label>Email</label>
          <input type="email" value="<?php echo $email; ?>" disabled>

          <label>Phone</label>
          <input type="text" name="phone" value="<?php echo $phone; ?>">
          <span class="error"><?php echo $phone_err; ?></span>

          <label>Address</label>
          <input type="text" name="address" value="<?php echo $address; ?>">
          <span class="error"><?php echo $address_err; ?></span>

          <button type="submit">Update Info</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    // Logout functionality
    const logoutBtn = document.querySelector('.logout-btn');
    logoutBtn.onclick = () => {
      // Redirect to login page
      window.location.href = 'logout.php';
    }
  </script>
</body>

</html>