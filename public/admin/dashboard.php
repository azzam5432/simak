<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Dashboard Admin';
$adminController = new AdminController($pdo);
$stats = $adminController->getDashboardStats();

include __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-number"><?= $stats['total_mahasiswa'] ?></div>
        <div class="stat-label">Total Mahasiswa</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-number"><?= $stats['total_dosen'] ?></div>
        <div class="stat-label">Total Dosen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-number"><?= $stats['total_matakuliah'] ?></div>
        <div class="stat-label">Total Mata Kuliah</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-number" style="color: <?= $stats['irs_pending'] > 0 ? '#e74c3c' : '#2ecc71' ?>">
            <?= $stats['irs_pending'] ?>
        </div>
        <div class="stat-label">IRS Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-number" style="color: <?= $stats['nilai_draft'] > 0 ? '#f39c12' : '#2ecc71' ?>">
            <?= $stats['nilai_draft'] ?>
        </div>
        <div class="stat-label">Nilai Draft</div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-history"></i> Aktivitas Terbaru</h3>
    </div>
    <p style="color: #7f8c8d; text-align: center; padding: 20px;">
        <i class="fas fa-user-circle"></i> Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>! 
        Silakan kelola data melalui menu di samping.
    </p>
</div>

<style>
/* ===== STAT CARD ICON ===== */
.stat-card .stat-icon {
    font-size: 28px;
    color: #7f8c8d;  /* Abu-abu */
    margin-bottom: 6px;
    opacity: 0.7;
}

.stat-card .stat-icon i {
    display: block;
}

/* ===== TABLE HEADER ICON ===== */
.table-header h3 i {
    margin-right: 8px;
    color: #7f8c8d;  /* Abu-abu */
}

/* ===== WELCOME ICON ===== */
.table-container p i {
    margin-right: 6px;
    color: #7f8c8d;  /* Abu-abu */
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>