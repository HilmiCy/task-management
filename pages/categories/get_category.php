<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Category.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $category = new Category();
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID kategori tidak valid!']);
        exit;
    }
    
    $data = $category->getCategoryById($id, $_SESSION['user_id']);
    
    if ($data) {
        echo json_encode(['success' => true, 'category' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
?>