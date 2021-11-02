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

namespace Espo\Core\Formula\Functions\EntityGroup;

use Espo\Core\Exceptions\Error;

use Espo\Core\Di;

use stdClass;
use PDO;

class SumRelatedType extends \Espo\Core\Formula\Functions\Base implements
    Di\EntityManagerAware,
    Di\SelectBuilderFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\SelectBuilderFactorySetter;

    public function process(stdClass $item)
    {
        if (count($item->value) < 2) {
            throw new Error("sumRelated: Too few arguments.");
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

        $entityManager = $this->entityManager;

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $foreignLink = $entity->getRelationParam($link, 'foreign');
        $foreignLinkAlias = $foreignLink . 'SumRelated';

        if (empty($foreignLink)) {
            throw new Error("No foreign link for link {$link}.");
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType);

        if ($filter) {
            $builder->withPrimaryFilter($filter);
        }

        $queryBuilder = $builder->buildQueryBuilder();

        $queryBuilder->select([
            [$foreignLinkAlias . '.id', 'foreignId'],
            'SUM:' . $field,
        ]);

        if ($entity->getRelationType($link) === 'hasChildren') {
            $queryBuilder
                ->join(
                    $entity->getEntityType(),
                    $foreignLinkAlias,
                    [
                         $foreignLinkAlias . '.id:' => $foreignLink . 'Id',
                        'deleted' => false,
                        $foreignLinkAlias . '.id!=' => null,
                    ]
                )
                ->where([
                    $foreignLink . 'Type'  => $entity->getEntityType(),
                ]);
        }
        else {
            $queryBuilder->join($foreignLink, $foreignLinkAlias);
        }

        $queryBuilder->where([
            $foreignLinkAlias . '.id' => $entity->id,
        ]);

        if ($queryBuilder->build()->isDistinct()) {
            // Use a sub-query to weed out duplicate rows.

            $sqQueryBuilder = clone $queryBuilder;

            $sqQueryBuilder
                ->order([])
                ->select(['id']);

            $queryBuilder->where([
                'id=s' => $sqQueryBuilder->build()->getRaw(),
            ]);
        }

        $queryBuilder->group($foreignLinkAlias . '.id');

        $sth = $entityManager->getQueryExecutor()->execute($queryBuilder->build());

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rowList)) {
            return 0;
        }

        $stringValue = $rowList[0]['SUM:' . $field];

        return floatval($stringValue);
    }
}
