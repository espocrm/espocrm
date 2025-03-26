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

namespace Espo\Core\Record\Access;

use Espo\Core\Acl;
use Espo\Core\Acl\LinkChecker;
use Espo\Core\Acl\LinkChecker\LinkCheckerFactory;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\Error\Body as ErrorBody;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\InjectableFactory;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Defs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;
use stdClass;

/**
 * Check access for record linking. When linking directly through relationships or via link fields.
 * Also loads foreign name attributes.
 */
class LinkCheck
{
    /** @var array<string, LinkChecker<Entity, Entity>> */
    private $linkCheckerCache = [];

    /** @var string[] */
    private array $oneFieldTypeList = [
        FieldType::LINK,
        FieldType::LINK_PARENT,
        FieldType::LINK_ONE,
        FieldType::FILE,
        FieldType::IMAGE,
    ];

    /** @var string[] */
    private array $manyFieldTypeList = [
        FieldType::LINK_MULTIPLE,
        FieldType::ATTACHMENT_MULTIPLE,
    ];

    public function __construct(
        private Defs $ormDefs,
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private User $user,
    ) {}

    /**
     * Checks relation fields set in an entity (link-multiple, link and others).
     *
     * @throws Forbidden
     */
    public function processFields(Entity $entity): void
    {
        $this->processLinkMultipleFields($entity);
        $this->processLinkFields($entity);
    }

    /**
     * @throws Forbidden
     */
    private function processLinkMultipleFields(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $typeList = [
            Entity::HAS_MANY,
            Entity::MANY_MANY,
            Entity::HAS_CHILDREN,
        ];

        foreach ($entityDefs->getRelationList() as $relationDefs) {
            $name = $relationDefs->getName();

            if (!in_array($relationDefs->getType(), $typeList)) {
                continue;
            }

            $attribute = $name . 'Ids';
            $namesAttribute = $name . 'Names';

            if (
                !$entityDefs->hasAttribute($attribute) ||
                !$entity->isAttributeChanged($attribute)
            ) {
                continue;
            }

            /** @var string[] $ids */
            $ids = $entity->get($attribute) ?? [];
            /** @var string[] $oldIds */
            $oldIds = $entity->getFetched($attribute) ?? [];

            $setIds = $ids;
            $ids = array_values(array_diff($ids, $oldIds));
            $removedIds = array_values(array_diff($oldIds, $ids));

            if ($ids === [] && $removedIds === []) {
                continue;
            }

            $this->processCheckLinkWithoutField($entityDefs, $name, false, $setIds);

            $names = $this->prepareNames($entity, $namesAttribute, $setIds);

            foreach ($ids as $id) {
                $foreignEntity = $this->processLinkedRecordsCheckItem($entity, $relationDefs, $id);

                if ($foreignEntity) {
                    $names->$id = $foreignEntity->get(Field::NAME);
                }
            }

            if (!$entityDefs->tryGetAttribute($namesAttribute)?->getParam(AttributeParam::IS_LINK_MULTIPLE_NAME_MAP)) {
                continue;
            }

            $entity->set($namesAttribute, $names);
        }
    }

    /**
     * @param ?string[] $ids
     * @throws Forbidden
     */
    private function processCheckLinkWithoutField(
        EntityDefs $entityDefs,
        string $name,
        bool $isOne,
        ?array $ids = null
    ): void {

        $fieldTypes = $isOne ? $this->oneFieldTypeList : $this->manyFieldTypeList;

        $hasField =
            $entityDefs->hasField($name) &&
            in_array($entityDefs->getField($name)->getType(), $fieldTypes);

        if ($hasField) {
            return;
        }

        if ($isOne) {
            throw new ForbiddenSilent("Cannot set ID attribute for link '$name' as there's no link field.");
        }

        if ($ids !== null && count($ids) > 1) {
            throw new ForbiddenSilent("Cannot set multiple IDs for link '$name' as there's no link-multiple field.");
        }

        $forbiddenLinkList = $this->acl->getScopeForbiddenLinkList($entityDefs->getName(), AclTable::ACTION_EDIT);

        if (!in_array($name, $forbiddenLinkList)) {
            return;
        }

        throw ForbiddenSilent::createWithBody(
            "No access to link $name.",
            ErrorBody::create()
                ->withMessageTranslation('cannotRelateForbiddenLink', null, ['link' => $name])
                ->encode()
        );
    }

    /**
     * @throws Forbidden
     */
    private function processLinkedRecordsCheckItem(
        Entity $entity,
        RelationDefs $defs,
        string $id,
        bool $isOne = false
    ): ?Entity {

        $entityType = $entity->getEntityType();
        $link = $defs->getName();

        if ($this->getParam($entityType, $link, 'linkCheckDisabled')) {
            return null;
        }

        $foreignEntityType = null;

        if ($defs->getType() === RelationType::BELONGS_TO_PARENT) {
            $foreignEntityType = $entity->get($link . 'Type');
        }

        if (!$foreignEntityType && !$defs->hasForeignEntityType()) {
            return null;
        }

        $foreignEntityType ??= $defs->getForeignEntityType();

        $foreignEntity = $this->entityManager->getEntityById($foreignEntityType, $id);

        if (!$foreignEntity) {
            throw ForbiddenSilent::createWithBody(
                "Can't relate with non-existing record. entity type: $entityType, link: $link.",
                ErrorBody::create()
                    ->withMessageTranslation(
                        'cannotRelateNonExisting', null, ['foreignEntityType' => $foreignEntityType])
                    ->encode()
            );
        }

        $toSkip = $this->linkForeignAccessCheck($isOne, $entityType, $link, $foreignEntity);

        if ($toSkip) {
            return $foreignEntity;
        }

        $this->linkEntityAccessCheck($entity, $foreignEntity, $link);

        return $foreignEntity;
    }

    /**
     * @throws Forbidden
     */
    private function linkForeignAccessCheck(
        bool $isOne,
        string $entityType,
        string $link,
        Entity $foreignEntity
    ): bool {

        if ($isOne) {
            return $this->linkForeignAccessCheckOne($entityType, $link, $foreignEntity);
        }

        return $this->linkForeignAccessCheckMany($entityType, $link, $foreignEntity, true);
    }

    private function getParam(string $entityType, string $link, string $param): mixed
    {
        return $this->metadata->get(['recordDefs', $entityType, 'relationships', $link, $param]);
    }

    /**
     * Check access to a specific link.
     *
     * @throws Forbidden
     */
    public function processLink(Entity $entity, string $link): void
    {
        $entityType = $entity->getEntityType();

        /** @var AclTable::ACTION_*|null $action */
        $action = $this->getParam($entityType, $link, 'linkRequiredAccess');

        if (!$action) {
            $action = AclTable::ACTION_EDIT;
        }

        if (!$this->acl->check($entity, $action)) {
            throw ForbiddenSilent::createWithBody(
                "No record access for link operation ($entityType:$link).",
                ErrorBody::create()
                    ->withMessageTranslation('noAccessToRecord', null, ['action' => $action])
                    ->encode()
            );
        }
    }

    /**
     * Check unlink access to a specific link.
     *
     * @throws Forbidden
     */
    public function processUnlink(Entity $entity, string $link): void
    {
        $this->processLink($entity, $link);
    }

    /**
     * Check link access for a specific foreign entity.
     *
     * @throws Forbidden
     */
    public function processLinkForeign(Entity $entity, string $link, Entity $foreignEntity): void
    {
        $this->processLinkForeignInternal($entity, $link, $foreignEntity);
        $this->processLinkAlreadyLinkedCheck($entity, $link, $foreignEntity);
    }

    /**
     * Check link access for a specific foreign entity.
     *
     * @throws Forbidden
     */
    private function processLinkForeignInternal(Entity $entity, string $link, Entity $foreignEntity): void
    {
        $toSkip = $this->linkForeignAccessCheckMany($entity->getEntityType(), $link, $foreignEntity);

        if ($toSkip) {
            return;
        }

        $this->linkEntityAccessCheck($entity, $foreignEntity, $link);
    }

    /**
     * Check unlink access for a specific foreign entity.
     *
     * @throws Forbidden
     */
    public function processUnlinkForeign(Entity $entity, string $link, Entity $foreignEntity): void
    {
        $this->processLinkForeignInternal($entity, $link, $foreignEntity);
        $this->processUnlinkForeignRequired($entity, $link, $foreignEntity);
    }

    /**
     * Check access to foreign record for has-many and many-many links.
     *
     * @return bool True indicates that the link checker should be bypassed.
     * @throws Forbidden
     */
    private function linkForeignAccessCheckMany(
        string $entityType,
        string $link,
        Entity $foreignEntity,
        bool $fromUpdate = false
    ): bool {

        /** @var AclTable::ACTION_* $action */
        $action = $this->getParam($entityType, $link, 'linkRequiredForeignAccess') ?? AclTable::ACTION_EDIT;

        if ($this->getParam($entityType, $link, 'linkForeignAccessCheckDisabled')) {
            return true;
        }

        $fieldDefs = $fromUpdate ?
            $this->entityManager
                ->getDefs()
                ->getEntity($entityType)
                ->tryGetField($link) :
            null;

        if (
            $fromUpdate &&
            $fieldDefs &&
            in_array($fieldDefs->getType(), $this->manyFieldTypeList)
        ) {
            $action = AclTable::ACTION_READ;

            if ($this->checkInDefaults($fieldDefs, $link, $foreignEntity)) {
                return true;
            }
        }

        if (
            $action === AclTable::ACTION_READ &&
            $this->checkIsAllowedForPortal($foreignEntity)
        ) {
            return true;
        }

        if ($this->acl->check($foreignEntity, $action)) {
            return false;
        }

        if ($this->getLinkChecker($entityType, $link)) {
            return false;
        }

        $body = ErrorBody::create();

        $body = $fromUpdate ?
            $body->withMessageTranslation('cannotRelateForbidden', null, [
                'foreignEntityType' => $foreignEntity->getEntityType(),
                'action' => $action,
            ]) :
            $body->withMessageTranslation('noAccessToForeignRecord', null, ['action' => $action]);

        throw ForbiddenSilent::createWithBody(
            "No foreign record access for link operation ($entityType:$link).",
            $body->encode()
        );
    }

    public function checkIsAllowedForPortal(Entity $foreignEntity): bool
    {
        if (!$this->user->isPortal()) {
            return false;
        }

        if (
            $foreignEntity->getEntityType() === Account::ENTITY_TYPE &&
            $this->user->getAccounts()->hasId($foreignEntity->getId())
        ) {
            return true;
        }

        if (
            $foreignEntity->getEntityType() === Contact::ENTITY_TYPE &&
            $this->user->getContactId() === $foreignEntity->getId()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws Forbidden
     */
    private function linkEntityAccessCheck(Entity $entity, Entity $foreignEntity, string $link): void
    {
        $entityType = $entity->getEntityType();

        $checker = $this->getLinkChecker($entityType, $link);

        if (!$checker) {
            return;
        }

        if ($checker->check($this->user, $entity, $foreignEntity)) {
            return;
        }

        throw ForbiddenSilent::createWithBody(
            "No access for link operation ($entityType:$link).",
            ErrorBody::create()
                ->withMessageTranslation('noLinkAccess', null, [
                    'foreignEntityType' => $foreignEntity->getEntityType(),
                    'link' => $link,
                ])
        );
    }

    /**
     * @return ?LinkChecker<Entity, Entity>
     */
    private function getLinkChecker(string $entityType, string $link): ?LinkChecker
    {
        $key = $entityType . '_' . $link;

        if (array_key_exists($key, $this->linkCheckerCache)) {
            return $this->linkCheckerCache[$key];
        }

        $factory = $this->injectableFactory->create(LinkCheckerFactory::class);

        if (!$factory->isCreatable($entityType, $link)) {
            return null;
        }

        $checker = $factory->create($entityType, $link);

        $this->linkCheckerCache[$link] = $checker;

        return $checker;
    }

    /**
     * @throws Forbidden
     */
    private function processUnlinkForeignRequired(Entity $entity, string $link, Entity $foreignEntity): void
    {
        $relationDefs = $this->ormDefs
            ->getEntity($entity->getEntityType())
            ->tryGetRelation($link);

        if (!$relationDefs) {
            return;
        }

        if (
            !$relationDefs->hasForeignEntityType() ||
            !$relationDefs->hasForeignRelationName()
        ) {
            return;
        }

        $foreignLink = $relationDefs->getForeignRelationName();

        $foreignRelationDefs = $this->ormDefs
            ->getEntity($foreignEntity->getEntityType())
            ->tryGetRelation($foreignLink);

        if (!$foreignRelationDefs) {
            return;
        }

        if (
            !in_array($foreignRelationDefs->getType(), [
                RelationType::BELONGS_TO,
                RelationType::HAS_ONE,
                RelationType::BELONGS_TO_PARENT,
            ])
        ) {
            return;
        }

        $foreignFieldDefs = $this->ormDefs
            ->getEntity($foreignEntity->getEntityType())
            ->tryGetField($foreignLink);

        if (!$foreignFieldDefs) {
            return;
        }

        if (!$foreignFieldDefs->getParam('required')) {
            return;
        }

        throw ForbiddenSilent::createWithBody(
            "Can't unlink required field ({$foreignEntity->getEntityType()}:$foreignLink}).",
            ErrorBody::create()
                ->withMessageTranslation('cannotUnrelateRequiredLink')
                ->encode()
        );
    }

    /**
     * @throws Forbidden
     */
    private function processLinkFields(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $typeList = [
            Entity::BELONGS_TO,
            Entity::BELONGS_TO_PARENT,
            Entity::HAS_ONE,
        ];

        foreach ($entityDefs->getRelationList() as $relationDefs) {
            $name = $relationDefs->getName();
            $attribute = $name . 'Id';
            $nameAttribute = $name . 'Name';

            if (
                !in_array($relationDefs->getType(), $typeList) ||
                !$entityDefs->hasAttribute($attribute) ||
                !$entity->isAttributeChanged($attribute) ||
                $entity->get($attribute) === null
            ) {
                continue;
            }

            $this->processCheckLinkWithoutField($entityDefs, $name, true);

            $id = $entity->get($attribute);

            $foreignEntity = $this->processLinkedRecordsCheckItem($entity, $relationDefs, $id, true);

            if (!$foreignEntity) {
                continue;
            }

            $nameAttributeDefs = $entityDefs->tryGetAttribute($nameAttribute);

            if (!$nameAttributeDefs) {
                return;
            }

            if (
                $nameAttributeDefs->getType() === AttributeType::FOREIGN ||
                $nameAttributeDefs->isNotStorable()
            ) {
                $foreignName = $relationDefs->getParam('foreignName') ?? 'name';

                $entity->set($nameAttribute, $foreignEntity->get($foreignName));
            }
        }
    }

    /**
     * Check access to foreign record for belongs-to, has-one and belongs-to-parent links.
     *
     * @return bool True indicates that the link checker should be bypassed.
     * @throws Forbidden
     */
    private function linkForeignAccessCheckOne(string $entityType, string $link, Entity $foreignEntity): bool
    {
        if ($this->getParam($entityType, $link, 'linkForeignAccessCheckDisabled')) {
            return true;
        }

        $fieldDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->tryGetField($link);

        if (
            $fieldDefs &&
            in_array($fieldDefs->getType(), $this->oneFieldTypeList)
        ) {
            if ($this->checkIsDefault($fieldDefs, $link, $foreignEntity)) {
                return true;
            }
        }

        if ($this->checkIsAllowedForPortal($foreignEntity)) {
            return true;
        }

        if ($this->acl->check($foreignEntity, AclTable::ACTION_READ)) {
            return false;
        }

        if ($this->getLinkChecker($entityType, $link)) {
            return false;
        }

        throw ForbiddenSilent::createWithBody(
            "No foreign record access for link operation ($entityType:$link).",
            ErrorBody::create()
                ->withMessageTranslation('cannotRelateForbidden', null, [
                    'foreignEntityType' => $foreignEntity->getEntityType(),
                    'action' => AclTable::ACTION_READ,
                ])
                ->encode()
        );
    }

    private function checkInDefaults(FieldDefs $fieldDefs, string $link, Entity $foreignEntity): bool
    {
        /** @var string[] $defaults */
        $defaults = $this->getDefault($fieldDefs,  $link . 'Ids') ?? [];

        return in_array($foreignEntity->getId(), $defaults);
    }

    private function checkIsDefault(FieldDefs $fieldDefs, string $link, Entity $foreignEntity): bool
    {
        return $foreignEntity->getId() === $this->getDefault($fieldDefs, $link . 'Id');
    }
    private function getDefault(FieldDefs $fieldDefs, string $attribute): mixed
    {
        $defaultAttributes = (object) ($fieldDefs->getParam('defaultAttributes') ?? []);

        return $defaultAttributes->$attribute ?? null;
    }

    /**
     * @param string[] $setIds
     */
    private function prepareNames(Entity $entity, string $namesAttribute, array $setIds): stdClass
    {
        $oldNames = $entity->getFetched($namesAttribute);

        if (!$oldNames instanceof stdClass) {
            $oldNames = (object) [];
        }

        $names = (object) [];

        foreach ($setIds as $id) {
            if (isset($oldNames->$id)) {
                $names->$id = $oldNames->$id;
            }
        }

        return $names;
    }

    /**
     * @throws Forbidden
     */
    private function processLinkAlreadyLinkedCheck(Entity $entity, string $link, Entity $foreignEntity): void
    {
        if (!$this->getParam($entity->getEntityType(), $link, 'linkOnlyNotLinked')) {
            return;
        }

        $entityType = $entity->getEntityType();

        $foreign = $this->ormDefs
            ->getEntity($entityType)
            ->tryGetRelation($link)
            ?->tryGetForeignRelationName();

        if (!$foreign) {
            return;
        }

        $one = $this->entityManager
            ->getRDBRepository($foreignEntity->getEntityType())
            ->getRelation($foreignEntity, $foreign)
            ->findOne();

        if (!$one) {
            return;
        }

        throw ForbiddenSilent::createWithBody(
            "Cannot link as the record is already linked ($entityType:$link).",
            ErrorBody::create()
                ->withMessageTranslation('cannotLinkAlreadyLinked')
                ->encode()
        );
    }
}
