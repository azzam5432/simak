<?php
// ============================================
// public/admin/dosen_get.php
// API: Get data dosen untuk edit
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json');

// Cek akses
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
$dosen = $adminController->getDosenById($id);

if ($dosen) {
    echo json_encode([
        'success' => true,
        'dosen_id' => $dosen['dosen_id'],
        'nid' => $dosen['nid'],
        'nama' => $dosen['nama'],
        'email' => $dosen['email'],
        'fakultas_id' => $dosen['fakultas_id'],
        'jurusan_id' => $dosen['jurusan_id']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dosen tidak ditemukan'
    ]);
}
?>