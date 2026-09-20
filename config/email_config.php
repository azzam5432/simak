<?php
// ============================================
// config/email_config.php
// Konfigurasi Email (dibaca dari environment variables / file .env)
//
// AMAN DI-COMMIT: tidak ada kredensial di file ini.
// Cara setup:
//   1. Salin .env.example menjadi .env
//   2. Isi kredensial SMTP di file .env
//   3. File .env otomatis diabaikan git (lihat .gitignore)
// ============================================

require_once __DIR__ . '/env.php';

$username = trim((string) env('SIMAK_EMAIL_USERNAME', ''));

return [
    // ============================================
    // SMTP Configuration
    // ============================================
    'host'       => env('SIMAK_EMAIL_HOST', 'smtp.gmail.com'),
    'port'       => (int) env('SIMAK_EMAIL_PORT', 587),
    'encryption' => env('SIMAK_EMAIL_ENCRYPTION', 'tls'),  // 'tls' atau 'ssl'
    'auth'       => env_bool('SIMAK_EMAIL_AUTH', true),

    // ============================================
    // Kredensial Akun Sekolah/Kampus
    // ============================================
    // Diisi via SIMAK_EMAIL_USERNAME & SIMAK_EMAIL_PASSWORD di file .env
    // (App Password 16 karakter untuk Gmail)
    'username'   => $username,
    'password'   => (string) env('SIMAK_EMAIL_PASSWORD', ''),

    // ============================================
    // Pengirim
    // ============================================
    // FROM_EMAIL kosong otomatis memakai USERNAME
    'from_email' => env('SIMAK_EMAIL_FROM_EMAIL', $username),
    'from_name'  => env('SIMAK_EMAIL_FROM_NAME', 'SIMAK - Politeknik Mitra Industri'),

    // ============================================
    // Reply-To (opsional)
    // ============================================
    'reply_email' => env('SIMAK_EMAIL_REPLY_EMAIL', 'noreply@politeknikmitra.ac.id'),
    'reply_name'  => env('SIMAK_EMAIL_REPLY_NAME', 'SIMAK No Reply'),

    // ============================================
    // Debug Mode (untuk testing)
    // ============================================
    // 0 = off (production)
    // 1 = client messages
    // 2 = client and server messages (development)
    'debug' => (int) env('SIMAK_EMAIL_DEBUG', 0),

    // ============================================
    // Log Email
    // ============================================
    'log_email' => env_bool('SIMAK_EMAIL_LOG', true),
    'log_path'  => __DIR__ . '/../logs/email/',
];
