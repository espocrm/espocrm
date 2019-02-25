<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class GlobalSearch extends \Espo\Core\Services\Base
{
    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'entityManager',
            'user',
            'metadata',
            'acl',
            'selectManagerFactory',
            'config'
        ]);
    }

    protected function getSelectManagerFactory()
    {
        return $this->injections['selectManagerFactory'];
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    public function find($query, $offset, $maxSize)
    {
        $entityTypeList = $this->getConfig()->get('globalSearchEntityList');

        $hasFullTextSearch = false;

        $relevanceSelectPosition = 0;

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getAcl()->checkScope($entityType, 'read')) {
                continue;
            }
            if (!$this->getMetadata()->get(['scopes', $entityType])) {
                continue;
            }

            $selectManager = $this->getSelectManagerFactory()->create($entityType);

            $params = [
                'select' => ['id', 'name', ['VALUE:' . $entityType, 'entityType']]
            ];

            $fullTextSearchData = $selectManager->getFullTextSearchDataForTextFilter($query);

            if ($fullTextSearchData) {
                $hasFullTextSearch = true;
                $params['select'][] = [$fullTextSearchData['where'], '_relevance'];
            } else {
                $params['select'][] = ['VALUE:1.1', '_relevance'];
                $relevanceSelectPosition = count($params['select']);
            }

            $selectManager->manageAccess($params);
            $params['useFullTextSearch'] = true;
            $selectManager->applyTextFilter($query, $params);

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $params);

            $unionPartList[] = '' . $sql . '';
        }
        if (empty($unionPartList)) {
            return [
                'total' => 0,
                'list' => []
            ];
        }

        $pdo = $this->getEntityManager()->getPDO();

        $unionSql = implode(' UNION ', $unionPartList);
        $countSql = "SELECT COUNT(*) AS 'COUNT' FROM ({$unionSql}) AS c";
        $sth = $pdo->prepare($countSql);
        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $totalCount = $row['COUNT'];

        if (count($entityTypeList)) {
            $entityListQuoted = [];
            foreach ($entityTypeList as $entityType) {
                $entityListQuoted[] = $pdo->quote($entityType);
            }
            if ($hasFullTextSearch) {
                $unionSql .= " ORDER BY " . $relevanceSelectPosition . " DESC, FIELD(entityType, ".implode(', ', $entityListQuoted)."), name";
            } else {
                $unionSql .= " ORDER BY FIELD(entityType, ".implode(', ', $entityListQuoted)."), name";
            }
        } else {
            $unionSql .= " ORDER BY name";
        }

        $unionSql .= " LIMIT :offset, :maxSize";

        $sth = $pdo->prepare($unionSql);

        $sth->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $sth->bindParam(':maxSize', $maxSize, \PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['id']);
            $entityData = $entity->toArray();
            $entityData['_scope'] = $entity->getEntityType();
            $entityDataList[] = $entityData;
        }

        return array(
            'total' => $totalCount,
            'list' => $entityDataList,
        );
    }
}

