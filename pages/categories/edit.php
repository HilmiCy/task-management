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
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '#2c7a6e');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $description = trim($_POST['description'] ?? '');
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID kategori tidak valid!']);
        exit;
    }
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Nama kategori tidak boleh kosong!']);
        exit;
    }
    
    $data = [
        'name' => $name,
        'color' => $color,
        'icon' => $icon,
        'description' => $description
    ];
    
    $result = $category->updateCategory($id, $_SESSION['user_id'], $data);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil diupdate!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate kategori!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
?>