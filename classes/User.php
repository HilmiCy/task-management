<?php
require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    // Register user baru
    public function register($username, $email, $password, $full_name = '') {
        try {
            // Validasi input
            $errors = [];
            
            if (empty($username) || strlen($username) < 3) {
                $errors[] = "Username minimal 3 karakter";
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email tidak valid";
            }
            
            if (strlen($password) < 6) {
                $errors[] = "Password minimal 6 karakter";
            }
            
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Cek username sudah ada?
            $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $existing = $this->db->fetch($sql, [$username, $email]);
            
            if ($existing) {
                return ['success' => false, 'errors' => ['Username atau email sudah terdaftar']];
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, full_name, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $result = $this->db->insert($sql, [$username, $email, $hashed_password, $full_name]);
            
            if ($result) {
                $user_id = $result;
                
                // Buat default categories untuk user baru
                $this->createDefaultCategories($user_id);
                
                return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
            }
            
            return ['success' => false, 'errors' => ['Registrasi gagal, silakan coba lagi']];
            
        } catch(Exception $e) {
            error_log("Register error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Terjadi kesalahan sistem']];
        }
    }
    
    // Login user
    public function login($username, $password) {
        try {
            // Cek user berdasarkan username atau email
            $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
            $user = $this->db->fetch($sql, [$username, $username]);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Username/email atau password salah'];
            }
            
            // Verifikasi password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Username/email atau password salah'];
            }
            
            // Cek status aktif
            if ($user['is_active'] != 1) {
                return ['success' => false, 'error' => 'Akun Anda tidak aktif. Hubungi administrator.'];
            }
            
            // Update last login
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $this->db->update($sql, [$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'message' => 'Login berhasil!'];
            
        } catch(Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem'];
        }
    }
    
    // Get user by ID
    public function getUserById($id) {
        try {
            $sql = "SELECT id, username, email, full_name, avatar, created_at, last_login 
                    FROM users WHERE id = ?";
            return $this->db->fetch($sql, [$id]);
        } catch(Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    // Update profile
    public function updateProfile($user_id, $data) {
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['full_name'])) {
                $fields[] = "full_name = ?";
                $params[] = $data['full_name'];
            }
            
            if (isset($data['email'])) {
                // Cek email duplikat
                $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                $existing = $this->db->fetch($sql, [$data['email'], $user_id]);
                if ($existing) {
                    return ['success' => false, 'error' => 'Email sudah digunakan user lain'];
                }
                $fields[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (isset($data['avatar'])) {
                $fields[] = "avatar = ?";
                $params[] = $data['avatar'];
            }
            
            if (empty($fields)) {
                return ['success' => false, 'error' => 'Tidak ada data yang diupdate'];
            }
            
            $fields[] = "updated_at = NOW()";
            $params[] = $user_id;
            
            $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
            $result = $this->db->update($sql, $params);
            
            if ($result !== false) {
                return ['success' => true, 'message' => 'Profile berhasil diupdate'];
            }
            
            return ['success' => false, 'error' => 'Gagal mengupdate profile'];
            
        } catch(Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem'];
        }
    }
    
    // Change password
    public function changePassword($user_id, $old_password, $new_password) {
        try {
            // Get current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $user = $this->db->fetch($sql, [$user_id]);
            
            if (!$user) {
                return ['success' => false, 'error' => 'User tidak ditemukan'];
            }
            
            // Verify old password
            if (!password_verify($old_password, $user['password'])) {
                return ['success' => false, 'error' => 'Password lama salah'];
            }
            
            // Validate new password
            if (strlen($new_password) < 6) {
                return ['success' => false, 'error' => 'Password baru minimal 6 karakter'];
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $result = $this->db->update($sql, [$hashed_password, $user_id]);
            
            if ($result !== false) {
                return ['success' => true, 'message' => 'Password berhasil diubah'];
            }
            
            return ['success' => false, 'error' => 'Gagal mengubah password'];
            
        } catch(Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Terjadi kesalahan sistem'];
        }
    }
    
    // Update avatar
    public function updateAvatar($user_id, $avatar_filename) {
        $sql = "UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->update($sql, [$avatar_filename, $user_id]);
    }
    
    // Create default categories for new user
    private function createDefaultCategories($user_id) {
        $default_categories = [
            ['name' => 'Pekerjaan', 'color' => '#28a745', 'icon' => 'fa-briefcase'],
            ['name' => 'Pribadi', 'color' => '#17a2b8', 'icon' => 'fa-user'],
            ['name' => 'Belajar', 'color' => '#ffc107', 'icon' => 'fa-graduation-cap'],
            ['name' => 'Olahraga', 'color' => '#dc3545', 'icon' => 'fa-heartbeat']
        ];
        
        $sql = "INSERT INTO categories (user_id, name, color, icon) VALUES (?, ?, ?, ?)";
        foreach ($default_categories as $category) {
            $this->db->insert($sql, [$user_id, $category['name'], $category['color'], $category['icon']]);
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get current logged in user
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $this->getUserById($_SESSION['user_id']);
        }
        return null;
    }
    
    // Logout
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }
}
?>