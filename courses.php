<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('student');


// Set base path for navigation
$basePath = '../';
$course = new Course($conn);
$courses = $course->getAllCourses();

$user = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Browse Courses</h1>
            <p>Discover and enroll in courses</p>
        </div>
        
        <?php if (empty($courses)): ?>
            <div class="info-message">
                No courses available at the moment.
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
                                <span class="course-instructor">By <?php echo htmlspecialchars($courseData['first_name'] . ' ' . $courseData['last_name']); ?></span>
                                <span class="course-price">$<?php echo number_format($courseData['price'], 2); ?></span>
                            </div>
                            <div class="course-actions">
                                <a href="course-details.php?id=<?php echo $courseData['id']; ?>" class="btn btn-primary">View Details</a>
                                <?php if ($course->isEnrolled($user['id'], $courseData['id'])): ?>
                                    <span class="btn btn-success">Enrolled</span>
                                <?php else: ?>
                                    <a href="enroll.php?course_id=<?php echo $courseData['id']; ?>" class="btn btn-primary">Enroll</a>
                                <?php endif; ?>
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
