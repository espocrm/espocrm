<?php

namespace Espo\Core\Formula\Functions\DateTimeGroup;

use \Espo\Core\Exceptions\Error;

class FormatType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
             throw new Error();
        }

        $timezone = null;
        if (count($item->value) > 1) {
            $timezone = $this->evaluate($item->value[1]);
        }
        $value = $this->evaluate($item->value[0]);

        if (strlen($value) > 11) {
            return $this->getInjection('dateTime')->convertSystemDateTime($value, $timezone);
        } else {
            return $this->getInjection('dateTime')->convertSystemDate($value);
        }
    }
}