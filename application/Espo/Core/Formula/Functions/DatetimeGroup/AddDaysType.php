<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class AddDaysType extends AddIntervalType
{
    protected $intervalTypeString = 'days';
}