<?php
session_start();
require_once '../includes/db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$message = "";

// Handle new academic year creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_calendar'])) {
    $academic_year = trim($_POST['academic_year']);
    $semester = $_POST['semester'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $registration_start = $_POST['registration_start'];
    $registration_end = $_POST['registration_end'];

    // Create academic calendar entry
    $sql = "INSERT INTO academic_calendar (academic_year, semester, start_date, end_date, registration_start, registration_end) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sissss", $academic_year, $semester, $start_date, $end_date, $registration_start, $registration_end);
        if ($stmt->execute()) {
            $message = "Academic calendar entry created successfully!";
        } else {
            $message = "Error creating calendar entry: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle setting active academic calendar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_active'])) {
    $calendar_id = $_POST['calendar_id'];

    // First deactivate all registration sessions
    $sql = "UPDATE registration_sessions SET is_active = 0";
    $conn->query($sql);

    // Get the selected academic calendar details
    $sql = "SELECT * FROM academic_calendar WHERE calendar_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $calendar_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $calendar = $result->fetch_assoc();
    $stmt->close();

    if ($calendar) {
        // Create or update registration session for this academic calendar
        $session_name = $calendar['academic_year'] . " - Semester " . $calendar['semester'];

        // Check if session already exists for this calendar
        $sql = "SELECT session_id FROM registration_sessions WHERE calendar_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $calendar_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing session
            $session = $result->fetch_assoc();
            $sql = "UPDATE registration_sessions SET is_active = 1, start_date = ?, end_date = ? WHERE session_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $calendar['registration_start'], $calendar['registration_end'], $session['session_id']);
        } else {
            // Create new session
            $sql = "INSERT INTO registration_sessions (session_name, start_date, end_date, is_active, calendar_id) VALUES (?, ?, ?, 1, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $session_name, $calendar['registration_start'], $calendar['registration_end'], $calendar_id);
        }

        if ($stmt->execute()) {
            $message = "Academic calendar activated successfully! Registration session updated.";
        } else {
            $message = "Error activating calendar: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all academic calendars
$sql = "SELECT ac.*, 
        (SELECT COUNT(*) FROM registration_sessions rs WHERE rs.calendar_id = ac.calendar_id AND rs.is_active = 1) as is_active
        FROM academic_calendar ac 
        ORDER BY ac.academic_year DESC, ac.semester DESC";
$calendars = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Academic Calendar</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <h2>Manage Academic Calendar</h2>
            </header>
            <main>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

                <?php if (!empty($message)) : ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Create New Academic Calendar Entry -->
                <div class="form-container">
                    <h3><i class="fas fa-calendar-plus"></i> Create New Academic Calendar</h3>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-section">
                            <h4>Academic Period</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Academic Year</label>
                                    <input type="text" name="academic_year" required
                                        placeholder="e.g., 2025/2026" pattern="^\d{4}/\d{4}$"
                                        title="Format: YYYY/YYYY (e.g., 2025/2026)">
                                </div>
                                <div class="form-group">
                                    <label>Semester</label>
                                    <select name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="form-section">
                            <h4>Academic Calendar Dates</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Academic Start Date</label>
                                    <input type="date" name="start_date" required id="academicStart">
                                </div>
                                <div class="form-group">
                                    <label>Academic End Date</label>
                                    <input type="date" name="end_date" required id="academicEnd">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Course Registration Period</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Registration Start Date</label>
                                    <input type="date" name="registration_start" required id="regStart">
                                </div>
                                <div class="form-group">
                                    <label>Registration End Date</label>
                                    <input type="date" name="registration_end" required id="regEnd">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="create_calendar" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Academic Calendar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Academic Calendars -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-calendar-alt"></i> Academic Calendar</h3>
                    </div>
                    <div class="responsive-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Semester</th>
                                    <th>Academic Period</th>
                                    <th>Registration Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($calendars->num_rows > 0) : ?>
                                    <?php while ($calendar = $calendars->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($calendar['academic_year']); ?></td>
                                            <td>Semester <?php echo $calendar['semester']; ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($calendar['start_date'])); ?> -
                                                <?php echo date('M d, Y', strtotime($calendar['end_date'])); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($calendar['registration_start'])); ?> -
                                                <?php echo date('M d, Y', strtotime($calendar['registration_end'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($calendar['is_active'] > 0) : ?>
                                                    <span class="status-badge status-active">Active</span>
                                                <?php else : ?>
                                                    <span class="status-badge status-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($calendar['is_active'] == 0) : ?>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="calendar_id" value="<?php echo $calendar['calendar_id']; ?>">
                                                        <button type="submit" name="set_active" class="btn btn-sm btn-primary"
                                                            onclick="return confirm('This will activate this academic calendar and deactivate all others. Continue?')">
                                                            <i class="fas fa-play"></i> Activate
                                                        </button>
                                                    </form>
                                                <?php else : ?>
                                                    <span class="text-muted">Currently Active</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No academic calendars found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Date validation for academic calendar
        document.addEventListener('DOMContentLoaded', function() {
            const academicStart = document.getElementById('academicStart');
            const academicEnd = document.getElementById('academicEnd');
            const regStart = document.getElementById('regStart');
            const regEnd = document.getElementById('regEnd');

            // Set minimum dates to today
            const today = new Date().toISOString().split('T')[0];
            academicStart.min = today;
            regStart.min = today;

            // Validate academic dates
            academicStart.addEventListener('change', function() {
                academicEnd.min = this.value;
                // Registration should typically end before or when academic period starts
                regEnd.max = this.value;
            });

            academicEnd.addEventListener('change', function() {
                academicStart.max = this.value;
            });

            // Validate registration dates
            regStart.addEventListener('change', function() {
                regEnd.min = this.value;
            });

            regEnd.addEventListener('change', function() {
                regStart.max = this.value;
            });

            // Form validation before submit
            document.querySelector('form').addEventListener('submit', function(e) {
                const academicStartDate = new Date(academicStart.value);
                const academicEndDate = new Date(academicEnd.value);
                const regStartDate = new Date(regStart.value);
                const regEndDate = new Date(regEnd.value);

                // Check if academic period is valid
                if (academicStartDate >= academicEndDate) {
                    alert('Academic end date must be after the start date.');
                    e.preventDefault();
                    return;
                }

                // Check if registration period is valid
                if (regStartDate >= regEndDate) {
                    alert('Registration end date must be after the start date.');
                    e.preventDefault();
                    return;
                }

                // Warn if registration period extends beyond academic start
                if (regEndDate > academicStartDate) {
                    if (!confirm('Registration period extends beyond the academic start date. This is unusual but allowed. Continue?')) {
                        e.preventDefault();
                        return;
                    }
                }

                // Check if academic period is reasonable (at least 2 months)
                const academicDuration = (academicEndDate - academicStartDate) / (1000 * 60 * 60 * 24);
                if (academicDuration < 60) {
                    if (!confirm('Academic period is less than 2 months. Are you sure this is correct?')) {
                        e.preventDefault();
                        return;
                    }
                }
            });
        });
    </script>
</body>

</html>