<?php

namespace Espo\Core\Formula\Functions\StringGroup;

use \Espo\Core\Exceptions\Error;

class ConcatenationType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return '';
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'Concatenation\' item is not array.');
        }

        $result = '';

        foreach ($item->value as $subItem) {
            $part = $this->evaluate($subItem);

            if (!is_string($part)) {
                $part = strval($part);
            }

            $result .= $part;
        }

        return $result;
    }
}