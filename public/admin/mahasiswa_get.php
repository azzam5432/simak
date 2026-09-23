<?php
// ============================================
// public/admin/mahasiswa_get.php
// API untuk ambil data mahasiswa (AJAX)
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

$adminController = new AdminController($pdo);
$mahasiswa = $adminController->getMahasiswaById($id);

if ($mahasiswa) {
    echo json_encode([
        'success' => true,
        'mahasiswa_id' => $mahasiswa['mahasiswa_id'],
        'nim' => $mahasiswa['nim'],
        'nama' => $mahasiswa['nama'],
        'email' => $mahasiswa['email'],
        'fakultas_id' => $mahasiswa['fakultas_id'],
        'jurusan_id' => $mahasiswa['jurusan_id'],
        'tahun_ajaran' => $mahasiswa['tahun_ajaran'],
        'tingkat' => $mahasiswa['tingkat'],
        'semester' => $mahasiswa['semester']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Mahasiswa tidak ditemukan'
    ]);
}
?>