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

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $task = new Task();
    
    $task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($task_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'ID tugas tidak valid!'
        ]);
        exit;
    }
    
    // Ambil data task
    $taskData = $task->getTaskById($task_id, $_SESSION['user_id']);
    
    if ($taskData) {
        echo json_encode([
            'success' => true, 
            'task' => $taskData
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Tugas tidak ditemukan!'
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