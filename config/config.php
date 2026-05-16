<?php
// Konfigurasi aplikasi
session_start();

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (nonaktifkan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base URL (ubah sesuai domain Anda)
define('BASE_URL', 'http://localhost/task_management/');
define('BASE_PATH', dirname(__DIR__) . '/');

// Konfigurasi aplikasi
define('APP_NAME', 'Task Management System');
define('APP_VERSION', '1.0.0');

// Upload settings
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Session timeout (30 menit)
define('SESSION_TIMEOUT', 1800);

// Security
define('CSRF_TOKEN_KEY', 'csrf_token');
define('PASSWORD_BCRYPT_COST', 12);

// Fungsi helper
require_once BASE_PATH . 'includes/functions.php';
?>