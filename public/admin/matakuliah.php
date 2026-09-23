<?php
// ============================================
// public/admin/matakuliah.php
// Master Data Mata Kuliah, SKS & alokasi Ruangan
// ============================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../controllers/MasterDataController.php';

checkAccess(['admin']);

$page_title = 'Data Mata Kuliah';
$adminController = new AdminController($pdo);
$masterController = new MasterDataController($pdo);

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $adminController->createMatakuliah($_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Mata kuliah berhasil ditambahkan!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'update':
                $result = $adminController->updateMatakuliah($_POST['id'], $_POST);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Mata kuliah berhasil diupdate!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
                
            case 'delete':
                $result = $adminController->deleteMatakuliah($_POST['id']);
                if ($result['success']) {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Mata kuliah berhasil dihapus!', 'success');
                } else {
                    redirectWithMessage('/simak_app/public/admin/matakuliah.php', 'Gagal: ' . $result['message'], 'error');
                }
                break;
        }
    }
}

// Ambil data
$matakuliah = $adminController->getAllMatakuliah();
$fakultas_list = $masterController->getFakultasList();

// Ambil daftar dosen untuk dropdown
$dosenList = [];
try {
    $stmt = $pdo->prepare("
        SELECT d.id, u.nama, d.nid
        FROM dosen d
        JOIN users u ON d.user_id = u.id
        ORDER BY u.nama ASC
    ");
    $stmt->execute();
    $dosenList = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika error, biarkan array kosong
    error_log("Error getting dosen list: " . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-book-open"></i> Data Mata Kuliah</h3>
        <div>
            <input type="text" id="tableSearch" placeholder="Cari matakuliah..." style="padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px;">
            <button class="btn btn-primary" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Mata Kuliah
            </button>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Semester</th>
                <th>Jurusan</th>
                <th>Dosen</th>
                <th>Ruang</th>
                <th>Jadwal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($matakuliah)): ?>
                <?php foreach ($matakuliah as $mk): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mk['kode_mk']) ?></strong></td>
                        <td><?= htmlspecialchars($mk['nama_mk']) ?></td>
                        <td><?= $mk['sks'] ?></td>
                        <td><?= $mk['semester'] ?></td>
                        <td>
                            <?php if (!empty($mk['jurusan_nama'])): ?>
                                <strong style="color: #2c3e50;"><?= htmlspecialchars($mk['jurusan_kode'] ?? '-') ?></strong><br>
                                <small><?= htmlspecialchars($mk['jurusan_nama']) ?></small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($mk['dosen_nama'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($mk['ruang'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($mk['hari'])): ?>
                                <small>
                                    <?= htmlspecialchars($mk['hari']) ?> 
                                    <?= date('H:i', strtotime($mk['jam_mulai'])) ?> - 
                                    <?= date('H:i', strtotime($mk['jam_selesai'])) ?>
                                </small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editMatakuliah(<?= $mk['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm btn-delete" onclick="deleteMatakuliah(<?= $mk['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; color: #7f8c8d; padding: 30px;">
                        <i class="fas fa-inbox"></i> Belum ada data mata kuliah
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ============================================ -->
<!-- MODAL TAMBAH MATA KULIAH -->
<!-- ============================================ -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 550px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-book"></i> Tambah Mata Kuliah</h3>
            <button type="button" onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Kode Mata Kuliah <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode_mk" placeholder="Contoh: TI101" required>
            </div>
            <div class="form-group">
                <label>Nama Mata Kuliah <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama_mk" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>SKS <span style="color: #e74c3c;">*</span></label>
                    <input type="number" name="sks" min="1" max="6" value="3" required>
                </div>
                <div class="form-group">
                    <label>Semester <span style="color: #e74c3c;">*</span></label>
                    <input type="number" name="semester" min="1" max="8" value="1" required>
                </div>
            </div>
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select id="tambah_fakultas" required onchange="loadJurusan('tambah', this.value)">
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
            <div class="form-group">
                <label>Dosen Pengampu</label>
                <select name="dosen_id">
                    <option value="">-- Pilih Dosen --</option>
                    <?php foreach ($dosenList as $d): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= htmlspecialchars($d['nama']) ?> (<?= htmlspecialchars($d['nid']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ruang</label>
                <input type="text" name="ruang" placeholder="Contoh: Lab 1, Ruang 201">
            </div>
            <div class="form-group">
                <label>Hari</label>
                <select name="hari">
                    <option value="">-- Pilih Hari --</option>
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Jam Mulai</label>
                    <input type="time" name="jam_mulai">
                </div>
                <div class="form-group">
                    <label>Jam Selesai</label>
                    <input type="time" name="jam_selesai">
                </div>
            </div>
            <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" min="1" value="30">
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
<!-- MODAL EDIT MATA KULIAH -->
<!-- ============================================ -->
<div id="modalEdit" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalEdit')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 550px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-book"></i> Edit Mata Kuliah</h3>
            <button type="button" onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formEditMatakuliah" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Kode Mata Kuliah <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="kode_mk" id="edit_kode_mk" required>
            </div>
            <div class="form-group">
                <label>Nama Mata Kuliah <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama_mk" id="edit_nama_mk" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>SKS <span style="color: #e74c3c;">*</span></label>
                    <input type="number" name="sks" id="edit_sks" min="1" max="6" required>
                </div>
                <div class="form-group">
                    <label>Semester <span style="color: #e74c3c;">*</span></label>
                    <input type="number" name="semester" id="edit_semester" min="1" max="8" required>
                </div>
            </div>
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select id="edit_fakultas" required onchange="loadJurusan('edit', this.value)">
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
                    <option value="">-- Pilih Fakultas Dulu --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Dosen Pengampu</label>
                <select name="dosen_id" id="edit_dosen_id">
                    <option value="">-- Pilih Dosen --</option>
                    <?php foreach ($dosenList as $d): ?>
                        <option value="<?= $d['id'] ?>">
                            <?= htmlspecialchars($d['nama']) ?> (<?= htmlspecialchars($d['nid']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ruang</label>
                <input type="text" name="ruang" id="edit_ruang" placeholder="Contoh: Lab 1, Ruang 201">
            </div>
            <div class="form-group">
                <label>Hari</label>
                <select name="hari" id="edit_hari">
                    <option value="">-- Pilih Hari --</option>
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Jam Mulai</label>
                    <input type="time" name="jam_mulai" id="edit_jam_mulai">
                </div>
                <div class="form-group">
                    <label>Jam Selesai</label>
                    <input type="time" name="jam_selesai" id="edit_jam_selesai">
                </div>
            </div>
            <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" id="edit_kapasitas" min="1" value="30">
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
// FUNGSI EDIT MATA KULIAH
// ============================================

function editMatakuliah(id) {
    showToast('Memuat data...', 'info');
    
    fetch('/simak_app/public/admin/matakuliah_get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_kode_mk').value = data.kode_mk;
                document.getElementById('edit_nama_mk').value = data.nama_mk;
                document.getElementById('edit_sks').value = data.sks;
                document.getElementById('edit_semester').value = data.semester;
                
                // Set fakultas, lalu load jurusan dan pilih jurusan MK
                document.getElementById('edit_fakultas').value = data.fakultas_id || '';
                
                const jurusanSelect = document.getElementById('edit_jurusan');
                if (data.fakultas_id) {
                    jurusanSelect.innerHTML = '<option value="">Loading...</option>';
                    fetch('/simak_app/public/admin/get_jurusan.php?fakultas_id=' + data.fakultas_id)
                        .then(response => response.json())
                        .then(jurusanData => {
                            let options = '<option value="">-- Pilih Jurusan --</option>';
                            jurusanData.forEach(j => {
                                const selected = (j.id == data.jurusan_id) ? 'selected' : '';
                                options += `<option value="${j.id}" ${selected}>${j.kode} - ${j.nama} (${j.jenjang})</option>`;
                            });
                            jurusanSelect.innerHTML = options;
                        })
                        .catch(error => {
                            jurusanSelect.innerHTML = '<option value="">Gagal memuat</option>';
                            console.error('Error:', error);
                        });
                } else {
                    jurusanSelect.innerHTML = '<option value="">-- Pilih Fakultas Dulu --</option>';
                }
                
                document.getElementById('edit_dosen_id').value = data.dosen_id || '';
                document.getElementById('edit_ruang').value = data.ruang || '';
                document.getElementById('edit_hari').value = data.hari || '';
                document.getElementById('edit_jam_mulai').value = data.jam_mulai || '';
                document.getElementById('edit_jam_selesai').value = data.jam_selesai || '';
                document.getElementById('edit_kapasitas').value = data.kapasitas || 30;
                
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
// FUNGSI HAPUS MATA KULIAH
// ============================================

function deleteMatakuliah(id) {
    if (confirm('Yakin hapus mata kuliah ini?')) {
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