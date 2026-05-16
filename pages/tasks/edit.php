<?php
require_once '../../config/session.php';
require_once '../../config/config.php';
require_once '../../classes/Database.php';
require_once '../../classes/Task.php';
require_once '../../classes/Category.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

// Set header untuk JSON response
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task = new Task();
    $category = new Category();
    
    // Ambil ID task
    $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    
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
    
    // Ambil dan validasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    $due_date = $_POST['due_date'] ?? '';
    $due_time = !empty($_POST['due_time']) ? $_POST['due_time'] : null;
    $estimated_hours = isset($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : 0;
    
    // Validasi field wajib
    if (empty($title)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Judul tugas tidak boleh kosong!'
        ]);
        exit;
    }
    
    if (empty($due_date)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Deadline tugas harus diisi!'
        ]);
        exit;
    }
    
    // Validasi tanggal
    $date = DateTime::createFromFormat('Y-m-d', $due_date);
    if (!$date || $date->format('Y-m-d') !== $due_date) {
        echo json_encode([
            'success' => false, 
            'message' => 'Format tanggal deadline tidak valid!'
        ]);
        exit;
    }
    
    // Validasi priority
    $validPriorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $validPriorities)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Prioritas tidak valid!'
        ]);
        exit;
    }
    
    // Validasi status
    $validStatus = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $validStatus)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Status tidak valid!'
        ]);
        exit;
    }
    
    // Validasi kategori jika ada
    if ($category_id) {
        $categories = $category->getAllCategories($_SESSION['user_id']);
        $validCategory = false;
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $validCategory = true;
                break;
            }
        }
        if (!$validCategory) {
            echo json_encode([
                'success' => false, 
                'message' => 'Kategori tidak valid!'
            ]);
            exit;
        }
    }
    
    // Validasi waktu jika ada (format 24 jam)
    if (!empty($due_time)) {
        $timePattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/';
        if (!preg_match($timePattern, $due_time)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Format waktu tidak valid! Gunakan format HH:MM (contoh: 14:30)'
            ]);
            exit;
        }
    }
    
    // Siapkan data untuk update task
    $data = [
        'category_id' => $category_id,
        'title' => $title,
        'description' => $description,
        'priority' => $priority,
        'due_date' => $due_date,
        'due_time' => $due_time,
        'estimated_hours' => $estimated_hours
    ];
    
    // Update task
    $result = $task->updateTask($task_id, $_SESSION['user_id'], $data);
    
    if ($result !== false) {
        // Update status jika berbeda
        if ($existingTask['status'] != $status) {
            $task->updateStatus($task_id, $_SESSION['user_id'], $status);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas "' . htmlspecialchars($title) . '" berhasil diupdate!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal mengupdate tugas. Silakan coba lagi!'
        ]);
    }
    exit;
}

// Jika bukan method POST
echo json_encode([
    'success' => false, 
    'message' => 'Metode request tidak valid!'
]);
exit;
?>