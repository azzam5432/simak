<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

checkAccess(['admin']);

$page_title = 'Broadcast Pengumuman';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    try {
        $sender_id = $_SESSION['user_id'];
        $target_role = $_POST['target_role'];
        $judul = trim($_POST['judul']);
        $pesan = trim($_POST['pesan']);
        $priority = $_POST['priority'] ?? 'medium';
        
        if (empty($judul) || empty($pesan)) {
            redirectWithMessage('/simak_app/public/admin/broadcast.php', 'Judul dan pesan harus diisi!', 'error');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO announcements (sender_id, sender_role, target_role, judul, pesan, priority)
            VALUES (?, 'admin', ?, ?, ?, ?)
        ");
        $stmt->execute([$sender_id, $target_role, $judul, $pesan, $priority]);
        
        redirectWithMessage('/simak_app/public/admin/broadcast.php', 'Pengumuman berhasil dikirim ke semua pengguna!', 'success');
    } catch (PDOException $e) {
        redirectWithMessage('/simak_app/public/admin/broadcast.php', 'Gagal: ' . $e->getMessage(), 'error');
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, u.nama as sender_name
    FROM announcements a
    JOIN users u ON a.sender_id = u.id
    WHERE a.sender_role = 'admin'
    ORDER BY a.created_at DESC
    LIMIT 50
");
$stmt->execute();
$history = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
    <div>
        <div class="table-container">
            <h3><i class="fas fa-bullhorn"></i> Kirim Pengumuman Kampus</h3>
            <form method="POST">
                <input type="hidden" name="action" value="send">
                
                <div class="form-group">
                    <label>Judul Pengumuman <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="judul" required placeholder="Masukkan judul pengumuman">
                </div>
                
                <div class="form-group">
                    <label>Target Penerima <span style="color: #e74c3c;">*</span></label>
                    <select name="target_role" required>
                        <option value="all">Semua Pengguna</option>
                        <option value="admin">Admin</option>
                        <option value="dosen">Dosen</option>
                        <option value="mahasiswa">Mahasiswa</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Prioritas</label>
                    <select name="priority">
                        <option value="low">Rendah</option>
                        <option value="medium" selected>Sedang</option>
                        <option value="high">Tinggi (Penting)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Pesan <span style="color: #e74c3c;">*</span></label>
                    <textarea name="pesan" rows="6" required placeholder="Tulis pesan pengumuman..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 16px;">
                    <i class="fas fa-paper-plane"></i> Kirim Pengumuman
                </button>
            </form>
        </div>
    </div>
    
    <div>
        <div class="table-container">
            <h3><i class="fas fa-history"></i> Riwayat Pengumuman</h3>
            
            <?php if (empty($history)): ?>
                <p style="text-align: center; color: #7f8c8d; padding: 30px;">
                    Belum ada pengumuman yang dikirim
                </p>
            <?php else: ?>
                <?php foreach ($history as $h): ?>
                    <div style="padding: 12px 15px; border-bottom: 1px solid #e9ecef; <?= $h['priority'] === 'high' ? 'border-left: 4px solid #e74c3c;' : '' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= htmlspecialchars($h['judul']) ?></strong>
                                <?php if ($h['priority'] === 'high'): ?>
                                    <span class="status-badge" style="background: #e74c3c; color: #fff; padding: 2px 10px; border-radius: 20px; font-size: 10px;">Penting</span>
                                <?php endif; ?>
                            </div>
                            <small style="color: #7f8c8d;">
                                <?= date('d-m-Y H:i', strtotime($h['created_at'])) ?>
                            </small>
                        </div>
                        <div style="font-size: 13px; color: #555; margin: 5px 0;">
                            <?= htmlspecialchars(substr($h['pesan'], 0, 100)) ?>...
                        </div>
                        <div style="display: flex; gap: 10px; font-size: 12px; color: #7f8c8d;">
                            <span>Target: <?= ucfirst($h['target_role']) ?></span>
                            <span>Oleh: <?= htmlspecialchars($h['sender_name']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>