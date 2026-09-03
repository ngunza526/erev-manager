<?php

namespace App\Support;

/**
 * TOTP (RFC 6238) minimal, sans dependance : secret base32, HMAC-SHA1,
 * pas de 30 secondes, code a 6 chiffres. Sert la 2FA par application
 * d'authentification (colonne users.otp_secret).
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    /** Genere un secret base32 (par defaut 160 bits, recommandation RFC 4226). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes(max(10, $bytes)));
    }

    /**
     * Verifie un code sur la fenetre [-$window, +$window] pas de temps
     * (tolerance de derive d'horloge). Comparaison a temps constant.
     */
    public static function verify(string $secret, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\D/', '', $code);

        if (strlen((string) $code) !== self::DIGITS) {
            return false;
        }

        $timestamp ??= time();
        $counter = intdiv($timestamp, self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeForCounter($secret, $counter + $i), (string) $code)) {
                return true;
            }
        }

        return false;
    }

    /** Code courant (utile pour les tests et la confirmation d'activation). */
    public static function codeAt(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return self::codeForCounter($secret, intdiv($timestamp, self::PERIOD));
    }

    /** URI otpauth:// a encoder dans un QR code. */
    public static function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer).':'.rawurlencode($accountName);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
    }

    private static function codeForCounter(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);

        if ($key === '') {
            return str_repeat('0', self::DIGITS);
        }

        $binCounter = pack('N*', 0, $counter); // 64 bits big-endian
        $hash = hash_hmac('sha1', $binCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = (unpack('N', $part)[1] & 0x7FFFFFFF) % (10 ** self::DIGITS);

        return str_pad((string) $value, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $output;
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));

        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }
}
