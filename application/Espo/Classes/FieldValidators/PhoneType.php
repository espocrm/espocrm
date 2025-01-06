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

use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;
use Espo\Core\PhoneNumber\Util;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;

use stdClass;

/**
 * @noinspection PhpUnused
 */
class PhoneType
{
    private const DEFAULT_MAX_LENGTH = 36;
    private const MAX_COUNT = 10;

    public function __construct(
        private Metadata $metadata,
        private Defs $defs,
        private Config $config
    ) {}

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
        $maxLength = $this->metadata->get(['entityDefs', 'PhoneNumber', 'fields', 'name', FieldParam::MAX_LENGTH]) ??
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

        if (!preg_match($preparedPattern, $number)) {
            return false;
        }

        if (!$this->config->get('phoneNumberInternational')) {
            return true;
        }

        $ext = null;

        if ($this->config->get('phoneNumberExtensions')) {
            [$number, $ext] = Util::splitExtension($number);
        }

        if ($ext) {
            if (!preg_match('/[0-9]+/', $ext)) {
                return false;
            }

            if (strlen($ext) > 6) {
                return false;
            }
        }

        try {
            $numberObj = PhoneNumber::parse($number);
        } catch (PhoneNumberParseException) {
            return false;
        }

        if ((string) $numberObj !== $number) {
            return false;
        }

        return $numberObj->isPossibleNumber();
    }

    public function checkMaxCount(Entity $entity, string $field): bool
    {
        $maxCount = $this->config->get('phoneNumberMaxCount') ?? self::MAX_COUNT;

        $dataList = $entity->get($field . 'Data');

        if (!is_array($dataList)) {
            return true;
        }

        return count($dataList) <= $maxCount;
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return $entity->has($field) && $entity->get($field) !== '' && $entity->get($field) !== null;
    }
}
