<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$course = new Course($conn);
$user = $auth->getCurrentUser();
$courses = $course->getStudentCourses($user['id']);

$enrolled = isset($_GET['enrolled']) ? $_GET['enrolled'] : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>My Courses</h1>
            <p>Your enrolled courses</p>
        </div>
        
        <?php if ($enrolled): ?>
            <div class="success-message">
                Successfully enrolled in the course!
            </div>
        <?php endif; ?>
        
        <?php if (empty($courses)): ?>
            <div class="info-message">
                You haven't enrolled in any courses yet. <a href="courses.php">Browse courses</a> to get started.
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $courseData): ?>
                    <div class="course-card">
                        <div class="course-thumbnail">
                            <?php if ($courseData['thumbnail']): ?>
                                <img src="../uploads/<?php echo $courseData['thumbnail']; ?>" alt="Course Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span>No Thumbnail</span>
                            <?php endif; ?>
                        </div>
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($courseData['title']); ?></h3>
                            <p class="course-description"><?php echo htmlspecialchars(substr($courseData['description'], 0, 100)) . '...'; ?></p>
                            <div class="course-meta">
                                <span class="course-price">$<?php echo number_format($courseData['price'], 2); ?></span>
                                <span class="enrolled-date">Enrolled: <?php echo date('M d, Y', strtotime($courseData['enrolled_at'])); ?></span>
                            </div>
                            <div class="course-actions">
                                <a href="course-content.php?id=<?php echo $courseData['id']; ?>" class="btn btn-primary">Access Course</a>
                                <a href="course-details.php?id=<?php echo $courseData['id']; ?>" class="btn btn-secondary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
