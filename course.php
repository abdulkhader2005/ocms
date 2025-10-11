<?php
require_once __DIR__ . '/../config/database.php';

class Course {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function createCourse($title, $description, $instructorId, $price = 0.00, $thumbnail = null) {
        $stmt = $this->conn->prepare("INSERT INTO courses (title, description, instructor_id, price, thumbnail) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssids", $title, $description, $instructorId, $price, $thumbnail);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Course created successfully', 'course_id' => $this->conn->insert_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create course'];
        }
    }
    
    public function updateCourse($courseId, $title, $description, $price, $thumbnail = null) {
        $stmt = $this->conn->prepare("UPDATE courses SET title = ?, description = ?, price = ?, thumbnail = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $title, $description, $price, $thumbnail, $courseId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Course updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update course'];
        }
    }
    
    public function deleteCourse($courseId) {
        $stmt = $this->conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $courseId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Course deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete course'];
        }
    }
    
    public function getCourse($courseId) {
        $stmt = $this->conn->prepare("SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getAllCourses($limit = null, $offset = 0) {
        $sql = "SELECT c.*, u.first_name, u.last_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.status = 'active' ORDER BY c.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getInstructorCourses($instructorId) {
        $stmt = $this->conn->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $instructorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function enrollStudent($studentId, $courseId) {
        // Check if already enrolled
        $stmt = $this->conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Already enrolled in this course'];
        }
        
        // Enroll student
        $stmt = $this->conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $studentId, $courseId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Successfully enrolled in course'];
        } else {
            return ['success' => false, 'message' => 'Failed to enroll in course'];
        }
    }
    
    public function getStudentCourses($studentId) {
        $stmt = $this->conn->prepare("SELECT c.*, e.enrolled_at FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = ? ORDER BY e.enrolled_at DESC");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function isEnrolled($studentId, $courseId) {
        $stmt = $this->conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
?>
