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

$sql = "SELECT t.*, c.name as category_name 
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.is_archived = 0
        AND t.status != 'completed'
        AND t.due_date >= CURDATE()
        ORDER BY t.due_date ASC, FIELD(t.priority, 'high', 'medium', 'low')
        LIMIT 10";

$db = DB::getInstance();
$tasks = $db->fetchAll($sql, [$user_id]);

echo json_encode(['success' => true, 'tasks' => $tasks]);
?>