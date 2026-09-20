<?php
// ============================================
// templates/email/irs_notification.php
// Template email notifikasi IRS
// ============================================

require_once __DIR__ . '/base_template.php';

/**
 * Template email IRS Approved/Rejected
 * 
 * @param array $data Data IRS
 * @return string HTML email
 */
function renderIRSNotification($data) {
    $nama = htmlspecialchars($data['nama']);
    $nim = htmlspecialchars($data['nim']);
    $semester = htmlspecialchars($data['semester']);
    $status = $data['status'];
    $catatan = htmlspecialchars($data['catatan'] ?? '');
    $total_sks = $data['total_sks'] ?? 0;
    $matakuliah = $data['matakuliah'] ?? [];
    
    // Status badge & warna
    if ($status === 'approved') {
        $status_text = '✅ DISETUJUI';
        $status_color = '#2ecc71';
        $title = 'IRS Anda Telah Disetujui';
        $intro = "Selamat! Rencana Studi (IRS) Anda telah <strong>disetujui</strong> oleh Admin Akademik.";
    } else {
        $status_text = '❌ DITOLAK';
        $status_color = '#e74c3c';
        $title = 'IRS Anda Ditolak';
        $intro = "Mohon maaf, Rencana Studi (IRS) Anda <strong>ditolak</strong> oleh Admin Akademik.";
    }
    
    // Daftar mata kuliah
    $mk_rows = '';
    foreach ($matakuliah as $mk) {
        $mk_rows .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>
                    <strong>" . htmlspecialchars($mk['kode_mk']) . "</strong>
                </td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef;'>
                    " . htmlspecialchars($mk['nama_mk']) . "
                </td>
                <td style='padding: 10px; border-bottom: 1px solid #e9ecef; text-align: center;'>
                    " . $mk['sks'] . "
                </td>
            </tr>
        ";
    }
    
    $catatan_html = '';
    if ($status === 'rejected' && !empty($catatan)) {
        $catatan_html = "
            <div style='background: #fff3cd; border-left: 4px solid #f39c12; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                <strong style='color: #856404;'>Catatan dari Admin:</strong>
                <p style='margin: 8px 0 0; color: #856404;'>" . nl2br($catatan) . "</p>
            </div>
        ";
    }
    
    $content = "
        <p>Halo <strong>{$nama}</strong>,</p>
        <p>{$intro}</p>
        
        <div style='background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                    <td style='padding: 8px 0;'><strong>NIM:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$nim}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>Semester:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$semester}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>Total SKS:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$total_sks} SKS</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>Status:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>
                        <span style='background: {$status_color}; color: #fff; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;'>
                            {$status_text}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <h3 style='color: #2c3e50; font-size: 15px; margin: 20px 0 10px;'>Daftar Mata Kuliah:</h3>
        <table width='100%' cellpadding='0' cellspacing='0' style='border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden;'>
            <thead>
                <tr style='background: #f8f9fa;'>
                    <th style='padding: 10px; text-align: left; font-size: 12px; color: #7f8c8d;'>KODE</th>
                    <th style='padding: 10px; text-align: left; font-size: 12px; color: #7f8c8d;'>MATA KULIAH</th>
                    <th style='padding: 10px; text-align: center; font-size: 12px; color: #7f8c8d;'>SKS</th>
                </tr>
            </thead>
            <tbody>
                {$mk_rows}
            </tbody>
        </table>
        
        {$catatan_html}
        
        <p style='margin-top: 20px; font-size: 13px; color: #7f8c8d;'>
            Silakan login ke aplikasi SIMAK untuk melihat detail lengkap.
        </p>
    ";
    
    return renderEmailTemplate(
        $title,
        $content,
        'Login ke SIMAK',
        'http://localhost/simak_app/public/'
    );
}
?>