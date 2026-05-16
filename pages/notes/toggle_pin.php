<?php
$root_path = dirname(dirname(__DIR__));
require_once $root_path . '/config/session.php';
require_once $root_path . '/config/config.php';
require_once $root_path . '/classes/Database.php';
require_once $root_path . '/classes/Note.php';

// Set header untuk JSON response
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired, silakan login kembali']);
    exit;
}

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Ambil data dari request
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID catatan tidak valid']);
    exit;
}

$note = new Note();
$user_id = $_SESSION['user_id'];

// Cek apakah note milik user
$existingNote = $note->getNoteById($id, $user_id);
if (!$existingNote) {
    echo json_encode(['success' => false, 'message' => 'Catatan tidak ditemukan']);
    exit;
}

// Toggle pin
$result = $note->togglePin($id, $user_id);

if ($result) {
    $newStatus = $existingNote['is_pinned'] == 1 ? 'dilepas pinnya' : 'disematkan';
    echo json_encode(['success' => true, 'message' => "Catatan berhasil {$newStatus}"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status pin, silakan coba lagi']);
}
exit;
?>