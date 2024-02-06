<?php
namespace Espo\Custom\SelectFilters;

use \Datetime;
use \DateTimeZone;
use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Order;

class ContactBirthday implements Filter
{
    public function apply(SelectBuilder $selectBuilder): void
    {
        $today = new DateTime("now", new DateTimeZone('Europe/Kiev'));
        $todayOfAnyYear = '%-' . $today->format('m-d');

        $selectBuilder
            ->where([
                'birthday*' => $todayOfAnyYear
            ]);
    }
}