<?php
// ============================================
// public/admin/dosen.php
// CRUD Data Dosen (Revisi - NID, Fakultas, Jurusan)
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Data Dosen';
$adminController = new AdminController($pdo);

// ============================================
// PROSES CRUD
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $adminController->createDosen($_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Dosen berhasil ditambahkan!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'update':
                $result = $adminController->updateDosen($_POST['dosen_id'], $_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Dosen berhasil diupdate!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'delete':
                $result = $adminController->deleteDosen($_POST['dosen_id']);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Dosen berhasil dihapus!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
        }
    }
}

// ============================================
// AMBIL DATA
// ============================================
$dosen = $adminController->getAllDosen();
$fakultas_list = $adminController->getFakultasList();

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-chalkboard-teacher"></i> Data Dosen</h3>
        <div>
            <input type="text" id="tableSearch" placeholder="Cari dosen..." style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
            <button class="btn btn-primary" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Dosen
            </button>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>NID</th>
                    <th>Nama</th>
                    <th>Fakultas</th>
                    <th>Jurusan</th>
                    <th>Jenjang</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dosen as $d): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['nid']) ?></strong></td>
                        <td><?= htmlspecialchars($d['nama']) ?></td>
                        <td>
                            <small style="color: #7f8c8d;"><?= htmlspecialchars($d['fakultas_kode'] ?? '-') ?></small><br>
                            <?= htmlspecialchars($d['fakultas_nama'] ?? '-') ?>
                        </td>
                        <td>
                            <small style="color: #7f8c8d;"><?= htmlspecialchars($d['jurusan_kode'] ?? '-') ?></small><br>
                            <?= htmlspecialchars($d['jurusan_nama'] ?? '-') ?>
                        </td>
                        <td>
                            <span class="badge" style="background: #3498db; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                                <?= htmlspecialchars($d['jenjang'] ?? '-') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($d['email']) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editDosen(<?= $d['dosen_id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteDosen(<?= $d['dosen_id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($dosen)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 30px;">
                            <i class="fas fa-inbox"></i> Belum ada data dosen
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL TAMBAH DOSEN -->
<!-- ============================================ -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 550px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-plus"></i> Tambah Dosen</h3>
            <button type="button" onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>NID <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nid" required placeholder="Contoh: 1234567890" 
                       pattern="[0-9]+" title="Hanya angka"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <small style="color: #7f8c8d;">Hanya boleh angka</small>
            </div>
            
            <div class="form-group">
                <label>Username <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="username" required placeholder="Username untuk login">
            </div>
            
            <div class="form-group">
                <label>Password <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="password" value="password123" required>
                <small style="color: #7f8c8d;">Default: password123</small>
            </div>
            
            <div class="form-group">
                <label>Nama Lengkap <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: Dr. Ahmad Santoso, M.Kom">
            </div>
            
            <div class="form-group">
                <label>Email <span style="color: #e74c3c;">*</span></label>
                <input type="email" name="email" required placeholder="email@domain.com">
            </div>
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" id="tambah_fakultas" required onchange="loadJurusan('tambah', this.value)">
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['kode']) ?> - <?= htmlspecialchars($f['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Jurusan <span style="color: #e74c3c;">*</span></label>
                <select name="jurusan_id" id="tambah_jurusan" required>
                    <option value="">-- Pilih Fakultas Dulu --</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDIT DOSEN -->
<!-- ============================================ -->
<div id="modalEdit" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalEdit')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 550px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-edit"></i> Edit Dosen</h3>
            <button type="button" onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="formEdit">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="dosen_id" id="edit_dosen_id">
            
            <div class="form-group">
                <label>NID <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nid" id="edit_nid" required 
                       pattern="[0-9]+" title="Hanya angka"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <div class="form-group">
                <label>Nama Lengkap <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" id="edit_nama" required>
            </div>
            
            <div class="form-group">
                <label>Email <span style="color: #e74c3c;">*</span></label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label>Password (kosongkan jika tidak diubah)</label>
                <input type="text" name="password" placeholder="Kosongkan jika tidak diubah">
                <small style="color: #7f8c8d;">Isi hanya jika ingin mengganti password</small>
            </div>
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" id="edit_fakultas" required onchange="loadJurusan('edit', this.value)">
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['kode']) ?> - <?= htmlspecialchars($f['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Jurusan <span style="color: #e74c3c;">*</span></label>
                <select name="jurusan_id" id="edit_jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ============================================
// MODAL FUNCTIONS
// ============================================

function openModal(id) {
    document.getElementById(id).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

// ============================================
// LOAD JURUSAN BY FAKULTAS (AJAX)
// ============================================

function loadJurusan(type, fakultas_id) {
    const jurusanSelect = document.getElementById(type + '_jurusan');
    
    if (!fakultas_id) {
        jurusanSelect.innerHTML = '<option value="">-- Pilih Fakultas Dulu --</option>';
        return;
    }
    
    jurusanSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch('/simak_app/public/admin/get_jurusan.php?fakultas_id=' + fakultas_id)
        .then(response => response.json())
        .then(data => {
            let options = '<option value="">-- Pilih Jurusan --</option>';
            data.forEach(j => {
                options += `<option value="${j.id}">${j.kode} - ${j.nama} (${j.jenjang})</option>`;
            });
            jurusanSelect.innerHTML = options;
        })
        .catch(error => {
            jurusanSelect.innerHTML = '<option value="">Gagal memuat</option>';
            console.error('Error:', error);
        });
}

// ============================================
// EDIT DOSEN - LOAD DATA VIA AJAX
// ============================================

function editDosen(dosen_id) {
    showToast('Memuat data...', 'info');
    
    fetch('/simak_app/public/admin/dosen_get.php?id=' + dosen_id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Isi form
                document.getElementById('edit_dosen_id').value = data.dosen_id;
                document.getElementById('edit_nid').value = data.nid;
                document.getElementById('edit_nama').value = data.nama;
                document.getElementById('edit_email').value = data.email;
                
                // Set fakultas dulu
                document.getElementById('edit_fakultas').value = data.fakultas_id;
                
                // Load jurusan, lalu set nilai
                fetch('/simak_app/public/admin/get_jurusan.php?fakultas_id=' + data.fakultas_id)
                    .then(response => response.json())
                    .then(jurusanData => {
                        let options = '<option value="">-- Pilih Jurusan --</option>';
                        jurusanData.forEach(j => {
                            const selected = (j.id == data.jurusan_id) ? 'selected' : '';
                            options += `<option value="${j.id}" ${selected}>${j.kode} - ${j.nama} (${j.jenjang})</option>`;
                        });
                        document.getElementById('edit_jurusan').innerHTML = options;
                    });
                
                // Buka modal
                openModal('modalEdit');
            } else {
                showToast(data.message || 'Gagal memuat data', 'error');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan: ' + error, 'error');
        });
}

// ============================================
// DELETE DOSEN
// ============================================

function deleteDosen(dosen_id) {
    if (confirm('Yakin hapus dosen ini?\n\nData user, akun login, dan semua data terkait akan terhapus.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="dosen_id" value="${dosen_id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============================================
// CLOSE MODAL WITH ESC
// ============================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('modalTambah');
        closeModal('modalEdit');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>