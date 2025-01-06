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

class LinkParentType
{
    private Metadata $metadata;
    private Defs $defs;

    public function __construct(Metadata $metadata, Defs $defs)
    {
        $this->metadata = $metadata;
        $this->defs = $defs;
    }

    public function checkRequired(Entity $entity, string $field): bool
    {
        $idAttribute = $field . 'Id';
        $typeAttribute = $field . 'Type';

        if (
            !$entity->has($idAttribute) ||
            $entity->get($idAttribute) === '' ||
            $entity->get($idAttribute) === null
        ) {
            return false;
        }

        if (!$entity->get($typeAttribute)) {
            return false;
        }

        return true;
    }

    public function checkPattern(Entity $entity, string $field): bool
    {
        /** @var ?string $idValue */
        $idValue = $entity->get($field . 'Id');

        if ($idValue === null) {
            return true;
        }

        $pattern = $this->metadata->get(['app', 'regExpPatterns', 'id', 'pattern']);

        if (!$pattern) {
            return true;
        }

        $preparedPattern = '/^' . $pattern . '$/';

        return (bool) preg_match($preparedPattern, $idValue);
    }

    public function checkValid(Entity $entity, string $field): bool
    {
        /** @var ?string $typeValue */
        $typeValue = $entity->get($field . 'Type');

        if ($typeValue === null) {
            return true;
        }

        /** @var ?string[] $entityTypeList */
        $entityTypeList = $this->defs
            ->getEntity($entity->getEntityType())
            ->getField($field)
            ->getParam('entityList');

        if ($entityTypeList !== null) {
            return in_array($typeValue, $entityTypeList);
        }

        return (bool) $this->metadata->get(['entityDefs', $typeValue]);
    }
}
