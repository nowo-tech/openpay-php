<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiDerivedResource;

class OpenpayFeeList extends OpenpayApiDerivedResource
{
    public function create($params)
    {
        return $this->add($params);
    }
}
