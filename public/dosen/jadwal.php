<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/DosenController.php';

checkAccess(['dosen']);

$page_title = 'Jadwal Mengajar';

$stmt = $pdo->prepare("SELECT id FROM dosen WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dosen = $stmt->fetch();

if (!$dosen) {
    redirectWithMessage('/simak_app/public/logout.php', 'Data dosen tidak ditemukan', 'error');
}

$controller = new DosenController($pdo, $dosen['id']);
$jadwal = $controller->getJadwalMengajar();

$hari_urutan = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$jadwal_group = [];

foreach ($hari_urutan as $hari) {
    $jadwal_group[$hari] = array_filter($jadwal, function($item) use ($hari) {
        return $item['hari'] === $hari;
    });
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3>📅 Jadwal Mengajar</h3>
        <small style="color: #7f8c8d;">Semester <?= date('Y') ?></small>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-top: 15px;">
        <?php foreach ($hari_urutan as $hari): ?>
            <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 10px; min-height: 200px;">
                <h4 style="text-align: center; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #3498db; color: #2c3e50;">
                    <?= $hari ?>
                </h4>
                
                <?php if (empty($jadwal_group[$hari])): ?>
                    <p style="text-align: center; color: #bdc3c7; font-size: 12px; margin-top: 30px;">- Kosong -</p>
                <?php else: ?>
                    <?php foreach ($jadwal_group[$hari] as $jk): ?>
                        <div style="background: #f8f9fa; padding: 8px 10px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #3498db;">
                            <div style="font-weight: bold; font-size: 13px;">
                                <?= htmlspecialchars($jk['kode_mk']) ?>
                            </div>
                            <div style="font-size: 12px; color: #555;">
                                <?= htmlspecialchars($jk['nama_mk']) ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                🕐 <?= date('H:i', strtotime($jk['jam_mulai'])) ?> - <?= date('H:i', strtotime($jk['jam_selesai'])) ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                📍 <?= htmlspecialchars($jk['ruang'] ?? 'TBD') ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                👥 <?= $jk['jumlah_mahasiswa'] ?? 0 ?> mahasiswa
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>