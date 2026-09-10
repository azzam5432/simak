<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/MahasiswaController.php';

checkAccess(['mahasiswa']);

$page_title = 'Presensi Digital';

$stmt = $pdo->prepare("SELECT id FROM mahasiswa WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$mahasiswa = $stmt->fetch();

if (!$mahasiswa) {
    redirectWithMessage('/simak_app/public/logout.php', 'Data mahasiswa tidak ditemukan', 'error');
}

$controller = new MahasiswaController($pdo, $mahasiswa['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'konfirmasi') {
    $result = $controller->konfirmasiPresensi($_POST['course_id'], $_POST['kode']);
    if ($result['success']) {
        redirectWithMessage('/simak_app/public/mahasiswa/presensi.php', $result['message'], 'success');
    } else {
        redirectWithMessage('/simak_app/public/mahasiswa/presensi.php', $result['message'], 'error');
    }
}

$jadwal = $controller->getJadwalKuliah();
$rekap = $controller->getRekapPresensi();
$sesi_aktif = $_SESSION['presensi_sesi'] ?? null;

$kursus_aktif = [];
if ($sesi_aktif) {
    foreach ($jadwal as $j) {
        if ($j['id'] == $sesi_aktif['course_id']) {
            $kursus_aktif = $j;
            break;
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <div>
        <div class="table-container">
            <h3>Presensi Digital</h3>
            
            <?php if ($sesi_aktif && $kursus_aktif): ?>
                <div style="padding: 15px; background: #d4edda; border-radius: 6px; margin-bottom: 15px;">
                    <h4 style="margin: 0; color: #155724;">🟢 Sesi Presensi Aktif</h4>
                    <p style="margin: 5px 0; color: #155724;">
                        <strong><?= htmlspecialchars($kursus_aktif['kode_mk']) ?></strong> - 
                        <?= htmlspecialchars($kursus_aktif['nama_mk']) ?>
                    </p>
                    <p style="margin: 0; color: #155724; font-size: 13px;">
                        Kode: <strong><?= $sesi_aktif['kode'] ?></strong>
                    </p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="konfirmasi">
                    <input type="hidden" name="course_id" value="<?= $sesi_aktif['course_id'] ?>">
                    
                    <div class="form-group">
                        <label>Masukkan Kode Presensi</label>
                        <input type="text" name="kode" placeholder="Masukkan kode dari dosen" required 
                               style="font-size: 20px; text-align: center; letter-spacing: 5px; padding: 15px;">
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-block" style="padding: 12px; font-size: 16px;">
                        Konfirmasi Kehadiran
                    </button>
                </form>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #7f8c8d;">
                    <div style="font-size: 48px;">⏳</div>
                    <h3>Belum Ada Sesi Presensi</h3>
                    <p>Silakan tunggu dosen membuka sesi presensi.</p>
                    <p style="font-size: 13px; margin-top: 10px;">
                        Atau refresh halaman ini secara berkala.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div class="table-container">
            <h3>📊 Rekap Presensi</h3>
            
            <?php if (empty($rekap)): ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                    Belum ada data presensi
                </p>
            <?php else: ?>
                <?php foreach ($rekap as $r): ?>
                    <div style="padding: 12px 15px; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <strong><?= htmlspecialchars($r['kode_mk']) ?></strong>
                                <div style="font-size: 12px; color: #555;">
                                    <?= htmlspecialchars($r['nama_mk']) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: bold; color: <?= $r['persentase'] >= 75 ? '#2ecc71' : ($r['persentase'] >= 50 ? '#f39c12' : '#e74c3c') ?>">
                                    <?= $r['persentase'] ?>%
                                </div>
                                <div style="font-size: 11px; color: #7f8c8d;">
                                    <?= $r['hadir'] ?>H / <?= $r['izin'] ?>I / <?= $r['sakit'] ?>S / <?= $r['alpa'] ?>A
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 5px; height: 4px; background: #e9ecef; border-radius: 2px; overflow: hidden;">
                            <div style="height: 100%; width: <?= $r['persentase'] ?>%; background: <?= $r['persentase'] >= 75 ? '#2ecc71' : ($r['persentase'] >= 50 ? '#f39c12' : '#e74c3c') ?>; border-radius: 2px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>