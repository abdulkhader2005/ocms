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

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$courseData = $course->getCourse($courseId);

// Check if instructor owns this course
if (!$courseData || $courseData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

$quizzes = $quiz->getCourseQuizzes($courseId);
$error = '';
$success = '';

// Get success message from URL if redirected
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle quiz deletion
if (isset($_GET['delete_quiz'])) {
    $quizId = (int)$_GET['delete_quiz'];
    
    // Delete quiz (this will cascade delete questions and options)
    $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = ? AND course_id = ?");
    $stmt->bind_param("ii", $quizId, $courseId);
    
    if ($stmt->execute()) {
        $success = 'Quiz deleted successfully';
        // Refresh quiz list
        $quizzes = $quiz->getCourseQuizzes($courseId);
    } else {
        $error = 'Failed to delete quiz';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Quizzes - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Quizzes for: <?php echo htmlspecialchars($courseData['title']); ?></h1>
            <p>Manage quizzes for this course</p>
            <div class="action-buttons">
                <a href="add-quiz.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary">Add New Quiz</a>
                <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (empty($quizzes)): ?>
            <div class="info-message">
                No quizzes created for this course yet. <a href="add-quiz.php?course_id=<?php echo $courseId; ?>">Create your first quiz</a> to get started.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Description</th>
                            <th>Time Limit</th>
                            <th>Total Marks</th>
                            <th>Questions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quizData): ?>
                            <?php
                            // Get question count for this quiz
                            $stmt = $conn->prepare("SELECT COUNT(*) as question_count FROM quiz_questions WHERE quiz_id = ?");
                            $stmt->bind_param("i", $quizData['id']);
                            $stmt->execute();
                            $questionCount = $stmt->get_result()->fetch_assoc()['question_count'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quizData['title']); ?></td>
                                <td><?php echo htmlspecialchars(substr($quizData['description'], 0, 50)) . '...'; ?></td>
                                <td>
                                    <?php echo $quizData['time_limit'] > 0 ? $quizData['time_limit'] . ' minutes' : 'No limit'; ?>
                                </td>
                                <td><?php echo $quizData['total_marks']; ?></td>
                                <td><?php echo $questionCount; ?></td>
                                <td><?php echo date('M d, Y', strtotime($quizData['created_at'])); ?></td>
                                <td>
                                    <a href="edit-quiz.php?id=<?php echo $quizData['id']; ?>" class="btn btn-secondary">Edit</a>
                                    <a href="quiz-questions.php?id=<?php echo $quizData['id']; ?>" class="btn btn-primary">Questions</a>
                                    <a href="quiz-results.php?id=<?php echo $quizData['id']; ?>" class="btn btn-success">Results</a>
                                    <a href="?course_id=<?php echo $courseId; ?>&delete_quiz=<?php echo $quizData['id']; ?>" 
                                       class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this quiz?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
