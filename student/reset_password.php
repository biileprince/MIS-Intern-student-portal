<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: ../index.php");
    exit;
}

$student_id = $_SESSION["id"];
$password_err = $confirm_password_err = $update_success = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
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
            $stmt->bind_param("si", $param_password, $student_id);
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            if ($stmt->execute()) {
                $update_success = "Password updated successfully!";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="../assets/js/script.js"></script>
</head>

<body>
    <div class="container">
        <?php include '../includes/student_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Reset Password</h1>
            </header>
            <main>
                <?php if (!empty($update_success)) : ?>
                    <div class="success"><?php echo $update_success; ?></div>
                <?php endif; ?>
                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="reset_password">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password">
                            <span class="error"><?php echo $password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password">
                            <span class="error"><?php echo $confirm_password_err; ?></span>
                        </div>
                        <button type="submit" class="btn">Reset Password</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>