<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth($conn);
$auth->requireRole('admin');

// Set base path for navigation
$basePath = '../';

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Get success message from URL if redirected
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = (int)$_GET['delete_user'];
    
    // Don't allow deleting self
    if ($userId == $user['id']) {
        $error = 'You cannot delete your own account';
    } else {
        // Check if user exists before deleting
        $checkStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $userToDelete = $checkStmt->get_result()->fetch_assoc();
        
        if ($userToDelete) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $success = 'User "' . $userToDelete['username'] . '" deleted successfully';
                // Redirect to prevent duplicate submissions
                header('Location: users.php?success=' . urlencode($success));
                exit();
            } else {
                $error = 'Failed to delete user: ' . $conn->error;
            }
        } else {
            $error = 'User not found';
        }
    }
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - OCMS</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>Manage Users</h1>
            <p>View and manage all system users</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $userData): ?>
                        <tr>
                            <td><?php echo $userData['id']; ?></td>
                            <td><?php echo htmlspecialchars($userData['username']); ?></td>
                            <td><?php echo htmlspecialchars($userData['email']); ?></td>
                            <td><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></td>
                            <td>
                                <span class="role-<?php echo $userData['role']; ?>">
                                    <?php echo ucfirst($userData['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($userData['created_at'])); ?></td>
                            <td>
                                <a href="edit-user.php?id=<?php echo $userData['id']; ?>" class="btn btn-secondary">Edit</a>
                                <?php if ($userData['id'] != $user['id']): ?>
                                    <a href="?delete_user=<?php echo $userData['id']; ?>" class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
