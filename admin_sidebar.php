<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3>Admin Panel</h3>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="add_student.php" class="<?php echo $current_page == 'add_student.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Add Student</a>
        <a href="manage_programs.php" class="<?php echo $current_page == 'manage_programs.php' ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Manage Programs</a>
        <a href="manage_courses.php" class="<?php echo $current_page == 'manage_courses.php' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Manage Courses</a>
        <a href="manage_academic_calendar.php" class="<?php echo $current_page == 'manage_academic_calendar.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-plus"></i> Academic Calendar</a>
        <!-- <a href="manage_registration.php" class="<?php echo $current_page == 'manage_registration.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Course Registration</a> -->
        <a href="manage_faqs.php" class="<?php echo $current_page == 'manage_faqs.php' ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Manage FAQs</a>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>