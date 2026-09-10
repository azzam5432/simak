<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Data Mahasiswa';
$adminController = new AdminController($pdo);

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $adminController->createMahasiswa($_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Mahasiswa berhasil ditambahkan!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'update':
                $result = $adminController->updateMahasiswa($_POST['mahasiswa_id'], $_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Mahasiswa berhasil diupdate!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'delete':
                $result = $adminController->deleteMahasiswa($_POST['mahasiswa_id']);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Mahasiswa berhasil dihapus!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/mahasiswa.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
        }
    }
}

$mahasiswa = $adminController->getAllMahasiswa();

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-user-graduate"></i> Data Mahasiswa</h3>
        <div>
            <input type="text" id="tableSearch" placeholder="Cari mahasiswa..." style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
            <button class="btn btn-primary" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Mahasiswa
            </button>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Program Studi</th>
                <th>Angkatan</th>
                <th>Semester</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mahasiswa as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['nim']) ?></strong></td>
                    <td><?= htmlspecialchars($m['nama']) ?></td>
                    <td><?= htmlspecialchars($m['program_studi']) ?></td>
                    <td><?= $m['angkatan'] ?></td>
                    <td>
                        <span class="status-badge">Semester <?= $m['semester'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="editMahasiswa(<?= $m['mahasiswa_id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" onclick="deleteMahasiswa(<?= $m['mahasiswa_id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($mahasiswa)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox"></i> Belum ada data mahasiswa
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ============================================ -->
<!-- MODAL TAMBAH MAHASISWA -->
<!-- ============================================ -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-plus"></i> Tambah Mahasiswa</h3>
            <button type="button" onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>NIM <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nim" required placeholder="Contoh: 20261021">
                <small style="color: #7f8c8d;">Bisa diisi angka berapapun</small>
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
                <label>Angkatan <span style="color: #e74c3c;">*</span></label>
                <input type="number" name="angkatan" required>
            </div>
            <div class="form-group">
                <label>Semester <span style="color: #e74c3c;">*</span></label>
                <input type="number" name="semester" value="1" min="1" max="14" required>
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
<!-- MODAL EDIT MAHASISWA -->
<!-- ============================================ -->
<div id="modalEdit" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalEdit')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-edit"></i> Edit Mahasiswa</h3>
            <button type="button" onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Form Edit akan diisi oleh JavaScript -->
        <form id="formEdit" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="mahasiswa_id" id="edit_mahasiswa_id">
            
            <div class="form-group">
                <label>NIM <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nim" id="edit_nim" required>
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
                <label>Angkatan <span style="color: #e74c3c;">*</span></label>
                <input type="number" name="angkatan" id="edit_angkatan" required>
            </div>
            <div class="form-group">
                <label>Semester <span style="color: #e74c3c;">*</span></label>
                <input type="number" name="semester" id="edit_semester" min="1" max="14" required>
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
    document.body.style.overflow = 'hidden'; // Prevent scroll
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = ''; // Restore scroll
}

// ============================================
// FUNGSI EDIT MAHASISWA
// ============================================

function editMahasiswa(id) {
    // Tampilkan loading
    showToast('Memuat data...', 'info');
    
    // Ambil data mahasiswa via AJAX
    fetch('/simak_app/public/admin/mahasiswa_get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Isi form dengan data
                document.getElementById('edit_mahasiswa_id').value = data.mahasiswa_id;
                document.getElementById('edit_nim').value = data.nim;
                document.getElementById('edit_nama').value = data.nama;
                document.getElementById('edit_email').value = data.email || '';
                document.getElementById('edit_program_studi').value = data.program_studi;
                document.getElementById('edit_angkatan').value = data.angkatan;
                document.getElementById('edit_semester').value = data.semester;
                
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
// FUNGSI HAPUS MAHASISWA
// ============================================

function deleteMahasiswa(id) {
    if (confirm('Yakin hapus mahasiswa ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="mahasiswa_id" value="${id}">
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