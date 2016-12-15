<?php

namespace Espo\Core\Formula\Functions\LogicalGroup;

use \Espo\Core\Exceptions\Error;

class OrType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (is_null($item->value)) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error('Value for \'Or\' item is not array.');
        }

        $result = false;
        foreach ($item->value as $subItem) {
            $result = $result || $this->evaluate($subItem);
            if ($result) break;
        }

        return $result;
    }
}