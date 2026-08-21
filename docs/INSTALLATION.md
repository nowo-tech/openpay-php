# Installation

Install the Nowo fork of the Openpay PHP SDK with Composer:

```bash
composer require nowo-tech/openpay-php:^3.2
```

The package `replace`s `openpay/sdk` **3.1.1**. Composer will not install the
official SDK alongside it.

## Autoload

```php
require_once 'vendor/autoload.php';

use Openpay\Data\Openpay;
```

For a copy-paste install without Composer, require the **repository-root**
`Openpay.php` (not `Openpay/Openpay.php` — that path does not exist).

## Requirements

- PHP 8.3 or later
- ext-curl, ext-json, ext-mbstring, ext-hash

## Development (this repository)

```bash
make up
make test
```

Docker Compose starts a PHP 8.3 CLI container with pcov for coverage.
