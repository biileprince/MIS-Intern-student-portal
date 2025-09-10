<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect them to appropriate page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
  if ($_SESSION["role"] === "admin") {
    header("location: dashboard.php");
    exit;
  } else {
    header("location: portal.php");
    exit;
  }
}

require_once 'db_connect.php';

$login_id = $password = "";
$login_err = $password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (empty(trim($_POST["login_id"]))) {
    $login_err = "Please enter your registration number or email.";
  } else {
    $login_id = trim($_POST["login_id"]);
  }

  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter your password.";
  } else {
    $password = trim($_POST["password"]);
  }

  if (empty($login_err) && empty($password_err)) {
    // Check if login is email (admin) or registration number (student)
    if (filter_var($login_id, FILTER_VALIDATE_EMAIL)) {
      // Check in admin table
      $sql = "SELECT admin_id, name, email, password FROM admin WHERE email = ?";
      $role = "admin";
    } else {
      // Check in student table
      $sql = "SELECT student_id, first_name, reg_no, password FROM student WHERE reg_no = ?";
      $role = "student";
    }

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_login);
      $param_login = $login_id;

      if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
          if ($role == "admin") {
            $stmt->bind_result($id, $name, $email, $hashed_password);
          } else {
            $stmt->bind_result($id, $name, $reg_no, $hashed_password);
          }

          if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
              session_start();
              $_SESSION["loggedin"] = true;
              $_SESSION["id"] = $id;
              $_SESSION["name"] = $name;
              $_SESSION["role"] = $role;

              if ($role === "admin") {
                header("location: dashboard.php");
              } else {
                $_SESSION["reg_no"] = $reg_no;
                header("location: portal.php");
              }
              exit;
            } else {
              $login_err = "Invalid credentials.";
            }
          }
        } else {
          $login_err = "Invalid credentials.";
        }
      } else {
        echo "Oops! Something went wrong. Please try again later.";
      }
      $stmt->close();
    }
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Portal Login</title>
  <link rel="stylesheet" href="style.css?v=1.1">
  <style>
    /* Keep the existing styles from your index.php */
    /* ... */
  </style>
</head>

<body class="login-page">
  <div class="login-wrapper">
    <!-- Left side - Login box -->
    <div class="login-left">
      <div class="login-box">
        <h2>Login</h2>
        <p>Enter your account details</p>
        <?php
        if (!empty($login_err)) {
          echo '<div class="error">' . $login_err . '</div>';
        }
        ?>
        <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <label>Registration Number or Email</label>
          <input type="text" name="login_id" placeholder="Enter your registration number or email" required value="<?php echo $login_id; ?>">
          <span class="error"><?php echo $login_err; ?></span>

          <label>Password</label>
          <div class="password-field">
            <input type="password" name="password" placeholder="Enter your password" required>
            <span class="toggle-password">👁</span>
          </div>
          <span class="error"><?php echo $password_err; ?></span>

          <a href="#" class="forgot">Forgot Password?</a>

          <button type="submit" id="loginBtn">Login</button>
        </form>
      </div>
    </div>

    <!-- Right side - Welcome message -->
    <div class="login-right">
      <div class="welcome-text">
        <h1>Welcome to<br><span>the portal</span></h1>
        <p>Login to access your account</p>
      </div>
      <div class="illustration">
        <img src="./login.jpg" alt="Student Illustration">
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    const passwordField = document.querySelector('.password-field input');

    if (togglePassword && passwordField) {
      togglePassword.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.textContent = type === 'password' ? '👁' : '🙈';
      });
    }
  </script>
</body>

</html>