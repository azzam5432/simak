<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/DosenController.php';

checkAccess(['dosen']);

$page_title = 'Presensi Kelas';

$stmt = $pdo->prepare("SELECT id FROM dosen WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dosen = $stmt->fetch();

if (!$dosen) {
    redirectWithMessage('/simak_app/public/logout.php', 'Data dosen tidak ditemukan', 'error');
}

$controller = new DosenController($pdo, $dosen['id']);

$course_id = $_GET['course_id'] ?? 0;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$matakuliah = $controller->getMatakuliahDosen();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_presensi':
            $data = [
                'course_id' => $_POST['course_id'],
                'tanggal' => $_POST['tanggal'],
                'presensi' => $_POST['presensi'] ?? []
            ];
            $result = $controller->inputPresensiMassal($data);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/dosen/presensi.php?course_id=' . $course_id . '&tanggal=' . $tanggal, 'Presensi berhasil disimpan!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/dosen/presensi.php?course_id=' . $course_id . '&tanggal=' . $tanggal, 'Gagal: ' . $result['message'], 'error');
            }
            break;
            
        case 'open_session':
            $result = $controller->bukaSesiPresensi($_POST['course_id']);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/dosen/presensi.php?course_id=' . $_POST['course_id'], 'Sesi presensi dibuka! Kode: ' . $result['kode'], 'success');
            }
            break;
            
        case 'close_session':
            $result = $controller->tutupSesiPresensi();
            redirectWithMessage('/simak_app/public/dosen/presensi.php?course_id=' . $course_id, 'Sesi presensi ditutup!', 'success');
            break;
    }
}

if ($course_id) {
    $mahasiswa = $controller->getMahasiswaPerKelas($course_id);
    $presensi = $controller->getPresensiByCourse($course_id, $tanggal);
    $rekap = $controller->getRekapPresensi($course_id);
    $sesi_aktif = $controller->cekSesiPresensi();
    
    $presensi_map = [];
    foreach ($presensi as $p) {
        $presensi_map[$p['mahasiswa_id']] = $p['status'];
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container" style="margin-bottom: 20px;">
    <div class="table-header">
        <h3>📋 Pilih Kelas</h3>
    </div>
    <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <select name="course_id" required style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; min-width: 200px;">
            <option value="">-- Pilih Mata Kuliah --</option>
            <?php foreach ($matakuliah as $mk): ?>
                <option value="<?= $mk['id'] ?>" <?= $course_id == $mk['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mk['kode_mk']) ?> - <?= htmlspecialchars($mk['nama_mk']) ?>
                    (<?= $mk['jumlah_mahasiswa'] ?? 0 ?> mhs)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="tanggal" value="<?= $tanggal ?>" style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>
</div>

<?php if ($course_id && !empty($mahasiswa)): ?>

<div class="table-container" style="margin-bottom: 20px;">
    <div class="table-header">
        <h3>🔴 Sesi Presensi</h3>
    </div>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <?php if ($sesi_aktif && $sesi_aktif['course_id'] == $course_id): ?>
            <div style="padding: 10px 15px; background: #d4edda; border-radius: 6px; color: #155724;">
                ✅ Sesi Aktif - Kode: <strong><?= $sesi_aktif['kode'] ?></strong>
                <small style="display: block; color: #155724;">Mulai: <?= $sesi_aktif['waktu_mulai'] ?></small>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="close_session">
                <button type="submit" class="btn btn-danger">🔴 Tutup Sesi</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="open_session">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <button type="submit" class="btn btn-success">🟢 Buka Sesi Presensi</button>
            </form>
            <span style="color: #7f8c8d; align-self: center;">Kode akan muncul setelah sesi dibuka</span>
        <?php endif; ?>
    </div>
</div>

<div class="table-container" style="margin-bottom: 20px;">
    <div class="table-header">
        <h3>📝 Input Presensi - <?= date('d-m-Y', strtotime($tanggal)) ?></h3>
        <div>
            <span style="color: #7f8c8d;">Total Mahasiswa: <?= count($mahasiswa) ?></span>
        </div>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="save_presensi">
        <input type="hidden" name="course_id" value="<?= $course_id ?>">
        <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
        
        <table>
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mahasiswa as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nim']) ?></strong></td>
                        <td><?= htmlspecialchars($m['nama']) ?></td>
                        <td>
                            <select name="presensi[<?= $m['id'] ?>]" required>
                                <option value="hadir" <?= ($presensi_map[$m['id']] ?? '') === 'hadir' ? 'selected' : '' ?>>✅ Hadir</option>
                                <option value="izin" <?= ($presensi_map[$m['id']] ?? '') === 'izin' ? 'selected' : '' ?>>📝 Izin</option>
                                <option value="sakit" <?= ($presensi_map[$m['id']] ?? '') === 'sakit' ? 'selected' : '' ?>>🏥 Sakit</option>
                                <option value="alpa" <?= ($presensi_map[$m['id']] ?? '') === 'alpa' ? 'selected' : '' ?>>❌ Alpa</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 15px;">
            <button type="submit" class="btn btn-primary">💾 Simpan Presensi</button>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>📊 Rekap Presensi</h3>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpa</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rekap as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['nim']) ?></strong></td>
                    <td><?= htmlspecialchars($r['mahasiswa_nama']) ?></td>
                    <td style="color: #2ecc71;"><?= $r['hadir'] ?></td>
                    <td style="color: #f39c12;"><?= $r['izin'] ?></td>
                    <td style="color: #3498db;"><?= $r['sakit'] ?></td>
                    <td style="color: #e74c3c;"><?= $r['alpa'] ?></td>
                    <td><strong><?= $r['total_pertemuan'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($rekap)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        Belum ada data presensi
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($course_id): ?>
    <div class="table-container">
        <p style="text-align: center; color: #7f8c8d; padding: 30px;">
            Belum ada mahasiswa yang terdaftar di kelas ini.
        </p>
    </div>
<?php else: ?>
    <div class="table-container">
        <p style="text-align: center; color: #7f8c8d; padding: 30px;">
            Silakan pilih kelas terlebih dahulu.
        </p>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>