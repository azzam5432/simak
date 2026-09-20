<?php
// ============================================
// test_email.php (root proyek)
// Testing Pengiriman Email
// HAPUS SETELAH SELESAI!
// ============================================

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/email.php';

checkAccess(['admin']);

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/helper/EmailHelper.php';
    
    $to_email = $_POST['to_email'];
    $to_name = $_POST['to_name'] ?? 'Test User';
    
    $emailHelper = new EmailHelper();
    
    $testBody = "
        <h2>Test Email dari SIMAK</h2>
        <p>Halo <strong>{$to_name}</strong>,</p>
        <p>Ini adalah email testing dari sistem SIMAK.</p>
        <p>Jika Anda menerima email ini, berarti konfigurasi email sudah <strong>berhasil</strong>!</p>
        <p>Waktu: " . date('d-m-Y H:i:s') . "</p>
    ";
    
    $result = $emailHelper->send(
        $to_email,
        $to_name,
        'Test Email SIMAK - ' . date('H:i:s'),
        $testBody
    );
}

include __DIR__ . '/includes/header.php';
?>

<div class="table-container" style="max-width: 600px; margin: 0 auto;">
    <h3><i class="fas fa-envelope"></i> Test Pengiriman Email</h3>
    
    <?php if (!EmailConfig::isConfigured()): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Email belum dikonfigurasi. Salin <code>.env.example</code> menjadi <code>.env</code> lalu isi kredensial SMTP di sana.
        </div>
    <?php endif; ?>
    
    <?php if ($result): ?>
        <div class="alert alert-<?= $result['success'] ? 'success' : 'error' ?>">
            <i class="fas fa-<?= $result['success'] ? 'check-circle' : 'times-circle' ?>"></i>
            <?= htmlspecialchars($result['message']) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email Penerima</label>
            <input type="email" name="to_email" required placeholder="test@example.com">
        </div>
        <div class="form-group">
            <label>Nama Penerima</label>
            <input type="text" name="to_name" value="Test User">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Kirim Test Email
        </button>
    </form>
    
    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; font-size: 13px;">
        <strong>Info Konfigurasi:</strong>
        <ul style="margin: 10px 0 0 20px; color: #7f8c8d;">
            <li>Host: <?= EmailConfig::get('host') ?></li>
            <li>Port: <?= EmailConfig::get('port') ?></li>
            <li>From: <?= EmailConfig::get('from_email') ?></li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
