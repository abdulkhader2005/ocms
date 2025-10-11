<?php
require_once 'includes/auth.php';

$auth = new Auth($conn);

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Redirect to login if not logged in
header('Location: login.php');
exit();
?>
