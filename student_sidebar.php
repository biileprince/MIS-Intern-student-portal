<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3>Student Portal</h3>
    </div>
    <div class="sidebar-menu">
        <a href="portal.php" class="<?php echo $current_page == 'portal.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="personal_info.php" class="<?php echo $current_page == 'personal_info.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> Personal Information</a>
        <a href="course_registration.php" class="<?php echo $current_page == 'course_registration.php' ? 'active' : ''; ?>"><i class="fas fa-book-open"></i> Course Registration</a>
        <a href="ghana_card.php" class="<?php echo $current_page == 'ghana_card.php' ? 'active' : ''; ?>"><i class="fas fa-id-card"></i> Ghana Card</a>
        <a href="reset_password.php" class="<?php echo $current_page == 'reset_password.php' ? 'active' : ''; ?>"><i class="fas fa-key"></i> Reset Password</a>
        <a href="faqs.php" class="<?php echo $current_page == 'faqs.php' ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> FAQs</a>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>