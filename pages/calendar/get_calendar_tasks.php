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
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$sql = "SELECT 
            t.*, 
            c.name as category_name, 
            c.color as category_color,
            c.icon as category_icon
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.is_archived = 0
        AND t.due_date BETWEEN ? AND ?
        ORDER BY t.due_date ASC, 
                 FIELD(t.priority, 'high', 'medium', 'low'),
                 t.due_time ASC";

$db = DB::getInstance();
$tasks = $db->fetchAll($sql, [$user_id, $start_date, $end_date]);

$tasksByDate = [];
foreach ($tasks as $task) {
    $date = $task['due_date'];
    if (!isset($tasksByDate[$date])) {
        $tasksByDate[$date] = [];
    }
    // Set default color jika NULL
    if (!$task['category_color']) {
        $task['category_color'] = '#6b7280';
    }
    $tasksByDate[$date][] = $task;
}

echo json_encode(['success' => true, 'tasks' => $tasksByDate]);
?>