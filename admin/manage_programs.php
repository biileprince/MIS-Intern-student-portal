<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$program_name = $program_code = "";
$program_name_err = $program_code_err = "";
$update_success = "";

// Check for delete success message
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $update_success = "Program deleted successfully!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_program'])) {
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
            $sql = "INSERT INTO programs (program_name, program_code) VALUES (?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ss", $program_name, $program_code);
                if ($stmt->execute()) {
                    $update_success = "Program added successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$programs = [];
$sql = "SELECT program_id, program_name, program_code FROM programs";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Programs</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Manage Programs</h1>
            </header>
            <main>
                <div class="form-container">
                    <h3>Add New Program</h3>
                    <?php if (!empty($update_success)) : ?>
                        <div class="success"><?php echo $update_success; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="add_program">
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
                        <button type="submit" class="btn">Add Program</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Existing Programs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Program Name</th>
                                <th>Program Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $program) : ?>
                                <tr>
                                    <td><?php echo $program['program_name']; ?></td>
                                    <td><?php echo $program['program_code']; ?></td>
                                    <td>
                                        <a href="edit_program.php?id=<?php echo $program['program_id']; ?>" class="btn-edit">Edit</a>
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