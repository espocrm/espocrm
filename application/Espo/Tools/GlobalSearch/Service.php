<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\GlobalSearch;

use Espo\Core\Acl;
use Espo\Core\Record\Collection;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Expression as Expr;

use Espo\Core\Select\Text\FullTextSearch\DataComposerFactory as FullTextSearchDataComposerFactory;
use Espo\Core\Select\Text\FullTextSearch\DataComposer\Params as FullTextSearchDataComposerParams;
use Espo\Core\Select\SelectBuilderFactory;

class Service
{
    private FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory;
    private SelectBuilderFactory $selectBuilderFactory;
    private EntityManager $entityManager;
    private Metadata $metadata;
    private Acl $acl;
    private Config $config;

    public function __construct(
        FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        SelectBuilderFactory $selectBuilderFactory,
        EntityManager $entityManager,
        Metadata $metadata,
        Acl $acl,
        Config $config
    ) {
        $this->fullTextSearchDataComposerFactory = $fullTextSearchDataComposerFactory;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->config = $config;
    }

    /**
     * @param string $filter
     * @param int $offset
     * @param int $maxSize
     * @return Collection<Entity>
     */
    public function find(string $filter, int $offset, int $maxSize): Collection
    {
        $entityTypeList = $this->config->get('globalSearchEntityList') ?? [];

        $hasFullTextSearch = false;

        $queryList = [];

        foreach ($entityTypeList as $i => $entityType) {
            $query = $this->getEntityTypeQuery(
                $entityType,
                $i,
                $filter,
                $offset,
                $maxSize,
                $hasFullTextSearch
            );

            if (!$query) {
                continue;
            }

            $queryList[] = $query;
        }

        if (count($queryList) === 0) {
            return new Collection(new EntityCollection(), 0);
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union()
            ->all()
            ->limit($offset, $maxSize + 1);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        if ($hasFullTextSearch) {
            $builder->order('relevance', 'DESC');
        }

        $builder->order('order', 'DESC');
        $builder->order('name', 'ASC');

        $unionQuery = $builder->build();

        $collection = new EntityCollection();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        while ($row = $sth->fetch()) {
            $entity = $this->entityManager
                ->getRDBRepository($row['entityType'])
                ->select(['id', 'name'])
                ->where(['id' => $row['id']])
                ->findOne();

            if (!$entity) {
                continue;
            }

            $collection->append($entity);
        }

        return Collection::createNoCount($collection, $maxSize);
    }

    protected function getEntityTypeQuery(
        string $entityType,
        int $i,
        string $filter,
        int $offset,
        int $maxSize,
        bool &$hasFullTextSearch
    ): ?Select {

        if (!$this->acl->checkScope($entityType, Acl\Table::ACTION_READ)) {
            return null;
        }

        if (!$this->metadata->get(['scopes', $entityType])) {
            return null;
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withStrictAccessControl()
            ->withTextFilter($filter);

        $selectList = [
            'id',
            'name',
            ['VALUE:' . $entityType, 'entityType'],
            [(string) $i, 'order'],
        ];

        $fullTextSearchDataComposer = $this->fullTextSearchDataComposerFactory->create($entityType);

        $fullTextSearchData = $fullTextSearchDataComposer->compose(
            $filter,
            FullTextSearchDataComposerParams::create()
        );

        $isPerson = $this->metadata
            ->get(['entityDefs', $entityType, 'fields', 'name', 'type']) === 'personName';

        if ($isPerson) {
            $selectList[] = 'firstName';
            $selectList[] = 'lastName';
        }
        else {
            $selectList[] = ['VALUE:', 'firstName'];
            $selectList[] = ['VALUE:', 'lastName'];
        }

        $query = $selectBuilder->build();

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
        }
        else {
            $queryBuilder->select(Expr::value(1.1), 'relevance');
        }

        $queryBuilder->order('name');

        return $queryBuilder->build();
    }
}
