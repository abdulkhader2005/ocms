<?php
require_once 'includes/auth.php';

$auth = new Auth($conn);
$auth->logout();

header('Location: login.php');
exit();
?>
