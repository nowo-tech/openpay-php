<?php

declare(strict_types=1);

namespace Openpay\Tests\Unit;

use Openpay\Data\OpenpayHttpTransport;

final class FakeOpenpayHttpTransport implements OpenpayHttpTransport
{
    /** @var list<array{method: string, url: string, headers: list<string>, body: ?string, auth: ?string}> */
    public array $calls = [];

    public string $body = '{}';

    public int $status = 200;

    public function send(string $method, string $url, array $headers, ?string $body, ?string $auth): array
    {
        $this->calls[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'auth' => $auth,
        ];

        return [$this->body, $this->status];
    }
}
