<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class AddWeeksType extends AddIntervalType
{
    protected $intervalTypeString = 'weeks';
}