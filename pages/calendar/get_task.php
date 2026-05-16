<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tugas tidak valid']);
    exit;
}

// Query dengan JOIN yang benar - field color dari tabel categories
$sql = "SELECT 
            t.*, 
            c.name as category_name, 
            c.color as category_color,
            c.icon as category_icon
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.id = ? AND t.user_id = ? AND t.is_archived = 0";

$db = DB::getInstance();
$task = $db->fetch($sql, [$task_id, $user_id]);

if ($task) {
    // Pastikan category_color memiliki nilai default jika NULL
    if (!$task['category_color']) {
        $task['category_color'] = '#6b7280';
    }
    echo json_encode(['success' => true, 'task' => $task]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan']);
}
?>