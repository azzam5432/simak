<?php
// ============================================
// public/mahasiswa/debug.php
// Debug untuk mahasiswa
// ============================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

echo "<h1>Debug Mahasiswa</h1>";

// 1. Cek session
echo "<h2>1. Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 2. Cek data mahasiswa
echo "<h2>2. Data Mahasiswa</h2>";
$user_id = $_SESSION['user_id'] ?? 0;
echo "User ID: " . $user_id . "<br>";

if ($user_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $mahasiswa = $stmt->fetch();
    
    if ($mahasiswa) {
        echo "<p style='color:green;'><i class='fas fa-check-circle'></i> Mahasiswa ditemukan!</p>";
        echo "<pre>";
        print_r($mahasiswa);
        echo "</pre>";
        echo "<p>Mahasiswa ID: " . $mahasiswa['id'] . "</p>";
        echo "<p>NIM: " . $mahasiswa['nim'] . "</p>";
        echo "<p>Semester: " . $mahasiswa['semester'] . "</p>";
    } else {
        echo "<p style='color:red;'><i class='fas fa-times-circle'></i> Mahasiswa tidak ditemukan untuk user_id: $user_id</p>";
        echo "<p>Solusi: Insert data mahasiswa terlebih dahulu!</p>";
        
        // Cek user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            echo "<p>User ditemukan: " . $user['username'] . " (" . $user['nama'] . ")</p>";
            echo "<p>Role: " . $user['role'] . "</p>";
        }
    }
} else {
    echo "<p style='color:red;'><i class='fas fa-times-circle'></i> User tidak login!</p>";
}

// 3. Cek controller
echo "<h2>3. Controller Test</h2>";
require_once __DIR__ . '/../../controllers/MahasiswaController.php';

if (class_exists('MahasiswaController')) {
    echo "<p style='color:green;'><i class='fas fa-check-circle'></i> MahasiswaController ditemukan</p>";
    
    if (isset($mahasiswa) && $mahasiswa) {
        try {
            $controller = new MahasiswaController($pdo, $mahasiswa['id']);
            $stats = $controller->getDashboardStats();
            echo "<p>Stats berhasil diambil:</p>";
            echo "<pre>";
            print_r($stats);
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p style='color:red;'><i class='fas fa-times-circle'></i> Error: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color:red;'><i class='fas fa-times-circle'></i> MahasiswaController tidak ditemukan</p>";
}

echo "<p><a href='/simak_app/public/index.php' class='btn btn-primary'><i class='fas fa-arrow-left'></i> Kembali ke Login</a></p>";
echo "<p><a href='/simak_app/public/mahasiswa/dashboard.php' class='btn btn-success'><i class='fas fa-arrow-right'></i> Coba Dashboard</a></p>";
?>