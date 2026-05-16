<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$db = DB::getInstance();

$sql = "SELECT COUNT(*) as count FROM categories WHERE user_id = ?";
$result = $db->fetch($sql, [$user_id]);

echo json_encode(['success' => true, 'count' => $result['count'] ?? 0]);
?>