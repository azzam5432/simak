<?php
// ============================================
// config/env.php
// Loader environment variables dari file .env
//
// Prioritas nilai: environment asli (server/OS)
// > file .env > default di kode pemanggil.
// ============================================

if (!function_exists('loadEnvFile')) {
    /**
     * Parse file .env dan isi $_ENV + putenv
     * untuk key yang belum ada di environment asli.
     *
     * @param string|null $path Path file .env (default: root proyek)
     */
    function loadEnvFile($path = null) {
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris kosong dan komentar
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            // Buang komentar di belakang nilai (hanya jika tidak dalam kutip)
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $hash = strpos($value, ' #');
                if ($hash !== false) {
                    $value = trim(substr($value, 0, $hash));
                }
            }

            // Buang kutip pembungkus nilai
            $len = strlen($value);
            if ($len >= 2
                && (($value[0] === '"'  && substr($value, -1) === '"')
                ||  ($value[0] === "'" && substr($value, -1) === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Environment asli selalu menang atas file .env
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = $value;
                if (function_exists('putenv')) {
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Ambil nilai environment variable.
     *
     * @param string $key     Nama variable
     * @param mixed  $default Nilai default jika tidak ditemukan
     * @return mixed
     */
    function env($key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}

if (!function_exists('env_bool')) {
    /**
     * Ambil nilai boolean dari environment variable.
     * "1", "true", "yes", "on" => true, selainnya false.
     */
    function env_bool($key, $default = false) {
        $value = env($key, null);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

// Load .env sekali saat file ini di-include
loadEnvFile();
