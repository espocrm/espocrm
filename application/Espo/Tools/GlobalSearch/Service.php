<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Tools\GlobalSearch;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\Collection;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\Core\Select\Text\FullTextSearch\DataComposerFactory as FullTextSearchDataComposerFactory;
use Espo\Core\Select\Text\FullTextSearch\DataComposer\Params as FullTextSearchDataComposerParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Query\UnionBuilder;
use RuntimeException;

class Service
{
    public function __construct(
        private FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Acl $acl,
        private Config $config,
        private Config\ApplicationConfig $applicationConfig,
        private ServiceContainer $serviceContainer,
    ) {}

    /**
     * @param string $filter A search query.
     * @param ?int $offset An offset.
     * @param ?int $maxSize A limit.
     * @return Collection<Entity>
     */
    public function find(string $filter, ?int $offset = 0, ?int $maxSize = null): Collection
    {
        $entityTypeList = $this->config->get('globalSearchEntityList') ?? [];
        $offset ??= 0;
        $maxSize ??= $this->applicationConfig->getRecordsPerPage();

        $hasFullTextSearch = false;

        $queryList = [];

        foreach ($entityTypeList as $i => $entityType) {
            $query = $this->getEntityTypeQuery(
                entityType: $entityType,
                i: $i,
                filter: $filter,
                offset: $offset,
                maxSize: $maxSize,
                hasFullTextSearch: $hasFullTextSearch,
            );

            if (!$query) {
                continue;
            }

            $queryList[] = $query;
        }

        if (count($queryList) === 0) {
            return new Collection(new EntityCollection(), 0);
        }

        $builder = UnionBuilder::create()
            ->all()
            ->limit($offset, $maxSize + 1);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        if ($hasFullTextSearch) {
            $builder->order('relevance', Order::DESC);
        }

        $builder->order('order', Order::DESC);
        $builder->order(Field::NAME);

        $unionQuery = $builder->build();

        $collection = new EntityCollection();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        while ($row = $sth->fetch()) {
            $entityType = $row['entityType'] ?? null;

            if (!is_string($entityType)) {
                throw new RuntimeException();
            }

            $statusField = $this->getStatusField($entityType);

            $select = [
                Attribute::ID,
                Field::NAME,
            ];

            if ($statusField) {
                $select[] = $statusField;
            }

            $entity = $this->entityManager
                ->getRDBRepository($entityType)
                ->select($select)
                ->where([Attribute::ID => $row[Attribute::ID]])
                ->findOne();

            if (!$entity) {
                continue;
            }

            $this->serviceContainer->get($entityType)->prepareEntityForOutput($entity);

            $collection->append($entity);
        }

        return Collection::createNoCount($collection, $maxSize);
    }

    private function getEntityTypeQuery(
        string $entityType,
        int $i,
        string $filter,
        int $offset,
        int $maxSize,
        bool &$hasFullTextSearch,
    ): ?Select {

        if (!$this->acl->checkScope($entityType, Acl\Table::ACTION_READ)) {
            return null;
        }

        if (!$this->metadata->get("scopes.$entityType")) {
            return null;
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withStrictAccessControl()
            ->withTextFilter($filter);

        $entityDefs = $this->entityManager->getDefs()->getEntity($entityType);

        $nameAttribute = $entityDefs->hasField(Field::NAME) ? Field::NAME : Attribute::ID;

        $selectList = [
            Attribute::ID,
            $nameAttribute,
            ['VALUE:' . $entityType, 'entityType'],
            [(string) $i, 'order'],
        ];

        $fullTextSearchDataComposer = $this->fullTextSearchDataComposerFactory->create($entityType);

        $fullTextSearchData = $fullTextSearchDataComposer->compose(
            $filter,
            FullTextSearchDataComposerParams::create()
        );

        if ($this->isPerson($entityType)) {
            $selectList[] = 'firstName';
            $selectList[] = 'lastName';
        } else {
            $selectList[] = ['VALUE:', 'firstName'];
            $selectList[] = ['VALUE:', 'lastName'];
        }

        $statusField = $this->getStatusField($entityType);

        if ($statusField) {
            $selectList[] = [$statusField, 'status'];
        } else {
            $selectList[] = ['VALUE:', 'status'];
        }

        try {
            $query = $selectBuilder->build();
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException("", 0, $e);
        }

        $queryBuilder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->clone($query)
            ->limit(0, $offset + $maxSize + 1)
            ->select($selectList)
            ->order([]);

        if ($fullTextSearchData) {
            $hasFullTextSearch = true;

            $expression = $fullTextSearchData->getExpression();

            $queryBuilder
                ->select($expression, 'relevance')
                ->order($expression, Order::DESC);
        } else {
            $queryBuilder->select(Expr::value(1.1), 'relevance');
        }

        $queryBuilder->order($nameAttribute);

        return $queryBuilder->build();
    }

    private function isPerson(string $entityType): bool
    {
        $fieldDefs = $this->entityManager->getDefs()->getEntity($entityType);

        return $fieldDefs->tryGetField(Field::NAME)?->getType() === FieldType::PERSON_NAME;
    }

    private function getStatusField(string $entityType): ?string
    {
        return $this->metadata->get("scopes.$entityType.statusField");
    }
}
