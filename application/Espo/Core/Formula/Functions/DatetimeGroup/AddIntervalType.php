<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

abstract class AddIntervalType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    protected $timeOnly = false;

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

        $dateTimeString = $this->evaluate($item->value[0]);

        if (!$dateTimeString) {
            return null;
        }

        if (!is_string($dateTimeString)) {
            throw new Error();
        }

        $interval = $this->evaluate($item->value[1]);

        if (!is_numeric($interval)) {
            throw new Error();
        }

        $isTime = false;
        if (strlen($dateTimeString) > 10) {
            $isTime = true;
        }

        if ($this->timeOnly && !$isTime) {
            $dateTimeString .= ' 00:00:00';
            $isTime = true;
        }

        try {
            $dateTime = new \DateTime($dateTimeString);
        } catch (\Exception $e) {
            return null;
        }

        $dateTime->modify(($interval > 0 ? '+' : '') . strval($interval) . ' ' . $this->intervalTypeString);

        if ($isTime) {
            return $dateTime->format($this->getInjection('dateTime')->getInternalDateTimeFormat());
        } else {
            return $dateTime->format($this->getInjection('dateTime')->getInternalDateFormat());
        }
    }
}