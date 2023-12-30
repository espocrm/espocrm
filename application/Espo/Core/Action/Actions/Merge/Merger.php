<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Action\Actions\Merge;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Action\Params;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Record\ActionHistory\Action;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\ObjectUtil;
use Espo\ORM\Entity;
use Espo\Entities\EmailAddress;
use Espo\Entities\PhoneNumber;

use stdClass;

class Merger
{
    public function __construct(
        private Acl $acl,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private ServiceContainer $serviceContainer
    ) {}

    /**
     * @param string[] $sourceIdList
     * @throws NotFound
     * @throws Forbidden
     */
    public function process(Params $params, array $sourceIdList, stdClass $data): void
    {
        $clonedData = ObjectUtil::clone($data);

        $entityType = $params->getEntityType();
        $id = $params->getId();

        $entity = $this->entityManager->getEntity($entityType, $id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access.");
        }

        $service = $this->serviceContainer->get($entityType);

        $service->filterUpdateInput($clonedData);

        $entity->set($clonedData);

        $this->unsetNotActualAttributes($entity);

        if (!$service->checkAssignment($entity)) {
            throw new Forbidden("Assignment permission failure.");
        }

        $sourceEntityList = $this->fetchSourceEntityList($entityType, $sourceIdList);

        $entityDefs = $this->entityManager->getDefs()->getEntity($entityType);

        $hasPhoneNumber =
            $entityDefs->hasField('phoneNumber') &&
            $entityDefs->getField('phoneNumber')->getType() === 'phone';

        $hasEmailAddress =
            $entityDefs->hasField('emailAddress') &&
            $entityDefs->getField('emailAddress')->getType() === 'email';

        if ($hasPhoneNumber) {
            $phoneNumberToRelateList = $this->fetchEntityPhoneNumberList($entity);
        }

        if ($hasEmailAddress) {
            $emailAddressToRelateList = $this->fetchEntityEmailAddressList($entity);
        }

        foreach ($sourceEntityList as $sourceEntity) {
            if ($hasPhoneNumber) {
                $phoneNumberToRelateList = array_merge(
                    $phoneNumberToRelateList,
                    $this->fetchEntityPhoneNumberList($sourceEntity)
                );
            }

            if ($hasEmailAddress) {
                $emailAddressToRelateList = array_merge(
                    $emailAddressToRelateList,
                    $this->fetchEntityEmailAddressList($sourceEntity)
                );
            }

            $this->updateNotes($sourceEntity, $entity);
        }

        $mergeLinkList = $this->getMergeLinkList($entityType);

        foreach ($sourceEntityList as $sourceEntity) {
            foreach ($mergeLinkList as $link) {
                $this->updateRelations($sourceEntity, $entity, $link);
            }
        }

        foreach ($sourceEntityList as $sourceEntity) {
            $this->entityManager->removeEntity($sourceEntity);

            $service->processActionHistoryRecord(Action::DELETE, $sourceEntity);
        }

        if ($hasPhoneNumber) {
            $this->preparePhoneNumberData($phoneNumberToRelateList, $clonedData);
        }

        if ($hasEmailAddress) {
            $this->prepareEmailAddressData($emailAddressToRelateList, $clonedData);
        }

        $entity->set($clonedData);

        $this->entityManager->saveEntity($entity);

        $service->processActionHistoryRecord(Action::UPDATE, $entity);
    }

    /**
     * @param string[] $sourceIdList
     * @return Entity[]
     * @throws Forbidden
     * @throws NotFound
     */
    private function fetchSourceEntityList(string $entityType, array $sourceIdList): array
    {
        $list = [];

        foreach ($sourceIdList as $sourceId) {
            $sourceEntity = $this->entityManager->getEntity($entityType, $sourceId);

            if (!$sourceEntity) {
                throw new NotFound("Source record not found.");
            }

            $list[] = $sourceEntity;

            if (
                !$this->acl->check($sourceEntity, Table::ACTION_READ) ||
                !$this->acl->check($sourceEntity, Table::ACTION_EDIT) ||
                !$this->acl->check($sourceEntity, Table::ACTION_DELETE)
            ) {
                throw new Forbidden("No read, edit or delete access for one of source entities.");
            }
        }

        return $list;
    }

    /**
     * @return PhoneNumber[]
     */
    private function fetchEntityPhoneNumberList(Entity $entity): array
    {
        $list = [];

        /** @var iterable<PhoneNumber> $collection */
        $collection = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'phoneNumbers')
            ->find();

        foreach ($collection as $entity) {
            $list[] = $entity;
        }

        return $list;
    }

    /**
     * @return EmailAddress[]
     */
    private function fetchEntityEmailAddressList(Entity $entity): array
    {
        $list = [];

        /** @var iterable<EmailAddress> $collection */
        $collection = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'emailAddresses')
            ->find();

        foreach ($collection as $entity) {
            $list[] = $entity;
        }

        return $list;
    }

    private function updateNotes(Entity $sourceEntity, Entity $targetEntity): void
    {
        $updateQuery = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('Note')
            ->set([
                'parentId' => $targetEntity->getId(),
                'parentType' => $targetEntity->getEntityType(),
            ])
            ->where([
                'type' => ['Post', 'EmailSent', 'EmailReceived'],
                'parentId' => $sourceEntity->getId(),
                'parentType' => $sourceEntity->getEntityType(),
            ])
            ->build();

        $this->entityManager
            ->getQueryExecutor()
            ->execute($updateQuery);
    }

    private function updateRelations(Entity $sourceEntity, Entity $targetEntity, string $link): void
    {
        $repository = $this->entityManager->getRDBRepository($targetEntity->getEntityType());

        $collection = $repository
            ->getRelation($sourceEntity, $link)
            ->find();

        foreach ($collection as $relatedEntity) {
            $repository
                ->getRelation($targetEntity, $link)
                ->relate($relatedEntity);
        }
    }

    /**
     * @return string[]
     */
    private function getMergeLinkList(string $entityType): array
    {
        $list = [];

        $entityDefs = $this->entityManager->getDefs()->getEntity($entityType);

        $ignoreList = [
            'emailAddresses',
            'phoneNumbers',
        ];

        foreach ($entityDefs->getRelationList() as $relationDefs) {
            $name = $relationDefs->getName();
            $type = $relationDefs->getType();

            if (in_array($name, $ignoreList)) {
                continue;
            }

            $notMergeable = $this->metadata
                ->get(['entityDefs', $entityType, 'links', $name, 'notMergeable']);

            if ($notMergeable) {
                continue;
            }

            if (
                $type !== Entity::HAS_MANY &&
                $type !== Entity::HAS_CHILDREN &&
                $type !== Entity::MANY_MANY
            ) {
                continue;
            }

            $list[] = $name;
        }

        return $list;
    }

    /**
     * @param PhoneNumber[] $phoneNumberList
     */
    private function preparePhoneNumberData(array $phoneNumberList, stdClass $data): void
    {
        $phoneNumberData = [];

        foreach ($phoneNumberList as $i => $phoneNumber) {
            $o = (object) [];

            $o->phoneNumber = $phoneNumber->getNumber();
            $o->primary = false;

            if (empty($data->phoneNumber) && $i === 0) {
                $o->primary = true;
            }

            if (!empty($data->phoneNumber)) {
                $o->primary = $o->phoneNumber === $data->phoneNumber;
            }

            $o->optOut = $phoneNumber->isOptedOut();
            $o->invalid = $phoneNumber->isInvalid();
            $o->type = $phoneNumber->getType();

            $phoneNumberData[] = $o;
        }

        $data->phoneNumberData = $phoneNumberData;
    }

    /**
     * @param EmailAddress[] $emailAddressList
     */
    private function prepareEmailAddressData(array $emailAddressList, stdClass $data): void
    {
        $emailAddressData = [];

        foreach ($emailAddressList as $i => $emailAddress) {
            $o = (object) [];

            $o->emailAddress = $emailAddress->getAddress();
            $o->primary = false;

            if (empty($data->emailAddress) && $i === 0) {
                $o->primary = true;
            }

            if (!empty($data->emailAddress)) {
                $o->primary = $o->emailAddress === $data->emailAddress;
            }

            $o->optOut = $emailAddress->isOptedOut();
            $o->invalid = $emailAddress->isInvalid();

            $emailAddressData[] = $o;
        }

        $data->emailAddressData = $emailAddressData;
    }

    private function unsetNotActualAttributes(Entity $entity): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();

            if ($fieldDefs->getType() === 'link' && $entity->isAttributeChanged($field . 'Id')) {
                $entity->clear($field . 'Name');
            }
        }
    }
}
