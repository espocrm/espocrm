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

use Doctrine\DBAL\Types\Types;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Entity;
use stdClass;

class IntType
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function checkRequired(Entity $entity, string $field): bool
    {
        return $this->isNotEmpty($entity, $field);
    }

    /** @noinspection PhpUnused */
    public function checkRangeInternal(Entity $entity, string $field): bool
    {
        $value = $entity->get($field);

        if ($value === null) {
            return true;
        }

        $dbType = $this->defs
            ->getEntity($entity->getEntityType())
            ->tryGetAttribute($field)
            ?->getParam(AttributeParam::DB_TYPE) ?? Types::INTEGER;

        $ranges = [
            Types::INTEGER => [-2147483648, 2147483647],
            Types::SMALLINT => [-32768, 32767],
        ];

        $range = $ranges[$dbType] ?? null;

        if (!$range) {
            return true;
        }

        if ($value < $range[0] || $value > $range[1]) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $validationValue
     * @noinspection PhpUnused
     */
    public function checkMax(Entity $entity, string $field, $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        if ($entity->get($field) > $validationValue) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $validationValue
     * @noinspection PhpUnused
     */
    public function checkMin(Entity $entity, string $field, $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        if ($entity->get($field) < $validationValue) {
            return false;
        }

        return true;
    }

    /** @noinspection PhpUnused */
    public function rawCheckValid(stdClass $data, string $field): bool
    {
        if (!isset($data->$field)) {
            return true;
        }

        $value = $data->$field;

        if ($value === '') {
            return true;
        }

        if (is_numeric($value)) {
            return true;
        }

        return false;
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return $entity->has($field) && $entity->get($field) !== null;
    }
}
