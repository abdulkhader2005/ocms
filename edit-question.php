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

$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get question data
$stmt = $conn->prepare("
    SELECT qq.*, q.title as quiz_title, q.course_id, c.title as course_title 
    FROM quiz_questions qq 
    JOIN quizzes q ON qq.quiz_id = q.id 
    JOIN courses c ON q.course_id = c.id 
    WHERE qq.id = ?
");
$stmt->bind_param("i", $questionId);
$stmt->execute();
$questionData = $stmt->get_result()->fetch_assoc();

if (!$questionData) {
    header('Location: courses.php');
    exit();
}

// Check if instructor owns this course
if ($questionData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

// Get existing options for this question
$options = $quiz->getQuestionOptions($questionId);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionText = $_POST['question'];
    $questionType = $_POST['question_type'];
    $marks = (int)$_POST['marks'];
    $orderIndex = (int)$_POST['order_index'];
    
    // Update question
    $stmt = $conn->prepare("UPDATE quiz_questions SET question = ?, question_type = ?, marks = ?, order_index = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $questionText, $questionType, $marks, $orderIndex, $questionId);
    
    if ($stmt->execute()) {
        // Handle options for multiple choice questions
        if ($questionType === 'multiple_choice') {
            // Delete existing options
            $stmt = $conn->prepare("DELETE FROM quiz_options WHERE question_id = ?");
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            
            // Add new options
            if (isset($_POST['options'])) {
                $options = $_POST['options'];
                $correctOption = (int)$_POST['correct_option'];
                
                foreach ($options as $index => $optionText) {
                    if (!empty($optionText)) {
                        $isCorrect = ($index === $correctOption);
                        $quiz->addOption($questionId, $optionText, $isCorrect);
                    }
                }
            }
        }
        
        $success = 'Question updated successfully';
        // Refresh question data
        $stmt = $conn->prepare("
            SELECT qq.*, q.title as quiz_title, q.course_id, c.title as course_title 
            FROM quiz_questions qq 
            JOIN quizzes q ON qq.quiz_id = q.id 
            JOIN courses c ON q.course_id = c.id 
            WHERE qq.id = ?
        ");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $questionData = $stmt->get_result()->fetch_assoc();
        $options = $quiz->getQuestionOptions($questionId);
    } else {
        $error = 'Failed to update question: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Edit Question</h1>
            <p>Update quiz question information</p>
            <div class="action-buttons">
                <a href="quiz-questions.php?id=<?php echo $questionData['quiz_id']; ?>" class="btn btn-secondary">Back to Questions</a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="question-info">
                <h3>Quiz: <?php echo htmlspecialchars($questionData['quiz_title']); ?></h3>
                <p>Course: <?php echo htmlspecialchars($questionData['course_title']); ?></p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="question">Question</label>
                    <textarea id="question" name="question" rows="3" required><?php echo htmlspecialchars($questionData['question']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="question_type">Question Type</label>
                        <select id="question_type" name="question_type" required onchange="toggleOptions()">
                            <option value="multiple_choice" <?php echo $questionData['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="true_false" <?php echo $questionData['question_type'] === 'true_false' ? 'selected' : ''; ?>>True/False</option>
                            <option value="short_answer" <?php echo $questionData['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="marks">Marks</label>
                        <input type="number" id="marks" name="marks" min="1" value="<?php echo $questionData['marks']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="order_index">Order</label>
                        <input type="number" id="order_index" name="order_index" min="0" value="<?php echo $questionData['order_index']; ?>">
                    </div>
                </div>
                
                <div id="options-container" <?php echo $questionData['question_type'] === 'multiple_choice' ? '' : 'style="display: none;"'; ?>>
                    <label>Options:</label>
                    <div id="options-list">
                        <?php if ($questionData['question_type'] === 'multiple_choice' && !empty($options)): ?>
                            <?php foreach ($options as $index => $option): ?>
                                <div class="option-input-group">
                                    <input type="text" name="options[<?php echo $index; ?>]" 
                                           value="<?php echo htmlspecialchars($option['option_text']); ?>" 
                                           placeholder="Option <?php echo $index + 1; ?>" class="option-input">
                                    <?php if ($option['is_correct']): ?>
                                        <span class="correct-option">✓ Correct</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="option-input-group">
                                <input type="text" name="options[0]" placeholder="Option 1" class="option-input">
                            </div>
                            <div class="option-input-group">
                                <input type="text" name="options[1]" placeholder="Option 2" class="option-input">
                            </div>
                            <div class="option-input-group">
                                <input type="text" name="options[2]" placeholder="Option 3" class="option-input">
                            </div>
                            <div class="option-input-group">
                                <input type="text" name="options[3]" placeholder="Option 4" class="option-input">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <label for="correct_option">Correct Option:</label>
                    <select id="correct_option" name="correct_option">
                        <option value="0">Option 1</option>
                        <option value="1">Option 2</option>
                        <option value="2">Option 3</option>
                        <option value="3">Option 4</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Question</button>
                    <a href="quiz-questions.php?id=<?php echo $questionData['quiz_id']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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
        
        // Set correct option if editing existing question
        <?php if ($questionData['question_type'] === 'multiple_choice' && !empty($options)): ?>
            <?php foreach ($options as $index => $option): ?>
                <?php if ($option['is_correct']): ?>
                    document.getElementById('correct_option').value = '<?php echo $index; ?>';
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
