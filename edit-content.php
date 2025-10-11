<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';
require_once __DIR__ . '/../includes/content.php';

$auth = new Auth($conn);
$auth->requireRole('instructor');

// Set base path for navigation
$basePath = '../';

$course = new Course($conn);
$content = new Content($conn);
$user = $auth->getCurrentUser();

$contentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contentData = $content->getContent($contentId);

if (!$contentData) {
    header('Location: courses.php');
    exit();
}

// Get course data to verify ownership
$courseData = $course->getCourse($contentData['course_id']);

// Check if instructor owns this course
if (!$courseData || $courseData['instructor_id'] != $user['id']) {
    header('Location: courses.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $contentText = $_POST['content'];
    $orderIndex = (int)$_POST['order_index'];
    
    $filePath = $contentData['file_path']; // Keep existing file by default
    
    if ($type === 'video' || $type === 'document') {
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $content->uploadFile($_FILES['file']);
            if ($uploadResult['success']) {
                $filePath = $uploadResult['file_path'];
            } else {
                $error = $uploadResult['message'];
            }
        }
    }
    
    if (!$error) {
        $result = $content->updateContent($contentId, $title, $type, $filePath, $contentText, $orderIndex);
        if ($result['success']) {
            $success = $result['message'];
            // Refresh content data
            $contentData = $content->getContent($contentId);
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Content - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Edit Content</h1>
            <p>Update course content</p>
            <a href="course-content.php?id=<?php echo $contentData['course_id']; ?>" class="btn btn-secondary">Back to Content</a>
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
                    <label for="title">Content Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($contentData['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="type">Content Type</label>
                    <select id="type" name="type" required onchange="toggleFileInput()">
                        <option value="video" <?php echo $contentData['type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                        <option value="document" <?php echo $contentData['type'] === 'document' ? 'selected' : ''; ?>>Document (PDF)</option>
                        <option value="note" <?php echo $contentData['type'] === 'note' ? 'selected' : ''; ?>>Note</option>
                    </select>
                </div>
                
                <div class="form-group" id="file-group">
                    <label for="file">File</label>
                    <?php if ($contentData['file_path']): ?>
                        <p>Current file: <a href="../uploads/content/<?php echo $contentData['file_path']; ?>" target="_blank">View</a></p>
                    <?php endif; ?>
                    <input type="file" id="file" name="file" accept="video/*,.pdf">
                    <small>Leave blank to keep current file</small>
                </div>
                
                <div class="form-group">
                    <label for="content">Content/Description</label>
                    <textarea id="content" name="content" rows="4"><?php echo htmlspecialchars($contentData['content']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="order_index">Order</label>
                    <input type="number" id="order_index" name="order_index" min="0" value="<?php echo $contentData['order_index']; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Content</button>
                    <a href="course-content.php?id=<?php echo $contentData['course_id']; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleFileInput() {
            const type = document.getElementById('type').value;
            const fileGroup = document.getElementById('file-group');
            const fileInput = document.getElementById('file');
            
            if (type === 'video' || type === 'document') {
                fileGroup.style.display = 'block';
                if (type === 'video') {
                    fileInput.accept = 'video/*';
                } else {
                    fileInput.accept = '.pdf';
                }
            } else {
                fileGroup.style.display = 'none';
            }
        }
        
        // Initialize on page load
        toggleFileInput();
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
