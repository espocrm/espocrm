<?php

namespace Espo\Core\Formula\Functions\ComparisonGroup;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

abstract class Base extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error('Value is not array.');
        }

        if (count($item->value) < 2) {
             throw new Error('Bad value.');
        }

        if (is_object($item->value[0])) {
            $left = $this->evaluate($item->value[0]);
        } else {
            $left = $item->value[0];
        }
        if (is_object($item->value[1])) {
            $right = $this->evaluate($item->value[1]);
        } else {
            $right = $item->value[1];
        }

        return $this->compare($left, $right);
    }
}