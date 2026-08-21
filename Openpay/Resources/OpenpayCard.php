<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiResourceBase;

class OpenpayCard extends OpenpayApiResourceBase
{
    protected $type;
    protected $brand;
    protected $allows_charges;
    protected $allows_payouts;
    protected $creation_date;
    protected $bank_name;
    protected $bank_code;
    protected $customer_id;

    public function delete(): void
    {
        $this->_delete();
    }

    public function get($param)
    {
        return $this->_getAttributes($param);
    }
}

// ----------------------------------------------------------------------------
