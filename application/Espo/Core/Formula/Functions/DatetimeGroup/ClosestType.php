<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula\Functions\DatetimeGroup;

use Espo\Core\Di;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

use DateTime;
use DateTimeZone;

class ClosestType extends BaseFunction implements Di\ConfigAware
{
    use Di\ConfigSetter;

    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 3) {
            $this->throwTooFewArguments();
        }

        $value = $args[0];
        $type = $args[1];
        $target = $args[2];

        if (!in_array($type, ['time', 'minute', 'hour', 'date', 'dayOfWeek', 'month'])) {
            $this->throwBadArgumentType(1);
        }

        $inPast = false;

        if (count($args) > 3) {
            $inPast = $args[3];
        }

        $timezone = null;

        if (count($args) > 4) {
            $timezone = $args[4];
        }

        if (!$value) {
            return null;
        }

        if (!is_string($value)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$timezone) {
            $timezone = $this->config->get('timeZone');
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

        /** @var DateTime $dt */
        $dt = DateTime::createFromFormat($format, $value, new DateTimeZone($timezone));

        $valueTimestamp = $dt->getTimestamp();

        if ($type === 'time') {
            if (!is_string($target)) {
                $this->throwBadArgumentType(3, 'string');
            }

            list($hour, $minute) = explode(':', $target);

            if (!$hour) {
                $hour = 0;
            }

            if (!$minute) {
                $minute = 0;
            }

            $dt->setTime((int) $hour, (int) $minute, 0);

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
            $dt->setTimezone(new DateTimeZone('UTC'));

            return $dt->format('Y-m-d H:i');
        }

        return $dt->format('Y-m-d');
    }
}
