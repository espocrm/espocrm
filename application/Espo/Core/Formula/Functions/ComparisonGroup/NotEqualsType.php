<?php

namespace Espo\Core\Formula\Functions\ComparisonGroup;

use \Espo\Core\Exceptions\Error;

class NotEqualsType extends EqualsType
{
    protected function compare($left, $right)
    {
        return !parent::compare($left, $right);
    }
}