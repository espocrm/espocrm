<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Classes\FieldValidators\WorkingTimeCalendar\TimeRanges;

use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\ORM\Entity;

/**
 * List of time ranges, e.g. `[["09:00", "13:00"], ["14:00", "00:00"]]`.
 * The end `00:00` stands for midnight of the next day. Ranges must be ordered and not overlap.
 *
 * @implements Validator<Entity>
 */
class Valid implements Validator
{
    private const int MINUTES_IN_DAY = 1440;

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        $value = $entity->get($field);

        if ($value === null) {
            return null;
        }

        if (!is_array($value) || !array_is_list($value)) {
            return Failure::create();
        }

        $prevEnd = null;

        foreach ($value as $item) {
            if (!is_array($item) || !array_is_list($item) || count($item) !== 2) {
                return Failure::create();
            }

            $start = $this->toMinutes($item[0]);
            $end = $this->toMinutes($item[1]);

            if ($start === null || $end === null) {
                return Failure::create();
            }

            if ($end === 0) {
                $end = self::MINUTES_IN_DAY;
            }

            if ($start >= $end) {
                return Failure::create();
            }

            if ($prevEnd !== null && $prevEnd > $start) {
                return Failure::create();
            }

            $prevEnd = $end;
        }

        return null;
    }

    private function toMinutes(mixed $time): ?int
    {
        if (!is_string($time)) {
            return null;
        }

        if (!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time, $matches)) {
            return null;
        }

        return (int) $matches[1] * 60 + (int) $matches[2];
    }
}
