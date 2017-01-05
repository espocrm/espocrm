<?php

namespace Espo\Core\Formula\Functions\ComparisonGroup;

use \Espo\Core\Exceptions\Error;

class EqualsType extends Base
{
    protected function compare($left, $right)
    {
        if (is_array($left) && is_array($right)) {
            $result = true;
            foreach ($left as $i => $value) {
                if (!array_key_exists($i, $right)) {
                    $result = false;
                    break;
                }
                if ($value !== $right[$i]) {
                    $result = false;
                    break;
                }
            }
            return $result;
        }

        return $left === $right;
    }
}