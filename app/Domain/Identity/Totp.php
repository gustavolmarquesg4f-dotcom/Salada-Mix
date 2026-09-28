<?php

namespace App\Domain\Identity;

use InvalidArgumentException;

class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function newSecret(): string
    {
        return $this->encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $email): string
    {
        return 'otpauth://totp/'.rawurlencode('Salada Mix:'.$email)
            .'?secret='.rawurlencode($secret).'&issuer='.rawurlencode('Salada Mix')
            .'&algorithm=SHA1&digits=6&period=30';
    }

    /** @return array{step: int, code: string}|null */
    public function verify(string $secret, string $code, ?int $now = null): ?array
    {
        if (! preg_match('/^[0-9]{6}$/', $code)) {
            return null;
        }

        $step = intdiv($now ?? time(), 30);

        // Accept clock drift of up to 30 seconds, but caller rejects reused steps.
        foreach ([$step, $step - 1, $step + 1] as $candidate) {
            if ($candidate >= 0 && hash_equals($this->code($secret, $candidate), $code)) {
                return ['step' => $candidate, 'code' => $code];
            }
        }

        return null;
    }

    public function code(string $secret, int $step): string
    {
        $binary = $this->decode($secret);
        $counter = pack('N2', intdiv($step, 4294967296), $step & 0xffffffff);
        $digest = hash_hmac('sha1', $counter, $binary, true);
        $offset = ord($digest[19]) & 0x0f;
        $truncated = unpack('N', substr($digest, $offset, 4))[1] & 0x7fffffff;

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function encode(string $bytes): string
    {
        $bits = '';

        foreach (unpack('C*', $bytes) as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $result = '';

        for ($i = 0; $i < strlen($bits); $i += 5) {
            $result .= self::ALPHABET[bindec(str_pad(substr($bits, $i, 5), 5, '0'))];
        }

        return $result;
    }

    private function decode(string $input): string
    {
        if (! preg_match('/^[A-Z2-7]{32}$/', $input)) {
            throw new InvalidArgumentException('Invalid authenticator secret.');
        }

        $bits = '';

        foreach (str_split($input) as $character) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $character)), 5, '0', STR_PAD_LEFT);
        }

        $result = '';

        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $result .= chr(bindec(substr($bits, $i, 8)));
        }

        return $result;
    }
}

