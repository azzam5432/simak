<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/MahasiswaController.php';

checkAccess(['mahasiswa']);

$page_title = 'Jadwal Kuliah';

$stmt = $pdo->prepare("SELECT id FROM mahasiswa WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$mahasiswa = $stmt->fetch();

if (!$mahasiswa) {
    redirectWithMessage('/simak_app/public/logout.php', 'Data mahasiswa tidak ditemukan', 'error');
}

$controller = new MahasiswaController($pdo, $mahasiswa['id']);
$jadwal = $controller->getJadwalKuliah();

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
        <h3><i class="fas fa-calendar-alt"></i> Jadwal Kuliah</h3>
        <small style="color: #7f8c8d;">Semester <?= date('Y') ?></small>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-top: 15px;">
        <?php foreach ($hari_urutan as $hari): ?>
            <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 10px; min-height: 200px; background: <?= date('N') == array_search($hari, $hari_urutan) + 1 ? '#f0f7ff' : '#fff' ?>">
                <h4 style="text-align: center; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #3498db; color: #2c3e50;">
                    <?= $hari ?>
                    <?php if (date('N') == array_search($hari, $hari_urutan) + 1): ?>
                        <span style="font-size: 10px; color: #3498db;">(Hari Ini)</span>
                    <?php endif; ?>
                </h4>
                
                <?php if (empty($jadwal_group[$hari])): ?>
                    <p style="text-align: center; color: #bdc3c7; font-size: 12px; margin-top: 30px;">- Kosong -</p>
                <?php else: ?>
                    <?php foreach ($jadwal_group[$hari] as $j): ?>
                        <div style="background: #f8f9fa; padding: 8px 10px; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #3498db;">
                            <div style="font-weight: bold; font-size: 13px;">
                                <?= htmlspecialchars($j['kode_mk']) ?>
                            </div>
                            <div style="font-size: 12px; color: #555;">
                                <?= htmlspecialchars($j['nama_mk']) ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                <i class="fas fa-clock"></i> <?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                <i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($j['dosen_nama'] ?? '-') ?>
                            </div>
                            <div style="font-size: 11px; color: #7f8c8d;">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($j['ruang'] ?? '-') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>