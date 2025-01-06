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

use stdClass;

class ArrayType
{
    private const DEFAULT_MAX_ITEM_LENGTH = 100;

    public function __construct(protected Metadata $metadata, private Defs $defs)
    {}

    public function checkRequired(Entity $entity, string $field): bool
    {
        return $this->isNotEmpty($entity, $field);
    }

    public function checkMaxCount(Entity $entity, string $field, int $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        $list = $entity->get($field);

        if (count($list) > $validationValue) {
            return false;
        }

        return true;
    }

    public function checkArrayOfString(Entity $entity, string $field): bool
    {
        /** @var ?mixed[] $list */
        $list = $entity->get($field);

        if ($list === null) {
            return true;
        }

        foreach ($list as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        if (!$entity->has($field)) {
            return true;
        }

        /** @var ?string[] $value */
        $value = $entity->get($field);

        if ($value === null || $value === []) {
            return true;
        }

        $fieldDefs = $this->defs
            ->getEntity($entity->getEntityType())
            ->getField($field);

        if ($fieldDefs->getParam('allowCustomOptions')) {
            return true;
        }

        $optionList = $this->getOptionList($entity->getEntityType(), $field);

        if ($optionList === null) {
            return true;
        }

        foreach ($value as $item) {
            if (!in_array($item, $optionList)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ?string[]
     */
    private function getOptionList(string $entityType, string $field): ?array
    {
        $fieldDefs = $this->defs
            ->getEntity($entityType)
            ->getField($field);

        /** @var ?string $path */
        $path = $fieldDefs->getParam('optionsPath');
        /** @var ?string $path */
        $ref = $fieldDefs->getParam('optionsReference');

        if (!$path && $ref && str_contains($ref, '.')) {
            [$refEntityType, $refField] = explode('.', $ref);

            $path = "entityDefs.{$refEntityType}.fields.{$refField}.options";
        }

        /** @var string[]|null|false $optionList */
        $optionList = $path ?
            $this->metadata->get($path) :
            $fieldDefs->getParam('options');

        if ($optionList === null) {
            return null;
        }

        // For bc.
        if ($optionList === false) {
            return null;
        }

        return $optionList;
    }

    public function rawCheckArray(stdClass $data, string $field): bool
    {
        if (isset($data->$field) && !is_array($data->$field)) {
            return false;
        }

        return true;
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        if (!$entity->has($field) || $entity->get($field) === null) {
            return false;
        }

        $list = $entity->get($field);

        if (!is_array($list)) {
            return false;
        }

        if (count($list)) {
            return true;
        }

        return false;
    }

    public function checkMaxItemLength(Entity $entity, string $field, ?int $validationValue): bool
    {
        $maxLength = $validationValue ?? self::DEFAULT_MAX_ITEM_LENGTH;

        /** @var mixed[] $value */
        $value = $entity->get($field) ?? [];

        foreach ($value as $item) {
            if (is_string($item) && mb_strlen($item) > $maxLength) {
                return false;
            }
        }

        return true;
    }

    public function checkPattern(Entity $entity, string $field, ?string $validationValue): bool
    {
        if (!$validationValue) {
            return true;
        }

        $pattern = $validationValue;

        if ($validationValue[0] === '$') {
            $patternName = substr($validationValue, 1);

            $pattern = $this->metadata->get(['app', 'regExpPatterns', $patternName, 'pattern']) ??
                $pattern;
        }

        $preparedPattern = '/^' . $pattern . '$/';

        /** @var string[] $value */
        $value = $entity->get($field) ?? [];

        foreach ($value as $item) {
            if ($item === '') {
                continue;
            }

            if (!preg_match($preparedPattern, $item)) {
                return false;
            }
        }

        return true;
    }

    public function checkNoEmptyString(Entity $entity, string $field, ?bool $validationValue): bool
    {
        if (!$validationValue) {
            return true;
        }

        /** @var string[] $value */
        $value = $entity->get($field) ?? [];

        $optionList = $this->getOptionList($entity->getEntityType(), $field) ?? [];

        foreach ($value as $item) {
            if ($item === '' && !in_array($item, $optionList)) {
                return false;
            }
        }

        return true;
    }
}
