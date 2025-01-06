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

namespace Espo\Modules\Crm\Tools\TargetList;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Core\Name\Field;
use Espo\Core\Record\Collection;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Record\EntityProvider;
use Espo\Core\Select\SearchParams;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Select;
use PDO;
use RuntimeException;

class OptOutService
{
    public function __construct(
        private EntityManager $entityManager,
        private MetadataProvider $metadataProvider,
        private EntityProvider $entityProvider,
        private HookManager $hookManager
    ) {}

    /**
     * Opt out a target.
     *
     * @throws Forbidden
     * @throws NotFound
     */
    public function optOut(string $id, string $targetType, string $targetId): void
    {
        $targetList = $this->entityProvider->getByClass(TargetList::class, $id);

        $target = $this->entityManager->getEntityById($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = $this->metadataProvider->getEntityTypeLinkMap();

        if (empty($map[$targetType])) {
            throw new Forbidden("Not supported target type.");
        }

        $link = $map[$targetType];

        $this->entityManager
            ->getRDBRepository(TargetList::ENTITY_TYPE)
            ->getRelation($targetList, $link)
            ->relateById($targetId, ['optedOut' => true]);

        $hookData = [
            'link' => $link,
            'targetId' => $targetId,
            'targetType' => $targetType,
        ];

        $this->hookManager->process(TargetList::ENTITY_TYPE, 'afterOptOut', $targetList, [], $hookData);
    }

    /**
     * Cancel opt-out for a target.
     *
     * @throws Forbidden
     * @throws NotFound
     */
    public function cancelOptOut(string $id, string $targetType, string $targetId): void
    {
        $targetList = $this->entityProvider->getByClass(TargetList::class, $id);

        $target = $this->entityManager->getEntityById($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        $map = $this->metadataProvider->getEntityTypeLinkMap();

        if (empty($map[$targetType])) {
            throw new Forbidden("Not supported target type.");
        }

        $link = $map[$targetType];

        $this->entityManager
            ->getRDBRepository(TargetList::ENTITY_TYPE)
            ->getRelation($targetList, $link)
            ->updateColumnsById($targetId, ['optedOut' => false]);

        $hookData = [
            'link' => $link,
            'targetId' => $targetId,
            'targetType' => $targetType,
        ];

        $this->hookManager->process('TargetList', TargetList::ENTITY_TYPE, $targetList, [], $hookData);
    }

    /**
     * Find opted out targets in a target list.
     *
     * @return Collection<Entity>
     * @throws Forbidden
     * @throws NotFound
     */
    public function find(string $id, SearchParams $params): Collection
    {
        $this->checkEntity($id);

        $offset = $params->getOffset() ?? 0;
        $maxSize = $params->getMaxSize() ?? 0;

        $em = $this->entityManager;
        $queryBuilder = $em->getQueryBuilder();

        $queryList = [];

        $targetLinkList = $this->metadataProvider->getTargetLinkList();

        foreach ($targetLinkList as $link) {
            $queryList[] = $this->getSelectQueryForLink($id, $link);
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
            ->order(Field::CREATED_AT, 'DESC')
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

    private function getSelectQueryForLink(string $id, string $link): Select
    {
        $seed = $this->entityManager->getRDBRepositoryByClass(TargetList::class)->getNew();

        $entityType = $seed->getRelationParam($link, RelationParam::ENTITY);

        if (!$entityType) {
            throw new RuntimeException();
        }

        $linkEntityType = ucfirst(
            $seed->getRelationParam($link, RelationParam::RELATION_NAME) ?? ''
        );

        if ($linkEntityType === '') {
            throw new RuntimeException();
        }

        $key = $seed->getRelationParam($link, RelationParam::MID_KEYS)[1] ?? null;

        if (!$key) {
            throw new RuntimeException();
        }

        return $this->entityManager->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->select([
                'id',
                'name',
                Field::CREATED_AT,
                ["'$entityType'", 'entityType'],
            ])
            ->join(
                $linkEntityType,
                'j',
                [
                    "j.$key:" => 'id',
                    'j.deleted' => false,
                    'j.optedOut' => true,
                    'j.targetListId' => $id,
                ]
            )
            ->order(Field::CREATED_AT, Order::DESC)
            ->build();
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function checkEntity(string $id): void
    {
        $this->entityProvider->getByClass(TargetList::class, $id);
    }
}
