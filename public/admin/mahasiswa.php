<?php
// ============================================
// public/admin/mahasiswa.php
// CRUD Mahasiswa V2 - Fakultas/Jurusan
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../controllers/MasterDataController.php';

checkAccess(['admin']);

$page_title = 'Data Mahasiswa';
$adminController = new AdminController($pdo);
$masterController = new MasterDataController($pdo);

// Proses CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $result = $adminController->createMahasiswa($_POST);
            redirectWithMessage(
                '/simak_app/public/admin/mahasiswa.php',
                $result['success'] ? 'Mahasiswa berhasil ditambahkan!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'update':
            $result = $adminController->updateMahasiswa($_POST['mahasiswa_id'], $_POST);
            redirectWithMessage(
                '/simak_app/public/admin/mahasiswa.php',
                $result['success'] ? 'Mahasiswa berhasil diupdate!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
            
        case 'delete':
            $result = $adminController->deleteMahasiswa($_POST['mahasiswa_id']);
            redirectWithMessage(
                '/simak_app/public/admin/mahasiswa.php',
                $result['success'] ? 'Mahasiswa berhasil dihapus!' : 'Gagal: ' . $result['message'],
                $result['success'] ? 'success' : 'error'
            );
            break;
    }
}

$mahasiswa_list = $adminController->getAllMahasiswa();
$fakultas_list = $masterController->getFakultasList();
$jurusan_list = $masterController->getJurusanList();
$tahun_ajaran_otomatis = MasterDataController::getTahunAjaranOtomatis();
$semester_label = MasterDataController::getSemesterLabel();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Info Tahun Ajaran Aktif -->
<div class="table-container" style="margin-bottom: 15px; background: linear-gradient(135deg, #3498db, #2980b9); color: #fff;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong><i class="fas fa-calendar-alt"></i> Periode Aktif:</strong>
            <span style="font-size: 16px; margin-left: 10px;"><?= $tahun_ajaran_otomatis ?></span>
            <span style="background: rgba(255,255,255,0.2); padding: 3px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">
                Semester <?= $semester_label ?>
            </span>
        </div>
        <div style="font-size: 13px;">
            <i class="fas fa-info-circle"></i> Semester otomatis dihitung dari tingkat
        </div>
    </div>
</div>

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
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Fakultas</th>
                    <th>Jurusan</th>
                    <th>Tahun Ajaran</th>
                    <th>Tingkat</th>
                    <th>Semester</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mahasiswa_list as $m): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nim']) ?></strong></td>
                        <td><?= htmlspecialchars($m['nama']) ?></td>
                        <td>
                            <?php if ($m['fakultas_nama']): ?>
                                <span style="background: #e8f4fd; color: #2980b9; padding: 3px 10px; border-radius: 12px; font-size: 11px;">
                                    <?= htmlspecialchars($m['fakultas_kode']) ?>
                                </span>
                                <small style="display: block; color: #7f8c8d; margin-top: 2px;">
                                    <?= htmlspecialchars($m['fakultas_nama']) ?>
                                </small>
                            <?php else: ?>
                                <span style="color: #bdc3c7;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['jurusan_nama']): ?>
                                <strong style="color: #2c3e50;"><?= htmlspecialchars($m['jurusan_nama']) ?></strong>
                                <small style="display: block; color: #7f8c8d;">
                                    (<?= $m['jenjang'] ?>)
                                </small>
                            <?php else: ?>
                                <span style="color: #bdc3c7;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['tahun_ajaran'] ?? '-') ?></td>
                        <td>
                            <span style="background: #f39c12; color: #fff; padding: 3px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                Tingkat <?= $m['tingkat'] ?? 1 ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $sem = $m['semester'] ?? 1;
                            $is_ganjil = $sem % 2 == 1;
                            $warna = $is_ganjil ? '#3498db' : '#9b59b6';
                            ?>
                            <span style="background: <?= $warna ?>; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 11px;">
                                Smt <?= $sem ?> (<?= $is_ganjil ? 'Ganjil' : 'Genap' ?>)
                            </span>
                        </td>
                        <td><small><?= htmlspecialchars($m['email']) ?></small></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editMahasiswa(<?= $m['mahasiswa_id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteMahasiswa(<?= $m['mahasiswa_id'] ?>, '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($mahasiswa_list)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #7f8c8d; padding: 30px;">
                            <i class="fas fa-inbox"></i> Belum ada data mahasiswa
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 999;">
    <div class="modal-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);" onclick="closeModal('modalTambah')"></div>
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-plus"></i> Tambah Mahasiswa</h3>
            <button onclick="closeModal('modalTambah')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <h4 style="color: #3498db; font-size: 13px; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-user"></i> Data Pribadi
            </h4>
            
            <div class="form-group">
                <label>NIM <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nim" required placeholder="Contoh: 202401001">
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="nama" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@domain.com">
            </div>
            
            <h4 style="color: #3498db; font-size: 13px; margin: 20px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-university"></i> Data Akademik
            </h4>
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" id="tambah_fakultas" required onchange="filterJurusan('tambah')">
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jurusan <span style="color: #e74c3c;">*</span></label>
                <select name="jurusan_id" id="tambah_jurusan" required>
                    <option value="">-- Pilih Fakultas Dulu --</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Tahun Ajaran <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="tahun_ajaran" value="<?= $tahun_ajaran_otomatis ?>" required placeholder="2024/2025">
                    <small style="color: #7f8c8d;">Otomatis, bisa diubah</small>
                </div>
                <div class="form-group">
                    <label>Tingkat <span style="color: #e74c3c;">*</span></label>
                    <select name="tingkat" id="tambah_tingkat" required onchange="hitungSemester('tambah')">
                        <option value="1">Tingkat 1</option>
                        <option value="2">Tingkat 2</option>
                        <option value="3">Tingkat 3</option>
                        <option value="4">Tingkat 4</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Semester (Otomatis)</label>
                <input type="text" id="tambah_semester_info" readonly style="background: #f8f9fa; font-weight: bold; color: #3498db;">
                <small style="color: #7f8c8d;">
                    <i class="fas fa-info-circle"></i> 
                    Semester <?= $semester_label ?> (<?= date('F') ?>)
                </small>
            </div>
            
            <h4 style="color: #3498db; font-size: 13px; margin: 20px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-key"></i> Akun Login
            </h4>
            
            <div class="form-group">
                <label>Username <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password <span style="color: #e74c3c;">*</span></label>
                <input type="text" name="password" value="password123" required>
                <small style="color: #7f8c8d;">Default: password123</small>
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
    <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><i class="fas fa-user-edit"></i> Edit Mahasiswa</h3>
            <button onclick="closeModal('modalEdit')" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST" id="formEdit">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="mahasiswa_id" id="edit_mahasiswa_id">
            
            <h4 style="color: #3498db; font-size: 13px; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-user"></i> Data Pribadi
            </h4>
            
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
            
            <h4 style="color: #3498db; font-size: 13px; margin: 20px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-university"></i> Data Akademik
            </h4>
            
            <div class="form-group">
                <label>Fakultas <span style="color: #e74c3c;">*</span></label>
                <select name="fakultas_id" id="edit_fakultas" required onchange="filterJurusan('edit')">
                    <option value="">-- Pilih Fakultas --</option>
                    <?php foreach ($fakultas_list as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jurusan <span style="color: #e74c3c;">*</span></label>
                <select name="jurusan_id" id="edit_jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Tahun Ajaran <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="tahun_ajaran" id="edit_tahun_ajaran" required>
                </div>
                <div class="form-group">
                    <label>Tingkat <span style="color: #e74c3c;">*</span></label>
                    <select name="tingkat" id="edit_tingkat" required onchange="hitungSemester('edit')">
                        <option value="1">Tingkat 1</option>
                        <option value="2">Tingkat 2</option>
                        <option value="3">Tingkat 3</option>
                        <option value="4">Tingkat 4</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Semester (Otomatis)</label>
                <input type="text" id="edit_semester_info" readonly style="background: #f8f9fa; font-weight: bold; color: #3498db;">
            </div>
            
            <h4 style="color: #3498db; font-size: 13px; margin: 20px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e9ecef;">
                <i class="fas fa-key"></i> Akun Login
            </h4>
            
            <div class="form-group">
                <label>Password (Kosongkan jika tidak diubah)</label>
                <input type="text" name="password" placeholder="Kosongkan jika tidak diubah">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// Data jurusan (untuk filter)
const jurusanData = <?= json_encode($jurusan_list) ?>;
const fakultasData = <?= json_encode($fakultas_list) ?>;
const semesterLabel = '<?= $semester_label ?>';

function openModal(id) {
    document.getElementById(id).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

// Filter jurusan berdasarkan fakultas
function filterJurusan(mode) {
    const fakultasSelect = document.getElementById(mode + '_fakultas');
    const jurusanSelect = document.getElementById(mode + '_jurusan');
    const fakultasId = fakultasSelect.value;
    
    // Reset
    jurusanSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
    
    if (!fakultasId) return;
    
    // Filter
    jurusanData.forEach(j => {
        if (j.fakultas_id == fakultasId) {
            const opt = document.createElement('option');
            opt.value = j.id;
            opt.textContent = j.nama + ' (' + j.jenjang + ')';
            jurusanSelect.appendChild(opt);
        }
    });
}

// Hitung semester otomatis
function hitungSemester(mode) {
    const tingkat = parseInt(document.getElementById(mode + '_tingkat').value);
    const isGanjil = semesterLabel === 'Ganjil';
    
    const semester = isGanjil ? (tingkat * 2) - 1 : tingkat * 2;
    const label = semester % 2 === 1 ? 'Ganjil' : 'Genap';
    
    document.getElementById(mode + '_semester_info').value = 
        'Semester ' + semester + ' (' + label + ')';
}

// Edit mahasiswa via AJAX
function editMahasiswa(id) {
    fetch('/simak_app/public/admin/mahasiswa_get.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Gagal memuat data');
                return;
            }
            
            document.getElementById('edit_mahasiswa_id').value = data.mahasiswa_id;
            document.getElementById('edit_nim').value = data.nim;
            document.getElementById('edit_nama').value = data.nama;
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_tahun_ajaran').value = data.tahun_ajaran || '';
            document.getElementById('edit_tingkat').value = data.tingkat || 1;
            
            // Set fakultas & jurusan
            document.getElementById('edit_fakultas').value = data.fakultas_id || '';
            filterJurusan('edit');
            
            // Setelah filter jurusan selesai, set jurusan
            setTimeout(() => {
                document.getElementById('edit_jurusan').value = data.jurusan_id || '';
                hitungSemester('edit');
            }, 100);
            
            openModal('modalEdit');
        })
        .catch(err => {
            alert('Error: ' + err);
        });
}

function deleteMahasiswa(id, nama) {
    if (confirm('Yakin hapus mahasiswa "' + nama + '"?')) {
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

// Init semester saat halaman load
document.addEventListener('DOMContentLoaded', function() {
    hitungSemester('tambah');
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>