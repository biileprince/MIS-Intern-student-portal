<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Check if student ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: dashboard.php");
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

$student_id = $_GET['id'];
$reg_no = $first_name = $last_name = $date_of_birth = $gender = $email = $phone = $address = $program_id = $level = "";
$reg_no_err = $first_name_err = $last_name_err = $email_err = $phone_err = $update_success = "";

// Fetch student data from database
$sql = "SELECT reg_no, first_name, last_name, date_of_birth, gender, email, phone, address, program_id, level FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $student_id;

    if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($reg_no, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address, $program_id, $level);
            $stmt->fetch();
        } else {
            // Student doesn't exist, redirect to dashboard
            header("location: dashboard.php");
            exit();
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    $stmt->close();
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
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

    if (empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($phone_err)) {
        $sql = "UPDATE student SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, email = ?, phone = ?, address = ?, program_id = ?, level = ? WHERE student_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssssisi", $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address, $program_id, $level, $student_id);

            if ($stmt->execute()) {
                $update_success = "Student information updated successfully!";
                // Refresh the page to show updated data
                header("refresh:2; url=edit_student.php?id=" . $student_id);
            } else {
                echo "Something went wrong. Please try again later. Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Something went wrong with the prepare statement. Error: " . $conn->error;
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <h2>Edit Student</h2>
            </header>
            <main>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

                <div class="form-container">
                    <?php
                    if (!empty($update_success)) {
                        echo '<div class="success">' . $update_success . '</div>';
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $student_id; ?>" method="post">
                        <label>Registration No</label>
                        <input type="text" value="<?php echo $reg_no; ?>" disabled>

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
                        <input type="text" name="phone" required value="<?php echo $phone; ?>"
                            placeholder="0555902675" maxlength="10" pattern="0[0-9]{9}"
                            title="Format: 0XXXXXXXXX (10 digits starting with 0)">
                        <span class="error"><?php echo $phone_err; ?></span>

                        <label>Address</label>
                        <input type="text" name="address" required value="<?php echo $address; ?>">

                        <label>Program</label>
                        <select name="program_id" required>
                            <?php foreach ($programs as $program) : ?>
                                <option value="<?php echo $program['program_id']; ?>" <?php echo ($program_id == $program['program_id']) ? "selected" : ""; ?>>
                                    <?php echo $program['program_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Level</label>
                        <input type="text" name="level" required value="<?php echo $level; ?>">

                        <button type="submit">Update Student</button>
                    </form>
                </div>
            </main>
        </div>
</body>

</html>