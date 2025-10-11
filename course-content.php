<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';
require_once __DIR__ . '/../includes/content.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$course = new Course($conn);
$content = new Content($conn);
$user = $auth->getCurrentUser();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$courseData = $course->getCourse($courseId);

if (!$courseData) {
    header('Location: my-courses.php');
    exit();
}

// Check if student is enrolled
if (!$course->isEnrolled($user['id'], $courseId)) {
    header('Location: courses.php');
    exit();
}

$courseContent = $content->getCourseContent($courseId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1><?php echo htmlspecialchars($courseData['title']); ?></h1>
            <p>Course Content</p>
            <a href="my-courses.php" class="btn btn-secondary">Back to My Courses</a>
        </div>
        
        <?php if (empty($courseContent)): ?>
            <div class="info-message">
                No content available for this course yet.
            </div>
        <?php else: ?>
            <div class="content-list">
                <?php foreach ($courseContent as $item): ?>
                    <div class="content-item">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="content-type">Type: <?php echo ucfirst($item['type']); ?></p>
                        
                        <?php if ($item['type'] === 'video' && $item['file_path']): ?>
                            <video controls width="100%" height="400">
                                <source src="../uploads/content/<?php echo $item['file_path']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php elseif ($item['type'] === 'document' && $item['file_path']): ?>
                            <div class="document-viewer">
                                <a href="../uploads/content/<?php echo $item['file_path']; ?>" target="_blank" class="btn btn-primary">
                                    View Document
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($item['content']): ?>
                            <div class="content-text">
                                <?php echo nl2br(htmlspecialchars($item['content'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
