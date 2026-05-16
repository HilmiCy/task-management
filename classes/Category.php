<?php
require_once __DIR__ . '/Database.php';

class Category {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    // Get all categories for a user
    public function getAllCategories($user_id) {
        $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC";
        return $this->db->fetchAll($sql, [$user_id]);
    }
    
    // Get category by ID
    public function getCategoryById($id, $user_id) {
        $sql = "SELECT * FROM categories WHERE id = ? AND user_id = ?";
        return $this->db->fetch($sql, [$id, $user_id]);
    }
    
    // Create new category
    public function createCategory($data) {
        $sql = "INSERT INTO categories (user_id, name, description, color, icon) VALUES (?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['color'] ?? '#2c7a6e',
            $data['icon'] ?? 'fa-tag'
        ]);
    }
    
    // Update category
    public function updateCategory($id, $user_id, $data) {
        $sql = "UPDATE categories SET name = ?, description = ?, color = ?, icon = ? WHERE id = ? AND user_id = ?";
        return $this->db->update($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['color'] ?? '#2c7a6e',
            $data['icon'] ?? 'fa-tag',
            $id,
            $user_id
        ]);
    }
    
    // Delete category
    public function deleteCategory($id, $user_id) {
        // First, set category_id to NULL for tasks in this category
        $sql1 = "UPDATE tasks SET category_id = NULL WHERE category_id = ? AND user_id = ?";
        $this->db->update($sql1, [$id, $user_id]);
        
        // Then delete the category
        $sql2 = "DELETE FROM categories WHERE id = ? AND user_id = ?";
        return $this->db->delete($sql2, [$id, $user_id]);
    }
    
    // Get task statistics per category
    public function getTaskStats($user_id) {
        $sql = "
            SELECT 
                c.id,
                c.name,
                c.color,
                c.icon,
                COUNT(t.id) as total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                ROUND(
                    CASE 
                        WHEN COUNT(t.id) > 0 
                        THEN (SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100 
                        ELSE 0 
                    END, 0
                ) as completion_rate
            FROM categories c
            LEFT JOIN tasks t ON c.id = t.category_id AND t.user_id = ? AND t.is_archived = 0
            WHERE c.user_id = ?
            GROUP BY c.id, c.name, c.color, c.icon
            ORDER BY total_tasks DESC
        ";
        $result = $this->db->fetchAll($sql, [$user_id, $user_id]);
        
        // Format for display
        $formatted = [];
        foreach ($result as $cat) {
            $formatted[] = [
                'id' => $cat['id'],
                'name' => $cat['name'],
                'color' => $cat['color'],
                'icon' => $cat['icon'],
                'total' => (int)$cat['total_tasks'],
                'completed' => (int)$cat['completed_tasks'],
                'pending' => (int)$cat['pending_tasks'],
                'in_progress' => (int)$cat['in_progress_tasks'],
                'completion_rate' => (int)$cat['completion_rate']
            ];
        }
        return $formatted;
    }
    
    // Get category with task count
    public function getCategoriesWithCount($user_id) {
        $sql = "
            SELECT 
                c.*,
                COUNT(t.id) as task_count
            FROM categories c
            LEFT JOIN tasks t ON c.id = t.category_id AND t.user_id = ? AND t.is_archived = 0
            WHERE c.user_id = ?
            GROUP BY c.id
            ORDER BY c.name ASC
        ";
        return $this->db->fetchAll($sql, [$user_id, $user_id]);
    }
}
?>