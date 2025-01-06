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

namespace Espo\Core\FieldProcessing\File;

use Espo\Entities\Attachment;
use Espo\ORM\Entity;

use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;

/**
 * @implements SaverInterface<Entity>
 */
class Saver implements SaverInterface
{
    private EntityManager $entityManager;

    /** @var array<string, string[]>*/
    private $fieldListMapCache = [];

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, Params $params): void
    {
        foreach ($this->getFieldList($entity->getEntityType()) as $name) {
            $this->processItem($entity, $name);
        }
    }

    private function processItem(Entity $entity, string $name): void
    {
        $attribute = $name . 'Id';

        if (!$entity->isAttributeChanged($attribute)) {
            return;
        }

        $id = $entity->get($attribute);

        if (!$id) {
            $this->removePrevious($entity, $attribute);

            return;
        }

        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $id);

        if (!$attachment) {
            return;
        }

        $attachment->set([
            'relatedId' => $entity->getId(),
            'relatedType' => $entity->getEntityType(),
        ]);

        $this->entityManager->saveEntity($attachment);

        if ($entity->isNew()) {
            return;
        }

        $this->removePrevious($entity, $attribute);
    }

    private function removePrevious(Entity $entity, string $attribute): void
    {
        $previousId = $entity->getFetched($attribute);

        if (!$previousId) {
            return;
        }

        $previousAttachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $previousId);

        if (!$previousAttachment) {
            return;
        }

        $this->entityManager->removeEntity($previousAttachment);
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->fieldListMapCache)) {
            return $this->fieldListMapCache[$entityType];
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $list = [];

        foreach ($entityDefs->getRelationNameList() as $name) {
            $defs = $entityDefs->getRelation($name);

            $type = $defs->getType();

            if (!$defs->hasForeignEntityType()) {
                continue;
            }

            $foreignEntityType = $defs->getForeignEntityType();

            if ($type !== Entity::BELONGS_TO || $foreignEntityType !== 'Attachment') {
                continue;
            }


            if (!$entityDefs->hasAttribute($name . 'Id')) {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListMapCache[$entityType] = $list;

        return $list;
    }
}
