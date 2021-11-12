<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Where;

use Espo\Core\Exceptions\Error;
use Espo\Entities\User;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Config;

use DateTime;
use DateTimeZone;
use DateInterval;

/**
 * Transforms date-time where item. Applies timezone.
 */
class DateTimeItemTransformer
{
    /**
     * @var User
     */
    protected $user;

    private $config;

    public function __construct(User $user, Config $config)
    {
        $this->user = $user;
        $this->config = $config;
    }

    public function transform(Item $item): Item
    {
        $format = DateTimeUtil::SYSTEM_DATE_TIME_FORMAT;

        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();
        $isDateTime = $item->isDateTime();
        $timeZone =  $item->getTimeZone() ?? 'UTC';

        if (!$isDateTime) {
            throw new Error("Bad where item.");
        }

        if (!$attribute) {
            throw new Error("Bad datetime where item. Empty 'attribute'.");
        }

        if (!$type) {
            throw new Error("Bad datetime where item. Empty 'type'.");
        }

        if (empty($value) && in_array($type, ['on', 'before', 'after'])) {
            throw new Error("Bad where item. Empty value.");
        }

        $where = [
            'attribute' => $attribute,
        ];

        $dt = new DateTime('now', new DateTimeZone($timeZone));

        switch ($type) {
            case 'today':

                $where['type'] = 'between';

                $dt->setTime(0, 0, 0);

                $dtTo = clone $dt;
                $dtTo->modify('+1 day -1 second');
                $dt->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $from = $dt->format($format);
                $to = $dtTo->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'past':

                $where['type'] = 'before';

                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'future':

                $where['type'] = 'after';

                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'lastSevenDays':

                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));
                $to = $dt->format($format);

                $dtFrom->modify('-7 day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'lastXDays':

                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));

                $to = $dt->format($format);

                $number = strval(intval($value));

                $dtFrom->modify('-'.$number.' day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'nextXDays':
                $where['type'] = 'between';

                $dtTo = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));

                $from = $dt->format($format);

                $number = strval(intval($value));

                $dtTo->modify('+'.$number.' day');
                $dtTo->setTime(24, 59, 59);
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $to = $dtTo->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'olderThanXDays':

                $where['type'] = 'before';

                $number = strval(intval($value));

                $dt->modify('-'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'afterXDays':

                $where['type'] = 'after';

                $number = strval(intval($value));

                $dt->modify('+'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'on':
                $where['type'] = 'between';

                $dt = new DateTime($value, new DateTimeZone($timeZone));
                $dtTo = clone $dt;

                if (strlen($value) <= 10) {
                    $dtTo->modify('+1 day -1 second');
                }

                $dt->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $from = $dt->format($format);
                $to = $dtTo->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'before':
                $where['type'] = 'before';

                $dt = new DateTime($value, new DateTimeZone($timeZone));
                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'after':
                $where['type'] = 'after';

                $dt = new DateTime($value, new DateTimeZone($timeZone));

                if (strlen($value) <= 10) {
                    $dt->modify('+1 day -1 second');
                }

                $dt->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = $dt->format($format);

                break;

            case 'between':

                $where['type'] = 'between';

                if (!is_array($value) || count($value) < 2) {
                    throw new Error("Bad where item. Bad value.");
                }

                $dt = new DateTime($value[0], new DateTimeZone($timeZone));
                $dt->setTimezone(new DateTimeZone('UTC'));

                $from = $dt->format($format);

                $dt = new DateTime($value[1], new DateTimeZone($timeZone));
                $dt->setTimezone(new DateTimeZone('UTC'));

                if (strlen($value[1]) <= 10) {
                    $dt->modify('+1 day -1 second');
                }

                $to = $dt->format($format);

                $where['value'] = [$from, $to];

                break;

            case 'currentMonth':
            case 'lastMonth':
            case 'nextMonth':

                $where['type'] = 'between';

                $dtFrom = new DateTime('now', new DateTimeZone($timeZone));
                $dtFrom = $dt->modify('first day of this month')->setTime(0, 0, 0);

                if ($type == 'lastMonth') {
                    $dtFrom->modify('-1 month');
                } else if ($type == 'nextMonth') {
                    $dtFrom->modify('+1 month');
                }

                $dtTo = clone $dtFrom;
                $dtTo->modify('+1 month');

                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [$dtFrom->format($format), $dtTo->format($format)];

                break;

            case 'currentQuarter':
            case 'lastQuarter':

                $where['type'] = 'between';

                $dt = new DateTime('now', new DateTimeZone($timeZone));
                $quarter = ceil($dt->format('m') / 3);

                $dtFrom = clone $dt;
                $dtFrom->modify('first day of January this year')->setTime(0, 0, 0);

                if ($type === 'lastQuarter') {
                    $quarter--;

                    if ($quarter == 0) {
                        $quarter = 4;
                        $dtFrom->modify('-1 year');
                    }
                }

                $dtFrom->add(new DateInterval('P'.(($quarter - 1) * 3).'M'));
                $dtTo = clone $dtFrom;
                $dtTo->add(new DateInterval('P3M'));
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format),
                ];

                break;

            case 'currentYear':
            case 'lastYear':

                $where['type'] = 'between';
                $dtFrom = new DateTime('now', new DateTimeZone($timeZone));
                $dtFrom->modify('first day of January this year')->setTime(0, 0, 0);

                if ($type == 'lastYear') {
                    $dtFrom->modify('-1 year');
                }

                $dtTo = clone $dtFrom;
                $dtTo = $dtTo->modify('+1 year');
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format),
                ];

                break;

            case 'currentFiscalYear':
            case 'lastFiscalYear':

                $where['type'] = 'between';
                $dtToday = new DateTime('now', new DateTimeZone($timeZone));
                $dt = clone $dtToday;
                $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

                $dt
                    ->modify('first day of January this year')
                    ->modify('+' . $fiscalYearShift . ' months')
                    ->setTime(0, 0, 0);

                if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
                    $dt->modify('-1 year');
                }

                if ($type === 'lastFiscalYear') {
                    $dt->modify('-1 year');
                }

                $dtFrom = clone $dt;
                $dtTo = clone $dt;
                $dtTo = $dtTo->modify('+1 year');

                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format),
                ];

                break;

            case 'currentFiscalQuarter':
            case 'lastFiscalQuarter':

                $where['type'] = 'between';

                $dtToday = new DateTime('now', new DateTimeZone($timeZone));

                $dt = clone $dtToday;

                $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

                $dt
                    ->modify('first day of January this year')
                    ->modify('+' . $fiscalYearShift . ' months')
                    ->setTime(0, 0, 0);

                $month = intval($dtToday->format('m'));

                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);

                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new DateInterval('P' . ($quarterShift * 3) . 'M'));
                    } else {
                        $quarterShift *= -1;
                        $dt->sub(new DateInterval('P' . ($quarterShift * 3) . 'M'));
                    }
                }

                if ($type === 'lastFiscalQuarter') {
                    $dt->modify('-3 months');
                }

                $dtFrom = clone $dt;
                $dtTo = clone $dt;
                $dtTo = $dtTo->modify('+3 months');

                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format),
                ];

                break;

            default:

                $where['type'] = $type;
        }

        return Item::fromRaw($where);
    }
}
