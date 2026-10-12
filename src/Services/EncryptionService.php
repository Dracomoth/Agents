<?php
namespace App\Services;

class EncryptionService
{
    private static string $cipher = 'aes-256-gcm';

    private static function getKey(): string
    {
        $rawKey = defined('APP_ENCRYPTION_KEY') ? APP_ENCRYPTION_KEY : 'default-key-32-chars-long-1234';
        if (str_starts_with($rawKey, 'base64:')) {
            $decoded = base64_decode(substr($rawKey, 7));
            if ($decoded !== false) {
                return substr(str_pad($decoded, 32, "\0"), 0, 32);
            }
        }
        return substr(str_pad($rawKey, 32, "\0"), 0, 32);
    }

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        // Store IV + Tag + Ciphertext encoded in base64
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload): string
    {
        if ($payload === '') {
            return '';
        }
        $raw = base64_decode($payload);
        if ($raw === false) {
            return '';
        }

        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $tagLength = 16;

        if (strlen($raw) < ($ivLength + $tagLength)) {
            return '';
        }

        $iv = substr($raw, 0, $ivLength);
        $tag = substr($raw, $ivLength, $tagLength);
        $ciphertext = substr($raw, $ivLength + $tagLength);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::$cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plaintext !== false ? $plaintext : '';
    }

    public static function mask(string $key): string
    {
        $len = strlen($key);
        if ($len <= 8) {
            return str_repeat('•', max(4, $len));
        }
        return substr($key, 0, 4) . str_repeat('•', 12) . substr($key, -4);
    }
}
