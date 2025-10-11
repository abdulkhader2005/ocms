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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = (float)$_POST['price'];
    
    // Handle file upload
    $thumbnail = null;
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
    
    $result = $course->createCourse($title, $description, $user['id'], $price, $thumbnail);
    
    if ($result['success']) {
        $success = $result['message'];
        // Clear form data
        $_POST = array();
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
    <title>Add Course - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Add New Course</h1>
            <p>Create a new course for your students</p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Course Title</label>
                    <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? $_POST['price'] : '0.00'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">Course Thumbnail</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Course</button>
                    <a href="courses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
