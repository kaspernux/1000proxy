<?php

namespace App\Services;

class TwoFactorService
{
    public function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    public function getOtpAuthUri(string $issuer, string $accountName, string $secret, int $digits = 6, int $period = 30, string $algorithm = 'SHA1'): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'digits' => $digits,
            'period' => $period,
            'algorithm' => strtoupper($algorithm),
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    public function verifyCode(string $secret, string $code, int $window = 1, int $period = 30, int $digits = 6): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!ctype_digit($code) || strlen($code) !== $digits) {
            return false;
        }

        $secretBin = $this->base32Decode($secret);
        if ($secretBin === '') {
            return false;
        }

        $time = time();
        $counter = (int) floor($time / $period);
        for ($i = -$window; $i <= $window; $i++) {
            $hotp = $this->hotp($secretBin, $counter + $i, $digits);
            if (hash_equals($hotp, $code)) {
                return true;
            }
        }
        return false;
    }

    private function hotp(string $secret, int $counter, int $digits = 6): string
    {
        $binCounter = pack('N*', 0) . pack('N*', $counter); // 64-bit big-endian
        $hash = hash_hmac('sha1', $binCounter, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        $code = $truncated % (10 ** $digits);
        return str_pad((string) $code, $digits, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $b32): string
    {
        $b32 = strtoupper($b32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $flipped = array_flip(str_split($alphabet));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        $b32 = preg_replace('/[^A-Z2-7]/', '', $b32);

        for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
            $buffer = ($buffer << 5) | $flipped[$b32[$i]];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
