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
    
    // Ambil dan validasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? '';
    $due_time = $_POST['due_time'] ?? null;
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
    
    // Validasi waktu jika ada
    if (!empty($due_time)) {
        $time = DateTime::createFromFormat('H:i', $due_time);
        if (!$time) {
            echo json_encode([
                'success' => false, 
                'message' => 'Format waktu tidak valid!'
            ]);
            exit;
        }
    }
    
    // Siapkan data untuk insert task
    $data = [
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'description' => $description,
        'category_id' => $category_id,
        'priority' => $priority,
        'status' => 'pending',
        'due_date' => $due_date,
        'due_time' => !empty($due_time) ? $due_time : null,
        'estimated_hours' => $estimated_hours
    ];
    
    // Simpan ke database
    $result = $task->createTask($data);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Tugas "' . htmlspecialchars($title) . '" berhasil ditambahkan!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal menambahkan tugas. Silakan coba lagi!'
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