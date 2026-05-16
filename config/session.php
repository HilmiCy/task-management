<?php
require_once __DIR__ . '/config.php';

class Session {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Set session data
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    // Get session data
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    // Check if session exists
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    // Delete session
    public function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    // Destroy all sessions
    public function destroy() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // Set user login
    public function setUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            $this->destroy();
            return false;
        }
        
        return true;
    }
    
    // Get current user ID
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user data
    public function getUser() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email' => $_SESSION['email'] ?? null
        ];
    }
    
    // Regenerate session ID (security)
    public function regenerate() {
        session_regenerate_id(true);
    }
    
    // Set flash message
    public function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type, // success, error, warning, info
            'message' => $message
        ];
    }
    
    // Get flash message
    public function getFlash() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
    
    // Set CSRF token
    public function setCsrfToken() {
        if (!isset($_SESSION[CSRF_TOKEN_KEY])) {
            $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_KEY];
    }
    
    // Verify CSRF token
    public function verifyCsrfToken($token) {
        return isset($_SESSION[CSRF_TOKEN_KEY]) && hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
    }
}

// Initialize session
$session = new Session();
?>