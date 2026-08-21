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

    /** @var list<array{0: string, 1: int}> */
    public array $queue = [];

    public function enqueue(string $body, int $status = 200): void
    {
        $this->queue[] = [$body, $status];
    }

    public function send(string $method, string $url, array $headers, ?string $body, ?string $auth): array
    {
        $this->calls[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'auth' => $auth,
        ];

        if ([] !== $this->queue) {
            return array_shift($this->queue);
        }

        return [$this->body, $this->status];
    }
}
