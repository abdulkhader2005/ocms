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

$attemptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get attempt data with student info
$stmt = $conn->prepare("
    SELECT qa.*, q.title as quiz_title, q.course_id, c.title as course_title,
           u.first_name, u.last_name, u.username
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN courses c ON q.course_id = c.id 
    JOIN users u ON qa.student_id = u.id
    WHERE qa.id = ?
");
$stmt->bind_param("i", $attemptId);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();

if (!$attempt) {
    header('Location: courses.php');
    exit();
}

// Check if instructor owns this course
if ($attempt['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

// Get quiz questions and student answers
$questions = $quiz->getQuizQuestions($attempt['quiz_id']);
$answers = [];

foreach ($questions as $question) {
    $stmt = $conn->prepare("
        SELECT qa.*, qo.option_text, qo.is_correct 
        FROM quiz_answers qa 
        LEFT JOIN quiz_options qo ON qa.selected_option_id = qo.id 
        WHERE qa.attempt_id = ? AND qa.question_id = ?
    ");
    $stmt->bind_param("ii", $attemptId, $question['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $answers[$question['id']] = $result->fetch_assoc();
}

// Calculate percentage
$percentage = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Attempt Details - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Quiz Attempt Details</h1>
            <p>Detailed view of student's quiz attempt and answers</p>
            <div class="action-buttons">
                <a href="quiz-results.php?id=<?php echo $attempt['quiz_id']; ?>" class="btn btn-secondary">Back to Results</a>
            </div>
        </div>
        
        <!-- Student and Quiz Info -->
        <div class="quiz-attempt-info">
            <div class="attempt-summary">
                <h2>Student: <?php echo htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']); ?></h2>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($attempt['username']); ?></p>
                <p><strong>Quiz:</strong> <?php echo htmlspecialchars($attempt['quiz_title']); ?></p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($attempt['course_title']); ?></p>
                <p><strong>Score:</strong> <?php echo $attempt['score']; ?>/<?php echo $attempt['total_marks']; ?> (<?php echo $percentage; ?>%)</p>
                <p><strong>Status:</strong> <span class="status-<?php echo $attempt['status']; ?>"><?php echo ucfirst($attempt['status']); ?></span></p>
                <p><strong>Submitted:</strong> <?php echo $attempt['submitted_at'] ? date('M d, Y H:i', strtotime($attempt['submitted_at'])) : 'Not submitted'; ?></p>
            </div>
        </div>
        
        <!-- Questions and Answers -->
        <div class="quiz-questions-review">
            <h2>Question Review</h2>
            <?php foreach ($questions as $index => $question): ?>
                <div class="quiz-question-item">
                    <h4>Question <?php echo $index + 1; ?> (<?php echo $question['marks']; ?> marks)</h4>
                    <p><?php echo htmlspecialchars($question['question']); ?></p>
                    
                    <?php 
                    $answer = $answers[$question['id']];
                    $isCorrect = $answer ? $answer['is_correct'] : false;
                    $marksObtained = $answer ? $answer['marks_obtained'] : 0;
                    ?>
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <div class="question-options">
                            <h5>Options:</h5>
                            <?php
                            $options = $quiz->getQuestionOptions($question['id']);
                            foreach ($options as $option):
                            ?>
                                <div class="option-item <?php echo $option['is_correct'] ? 'correct' : ''; ?>">
                                    <?php if ($answer && $answer['selected_option_id'] == $option['id']): ?>
                                        <strong>Student Selected: </strong>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                    <?php if ($option['is_correct']): ?>
                                        <span class="correct-indicator">✓ Correct Answer</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php elseif ($question['question_type'] === 'true_false'): ?>
                        <div class="question-answer">
                            <h5>Student Answer:</h5>
                            <p><strong><?php echo $answer ? ucfirst($answer['answer_text']) : 'No answer'; ?></strong></p>
                        </div>
                        
                    <?php else: // short_answer ?>
                        <div class="question-answer">
                            <h5>Student Answer:</h5>
                            <p><?php echo $answer ? htmlspecialchars($answer['answer_text']) : 'No answer provided'; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="question-feedback">
                        <div class="marks-info">
                            <strong>Marks Obtained:</strong> <?php echo $marksObtained; ?>/<?php echo $question['marks']; ?>
                        </div>
                        <div class="correctness-status">
                            <?php if ($isCorrect): ?>
                                <span class="correct-feedback">✓ Correct Answer</span>
                            <?php else: ?>
                                <span class="incorrect-feedback">✗ Incorrect Answer</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Performance Summary -->
        <div class="performance-summary">
            <h2>Performance Summary</h2>
            <div class="summary-stats">
                <div class="summary-item">
                    <strong>Total Questions:</strong> <?php echo count($questions); ?>
                </div>
                <div class="summary-item">
                    <strong>Correct Answers:</strong> <?php echo $attempt['score']; ?>
                </div>
                <div class="summary-item">
                    <strong>Incorrect Answers:</strong> <?php echo count($questions) - $attempt['score']; ?>
                </div>
                <div class="summary-item">
                    <strong>Accuracy:</strong> <?php echo round(($attempt['score'] / count($questions)) * 100, 1); ?>%
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
