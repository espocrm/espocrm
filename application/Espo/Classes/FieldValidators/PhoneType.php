<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Classes\FieldValidators;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\ORM\Entity;

use stdClass;

class PhoneType
{
    private Metadata $metadata;

    private Defs $defs;

    private const DEFAULT_MAX_LENGTH = 36;

    public function __construct(Metadata $metadata, Defs $defs)
    {
        $this->metadata = $metadata;
        $this->defs = $defs;
    }

    public function checkRequired(Entity $entity, string $field): bool
    {
        if ($this->isNotEmpty($entity, $field)) {
            return true;
        }

        $dataList = $entity->get($field . 'Data');

        if (!is_array($dataList)) {
            return false;
        }

        foreach ($dataList as $item) {
            if (!empty($item->phoneNumber)) {
                return true;
            }
        }

        return false;
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        if ($this->isNotEmpty($entity, $field)) {
            $number = $entity->get($field);

            if (!$this->isValidNumber($number)) {
                return false;
            }
        }

        $dataList = $entity->get($field . 'Data');

        if (!is_array($dataList)) {
            return true;
        }

        foreach ($dataList as $item) {
            if (!$item instanceof stdClass) {
                return false;
            }

            $number = $item->phoneNumber ?? null;
            $type = $item->type ?? null;

            if (!$number) {
                return false;
            }

            if (!$this->isValidNumber($number)) {
                return false;
            }

            if (!$this->isValidType($entity->getEntityType(), $field, $type)) {
                return false;
            }
        }

        return true;
    }

    public function checkMaxLength(Entity $entity, string $field): bool
    {
        /** @var ?string $value */
        $value = $entity->get($field);

        /** @var int $maxLength */
        $maxLength = $this->metadata->get(['entityDefs', 'PhoneNumber', 'fields', 'name', 'maxLength']) ??
            self::DEFAULT_MAX_LENGTH;

        if ($value && mb_strlen($value) > $maxLength) {
            return false;
        }

        $dataList = $entity->get($field . 'Data');

        if (!is_array($dataList)) {
            return true;
        }

        foreach ($dataList as $item) {
            $value = $item->phoneNumber;

            if ($value && mb_strlen($value) > $maxLength) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $type
     */
    private function isValidType(string $entityType, string $field, $type): bool
    {
        if ($type === null) {
            // Will be stored with a default type.
            return true;
        }

        if (!is_string($type)) {
            return false;
        }

        /** @var string[]|null|false $typeList */
        $typeList = $this->defs
            ->getEntity($entityType)
            ->getField($field)
            ->getParam('typeList');

        if ($typeList === null) {
            return true;
        }

        // For bc.
        if ($typeList === false) {
            return true;
        }

        return in_array($type, $typeList);
    }

    /**
     * @param mixed $number
     */
    private function isValidNumber($number): bool
    {
        if (!is_string($number)) {
            return false;
        }

        if ($number === '') {
            return false;
        }

        $pattern = $this->metadata->get(['app', 'regExpPatterns', 'phoneNumberLoose', 'pattern']);

        if (!$pattern) {
            return true;
        }

        $preparedPattern = '/^' . $pattern . '$/';

        return (bool) preg_match($preparedPattern, $number);
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return $entity->has($field) && $entity->get($field) !== '' && $entity->get($field) !== null;
    }
}
