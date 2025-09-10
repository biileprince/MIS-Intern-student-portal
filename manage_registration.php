<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

$message = "";

// Handle new session creation using academic calendar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_session'])) {
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
        $session_name = $calendar['academic_year'] . " - Semester " . $calendar['semester'];
        
        // Check if session already exists for this calendar
        $sql = "SELECT session_id FROM registration_sessions WHERE calendar_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $calendar_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing session to be active
            $session = $result->fetch_assoc();
            $sql = "UPDATE registration_sessions SET is_active = 1 WHERE session_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $session['session_id']);
        } else {
            // Create new session
            $sql = "INSERT INTO registration_sessions (session_name, start_date, end_date, is_active, calendar_id) VALUES (?, ?, ?, 1, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $session_name, $calendar['registration_start'], $calendar['registration_end'], $calendar_id);
        }
        
        if ($stmt->execute()) {
            $message = "Registration session activated successfully! Academic calendar: " . $session_name;
        } else {
            $message = "Error creating/activating session: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle session activation/deactivation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_session'])) {
    $session_id = $_POST['session_id'];

    // First deactivate all sessions
    $sql = "UPDATE registration_sessions SET is_active = 0";
    $conn->query($sql);

    // Then activate the selected session
    $sql = "UPDATE registration_sessions SET is_active = 1 WHERE session_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $session_id);
        if ($stmt->execute()) {
            $message = "Session activated successfully!";
        } else {
            $message = "Error activating session: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all academic calendars for dropdown
$sql = "SELECT * FROM academic_calendar ORDER BY academic_year DESC, semester DESC";
$academic_calendars = $conn->query($sql);

// Get all registration sessions with their academic calendar info
$sql = "SELECT rs.*, ac.academic_year, ac.semester, ac.start_date as academic_start, ac.end_date as academic_end
        FROM registration_sessions rs
        LEFT JOIN academic_calendar ac ON rs.calendar_id = ac.calendar_id
        ORDER BY rs.is_active DESC, rs.session_id DESC";
$sessions = $conn->query($sql);
?>

// Get all registration sessions
$sessions = [];
$sql = "SELECT rs.*, 
               COUNT(DISTINCT sc.student_id) as registered_students,
               COUNT(sc.course_id) as total_registrations
        FROM registration_sessions rs
        LEFT JOIN student_courses sc ON rs.session_id = sc.session_id
        GROUP BY rs.session_id
        ORDER BY rs.session_id DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Course Registration</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include 'admin_sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <div class="header-content">
                    <div class="header-title">
                        <h2><i class="fas fa-calendar-check"></i> Manage Course Registration</h2>
                        <p class="header-subtitle">Control when students can register for courses by activating registration sessions</p>
                    </div>
                </div>
            </header>
            <main>
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

                <!-- Information Box -->
                <div class="info-box">
                    <div class="info-header">
                        <i class="fas fa-info-circle"></i>
                        <h4>What is Course Registration Management?</h4>
                    </div>
                    <div class="info-content">
                        <p><strong>Purpose:</strong> This page allows you to control when students can register for courses by activating registration sessions tied to academic calendars.</p>
                        <ul>
                            <li><strong>Step 1:</strong> Create academic calendars (with registration periods) in the Academic Calendar section</li>
                            <li><strong>Step 2:</strong> Activate a registration session here by selecting an academic calendar</li>
                            <li><strong>Step 3:</strong> Students can then register for courses during the active session</li>
                        </ul>
                        <p><strong>Note:</strong> Only one registration session can be active at a time. When you activate a new session, the previous one is automatically deactivated.</p>
                    </div>
                </div>

                <?php if (!empty($message)) : ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Activate Registration Session -->
                <div class="form-container">
                    <h3><i class="fas fa-calendar-check"></i> Activate Registration Session</h3>
                    <p class="form-description">Select an academic calendar to activate registration for that period. The registration dates will be automatically set based on the academic calendar.</p>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registrationForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Academic Calendar</label>
                                <select name="calendar_id" required onchange="updateRegistrationDates(this.value)">
                                    <option value="">Select Academic Calendar</option>
                                    <?php if ($academic_calendars->num_rows > 0) : ?>
                                        <?php while ($calendar = $academic_calendars->fetch_assoc()) : ?>
                                            <option value="<?php echo $calendar['calendar_id']; ?>" 
                                                    data-registration-start="<?php echo date('M d, Y', strtotime($calendar['registration_start'])); ?>"
                                                    data-registration-end="<?php echo date('M d, Y', strtotime($calendar['registration_end'])); ?>"
                                                    data-academic-start="<?php echo date('M d, Y', strtotime($calendar['start_date'])); ?>"
                                                    data-academic-end="<?php echo date('M d, Y', strtotime($calendar['end_date'])); ?>">
                                                <?php echo $calendar['academic_year'] . " - Semester " . $calendar['semester']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Registration Start Date</label>
                                <input type="text" id="regStartDate" readonly placeholder="Select academic calendar first" class="readonly-input">
                            </div>
                            <div class="form-group">
                                <label>Registration End Date</label>
                                <input type="text" id="regEndDate" readonly placeholder="Select academic calendar first" class="readonly-input">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Academic Period</label>
                                <input type="text" id="academicPeriod" readonly placeholder="Select academic calendar first" class="readonly-input">
                            </div>
                        </div>

                        <div class="form-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Activating this session will automatically deactivate all other registration sessions. Only one session can be active at a time.</span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="create_session" class="btn btn-primary">
                                <i class="fas fa-play"></i> Activate Registration Session
                            </button>
                            <a href="manage_academic_calendar.php" class="btn btn-secondary">
                                <i class="fas fa-calendar-plus"></i> Manage Academic Calendar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Existing Sessions -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Registration Sessions</h3>
                    </div>
                    <div class="responsive-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Session Name</th>
                                    <th>Academic Period</th>
                                    <th>Registration Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sessions->num_rows == 0) : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No registration sessions found.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php while ($session = $sessions->fetch_assoc()) : ?>
                                        <tr>
                                            <td data-label="Session"><?php echo htmlspecialchars($session['session_name']); ?></td>
                                            <td data-label="Academic Period">
                                                <?php if ($session['academic_year']) : ?>
                                                    <?php echo date('M d, Y', strtotime($session['academic_start'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($session['academic_end'])); ?>
                                                <?php else : ?>
                                                    <span class="text-muted">No academic calendar linked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Registration Period">
                                                <?php echo date('M d, Y', strtotime($session['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($session['end_date'])); ?>
                                            </td>
                                            <td data-label="Status">
                                                <?php if ($session['is_active']) : ?>
                                                    <span class="status-badge status-active">Active</span>
                                                <?php else : ?>
                                                    <span class="status-badge status-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Actions">
                                                <?php if (!$session['is_active']) : ?>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                        <button type="submit" name="toggle_session" class="btn btn-sm btn-primary" 
                                                                onclick="return confirm('Activate this registration session? This will deactivate all other sessions.')">
                                                            <i class="fas fa-play"></i> Activate
                                                        </button>
                                                    </form>
                                                <?php else : ?>
                                                    <span class="text-muted">Currently Active</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updateRegistrationDates(calendarId) {
            const select = document.querySelector('select[name="calendar_id"]');
            const selectedOption = select.options[select.selectedIndex];
            
            if (calendarId && selectedOption) {
                // Update the readonly inputs with the selected calendar dates
                document.getElementById('regStartDate').value = selectedOption.getAttribute('data-registration-start');
                document.getElementById('regEndDate').value = selectedOption.getAttribute('data-registration-end');
                
                const academicStart = selectedOption.getAttribute('data-academic-start');
                const academicEnd = selectedOption.getAttribute('data-academic-end');
                document.getElementById('academicPeriod').value = academicStart + ' - ' + academicEnd;
            } else {
                // Clear the fields if no calendar is selected
                document.getElementById('regStartDate').value = '';
                document.getElementById('regEndDate').value = '';
                document.getElementById('academicPeriod').value = '';
            }
        }
    </script>
</body>

</html>
                                        <tr>
                                            <td data-label="Session"><?php echo htmlspecialchars($session['session_name']); ?></td>
                                            <td data-label="Period">
                                                <?php echo date('M d, Y', strtotime($session['start_date'])); ?> -
                                                <?php echo date('M d, Y', strtotime($session['end_date'])); ?>
                                            </td>
                                            <td data-label="Status">
                                                <span class="status-badge <?php echo $session['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $session['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td data-label="Students"><?php echo $session['registered_students']; ?></td>
                                            <td data-label="Registrations"><?php echo $session['total_registrations']; ?></td>
                                            <td data-label="Actions" class="actions">
                                                <?php if (!$session['is_active']) : ?>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                        <button type="submit" name="toggle_session" class="btn-action btn-activate"
                                                            title="Activate Session"
                                                            onclick="return confirm('This will deactivate all other sessions. Continue?')">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                <?php else : ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updateRegistrationDates(calendarId) {
            const select = document.querySelector('select[name="calendar_id"]');
            const selectedOption = select.options[select.selectedIndex];
            
            if (calendarId && selectedOption) {
                // Update the readonly inputs with the selected calendar dates
                document.getElementById('regStartDate').value = selectedOption.getAttribute('data-registration-start');
                document.getElementById('regEndDate').value = selectedOption.getAttribute('data-registration-end');
                
                const academicStart = selectedOption.getAttribute('data-academic-start');
                const academicEnd = selectedOption.getAttribute('data-academic-end');
                document.getElementById('academicPeriod').value = academicStart + ' - ' + academicEnd;
            } else {
                // Clear the fields if no calendar is selected
                document.getElementById('regStartDate').value = '';
                document.getElementById('regEndDate').value = '';
                document.getElementById('academicPeriod').value = '';
            }
        }
    </script>
</body>

</html>