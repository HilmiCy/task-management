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
    $completed = isset($input['completed']) ? (bool)$input['completed'] : false;
    
    if ($task_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID tugas tidak valid!'
        ]);
        exit;
    }
    
    // Update status task
    $newStatus = $completed ? 'completed' : 'pending';
    $result = $task->updateStatus($task_id, $_SESSION['user_id'], $newStatus);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Status tugas berhasil diupdate!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal mengupdate status tugas!'
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