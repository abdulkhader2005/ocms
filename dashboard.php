<?php
require_once 'includes/auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

$user = $auth->getCurrentUser();
$role = $user['role'];

// Get dashboard data based on role
$dashboardData = [];

if ($role === 'admin') {
    // Admin dashboard data
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $dashboardData['total_users'] = $stmt->get_result()->fetch_assoc()['total_users'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total_courses FROM courses");
    $stmt->execute();
    $dashboardData['total_courses'] = $stmt->get_result()->fetch_assoc()['total_courses'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total_enrollments FROM enrollments");
    $stmt->execute();
    $dashboardData['total_enrollments'] = $stmt->get_result()->fetch_assoc()['total_enrollments'];
    
} elseif ($role === 'instructor') {
    // Instructor dashboard data
    $stmt = $conn->prepare("SELECT COUNT(*) as my_courses FROM courses WHERE instructor_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $dashboardData['my_courses'] = $stmt->get_result()->fetch_assoc()['my_courses'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.instructor_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $dashboardData['total_students'] = $stmt->get_result()->fetch_assoc()['total_students'];
    
} elseif ($role === 'student') {
    // Student dashboard data
    $stmt = $conn->prepare("SELECT COUNT(*) as enrolled_courses FROM enrollments WHERE student_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $dashboardData['enrolled_courses'] = $stmt->get_result()->fetch_assoc()['enrolled_courses'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as completed_quizzes FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $dashboardData['completed_quizzes'] = $stmt->get_result()->fetch_assoc()['completed_quizzes'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OCMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $user['first_name']; ?>!</h1>
            <p>Role: <?php echo ucfirst($role); ?></p>
        </div>
        
        <div class="dashboard-stats">
            <?php if ($role === 'admin'): ?>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['total_courses']; ?></h3>
                    <p>Total Courses</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['total_enrollments']; ?></h3>
                    <p>Total Enrollments</p>
                </div>
            <?php elseif ($role === 'instructor'): ?>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['my_courses']; ?></h3>
                    <p>My Courses</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['total_students']; ?></h3>
                    <p>Total Students</p>
                </div>
            <?php elseif ($role === 'student'): ?>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['enrolled_courses']; ?></h3>
                    <p>Enrolled Courses</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $dashboardData['completed_quizzes']; ?></h3>
                    <p>Completed Quizzes</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-content">
            <?php if ($role === 'admin'): ?>
                <div class="dashboard-section">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="admin/users.php" class="btn btn-primary">Manage Users</a>
                        <a href="admin/courses.php" class="btn btn-primary">Manage Courses</a>
                        <a href="admin/analytics.php" class="btn btn-primary">View Analytics</a>
                    </div>
                </div>
            <?php elseif ($role === 'instructor'): ?>
                <div class="dashboard-section">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="instructor/courses.php" class="btn btn-primary">My Courses</a>
                        <a href="instructor/add-course.php" class="btn btn-primary">Add New Course</a>
                        <a href="instructor/content.php" class="btn btn-primary">Manage Content</a>
                    </div>
                </div>
            <?php elseif ($role === 'student'): ?>
                <div class="dashboard-section">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="student/courses.php" class="btn btn-primary">Browse Courses</a>
                        <a href="student/my-courses.php" class="btn btn-primary">My Courses</a>
                        <a href="student/quizzes.php" class="btn btn-primary">Take Quizzes</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
