<?php
require_once __DIR__ . '/Database.php'; // ini file yang berisi class DB

class Task {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    // Get total tasks for a user
    public function getTotalTasks($user_id) {
        $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND is_archived = 0";
        $result = $this->db->fetch($sql, [$user_id]);
        return $result['total'] ?? 0;
    }
    
    // Get completed tasks count (status = 'completed')
    public function getCompletedTasks($user_id) {
        $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = 'completed' AND is_archived = 0";
        $result = $this->db->fetch($sql, [$user_id]);
        return $result['total'] ?? 0;
    }
    
    // Get pending tasks count (status = 'pending')
    public function getPendingTasks($user_id) {
        $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = 'pending' AND is_archived = 0";
        $result = $this->db->fetch($sql, [$user_id]);
        return $result['total'] ?? 0;
    }
    
    // Get in progress tasks count
    public function getInProgressTasks($user_id) {
        $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = 'in_progress' AND is_archived = 0";
        $result = $this->db->fetch($sql, [$user_id]);
        return $result['total'] ?? 0;
    }
    
    // Get today's tasks (based on due_date)
    public function getTodayTasks($user_id) {
        $sql = "
            SELECT t.*, c.name as category_name, c.color as category_color 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.due_date = CURDATE()
            AND t.is_archived = 0
            ORDER BY t.due_time ASC, FIELD(t.priority, 'high', 'medium', 'low')
        ";
        return $this->db->fetchAll($sql, [$user_id]);
    }
    
    // Get upcoming tasks (within next X days)
    public function getUpcomingTasks($user_id, $days = 3) {
        $sql = "
            SELECT t.*, c.name as category_name, c.color as category_color 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.status != 'completed'
            AND t.status != 'cancelled'
            AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND t.is_archived = 0
            ORDER BY t.due_date ASC, FIELD(t.priority, 'high', 'medium', 'low')
            LIMIT 10
        ";
        return $this->db->fetchAll($sql, [$user_id, $days]);
    }
    
    // Get overdue tasks (due_date < today and not completed)
    public function getOverdueTasks($user_id) {
        $sql = "
            SELECT t.*, c.name as category_name, c.color as category_color 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.status != 'completed'
            AND t.status != 'cancelled'
            AND t.due_date < CURDATE()
            AND t.is_archived = 0
            ORDER BY t.due_date ASC
            LIMIT 10
        ";
        return $this->db->fetchAll($sql, [$user_id]);
    }
    
    // Get recent activities
    public function getRecentActivities($user_id, $limit = 10) {
        $sql = "
            (SELECT 
                'task' as type,
                id,
                title as description,
                created_at,
                'tasks' as icon
            FROM tasks 
            WHERE user_id = ? AND is_archived = 0)
            UNION ALL
            (SELECT 
                'note' as type,
                id,
                title as description,
                created_at,
                'sticky-note' as icon
            FROM notes 
            WHERE user_id = ?)
            ORDER BY created_at DESC
            LIMIT ?
        ";
        return $this->db->fetchAll($sql, [$user_id, $user_id, $limit]);
    }
    
    // Get task by ID
    public function getTaskById($id, $user_id) {
        $sql = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
        return $this->db->fetch($sql, [$id, $user_id]);
    }
    
    // Create new task
    public function createTask($data) {
        $sql = "
            INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, due_time, estimated_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        return $this->db->insert($sql, [
            $data['user_id'],
            $data['category_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $data['due_date'],
            $data['due_time'] ?? null,
            $data['estimated_hours'] ?? 0
        ]);
    }
    
    // Update task
    public function updateTask($id, $user_id, $data) {
        $sql = "
            UPDATE tasks 
            SET category_id = ?, title = ?, description = ?, priority = ?, due_date = ?, due_time = ?, estimated_hours = ?
            WHERE id = ? AND user_id = ?
        ";
        return $this->db->update($sql, [
            $data['category_id'] ?? null,
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['due_date'],
            $data['due_time'] ?? null,
            $data['estimated_hours'] ?? 0,
            $id,
            $user_id
        ]);
    }
    
    // Update task status
    public function updateStatus($id, $user_id, $status) {
        $completed_at = ($status == 'completed') ? date('Y-m-d H:i:s') : null;
        $sql = "UPDATE tasks SET status = ?, completed_at = ? WHERE id = ? AND user_id = ?";
        return $this->db->update($sql, [$status, $completed_at, $id, $user_id]);
    }
    
    // Toggle task completion
    public function toggleTask($id, $user_id) {
        $task = $this->getTaskById($id, $user_id);
        if (!$task) return false;
        
        $newStatus = ($task['status'] == 'completed') ? 'pending' : 'completed';
        return $this->updateStatus($id, $user_id, $newStatus);
    }
    
    // Soft delete task (archive)
    public function deleteTask($id, $user_id) {
        $sql = "UPDATE tasks SET is_archived = 1 WHERE id = ? AND user_id = ?";
        return $this->db->update($sql, [$id, $user_id]);
    }
    
    // Hard delete task
    public function hardDeleteTask($id, $user_id) {
        $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        return $this->db->delete($sql, [$id, $user_id]);
    }
    
    // Get tasks by category
    public function getTasksByCategory($user_id, $category_id) {
        $sql = "
            SELECT t.*, c.name as category_name 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.category_id = ? AND t.is_archived = 0
            ORDER BY t.due_date ASC
        ";
        return $this->db->fetchAll($sql, [$user_id, $category_id]);
    }
    
    // Search tasks
    public function searchTasks($user_id, $keyword) {
        $sql = "
            SELECT t.*, c.name as category_name 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? 
            AND t.is_archived = 0
            AND (t.title LIKE ? OR t.description LIKE ?)
            ORDER BY t.created_at DESC
        ";
        $searchTerm = "%{$keyword}%";
        return $this->db->fetchAll($sql, [$user_id, $searchTerm, $searchTerm]);
    }
    
    // Get tasks by status
    public function getTasksByStatus($user_id, $status) {
        $sql = "
            SELECT t.*, c.name as category_name 
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = ? AND t.status = ? AND t.is_archived = 0
            ORDER BY t.due_date ASC
        ";
        return $this->db->fetchAll($sql, [$user_id, $status]);
    }
    
    // Get task statistics
    public function getTaskStatistics($user_id) {
        $sql = "
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as progress_tasks,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_count,
                SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium_priority_count,
                SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low_priority_count
            FROM tasks 
            WHERE user_id = ? AND is_archived = 0
        ";
        $result = $this->db->fetch($sql, [$user_id]);
        
        // Return dengan key yang konsisten
        return [
            'total' => $result['total_tasks'] ?? 0,
            'completed' => $result['completed_tasks'] ?? 0,
            'pending' => $result['pending_tasks'] ?? 0,
            'in_progress' => $result['progress_tasks'] ?? 0,
            'high' => $result['high_priority_count'] ?? 0,
            'medium' => $result['medium_priority_count'] ?? 0,
            'low' => $result['low_priority_count'] ?? 0
        ];
}
}
?>