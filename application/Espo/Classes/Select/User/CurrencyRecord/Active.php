<?php

namespace Espo\Classes\Select\User\CurrencyRecord;

use Espo\Core\Select\Primary\Filter;
use Espo\Entities\CurrencyRecord;
use Espo\ORM\Query\SelectBuilder;

class Active implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where([
            CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_ACTIVE,
        ]);
    }
}
