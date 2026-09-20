<?php
// ============================================
// templates/email/reset_password.php
// Template email reset password
// ============================================

require_once __DIR__ . '/base_template.php';

function renderResetPassword($data) {
    $nama = htmlspecialchars($data['nama']);
    $username = htmlspecialchars($data['username']);
    $new_password = htmlspecialchars($data['new_password']);
    $reset_by = htmlspecialchars($data['reset_by'] ?? 'Admin');
    $login_url = 'http://localhost/simak_app/public/';
    
    $content = "
        <p>Halo <strong>{$nama}</strong>,</p>
        <p>Password akun SIMAK Anda telah <strong>direset</strong> oleh <strong>{$reset_by}</strong>.</p>
        
        <div style='background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 25px; margin: 25px 0; text-align: center;'>
            <h3 style='margin: 0 0 15px; color: #2c3e50;'>Password Baru Anda</h3>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='margin: 15px 0;'>
                <tr>
                    <td style='padding: 8px 0; text-align: left;'><strong>Username:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>
                        <code style='background: #fff; padding: 5px 15px; border-radius: 4px; font-size: 14px;'>{$username}</code>
                    </td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; text-align: left;'><strong>Password Baru:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>
                        <code style='background: #fff; padding: 5px 15px; border-radius: 4px; font-size: 14px;'>{$new_password}</code>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style='background: #fff3cd; border-left: 4px solid #f39c12; padding: 15px; border-radius: 6px; margin: 20px 0;'>
            <strong style='color: #856404;'>⚠️ PENTING:</strong>
            <p style='margin: 8px 0 0; color: #856404; font-size: 13px;'>
                Segera ganti password ini setelah login untuk keamanan akun Anda.
            </p>
        </div>
        
        <p style='font-size: 13px; color: #7f8c8d;'>
            Jika Anda tidak meminta reset password, segera hubungi Admin Akademik.
        </p>
    ";
    
    return renderEmailTemplate(
        'Password Akun SIMAK Anda Direset',
        $content,
        'Login Sekarang',
        $login_url
    );
}
?>