<?php

declare(strict_types=1);

namespace Openpay\Data;

/**
 * Sends an already-encoded Openpay HTTP request.
 *
 * @phpstan-type Response array{0: string, 1: int}
 */
interface OpenpayHttpTransport
{
    /**
     * @param list<string> $headers
     *
     * @return array{0: string, 1: int} Response body and HTTP status code
     *
     * @throws OpenpayApiConnectionError
     */
    public function send(string $method, string $url, array $headers, ?string $body, ?string $auth): array;
}
