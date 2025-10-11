<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quiz.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$quiz = new Quiz($conn);
$course = new Course($conn);
$user = $auth->getCurrentUser();

// Get student's enrolled courses
$enrolledCourses = $course->getStudentCourses($user['id']);
$courseIds = array_column($enrolledCourses, 'id');

$availableQuizzes = [];
if (!empty($courseIds)) {
    $placeholders = str_repeat('?,', count($courseIds) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT q.*, c.title as course_title 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        WHERE q.course_id IN ($placeholders)
        ORDER BY q.created_at DESC
    ");
    $stmt->bind_param(str_repeat('i', count($courseIds)), ...$courseIds);
    $stmt->execute();
    $availableQuizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get student's quiz attempts
$attempts = $quiz->getStudentAttempts($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Available Quizzes</h1>
            <p>Take quizzes for your enrolled courses</p>
        </div>
        
        <?php if (empty($availableQuizzes)): ?>
            <div class="info-message">
                No quizzes available for your enrolled courses.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Course</th>
                            <th>Time Limit</th>
                            <th>Total Marks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availableQuizzes as $quizData): ?>
                            <?php
                            // Check if student has attempted this quiz
                            $attempted = false;
                            $attempt = null;
                            foreach ($attempts as $attemptData) {
                                if ($attemptData['quiz_id'] == $quizData['id']) {
                                    $attempted = true;
                                    $attempt = $attemptData;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quizData['title']); ?></td>
                                <td><?php echo htmlspecialchars($quizData['course_title']); ?></td>
                                <td>
                                    <?php echo $quizData['time_limit'] > 0 ? $quizData['time_limit'] . ' minutes' : 'No limit'; ?>
                                </td>
                                <td><?php echo $quizData['total_marks']; ?></td>
                                <td>
                                    <?php if ($attempted): ?>
                                        <?php if ($attempt['status'] === 'completed'): ?>
                                            <span class="status-completed">Completed</span>
                                            <br><small>Score: <?php echo $attempt['score']; ?>/<?php echo $attempt['total_marks']; ?></small>
                                        <?php else: ?>
                                            <span class="status-in-progress">In Progress</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-not-attempted">Not Attempted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempted && $attempt['status'] === 'in_progress'): ?>
                                        <a href="take-quiz.php?id=<?php echo $quizData['id']; ?>" class="btn btn-primary">Continue Quiz</a>
                                    <?php elseif (!$attempted): ?>
                                        <a href="take-quiz.php?id=<?php echo $quizData['id']; ?>" class="btn btn-primary">Start Quiz</a>
                                    <?php else: ?>
                                        <a href="quiz-results.php?id=<?php echo $attempt['id']; ?>" class="btn btn-secondary">View Results</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Quiz Attempts History -->
        <?php if (!empty($attempts)): ?>
            <div class="dashboard-content">
                <h2>Quiz History</h2>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Course</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['course_title']); ?></td>
                                    <td><?php echo $attempt['score']; ?>/<?php echo $attempt['total_marks']; ?></td>
                                    <td>
                                        <span class="status-<?php echo $attempt['status']; ?>">
                                            <?php echo ucfirst($attempt['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($attempt['started_at'])); ?></td>
                                    <td>
                                        <?php echo $attempt['submitted_at'] ? date('M d, Y H:i', strtotime($attempt['submitted_at'])) : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
