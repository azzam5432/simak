<?php
// ============================================
// public/admin/matakuliah_get.php
// API untuk mengambil data mata kuliah (AJAX)
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
$matakuliah = $adminController->getMatakuliahById($id);

header('Content-Type: application/json');

if ($matakuliah) {
    echo json_encode([
        'success' => true,
        'id' => $matakuliah['id'],
        'kode_mk' => $matakuliah['kode_mk'],
        'nama_mk' => $matakuliah['nama_mk'],
        'sks' => $matakuliah['sks'],
        'semester' => $matakuliah['semester'],
        'program_studi' => $matakuliah['program_studi'],
        'dosen_id' => $matakuliah['dosen_id'],
        'ruang' => $matakuliah['ruang'],
        'hari' => $matakuliah['hari'],
        'jam_mulai' => $matakuliah['jam_mulai'],
        'jam_selesai' => $matakuliah['jam_selesai'],
        'kapasitas' => $matakuliah['kapasitas']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Mata kuliah tidak ditemukan'
    ]);
}
?>