<?php

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

$response = ['active' => false];

if (isset($_SESSION['presensi_sesi'])) {
    $sesi = $_SESSION['presensi_sesi'];
    $waktu_mulai = strtotime($sesi['waktu_mulai']);
    $waktu_sekarang = time();
    
    if (($waktu_sekarang - $waktu_mulai) < 1800 && $sesi['aktif']) {
        $response = [
            'active' => true,
            'course_id' => $sesi['course_id'],
            'kode' => $sesi['kode']
        ];
    } else {
        unset($_SESSION['presensi_sesi']);
    }
}

echo json_encode($response);
?>