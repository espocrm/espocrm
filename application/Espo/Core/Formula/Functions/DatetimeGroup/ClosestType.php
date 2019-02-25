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

namespace Espo\Core\Formula\Functions\DateTimeGroup;

use \Espo\Core\Exceptions\Error;

class ClosestType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('dateTime');
        $this->addDependency('config');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            return true;
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 3) {
             throw new Error();
        }

        $value = $this->evaluate($item->value[0]);
        $type = $this->evaluate($item->value[1]);
        $target = $this->evaluate($item->value[2]);

        if (!in_array($type, ['time', 'minute', 'hour', 'date', 'dayOfWeek', 'month'])) {
            throw new Error('Bad TYPE passed to datetime\\closest function.');
        }

        $inPast = false;
        if (count($item->value) > 3) {
            $inPast = $this->evaluate($item->value[3]);
        }

        $timezone = null;
        if (count($item->value) > 4) {
            $timezone = $this->evaluate($item->value[4]);
        }

        if (!$value) {
            return null;
        }

        if (!is_string($value)) {
            throw new Error('Bad VALUE passed to datetime\\closest function.');
        }

        if (!$timezone) {
            $timezone = $this->getInjection('config')->get('timeZone');
        }

        $isDate = false;
        if (strlen($value) === 10) {
            $isDate = true;
            $value .= ' 00:00:00';
        }

        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $format = 'Y-m-d H:i:s';

        $dt = \DateTime::createFromFormat($format, $value, new \DateTimeZone($timezone));
        $valueTimestamp = $dt->getTimestamp();

        if ($type === 'time') {
            if (!is_string($target)) {
                throw new Error('Bad TARGET passed to datetime\\closest function.');
            }
            list($hour, $minute) = explode(':', $target);
            if (!$hour) {
                $hour = 0;
            }
            if (!$minute) {
                $minute = 0;
            }
            $dt->setTime($hour, $minute, 0);
            if ($valueTimestamp < $dt->getTimestamp()) {
                if ($inPast) {
                    $dt->modify('-1 day');
                }
            } else if ($valueTimestamp > $dt->getTimestamp()) {
                if (!$inPast) {
                    $dt->modify('+1 day');
                }
            }
        } else if ($type === 'hour') {
            $target = intval($target);
            $dt->setTime($target, 0, 0);
            if ($valueTimestamp < $dt->getTimestamp()) {
                if ($inPast) {
                    $dt->modify('-1 day');
                }
            } else if ($valueTimestamp > $dt->getTimestamp()) {
                if (!$inPast) {
                    $dt->modify('+1 day');
                }
            }
        } else if ($type === 'minute') {
            $target = intval($target);
            $dt->setTime(intval($dt->format('G')), intval($target), 0);

            if ($valueTimestamp < $dt->getTimestamp()) {
                if ($inPast) {
                    $dt->modify('-1 hour');
                }
            } else if ($valueTimestamp > $dt->getTimestamp()) {
                if (!$inPast) {
                    $dt->modify('+1 hour');
                }
            }
        } else if ($type === 'dayOfWeek') {
            $target = intval($target);
            $dt->setTime(0, 0, 0);

            $dayOfWeek = $dt->format('w');
            $dt->modify('-' . $dayOfWeek . ' days');
            $dt->modify('+' . $target . ' days');

            if ($valueTimestamp < $dt->getTimestamp()) {
                if ($inPast) {
                    $dt->modify('-1 week');
                }
            } else if ($valueTimestamp > $dt->getTimestamp()) {
                if (!$inPast) {
                    $dt->modify('+1 week');
                }
            }
        } else if ($type === 'date') {
            $target = intval($target);
            $dt->setTime(0, 0, 0);

            if ($inPast) {
                while (true) {
                    $date = intval($dt->format('d'));
                    if ($date === $target) {
                        break;
                    }
                    $dt->modify('-1 day');
                }
            } else {
                if ($valueTimestamp > $dt->getTimestamp()) {
                    $dt->modify('+1 day');
                }
                while (true) {
                    $date = intval($dt->format('d'));
                    if ($date === $target) {
                        break;
                    }
                    $dt->modify('+1 day');
                }
            }
        } else if ($type === 'month') {
            $target = intval($target);

            $dt->setTime(0, 0, 0);
            $days = intval($dt->format('d')) - 1;
            $dt->modify('-' . $days . ' days');

            if ($inPast) {
                while (true) {
                    $month = intval($dt->format('m'));
                    if ($month === $target) {
                        break;
                    }
                    $dt->modify('-1 month');
                }
            } else {
                if ($valueTimestamp > $dt->getTimestamp()) {
                    $dt->modify('+1 month');
                }
                while (true) {
                    $month = intval($dt->format('m'));
                    if ($month === $target) {
                        break;
                    }
                    $dt->modify('+1 month');
                }
            }
        }

        if ($isDate && in_array($type, ['time', 'minute', 'hour'])) {
            $isDate = false;
        }

        if (!$isDate) {
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i');
        } else {
            return $dt->format('Y-m-d');
        }
    }
}