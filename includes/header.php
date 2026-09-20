<?php
// ============================================
// includes/header.php
// Template Header & Navigation
// ============================================

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    header("Location: /simak_app/public/index.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
$initial = strtoupper(substr($nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAK - <?= ucfirst($role) ?> Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/simak_app/public/assets/css/style.css">
    <link rel="icon" href="/simak_app/public/assets/favicon.ico" type="image/x-icon">
</head>
<body>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-container">
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap brand-icon"></i>
            <h2>SIMAK</h2>
            <small>Politeknik Mitra Industri</small>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-label">Navigasi Utama</li>
            
            <?php if ($role === 'admin'): ?>
                <li>
                    <a href="/simak_app/public/admin/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/admin/mahasiswa.php" class="<?= $current_page === 'mahasiswa.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-graduate"></i> Mahasiswa
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/admin/dosen.php" class="<?= $current_page === 'dosen.php' ? 'active' : '' ?>">
                        <i class="fas fa-chalkboard-teacher"></i> Dosen
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/admin/matakuliah.php" class="<?= $current_page === 'matakuliah.php' ? 'active' : '' ?>">
                        <i class="fas fa-book-open"></i> Mata Kuliah
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/admin/validasi_irs.php" class="<?= $current_page === 'validasi_irs.php' ? 'active' : '' ?>">
                        <i class="fas fa-check-double"></i> Validasi IRS
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM irs WHERE status = 'pending'");
                        $pending = $stmt->fetch()['total'];
                        if ($pending > 0): 
                        ?>
                            <span class="badge"><?= $pending ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/admin/verifikasi_nilai.php" class="<?= $current_page === 'verifikasi_nilai.php' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-check"></i> Verifikasi Nilai
                        <?php 
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM grades WHERE status_verifikasi = 'draft'");
                        $draft = $stmt->fetch()['total'];
                        if ($draft > 0): 
                        ?>
                            <span class="badge"><?= $draft ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- MENU LAPORAN AKADEMIK -->
                <li>
                    <a href="/simak_app/public/admin/laporan.php" class="<?= strpos($current_page, 'laporan') !== false ? 'active' : '' ?>">
                        <i class="fas fa-file-alt"></i> Laporan
                    </a>
                </li>
                
                <li>
                    <a href="/simak_app/public/admin/broadcast.php" class="<?= $current_page === 'broadcast.php' ? 'active' : '' ?>">
                        <i class="fas fa-bullhorn"></i> Broadcast
                    </a>
                </li>
                
            <?php elseif ($role === 'dosen'): ?>
                <li>
                    <a href="/simak_app/public/dosen/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/dosen/jadwal.php" class="<?= $current_page === 'jadwal.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Jadwal Mengajar
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/dosen/presensi.php" class="<?= $current_page === 'presensi.php' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list"></i> Presensi
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/dosen/nilai.php" class="<?= $current_page === 'nilai.php' ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i> Input Nilai
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/dosen/tugas.php" class="<?= $current_page === 'tugas.php' ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i> Tugas
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/dosen/broadcast.php" class="<?= $current_page === 'broadcast.php' ? 'active' : '' ?>">
                        <i class="fas fa-bullhorn"></i> Broadcast Kelas
                    </a>
                </li>
                
            <?php elseif ($role === 'mahasiswa'): ?>
                <li>
                    <a href="/simak_app/public/mahasiswa/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/mahasiswa/jadwal.php" class="<?= $current_page === 'jadwal.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i> Jadwal Kuliah
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/mahasiswa/tugas.php" class="<?= $current_page === 'tugas.php' ? 'active' : '' ?>">
                        <i class="fas fa-tasks"></i> Tugas
                        <?php 
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as total
                            FROM tasks t
                            JOIN irs i ON t.course_id = i.course_id
                            JOIN mahasiswa m ON i.mahasiswa_id = m.id
                            WHERE m.user_id = ? 
                                AND t.deadline > NOW()
                                AND t.deadline < DATE_ADD(NOW(), INTERVAL 2 DAY)
                                AND i.status = 'approved'
                                AND NOT EXISTS (
                                    SELECT 1 FROM task_submissions ts 
                                    WHERE ts.task_id = t.id AND ts.mahasiswa_id = m.id
                                )
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $urgent = $stmt->fetch()['total'];
                        if ($urgent > 0): 
                        ?>
                            <span class="badge"><?= $urgent ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/mahasiswa/irs.php" class="<?= $current_page === 'irs.php' ? 'active' : '' ?>">
                        <i class="fas fa-file-signature"></i> IRS
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/mahasiswa/khs.php" class="<?= $current_page === 'khs.php' ? 'active' : '' ?>">
                        <i class="fas fa-star"></i> KHS & IPK
                    </a>
                </li>
                <li>
                    <a href="/simak_app/public/mahasiswa/presensi.php" class="<?= $current_page === 'presensi.php' ? 'active' : '' ?>">
                        <i class="fas fa-check-circle"></i> Presensi
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="menu-label">Akun</li>
            <li>
                <a href="/simak_app/public/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1><?= $page_title ?? 'Dashboard' ?></h1>
                <span class="breadcrumb">/ <?= ucfirst($role) ?></span>
            </div>
            <div class="user-info">
                <span class="user-badge"><i class="fas fa-user-circle"></i> <?= ucfirst($role) ?></span>
                <span class="user-name"><?= htmlspecialchars($nama) ?></span>
                <div class="user-avatar"><?= $initial ?></div>
            </div>
        </div>

        <?php 
        $flash = getFlashMessage(); 
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <span class="alert-icon">
                    <?php 
                    if ($flash['type'] === 'success') {
                        echo '<i class="fas fa-check-circle"></i>';
                    } elseif ($flash['type'] === 'error') {
                        echo '<i class="fas fa-times-circle"></i>';
                    } elseif ($flash['type'] === 'warning') {
                        echo '<i class="fas fa-exclamation-triangle"></i>';
                    } else {
                        echo '<i class="fas fa-info-circle"></i>';
                    }
                    ?>
                </span>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>