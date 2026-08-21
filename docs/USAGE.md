# Usage

See the root [README](../README.md) cookbook for charges, customers, cards,
plans, and subscriptions (same `Openpay\\` API as `openpay/sdk` 3.1.1).

## Request isolation (php-fpm / FrankenPHP)

```php
use Openpay\Data\OpenpaySession;

$session = new OpenpaySession($merchantId, $privateKey, 'MX', $publicIp);
$result  = $session->run(function ($openpay) use ($payload) {
    return $openpay->charges->add($payload);
});
```

## Tests: fake HTTP

Implement `OpenpayHttpTransport` and call `Openpay::setHttpTransport()`.
A fake is provided in `tests/Unit/FakeOpenpayHttpTransport.php`.
