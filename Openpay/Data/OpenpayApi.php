<?php

declare(strict_types=1);

namespace Openpay\Data;

use Override;

class OpenpayApi extends OpenpayApiResourceBase
{
    protected $derivedResources = [
        'Bine'     => [],
        'Customer' => [],
        'Card'     => [],
        'Charge'   => [],
        'Pse'      => [],
        'Payout'   => [],
        'Fee'      => [],
        'Plan'     => [],
        'Webhook'  => [],
        'Token'    => []];

    #[Override]
    protected static function getInstance($r, $p = null)
    {
        $resourceName = self::class;

        return parent::getInstance($resourceName);
    }

    /**
     * Public entry used by {@see Openpay::getInstance()} (credentials already configured).
     */
    public static function createRoot(): self
    {
        return self::getInstance(null);
    }

    #[Override]
    protected function getMerchantInfo()
    {
        return parent::getMerchantInfo();
    }

    #[Override]
    protected function getResourceUrlName($p = true): string
    {
        return '';
    }

    public function getFullURL()
    {
        return $this->getUrl();
    }
}
