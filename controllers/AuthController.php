<?php
// ============================================
// controllers/AuthController.php
// Logika Login & Logout
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

class AuthController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Proses login user
     */
    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username dan password harus diisi'];
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['login_time'] = time();
            
            return [
                'success' => true, 
                'role' => $user['role'],
                'user_id' => $user['id'],
                'message' => 'Login berhasil'
            ];
        }
        
        return ['success' => false, 'message' => 'Username atau password salah'];
    }
    
    /**
     * Proses logout
     */
    public function logout() {
        $_SESSION = [];
        session_destroy();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        return ['success' => true, 'message' => 'Logout berhasil'];
    }
    
    /**
     * Cek session user
     */
    public function checkSession() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Get user role
     */
    public function getCurrentRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Get user name
     */
    public function getCurrentName() {
        return $_SESSION['nama'] ?? 'Pengguna';
    }
}
?>