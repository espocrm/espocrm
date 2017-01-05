<?php

namespace Espo\Core\Formula\Functions\NumericGroup;

use \Espo\Core\Exceptions\Error;

class DivisionType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return '';
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'Division\' item is not array.');
        }

        if (count($item->value) < 2) {
            throw new Error('Bad value for \'Division\'.');
        }

        $result = $this->evaluate($item->value[0]);
        $part = $this->evaluate($item->value[1]);
        if (!is_float($part) && !is_int($part)) {
            $part = intval($part);
        }
        $result /= $part;

        return $result;
    }
}