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

namespace Espo\Classes\FieldValidators;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Date;
use Espo\ORM\Entity;
use Exception;

class DatetimeOptionalType extends DatetimeType
{
    public function checkRequired(Entity $entity, string $field): bool
    {
        return $this->isNotEmpty($entity, $field);
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        if ($entity->has($field) && $entity->get($field) !== null) {
            return true;
        }

        if ($entity->has($field . 'Date') && $entity->get($field . 'Date') !== null) {
            return true;
        }

        return false;
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        /** @var ?string $dateValue */
        $dateValue = $entity->get($field  . 'Date');

        if ($dateValue !== null) {
            try {
                Date::fromString($dateValue);
            } catch (Exception $e) {
                return false;
            }
        }

        /** @var ?string $value */
        $value = $entity->get($field);

        if ($value !== null) {
            try {
                DateTime::fromString($value);
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }
}
