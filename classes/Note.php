<?php
require_once __DIR__ . '/Database.php';

class Note {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    // Get all notes for a user
    public function getAllNotes($user_id) {
        $sql = "SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql, [$user_id]);
    }
    
    // Get recent notes (limited)
    public function getRecentNotes($user_id, $limit = 5) {
        $sql = "SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$user_id, $limit]);
    }
    
    // Get pinned notes
    public function getPinnedNotes($user_id) {
        $sql = "SELECT * FROM notes WHERE user_id = ? AND is_pinned = 1 ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql, [$user_id]);
    }
    
    // Get note by ID
    public function getNoteById($id, $user_id) {
        $sql = "SELECT * FROM notes WHERE id = ? AND user_id = ?";
        return $this->db->fetch($sql, [$id, $user_id]);
    }
    
    // Get notes by type
    public function getNotesByType($user_id, $type) {
        $sql = "SELECT * FROM notes WHERE user_id = ? AND note_type = ? ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql, [$user_id, $type]);
    }
    
    // Create new note
    public function createNote($data) {
        $sql = "INSERT INTO notes (user_id, task_id, title, content, note_type, is_pinned) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $data['user_id'],
            $data['task_id'] ?? null,
            $data['title'],
            $data['content'] ?? null,
            $data['note_type'] ?? 'personal',
            $data['is_pinned'] ?? 0
        ]);
    }
    
    // Update note
    public function updateNote($id, $user_id, $data) {
        $sql = "UPDATE notes SET title = ?, content = ?, note_type = ?, is_pinned = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        return $this->db->update($sql, [
            $data['title'],
            $data['content'] ?? null,
            $data['note_type'] ?? 'personal',
            $data['is_pinned'] ?? 0,
            $id,
            $user_id
        ]);
    }
    
    // Toggle pin note
    public function togglePin($id, $user_id) {
        // Ambil data note saat ini
        $note = $this->getNoteById($id, $user_id);
        if (!$note) {
            return false;
        }
        
        // Toggle nilai is_pinned
        $newPin = ($note['is_pinned'] == 1) ? 0 : 1;
        
        // Update database
        $sql = "UPDATE notes SET is_pinned = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $result = $this->db->update($sql, [$newPin, $id, $user_id]);
        
        return $result > 0;
    }
    
    // Delete note
    public function deleteNote($id, $user_id) {
        $sql = "DELETE FROM notes WHERE id = ? AND user_id = ?";
        return $this->db->delete($sql, [$id, $user_id]);
    }
    
    // Search notes
    public function searchNotes($user_id, $keyword) {
        $sql = "
            SELECT * FROM notes 
            WHERE user_id = ? 
            AND (title LIKE ? OR content LIKE ?)
            ORDER BY updated_at DESC
        ";
        $searchTerm = "%{$keyword}%";
        return $this->db->fetchAll($sql, [$user_id, $searchTerm, $searchTerm]);
    }
    
    // Get notes for a specific task
    public function getNotesByTask($user_id, $task_id) {
        $sql = "SELECT * FROM notes WHERE user_id = ? AND task_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$user_id, $task_id]);
    }
}
?>