<?php

declare(strict_types=1);

namespace Openpay\Data;

/**
 * Request-scoped Openpay session. Configures static credentials, runs a callback
 * against the merchant root, then {@see Openpay::reset()} so keys cannot leak
 * to the next php-fpm / FrankenPHP request.
 */
final readonly class OpenpaySession
{
    public function __construct(
        private string $id,
        private string $apiKey,
        private string $country = 'MX',
        private string $publicIp = '127.0.0.1',
        private ?OpenpayHttpTransport $transport = null,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(OpenpayApi): T $callback
     *
     * @return T
     */
    public function run(callable $callback): mixed
    {
        Openpay::configure($this->id, $this->apiKey, $this->country, $this->publicIp);
        if ($this->transport instanceof OpenpayHttpTransport) {
            Openpay::setHttpTransport($this->transport);
        }

        try {
            return $callback(OpenpayApi::createRoot());
        } finally {
            Openpay::reset();
        }
    }
}
