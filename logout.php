<?php
require_once 'config/config.php';
require_once 'classes/User.php';

$user = new User();
$user->logout();

// Hapus cookie remember me
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect ke halaman login
header('Location: login.php?message=logged_out');
exit();
?>