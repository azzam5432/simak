<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Verifikasi Nilai';
$adminController = new AdminController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'verify':
            $result = $adminController->verifyGrade($_POST['grade_id']);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/admin/verifikasi_nilai.php', 'Nilai berhasil diverifikasi!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/admin/verifikasi_nilai.php', 'Gagal: ' . $result['message'], 'error');
            }
            break;
            
        case 'verify_all':
            $result = $adminController->verifyAllGrades($_POST['course_id']);
            if ($result['success']) {
                redirectWithMessage('/simak_app/public/admin/verifikasi_nilai.php', $result['affected'] . ' nilai berhasil diverifikasi!', 'success');
            } else {
                redirectWithMessage('/simak_app/public/admin/verifikasi_nilai.php', 'Gagal: ' . $result['message'], 'error');
            }
            break;
    }
}

$stats = $adminController->getNilaiStats();
$courses_with_draft = $adminController->getCoursesWithDraft();

include __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-number" style="color: #f39c12;"><?= $stats['draft'] ?></div>
        <div class="stat-label">Draft Nilai</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #2ecc71;"><?= $stats['verified'] ?></div>
        <div class="stat-label">Terverifikasi</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #3498db;"><?= $stats['courses_with_draft'] ?></div>
        <div class="stat-label">Mata Kuliah</div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>📝 Nilai Menunggu Verifikasi</h3>
    </div>
    
    <?php if (empty($courses_with_draft)): ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
            <h3>✅ Semua nilai telah diverifikasi</h3>
            <p>Tidak ada nilai draft yang menunggu verifikasi.</p>
        </div>
    <?php else: ?>
        <?php foreach ($courses_with_draft as $course): ?>
            <div style="margin-bottom: 30px; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; background: #fafafa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div>
                        <h4 style="margin: 0;">
                            <?= htmlspecialchars($course['kode_mk']) ?> - 
                            <?= htmlspecialchars($course['nama_mk']) ?>
                            <span style="font-weight: normal; font-size: 14px; color: #7f8c8d; margin-left: 10px;">
                                (<?= $course['sks'] ?> SKS)
                            </span>
                        </h4>
                        <small style="color: #7f8c8d;">
                            Dosen: <?= htmlspecialchars($course['dosen_nama'] ?? '-') ?> 
                            · <?= $course['total_draft'] ?> nilai draft
                        </small>
                    </div>
                    <div>
                        <form method="POST" onsubmit="return confirm('Verifikasi semua nilai untuk <?= htmlspecialchars($course['kode_mk']) ?>?')">
                            <input type="hidden" name="action" value="verify_all">
                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                            <button type="submit" class="btn btn-success">✅ Verifikasi Semua</button>
                        </form>
                    </div>
                </div>
                
                <?php 
                $draft_grades = $adminController->getDraftGradesByCourse($course['id']);
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Mahasiswa</th>
                            <th>Semester</th>
                            <th>Tugas</th>
                            <th>UTS</th>
                            <th>UAS</th>
                            <th>Nilai Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($draft_grades as $g): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($g['nim']) ?></strong></td>
                                <td><?= htmlspecialchars($g['mahasiswa_nama']) ?></td>
                                <td><?= $g['semester'] ?></td>
                                <td><?= number_format($g['nilai_tugas'], 1) ?></td>
                                <td><?= number_format($g['nilai_uts'], 1) ?></td>
                                <td><?= number_format($g['nilai_uas'], 1) ?></td>
                                <td><strong><?= number_format($g['nilai_akhir'], 1) ?></strong></td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="grade_id" value="<?= $g['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">✅ Verifikasi</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>