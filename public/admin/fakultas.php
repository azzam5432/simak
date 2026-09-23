<?php
// ============================================
// public/admin/fakultas.php
// CRUD Fakultas
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/MasterDataController.php';

checkAccess(['admin']);

$page_title = 'Master Fakultas';
$masterController = new MasterDataController($pdo);

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = $masterController->createFakultas($_POST);
            redirectWithMessage(
                '/simak_app/public/admin/fakultas.php',
                $result['success'] ? 'Fakultas berhasil ditambahkan!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'update':
            $result = $masterController->updateFakultas($_POST['id'], $_POST);
            redirectWithMessage(
                '/simak_app/public/admin/fakultas.php',
                $result['success'] ? 'Fakultas berhasil diupdate!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'delete':
            $result = $masterController->deleteFakultas($_POST['id']);
            redirectWithMessage(
                '/simak_app/public/admin/fakultas.php',
                $result['success'] ? 'Fakultas berhasil dihapus!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
    }
}

$fakultas_list = $masterController->getAllFakultas();

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-university"></i> Master Fakultas</h3>
        <div>
            <input type="text" id="tableSearch" placeholder="Cari fakultas..." style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
            <button class="btn btn-primary" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Fakultas
            </button>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Fakultas</th>
                <th>Dekan</th>
                <th>Jurusan</th>
                <th>Mahasiswa</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fakultas_list as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['kode']) ?></strong></td>
                    <td><?= htmlspecialchars($f['nama']) ?></td>
                    <td><?= htmlspecialchars($f['dekan'] ?? '-') ?></td>
                    <td>
                        <span class="status-badge" style="background: #3498db; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                            <?= $f['jumlah_jurusan'] ?> jurusan
                        </span>
                    </td>
                    <td>
                        <span class="status-badge" style="background: #2ecc71; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                            <?= $f['jumlah_mahasiswa'] ?> mhs
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick='editFakultas(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteFakultas(<?= $f['id'] ?>, '<?= htmlspecialchars($f['nama'], ENT_QUOTES) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($fakultas_list)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox"></i> Belum ada data fakultas
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-plus"></i> Tambah Fakultas</h3>
            <button onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Kode Fakultas <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode" required placeholder="Contoh: FT, FE, FIK" maxlength="10" style="text-transform: uppercase;">
                <small style="color: #7f8c8d;">Maksimal 10 karakter, otomatis jadi huruf kapital</small>
            </div>
            <div class="form-group">
                <label>Nama Fakultas <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: Fakultas Teknik">
            </div>
            <div class="form-group">
                <label>Dekan</label>
                <input type="text" name="dekan" placeholder="Nama dekan">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalEdit')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-edit"></i> Edit Fakultas</h3>
            <button onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Kode Fakultas <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode" id="edit_kode" required maxlength="10" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Nama Fakultas <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" id="edit_nama" required>
            </div>
            <div class="form-group">
                <label>Dekan</label>
                <input type="text" name="dekan" id="edit_dekan">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

function editFakultas(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_kode').value = data.kode;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_dekan').value = data.dekan || '';
    openModal('modalEdit');
}

function deleteFakultas(id, nama) {
    if (confirm('Yakin hapus fakultas "' + nama + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>