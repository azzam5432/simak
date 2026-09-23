<?php
// ============================================
// public/admin/jurusan.php
// CRUD Jurusan
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/MasterDataController.php';

checkAccess(['admin']);

$page_title = 'Master Jurusan';
$masterController = new MasterDataController($pdo);

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = $masterController->createJurusan($_POST);
            redirectWithMessage(
                '/simak_app/public/admin/jurusan.php',
                $result['success'] ? 'Jurusan berhasil ditambahkan!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'update':
            $result = $masterController->updateJurusan($_POST['id'], $_POST);
            redirectWithMessage(
                '/simak_app/public/admin/jurusan.php',
                $result['success'] ? 'Jurusan berhasil diupdate!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'delete':
            $result = $masterController->deleteJurusan($_POST['id']);
            redirectWithMessage(
                '/simak_app/public/admin/jurusan.php',
                $result['success'] ? 'Jurusan berhasil dihapus!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
    }
}

$filter_fakultas = $_GET['fakultas_id'] ?? null;
$jurusan_list = $masterController->getAllJurusan($filter_fakultas);
$fakultas_list = $masterController->getFakultasList();

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-book"></i> Master Jurusan</h3>
        <div>
            <select onchange="window.location.href='?fakultas_id='+this.value" style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
                <option value="">-- Semua Fakultas --</option>
                <?php foreach ($fakultas_list as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $filter_fakultas == $f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Jurusan
            </button>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Jurusan</th>
                <th>Fakultas</th>
                <th>Jenjang</th>
                <th>Ketua Jurusan</th>
                <th>Mahasiswa</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jurusan_list as $j): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($j['kode']) ?></strong></td>
                    <td><?= htmlspecialchars($j['nama']) ?></td>
                    <td><small><?= htmlspecialchars($j['fakultas_nama']) ?></small></td>
                    <td>
                        <span class="status-badge" style="background: #3498db; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                            <?= $j['jenjang'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($j['ketua_jurusan'] ?? '-') ?></td>
                    <td>
                        <span class="status-badge" style="background: #2ecc71; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                            <?= $j['jumlah_mahasiswa'] ?> mhs
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick='editJurusan(<?= json_encode($j, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteJurusan(<?= $j['id'] ?>, '<?= htmlspecialchars($j['nama'], ENT_QUOTES) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($jurusan_list)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox"></i> Belum ada data jurusan
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-plus"></i> Tambah Jurusan</h3>
            <button onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" required>
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Kode Jurusan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode" required placeholder="Contoh: TI, SI" maxlength="10" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Nama Jurusan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: Teknik Informatika">
            </div>
            <div class="form-group">
                <label>Jenjang <span style="color: #e74c3c;">*</span></label>
                <select name="jenjang" required>
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ketua Jurusan</label>
                <input type="text" name="ketua_jurusan" placeholder="Nama ketua jurusan">
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
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-edit"></i> Edit Jurusan</h3>
            <button onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" id="edit_fakultas_id" required>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Kode Jurusan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode" id="edit_kode" required maxlength="10" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Nama Jurusan <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" id="edit_nama" required>
            </div>
            <div class="form-group">
                <label>Jenjang <span style="color: #e74c3c;">*</span></label>
                <select name="jenjang" id="edit_jenjang" required>
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ketua Jurusan</label>
                <input type="text" name="ketua_jurusan" id="edit_ketua_jurusan">
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

function editJurusan(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_fakultas_id').value = data.fakultas_id;
    document.getElementById('edit_kode').value = data.kode;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_jenjang').value = data.jenjang;
    document.getElementById('edit_ketua_jurusan').value = data.ketua_jurusan || '';
    openModal('modalEdit');
}

function deleteJurusan(id, nama) {
    if (confirm('Yakin hapus jurusan "' + nama + '"?')) {
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