<?php

declare(strict_types=1);

namespace Openpay\Data;

use Openpay\Data\Openpay as Openpay;

class OpenpayApiConnector
{

    private static $instance;

    private static ?OpenpayHttpTransport $transport = null;

    private $apiKey;

    private function __construct()
    {
        $this->apiKey = '';
    }

    private static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Drops the process-wide connector singleton and custom transport.
     * Called from {@see Openpay::reset()}.
     */
    public static function reset(): void
    {
        self::$instance  = null;
        self::$transport = null;
    }

    public static function setTransport(?OpenpayHttpTransport $transport): void
    {
        self::$transport = $transport;
    }

    // ---------------------------------------------------------
    // ------------------  PRIVATE FUNCTIONS  ------------------

    private function _request($method, $url, $params)
    {
        if (!class_exists('Openpay\\Data\\Openpay')) {
            throw new OpenpayApiError("Library install error, there are some missing classes");
        }
        OpenpayApiConsole::trace('OpenpayApiConnector @_request');

        $myId = Openpay::getId();
        if (!$myId) {
            throw new OpenpayApiAuthError("Empty or no Merchant ID provided");
        } else if (!preg_match('/^[a-z0-9]{20}$/i', $myId)) {
            throw new OpenpayApiAuthError("Invalid Merchant ID '" . $myId . "'");
        }

        $myApiKey = Openpay::getApiKey();
        if (!$myApiKey) {
            throw new OpenpayApiAuthError("Empty or no Private Key provided");
        } else if (!preg_match('/^sk_[a-z0-9]{32}$/i', $myApiKey)) {
            throw new OpenpayApiAuthError("Invalid Private Key '" . $myApiKey . "'");
        }

        $publicIp = Openpay::getPublicIp();
        if(is_null($publicIp)){
            throw new OpenpayApiAuthError("Empty or no public ip provided");
        } else if (!filter_var($publicIp, FILTER_VALIDATE_IP)){
            throw new OpenpayApiAuthError("Invalid public ip '" . $publicIp . "'");
        }

        $absUrl = Openpay::getEndpointUrl();
        if (!$absUrl) {
            throw new OpenpayApiConnectionError("No API endpoint set");
        }
        $absUrl .= '/' . $myId . $url;

        //$params = self::_encodeObjects($params);

        $userAgent = Openpay::getUserAgent();

        if (empty($userAgent)) {
            $headers = ['User-Agent: OpenpayPhp/' . Openpay::VERSION];
        } else {
            $headers = ['User-Agent: ' . $userAgent];
        }

        array_push($headers, 'X-Forwarded-For: ' . $publicIp);

        list($rbody, $rcode) = $this->dispatch($method, $absUrl, $headers, $params, $myApiKey);
        return $this->interpretResponse($rbody, $rcode);
    }

    private function transport(): OpenpayHttpTransport
    {
        return self::$transport ?? new CurlHttpTransport();
    }

    private function dispatch($method, $absUrl, $headers, $params, $auth = null)
    {
        $body = null;

        if ($method === 'get') {
            if (count($params) > 0) {
                $absUrl .= '?' . $this->encodeToQueryString($params);
            }
        } elseif ($method === 'post' || $method === 'put') {
            $body     = $this->encodeToJson($params);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($body);
        } elseif ($method === 'delete') {
            if (count($params) > 0) {
                $absUrl .= '?' . $this->encodeToQueryString($params);
            }
        } else {
            throw new OpenpayApiError("Invalid request method '" . $method . "'");
        }

        return $this->transport()->send($method, $absUrl, $headers, $body, $auth);
    }

    private function encodeToQueryString($arr, $prefix = null)
    {
        if (!is_array($arr))
            return $arr;

        $r = array();
        foreach ($arr as $k => $v) {
            if (is_null($v))
                continue;

            if ($prefix && $k && !is_int($k))
                $k = $prefix . "[" . $k . "]";
            else if ($prefix)
                $k = $prefix . "[]";

            if (is_array($v)) {
                $r[] = $this->encodeToQueryString($v, $k);
            } else {
                $r[] = urlencode((string) $k) . "=" . urlencode((string) $v);
            }
        }
        $string = implode("&", $r);
        return $string;
    }

    private function encodeToJson($arr)
    {
        try {
            return json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new OpenpayApiError('Failed to encode request as JSON: ' . $e->getMessage());
        }
    }

    private function interpretResponse($responseBody, $responseCode)
    {
        OpenpayApiConsole::trace('OpenpayApiConnector @interpretResponse');
        try {
            if (!empty($responseBody)) {
                $traslatedResponse = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            } else {
                $traslatedResponse = array();
            }
        } catch (\JsonException $e) {
            throw new OpenpayApiRequestError('Invalid response: ' . $responseBody, $responseCode);
        }

        if ($responseCode < 200 || $responseCode >= 300) {
            OpenpayApiConsole::error('Request finished with HTTP code ' . $responseCode);
            $this->handleRequestError($responseBody, $responseCode, $traslatedResponse);
            return array();
        }
        return $traslatedResponse;
    }

    private function handleRequestError($responseBody, $responseCode, $traslatedResponse)
    {
        if (!is_array($traslatedResponse) || !isset($traslatedResponse['error_code'])) {
            throw new OpenpayApiRequestError("Invalid response body received from Openpay API Server");
        }

        $message = isset($traslatedResponse['description']) ? $traslatedResponse['description'] : 'No description';
        $error = $traslatedResponse['error_code'];
        $category = isset($traslatedResponse['category']) ? $traslatedResponse['category'] : null;
        $request_id = isset($traslatedResponse['request_id']) ? $traslatedResponse['request_id'] : null;
        $fraud_rules = isset($traslatedResponse['fraud_rules']) ? $traslatedResponse['fraud_rules'] : null;

        switch ($responseCode) {

            // Unauthorized - Forbidden
            case 401:
            case 403:
                throw new OpenpayApiAuthError($message, $error, $category, $request_id, $responseCode, $fraud_rules);
                break;

            // Bad Request - Request Entity too large - Request Entity too large - Internal Server Error - Service Unavailable
            case 400:
            case 404:
            case 413:
            case 422:
            case 500:
            case 503:
                throw new OpenpayApiRequestError($message, $error, $category, $request_id, $responseCode, $fraud_rules);
                break;

            // Payment Required - Conflict - Preconditon Failed - Unprocessable Entity - Locked
            case 402:
            case 409:
            case 412:
            case 423:
                throw new OpenpayApiTransactionError($message, $error, $category, $request_id, $responseCode, $fraud_rules);
                break;

            // Not Found
            default:
                throw new OpenpayApiError($message, $error, $category, $request_id, $responseCode, $fraud_rules);
        }
    }

    // ---------------------------------------------------------
    // ------------------  PUBLIC FUNCTIONS  -------------------

    public static function request($method, $url, $params = null)
    {
        OpenpayApiConsole::trace('OpenpayApiConnector @request ' . $url);

        if (!$params) {
            $params = array();
        }

        $method = strtolower($method);
        if (!in_array($method, array('get', 'post', 'delete', 'put'))) {
            throw new OpenpayApiError("Invalid request method '" . $method . "'");
        }

        $connector = self::getInstance();
        return $connector->_request($method, $url, $params);
    }

}
