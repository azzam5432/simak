<?php
// ============================================
// templates/email/base_template.php
// Template dasar untuk semua email
// ============================================

/**
 * Render template email dasar
 * 
 * @param string $title Judul email
 * @param string $content Konten HTML
 * @param string $button_text Teks tombol (opsional)
 * @param string $button_url URL tombol (opsional)
 * @return string HTML email
 */
function renderEmailTemplate($title, $content, $button_text = null, $button_url = null) {
    $year = date('Y');
    $app_name = 'SIMAK';
    $app_full = 'Sistem Informasi & Manajemen Akademik';
    $institution = 'Politeknik Mitra Industri';
    
    $button_html = '';
    if ($button_text && $button_url) {
        $button_html = "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$button_url}' style='display: inline-block; padding: 14px 35px; background: #3498db; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;'>
                    {$button_text}
                </a>
            </div>
        ";
    }
    
    return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
</head>
<body style='margin: 0; padding: 0; background: #f4f6f9; font-family: Arial, sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background: #f4f6f9; padding: 30px 15px;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; max-width: 600px;'>
                    
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); padding: 30px; text-align: center;'>
                            <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold; letter-spacing: 2px;'>
                                📚 {$app_name}
                            </h1>
                            <p style='margin: 8px 0 0; color: #ecf0f1; font-size: 13px;'>
                                {$app_full}
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Title -->
                    <tr>
                        <td style='padding: 30px 30px 15px; border-bottom: 2px solid #f4f6f9;'>
                            <h2 style='margin: 0; color: #2c3e50; font-size: 20px;'>
                                {$title}
                            </h2>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style='padding: 25px 30px; color: #555; font-size: 14px; line-height: 1.7;'>
                            {$content}
                            {$button_html}
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='background: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;'>
                            <p style='margin: 0; color: #7f8c8d; font-size: 12px;'>
                                Email ini dikirim otomatis oleh sistem {$app_name}.<br>
                                Jangan balas email ini.
                            </p>
                            <p style='margin: 10px 0 0; color: #95a5a6; font-size: 11px;'>
                                &copy; {$year} {$app_name} - {$institution}
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
    ";
}
?>