<?php
require_once __DIR__ . '/auth.php';
$auth = new Auth($conn);
$currentUser = $auth->getCurrentUser();

// Determine the base path based on current directory
$currentDir = dirname($_SERVER['PHP_SELF']);
$basePath = '';

if (strpos($currentDir, '/admin') !== false) {
    $basePath = '../';
} elseif (strpos($currentDir, '/instructor') !== false) {
    $basePath = '../';
} elseif (strpos($currentDir, '/student') !== false) {
    $basePath = '../';
}
?>

<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <h1><a href="<?php echo $basePath; ?>dashboard.php">OCMS</a></h1>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo $basePath; ?>dashboard.php">Dashboard</a></li>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <li><a href="<?php echo $basePath; ?>admin/users.php">Users</a></li>
                        <li><a href="<?php echo $basePath; ?>admin/courses.php">Courses</a></li>
                        <li><a href="<?php echo $basePath; ?>admin/analytics.php">Analytics</a></li>
                    <?php elseif ($currentUser['role'] === 'instructor'): ?>
                        <li><a href="<?php echo $basePath; ?>instructor/courses.php">My Courses</a></li>
                        <li><a href="<?php echo $basePath; ?>instructor/add-course.php">Add Course</a></li>
                        <li><a href="<?php echo $basePath; ?>instructor/course-content.php">Content</a></li>
                    <?php elseif ($currentUser['role'] === 'student'): ?>
                        <li><a href="<?php echo $basePath; ?>student/courses.php">Browse Courses</a></li>
                        <li><a href="<?php echo $basePath; ?>student/my-courses.php">My Courses</a></li>
                        <li><a href="<?php echo $basePath; ?>student/quizzes.php">Quizzes</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="user-menu">
                <span>Welcome, <?php echo $currentUser['first_name']; ?></span>
                <a href="<?php echo $basePath; ?>logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</header>
