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

namespace Espo\Core\Formula;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\FieldProcessing\SpecificFieldLoader;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\EmailAddress;
use Espo\Entities\PhoneNumber;
use Espo\ORM\Defs\Params\AttributeParam as OrmAttributeParam;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Repositories\PhoneNumber as PhoneNumberRepository;

/**
 * Fetches attributes from an entity.
 */
class AttributeFetcher
{
    /** @var array<string, mixed> */
    private $relatedEntitiesCacheMap = [];

    public function __construct(
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil,
        private SpecificFieldLoader $specificFieldLoader,
        private SystemRestriction $systemRestriction,
    ) {}

    /**
     * @throws NotAllowedUsage
     */
    public function fetch(Entity $entity, string $attribute, bool $getFetchedAttribute = false): mixed
    {
        if (str_contains($attribute, '.')) {
            $arr = explode('.', $attribute);

            $link = $arr[0];
            $relatedAttribute = $arr[1] ?? null;

            if (!$relatedAttribute) {
                return null;
            }

            return $this->fetchRelated($entity, $link, $relatedAttribute);
        }

        if (!$this->systemRestriction->checkAttributeRead($entity->getEntityType(), $attribute)) {
            throw new NotAllowedUsage("Cannot read restricted attribute {$entity->getEntityType()}.$attribute.");
        }

        if ($getFetchedAttribute) {
            return $entity->getFetched($attribute);
        }

        if (
            $entity instanceof CoreEntity &&
            !$entity->has($attribute)
        ) {
            $this->load($entity, $attribute);
        }

        return $entity->get($attribute);
    }

    private function load(CoreEntity $entity, string $attribute): void
    {
        if ($entity->getAttributeParam($attribute, 'isParentName')) {
            /** @var ?string $relationName */
            $relationName = $entity->getAttributeParam($attribute, OrmAttributeParam::RELATION);

            if ($relationName) {
                $entity->loadParentNameField($relationName);
            }

            return;
        }

        if ($entity->getAttributeParam($attribute, AttributeParam::IS_LINK_MULTIPLE_ID_LIST)) {
            /** @var ?string $relationName */
            $relationName = $entity->getAttributeParam($attribute, OrmAttributeParam::RELATION);

            if ($relationName) {
                $entity->loadLinkMultipleField($relationName);
            }

            return;
        }

        if ($entity->getAttributeParam($attribute, 'isEmailAddressData')) {
            /** @var ?string $fieldName */
            $fieldName = $entity->getAttributeParam($attribute, 'field');

            if (!$fieldName) {
                return;
            }

            /** @var EmailAddressRepository $emailAddressRepository */
            $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

            $data = $emailAddressRepository->getEmailAddressData($entity);

            $entity->set($attribute, $data);
            $entity->setFetched($attribute, $data);

            return;
        }

        if ($entity->getAttributeParam($attribute, 'isPhoneNumberData')) {
            /** @var ?string $fieldName */
            $fieldName = $entity->getAttributeParam($attribute, 'field');

            if (!$fieldName) {
                return;
            }

            /** @var PhoneNumberRepository $phoneNumberRepository */
            $phoneNumberRepository = $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);

            $data = $phoneNumberRepository->getPhoneNumberData($entity);

            $entity->set($attribute, $data);
            $entity->setFetched($attribute, $data);

            return;
        }

        $field = $this->fieldUtil->getFieldOfAttribute($entity->getEntityType(), $attribute);

        if (!$field) {
            return;
        }

        $this->specificFieldLoader->process($entity, $field);
    }

    public function resetRuntimeCache(): void
    {
        $this->relatedEntitiesCacheMap = [];
    }

    private function buildKey(Entity $entity, string $link): string
    {
        return spl_object_hash($entity) . '-' . $link;
    }

    /**
     * @throws NotAllowedUsage
     */
    private function fetchRelated(Entity $entity, string $link, string $attribute): mixed
    {
        $entityType = $entity->getEntityType();

        if (!$this->systemRestriction->checkLinkRead($entityType, $link) ) {
            throw new NotAllowedUsage("Cannot read restricted link $entityType.$link.");
        }

        $key = $this->buildKey($entity, $link);

        if (
            !array_key_exists($key, $this->relatedEntitiesCacheMap) &&
            $entity->hasRelation($link) &&
            !in_array(
                $entity->getRelationType($link),
                [Entity::MANY_MANY, Entity::HAS_MANY, Entity::HAS_CHILDREN]
            )
        ) {
            $this->relatedEntitiesCacheMap[$key] = $this->entityManager
                ->getRelation($entity, $link)
                ->findOne();
        }

        $relatedEntity = $this->relatedEntitiesCacheMap[$key] ?? null;

        if (!$relatedEntity instanceof Entity) {
            return null;
        }

        return $this->fetch($relatedEntity, $attribute);
    }
}
