<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth($conn);
$auth->requireRole('admin');

// Set base path for navigation
$basePath = '../';

// Get analytics data
$analytics = [];

// Total users by role
$stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stmt->execute();
$roleStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total courses
$stmt = $conn->prepare("SELECT COUNT(*) as total_courses FROM courses");
$stmt->execute();
$analytics['total_courses'] = $stmt->get_result()->fetch_assoc()['total_courses'];

// Total enrollments
$stmt = $conn->prepare("SELECT COUNT(*) as total_enrollments FROM enrollments");
$stmt->execute();
$analytics['total_enrollments'] = $stmt->get_result()->fetch_assoc()['total_enrollments'];

// Total quizzes
$stmt = $conn->prepare("SELECT COUNT(*) as total_quizzes FROM quizzes");
$stmt->execute();
$analytics['total_quizzes'] = $stmt->get_result()->fetch_assoc()['total_quizzes'];

// Quiz attempts
$stmt = $conn->prepare("SELECT COUNT(*) as total_attempts FROM quiz_attempts");
$stmt->execute();
$analytics['total_attempts'] = $stmt->get_result()->fetch_assoc()['total_attempts'];

// Recent enrollments
$stmt = $conn->prepare("
    SELECT e.*, c.title as course_title, u.first_name, u.last_name 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id 
    JOIN users u ON e.student_id = u.id 
    ORDER BY e.enrolled_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top courses by enrollment
$stmt = $conn->prepare("
    SELECT c.title, COUNT(e.id) as enrollment_count 
    FROM courses c 
    LEFT JOIN enrollments e ON c.id = e.course_id 
    GROUP BY c.id 
    ORDER BY enrollment_count DESC 
    LIMIT 5
");
$stmt->execute();
$topCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>System Analytics</h1>
            <p>Overview of system usage and statistics</p>
        </div>
        
        <!-- Key Metrics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo $analytics['total_courses']; ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $analytics['total_enrollments']; ?></h3>
                <p>Total Enrollments</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $analytics['total_quizzes']; ?></h3>
                <p>Total Quizzes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $analytics['total_attempts']; ?></h3>
                <p>Quiz Attempts</p>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div class="dashboard-content">
            <h2>User Statistics</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roleStats as $stat): ?>
                            <tr>
                                <td><?php echo ucfirst($stat['role']); ?></td>
                                <td><?php echo $stat['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Courses -->
        <div class="dashboard-content">
            <h2>Most Popular Courses</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrollments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCourses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo $course['enrollment_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="dashboard-content">
            <h2>Recent Enrollments</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Enrolled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEnrollments as $enrollment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($enrollment['enrolled_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
