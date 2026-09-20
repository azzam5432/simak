<?php

// ============================================
// config/database.php
// Koneksi Database (dibaca dari environment variables / file .env)
//
// Nilai default di bawah adalah default XAMPP (development).
// Untuk production, isi SIMAK_DB_* di file .env di server.
// ============================================

require_once __DIR__ . '/env.php';

$host     = env('SIMAK_DB_HOST', 'localhost');
$db_name  = env('SIMAK_DB_NAME', 'simak_db');
$username = env('SIMAK_DB_USER', 'root');
$password = env('SIMAK_DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

function debugQuery($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
?>