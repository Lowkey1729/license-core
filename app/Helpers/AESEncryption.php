<?php

declare(strict_types=1);

namespace App\Helpers;

use Exception;

class AESEncryption
{
    protected string $data;

    protected ?string $method;

    protected int $options = 0;

    private const string ENCRYPTION_METHOD = 'AES';

    /**
     * @throws Exception
     */
    public function __construct(
        private readonly string $key,
        private readonly string $iv,
        private readonly int $blockSize = 256,
        private readonly string $mode = 'CBC',
    ) {
        $this->setMethod($this->blockSize, $this->mode);
    }

    /**
     * @throws Exception
     */
    public function setMethod(int $blockSize, string $mode = 'CBC'): void
    {
        $method = sprintf(
            '%s-%s-%s',
            self::ENCRYPTION_METHOD, $blockSize, $mode
        );

        if (in_array($method, ['CBC-HMAC-SHA1', 'CBC-HMAC-SHA256', 'XTS'])) {
            $this->method = null;
            throw new Exception('Invalid block size and mode combination!');
        }
        $this->method = $method;
    }

    public function encrypt(string $data): ?string
    {
        return trim((string) openssl_encrypt($data, $this->method, $this->key, $this->options, $this->iv));
    }

    public function decrypt(string $data): ?string
    {
        if (str_contains($data, '{') || str_contains($data, '}') || str_contains($data, '<')) {
            return $data;
        }

        return trim(
            (string) openssl_decrypt($data, $this->method, $this->key, $this->options, $this->iv)
        );
    }
}
