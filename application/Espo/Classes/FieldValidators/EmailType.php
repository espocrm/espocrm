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

use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;

use stdClass;

class EmailType
{
    private const DEFAULT_MAX_LENGTH = 255;
    private const MAX_COUNT = 10;

    public function __construct(
        private Metadata $metadata,
        private Config $config,
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
            if (!empty($item->emailAddress)) {
                return true;
            }
        }

        return false;
    }

    public function checkEmailAddress(Entity $entity, string $field): bool
    {
        if ($this->isNotEmpty($entity, $field)) {
            $address = $entity->get($field);

            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
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

            if (empty($item->emailAddress)) {
                continue;
            }

            $address = $item->emailAddress;

            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
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
        $maxLength = $this->metadata
                ->get(['entityDefs', 'EmailAddress', 'fields', Field::NAME, FieldParam::MAX_LENGTH]) ??
            self::DEFAULT_MAX_LENGTH;

        if ($value && mb_strlen($value) > $maxLength) {
            return false;
        }

        $dataList = $entity->get($field . 'Data');

        if (!is_array($dataList)) {
            return true;
        }

        foreach ($dataList as $item) {
            $value = $item->emailAddress;

            if ($value && mb_strlen($value) > $maxLength) {
                return false;
            }
        }

        return true;
    }

    public function checkMaxCount(Entity $entity, string $field): bool
    {
        $maxCount = $this->config->get('emailAddressMaxCount') ?? self::MAX_COUNT;

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
