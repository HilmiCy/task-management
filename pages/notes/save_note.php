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
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $note_type = $_POST['note_type'] ?? 'personal';
    $task_id = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Judul catatan tidak boleh kosong!']);
        exit;
    }
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'task_id' => $task_id,
        'title' => $title,
        'content' => $content,
        'note_type' => $note_type,
        'is_pinned' => $is_pinned
    ];
    
    $result = $note->createNote($data);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Catatan berhasil ditambahkan!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan catatan!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Metode request tidak valid!']);
?>