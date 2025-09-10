<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

$course_id = $course_code = $course_name = $program_id = $level = $credits = "";
$course_code_err = $course_name_err = "";
$update_success = "";

// Check if course ID is provided
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $course_id = trim($_GET["id"]);

    // Fetch course data
    $sql = "SELECT course_code, course_name, program_id, level, credits FROM courses WHERE course_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $course_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $course_code = $row["course_code"];
                $course_name = $row["course_name"];
                $program_id = $row["program_id"];
                $level = $row["level"];
                $credits = $row["credits"];
            } else {
                header("location: manage_courses.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
} else {
    header("location: manage_courses.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_course'])) {
        if (empty(trim($_POST["course_code"]))) {
            $course_code_err = "Please enter a course code.";
        } else {
            $course_code = trim($_POST["course_code"]);
        }

        if (empty(trim($_POST["course_name"]))) {
            $course_name_err = "Please enter a course name.";
        } else {
            $course_name = trim($_POST["course_name"]);
        }

        $program_id = $_POST["program_id"];
        $level = $_POST["level"];
        $credits = $_POST["credits"];

        if (empty($course_code_err) && empty($course_name_err)) {
            $sql = "UPDATE courses SET course_code = ?, course_name = ?, program_id = ?, level = ?, credits = ? WHERE course_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssisii", $course_code, $course_name, $program_id, $level, $credits, $course_id);
                if ($stmt->execute()) {
                    $update_success = "Course updated successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['delete_course'])) {
        // Check if course is being used by students
        $sql_check = "SELECT COUNT(*) as count FROM student_courses WHERE course_id = ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("i", $course_id);
            $stmt_check->execute();
            $stmt_check->bind_result($student_count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($student_count > 0) {
                $update_success = "Cannot delete course: " . $student_count . " student(s) are registered for this course.";
            } else {
                $sql_delete = "DELETE FROM courses WHERE course_id = ?";
                if ($stmt_delete = $conn->prepare($sql_delete)) {
                    $stmt_delete->bind_param("i", $course_id);
                    if ($stmt_delete->execute()) {
                        header("location: manage_courses.php?deleted=1");
                        exit();
                    } else {
                        echo "Error: " . $stmt_delete->error;
                    }
                    $stmt_delete->close();
                }
            }
        }
    }
}

$programs = [];
$sql_programs = "SELECT program_id, program_name FROM programs";
$result_programs = $conn->query($sql_programs);
if ($result_programs->num_rows > 0) {
    while ($row = $result_programs->fetch_assoc()) {
        $programs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Course</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include 'admin_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Edit Course</h1>
            </header>
            <main class="centered-content">
                <a href="manage_courses.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Manage Courses
                </a>

                <div class="form-container">
                    <h3>Edit Course Information</h3>
                    <?php if (!empty($update_success)) : ?>
                        <div class="success"><?php echo $update_success; ?></div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $course_id; ?>" method="post">
                        <input type="hidden" name="update_course">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Course Code</label>
                                <input type="text" name="course_code" class="<?php echo (!empty($course_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $course_code; ?>">
                                <span class="error"><?php echo $course_code_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Course Name</label>
                                <input type="text" name="course_name" class="<?php echo (!empty($course_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $course_name; ?>">
                                <span class="error"><?php echo $course_name_err; ?></span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Program</label>
                                <select name="program_id">
                                    <option value="">Select Program</option>
                                    <?php foreach ($programs as $program) : ?>
                                        <option value="<?php echo $program['program_id']; ?>" <?php echo ($program_id == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo $program['program_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Level</label>
                                <select name="level">
                                    <option value="100" <?php echo ($level == 100) ? 'selected' : ''; ?>>100</option>
                                    <option value="200" <?php echo ($level == 200) ? 'selected' : ''; ?>>200</option>
                                    <option value="300" <?php echo ($level == 300) ? 'selected' : ''; ?>>300</option>
                                    <option value="400" <?php echo ($level == 400) ? 'selected' : ''; ?>>400</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Credits</label>
                                <input type="number" name="credits" value="<?php echo $credits; ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Update Course</button>
                        </div>
                    </form>

                    <!-- Delete Course Section -->
                    <div class="danger-zone">
                        <h4>Danger Zone</h4>
                        <p>Permanently delete this course. This action cannot be undone.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $course_id; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.');">
                            <input type="hidden" name="delete_course">
                            <button type="submit" class="btn btn-danger">Delete Course</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>