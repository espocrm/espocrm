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

namespace Espo\Entities;

use Espo\Core\Field\Date;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\ORM\Entity;
use Espo\Tools\WorkingTime\Calendar\Time;
use Espo\Tools\WorkingTime\Calendar\TimeRange;
use RuntimeException;

class WorkingTimeRange extends Entity
{
    public const ENTITY_TYPE = 'WorkingTimeRange';

    public const TYPE_NON_WORKING = 'Non-working';
    public const TYPE_WORKING = 'Working';

    /**
     * @return (self::TYPE_NON_WORKING|self::TYPE_WORKING)
     */
    public function getType(): string
    {
        $type = $this->get('type');

        if (!$type) {
            throw new RuntimeException();
        }

        return $type;
    }

    public function getDateStart(): Date
    {
        /** @var ?Date $value */
        $value = $this->getValueObject('dateStart');

        if (!$value) {
            throw new RuntimeException();
        }

        return $value;
    }

    public function getDateEnd(): Date
    {
        /** @var ?Date $value */
        $value = $this->getValueObject('dateEnd');

        if (!$value) {
            throw new RuntimeException();
        }

        return $value;
    }

    /**
     * @return ?TimeRange[]
     */
    public function getTimeRanges(): ?array
    {
        $ranges = self::convertRanges($this->get('timeRanges') ?? []);

        if ($ranges === []) {
            return null;
        }

        return $ranges;
    }

    /**
     * @param array{string, string}[] $ranges
     * @return TimeRange[]
     */
    private static function convertRanges(array $ranges): array
    {
        $list = [];

        foreach ($ranges as $range) {
            $list[] = new TimeRange(
                self::convertTime($range[0]),
                self::convertTime($range[1])
            );
        }

        return $list;
    }

    private static function convertTime(string $time): Time
    {
        /** @var int<0, 23> $h */
        $h = (int) explode(':', $time)[0];
        /** @var int<0, 59> $m */
        $m = (int) explode(':', $time)[1];

        return new Time($h, $m);
    }

    public function getUsers(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('users');
    }
}
