<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Validasi IRS';
$adminController = new AdminController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'approve':
            $result = $adminController->approveIRS($_POST['irs_id']);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', 'IRS berhasil disetujui!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', 'Gagal: ' . $result['message'], 'error');
            }
            break;
            
        case 'reject':
            $result = $adminController->rejectIRS($_POST['irs_id'], $_POST['catatan'] ?? null);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', 'IRS ditolak!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', 'Gagal: ' . $result['message'], 'error');
            }
            break;
            
        case 'approve_all':
            $result = $adminController->approveAllIRS($_POST['mahasiswa_id'], $_POST['semester']);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', $result['affected'] . ' IRS berhasil disetujui!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/admin/validasi_irs.php', 'Gagal: ' . $result['message'], 'error');
            }
            break;
    }
}

$pending_irs = $adminController->getPendingIRS();
$stats = $adminController->getIRSStats();

$grouped_irs = [];
foreach ($pending_irs as $irs) {
    $key = $irs['mahasiswa_id'] . '_' . $irs['semester'];
    if (!isset($grouped_irs[$key])) {
        $grouped_irs[$key] = [
            'mahasiswa_id' => $irs['mahasiswa_id'],
            'mahasiswa_nama' => $irs['mahasiswa_nama'],
            'nim' => $irs['nim'],
            'semester' => $irs['semester'],
            'items' => []
        ];
    }
    $grouped_irs[$key]['items'][] = $irs;
}

$status_color = [
    'pending' => '#f39c12',
    'approved' => '#2ecc71',
    'rejected' => '#e74c3c'
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-number" style="color: #f39c12;"><?= $stats['pending'] ?></div>
        <div class="stat-label">⏳ Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #2ecc71;"><?= $stats['approved'] ?></div>
        <div class="stat-label">✅ Approved</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #e74c3c;"><?= $stats['rejected'] ?></div>
        <div class="stat-label">❌ Rejected</div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>⏳ IRS Menunggu Validasi</h3>
        <div>
            <input type="text" id="tableSearch" placeholder="🔍 Cari mahasiswa..." style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
        </div>
    </div>
    
    <?php if (empty($grouped_irs)): ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
            <h3>✅ Semua IRS telah divalidasi</h3>
            <p>Tidak ada IRS yang menunggu persetujuan.</p>
        </div>
    <?php else: ?>
        <?php foreach ($grouped_irs as $group): ?>
            <div style="margin-bottom: 30px; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; background: #fafafa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0;">
                            👤 <?= htmlspecialchars($group['mahasiswa_nama']) ?>
                            <span style="font-weight: normal; font-size: 14px; color: #7f8c8d; margin-left: 10px;">
                                (<?= htmlspecialchars($group['nim']) ?>)
                            </span>
                        </h4>
                        <small style="color: #7f8c8d;">Semester <?= $group['semester'] ?></small>
                    </div>
                    <div>
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Setujui semua IRS untuk <?= htmlspecialchars($group['mahasiswa_nama']) ?>?')">
                            <input type="hidden" name="action" value="approve_all">
                            <input type="hidden" name="mahasiswa_id" value="<?= $group['mahasiswa_id'] ?>">
                            <input type="hidden" name="semester" value="<?= $group['semester'] ?>">
                            <button type="submit" class="btn btn-success btn-sm">✅ Setujui Semua</button>
                        </form>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Ruang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_sks_group = 0;
                        foreach ($group['items'] as $irs):
                            $total_sks_group += $irs['sks'];
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($irs['kode_mk']) ?></strong></td>
                                <td><?= htmlspecialchars($irs['nama_mk']) ?></td>
                                <td><?= $irs['sks'] ?></td>
                                <td><?= htmlspecialchars($irs['ruang'] ?? '-') ?></td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="irs_id" value="<?= $irs['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">✅ Setuju</button>
                                    </form>
                                    <button class="btn btn-danger btn-sm" onclick="showRejectForm(<?= $irs['id'] ?>, '<?= htmlspecialchars($irs['kode_mk']) ?>')">❌ Tolak</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="2" style="text-align: right;">Total SKS</td>
                            <td><?= $total_sks_group ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="modalReject" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);"></div>
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 450px; width: 90%;">
        <h3 style="margin-bottom: 10px;">❌ Tolak IRS</h3>
        <p style="color: #7f8c8d; margin-bottom: 15px;" id="rejectInfo">Menolak mata kuliah: </p>
        <form method="POST">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="irs_id" id="rejectIrsId">
            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="catatan" placeholder="Alasan penolakan..." rows="3"></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-danger">Tolak</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalReject').style.display='none'">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectForm(irsId, kodeMk) {
    document.getElementById('rejectIrsId').value = irsId;
    document.getElementById('rejectInfo').textContent = 'Menolak mata kuliah: ' + kodeMk;
    document.getElementById('modalReject').style.display = 'block';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>