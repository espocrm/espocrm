<?php

namespace Espo\Core\Formula\Functions\NumericGroup;

use \Espo\Core\Exceptions\Error;

class SummationType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return '';
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'Summation\' item is not array.');
        }

        $result = 0;
        foreach ($item->value as $subItem) {
            $part = $this->evaluate($subItem);

            if (!is_float($part) && !is_int($part)) {
                $part = intval($part);
            }

            $result += $part;
        }

        return $result;
    }
}