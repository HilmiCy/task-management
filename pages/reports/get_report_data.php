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
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['type'] ?? 'overview';

$db = DB::getInstance();

// Get task statistics
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
        FROM tasks 
        WHERE user_id = ? AND is_archived = 0 AND due_date BETWEEN ? AND ?";
$stats = $db->fetch($sql, [$user_id, $start_date, $end_date]);

// Get priority statistics
$sql = "SELECT 
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high,
            SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium,
            SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low
        FROM tasks 
        WHERE user_id = ? AND is_archived = 0 AND due_date BETWEEN ? AND ?";
$priority = $db->fetch($sql, [$user_id, $start_date, $end_date]);

// Get category statistics
$sql = "SELECT 
            c.id,
            c.name,
            c.color,
            COUNT(t.id) as total
        FROM categories c
        LEFT JOIN tasks t ON c.id = t.category_id AND t.user_id = ? AND t.is_archived = 0 AND t.due_date BETWEEN ? AND ?
        WHERE c.user_id = ?
        GROUP BY c.id, c.name, c.color
        UNION ALL
        SELECT 
            NULL as id,
            'Tanpa Kategori' as name,
            '#9ca3af' as color,
            COUNT(*) as total
        FROM tasks 
        WHERE user_id = ? AND is_archived = 0 AND category_id IS NULL AND due_date BETWEEN ? AND ?
        HAVING total > 0";
$categories = $db->fetchAll($sql, [$user_id, $start_date, $end_date, $user_id, $user_id, $start_date, $end_date]);

// Get recent tasks
$sql = "SELECT t.*, c.name as category_name
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.is_archived = 0 AND t.due_date BETWEEN ? AND ?
        ORDER BY t.created_at DESC
        LIMIT 20";
$tasks = $db->fetchAll($sql, [$user_id, $start_date, $end_date]);

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'priority' => $priority,
    'categories' => $categories,
    'tasks' => $tasks
]);
?>