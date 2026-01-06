<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Get programs for dropdown
$programs = [];
$sql = "SELECT program_id, program_name FROM programs";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
}

$reg_no = $first_name = $last_name = $date_of_birth = $gender = $email = $phone = $address = $password = $level = $program_id = "";
$reg_no_err = $first_name_err = $last_name_err = $email_err = $password_err = $phone_err = "";

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

    // Validate phone format if provided
    if (!empty($phone) && !preg_match('/^0\d{9}$/', $phone)) {
        $phone_err = "Please enter a valid phone number format: 0XXXXXXXXX (10 digits starting with 0)";
    }

    $address = trim($_POST['address']);
    $program_id = trim($_POST['program_id']);
    $level = trim($_POST['level']);

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Get admin_id from the session
    $admin_id = $_SESSION['id'];

    if (empty($reg_no_err) && empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($phone_err)) {
        $sql = "INSERT INTO student (reg_no, first_name, last_name, date_of_birth, gender, email, phone, address, password, assigned_by, program_id, level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssssiis", $reg_no, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address, $hashed_password, $admin_id, $program_id, $level);

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
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <h2>Add New Student</h2>
            </header>
            <main class="centered-content">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h4>Personal Information</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" required value="<?php echo $first_name; ?>">
                                    <span class="error"><?php echo $first_name_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" required value="<?php echo $last_name; ?>">
                                    <span class="error"><?php echo $last_name_err; ?></span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" required value="<?php echo $date_of_birth; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($gender == "Male") ? "selected" : ""; ?>>Male</option>
                                        <option value="Female" <?php echo ($gender == "Female") ? "selected" : ""; ?>>Female</option>
                                        <option value="Other" <?php echo ($gender == "Other") ? "selected" : ""; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h4>Contact Information</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" required value="<?php echo $email; ?>">
                                    <span class="error"><?php echo $email_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" required value="<?php echo $phone; ?>"
                                        placeholder="0555902675" maxlength="10" pattern="0[0-9]{9}"
                                        title="Format: 0XXXXXXXXX (10 digits starting with 0)">
                                    <span class="error"><?php echo $phone_err; ?></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" required value="<?php echo $address; ?>"
                                    placeholder="Enter address">
                            </div>
                        </div>

                        <!-- Academic Information Section -->
                        <div class="form-section">
                            <h4>Academic Information</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Registration No</label>
                                    <input type="text" name="reg_no" required value="<?php echo $reg_no; ?>">
                                    <span class="error"><?php echo $reg_no_err; ?></span>
                                </div>
                                <div class="form-group">
                                    <label>Level</label>
                                    <select name="level" required>
                                        <option value="">Select Level</option>
                                        <option value="100" <?php echo ($level == "100") ? "selected" : ""; ?>>100</option>
                                        <option value="200" <?php echo ($level == "200") ? "selected" : ""; ?>>200</option>
                                        <option value="300" <?php echo ($level == "300") ? "selected" : ""; ?>>300</option>
                                        <option value="400" <?php echo ($level == "400") ? "selected" : ""; ?>>400</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Program</label>
                                <select name="program_id" required>
                                    <option value="">Select Program</option>
                                    <?php foreach ($programs as $program) : ?>
                                        <option value="<?php echo $program['program_id']; ?>" <?php echo ($program_id == $program['program_id']) ? "selected" : ""; ?>>
                                            <?php echo $program['program_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Account Security Section -->
                        <div class="form-section">
                            <h4>Account Security</h4>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" required placeholder="Enter a secure password">
                                <span class="error"><?php echo $password_err; ?></span>
                                <small class="form-help">Password should be at least 8 characters long</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Add Student</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="script.js"></script>
</body>

</html>