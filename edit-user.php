<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth($conn);
$auth->requireRole('admin');

// Set base path for navigation
$basePath = '../';

$user = $auth->getCurrentUser();
$error = '';
$success = '';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId == 0) {
    header('Location: users.php');
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    header('Location: users.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    // Check if username or email already exists (excluding current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $userId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error = 'Username or email already exists';
    } else {
        // Update user
        if (!empty($password)) {
            // Update with new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $username, $email, $firstName, $lastName, $role, $hashedPassword, $userId);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $username, $email, $firstName, $lastName, $role, $userId);
        }
        
        if ($stmt->execute()) {
            $success = 'User updated successfully';
            // Redirect to prevent duplicate submissions
            header('Location: users.php?success=' . urlencode($success));
            exit();
        } else {
            $error = 'Failed to update user: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Edit User</h1>
            <p>Update user information</p>
            <a href="users.php" class="btn btn-secondary">Back to Users</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?php echo $userData['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="instructor" <?php echo $userData['role'] === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                        <option value="student" <?php echo $userData['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current password)</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
