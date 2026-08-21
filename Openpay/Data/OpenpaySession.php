<?php

declare(strict_types=1);

namespace Openpay\Data;

/**
 * Request-scoped Openpay session. Configures static credentials, runs a callback
 * against the merchant root, then {@see Openpay::reset()} so keys cannot leak
 * to the next php-fpm / FrankenPHP request.
 */
final class OpenpaySession
{
    public function __construct(
        private readonly string $id,
        private readonly string $apiKey,
        private readonly string $country = 'MX',
        private readonly string $publicIp = '127.0.0.1',
        private readonly ?OpenpayHttpTransport $transport = null,
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
        if (null !== $this->transport) {
            Openpay::setHttpTransport($this->transport);
        }

        try {
            return $callback(OpenpayApi::createRoot());
        } finally {
            Openpay::reset();
        }
    }
}
