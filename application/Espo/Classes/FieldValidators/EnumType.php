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

class EnumType
{
    private Metadata $metadata;
    private Defs $defs;

    private const DEFAULT_MAX_LENGTH = 255;

    public function __construct(Metadata $metadata, Defs $defs)
    {
        $this->metadata = $metadata;
        $this->defs = $defs;
    }

    public function checkRequired(Entity $entity, string $field): bool
    {
        return $this->isNotEmpty($entity, $field);
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        if (!$entity->has($field)) {
            return true;
        }

        $fieldDefs = $this->defs
            ->getEntity($entity->getEntityType())
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
            return true;
        }

        // For bc.
        if ($optionList === false) {
            return true;
        }

        $optionList = array_map(
            fn ($item) => $item === '' ? null : $item,
            $optionList
        );

        $value = $entity->get($field);

        return in_array($value, $optionList);
    }

    public function checkMaxLength(Entity $entity, string $field, ?int $validationValue): bool
    {
        if (!$this->isNotEmpty($entity, $field)) {
            return true;
        }

        $value = $entity->get($field);

        $maxLength = $validationValue ?? self::DEFAULT_MAX_LENGTH;

        if (mb_strlen($value) > $maxLength) {
            return false;
        }

        return true;
    }

    protected function isNotEmpty(Entity $entity, string $field): bool
    {
        return $entity->has($field) && $entity->get($field) !== null;
    }
}
