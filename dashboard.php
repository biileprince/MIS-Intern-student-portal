<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
  header("location: index.php");
  exit;
}

// Fetch all students for display
$sql = "SELECT student_id, reg_no, first_name, last_name, email, phone FROM student";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
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

    /* Cards */
    .card-container {
      display: flex;
      gap: 20px;
      padding: 20px;
    }

    .card {
      flex: 1;
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .card h3 {
      margin-bottom: 10px;
      color: #9b5cff;
    }

    /* Table */
    .content {
      padding: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
    }

    th,
    td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
      background: #9b5cff;
      color: white;
    }

    button,
    .action-btn {
      padding: 6px 10px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }

    .edit-btn {
      background: #3498db;
      color: white;
      margin-right: 5px;
    }

    .delete-btn {
      background: #e74c3c;
      color: white;
    }

    .add-btn {
      background: #27ae60;
      color: white;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Admin</h2>
    <a href="dashboard.php" class="active">Dashboard</a>
    <a href="add_student.php">Add Student</a>
  </div>

  <!-- Main -->
  <div class="main">
    <header>
      <h2>Student Management Dashboard</h2>
      <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <!-- Cards -->
    <div class="card-container">
      <div class="card">
        <h3>Total Students</h3>
        <p><?php echo $result->num_rows; ?></p>
      </div>
    </div>

    <!-- Student List -->
    <div class="content">
      <a href="add_student.php" class="action-btn add-btn">Add New Student</a>
      <h2>Student List</h2>
      <table>
        <tr>
          <th>Reg No</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo $row["reg_no"]; ?></td>
              <td><?php echo $row["first_name"] . ' ' . $row["last_name"]; ?></td>
              <td><?php echo $row["email"]; ?></td>
              <td><?php echo $row["phone"]; ?></td>
              <td>
                <a href="edit_student.php?id=<?php echo $row['student_id']; ?>" class="action-btn edit-btn">Edit</a>
                <a href="delete_student.php?id=<?php echo $row['student_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5">No students found</td>
          </tr>
        <?php endif; ?>
      </table>
    </div>
  </div>
</body>

</html>