<?php
// ============================================
// public/admin/get_jurusan.php
// API: Get jurusan by fakultas
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json');

// Cek akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit();
}

$fakultas_id = intval($_GET['fakultas_id'] ?? 0);

if ($fakultas_id <= 0) {
    echo json_encode([]);
    exit();
}

$adminController = new AdminController($pdo);
$jurusan = $adminController->getJurusanByFakultas($fakultas_id);

echo json_encode($jurusan);
?>