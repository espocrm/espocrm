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

namespace Espo\Core\Formula\Functions\EntityGroup;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Di;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\Functions\Base;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use stdClass;
use PDO;

class SumRelatedType extends Base implements
    Di\EntityManagerAware,
    Di\SelectBuilderFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\SelectBuilderFactorySetter;

    /**
     * @return float
     * @throws Error
     */
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

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $foreignLink = $entity->getRelationParam($link, RelationParam::FOREIGN);
        $foreignLinkAlias = $foreignLink . 'SumRelated';

        if (empty($foreignLink)) {
            throw new Error("No foreign link for link {$link}.");
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType);

        if ($filter) {
            (new FindQueryUtil())->applyFilter($builder, $filter, 3);
        }

        try {
            $queryBuilder = $builder->buildQueryBuilder();
        } catch (BadRequest|Forbidden $e) {
            throw new Error($e->getMessage(), $e->getCode(), $e);
        }

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
                        Attribute::DELETED => false,
                        $foreignLinkAlias . '.id!=' => null,
                    ]
                )
                ->where([
                    $foreignLink . 'Type'  => $entity->getEntityType(),
                ]);
        } else {
            $queryBuilder->join($foreignLink, $foreignLinkAlias);
        }

        $queryBuilder->where([
            $foreignLinkAlias . '.id' => $entity->getId(),
        ]);

        if ($queryBuilder->build()->isDistinct()) {
            // Use a sub-query to weed out duplicate rows.

            $sqQueryBuilder = clone $queryBuilder;

            $sqQueryBuilder
                ->order([])
                ->select([Attribute::ID]);

            $queryBuilder->where([
                'id=s' => $sqQueryBuilder->build(),
            ]);
        }

        $queryBuilder->group($foreignLinkAlias . '.id');

        $sth = $entityManager->getQueryExecutor()->execute($queryBuilder->build());

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rowList)) {
            return 0.0;
        }

        $stringValue = $rowList[0]['SUM:' . $field];

        return floatval($stringValue);
    }
}
