<?php

namespace Espo\Core\Formula\Functions\StringGroup;

use \Espo\Core\Exceptions\Error;

class SubstringType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 2) {
            throw new Error();
        }

        $string = $this->evaluate($item->value[0]);
        $start = $this->evaluate($item->value[1]);

        if (count($item->value) > 2) {
            $length = $this->evaluate($item->value[2]);
            return substr($string, $start, $length);
        } else {
            return substr($string, $start);
        }
    }
}