<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\{
    Forbidden,
    NotFound
};

use Espo\ORM\{
    Entity,
    EntityManager,
};

use Espo\Core\{
    Acl,
    SelectManagerFactory,
    Utils\Metadata,
    Utils\Config,
};

class GlobalSearch
{
    public function __construct(
        EntityManager $entityManager,
        Metadata $metadata,
        Acl $acl,
        SelectManagerFactory $selectManagerFactory,
        Config $config
    ) {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->selectManagerFactory = $selectManagerFactory;
        $this->config = $config;
    }

    public function find(string $query, int $offset, int $maxSize)
    {
        $entityTypeList = $this->config->get('globalSearchEntityList') ?? [];
        $hasFullTextSearch = false;
        $relevanceSelectPosition = 4;

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->acl->checkScope($entityType, 'read')) continue;
            if (!$this->metadata->get(['scopes', $entityType])) continue;

            $selectManager = $this->selectManagerFactory->create($entityType);

            $selectParams = [
                'select' => ['id', 'name', ['VALUE:' . $entityType, 'entityType']],
            ];

            $fullTextSearchData = $selectManager->getFullTextSearchDataForTextFilter($query);

            if ($this->metadata->get(['entityDefs', $entityType, 'fields', 'name', 'type']) === 'personName') {
                $selectParams['select'][] = 'firstName';
                $selectParams['select'][] = 'lastName';
            } else {
                $selectParams['select'][] = ['VALUE:', 'firstName'];
                $selectParams['select'][] = ['VALUE:', 'lastName'];
            }

            $selectParams['offset'] = 0;
            $selectParams['limit'] = $offset + $maxSize + 1;

            $selectManager->manageAccess($selectParams);
            $selectParams['useFullTextSearch'] = true;
            $selectManager->applyTextFilter($query, $selectParams);

            unset($selectParams['additionalSelect']);

            if ($fullTextSearchData) {
                $hasFullTextSearch = true;
                $selectParams['select'][] = [$fullTextSearchData['where'], '_relevance'];
                $selectParams['orderBy'] = [[$fullTextSearchData['where'], 'desc'], ['name']];
            } else {
                $selectParams['select'][] = ['VALUE:1.1', '_relevance'];
                $selectParams['orderBy'] = [['name']];
            }

            $itemSql = $this->entityManager->getQuery()->createSelectQuery($entityType, $selectParams);

            $unionPartList[] = "(\n" . $itemSql . "\n)";
        }
        if (empty($unionPartList)) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }

        $pdo = $this->entityManager->getPDO();

        $sql = implode(' UNION ALL ', $unionPartList);

        if (count($entityTypeList)) {
            $entityListQuoted = [];
            foreach ($entityTypeList as $entityType) {
                $entityListQuoted[] = $pdo->quote($entityType);
            }
            if ($hasFullTextSearch) {
                $sql .= "\nORDER BY " . $relevanceSelectPosition . " DESC, FIELD(entityType, ".implode(', ', $entityListQuoted)."), name";
            } else {
                $sql .= "\nORDER BY FIELD(entityType, ".implode(', ', $entityListQuoted)."), name";
            }
        } else {
            $sql .= "\nORDER BY name";
        }

        $sql = $this->entityManager->getQuery()->limit($sql, $offset, $maxSize + 1);

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $resultList = [];

        foreach ($rows as $row) {
            $entity = $this->entityManager->getRepository($row['entityType'])
                ->select(['id', 'name'])
                ->where(['id' => $row['id']])
                ->findOne();
            if (!$entity) continue;
            $itemData = $entity->getValueMap();
            $itemData->_scope = $entity->getEntityType();
            $resultList[] = $itemData;
        }

        $total = -2;
        if (count($resultList) > $maxSize) {
            $total = -1;
            unset($resultList[count($resultList) - 1]);
        }

        return [
            'total' => $total,
            'list' => $resultList,
        ];
    }
}
