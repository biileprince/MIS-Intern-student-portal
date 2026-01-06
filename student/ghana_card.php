<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: ../index.php");
    exit;
}

$student_id = $_SESSION["id"];
$ghana_card = "";
$ghana_card_err = $update_success = "";

// Fetch student data
$sql = "SELECT ghana_card FROM student WHERE student_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $param_id);
    $param_id = $student_id;

    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($ghana_card);
            $stmt->fetch();
        } else {
            // Handle error
        }
    }
    $stmt->close();
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ghana_card'])) {
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
            $stmt->bind_param("si", $ghana_card, $student_id);
            if ($stmt->execute()) {
                $update_success = "Ghana Card number updated successfully!";
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
    <title>Ghana Card - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="../assets/js/script.js"></script>
</head>

<body>
    <div class="container">
        <?php include '../includes/student_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Ghana Card Information</h1>
            </header>
            <main>
                <?php if (!empty($update_success)) : ?>
                    <div class="success"><?php echo $update_success; ?></div>
                <?php endif; ?>
                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="update_ghana_card">
                        <div class="form-group">
                            <label>Ghana Card Number</label>
                            <input type="text" name="ghana_card" value="<?php echo $ghana_card; ?>"
                                placeholder="GHA-724556-56" maxlength="13" pattern="GHA-\d{6}-\d{2}"
                                title="Format: GHA-XXXXXX-XX">
                            <span class="error"><?php echo $ghana_card_err; ?></span>
                        </div>
                        <button type="submit" class="btn">Update Ghana Card</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>