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

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\ORM\Entity;

class VarcharType
{
    private Metadata $metadata;

    private const DEFAULT_MAX_LENGTH = 255;
    private Defs $defs;

    public function __construct(Metadata $metadata, Defs $defs)
    {
        $this->metadata = $metadata;
        $this->defs = $defs;
    }

    public function checkRequired(Entity $entity, string $field): bool
    {
        return $this->isNotEmpty($entity, $field);
    }

    public function checkMaxLength(Entity $entity, string $field, ?int $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        $fieldDefs = $this->defs
            ->getEntity($entity->getEntityType())
            ->getField($field);

        if ($fieldDefs->isNotStorable() && !$validationValue) {
            return true;
        }

        $value = $entity->get($field);

        $maxLength = $validationValue ?? self::DEFAULT_MAX_LENGTH;

        if (mb_strlen($value) > $maxLength) {
            return false;
        }

        return true;
    }

    public function checkPattern(Entity $entity, string $field, ?string $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field) || !$validationValue) {
            return true;
        }

        $value = $entity->get($field);
        $pattern = $validationValue;

        if ($validationValue[0] === '$') {
            $patternName = substr($validationValue, 1);

            $pattern = $this->metadata->get(['app', 'regExpPatterns', $patternName, 'pattern']) ??
                $pattern;
        }

        $preparedPattern = '/^' . $pattern . '$/';

        return (bool) preg_match($preparedPattern, $value);
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return
            $entity->has($field) &&
            $entity->get($field) !== '' &&
            $entity->get($field) !== null;
    }
}
