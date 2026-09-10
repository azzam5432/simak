<?php
// ============================================
// public/admin/mahasiswa_get.php
// API untuk mengambil data mahasiswa (AJAX)
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

// Cek akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

$adminController = new AdminController($pdo);
$mahasiswa = $adminController->getMahasiswaById($id);

header('Content-Type: application/json');

if ($mahasiswa) {
    echo json_encode([
        'success' => true,
        'mahasiswa_id' => $mahasiswa['mahasiswa_id'],
        'nim' => $mahasiswa['nim'],
        'nama' => $mahasiswa['nama'],
        'email' => $mahasiswa['email'],
        'program_studi' => $mahasiswa['program_studi'],
        'angkatan' => $mahasiswa['angkatan'],
        'semester' => $mahasiswa['semester']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Mahasiswa tidak ditemukan'
    ]);
}
?>