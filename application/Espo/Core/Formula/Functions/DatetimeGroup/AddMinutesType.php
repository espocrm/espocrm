<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class AddMinutesType extends AddIntervalType
{
    protected $intervalTypeString = 'minutes';

    protected $timeOnly = true;
}