<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('instructor');

// Set base path for navigation
$basePath = '../';

$course = new Course($conn);
$user = $auth->getCurrentUser();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$courseData = $course->getCourse($courseId);

// Check if instructor owns this course
if (!$courseData || $courseData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

$error = '';
$success = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $result = $course->deleteCourse($courseId);
    
    if ($result['success']) {
        $success = $result['message'];
        // Redirect to courses list after successful deletion
        header('Location: courses.php?success=' . urlencode($success));
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
    <title>Delete Course - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Delete Course</h1>
            <p>Are you sure you want to delete this course?</p>
            <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="course-details">
                <h2><?php echo htmlspecialchars($courseData['title']); ?></h2>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($courseData['description']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($courseData['price'], 2); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($courseData['status']); ?></p>
                <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($courseData['created_at'])); ?></p>
            </div>
            
            <div class="warning-message">
                <h3>⚠️ Warning</h3>
                <p>This action cannot be undone. Deleting this course will also remove:</p>
                <ul>
                    <li>All course content (videos, documents, notes)</li>
                    <li>All student enrollments</li>
                    <li>All quizzes and quiz attempts</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="form-actions">
                    <button type="submit" name="confirm_delete" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this course? This action cannot be undone!')">
                        Yes, Delete Course
                    </button>
                    <a href="courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
