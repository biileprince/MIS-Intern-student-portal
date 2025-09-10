<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Get FAQs from database
$faqs = [];
$sql = "SELECT question, answer, category FROM faqs ORDER BY category, faq_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faqs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>FAQs</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php
        if ($_SESSION['role'] == 'admin') {
            include 'admin_sidebar.php';
        } else {
            include 'student_sidebar.php';
        }
        ?>
        <div class="main-content">
            <header class="main-header">
                <h2>Frequently Asked Questions</h2>
            </header>

            <main>
                <div class="faqs-container">
                    <?php if (empty($faqs)) : ?>
                        <p>No FAQs available at the moment.</p>
                    <?php else : ?>
                        <?php
                        $current_category = "";
                        foreach ($faqs as $faq) :
                            if ($faq['category'] != $current_category) :
                                if ($current_category != "") : ?>
                </div>
            <?php endif; ?>
            <div class="faq-category">
                <h3><?php echo ucfirst($faq['category']); ?></h3>
            <?php
                                $current_category = $faq['category'];
                            endif;
            ?>
            <div class="faq-item">
                <div class="faq-question"><?php echo $faq['question']; ?></div>
                <div class="faq-answer"><?php echo $faq['answer']; ?></div>
            </div>
        <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
        </main>
    </div>
    </div>
</body>

</html>