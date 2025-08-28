<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

$reg_no = $first_name = $last_name = $date_of_birth = $gender = $email = $phone = $address = $password = "";
$reg_no_err = $first_name_err = $last_name_err = $email_err = $password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    if (empty(trim($_POST["reg_no"]))) {
        $reg_no_err = "Please enter a registration number.";
    } else {
        $reg_no = trim($_POST["reg_no"]);
    }

    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter a first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter a last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Non-validated fields
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Get admin_id from the session
    $admin_id = $_SESSION['id'];

    if (empty($reg_no_err) && empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err)) {
        $sql = "INSERT INTO student (reg_no, first_name, last_name, date_of_birth, gender, email, phone, address, password, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssssi", $reg_no, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address, $hashed_password, $admin_id);

            if ($stmt->execute()) {
                header("location: dashboard.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later. Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Something went wrong with the prepare statement. Error: " . $conn->error;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="style.css">
    <style>
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

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

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

        .content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
        }

        .form-container label {
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }

        .form-container input,
        .form-container select {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-container button {
            margin-top: 20px;
            padding: 12px;
            background: #9b5cff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .form-container button:hover {
            opacity: 0.9;
        }

        .error {
            color: #ff4757;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_student.php" class="active">Add Student</a>
    </div>
    <div class="main">
        <header>
            <h2>Add New Student</h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </header>
        <main class="content">
            <div class="form-container">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label>Registration No</label>
                    <input type="text" name="reg_no" required value="<?php echo $reg_no; ?>">
                    <span class="error"><?php echo $reg_no_err; ?></span>

                    <label>First Name</label>
                    <input type="text" name="first_name" required value="<?php echo $first_name; ?>">
                    <span class="error"><?php echo $first_name_err; ?></span>

                    <label>Last Name</label>
                    <input type="text" name="last_name" required value="<?php echo $last_name; ?>">
                    <span class="error"><?php echo $last_name_err; ?></span>

                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" required value="<?php echo $date_of_birth; ?>">

                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male" <?php echo ($gender == "Male") ? "selected" : ""; ?>>Male</option>
                        <option value="Female" <?php echo ($gender == "Female") ? "selected" : ""; ?>>Female</option>
                        <option value="Other" <?php echo ($gender == "Other") ? "selected" : ""; ?>>Other</option>
                    </select>

                    <label>Email</label>
                    <input type="email" name="email" required value="<?php echo $email; ?>">
                    <span class="error"><?php echo $email_err; ?></span>

                    <label>Phone</label>
                    <input type="text" name="phone" required value="<?php echo $phone; ?>">

                    <label>Address</label>
                    <input type="text" name="address" required value="<?php echo $address; ?>">

                    <label>Password</label>
                    <input type="password" name="password" required>
                    <span class="error"><?php echo $password_err; ?></span>

                    <button type="submit">Add Student</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>