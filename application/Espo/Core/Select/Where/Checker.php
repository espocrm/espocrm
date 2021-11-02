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

namespace Espo\Core\Select\Where;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Acl;

use Espo\ORM\QueryComposer\BaseQueryComposer as QueryComposer;
use Espo\ORM\QueryComposer\Util as QueryUtil;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;

/**
 * Checks Where parameters. Throws an exception if anything not allowed is met.
 */
class Checker
{
    private $seed = null;

    private $entityType;

    private $entityManager;

    private $acl;

    private $nestingTypeList = [
        'or',
        'and',
        'subQueryIn',
        'subQueryNotIn',
        'not',
    ];

    private $subQueryTypeList = [
        'subQueryIn',
        'subQueryNotIn',
        'not',
    ];

    private $linkTypeList = [
        'inCategory',
        'isLinked',
        'isNotLinked',
        'linkedWith',
        'notLinkedWith',
        'linkedWithAll',
        'isUserFromTeams',
    ];

    public function __construct(string $entityType, EntityManager $entityManager, Acl $acl)
    {
        $this->entityType = $entityType;

        $this->entityManager = $entityManager;
        $this->acl = $acl;
    }

    public function check(Item $item, Params $params): void
    {
        $this->checkItem($item, $params);
    }

    private function checkItem(Item $item, Params $params): void
    {
        $type = $item->getType();
        $attribute = $item->getAttribute();
        $value = $item->getValue();

        $forbidComplexExpressions = $params->forbidComplexExpressions();
        $checkWherePermission = $params->applyPermissionCheck();

        if ($forbidComplexExpressions) {
            if (in_array($type, $this->subQueryTypeList)) {
                throw new Forbidden("Sub-queries are forbidden in where.");
            }
        }

        if ($attribute && $forbidComplexExpressions) {
            if (
                QueryUtil::isComplexExpression($attribute)
            ) {
                throw new Forbidden("Complex expressions are forbidden in where.");
            }
        }

        if ($attribute) {
            $argumentList = QueryComposer::getAllAttributesFromComplexExpression($attribute);

            foreach ($argumentList as $argument) {
                $this->checkAttributeExistence($argument, $type);

                if ($checkWherePermission) {
                    $this->checkAttributePermission($argument, $type);
                }
            }
        }

        if (in_array($type, $this->nestingTypeList) && is_array($value)) {
            foreach ($value as $subItem) {
                $this->checkItem(Item::fromRaw($subItem), $params);
            }
        }
    }

    private function checkAttributeExistence(string $attribute, string $type): void
    {
        if (strpos($attribute, '.') !== false) {
            // @todo Check existance of foreign attributes.
            return;
        }

        if (in_array($type, $this->linkTypeList)) {
            if (!$this->getSeed()->hasRelation($attribute)) {
                throw new BadRequest("Not existing relation '{$attribute}' in where.");
            }

            return;
        }

        if (!$this->getSeed()->hasAttribute($attribute)) {
            throw new BadRequest("Not existing attribute '{$attribute}' in where.");
        }
    }

    private function checkAttributePermission(string $attribute, string $type): void
    {
        $entityType = $this->entityType;

        if (strpos($attribute, '.') !== false) {
            list($link, $attribute) = explode('.', $attribute);

            if (!$this->getSeed()->hasRelation($link)) {
                // TODO allow alias
                throw new Forbidden("Bad relation '{$link}' in where.");
            }

            $foreignEntityType = $this->getRelationParam($this->getSeed(), $link, 'entity');

            if (!$foreignEntityType) {
                throw new Forbidden("Bad relation '{$link}' in where.");
            }

            if (
                !$this->acl->checkScope($foreignEntityType) ||
                in_array($link, $this->acl->getScopeForbiddenLinkList($entityType))
            ) {
                throw new Forbidden("Forbidden relation '{$link}' in where.");
            }

            if (in_array($attribute, $this->acl->getScopeForbiddenAttributeList($foreignEntityType))) {
                throw new Forbidden("Forbidden attribute '{$link}.{$attribute}' in where.");
            }

            return;
        }

        if (in_array($type, $this->linkTypeList)) {
            $link = $attribute;

            if (!$this->getSeed()->hasRelation($link)) {
                throw new Forbidden("Bad relation '{$link}' in where.");
            }

            $foreignEntityType = $this->getRelationParam($this->getSeed(), $link, 'entity');

            if (!$foreignEntityType) {
                throw new Forbidden("Bad relation '{$link}' in where.");
            }

            if (
                in_array($link, $this->acl->getScopeForbiddenFieldList($entityType)) ||
                !$this->acl->checkScope($foreignEntityType) ||
                in_array($link, $this->acl->getScopeForbiddenLinkList($entityType))
            ) {
                throw new Forbidden("Forbidden relation '{$link}' in where.");
            }

            return;
        }

        if (in_array($attribute, $this->acl->getScopeForbiddenAttributeList($entityType))) {
            throw new Forbidden("Forbidden attribute '{$attribute}' in where.");
        }
    }

    private function getSeed(): Entity
    {
        return $this->seed ?? $this->entityManager->getEntity($this->entityType);
    }

    /**
     * @return mixed
     */
    private function getRelationParam(Entity $entity, string $relation, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }
}
