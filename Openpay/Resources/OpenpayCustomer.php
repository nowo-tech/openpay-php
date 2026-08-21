<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiResourceBase;

class OpenpayCustomer extends OpenpayApiResourceBase
{
    protected $status;
    protected $creation_date;
    protected $balance;
    protected $clabe;
    protected $derivedResources = [
        'Card' => [],
        'BankAccount' => [],
        'Charge' => [],
        'Pse' => [],
        'Transfer' => [],
        'Payout' => [],
        'Subscription' => []];

    public function save()
    {
        return $this->_update();
    }

    public function delete(): void
    {
        $this->_delete();
    }
}
