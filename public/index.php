<?php
require_once __DIR__ . '/../config/session.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    $redirect = match($role) {
        'admin' => '/simak_app/public/admin/dashboard.php',
        'dosen' => '/simak_app/public/dosen/dashboard.php',
        'mahasiswa' => '/simak_app/public/mahasiswa/dashboard.php',
        default => '/simak_app/public/logout.php'
    };
    header("Location: " . $redirect);
    exit();
}

$error = $_GET['error'] ?? '';
$errors = [
    'login_failed' => 'Username atau password salah!',
    'session_expired' => 'Sesi Anda telah berakhir. Silakan login kembali.',
    'access_denied' => 'Akses ditolak. Silakan login dengan akun yang valid.'
];
$errorMessage = $errors[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAK - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/simak_app/public/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1><span>SIM</span>AK</h1>
                <p>Sistem Informasi & Manajemen Akademik Kampus</p>
            </div>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i class="fas fa-times-circle"></i></span>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <form action="/simak_app/public/login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <i class="fas fa-copyright"></i> 2026 SIMAK - Politeknik Mitra Industri
            </div>
        </div>
    </div>
</body>
</html>