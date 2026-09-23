<?php
// ============================================
// public/admin/laporan.php
// Laporan Akademik - Versi 1 File dengan Tab
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/LaporanController.php';
require_once __DIR__ . '/../../includes/ExcelExport.php';

checkAccess(['admin']);

$page_title = 'Laporan Akademik';
$laporanController = new LaporanController($pdo);

$matakuliah = $laporanController->getMatakuliahList();

// ============================================
// EXPORT EXCEL HANDLER
// ============================================
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $filename = '';
    $headers = [];
    $data = [];
    $sheetName = 'Laporan';
    
    switch ($type) {
        case 'nilai':
            $course_id = $_GET['course_id'] ?? null;
            $semester = $_GET['semester'] ?? null;
            $laporan = $laporanController->getLaporanNilai($course_id, $semester);
            $filename = 'laporan_nilai_' . date('Ymd_His') . '.xlsx';
            $sheetName = 'Laporan Nilai';
            $headers = ['NIM', 'Mahasiswa', 'Prodi', 'Kode MK', 'Mata Kuliah', 'SKS', 'Semester', 'Tugas', 'UTS', 'UAS', 'Nilai Akhir', 'Status'];
            foreach ($laporan as $row) {
                $data[] = [$row['nim'], $row['mahasiswa_nama'], $row['program_studi'], $row['kode_mk'], $row['nama_mk'], (int)$row['sks'], $row['semester'], $row['nilai_tugas'] !== null ? (float)$row['nilai_tugas'] : null, $row['nilai_uts'] !== null ? (float)$row['nilai_uts'] : null, $row['nilai_uas'] !== null ? (float)$row['nilai_uas'] : null, $row['nilai_akhir'] !== null ? (float)$row['nilai_akhir'] : null, $row['status_verifikasi']];
            }
            break;
            
        case 'irs':
            $semester = $_GET['semester'] ?? null;
            $status = $_GET['status'] ?? null;
            $laporan = $laporanController->getLaporanIRS($semester, $status);
            $filename = 'laporan_irs_' . date('Ymd_His') . '.xlsx';
            $sheetName = 'Laporan IRS';
            $headers = ['NIM', 'Mahasiswa', 'Prodi', 'Kode MK', 'Mata Kuliah', 'SKS', 'Semester', 'Status', 'Tanggal'];
            foreach ($laporan as $row) {
                $data[] = [$row['nim'], $row['mahasiswa_nama'], $row['program_studi'], $row['kode_mk'], $row['nama_mk'], (int)$row['sks'], $row['semester'], $row['status'], date('d-m-Y', strtotime($row['created_at']))];
            }
            break;
            
        case 'presensi':
            $course_id = $_GET['course_id'] ?? null;
            $laporan = $laporanController->getLaporanPresensi($course_id);
            $filename = 'laporan_presensi_' . date('Ymd_His') . '.xlsx';
            $sheetName = 'Laporan Presensi';
            $headers = ['Kode MK', 'Mata Kuliah', 'NIM', 'Mahasiswa', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total', 'Persentase (%)'];
            foreach ($laporan as $row) {
                $data[] = [$row['kode_mk'], $row['nama_mk'], $row['nim'], $row['mahasiswa_nama'], (int)$row['hadir'], (int)$row['izin'], (int)$row['sakit'], (int)$row['alpa'], (int)$row['total_pertemuan'], (float)$row['persentase_hadir']];
            }
            break;
            
        case 'mahasiswa':
            $jurusan_id = $_GET['jurusan_id'] ?? null;
            $laporan = $laporanController->getLaporanMahasiswa($jurusan_id);
            $filename = 'laporan_mahasiswa_' . date('Ymd_His') . '.xlsx';
            $sheetName = 'Laporan Mahasiswa';
            $headers = ['NIM', 'Nama', 'Email', 'Prodi', 'Angkatan', 'Semester', 'Total SKS', 'IPK'];
            foreach ($laporan as $row) {
                $data[] = [$row['nim'], $row['mahasiswa_nama'], $row['email'], $row['program_studi'], (int)$row['angkatan'], (int)$row['semester'], (int)$row['total_sks'], (float)($row['ipk'] ?? 0)];
            }
            break;
    }
    
    if ($filename) {
        $excel = new ExcelExport('Laporan Akademik SIMAK');
        $excel->addSheet($sheetName, $headers, $data);
        $excel->download($filename);
    }
    exit();
}

// ============================================
// AMBIL DATA BERDASARKAN TAB AKTIF
// ============================================
$tab = $_GET['tab'] ?? 'nilai';
$stats_umum = $laporanController->getStatistikUmum();

// Data untuk dropdown
$semesters = $laporanController->getSemesterList();
$prodi_list = $laporanController->getProgramStudiList();

// Filter
$course_id = $_GET['course_id'] ?? null;
$semester = $_GET['semester'] ?? null;
$status = $_GET['status'] ?? null;
$jurusan_id = $_GET['jurusan_id'] ?? null;

// Data sesuai tab
$data = [];
$statistik = [];

switch ($tab) {
    case 'nilai':
        $data = $laporanController->getLaporanNilai($course_id, $semester);
        $statistik = $laporanController->getStatistikNilai($course_id);
        break;
    case 'irs':
        $data = $laporanController->getLaporanIRS($semester, $status);
        $rekap_irs = $laporanController->getRekapIRSPerMahasiswa($semester);
        $statistik = $laporanController->getStatistikIRS();
        break;
    case 'presensi':
        $data = $laporanController->getLaporanPresensi($course_id);
        $statistik = $laporanController->getStatistikPresensi($course_id);
        break;
    case 'mahasiswa':
        $data = $laporanController->getLaporanMahasiswa($jurusan_id);
        break;
}

// Helper: Nilai ke Huruf
function nilaiHuruf($nilai) {
    if ($nilai >= 85) return ['A', '#2ecc71'];
    elseif ($nilai >= 75) return ['B', '#3498db'];
    elseif ($nilai >= 65) return ['C', '#f39c12'];
    elseif ($nilai >= 50) return ['D', '#e67e22'];
    else return ['E', '#e74c3c'];
}

// Helper: Status Color
$status_color = [
    'pending' => '#f39c12',
    'approved' => '#2ecc71',
    'rejected' => '#e74c3c'
];

include __DIR__ . '/../../includes/header.php';
?>

<style>
.tabs-container {
    display: flex;
    gap: 5px;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
    overflow-x: auto;
}

.tab-btn {
    padding: 12px 25px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #7f8c8d;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: all 0.3s;
}

.tab-btn:hover {
    color: #3498db;
    background: #f8f9fa;
}

.tab-btn.active {
    color: #3498db;
    border-bottom-color: #3498db;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>

<!-- Statistik Umum -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-number"><?= $stats_umum['total_mahasiswa'] ?></div>
        <div class="stat-label">Total Mahasiswa</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-number"><?= $stats_umum['total_dosen'] ?></div>
        <div class="stat-label">Total Dosen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-number"><?= $stats_umum['total_matakuliah'] ?></div>
        <div class="stat-label">Mata Kuliah</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-number"><?= number_format($stats_umum['rata_nilai'], 2) ?></div>
        <div class="stat-label">Rata-rata Nilai</div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="table-container">
    <div class="tabs-container">
        <a href="?tab=nilai" class="tab-btn <?= $tab === 'nilai' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i> Laporan Nilai
        </a>
        <a href="?tab=irs" class="tab-btn <?= $tab === 'irs' ? 'active' : '' ?>">
            <i class="fas fa-file-signature"></i> Laporan IRS
        </a>
        <a href="?tab=presensi" class="tab-btn <?= $tab === 'presensi' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check"></i> Laporan Presensi
        </a>
        <a href="?tab=mahasiswa" class="tab-btn <?= $tab === 'mahasiswa' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Laporan Mahasiswa
        </a>
    </div>
    
    <!-- ============================================ -->
    <!-- TAB 1: LAPORAN NILAI -->
    <!-- ============================================ -->
    <?php if ($tab === 'nilai'): ?>
        
        <!-- Filter -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <input type="hidden" name="tab" value="nilai">
            
            <div class="form-group" style="margin: 0; min-width: 220px;">
                <label style="font-size: 13px;">Mata Kuliah</label>
                <select name="course_id" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua Mata Kuliah --</option>
                    <?php foreach ($matakuliah as $mk): ?>
                        <option value="<?= $mk['id'] ?>" <?= $course_id == $mk['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mk['kode_mk']) ?> - <?= htmlspecialchars($mk['nama_mk']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0; min-width: 130px;">
                <label style="font-size: 13px;">Semester</label>
                <select name="semester" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua --</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?= $s['semester'] ?>" <?= $semester == $s['semester'] ? 'selected' : '' ?>>
                            Smt <?= $s['semester'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?tab=nilai&export=nilai&course_id=<?= $course_id ?>&semester=<?= $semester ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="/simak_app/public/admin/cetak_pdf.php?type=nilai&course_id=<?= $course_id ?>&semester=<?= $semester ?>" target="_blank"
                    class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
        
        <!-- Statistik Nilai -->
        <?php if (!empty($statistik)): ?>
            <h4 style="margin: 15px 0 10px;"><i class="fas fa-chart-bar"></i> Statistik per Mata Kuliah</h4>
            <div style="overflow-x: auto; margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>Jumlah</th>
                            <th>Rata-rata</th>
                            <th>Max</th>
                            <th>Min</th>
                            <th>A</th>
                            <th>B</th>
                            <th>C</th>
                            <th>D</th>
                            <th>E</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistik as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['kode_mk']) ?></strong></td>
                                <td><?= htmlspecialchars($s['nama_mk']) ?></td>
                                <td><?= $s['total_mahasiswa'] ?></td>
                                <td><strong><?= number_format($s['rata_rata'], 2) ?></strong></td>
                                <td style="color: #2ecc71;"><?= number_format($s['nilai_max'], 1) ?></td>
                                <td style="color: #e74c3c;"><?= number_format($s['nilai_min'], 1) ?></td>
                                <td><?= $s['jumlah_a'] ?></td>
                                <td><?= $s['jumlah_b'] ?></td>
                                <td><?= $s['jumlah_c'] ?></td>
                                <td><?= $s['jumlah_d'] ?></td>
                                <td><?= $s['jumlah_e'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Detail Nilai -->
        <h4 style="margin: 15px 0 10px;"><i class="fas fa-list"></i> Detail Nilai (<?= count($data) ?> data)</h4>
        <?php if (empty($data)): ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">Tidak ada data nilai</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Prodi</th>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Smt</th>
                            <th>Tugas</th>
                            <th>UTS</th>
                            <th>UAS</th>
                            <th>Akhir</th>
                            <th>Huruf</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): 
                            list($huruf, $warna) = nilaiHuruf($row['nilai_akhir']);
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nim']) ?></strong></td>
                                <td><?= htmlspecialchars($row['mahasiswa_nama']) ?></td>
                                <td><small><?= htmlspecialchars($row['program_studi']) ?></small></td>
                                <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                                <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                                <td><?= $row['sks'] ?></td>
                                <td><?= $row['semester'] ?></td>
                                <td><?= number_format($row['nilai_tugas'], 1) ?></td>
                                <td><?= number_format($row['nilai_uts'], 1) ?></td>
                                <td><?= number_format($row['nilai_uas'], 1) ?></td>
                                <td><strong><?= number_format($row['nilai_akhir'], 1) ?></strong></td>
                                <td>
                                    <span style="background: <?= $warna ?>; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold;">
                                        <?= $huruf ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="background: <?= $row['status_verifikasi'] === 'verified' ? '#2ecc71' : '#f39c12' ?>; color: #fff; padding: 2px 8px; border-radius: 15px; font-size: 10px;">
                                        <?= ucfirst($row['status_verifikasi']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    <!-- ============================================ -->
    <!-- TAB 2: LAPORAN IRS -->
    <!-- ============================================ -->
    <?php elseif ($tab === 'irs'): ?>
        
        <!-- Filter -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <input type="hidden" name="tab" value="irs">
            
            <div class="form-group" style="margin: 0; min-width: 130px;">
                <label style="font-size: 13px;">Semester</label>
                <select name="semester" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua --</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?= $s['semester'] ?>" <?= $semester == $s['semester'] ? 'selected' : '' ?>>
                            Smt <?= $s['semester'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0; min-width: 130px;">
                <label style="font-size: 13px;">Status</label>
                <select name="status" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua --</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?tab=irs&export=irs&semester=<?= $semester ?>&status=<?= $status ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="/simak_app/public/admin/cetak_pdf.php?type=irs&semester=<?= $semester ?>&status=<?= $status ?>" target="_blank"
                    class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
        
        <!-- Statistik IRS -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
            <?php foreach ($statistik as $s): ?>
                <div style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid <?= $status_color[$s['status']] ?? '#7f8c8d' ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="font-size: 28px; font-weight: bold; color: <?= $status_color[$s['status']] ?? '#7f8c8d' ?>;">
                        <?= $s['total'] ?>
                    </div>
                    <div style="color: #7f8c8d; font-size: 13px;">IRS <?= ucfirst($s['status']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Rekap IRS per Mahasiswa -->
        <?php if (!empty($rekap_irs)): ?>
            <h4 style="margin: 15px 0 10px;"><i class="fas fa-users"></i> Rekap IRS per Mahasiswa</h4>
            <div style="overflow-x: auto; margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Prodi</th>
                            <th>Smt</th>
                            <th>Jumlah MK</th>
                            <th>Total SKS</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekap_irs as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['nim']) ?></strong></td>
                                <td><?= htmlspecialchars($r['mahasiswa_nama']) ?></td>
                                <td><small><?= htmlspecialchars($r['program_studi']) ?></small></td>
                                <td><?= $r['semester'] ?></td>
                                <td><?= $r['jumlah_mk'] ?></td>
                                <td><strong><?= $r['total_sks'] ?></strong></td>
                                <td style="color: #2ecc71;"><?= $r['approved'] ?></td>
                                <td style="color: #f39c12;"><?= $r['pending'] ?></td>
                                <td style="color: #e74c3c;"><?= $r['rejected'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Detail IRS -->
        <h4 style="margin: 15px 0 10px;"><i class="fas fa-list"></i> Detail IRS (<?= count($data) ?> data)</h4>
        <?php if (empty($data)): ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">Tidak ada data IRS</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Smt</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nim']) ?></strong></td>
                                <td><?= htmlspecialchars($row['mahasiswa_nama']) ?></td>
                                <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                                <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                                <td><?= $row['sks'] ?></td>
                                <td><?= $row['semester'] ?></td>
                                <td>
                                    <span style="background: <?= $status_color[$row['status']] ?>; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><small><?= date('d-m-Y', strtotime($row['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    <!-- ============================================ -->
    <!-- TAB 3: LAPORAN PRESENSI -->
    <!-- ============================================ -->
    <?php elseif ($tab === 'presensi'): ?>
        
        <!-- Filter -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <input type="hidden" name="tab" value="presensi">
            
            <div class="form-group" style="margin: 0; min-width: 220px;">
                <label style="font-size: 13px;">Mata Kuliah</label>
                <select name="course_id" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua Mata Kuliah --</option>
                    <?php foreach ($matakuliah as $mk): ?>
                        <option value="<?= $mk['id'] ?>" <?= $course_id == $mk['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mk['kode_mk']) ?> - <?= htmlspecialchars($mk['nama_mk']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?tab=presensi&export=presensi&course_id=<?= $course_id ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="/simak_app/public/admin/cetak_pdf.php?type=presensi&course_id=<?= $course_id ?>" target="_blank"
                    class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
        
        <!-- Statistik Presensi -->
        <?php if (!empty($statistik)): ?>
            <h4 style="margin: 15px 0 10px;"><i class="fas fa-chart-bar"></i> Statistik Presensi per Mata Kuliah</h4>
            <div style="overflow-x: auto; margin-bottom: 20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>Mahasiswa</th>
                            <th>Total</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                            <th>Sakit</th>
                            <th>Alpa</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistik as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['kode_mk']) ?></strong></td>
                                <td><?= htmlspecialchars($s['nama_mk']) ?></td>
                                <td><?= $s['total_mahasiswa'] ?></td>
                                <td><?= $s['total_presensi'] ?></td>
                                <td style="color: #2ecc71;"><?= $s['hadir'] ?></td>
                                <td style="color: #f39c12;"><?= $s['izin'] ?></td>
                                <td style="color: #3498db;"><?= $s['sakit'] ?></td>
                                <td style="color: #e74c3c;"><?= $s['alpa'] ?></td>
                                <td>
                                    <strong style="color: <?= $s['persentase'] >= 75 ? '#2ecc71' : ($s['persentase'] >= 50 ? '#f39c12' : '#e74c3c') ?>">
                                        <?= $s['persentase'] ?>%
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Detail Presensi -->
        <h4 style="margin: 15px 0 10px;"><i class="fas fa-list"></i> Detail Presensi (<?= count($data) ?> data)</h4>
        <?php if (empty($data)): ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">Tidak ada data presensi</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                            <th>Sakit</th>
                            <th>Alpa</th>
                            <th>Total</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['kode_mk']) ?></strong></td>
                                <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                                <td><?= htmlspecialchars($row['nim']) ?></td>
                                <td><?= htmlspecialchars($row['mahasiswa_nama']) ?></td>
                                <td style="color: #2ecc71;"><?= $row['hadir'] ?></td>
                                <td style="color: #f39c12;"><?= $row['izin'] ?></td>
                                <td style="color: #3498db;"><?= $row['sakit'] ?></td>
                                <td style="color: #e74c3c;"><?= $row['alpa'] ?></td>
                                <td><strong><?= $row['total_pertemuan'] ?></strong></td>
                                <td>
                                    <strong style="color: <?= $row['persentase_hadir'] >= 75 ? '#2ecc71' : ($row['persentase_hadir'] >= 50 ? '#f39c12' : '#e74c3c') ?>">
                                        <?= $row['persentase_hadir'] ?>%
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    <!-- ============================================ -->
    <!-- TAB 4: LAPORAN MAHASISWA -->
    <!-- ============================================ -->
    <?php elseif ($tab === 'mahasiswa'): ?>
        
        <!-- Filter -->
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <input type="hidden" name="tab" value="mahasiswa">
            
            <div class="form-group" style="margin: 0; min-width: 220px;">
                <label style="font-size: 13px;">Jurusan</label>
                <select name="jurusan_id" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%;">
                    <option value="">-- Semua Jurusan --</option>
                    <?php foreach ($prodi_list as $p): ?>
                        <option value="<?= $p['jurusan_id'] ?>" <?= $jurusan_id == $p['jurusan_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['kode']) ?> - <?= htmlspecialchars($p['program_studi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="?tab=mahasiswa&export=mahasiswa&jurusan_id=<?= urlencode($jurusan_id ?? '') ?>" 
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="/simak_app/public/admin/cetak_pdf.php?type=mahasiswa&jurusan_id=<?= $jurusan_id ?>" target="_blank"
                class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
        
        <!-- Data Mahasiswa -->
        <h4 style="margin: 15px 0 10px;"><i class="fas fa-list"></i> Data Mahasiswa & IPK (<?= count($data) ?> mahasiswa)</h4>
        <?php if (empty($data)): ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">Tidak ada data mahasiswa</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Prodi</th>
                            <th>Angkatan</th>
                            <th>Smt</th>
                            <th>Total SKS</th>
                            <th>IPK</th>
                            <th>Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): 
                            $ipk = $row['ipk'] ?? 0;
                            if ($ipk >= 3.5) { $predikat = 'Cumlaude'; $warna = '#2ecc71'; }
                            elseif ($ipk >= 3.0) { $predikat = 'Sangat Baik'; $warna = '#3498db'; }
                            elseif ($ipk >= 2.5) { $predikat = 'Baik'; $warna = '#f39c12'; }
                            elseif ($ipk >= 2.0) { $predikat = 'Cukup'; $warna = '#e67e22'; }
                            else { $predikat = 'Perlu Perbaikan'; $warna = '#e74c3c'; }
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nim']) ?></strong></td>
                                <td><?= htmlspecialchars($row['mahasiswa_nama']) ?></td>
                                <td><small><?= htmlspecialchars($row['email']) ?></small></td>
                                <td><?= htmlspecialchars($row['program_studi']) ?></td>
                                <td><?= $row['angkatan'] ?></td>
                                <td><?= $row['semester'] ?></td>
                                <td><strong><?= $row['total_sks'] ?></strong></td>
                                <td>
                                    <strong style="color: <?= $warna ?>; font-size: 15px;">
                                        <?= number_format($ipk, 2) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span style="background: <?= $warna ?>; color: #fff; padding: 3px 10px; border-radius: 15px; font-size: 11px;">
                                        <?= $predikat ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>