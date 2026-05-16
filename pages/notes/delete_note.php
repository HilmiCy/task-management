<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Note.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note = new Note();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID catatan tidak valid!']);
        exit;
    }
    
    $result = $note->deleteNote($id, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Catatan berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus catatan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
?>