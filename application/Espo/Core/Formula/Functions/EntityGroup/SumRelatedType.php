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

namespace Espo\Core\Formula\Functions\EntityGroup;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class SumRelatedType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('selectManagerFactory');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 2) {
            throw new Error();
        }

        $link = $this->evaluate($item->value[0]);

        if (empty($link)) {
            throw new Error("No link passed to sumRelated function.");
        }

        $field = $this->evaluate($item->value[1]);

        if (empty($field)) {
            throw new Error("No field passed to sumRelated function.");
        }

        $filter = null;
        if (count($item->value) > 2) {
            $filter = $this->evaluate($item->value[2]);
        }

        $entity = $this->getEntity();

        $entityManager = $this->getInjection('entityManager');

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $foreignSelectManager = $this->getInjection('selectManagerFactory')->create($foreignEntityType);

        $foreignLink = $entity->getRelationParam($link, 'foreign');
        $foreignLinkAlias = $foreignLink . 'SumRelated';

        if (empty($foreignLink)) {
            throw new Error("No foreign link for link {$link}.");
        }

        $selectParams = $foreignSelectManager->getEmptySelectParams();

        if ($filter) {
            $foreignSelectManager->applyFilter($filter, $selectParams);
        }

        $selectParams['select'] = [[$foreignLinkAlias . '.id', 'foreignId'], 'SUM:' . $field];

        if ($entity->getRelationType($link) === 'hasChildren') {
            $foreignSelectManager->addJoin([
                $entity->getEntityType(),
                $foreignLinkAlias,
                [
                     $foreignLinkAlias . '.id:' => $foreignLink . 'Id',
                    'deleted' => false,
                    $foreignLinkAlias . '.id!=' => null,
                ]
            ], $selectParams);
            $selectParams['whereClause'][] = [$foreignLink . 'Type'  => $entity->getEntityType()];

        } else {
            $foreignSelectManager->addJoin([$foreignLink, $foreignLinkAlias], $selectParams);
        }

        if (!empty($selectParams['distinct'])) {
            $sqSelectParams = $selectParams;

            $sqSelectParams['whereClause'][] = [
                $foreignLinkAlias . '.id' => $entity->id
            ];

            $sqSelectParams['select'] = ['id'];
            unset($sqSelectParams['distinct']);
            unset($sqSelectParams['orderBy']);
            unset($sqSelectParams['order']);

            $selectParams['whereClause'][] = [
                'id=s' => [
                    'entityType' => $foreignEntityType,
                    'selectParams' => $sqSelectParams,
                ]
            ];
        } else {
            $selectParams['whereClause'][] = [
                $foreignLinkAlias . '.id' => $entity->id
            ];
        }

        $selectParams['groupBy'] = [$foreignLinkAlias . '.id'];

        $entityManager->getRepository($foreignEntityType)->handleSelectParams($selectParams);

        $sql = $entityManager->getQuery()->createSelectQuery($foreignEntityType, $selectParams);

        $pdo = $entityManager->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rowList)) {
            return 0;
        }

        return floatval($rowList[0]['SUM:' . $field]);
    }
}