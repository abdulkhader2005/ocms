<?php
require_once __DIR__ . '/../config/database.php';

class Quiz {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function createQuiz($courseId, $title, $description, $timeLimit = 0, $totalMarks = 0) {
        $stmt = $this->conn->prepare("INSERT INTO quizzes (course_id, title, description, time_limit, total_marks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $courseId, $title, $description, $timeLimit, $totalMarks);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Quiz created successfully', 'quiz_id' => $this->conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create quiz'];
        }
    }
    
    public function addQuestion($quizId, $question, $questionType, $marks = 1, $orderIndex = 0) {
        $stmt = $this->conn->prepare("INSERT INTO quiz_questions (quiz_id, question, question_type, marks, order_index) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $quizId, $question, $questionType, $marks, $orderIndex);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Question added successfully', 'question_id' => $this->conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to add question'];
        }
    }
    
    public function addOption($questionId, $optionText, $isCorrect = false) {
        $stmt = $this->conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $questionId, $optionText, $isCorrect);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Option added successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to add option'];
        }
    }
    
    public function getQuiz($quizId) {
        $stmt = $this->conn->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getQuizQuestions($quizId) {
        $stmt = $this->conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY order_index ASC");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getQuestionOptions($questionId) {
        $stmt = $this->conn->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getCourseQuizzes($courseId) {
        $stmt = $this->conn->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function startQuizAttempt($studentId, $quizId) {
        // Check if there's already an in-progress attempt
        $stmt = $this->conn->prepare("SELECT id FROM quiz_attempts WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'");
        $stmt->bind_param("ii", $studentId, $quizId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'You already have an in-progress attempt for this quiz'];
        }
        
        // Create new attempt
        $stmt = $this->conn->prepare("INSERT INTO quiz_attempts (student_id, quiz_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $studentId, $quizId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Quiz attempt started', 'attempt_id' => $this->conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to start quiz attempt'];
        }
    }
    
    public function submitAnswer($attemptId, $questionId, $answerText = null, $selectedOptionId = null) {
        // Check if answer already exists
        $stmt = $this->conn->prepare("SELECT id FROM quiz_answers WHERE attempt_id = ? AND question_id = ?");
        $stmt->bind_param("ii", $attemptId, $questionId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Update existing answer
            $stmt = $this->conn->prepare("UPDATE quiz_answers SET answer_text = ?, selected_option_id = ? WHERE attempt_id = ? AND question_id = ?");
            $stmt->bind_param("siii", $answerText, $selectedOptionId, $attemptId, $questionId);
        } else {
            // Insert new answer
            $stmt = $this->conn->prepare("INSERT INTO quiz_answers (attempt_id, question_id, answer_text, selected_option_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $attemptId, $questionId, $answerText, $selectedOptionId);
        }
        
        return $stmt->execute();
    }
    
    public function submitQuiz($attemptId) {
        // Calculate score
        $this->calculateScore($attemptId);
        
        // Update attempt status
        $stmt = $this->conn->prepare("UPDATE quiz_attempts SET status = 'completed', submitted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $attemptId);
        
        return $stmt->execute();
    }
    
    private function calculateScore($attemptId) {
        // Get all answers for this attempt
        $stmt = $this->conn->prepare("
            SELECT qa.*, qq.marks, qq.question_type, qo.is_correct 
            FROM quiz_answers qa 
            JOIN quiz_questions qq ON qa.question_id = qq.id 
            LEFT JOIN quiz_options qo ON qa.selected_option_id = qo.id 
            WHERE qa.attempt_id = ?
        ");
        $stmt->bind_param("i", $attemptId);
        $stmt->execute();
        $answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $totalScore = 0;
        $totalMarks = 0;
        
        foreach ($answers as $answer) {
            $totalMarks += $answer['marks'];
            $isCorrect = false;
            $marksObtained = 0;
            
            // Calculate score based on question type
            if ($answer['question_type'] === 'multiple_choice') {
                // For multiple choice, check if selected option is correct
                if ($answer['selected_option_id'] && $answer['is_correct']) {
                    $isCorrect = true;
                    $marksObtained = $answer['marks'];
                    $totalScore += $answer['marks'];
                }
            } elseif ($answer['question_type'] === 'true_false') {
                // For true/false, check if answer is valid (true or false)
                $studentAnswer = strtolower(trim($answer['answer_text']));
                if ($studentAnswer === 'true' || $studentAnswer === 'false') {
                    $isCorrect = true;
                    $marksObtained = $answer['marks'];
                    $totalScore += $answer['marks'];
                }
            } else {
                // For short answer, assume correct for now (manual grading needed)
                if (!empty($answer['answer_text'])) {
                    $isCorrect = true;
                    $marksObtained = $answer['marks'];
                    $totalScore += $answer['marks'];
                }
            }
            
            // Update answer with calculated results
            $stmt = $this->conn->prepare("UPDATE quiz_answers SET marks_obtained = ?, is_correct = ? WHERE attempt_id = ? AND question_id = ?");
            $stmt->bind_param("iiii", $marksObtained, $isCorrect ? 1 : 0, $attemptId, $answer['question_id']);
            $stmt->execute();
        }
        
        // Update attempt with calculated score
        $stmt = $this->conn->prepare("UPDATE quiz_attempts SET score = ?, total_marks = ? WHERE id = ?");
        $stmt->bind_param("iii", $totalScore, $totalMarks, $attemptId);
        $stmt->execute();
    }
    
    public function getStudentAttempts($studentId) {
        $stmt = $this->conn->prepare("
            SELECT qa.*, q.title as quiz_title, c.title as course_title 
            FROM quiz_attempts qa 
            JOIN quizzes q ON qa.quiz_id = q.id 
            JOIN courses c ON q.course_id = c.id 
            WHERE qa.student_id = ? 
            ORDER BY qa.started_at DESC
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
