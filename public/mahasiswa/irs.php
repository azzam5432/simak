<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/MahasiswaController.php';

checkAccess(['mahasiswa']);

$page_title = 'IRS - Rencana Studi';

$stmt = $pdo->prepare("SELECT id, semester FROM mahasiswa WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$mahasiswa = $stmt->fetch();

if (!$mahasiswa) {
    redirectWithMessage('/simak_app/public/mahasiswa/dashboard.php', 'Data mahasiswa tidak ditemukan', 'error');
}

$mahasiswa_id = $mahasiswa['id'];
$controller = new MahasiswaController($pdo, $mahasiswa_id);

$semester = $_GET['semester'] ?? $mahasiswa['semester'];
$action = $_POST['action'] ?? '';

if ($action === 'add' && isset($_POST['course_id'])) {
    $result = $controller->createIRS([
        'course_id' => $_POST['course_id'],
        'semester' => $semester
    ]);
    
    if ($result['success']) {
        redirectWithMessage('/simak_app/public/mahasiswa/irs.php?semester=' . $semester, 'Mata kuliah berhasil ditambahkan ke IRS!', 'success');
    } else {
        redirectWithMessage('/simak_app/public/mahasiswa/irs.php?semester=' . $semester, 'Gagal: ' . $result['message'], 'error');
    }
}

if ($action === 'delete' && isset($_POST['irs_id'])) {
    $result = $controller->deleteIRS($_POST['irs_id']);
    
    if ($result['success']) {
        redirectWithMessage('/simak_app/public/mahasiswa/irs.php?semester=' . $semester, 'Mata kuliah dihapus dari IRS!', 'success');
    } else {
        redirectWithMessage('/simak_app/public/mahasiswa/irs.php?semester=' . $semester, 'Gagal: ' . $result['message'], 'error');
    }
}

$irs_list = $controller->getIRS($semester);
$available_courses = $controller->getAvailableCourses($semester);
$total_sks = $controller->getTotalSksIRS($semester);
$status_irs = $controller->getIRSStatus($semester);

$status_color = [
    'pending' => '#f39c12',
    'approved' => '#2ecc71',
    'rejected' => '#e74c3c'
];

$max_sks = 24;

include __DIR__ . '/../../includes/header.php';
?>

<div class="row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <div>
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-clipboard-list"></i> IRS - Semester <?= $semester ?></h3>
                <div>
                    <select onchange="window.location.href='?semester='+this.value" style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= $s == $semester ? 'selected' : '' ?>>
                                Semester <?= $s ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <?php if ($status_irs === 'pending' && $total_sks > 0): ?>
                        <button class="btn btn-success" onclick="printIRS()"><i class="fas fa-print"></i> Cetak IRS</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($status_irs): ?>
                <div style="margin-bottom: 15px; padding: 10px 15px; border-radius: 6px; background: <?= $status_color[$status_irs] ?>20; border: 1px solid <?= $status_color[$status_irs] ?>;">
                    <strong>Status IRS:</strong> 
                    <span class="status-badge" style="background: <?= $status_color[$status_irs] ?>; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                        <?= ucfirst($status_irs) ?>
                    </span>
                    <?php if ($status_irs === 'pending'): ?>
                        <small style="color: #7f8c8d; margin-left: 10px;"><i class="fas fa-hourglass-half"></i> Menunggu validasi admin</small>
                    <?php elseif ($status_irs === 'approved'): ?>
                        <small style="color: #27ae60; margin-left: 10px;"><i class="fas fa-check-circle"></i> Telah divalidasi</small>
                    <?php elseif ($status_irs === 'rejected'): ?>
                        <small style="color: #e74c3c; margin-left: 10px;"><i class="fas fa-times-circle"></i> Ditolak</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 15px; padding: 10px 15px; background: #f8f9fa; border-radius: 6px;">
                <strong>Total SKS:</strong> <?= $total_sks ?> / <?= $max_sks ?> SKS
                <?php if ($total_sks > $max_sks): ?>
                    <span style="color: #e74c3c; margin-left: 10px;"><i class="fas fa-exclamation-triangle"></i> Melebihi batas maksimal!</span>
                <?php endif; ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Ruang</th>
                        <th>Dosen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($irs_list): ?>
                        <?php foreach ($irs_list as $irs): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($irs['kode_mk']) ?></strong></td>
                                <td><?= htmlspecialchars($irs['nama_mk']) ?></td>
                                <td><?= $irs['sks'] ?></td>
                                <td><?= htmlspecialchars($irs['ruang'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($irs['dosen_nama'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?= $status_color[$irs['status']] ?>; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                                        <?= ucfirst($irs['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($irs['status'] === 'pending'): ?>
                                        <button class="btn btn-danger btn-sm btn-delete" onclick="deleteIRS(<?= $irs['id'] ?>)"><i class="fas fa-trash-alt"></i></button>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 30px;">
                                Belum ada mata kuliah di IRS
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div>
        <div class="table-container">
            <h3><i class="fas fa-plus-circle"></i> Tambah Mata Kuliah</h3>
            <p style="color: #7f8c8d; font-size: 13px; margin-bottom: 15px;">
                Pilih mata kuliah yang tersedia untuk semester <?= $semester ?>
            </p>
            
            <?php if ($status_irs === 'pending'): ?>
                <div style="padding: 15px; background: #fff3cd; border-radius: 6px; color: #856404; margin-bottom: 15px;">
                    <i class="fas fa-hourglass-half"></i> IRS sedang dalam proses validasi. Tidak dapat menambah/mengubah mata kuliah.
                </div>
            <?php elseif ($status_irs === 'approved'): ?>
                <div style="padding: 15px; background: #d4edda; border-radius: 6px; color: #155724; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i> IRS telah divalidasi. Tidak dapat menambah/mengubah mata kuliah.
                </div>
            <?php else: ?>
                <?php if ($total_sks >= $max_sks): ?>
                    <div style="padding: 15px; background: #f8d7da; border-radius: 6px; color: #721c24; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-triangle"></i> SKS sudah mencapai batas maksimal (<?= $max_sks ?> SKS).
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <select name="course_id" required style="width: 100%;">
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php foreach ($available_courses as $course): ?>
                                <option value="<?= $course['id'] ?>">
                                    <?= htmlspecialchars($course['kode_mk']) ?> - 
                                    <?= htmlspecialchars($course['nama_mk']) ?> 
                                    (<?= $course['sks'] ?> SKS)
                                    <?= $course['dosen_nama'] ? ' - ' . htmlspecialchars($course['dosen_nama']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" <?= $total_sks >= $max_sks ? 'disabled' : '' ?>>
                        + Tambah ke IRS
                    </button>
                </form>
                
                <?php if (empty($available_courses)): ?>
                    <p style="text-align: center; color: #7f8c8d; margin-top: 15px;">
                        Tidak ada mata kuliah yang tersedia untuk semester ini.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="table-container" style="margin-top: 15px;">
            <h4><i class="fas fa-info-circle"></i> Informasi</h4>
            <ul style="list-style: none; padding: 0; font-size: 13px; color: #555;">
                <li style="padding: 5px 0;">• Maksimal SKS: <strong><?= $max_sks ?> SKS</strong></li>
                <li style="padding: 5px 0;">• Status harus <strong>pending</strong> untuk dapat mengubah</li>
                <li style="padding: 5px 0;">• Setelah <strong>approved</strong>, IRS tidak dapat diubah</li>
                <li style="padding: 5px 0;">• IRS yang <strong>rejected</strong> dapat diubah</li>
            </ul>
        </div>
    </div>
</div>

<script>
function deleteIRS(id) {
    if (confirm('Yakin hapus mata kuliah ini dari IRS?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="irs_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function printIRS() {
    window.open('/simak_app/public/mahasiswa/irs_print.php?semester=<?= $semester ?>', '_blank');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>