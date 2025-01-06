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

namespace Espo\Tools\GlobalSearch;

use Espo\Core\Acl;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\Collection;
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

class Service
{
    public function __construct(
        private FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Acl $acl,
        private Config $config
    ) {}

    /**
     * @param string $filter A search query.
     * @param int $offset An offset.
     * @param ?int $maxSize A limit.
     * @return Collection<Entity>
     */
    public function find(string $filter, int $offset = 0, ?int $maxSize = null): Collection
    {
        $entityTypeList = $this->config->get('globalSearchEntityList') ?? [];
        $maxSize ??= (int) $this->config->get('recordsPerPage');

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
        $builder->order(Field::NAME, 'ASC');

        $unionQuery = $builder->build();

        $collection = new EntityCollection();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        while ($row = $sth->fetch()) {
            $entity = $this->entityManager
                ->getRDBRepository($row['entityType'])
                ->select([Attribute::ID, Field::NAME])
                ->where([Attribute::ID => $row[Attribute::ID]])
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

        $entityDefs = $this->entityManager->getDefs()->getEntity($entityType);

        $nameAttribute = $entityDefs->hasField('name') ?
            'name' : 'id';

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

        $isPerson = $this->metadata
            ->get(['entityDefs', $entityType, 'fields', Field::NAME, 'type']) === FieldType::PERSON_NAME;

        if ($isPerson) {
            $selectList[] = 'firstName';
            $selectList[] = 'lastName';
        } else {
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
        } else {
            $queryBuilder->select(Expr::value(1.1), 'relevance');
        }

        $queryBuilder->order($nameAttribute);

        return $queryBuilder->build();
    }
}
