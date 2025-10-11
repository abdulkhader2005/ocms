<?php
require_once '../includes/auth.php';
require_once '../includes/quiz.php';
require_once '../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');

// Set base path for navigation
$basePath = '../';

$quiz = new Quiz($conn);
$course = new Course($conn);
$user = $auth->getCurrentUser();

$attemptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get attempt data
$stmt = $conn->prepare("
    SELECT qa.*, q.title as quiz_title, q.description as quiz_description, 
           c.title as course_title, c.id as course_id
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN courses c ON q.course_id = c.id 
    WHERE qa.id = ? AND qa.student_id = ?
");
$stmt->bind_param("ii", $attemptId, $user['id']);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();

if (!$attempt) {
    header('Location: quizzes.php');
    exit();
}

// Get quiz questions and answers
$questions = $quiz->getQuizQuestions($attempt['quiz_id']);
$answers = [];

foreach ($questions as $question) {
    $stmt = $conn->prepare("
        SELECT qa.*, qo.option_text 
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

// Determine grade
$grade = '';
if ($percentage >= 90) {
    $grade = 'A+';
} elseif ($percentage >= 80) {
    $grade = 'A';
} elseif ($percentage >= 70) {
    $grade = 'B';
} elseif ($percentage >= 60) {
    $grade = 'C';
} elseif ($percentage >= 50) {
    $grade = 'D';
} else {
    $grade = 'F';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Quiz Results</h1>
            <p>Your quiz performance and answers</p>
            <a href="quizzes.php" class="btn btn-secondary">Back to Quizzes</a>
        </div>
        
        <!-- Quiz Summary -->
        <div class="quiz-results-summary">
            <div class="quiz-info">
                <h2><?php echo htmlspecialchars($attempt['quiz_title']); ?></h2>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($attempt['course_title']); ?></p>
                <?php if ($attempt['quiz_description']): ?>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($attempt['quiz_description']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="quiz-stats">
                <div class="stat-card">
                    <h3><?php echo $attempt['score']; ?>/<?php echo $attempt['total_marks']; ?></h3>
                    <p>Score</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $percentage; ?>%</h3>
                    <p>Percentage</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $grade; ?></h3>
                    <p>Grade</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo date('M d, Y H:i', strtotime($attempt['submitted_at'])); ?></h3>
                    <p>Submitted</p>
                </div>
            </div>
        </div>
        
        <!-- Quiz Questions and Answers -->
        <div class="quiz-questions-results">
            <h2>Question Review</h2>
            <?php foreach ($questions as $index => $question): ?>
                <div class="quiz-question-item">
                    <h4>Question <?php echo $index + 1; ?> (<?php echo $question['marks']; ?> marks)</h4>
                    <p><?php echo htmlspecialchars($question['question']); ?></p>
                    
                    <?php 
                    $answer = $answers[$question['id']];
                    $isCorrect = $answer ? (bool)$answer['is_correct'] : false;
                    $marksObtained = $answer ? $answer['marks_obtained'] : 0;
                    
                    // Debug: Let's see what we have
                    // echo "<!-- Debug: Answer data: " . print_r($answer, true) . " -->";
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
                                        <strong>Your Answer: </strong>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                    <?php if ($option['is_correct']): ?>
                                        <span class="correct-indicator">✓ Correct</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                    <?php elseif ($question['question_type'] === 'true_false'): ?>
                        <div class="question-answer">
                            <h5>Your Answer:</h5>
                            <p><strong><?php echo $answer ? ucfirst($answer['answer_text']) : 'No answer'; ?></strong></p>
                        </div>
                        
                    <?php else: // short_answer ?>
                        <div class="question-answer">
                            <h5>Your Answer:</h5>
                            <p><?php echo $answer ? htmlspecialchars($answer['answer_text']) : 'No answer provided'; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="question-feedback">
                        <?php if ($isCorrect && $marksObtained > 0): ?>
                            <span class="correct-feedback">✓ Correct! (+<?php echo $marksObtained; ?> marks)</span>
                        <?php else: ?>
                            <span class="incorrect-feedback">✗ Incorrect (0 marks)</span>
                        <?php endif; ?>
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
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
