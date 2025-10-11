<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$course = new Course($conn);
$user = $auth->getCurrentUser();

if (isset($_GET['course_id'])) {
    $courseId = (int)$_GET['course_id'];
    
    $result = $course->enrollStudent($user['id'], $courseId);
    
    if ($result['success']) {
        header('Location: my-courses.php?enrolled=1');
        exit();
    } else {
        $error = $result['message'];
    }
} else {
    header('Location: courses.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
