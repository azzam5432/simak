<?php
// ============================================
// includes/SmtpMailer.php
// SMTP client murni PHP (tanpa library eksternal).
//
// Fitur:
// - Koneksi via fsockopen (timeout bisa diatur)
// - STARTTLS (port 587) dan implicit SSL (port 465)
// - AUTH LOGIN
// - MIME multipart/alternative (HTML + text otomatis)
// - Header UTF-8 (RFC 2047 base64), dot-stuffing (RFC 5321)
// - Proteksi header injection pada nama/subject/email
//
// Metode send() melempar RuntimeException jika gagal,
// sehingga pemanggil bisa menangkap pesan errornya.
// ============================================

class SmtpMailer
{
    private $host;
    private $port;
    private $encryption;   // 'tls' | 'ssl' | 'none'
    private $auth;
    private $username;
    private $password;
    private $timeout;

    private $fromEmail = '';
    private $fromName  = '';
    private $to  = [];
    private $cc  = [];
    private $bcc = [];
    private $replyTo = [];

    private $charset  = 'UTF-8';
    private $socket   = null;
    private $lastResp = '';
    private $debugLog = [];

    /**
     * @param array $config [host, port, encryption, auth, username, password, timeout]
     */
    public function __construct(array $config) {
        $this->host       = (string) ($config['host'] ?? 'localhost');
        $this->port       = (int)    ($config['port'] ?? 587);
        $this->encryption = strtolower((string) ($config['encryption'] ?? 'tls'));
        $this->auth       = (bool)   ($config['auth'] ?? true);
        $this->username   = (string) ($config['username'] ?? '');
        $this->password   = (string) ($config['password'] ?? '');
        $this->timeout    = (int)    ($config['timeout'] ?? 15);
    }

    // ============================================
    // Pengirim & penerima
    // ============================================

    public function setFrom($email, $name = '') {
        $this->fromEmail = $this->sanitizeEmail($email);
        $this->fromName  = $this->sanitizeName($name);
        return $this;
    }

    public function addAddress($email, $name = '') {
        $this->to[] = ['email' => $this->sanitizeEmail($email), 'name' => $this->sanitizeName($name)];
        return $this;
    }

    public function addCC($email, $name = '') {
        $this->cc[] = ['email' => $this->sanitizeEmail($email), 'name' => $this->sanitizeName($name)];
        return $this;
    }

    public function addBCC($email, $name = '') {
        $this->bcc[] = ['email' => $this->sanitizeEmail($email), 'name' => $this->sanitizeName($name)];
        return $this;
    }

    public function addReplyTo($email, $name = '') {
        $this->replyTo = ['email' => $this->sanitizeEmail($email), 'name' => $this->sanitizeName($name)];
        return $this;
    }

    /** Kosongkan daftar penerima (To/Cc/Bcc). */
    public function clearAddresses() {
        $this->to = $this->cc = $this->bcc = [];
        return $this;
    }

    /** Kosongkan penerima sekaligus Reply-To. */
    public function clearAll() {
        $this->clearAddresses();
        $this->replyTo = [];
        return $this;
    }

    public function getDebugLog() {
        return $this->debugLog;
    }

    // ============================================
    // Kirim email
    // ============================================

    /**
     * Kirim email. Melempar RuntimeException jika gagal.
     *
     * @param string $subject  Subjek email
     * @param string $htmlBody Body HTML (text/plain otomatis dibuat)
     */
    public function send($subject, $htmlBody) {
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            throw new RuntimeException('Tidak ada penerima email.');
        }

        $this->connect();
        try {
            $this->ehlo();

            if ($this->encryption === 'tls') {
                $this->startTls();
                $this->ehlo(); // EHLO ulang setelah TLS
            }

            if ($this->auth) {
                $this->authLogin();
            }

            // Envelope
            $this->command('MAIL FROM:<' . $this->fromEmail . '>', [250]);
            foreach (array_merge($this->to, $this->cc, $this->bcc) as $r) {
                $this->command('RCPT TO:<' . $r['email'] . '>', [250, 251]);
            }

            // Data
            $this->command('DATA', [354]);
            $message = $this->buildMessage($subject, $htmlBody);
            $this->writeData($message);
            // Terminator: kirim titik lalu baca respons 250
            $this->command('.', [250]);

            $this->command('QUIT', [221]);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
                $this->socket = null;
            }
        }
    }

    // ============================================
    // Protokol SMTP
    // ============================================

    private function connect() {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port;

        // CA bundle sering belum diset di XAMPP/Windows,
        // jadi verifikasi sertifikat dinonaktifkan untuk kepraktisan development.
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $this->logDebug("CONNECT {$remote}");
        $socket = @stream_socket_client($remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            throw new RuntimeException("Koneksi ke server SMTP gagal: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;

        // Banner server, mis. "220 smtp.gmail.com ESMTP ..."
        $this->readResponse([220]);
    }

    private function ehlo() {
        $hostname = ($_SERVER['SERVER_NAME'] ?? '')
            ?: (gethostname() ?: 'localhost');

        $code = null;
        $this->lastResp = '';
        $this->writeLine('EHLO ' . $hostname);

        // EHLO membalas multiline; simpan barisnya untuk cek ekstensi
        do {
            list($code, $line) = $this->readLine();
            $this->lastResp .= $line . "\n";
        } while ($code === null);

        if ($code !== 250) {
            throw new RuntimeException("EHLO gagal ({$code}): " . trim($this->lastResp));
        }
    }

    private function startTls() {
        if (stripos($this->lastResp, 'STARTTLS') === false) {
            throw new RuntimeException('Server SMTP tidak mendukung STARTTLS.');
        }

        $this->command('STARTTLS', [220]);

        if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Negosiasi TLS gagal.');
        }

        $this->logDebug('TLS aktif');
    }

    private function authLogin() {
        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode($this->username), [334]);
        $this->command(base64_encode($this->password), [235], 'Autentikasi SMTP gagal');
    }

    private function command($cmd, array $expectCodes, $errorPrefix = 'SMTP error') {
        $this->writeLine($cmd);
        return $this->readResponse($expectCodes, $errorPrefix);
    }

    private function readResponse(array $expectCodes, $errorPrefix = 'SMTP error') {
        list($code, $text) = $this->readLine();

        if (!in_array($code, $expectCodes, true)) {
            throw new RuntimeException("{$errorPrefix} ({$code}): {$text}");
        }

        return [$code, $text];
    }

    private function readLine() {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Koneksi SMTP tidak tersedia.');
        }

        $line = fgets($this->socket, 1024);

        if ($line === false) {
            $meta = stream_get_meta_data($this->socket);
            throw new RuntimeException(
                $meta['timed_out'] ? 'Timeout menunggu respons SMTP.' : 'Koneksi SMTP terputus.'
            );
        }

        $this->logDebug('<< ' . rtrim($line));

        if (preg_match('/^(\d{3})-(.*)$/', rtrim($line, "\r\n"), $m)) {
            return [null, $m[0]]; // baris lanjutan (multiline)
        }

        if (preg_match('/^(\d{3}) (.*)$/', rtrim($line, "\r\n"), $m)) {
            return [(int) $m[1], $m[2]]; // baris terakhir
        }

        throw new RuntimeException('Respons SMTP tidak valid: ' . rtrim($line));
    }

    private function writeLine($line) {
        $this->logDebug('>> ' . $line);
        $this->write($line . "\r\n");
    }

    private function writeData($data) {
        // Dot-stuffing (RFC 5321 §4.5.2): baris diawali titik ditambah titik
        $data = preg_replace('/^\./m', '..', $data);
        $this->write($data);
        if (substr($data, -2) !== "\r\n") {
            $this->write("\r\n");
        }
    }

    private function write($data) {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('Koneksi SMTP tidak tersedia.');
        }

        $result = @fwrite($this->socket, $data);
        if ($result === false) {
            throw new RuntimeException('Gagal mengirim data ke server SMTP.');
        }
    }

    // ============================================
    // Pembuatan pesan (MIME)
    // ============================================

    private function buildMessage($subject, $htmlBody) {
        $subject = $this->sanitizeName($subject);
        $boundary = '=b_' . md5(uniqid('', true));

        $headers = [];
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'From: ' . $this->formatAddress($this->fromName, $this->fromEmail);
        $headers[] = 'To: ' . $this->formatAddressList($this->to);
        if (!empty($this->cc)) {
            $headers[] = 'Cc: ' . $this->formatAddressList($this->cc);
        }
        if (!empty($this->replyTo)) {
            $headers[] = 'Reply-To: ' . $this->formatAddress($this->replyTo['name'], $this->replyTo['email']);
        }
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Message-ID: <' . md5(uniqid('', true)) . '@' . $this->fromDomain() . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'X-Mailer: SIMAK/PHP-native';

        // Bagian text/plain otomatis dari HTML
        $textBody = $this->htmlToText($htmlBody);

        $body = '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset={$this->charset}\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($textBody) . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset={$this->charset}\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($htmlBody) . "\r\n"
            . '--' . $boundary . '--';

        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function formatAddressList(array $recipients) {
        $parts = [];
        foreach ($recipients as $r) {
            $parts[] = $this->formatAddress($r['name'], $r['email']);
        }
        return implode(', ', $parts);
    }

    private function formatAddress($name, $email) {
        if ($name === '') {
            return $email;
        }
        return $this->encodeHeader($name) . " <{$email}>";
    }

    /** Encoding header RFC 2047 (base64) untuk teks non-ASCII. */
    private function encodeHeader($text) {
        if (preg_match('/[^\x20-\x7E]/', $text)) {
            return '=?' . $this->charset . '?B?' . base64_encode($text) . '?=';
        }
        return $text;
    }

    private function htmlToText($html) {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(p|div|h[1-6]|tr|li)>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, $this->charset);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    private function fromDomain() {
        $pos = strrchr($this->fromEmail, '@');
        return $pos !== false ? substr($pos, 1) : 'localhost';
    }

    // ============================================
    // Sanitasi (anti header injection)
    // ============================================

    private function sanitizeEmail($email) {
        $email = trim(preg_replace('/[\r\n\x00]/', '', (string) $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Alamat email tidak valid: {$email}");
        }
        return $email;
    }

    private function sanitizeName($name) {
        // Buang karakter yang bisa memutus baris header
        return trim(preg_replace('/[\r\n\x00]/', ' ', (string) $name));
    }

    private function logDebug($line) {
        $this->debugLog[] = $line;
    }
}
