<?php
// ============================================
// helper/EmailHelper.php
// Helper untuk pengiriman email - 100% PHP native.
// Mesin kirim: includes/SmtpMailer.php (SMTP client tanpa library),
// konfigurasi: environment variables (.env).
// ============================================

require_once __DIR__ . '/../includes/SmtpMailer.php';
require_once __DIR__ . '/../config/email.php';

class EmailHelper {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->config = EmailConfig::get();
        
        $this->mailer = new SmtpMailer([
            'host'       => $this->config['host'],
            'port'       => $this->config['port'],
            'encryption' => $this->config['encryption'],
            'auth'       => $this->config['auth'],
            'username'   => $this->config['username'],
            'password'   => $this->config['password'],
        ]);
        
        // Pengirim
        $this->mailer->setFrom(
            $this->config['from_email'],
            $this->config['from_name']
        );
        
        // Reply-To
        if (!empty($this->config['reply_email'])) {
            $this->mailer->addReplyTo(
                $this->config['reply_email'],
                $this->config['reply_name']
            );
        }
    }
    
    /**
     * Kirim email
     * 
     * @param string $to_email Email penerima
     * @param string $to_name Nama penerima
     * @param string $subject Subjek email
     * @param string $body Body email (HTML)
     * @return array Hasil pengiriman
     */
    public function send($to_email, $to_name, $subject, $body) {
        try {
            // Cek konfigurasi
            if (!EmailConfig::isConfigured()) {
                $this->logError("Email belum dikonfigurasi (tujuan: {$to_email})");
                return [
                    'success' => false,
                    'message' => 'Email belum dikonfigurasi. Hubungi administrator.'
                ];
            }
            
            // Set penerima & konten lalu kirim
            $this->mailer->clearAll();
            $this->mailer->addAddress($to_email, $to_name);
            $this->mailer->send($subject, $body);
            
            // Log sukses
            $this->logSuccess($to_email, $subject);
            
            return [
                'success' => true,
                'message' => 'Email berhasil dikirim'
            ];
            
        } catch (RuntimeException $e) {
            $this->logError("Gagal kirim ke {$to_email}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kirim email ke banyak penerima
     */
    public function sendBulk($recipients, $subject, $body) {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[] = [
                'email' => $recipient['email'],
                'result' => $this->send(
                    $recipient['email'],
                    $recipient['name'],
                    $subject,
                    $body
                )
            ];
        }
        
        return $results;
    }
    
    /**
     * Log pengiriman sukses
     */
    private function logSuccess($to_email, $subject) {
        if (!$this->config['log_email']) return;
        
        $this->writeLog('SUCCESS', $to_email, $subject);
    }
    
    /**
     * Log error
     */
    private function logError($message) {
        if (!$this->config['log_email']) return;
        
        $this->writeLog('ERROR', '-', $message);
    }
    
    /**
     * Write log ke file
     */
    private function writeLog($type, $to_email, $message) {
        $logDir = $this->config['log_path'];
        
        // Buat folder jika belum ada
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . 'email_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$type}] To: {$to_email} | {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }
}
