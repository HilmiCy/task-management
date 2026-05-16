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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = new Category();
    
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#2c7a6e');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama kategori tidak boleh kosong!']);
        exit;
    }
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'name' => $name,
        'color' => $color,
        'icon' => $icon,
        'description' => $description
    ];
    
    $result = $category->createCategory($data);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan kategori!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
?>