<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Task.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task = new Task();
    
    // Ambil data dari JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = isset($input['id']) ? (int)$input['id'] : 0;
    
    if ($task_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID tugas tidak valid!'
        ]);
        exit;
    }
    
    // Cek apakah task milik user
    $existingTask = $task->getTaskById($task_id, $_SESSION['user_id']);
    if (!$existingTask) {
        echo json_encode([
            'success' => false, 
            'message' => 'Tugas tidak ditemukan!'
        ]);
        exit;
    }
    
    // Hapus task (soft delete / archive)
    $result = $task->deleteTask($task_id, $_SESSION['user_id']);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas berhasil dihapus!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menghapus tugas. Silakan coba lagi!'
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>