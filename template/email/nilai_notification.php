<?php
// ============================================
// templates/email/nilai_notification.php
// Template email notifikasi nilai
// ============================================

require_once __DIR__ . '/base_template.php';

function renderNilaiNotification($data) {
    $nama = htmlspecialchars($data['nama']);
    $nim = htmlspecialchars($data['nim']);
    $kode_mk = htmlspecialchars($data['kode_mk']);
    $nama_mk = htmlspecialchars($data['nama_mk']);
    $sks = $data['sks'];
    $nilai_akhir = floatval($data['nilai_akhir']);
    $nilai_tugas = floatval($data['nilai_tugas']);
    $nilai_uts = floatval($data['nilai_uts']);
    $nilai_uas = floatval($data['nilai_uas']);
    
    // Konversi huruf
    if ($nilai_akhir >= 85) { $huruf = 'A'; $warna = '#2ecc71'; }
    elseif ($nilai_akhir >= 75) { $huruf = 'B'; $warna = '#3498db'; }
    elseif ($nilai_akhir >= 65) { $huruf = 'C'; $warna = '#f39c12'; }
    elseif ($nilai_akhir >= 50) { $huruf = 'D'; $warna = '#e67e22'; }
    else { $huruf = 'E'; $warna = '#e74c3c'; }
    
    $content = "
        <p>Halo <strong>{$nama}</strong>,</p>
        <p>Nilai Anda untuk mata kuliah berikut telah <strong>diverifikasi</strong> oleh Admin Akademik:</p>
        
        <div style='background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                    <td style='padding: 8px 0;'><strong>NIM:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$nim}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>Mata Kuliah:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$kode_mk} - {$nama_mk}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>SKS:</strong></td>
                    <td style='padding: 8px 0; text-align: right;'>{$sks}</td>
                </tr>
            </table>
        </div>
        
        <h3 style='color: #2c3e50; font-size: 15px; margin: 20px 0 10px;'>Rincian Nilai:</h3>
        <table width='100%' cellpadding='0' cellspacing='0' style='border: 1px solid #e9ecef; border-radius: 6px; overflow: hidden;'>
            <tr style='background: #f8f9fa;'>
                <td style='padding: 12px;'><strong>Nilai Tugas (30%)</strong></td>
                <td style='padding: 12px; text-align: right;'>" . number_format($nilai_tugas, 1) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border-top: 1px solid #e9ecef;'><strong>Nilai UTS (30%)</strong></td>
                <td style='padding: 12px; text-align: right; border-top: 1px solid #e9ecef;'>" . number_format($nilai_uts, 1) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border-top: 1px solid #e9ecef;'><strong>Nilai UAS (40%)</strong></td>
                <td style='padding: 12px; text-align: right; border-top: 1px solid #e9ecef;'>" . number_format($nilai_uas, 1) . "</td>
            </tr>
            <tr style='background: #e8f4fd;'>
                <td style='padding: 15px; border-top: 2px solid #3498db;'><strong>NILAI AKHIR</strong></td>
                <td style='padding: 15px; text-align: right; border-top: 2px solid #3498db;'>
                    <strong style='font-size: 20px; color: {$warna};'>
                        " . number_format($nilai_akhir, 1) . " ({$huruf})
                    </strong>
                </td>
            </tr>
        </table>
        
        <p style='margin-top: 20px; font-size: 13px; color: #7f8c8d;'>
            Nilai ini sudah masuk ke Kartu Hasil Studi (KHS) Anda. Silakan cek di menu KHS & IPK.
        </p>
    ";
    
    return renderEmailTemplate(
        'Nilai Anda Telah Diverifikasi',
        $content,
        'Lihat KHS',
        'http://localhost/simak_app/public/mahasiswa/khs.php'
    );
}
?>