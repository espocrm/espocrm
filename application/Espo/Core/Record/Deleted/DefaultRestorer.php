<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Record\Deleted;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Repository\Util;

/**
 * @implements Restorer<Entity>
 */
class DefaultRestorer implements Restorer
{
    public function __construct(
        private EntityManager $entityManager,
        private Metadata $metadata,
        private RestorerFactory $restorerFactory,
    ) {}

    public function restore(Entity $entity): void
    {
        if (!$entity->get(Attribute::DELETED)) {
            throw new Conflict("Entity is not soft-deleted.");
        }

        $this->entityManager
            ->getTransactionManager()
            ->run(fn () => $this->restoreInTransaction($entity));
    }

    /**
     * @throws Conflict
     */
    private function restoreInTransaction(Entity $entity): void
    {
        $modifiedAt = $this->getModifiedAt($entity);

        $repository = $this->entityManager->getRDBRepository($entity->getEntityType());

        $repository->restoreDeleted($entity->getId());

        if (
            $entity->hasAttribute('deleteId') &&
            $this->metadata->get("entityDefs.{$entity->getEntityType()}.deleteId")
        ) {
            $this->entityManager->refreshEntity($entity);

            $entity->set('deleteId', '0');
            $repository->save($entity, [SaveOption::SILENT => true]);
        }

        if ($modifiedAt) {
            $this->restoreRelatedRecords($entity, $modifiedAt);
        }
    }

    /**
     * @throws Conflict
     */
    private function restoreRelatedRecords(Entity $entity, DateTime $modifiedAt): void
    {
        $relations = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getRelationList();

        foreach ($relations as $relation) {
            if (!$relation->getParam(RelationParam::CASCADE_REMOVAL)) {
                continue;
            }

            $this->restoreRelatedLink($entity, $relation, $modifiedAt);
        }
    }

    /**
     * @throws Conflict
     */
    private function restoreRelatedLink(Entity $entity, RelationDefs $relation, DateTime $modifiedAt): void
    {
        $foreignEntityType = $relation->tryGetForeignEntityType();
        $foreign = $relation->tryGetForeignRelationName();

        if (!$foreignEntityType || !$foreign) {
            return;
        }

        $foreignType = $this->entityManager
            ->getDefs()
            ->tryGetEntity($foreignEntityType)
            ?->tryGetRelation($foreign)
            ?->getType();

        if (!$foreignType) {
            return;
        }

        if (!Util::isRelationshipEligibleForCascadeRemoval($relation->getType(), $foreignType)) {
            return;
        }

        $link = $relation->getName();

        $builder = SelectBuilder::create()
            ->from($foreignEntityType)
            ->withDeleted();

        $collection = $this->entityManager
            ->getRelation($entity, $link)
            ->clone($builder->build())
            ->sth()
            ->where([
                Attribute::DELETED => true,
                Field::MODIFIED_AT . '>=' => $modifiedAt->toString(),
            ])
            ->find();

        foreach ($collection as $relatedEntity) {
            $this->restoreRelated($relatedEntity);
        }
    }

    /**
     * @throws Conflict
     */
    private function restoreRelated(Entity $relatedEntity): void
    {
        if (
            !$relatedEntity->hasAttribute(Attribute::DELETED) ||
            !$relatedEntity->get(Attribute::DELETED)
        ) {
            return;
        }

        $restorer = $this->restorerFactory->create($relatedEntity->getEntityType());

        $restorer->restore($relatedEntity);
    }


    private function getModifiedAt(Entity $entity): ?DateTime
    {
        $modifiedAtString = $entity->get(Field::MODIFIED_AT);

        return $modifiedAtString ? DateTime::fromString($modifiedAtString) : null;
    }
}
