<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

$program_id = $program_name = $program_code = "";
$program_name_err = $program_code_err = "";
$update_success = "";

// Check if program ID is provided
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $program_id = trim($_GET["id"]);

    // Fetch program data
    $sql = "SELECT program_name, program_code FROM programs WHERE program_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $program_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $program_name = $row["program_name"];
                $program_code = $row["program_code"];
            } else {
                header("location: manage_programs.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
} else {
    header("location: manage_programs.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_program'])) {
        if (empty(trim($_POST["program_name"]))) {
            $program_name_err = "Please enter a program name.";
        } else {
            $program_name = trim($_POST["program_name"]);
        }

        if (empty(trim($_POST["program_code"]))) {
            $program_code_err = "Please enter a program code.";
        } else {
            $program_code = trim($_POST["program_code"]);
        }

        if (empty($program_name_err) && empty($program_code_err)) {
            $sql = "UPDATE programs SET program_name = ?, program_code = ? WHERE program_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssi", $program_name, $program_code, $program_id);
                if ($stmt->execute()) {
                    $update_success = "Program updated successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_program'])) {
        // Check if program is being used by students or courses
        $sql_check_students = "SELECT COUNT(*) as count FROM student WHERE program_id = ?";
        $sql_check_courses = "SELECT COUNT(*) as count FROM courses WHERE program_id = ?";

        $student_count = 0;
        $course_count = 0;

        if ($stmt_check_students = $conn->prepare($sql_check_students)) {
            $stmt_check_students->bind_param("i", $program_id);
            $stmt_check_students->execute();
            $stmt_check_students->bind_result($student_count);
            $stmt_check_students->fetch();
            $stmt_check_students->close();
        }

        if ($stmt_check_courses = $conn->prepare($sql_check_courses)) {
            $stmt_check_courses->bind_param("i", $program_id);
            $stmt_check_courses->execute();
            $stmt_check_courses->bind_result($course_count);
            $stmt_check_courses->fetch();
            $stmt_check_courses->close();
        }

        if ($student_count > 0 || $course_count > 0) {
            $update_success = "Cannot delete program: " . $student_count . " student(s) and " . $course_count . " course(s) are associated with this program.";
        } else {
            $sql_delete = "DELETE FROM programs WHERE program_id = ?";
            if ($stmt_delete = $conn->prepare($sql_delete)) {
                $stmt_delete->bind_param("i", $program_id);
                if ($stmt_delete->execute()) {
                    header("location: manage_programs.php?deleted=1");
                    exit();
                } else {
                    echo "Error: " . $stmt_delete->error;
                }
                $stmt_delete->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Program</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include 'admin_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Edit Program</h1>
            </header>
            <main class="centered-content">
                <a href="manage_programs.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Manage Programs
                </a>

                <div class="form-container">
                    <h3>Edit Program Information</h3>
                    <?php if (!empty($update_success)) : ?>
                        <div class="success"><?php echo $update_success; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $program_id; ?>" method="post">
                        <input type="hidden" name="update_program">
                        <div class="form-group">
                            <label>Program Name</label>
                            <input type="text" name="program_name" class="<?php echo (!empty($program_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $program_name; ?>">
                            <span class="error"><?php echo $program_name_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Program Code</label>
                            <input type="text" name="program_code" class="<?php echo (!empty($program_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $program_code; ?>">
                            <span class="error"><?php echo $program_code_err; ?></span>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Update Program</button>
                        </div>
                    </form>

                    <!-- Delete Program Section -->
                    <div class="danger-zone">
                        <h4>Danger Zone</h4>
                        <p>Permanently delete this program. This action cannot be undone and will only work if no students or courses are associated with this program.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $program_id; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this program? This action cannot be undone.');">
                            <input type="hidden" name="delete_program">
                            <button type="submit" class="btn btn-danger">Delete Program</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>