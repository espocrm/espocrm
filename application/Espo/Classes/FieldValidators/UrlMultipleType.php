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

use Espo\ORM\Entity;

class UrlMultipleType extends ArrayType
{
    private const MAX_ITEM_LENGTH = 255;

    public function checkNoEmptyString(Entity $entity, string $field, ?bool $validationValue): bool
    {
        return parent::checkNoEmptyString($entity, $field, true);
    }

    public function checkMaxItemLength(Entity $entity, string $field, ?int $validationValue): bool
    {
        return parent::checkMaxItemLength($entity, $field, self::MAX_ITEM_LENGTH);
    }

    public function checkPattern(Entity $entity, string $field, ?string $validationValue): bool
    {
        /** @var string $pattern */
        $pattern = $this->metadata->get(['app', 'regExpPatterns', 'uriOptionalProtocol', 'pattern']);

        return parent::checkPattern($entity, $field, $pattern);
    }
}
