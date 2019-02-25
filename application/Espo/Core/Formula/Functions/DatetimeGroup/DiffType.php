<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use \Espo\Core\Exceptions\Error;

class DiffType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
    }

    protected $intevalTypePropertyMap = array(
        'years' => 'y', 'months' => 'm', 'days' => 'd', 'hours' => 'h', 'minutes' => 'i', 'seconds' => 's'
    );

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

        $dateTime1String = $this->evaluate($item->value[0]);
        $dateTime2String = $this->evaluate($item->value[1]);

        if (!$dateTime1String) {
            return null;
        }

        if (!$dateTime2String) {
            return null;
        }

        if (!is_string($dateTime1String)) {
            throw new Error();
        }

        if (!is_string($dateTime2String)) {
            throw new Error();
        }

        $intervalType = 'days';
        if (count($item->value) > 2) {
            $intervalType = $this->evaluate($item->value[2]);
        }

        if (!is_string($intervalType)) {
            throw new Error();
        }

        if (!array_key_exists($intervalType, $this->intevalTypePropertyMap)) {
            throw new Error('Not supported interval type' . $intervalType);
        }


        $isTime = false;
        if (strlen($dateTime1String) > 10) {
            $isTime = true;
        }

        try {
            $dateTime1 = new \DateTime($dateTime1String);
            $dateTime2 = new \DateTime($dateTime2String);
        } catch (\Exception $e) {
            return null;
        }

        $t1 = $dateTime1->getTimestamp();
        $t2 = $dateTime2->getTimestamp();

        $secondsDiff = $t1 - $t2;

        if ($intervalType === 'seconds') {
            $number = $secondsDiff;
        } else if ($intervalType === 'minutes') {
            $number = floor($secondsDiff / 60);
        } else if ($intervalType === 'hours') {
            $number = floor($secondsDiff / (60 * 60));
        } else if ($intervalType === 'days') {
            $number = floor($secondsDiff / (60 * 60 * 24));
        } else {
            $property = $this->intevalTypePropertyMap[$intervalType];
            $interval = $dateTime2->diff($dateTime1);
            $number = $interval->$property;
            if ($interval->invert) {
                $number *= -1;
            }

            if ($intervalType === 'months') {
                $number += $interval->y * 12;
            }
        }

        return $number;
    }
}