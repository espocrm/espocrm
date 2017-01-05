<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class AddMonthsType extends AddIntervalType
{
    protected $intervalTypeString = 'months';
}