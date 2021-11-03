<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\ORM\{
    Query\Select,
    Query\Part\Order,
};

use Espo\Core\{
    Di,
    Select\Text\FullTextSearchDataComposerFactory,
    Select\Text\FullTextSearchDataComposerParams,
    Select\SelectBuilderFactory,
};

use PDO;
use StdClass;

class GlobalSearch implements
    Di\EntityManagerAware,
    Di\MetadataAware,
    Di\AclAware,
    Di\ConfigAware
{
    use Di\EntityManagerSetter;
    use Di\MetadataSetter;
    use Di\AclSetter;
    use Di\ConfigSetter;

    protected $fullTextSearchDataComposerFactory;

    protected $selectBuilderFactory;

    public function __construct(
        FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        SelectBuilderFactory $selectBuilderFactory
    ) {
        $this->fullTextSearchDataComposerFactory = $fullTextSearchDataComposerFactory;
        $this->selectBuilderFactory = $selectBuilderFactory;
    }

    public function find(string $filter, int $offset, int $maxSize): StdClass
    {
        $entityTypeList = $this->config->get('globalSearchEntityList') ?? [];

        $hasFullTextSearch = false;

        $queryList = [];

        foreach ($entityTypeList as $i => $entityType) {
            $query = $this->getEntityTypeQuery($entityType, $i, $filter, $offset, $maxSize, $hasFullTextSearch);

            if (!$query) {
                continue;
            }

            $queryList[] = $query;
        }

        if (count($queryList) === 0) {
            return (object) [
                'total' => 0,
                'list' => [],
            ];
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

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $resultList = [];

        foreach ($rows as $row) {
            $entity = $this->entityManager
                ->getRDBRepository($row['entityType'])
                ->select(['id', 'name'])
                ->where(['id' => $row['id']])
                ->findOne();

            if (!$entity) {
                continue;
            }

            $itemData = $entity->getValueMap();
            $itemData->_scope = $entity->getEntityType();

            $resultList[] = $itemData;
        }

        $total = -2;

        if (count($resultList) > $maxSize) {
            $total = -1;

            unset($resultList[count($resultList) - 1]);
        }

        return (object) [
            'total' => $total,
            'list' => $resultList,
        ];
    }

    protected function getEntityTypeQuery(
        string $entityType,
        int $i,
        string $filter,
        int $offset,
        int $maxSize,
        bool &$hasFullTextSearch
    ): ?Select {

        if (!$this->acl->checkScope($entityType, 'read')) {
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
            FullTextSearchDataComposerParams::fromArray([])
        );

        $isPerson = $this->metadata->get([
            'entityDefs', $entityType, 'fields', 'name', 'type'
        ]) === 'personName';

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

            $queryBuilder->select($fullTextSearchData->getExpression(), 'relevance');

            $queryBuilder->order($fullTextSearchData->getExpression(), Order::DESC);
        }
        else {
            $queryBuilder->select('VALUE:1.1', 'relevance');
        }

        $queryBuilder->order('name');

        return $queryBuilder->build();
    }
}
