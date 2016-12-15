<?php

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class NowType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    public function process(\StdClass $item)
    {
        return $this->getInjection('dateTime')->getInternalNowString();
    }
}