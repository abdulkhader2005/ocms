<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

$auth = new Auth($conn);
$auth->requireRole('instructor');

// Set base path for navigation
$basePath = '../';

$course = new Course($conn);
$user = $auth->getCurrentUser();
$error = '';
$success = '';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$courseData = $course->getCourse($courseId);

// Check if instructor owns this course
if (!$courseData || $courseData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'];
    
    // Handle file upload
    $thumbnail = $courseData['thumbnail']; // Keep existing thumbnail by default
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . $_FILES['thumbnail']['name'];
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadPath)) {
            $thumbnail = $fileName;
        }
    }
    
    $result = $course->updateCourse($courseId, $title, $description, $price, $thumbnail);
    
    if ($result['success']) {
        $success = $result['message'];
        // Refresh course data
        $courseData = $course->getCourse($courseId);
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
    <title>Edit Course - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Edit Course</h1>
            <p>Update your course information</p>
            <a href="courses.php" class="btn btn-secondary">Back to Courses</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Course Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($courseData['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($courseData['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $courseData['price']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $courseData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $courseData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">Course Thumbnail</label>
                    <?php if ($courseData['thumbnail']): ?>
                        <p>Current thumbnail: <a href="../uploads/<?php echo $courseData['thumbnail']; ?>" target="_blank">View</a></p>
                    <?php endif; ?>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
                    <small>Leave blank to keep current thumbnail</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <a href="courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
