<?php

namespace App\Helpers;

use Random\RandomException;

class LicenseKeyGenerator
{
    private const int ENTROPY_BYTES = 16;

    /**
     * @throws RandomException
     */
    public function handle(): string
    {
        $bytes = random_bytes(self::ENTROPY_BYTES);

        return $this->base32Encode($bytes);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';
        $output = '';
        $v = 0;
        $vBits = 0;

        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vBits += 8;

            while ($vBits >= 5) {
                $vBits -= 5;
                $output .= $alphabet[($v >> $vBits) & 0x1F];
            }
        }

        if ($vBits > 0) {
            $output .= $alphabet[($v << (5 - $vBits)) & 0x1F];
        }

        return $output;
    }
}
