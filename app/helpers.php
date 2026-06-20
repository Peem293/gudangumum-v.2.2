<?php

if (!function_exists('get_openssl_config_args')) {
    /**
     * Mendapatkan argumen konfigurasi OpenSSL secara dinamis berdasarkan OS (Docker Linux / Windows Dev)
     * * @return array
     */
    function get_openssl_config_args(): array
    {
        // Jalur utama untuk Linux Docker
        $opensslConfigPath = "/etc/ssl/openssl.cnf";

        // Fallback jika tidak berada di lingkungan Docker Linux (PC Windows Dev)
        if (!file_exists($opensslConfigPath)) {
            $opensslConfigPath = "C:/Program Files/PostgreSQL/psqlODBC/etc/openssl.cnf";

            if (!file_exists($opensslConfigPath)) {
                $opensslConfigPath = "D:/laragon/bin/php/php-" . PHP_VERSION . "-Win32-vs17-x64/extras/ssl/openssl.cnf";
            }
        }

        // Kembalikan argumen array jika file konfigurasi fisik ditemukan
        if (file_exists($opensslConfigPath)) {
            return ["config" => $opensslConfigPath];
        }

        return [];
    }
}
