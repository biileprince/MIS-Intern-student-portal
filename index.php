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

$email = $password = "";
$email_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (empty(trim($_POST["email"]))) {
    $email_err = "Please enter email.";
  } else {
    $email = trim($_POST["email"]);
  }

  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter your password.";
  } else {
    $password = trim($_POST["password"]);
  }

  if (empty($email_err) && empty($password_err)) {
    // Check in admin table first
    $sql = "SELECT admin_id, name, email, password FROM admin WHERE email = ?";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_email);
      $param_email = $email;

      if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
          $stmt->bind_result($id, $name, $email, $hashed_password);
          if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
              session_start();
              $_SESSION["loggedin"] = true;
              $_SESSION["id"] = $id;
              $_SESSION["name"] = $name;
              $_SESSION["role"] = "admin";
              header("location: dashboard.php");
              exit;
            } else {
              $login_err = "Invalid email or password.";
            }
          }
        } else {
          // Not an admin, check student table
          $sql_student = "SELECT student_id, first_name, email, password FROM student WHERE email = ?";
          if ($stmt_student = $conn->prepare($sql_student)) {
            $stmt_student->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt_student->execute()) {
              $stmt_student->store_result();
              if ($stmt_student->num_rows == 1) {
                $stmt_student->bind_result($id, $name, $email, $hashed_password);
                if ($stmt_student->fetch()) {
                  if (password_verify($password, $hashed_password)) {
                    session_start();
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $id;
                    $_SESSION["name"] = $name;
                    $_SESSION["role"] = "student";
                    header("location: portal.php");
                    exit;
                  } else {
                    $login_err = "Invalid email or password.";
                  }
                }
              } else {
                $login_err = "Invalid email or password.";
              }
            }
            $stmt_student->close();
          }
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
  <link rel="stylesheet" href="style.css">
  <style>
    /* Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    /* Wrapper for split screen */
    .login-wrapper {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    /* Left side */
    .login-left {
      flex: 1;
      background: #111;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-box {
      width: 80%;
      max-width: 350px;
    }

    .login-box h2 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .login-box p {
      margin-bottom: 20px;
      color: #aaa;
    }

    .login-form label {
      display: block;
      font-size: 14px;
      margin: 10px 0 5px;
    }

    .login-form input {
      width: 100%;
      padding: 10px;
      border: none;
      outline: none;
      border-bottom: 2px solid #444;
      background: transparent;
      color: #fff;
    }

    .password-field {
      position: relative;
    }

    .password-field .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
    }

    .forgot {
      display: block;
      margin: 10px 0;
      font-size: 13px;
      color: #aaa;
      text-decoration: none;
    }

    #loginBtn {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      border: none;
      border-radius: 5px;
      background: #9b5cff;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
    }

    .signup-text {
      margin-top: 20px;
      font-size: 13px;
      color: #aaa;
    }

    .signup-text a {
      color: #9b5cff;
      text-decoration: none;
    }

    /* Right side */
    .login-right {
      flex: 1;
      background: #9b5cff;
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }

    .welcome-text h1 {
      font-size: 36px;
      line-height: 1.3;
    }

    .welcome-text span {
      font-weight: bold;
    }

    .welcome-text p {
      margin-top: 10px;
      font-size: 14px;
      color: #f1f1f1;
    }

    .illustration {
      margin-top: 40px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.7);
    }

    .illustration img {
      max-width: 300px;
      border-radius: 10px;
    }

    .login-box {
      background: #111;
    }

    .error {
      color: #ff4757;
      font-size: 12px;
    }
  </style>
</head>

<body>
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
          <label>Email</label>
          <input type="email" name="email" placeholder="Enter your email" required value="<?php echo $email; ?>">
          <span class="error"><?php echo $email_err; ?></span>

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