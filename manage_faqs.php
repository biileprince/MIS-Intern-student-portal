<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

$question = $answer = $category = "";
$question_err = $answer_err = "";
$update_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_faq'])) {
        if (empty(trim($_POST["question"]))) {
            $question_err = "Please enter a question.";
        } else {
            $question = trim($_POST["question"]);
        }

        if (empty(trim($_POST["answer"]))) {
            $answer_err = "Please enter an answer.";
        } else {
            $answer = trim($_POST["answer"]);
        }

        $category = trim($_POST["category"]);

        if (empty($question_err) && empty($answer_err)) {
            $sql = "INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sss", $question, $answer, $category);
                if ($stmt->execute()) {
                    $update_success = "FAQ added successfully!";
                } else {
                    echo "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$faqs = [];
$sql = "SELECT faq_id, question, answer, category FROM faqs";
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
    <title>Manage FAQs</title>
    <link rel="stylesheet" href="style.css?v=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <?php include 'admin_sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Manage FAQs</h1>
            </header>
            <main class="centered-content">
                <div class="form-container">
                    <h3>Add New FAQ</h3>
                    <?php if (!empty($update_success)) : ?>
                        <div class="success"><?php echo $update_success; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="add_faq">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Question</label>
                                <textarea name="question" class="<?php echo (!empty($question_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo $question; ?></textarea>
                                <span class="error"><?php echo $question_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label>Answer</label>
                                <textarea name="answer" class="<?php echo (!empty($answer_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo $answer; ?></textarea>
                                <span class="error"><?php echo $answer_err; ?></span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="category" value="<?php echo $category; ?>" placeholder="e.g., General, Academic, Registration">
                            </div>
                        </div>
                        <button type="submit" class="btn">Add FAQ</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Existing FAQs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faqs as $faq) : ?>
                                <tr>
                                    <td><?php echo $faq['question']; ?></td>
                                    <td><?php echo $faq['category']; ?></td>
                                    <td>
                                        <a href="edit_faq.php?id=<?php echo $faq['faq_id']; ?>" class="btn-edit">Edit</a>
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