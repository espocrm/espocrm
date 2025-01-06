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

namespace Espo\Tools\Export\Format\Xlsx;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\Tools\Export\AdditionalFieldsLoader as AdditionalFieldsLoaderInterface;

/**
 * @noinspection PhpUnused
 */
class AdditionalFieldsLoader implements AdditionalFieldsLoaderInterface
{
    public function __construct(private Metadata $metadata)
    {}

    public function load(Entity $entity, array $fieldList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($entity->getRelationList() as $link) {
            if (!in_array($link, $fieldList)) {
                continue;
            }

            if ($entity->getRelationType($link) === Entity::BELONGS_TO_PARENT) {
                if (!$entity->get($link . 'Name')) {
                    $entity->loadParentNameField($link);
                }
            } else if (
                (
                    (
                        $entity->getRelationType($link) === Entity::BELONGS_TO &&
                        $entity->getRelationParam($link, RelationParam::NO_JOIN)
                    ) ||
                    $entity->getRelationType($link) === Entity::HAS_ONE
                ) &&
                $entity->hasAttribute($link . 'Name')
            ) {
                if (!$entity->get($link . 'Name') || !$entity->get($link . 'Id')) {
                    $entity->loadLinkField($link);
                }
            }
        }

        foreach ($fieldList as $field) {
            $fieldType = $this->metadata
                ->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

            if ($fieldType === FieldType::LINK_MULTIPLE || $fieldType === FieldType::ATTACHMENT_MULTIPLE) {
                if (!$entity->has($field . 'Ids') && $entity->hasLinkMultipleField($field)) {
                    $entity->loadLinkMultipleField($field);
                }
            }
        }
    }
}
