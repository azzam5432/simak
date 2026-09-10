<?php
// ============================================
// public/admin/dosen_get.php
// API untuk mengambil data dosen (AJAX)
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
$dosen = $adminController->getDosenById($id);

header('Content-Type: application/json');

if ($dosen) {
    echo json_encode([
        'success' => true,
        'dosen_id' => $dosen['dosen_id'],
        'nidn' => $dosen['nidn'],
        'nama' => $dosen['nama'],
        'email' => $dosen['email'],
        'program_studi' => $dosen['program_studi'],
        'jabatan' => $dosen['jabatan']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dosen tidak ditemukan'
    ]);
}
?>