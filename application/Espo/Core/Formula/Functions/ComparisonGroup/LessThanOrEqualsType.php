<?php

namespace Espo\Core\Formula\Functions\ComparisonGroup;

use \Espo\Core\Exceptions\Error;

class LessThanOrEqualsType extends Base
{
    protected function compare($left, $right)
    {
        return $left <= $right;
    }
}