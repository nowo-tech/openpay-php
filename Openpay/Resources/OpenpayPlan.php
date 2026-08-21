<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiResourceBase;

class OpenpayPlan extends OpenpayApiResourceBase
{
    protected $creation_date;
    protected $currency;
    protected $amount;
    protected $repeat_every;
    protected $repeat_unit;
    protected $retry_times;
    protected $status;
    protected $status_after_retry;

    protected $derivedResources = ['Subscription' => []];

    public function save()
    {
        return $this->_update();
    }

    public function delete(): void
    {
        $this->_delete();
    }
}
