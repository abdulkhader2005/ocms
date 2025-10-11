<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';
require_once __DIR__ . '/../includes/quiz.php';

$auth = new Auth($conn);
$auth->requireRole('instructor');

// Set base path for navigation
$basePath = '../';

$course = new Course($conn);
$quiz = new Quiz($conn);
$user = $auth->getCurrentUser();

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quizData = $quiz->getQuiz($quizId);

if (!$quizData) {
    header('Location: courses.php');
    exit();
}

// Get course data to verify ownership
$courseData = $course->getCourse($quizData['course_id']);

// Check if instructor owns this course
if (!$courseData || $courseData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $timeLimit = (int)$_POST['time_limit'];
    $totalMarks = (int)$_POST['total_marks'];
    
    $stmt = $conn->prepare("UPDATE quizzes SET title = ?, description = ?, time_limit = ?, total_marks = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $title, $description, $timeLimit, $totalMarks, $quizId);
    
    if ($stmt->execute()) {
        $success = 'Quiz updated successfully';
        // Refresh quiz data
        $quizData = $quiz->getQuiz($quizId);
    } else {
        $error = 'Failed to update quiz: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Edit Quiz</h1>
            <p>Update quiz information</p>
            <a href="course-quizzes.php?course_id=<?php echo $quizData['course_id']; ?>" class="btn btn-secondary">Back to Quizzes</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($quizData['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($quizData['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="time_limit">Time Limit (minutes, 0 for no limit)</label>
                        <input type="number" id="time_limit" name="time_limit" min="0" value="<?php echo $quizData['time_limit']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks" name="total_marks" min="0" value="<?php echo $quizData['total_marks']; ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Quiz</button>
                    <a href="course-quizzes.php?course_id=<?php echo $quizData['course_id']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
