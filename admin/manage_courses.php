<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$course_code = $course_name = $program_id = $level = $credits = "";
$course_code_err = $course_name_err = "";
$update_success = "";

// Check for delete success message
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $update_success = "Course deleted successfully!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_course'])) {
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
            $sql = "INSERT INTO courses (course_code, course_name, program_id, level, credits) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssisi", $course_code, $course_name, $program_id, $level, $credits);
                if ($stmt->execute()) {
                    $update_success = "Course added successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
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

$courses = [];
$sql_courses = "SELECT c.course_id, c.course_code, c.course_name, p.program_name, c.level, c.credits FROM courses c LEFT JOIN programs p ON c.program_id = p.program_id";
$result_courses = $conn->query($sql_courses);
if ($result_courses->num_rows > 0) {
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Manage Courses</h1>
            </header>
            <main>
                <div class="form-container">
                    <h3>Add New Course</h3>
                    <?php if (!empty($update_success)) : ?>
                        <div class="success"><?php echo $update_success; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="add_course">
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
                                        <option value="<?php echo $program['program_id']; ?>"><?php echo $program['program_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Level</label>
                                <select name="level">
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="300">300</option>
                                    <option value="400">400</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Credits</label>
                                <input type="number" name="credits" value="<?php echo $credits; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn">Add Course</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Existing Courses</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Program</th>
                                <th>Level</th>
                                <th>Credits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course) : ?>
                                <tr>
                                    <td><?php echo $course['course_code']; ?></td>
                                    <td><?php echo $course['course_name']; ?></td>
                                    <td><?php echo $course['program_name']; ?></td>
                                    <td><?php echo $course['level']; ?></td>
                                    <td><?php echo $course['credits']; ?></td>
                                    <td>
                                        <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn-edit">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>

</html>