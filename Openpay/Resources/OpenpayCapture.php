<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiResourceBase;

class OpenpayCapture extends OpenpayApiResourceBase
{
    protected function getResourceUrlName($p = true)
    {
        return parent::getResourceUrlName(false);
    }
}
