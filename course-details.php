<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$course = new Course($conn);
$user = $auth->getCurrentUser();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$courseData = $course->getCourse($courseId);

if (!$courseData) {
    header('Location: courses.php');
    exit();
}

$isEnrolled = $course->isEnrolled($user['id'], $courseId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="course-details">
            <div class="course-header">
                <?php if ($courseData['thumbnail']): ?>
                    <img src="../uploads/<?php echo $courseData['thumbnail']; ?>" alt="Course Thumbnail" class="course-thumbnail-large">
                <?php endif; ?>
                
                <div class="course-info">
                    <h1><?php echo htmlspecialchars($courseData['title']); ?></h1>
                    <p class="course-instructor">By <?php echo htmlspecialchars($courseData['first_name'] . ' ' . $courseData['last_name']); ?></p>
                    <p class="course-price">$<?php echo number_format($courseData['price'], 2); ?></p>
                    
                    <div class="course-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($courseData['description'])); ?></p>
                    </div>
                    
                    <div class="course-actions">
                        <?php if ($isEnrolled): ?>
                            <a href="course-content.php?id=<?php echo $courseId; ?>" class="btn btn-primary">Access Course</a>
                            <span class="btn btn-success">Enrolled</span>
                        <?php else: ?>
                            <a href="enroll.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary">Enroll Now</a>
                        <?php endif; ?>
                        <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
