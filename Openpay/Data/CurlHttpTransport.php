<?php

declare(strict_types=1);

namespace Openpay\Data;

final class CurlHttpTransport implements OpenpayHttpTransport
{
    public function __construct(
        private readonly int $connectTimeout = 30,
        private readonly int $timeout = 80,
    ) {
    }

    public function send(string $method, string $url, array $headers, ?string $body, ?string $auth): array
    {
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if ($method === 'get') {
            $opts[CURLOPT_HTTPGET] = 1;
        } elseif ($method === 'post') {
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        } elseif ($method === 'put') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $opts[CURLOPT_POSTFIELDS]    = $body ?? '';
        } elseif ($method === 'delete') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } else {
            throw new OpenpayApiError("Invalid request method '" . $method . "'");
        }

        if ($auth) {
            $opts[CURLOPT_USERPWD] = $auth . ':';
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);

        OpenpayApiConsole::debug('Executing HTTP: ' . strtoupper($method) . ' > ' . $url);

        $rbody = curl_exec($curl);

        if ($rbody === false) {
            OpenpayApiConsole::error('cURL request error: ' . curl_errno($curl));
            $message   = curl_error($curl);
            $errorCode = curl_errno($curl);
            $this->handleCurlError($errorCode, $message);
        }

        $rcode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (is_string($rbody) && mb_detect_encoding($rbody, 'UTF-8', true) !== 'UTF-8') {
            OpenpayApiConsole::warn('Response body is not an UTF-8 string');
        }

        OpenpayApiConsole::debug('HTTP status: ' . $rcode);

        return [(string) $rbody, $rcode];
    }

    private function handleCurlError(int $errorCode, string $message): never
    {
        switch ($errorCode) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = 'Could not connect to Openpay.  Please check your internet connection and try again';
                break;
            default:
                $msg = 'Unexpected error connecting to Openpay';
        }

        $msg .= ' (Network error ' . $errorCode . ')';
        throw new OpenpayApiConnectionError($msg);
    }
}
