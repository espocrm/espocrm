<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class AddHoursType extends AddIntervalType
{
    protected $intervalTypeString = 'hours';

    protected $timeOnly = true;
}