<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Select\Where;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Acl;
use Espo\Core\Select\Helpers\EntityHelper;
use Espo\Core\Select\Where\Item\Type;
use Espo\Entities\Team;
use Espo\ORM\QueryComposer\Util;
use Espo\ORM\QueryComposer\Util as QueryUtil;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\Type\RelationType;

/**
 * Checks Where parameters. Throws an exception if anything not allowed is met.
 *
 * @todo Check read access to foreign entity for belongs-to, belongs-to-parent, has-one.
 */
class Checker
{
    private ?Entity $seed = null;

    private const TYPE_IN_CATEGORY = 'inCategory';
    private const TYPE_IS_USER_FROM_TEAMS = 'isUserFromTeams';

    /** @var string[] */
    private $nestingTypeList = [
        Type::OR,
        Type::AND,
        Type::NOT,
        Type::SUBQUERY_IN,
        Type::SUBQUERY_NOT_IN,
    ];

    /** @var string[] */
    private $subQueryTypeList = [
        Type::SUBQUERY_IN,
        Type::SUBQUERY_NOT_IN,
        Type::NOT,
    ];

    /** @var string[] */
    private $linkTypeList = [
        self::TYPE_IN_CATEGORY,
        self::TYPE_IS_USER_FROM_TEAMS,
        Type::IS_LINKED_WITH,
        Type::IS_NOT_LINKED_WITH,
        Type::IS_LINKED_WITH_ALL,
        Type::IS_LINKED_WITH_ANY,
        Type::IS_LINKED_WITH_NONE,
    ];

    /** @var string[] */
    private $linkWithIdsTypeList = [
        self::TYPE_IN_CATEGORY,
        self::TYPE_IS_USER_FROM_TEAMS,
        Type::IS_LINKED_WITH,
        Type::IS_NOT_LINKED_WITH,
        Type::IS_LINKED_WITH_ALL,
    ];

    public function __construct(
        private string $entityType,
        private EntityManager $entityManager,
        private Acl $acl,
        private Acl\SystemRestriction $systemRestriction,
        private EntityHelper $entityHelper,
    ) {}

    /**
     * Check.
     *
     * @throws Forbidden
     * @throws BadRequest
     */
    public function check(Item $item, Params $params): void
    {
        $this->checkItem($item, $params);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
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
            if (QueryUtil::isComplexExpression($attribute)) {
                throw new Forbidden("Complex expressions are forbidden in where.");
            }
        }

        if ($attribute) {
            $argumentList = Util::getAllAttributesFromComplexExpression($attribute);

            foreach ($argumentList as $argument) {
                $this->checkAttributeExistence($argument, $type);

                $this->checkAttributePermission($argument, $type, $value, $checkWherePermission);
            }
        }

        if (in_array($type, $this->nestingTypeList) && is_array($value)) {
            foreach ($value as $subItem) {
                $this->checkItem(Item::fromRaw($subItem), $params);
            }
        }
    }

    /**
     * @throws BadRequest
     */
    private function checkAttributeExistence(string $attribute, string $type): void
    {
        if (str_contains($attribute, '.')) {
            // @todo Check existence of foreign attributes.
            return;
        }

        if (in_array($type, $this->linkTypeList)) {
            if (!$this->getSeed()->hasRelation($attribute)) {
                throw new BadRequest("Not existing relation '$attribute' in where.");
            }

            return;
        }

        if (!$this->getSeed()->hasAttribute($attribute)) {
            throw new BadRequest("Not existing attribute '$attribute' in where.");
        }
    }

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    private function checkAttributePermission(string $attribute, string $type, mixed $value, bool $aclCheck): void
    {
        $entityType = $this->entityType;

        if (str_contains($attribute, '.')) {
            [$link, $attribute] = explode('.', $attribute);

            $this->checkAttributePermissionWithLink(
                entityType: $entityType,
                aclCheck: $aclCheck,
                link: $link,
                attribute: $attribute,
            );

            return;
        }

        if (in_array($type, $this->linkTypeList)) {
            $this->checkLink(
                type: $type,
                entityType: $entityType,
                link: $attribute,
                value: $value,
                aclCheck: $aclCheck,
            );

            return;
        }

        if (!$aclCheck) {
            $this->assertAttributeSystemRead($entityType, $attribute);

            return;
        }

        if (in_array($attribute, $this->acl->getScopeForbiddenAttributeList($entityType))) {
            throw new Forbidden("Forbidden attribute '$attribute' in where.");
        }
    }

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    private function checkAttributePermissionWithLink(
        string $entityType,
        bool $aclCheck,
        string $link,
        string $attribute,
    ): void {

        if (!$link) {
            throw new BadRequest("Empty relation in path in where.");
        }

        if (!$attribute) {
            throw new BadRequest("Empty attribute in path in where.");
        }

        if (!$this->getSeed()->hasRelation($link)) {
            // TODO allow alias
            throw new Forbidden("Bad relation '$link' in where.");
        }

        $foreignEntityType = $this->getRelationEntityType($this->getSeed(), $link);

        if (!$aclCheck) {
            $this->assertLinkSystemRead($entityType, $link);

            if ($this->getSeed()->getRelationType($link) === RelationType::BELONGS_TO_PARENT) {
                $entityTypeList = $this->entityManager
                    ->getDefs()
                    ->getEntity($entityType)
                    ->tryGetField($link)
                    ?->getParam('entityList') ?? [];

                foreach ($entityTypeList as $foreignEntityType) {
                    $this->assertAttributeRead($foreignEntityType, $attribute, $link);
                }

                return;
            }

            if (!$foreignEntityType) {
                throw new Forbidden("Bad relation '$link' in where.");
            }

            $this->assertAttributeRead($foreignEntityType, $attribute, $link);

            return;
        }

        if (!$foreignEntityType) {
            throw new Forbidden("Bad relation '$link' in where.");
        }

        if (
            !$this->acl->checkScope($foreignEntityType) ||
            in_array($link, $this->acl->getScopeForbiddenLinkList($entityType))
        ) {
            throw new Forbidden("Forbidden relation '$link' in where.");
        }

        if (in_array($attribute, $this->acl->getScopeForbiddenAttributeList($foreignEntityType))) {
            throw new Forbidden("Forbidden attribute '$link.$attribute' in where.");
        }
    }

    private function getSeed(): Entity
    {
        $this->seed ??= $this->entityManager->getNewEntity($this->entityType);

        return $this->seed;
    }

    private function getRelationEntityType(Entity $entity, string $relation): ?string
    {
        return $this->entityHelper->getRelationEntityType($entity, $relation);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function checkLink(
        string $type,
        string $entityType,
        string $link,
        mixed $value,
        bool $aclCheck,
    ): void {

        if (!$this->getSeed()->hasRelation($link)) {
            throw new Forbidden("Bad relation '$link' in where.");
        }

        $foreignEntityType = $this->getRelationEntityType($this->getSeed(), $link);

        if (!$foreignEntityType) {
            throw new Forbidden("Bad relation '$link' in where, no foreign entity type.");
        }

        if ($type === self::TYPE_IS_USER_FROM_TEAMS) {
            $foreignEntityType = Team::ENTITY_TYPE;
        }

        if ($aclCheck) {
            if (
                in_array($link, $this->acl->getScopeForbiddenFieldList($entityType)) ||
                !$this->acl->checkScope($foreignEntityType) ||
                in_array($link, $this->acl->getScopeForbiddenLinkList($entityType))
            ) {
                throw new Forbidden("Forbidden link '$link' in where.");
            }
        } else {
            $this->assertLinkSystemRead($entityType, $link);
            $this->assertFieldSystemRead($entityType, $link);
        }

        if (!in_array($type, $this->linkWithIdsTypeList)) {
            return;
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $it) {
            if (!is_string($it)) {
                throw new BadRequest("Bad where item. Non-string ID.");
            }
        }

        // @todo Use the Select Builder instead. Check the result count equal the input IDs count.

        foreach ($value as $id) {
            $entity = $this->entityManager->getEntityById($foreignEntityType, $id);

            if (!$entity) {
                throw new Forbidden("Record '$foreignEntityType' `$id` not found.");
            }

            if ($aclCheck && !$this->acl->checkEntityRead($entity)) {
                throw new Forbidden("No access to '$foreignEntityType' `$id`.");
            }
        }
    }

    /**
     * @throws Forbidden
     */
    private function assertLinkSystemRead(string $entityType, string $link): void
    {
        if (!$this->systemRestriction->checkLinkRead($entityType, $link)) {
            throw new Forbidden("System restricted link '$link' in where.");
        }
    }

    /**
     * @throws Forbidden
     */
    private function assertFieldSystemRead(string $entityType, string $field): void
    {
        if (!$this->systemRestriction->checkFieldRead($entityType, $field)) {
            throw new Forbidden("System restricted field '$field' in where.");
        }
    }

    /**
     * @throws Forbidden
     */
    private function assertAttributeSystemRead(string $entityType, string $attribute): void
    {
        if (!$this->systemRestriction->checkAttributeRead($entityType, $attribute)) {
            throw new Forbidden("System restricted attribute '$attribute' in where.");
        }
    }

    /**
     * @throws Forbidden
     */
    private function assertAttributeRead(string $entityType, string $attribute, string $link): void
    {
        if (!$this->systemRestriction->checkAttributeRead($entityType, $attribute)) {
            throw new Forbidden("System restricted attribute '$link.$attribute' in where.");
        }
    }
}
