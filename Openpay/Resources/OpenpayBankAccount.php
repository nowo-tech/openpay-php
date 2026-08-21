<?php

declare(strict_types=1);

namespace Openpay\Resources;

use Openpay\Data\OpenpayApiResourceBase;

class  OpenpayBankAccount extends OpenpayApiResourceBase
{
    protected $bank_code;
    protected $bank_name;
    protected $creation_date;

    public function delete()
    {
        $this->_delete();
    }
}
