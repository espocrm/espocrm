<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Services;

use Espo\Core\Acl\Table;
use Espo\ORM\Entity;
use Espo\ORM\Query\Select;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;
use Espo\Modules\Crm\Entities\TargetList as TargetListEntity;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Services\Record;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Metadata;

use PDO;
use Espo\Core\Di;
use RuntimeException;

/**
 * @extends Record<TargetListEntity>
 */
class TargetList extends Record implements

    Di\HookManagerAware
{
    use Di\HookManagerSetter;

    /** @var string[] */
    protected $targetLinkList = [];
    protected array $noEditAccessRequiredLinkList = [];
    protected $duplicatingLinkList = [];

    /** @var array<string, string> */
    protected $entityTypeLinkMap = [];

    public function setMetadata(Metadata $metadata): void
    {
        parent::setMetadata($metadata);

        $this->targetLinkList = $this->metadata->get(['scopes', 'TargetList', 'targetLinkList']) ?? [];

        $this->duplicatingLinkList = $this->targetLinkList;
        $this->noEditAccessRequiredLinkList = $this->targetLinkList;

        foreach ($this->targetLinkList as $link) {
            /** @var string $link */
            $this->linkMandatorySelectAttributeList[$link] = ['targetListIsOptedOut'];

            $entityType = $this->entityManager
                ->getDefs()
                ->getEntity(TargetListEntity::ENTITY_TYPE)
                ->getRelation($link)
                ->getForeignEntityType();

            $this->entityTypeLinkMap[$entityType] = $link;
        }
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     * @todo Move.
     */
    public function unlinkAll(string $id, string $link): void
    {
        /** @var ?TargetListEntity $entity */
        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            throw new Forbidden();
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (!$foreignEntityType) {
            throw new Error();
        }

        $linkEntityType = ucfirst(
            $entity->getRelationParam($link, 'relationName') ?? ''
        );

        if ($linkEntityType === '') {
            throw new Error();
        }

        $updateQuery = $this->entityManager->getQueryBuilder()
            ->update()
            ->in($linkEntityType)
            ->set(['deleted' => true])
            ->where(['targetListId' => $entity->getId()])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);

        $this->hookManager->process('TargetList', 'afterUnlinkAll', $entity, [], ['link' => $link]);
    }

    protected function getOptedOutSelectQueryForLink(string $targetListId, string $link): Select
    {
        /** @var TargetListEntity $seed */
        $seed = $this->getRepository()->getNew();

        $entityType = $seed->getRelationParam($link, 'entity');

        if (!$entityType) {
            throw new RuntimeException();
        }

        $linkEntityType = ucfirst(
            $seed->getRelationParam($link, 'relationName') ?? ''
        );

        if ($linkEntityType === '') {
            throw new RuntimeException();
        }

        $key = $seed->getRelationParam($link, 'midKeys')[1] ?? null;

        if (!$key) {
            throw new RuntimeException();
        }

        return $this->entityManager->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->select([
                'id',
                'name',
                'createdAt',
                ["'$entityType'", 'entityType'],
            ])
            ->join(
                $linkEntityType,
                'j',
                [
                    "j.$key:" => 'id',
                    'j.deleted' => false,
                    'j.optedOut' => true,
                    'j.targetListId' => $targetListId,
                ]
            )
            ->order('createdAt', 'DESC')
            ->build();
    }

    /**
     * @return RecordCollection<Entity>
     * @noinspection PhpUnused
     * @todo Move? Use Tools\TargetList\MetadataProvider.
     */
    protected function findLinkedOptedOut(string $id, SearchParams $searchParams): RecordCollection
    {
        $offset = $searchParams->getOffset() ?? 0;
        $maxSize = $searchParams->getMaxSize() ?? 0;

        $em = $this->entityManager;
        $queryBuilder = $em->getQueryBuilder();

        $queryList = [];

        foreach ($this->targetLinkList as $link) {
            $queryList[] = $this->getOptedOutSelectQueryForLink($id, $link);
        }

        $builder = $queryBuilder
            ->union()
            ->all();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $countQuery = $queryBuilder
            ->select()
            ->fromQuery($builder->build(), 'c')
            ->select('COUNT:(c.id)', 'count')
            ->build();

        $row = $em->getQueryExecutor()
            ->execute($countQuery)
            ->fetch(PDO::FETCH_ASSOC);

        $totalCount = $row['count'];

        $unionQuery = $builder
            ->limit($offset, $maxSize)
            ->order('createdAt', 'DESC')
            ->build();

        $sth = $em->getQueryExecutor()->execute($unionQuery);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create();

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $itemEntity = $this->entityManager->getNewEntity($row['entityType']);

            $itemEntity->set($row);
            $itemEntity->setAsFetched();

            $collection[] = $itemEntity;
        }

        /** @var RecordCollection<Entity> */
        return new RecordCollection($collection, $totalCount);
    }

    /**
     * @throws NotFound
     * @throws Error
     * @todo Move. Use Tools\TargetList\MetadataProvider.
     */
    public function optOut(string $id, string $targetType, string $targetId): void
    {
        $targetList = $this->entityManager->getEntityById(TargetListEntity::ENTITY_TYPE, $id);

        if (!$targetList) {
            throw new NotFound();
        }

        $target = $this->entityManager->getEntity($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = $this->entityTypeLinkMap;

        if (empty($map[$targetType])) {
            throw new Error();
        }

        $link = $map[$targetType];

        $this->entityManager
            ->getRDBRepository(TargetListEntity::ENTITY_TYPE)
            ->getRelation($targetList, $link)
            ->relateById($targetId, ['optedOut' => true]);

        $hookData = [
           'link' => $link,
           'targetId' => $targetId,
           'targetType' => $targetType,
        ];

        $this->hookManager->process('TargetList', 'afterOptOut', $targetList, [], $hookData);
    }

    /**
     * @throws NotFound
     * @throws Error
     * @todo Move. Use Tools\TargetList\MetadataProvider.
     */
    public function cancelOptOut(string $id, string $targetType, string $targetId): void
    {
        $targetList = $this->entityManager->getEntityById(TargetListEntity::ENTITY_TYPE, $id);

        if (!$targetList) {
            throw new NotFound();
        }

        $target = $this->entityManager->getEntityById($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = $this->entityTypeLinkMap;

        if (empty($map[$targetType])) {
            throw new Error();
        }

        $link = $map[$targetType];

        $this->entityManager
            ->getRDBRepository(TargetListEntity::ENTITY_TYPE)
            ->getRelation($targetList, $link)
            ->updateColumnsById($targetId, ['optedOut' => false]);

        $hookData = [
           'link' => $link,
           'targetId' => $targetId,
           'targetType' => $targetType,
        ];

        $this->hookManager->process('TargetList', 'afterCancelOptOut', $targetList, [], $hookData);
    }

    /**
     * @todo Don't use additionalColumnsConditions.
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity): void
    {
        $repository = $this->getRepository();

        foreach ($this->duplicatingLinkList as $link) {
            $linkedList = $repository
                ->getRelation($duplicatingEntity, $link)
                ->where(['@relation.optedOut' => false])
                ->find();

            foreach ($linkedList as $linked) {
                $repository
                    ->getRelation($entity, $link)
                    ->relate($linked, ['optedOut' => false]);
            }
        }
    }
}
