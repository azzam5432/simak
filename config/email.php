<?php
// ============================================
// config/email.php
// Konfigurasi Email - Public Config
// ============================================

class EmailConfig {
    private static $config = null;
    
    public static function get($key = null) {
        if (self::$config === null) {
            $configFile = __DIR__ . '/email_config.php';
            
            if (!file_exists($configFile)) {
                throw new Exception('File email_config.php tidak ditemukan!');
            }
            
            self::$config = require $configFile;
        }
        
        if ($key === null) {
            return self::$config;
        }
        
        return self::$config[$key] ?? null;
    }
    
    /**
     * Cek apakah konfigurasi email sudah di-setup
     */
    public static function isConfigured() {
        $config = self::get();
        
        $username = trim((string)($config['username'] ?? ''));
        $password = trim((string)($config['password'] ?? ''));
        
        // Username/password belum diisi
        if ($username === '' || $password === '') {
            return false;
        }
        
        // Password masih placeholder dari template config (diisi huruf 'x' atau kata contoh)
        $placeholders = ['your_app_password', 'app_password', 'password_anda'];
        if (preg_match('/^x{8,}$/i', $password) 
            || in_array(strtolower($password), $placeholders, true)) {
            return false;
        }
        
        return true;
    }
}
?>