<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Data Dosen';
$adminController = new AdminController($pdo);

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
                // PERBAIKAN: Gunakan dosen_id
                $result = $adminController->updateDosen($_POST['dosen_id'], $_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Dosen berhasil diupdate!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/dosen.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'delete':
                // PERBAIKAN: Gunakan dosen_id
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

$dosen = $adminController->getAllDosen();

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
    
    <table>
        <thead>
            <tr>
                <th>NIDN</th>
                <th>Nama</th>
                <th>Program Studi</th>
                <th>Jabatan</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dosen as $d): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($d['nidn']) ?></strong></td>
                    <td><?= htmlspecialchars($d['nama']) ?></td>
                    <td><?= htmlspecialchars($d['program_studi']) ?></td>
                    <td><?= htmlspecialchars($d['jabatan']) ?></td>
                    <td><?= htmlspecialchars($d['email']) ?></td>
                    <td>
                        <!-- PERBAIKAN: Gunakan dosen_id -->
                        <button class="btn btn-primary btn-sm" onclick="editDosen(<?= $d['dosen_id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" onclick="deleteDosen(<?= $d['dosen_id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($dosen)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox"></i> Belum ada data dosen
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ============================================ -->
<!-- MODAL TAMBAH DOSEN -->
<!-- ============================================ -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-plus"></i> Tambah Dosen</h3>
            <button type="button" onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>NIDN <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nidn" required>
            </div>
            <div class="form-group">
                <label>Username <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="password" value="password123" required>
                <small style="color: #7f8c8d;">Default: password123</small>
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@domain.com">
            </div>
            <div class="form-group">
                <label>Program Studi <span style="color: #e74c3c;">*</span></label>
                <select name="program_studi" required>
                    <option value="Teknik Informatika">Teknik Informatika</option>
                    <option value="Sistem Informasi">Sistem Informasi</option>
                    <option value="Manajemen Informatika">Manajemen Informatika</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jabatan</label>
                <select name="jabatan">
                    <option value="Asisten Ahli">Asisten Ahli</option>
                    <option value="Lektor">Lektor</option>
                    <option value="Lektor Kepala">Lektor Kepala</option>
                    <option value="Profesor">Profesor</option>
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
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-edit"></i> Edit Dosen</h3>
            <button type="button" onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formEditDosen" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="dosen_id" id="edit_dosen_id">
            
            <div class="form-group">
                <label>NIDN <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nidn" id="edit_nidn" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" id="edit_nama" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email">
            </div>
            <div class="form-group">
                <label>Password (kosongkan jika tidak diubah)</label>
                <input type="text" name="password" placeholder="Kosongkan jika tidak diubah">
                <small style="color: #7f8c8d;">Isi hanya jika ingin mengganti password</small>
            </div>
            <div class="form-group">
                <label>Program Studi <span style="color: #e74c3c;">*</span></label>
                <select name="program_studi" id="edit_program_studi" required>
                    <option value="Teknik Informatika">Teknik Informatika</option>
                    <option value="Sistem Informasi">Sistem Informasi</option>
                    <option value="Manajemen Informatika">Manajemen Informatika</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jabatan</label>
                <select name="jabatan" id="edit_jabatan">
                    <option value="Asisten Ahli">Asisten Ahli</option>
                    <option value="Lektor">Lektor</option>
                    <option value="Lektor Kepala">Lektor Kepala</option>
                    <option value="Profesor">Profesor</option>
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
// FUNGSI MODAL
// ============================================

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

// ============================================
// FUNGSI EDIT DOSEN
// ============================================

function editDosen(id) {
    showToast('Memuat data...', 'info');
    
    fetch('/simak_app/public/admin/dosen_get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_dosen_id').value = data.dosen_id;
                document.getElementById('edit_nidn').value = data.nidn;
                document.getElementById('edit_nama').value = data.nama;
                document.getElementById('edit_email').value = data.email || '';
                document.getElementById('edit_program_studi').value = data.program_studi;
                document.getElementById('edit_jabatan').value = data.jabatan;
                
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
// FUNGSI HAPUS DOSEN
// ============================================

function deleteDosen(id) {
    if (confirm('Yakin hapus dosen ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="dosen_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============================================
// CLOSE MODAL WITH ESC KEY
// ============================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('modalTambah');
        closeModal('modalEdit');
    }
});

// ============================================
// CLICK OUTSIDE MODAL CONTENT
// ============================================

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.closest('.modal').style.display = 'none';
        document.body.style.overflow = '';
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>