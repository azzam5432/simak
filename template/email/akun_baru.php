<?php
// ============================================
// templates/email/akun_baru.php
// Template email akun baru
// ============================================

require_once __DIR__ . '/base_template.php';

function renderAkunBaru($data) {
    $nama = htmlspecialchars($data['nama']);
    $username = htmlspecialchars($data['username']);
    $password = htmlspecialchars($data['password']);
    $role = htmlspecialchars($data['role']);
    $login_url = 'http://localhost/simak_app/public/';
    
    $role_label = [
        'mahasiswa' => 'Mahasiswa',
        'dosen' => 'Dosen',
        'admin' => 'Administrator'
    ][$role] ?? 'Pengguna';
    
    $content = "
        <p>Halo <strong>{$nama}</strong>,</p>
        <p>Selamat! Akun <strong>{$role_label}</strong> Anda di Sistem Informasi Akademik Kampus (SIMAK) telah dibuat.</p>
        
        <div style='background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 25px; margin: 25px 0; text-align: center;'>
            <h3 style='margin: 0 0 15px; color: #2c3e50;'>Informasi Login Anda</h3>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='margin: 15px 0;'>
                <tr>
                    <td style='padding: 8px 0; text-align: left;'><strong>Username:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>
                        <code style='background: #fff; padding: 5px 15px; border-radius: 4px; font-size: 14px;'>{$username}</code>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; text-align: left;'><strong>Password:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>
                        <code style='background: #fff; padding: 5px 15px; border-radius: 4px; font-size: 14px;'>{$password}</code>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style='background: #fff3cd; border-left: 4px solid #f39c12; padding: 15px; border-radius: 6px; margin: 20px 0;'>
            <strong style='color: #856404;'>⚠️ PENTING:</strong>
            <p style='margin: 8px 0 0; color: #856404; font-size: 13px;'>
                Demi keamanan akun Anda, segera ganti password setelah login pertama kali.
            </p>
        </div>
        
        <p style='font-size: 13px; color: #7f8c8d;'>
            Jika Anda tidak merasa mendaftar akun ini, abaikan email ini.
        </p>
    ";
    
    return renderEmailTemplate(
        'Akun SIMAK Anda Telah Dibuat',
        $content,
        'Login Sekarang',
        $login_url
    );
}
?>