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

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quizData = $quiz->getQuiz($quizId);

if (!$quizData) {
    header('Location: quizzes.php');
    exit();
}

// Check if student is enrolled in the course
$courseData = $course->getCourse($quizData['course_id']);
if (!$course->isEnrolled($user['id'], $quizData['course_id'])) {
    header('Location: quizzes.php');
    exit();
}

// Get or create quiz attempt
$attemptId = null;
$attempt = null;

// Check for existing in-progress attempt
$stmt = $conn->prepare("SELECT * FROM quiz_attempts WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'");
$stmt->bind_param("ii", $user['id'], $quizId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $attempt = $result->fetch_assoc();
    $attemptId = $attempt['id'];
} else {
    // Start new attempt
    $result = $quiz->startQuizAttempt($user['id'], $quizId);
    if ($result['success']) {
        $attemptId = $result['attempt_id'];
    } else {
        $error = $result['message'];
    }
}

// Get quiz questions
$questions = $quiz->getQuizQuestions($quizId);
$questionOptions = [];
foreach ($questions as $question) {
    $questionOptions[$question['id']] = $quiz->getQuestionOptions($question['id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'submit_answer') {
        $questionId = (int)$_POST['question_id'];
        $answerText = $_POST['answer_text'] ?? null;
        $selectedOptionId = isset($_POST['selected_option']) ? (int)$_POST['selected_option'] : null;
        
        $quiz->submitAnswer($attemptId, $questionId, $answerText, $selectedOptionId);
    } elseif ($_POST['action'] === 'save_answers' || $_POST['action'] === 'submit_quiz') {
        // Handle bulk answer submission
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $questionId => $answerData) {
                $questionId = (int)$questionId;
                $answerText = isset($answerData['text']) ? $answerData['text'] : null;
                $selectedOptionId = isset($answerData['option']) ? (int)$answerData['option'] : null;
                
                if ($answerText || $selectedOptionId) {
                    $quiz->submitAnswer($attemptId, $questionId, $answerText, $selectedOptionId);
                }
            }
        }
        
        if ($_POST['action'] === 'submit_quiz') {
            $quiz->submitQuiz($attemptId);
            header('Location: quiz-results.php?id=' . $attemptId);
            exit();
        } else {
            // Just save progress, stay on the same page
            $success = 'Progress saved successfully!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="quiz-container">
            <div class="quiz-header">
                <h1><?php echo htmlspecialchars($quizData['title']); ?></h1>
                <p>Course: <?php echo htmlspecialchars($courseData['title']); ?></p>
                <?php if ($quizData['time_limit'] > 0): ?>
                    <div class="quiz-timer" id="timer">
                        Time Remaining: <span id="time-display"></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!isset($error)): ?>
                <form method="POST" id="quiz-form">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="quiz-question">
                            <h3>Question <?php echo $index + 1; ?> (<?php echo $question['marks']; ?> marks)</h3>
                            <p><?php echo htmlspecialchars($question['question']); ?></p>
                            
                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <ul class="quiz-options">
                                    <?php foreach ($questionOptions[$question['id']] as $option): ?>
                                        <li>
                                            <label>
                                                <input type="radio" name="answers[<?php echo $question['id']; ?>][option]" value="<?php echo $option['id']; ?>">
                                                <?php echo htmlspecialchars($option['option_text']); ?>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif ($question['question_type'] === 'true_false'): ?>
                                <ul class="quiz-options">
                                    <li>
                                        <label>
                                            <input type="radio" name="answers[<?php echo $question['id']; ?>][text]" value="true">
                                            True
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input type="radio" name="answers[<?php echo $question['id']; ?>][text]" value="false">
                                            False
                                        </label>
                                    </li>
                                </ul>
                            <?php else: ?>
                                <textarea name="answers[<?php echo $question['id']; ?>][text]" rows="3" 
                                          placeholder="Enter your answer"></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="quiz-actions">
                        <button type="submit" name="action" value="save_answers" class="btn btn-secondary">Save Progress</button>
                        <button type="submit" name="action" value="submit_quiz" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit the quiz?')">Submit Quiz</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let timeLimit = <?php echo $quizData['time_limit']; ?>;
        let timeRemaining = timeLimit * 60; // Convert to seconds
        
        if (timeLimit > 0) {
            const timer = setInterval(function() {
                const hours = Math.floor(timeRemaining / 3600);
                const minutes = Math.floor((timeRemaining % 3600) / 60);
                const seconds = timeRemaining % 60;
                
                document.getElementById('time-display').textContent = 
                    hours.toString().padStart(2, '0') + ':' + 
                    minutes.toString().padStart(2, '0') + ':' + 
                    seconds.toString().padStart(2, '0');
                
                if (timeRemaining <= 0) {
                    clearInterval(timer);
                    document.getElementById('quiz-form').submit();
                }
                
                timeRemaining--;
            }, 1000);
        }
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
