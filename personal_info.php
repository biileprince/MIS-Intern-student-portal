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

// Fetch student data
$sql = "SELECT first_name, last_name, email, phone, address FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $student_id;

    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($first_name, $last_name, $email, $phone, $address);
            $stmt->fetch();
        } else {
            // Handle error
        }
    }
    $stmt->close();
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_info'])) {
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
            $stmt->bind_param("ssi", $phone, $address, $student_id);
            if ($stmt->execute()) {
                $update_success = "Information updated successfully!";
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
    <title>Personal Information - Student Portal</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="script.js"></script>
</head>

<body>
    <div class="container">
        <?php include 'student_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Personal Information</h1>
            </header>
            <main>
                <?php if (!empty($update_success)) : ?>
                    <div class="success"><?php echo $update_success; ?></div>
                <?php endif; ?>
                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="update_info">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" value="<?php echo $first_name; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" value="<?php echo $last_name; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo $email; ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo $phone; ?>"
                                placeholder="0555902675" maxlength="10" pattern="0[0-9]{9}"
                                title="Format: 0XXXXXXXXX (10 digits starting with 0)">
                            <span class="error"><?php echo $phone_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo $address; ?>"
                                placeholder="Enter your address (or check below for postal code format)">
                            <span class="error"><?php echo $address_err; ?></span>
                        </div>
                        <button type="submit" class="btn">Update Information</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>