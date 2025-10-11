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

$questions = $quiz->getQuizQuestions($quizId);
$questionOptions = [];
foreach ($questions as $question) {
    $questionOptions[$question['id']] = $quiz->getQuestionOptions($question['id']);
}

$error = '';
$success = '';

// Handle question deletion
if (isset($_GET['delete_question'])) {
    $questionId = (int)$_GET['delete_question'];
    
    $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
    $stmt->bind_param("ii", $questionId, $quizId);
    
    if ($stmt->execute()) {
        $success = 'Question deleted successfully';
        // Refresh questions list
        $questions = $quiz->getQuizQuestions($quizId);
        $questionOptions = [];
        foreach ($questions as $question) {
            $questionOptions[$question['id']] = $quiz->getQuestionOptions($question['id']);
        }
    } else {
        $error = 'Failed to delete question';
    }
}

// Handle form submission for adding questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $questionText = $_POST['question'];
    $questionType = $_POST['question_type'];
    $marks = (int)$_POST['marks'];
    $orderIndex = (int)$_POST['order_index'];
    
    $result = $quiz->addQuestion($quizId, $questionText, $questionType, $marks, $orderIndex);
    
    if ($result['success']) {
        $questionId = $result['question_id'];
        
        // Add options for multiple choice questions
        if ($questionType === 'multiple_choice' && isset($_POST['options'])) {
            $options = $_POST['options'];
            $correctOption = (int)$_POST['correct_option'];
            
            foreach ($options as $index => $optionText) {
                if (!empty($optionText)) {
                    $isCorrect = ($index === $correctOption);
                    $quiz->addOption($questionId, $optionText, $isCorrect);
                }
            }
        }
        
        $success = 'Question added successfully';
        // Refresh questions list
        $questions = $quiz->getQuizQuestions($quizId);
        $questionOptions = [];
        foreach ($questions as $question) {
            $questionOptions[$question['id']] = $quiz->getQuestionOptions($question['id']);
        }
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Questions - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Questions for: <?php echo htmlspecialchars($quizData['title']); ?></h1>
            <p>Manage quiz questions and answers</p>
            <div class="action-buttons">
                <a href="course-quizzes.php?course_id=<?php echo $quizData['course_id']; ?>" class="btn btn-secondary">Back to Quizzes</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Add Question Form -->
        <div class="form-container">
            <h2>Add New Question</h2>
            <form method="POST" id="add-question-form">
                <input type="hidden" name="action" value="add_question">
                
                <div class="form-group">
                    <label for="question">Question Text</label>
                    <textarea id="question" name="question" rows="3" placeholder="Enter your question here..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="question_type">Question Type</label>
                        <select id="question_type" name="question_type" required onchange="toggleOptions()">
                            <option value="">Select Question Type</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marks">Marks</label>
                        <input type="number" id="marks" name="marks" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="order_index">Order</label>
                        <input type="number" id="order_index" name="order_index" min="0" value="0">
                    </div>
                </div>
                
                <div id="options-container" style="display: none;">
                    <h4>Multiple Choice Options</h4>
                    <div class="options-list">
                        <div class="option-input-group">
                            <input type="text" name="options[0]" placeholder="Option 1" class="option-input" required>
                            <span class="option-number">1</span>
                        </div>
                        <div class="option-input-group">
                            <input type="text" name="options[1]" placeholder="Option 2" class="option-input" required>
                            <span class="option-number">2</span>
                        </div>
                        <div class="option-input-group">
                            <input type="text" name="options[2]" placeholder="Option 3" class="option-input">
                            <span class="option-number">3</span>
                        </div>
                        <div class="option-input-group">
                            <input type="text" name="options[3]" placeholder="Option 4" class="option-input">
                            <span class="option-number">4</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correct_option">Correct Option:</label>
                        <select id="correct_option" name="correct_option" required>
                            <option value="0">Option 1</option>
                            <option value="1">Option 2</option>
                            <option value="2">Option 3</option>
                            <option value="3">Option 4</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Question</button>
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>
        
        <!-- Questions List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Quiz Questions</h2>
                <button type="button" class="btn btn-primary" onclick="scrollToForm()">+ Add New Question</button>
            </div>
            <?php if (empty($questions)): ?>
                <div class="info-message">
                    No questions added yet. Use the form above to add your first question.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Marks</th>
                            <th>Options</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td><?php echo $question['order_index']; ?></td>
                                <td><?php echo htmlspecialchars($question['question']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></td>
                                <td><?php echo $question['marks']; ?></td>
                                <td>
                                    <?php if (isset($questionOptions[$question['id']])): ?>
                                        <ul>
                                            <?php foreach ($questionOptions[$question['id']] as $option): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                    <?php if ($option['is_correct']): ?>
                                                        <span class="correct-option">✓</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit-question.php?id=<?php echo $question['id']; ?>" class="btn btn-secondary">Edit</a>
                                    <a href="?id=<?php echo $quizId; ?>&delete_question=<?php echo $question['id']; ?>" 
                                       class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleOptions() {
            const type = document.getElementById('question_type').value;
            const optionsContainer = document.getElementById('options-container');
            
            if (type === 'multiple_choice') {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
        
        function scrollToForm() {
            document.getElementById('add-question-form').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
            // Focus on the question textarea
            document.getElementById('question').focus();
        }
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
