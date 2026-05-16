<?php
// File: pages/notes/get_note.php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../classes/Database.php';
require_once '../../classes/Note.php';

// Set header JSON
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired, silakan login kembali']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek method request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Ambil ID note
$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($note_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID catatan tidak valid']);
    exit;
}

// Inisialisasi class Note
$note = new Note();

// Ambil data note
$noteData = $note->getNoteById($note_id, $user_id);

if ($noteData) {
    echo json_encode([
        'success' => true,
        'note' => [
            'id' => $noteData['id'],
            'title' => $noteData['title'],
            'content' => $noteData['content'],
            'created_at' => $noteData['created_at'],
            'updated_at' => $noteData['updated_at'],
            'note_type' => $noteData['note_type'],
            'is_pinned' => $noteData['is_pinned']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Catatan tidak ditemukan atau akses ditolak'
    ]);
}
?>