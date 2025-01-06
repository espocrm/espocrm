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

namespace Espo\Core\FieldProcessing\LinkMultiple;

use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\FieldProcessing\Loader as LoaderInterface;
use Espo\Core\FieldProcessing\Loader\Params;

use Espo\ORM\Defs as OrmDefs;

/**
 * @implements LoaderInterface<Entity>
 */
class ListLoader implements LoaderInterface
{
    /** @var array<string, string[]> */
    private array $fieldListCacheMap = [];

    public function __construct(private OrmDefs $ormDefs)
    {}

    public function process(Entity $entity, Params $params): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $entityType = $entity->getEntityType();

        $select = $params->getSelect() ?? [];

        if (count($select) === 0) {
            return;
        }

        foreach ($this->getFieldList($entityType) as $field) {
            if (
                !in_array($field . 'Ids', $select) &&
                !in_array($field . 'Names', $select)
            ) {
                continue;
            }

            if ($entity->has($field . 'Ids') && $entity->has($field . 'Names')) {
                continue;
            }

            $entity->loadLinkMultipleField($field);
        }
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->fieldListCacheMap)) {
            return $this->fieldListCacheMap[$entityType];
        }

        $list = [];

        $entityDefs = $this->ormDefs->getEntity($entityType);

        foreach ($entityDefs->getFieldList() as $fieldDefs) {
            if (
                $fieldDefs->getType() !== FieldType::LINK_MULTIPLE &&
                $fieldDefs->getType() !== FieldType::ATTACHMENT_MULTIPLE
            ) {
                continue;
            }

            if ($fieldDefs->getParam('noLoad')) {
                continue;
            }

            if ($fieldDefs->isNotStorable()) {
                continue;
            }

            $name = $fieldDefs->getName();

            if (!$entityDefs->hasRelation($name)) {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListCacheMap[$entityType] = $list;

        return $list;
    }
}
