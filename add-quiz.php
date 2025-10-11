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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $timeLimit = (int)$_POST['time_limit'];
    $totalMarks = (int)$_POST['total_marks'];
    
    $result = $quiz->createQuiz($courseId, $title, $description, $timeLimit, $totalMarks);
    
    if ($result['success']) {
        $success = $result['message'];
        $quizId = $result['quiz_id'];
        
        // Add questions
        $questions = $_POST['questions'];
        foreach ($questions as $index => $questionData) {
            if (!empty($questionData['question'])) {
                $questionResult = $quiz->addQuestion(
                    $quizId, 
                    $questionData['question'], 
                    $questionData['type'], 
                    (int)$questionData['marks'], 
                    $index
                );
                
                if ($questionResult['success']) {
                    $questionId = $questionResult['question_id'];
                    
                    // Add options for multiple choice questions
                    if ($questionData['type'] === 'multiple_choice' && isset($questionData['options'])) {
                        foreach ($questionData['options'] as $optionIndex => $optionText) {
                            if (!empty($optionText)) {
                                $isCorrect = isset($questionData['correct_option']) && $questionData['correct_option'] == $optionIndex;
                                $quiz->addOption($questionId, $optionText, $isCorrect);
                            }
                        }
                    }
                }
            }
        }
        
        header('Location: course-quizzes.php?course_id=' . $courseId);
        exit();
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
    <title>Add Quiz - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Add Quiz: <?php echo htmlspecialchars($courseData['title']); ?></h1>
            <p>Create a new quiz for your course</p>
            <a href="course-quizzes.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">Back to Quizzes</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" id="quiz-form">
                <div class="form-group">
                    <label for="title">Quiz Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="time_limit">Time Limit (minutes, 0 for no limit)</label>
                        <input type="number" id="time_limit" name="time_limit" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks" name="total_marks" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <h3>Questions</h3>
                    <div id="questions-container">
                        <div class="question-item">
                            <div class="form-group">
                                <label>Question 1</label>
                                <input type="text" name="questions[0][question]" placeholder="Enter question" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select name="questions[0][type]" onchange="toggleOptions(0)">
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="true_false">True/False</option>
                                        <option value="short_answer">Short Answer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Marks</label>
                                    <input type="number" name="questions[0][marks]" min="1" value="1">
                                </div>
                            </div>
                            <div class="options-container" id="options-0">
                                <label>Options:</label>
                                <input type="text" name="questions[0][options][0]" placeholder="Option 1">
                                <input type="text" name="questions[0][options][1]" placeholder="Option 2">
                                <input type="text" name="questions[0][options][2]" placeholder="Option 3">
                                <input type="text" name="questions[0][options][3]" placeholder="Option 4">
                                <label>Correct Option:</label>
                                <select name="questions[0][correct_option]">
                                    <option value="0">Option 1</option>
                                    <option value="1">Option 2</option>
                                    <option value="2">Option 3</option>
                                    <option value="3">Option 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addQuestion()" class="btn btn-secondary">Add Question</button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Quiz</button>
                    <a href="course-quizzes.php?course_id=<?php echo $courseId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let questionCount = 1;
        
        function addQuestion() {
            const container = document.getElementById('questions-container');
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item';
            questionDiv.innerHTML = `
                <div class="form-group">
                    <label>Question ${questionCount + 1}</label>
                    <input type="text" name="questions[${questionCount}][question]" placeholder="Enter question" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Type</label>
                        <select name="questions[${questionCount}][type]" onchange="toggleOptions(${questionCount})">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Marks</label>
                        <input type="number" name="questions[${questionCount}][marks]" min="1" value="1">
                    </div>
                </div>
                <div class="options-container" id="options-${questionCount}">
                    <label>Options:</label>
                    <input type="text" name="questions[${questionCount}][options][0]" placeholder="Option 1">
                    <input type="text" name="questions[${questionCount}][options][1]" placeholder="Option 2">
                    <input type="text" name="questions[${questionCount}][options][2]" placeholder="Option 3">
                    <input type="text" name="questions[${questionCount}][options][3]" placeholder="Option 4">
                    <label>Correct Option:</label>
                    <select name="questions[${questionCount}][correct_option]">
                        <option value="0">Option 1</option>
                        <option value="1">Option 2</option>
                        <option value="2">Option 3</option>
                        <option value="3">Option 4</option>
                    </select>
                </div>
                <button type="button" onclick="removeQuestion(this)" class="btn btn-danger">Remove Question</button>
            `;
            container.appendChild(questionDiv);
            questionCount++;
        }
        
        function removeQuestion(button) {
            button.parentElement.remove();
        }
        
        function toggleOptions(questionIndex) {
            const type = document.querySelector(`select[name="questions[${questionIndex}][type]"]`).value;
            const optionsContainer = document.getElementById(`options-${questionIndex}`);
            
            if (type === 'multiple_choice') {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        }
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
