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

namespace Espo\Repositories;

use Espo\Core\Name\Field;
use Espo\Core\Repositories\Database;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;
use Espo\Entities\EmailAddress as EmailAddressEntity;
use Espo\Core\Di;

use Espo\ORM\Name\Attribute;
use stdClass;

/**
 * Not to be used directly. Use utilities from `Espo\Tools\EmailAddress` instead.
 * @internal
 * @extends Database<EmailAddressEntity>
 */
class EmailAddress extends Database implements
    Di\ApplicationStateAware,
    Di\AclManagerAware,
    Di\ConfigAware
{
    use Di\ApplicationStateSetter;
    use Di\AclManagerSetter;
    use Di\ConfigSetter;

    private const LOOKUP_SMALL_MAX_SIZE = 20;
    private const LOOKUP_MAX_SIZE = 50;

    /**
     * @param string[] $addressList
     * @return string[]
     */
    public function getIdListFormAddressList(array $addressList = []): array
    {
        return $this->getIds($addressList);
    }

    /**
     * @deprecated Use `getIdListFormAddressList`.
     * @param string[] $addressList
     * @return string[]
     */
    public function getIds(array $addressList = []): array
    {
        if (empty($addressList)) {
            return [];
        }

        $ids = [];

        $lowerAddressList = [];

        foreach ($addressList as $address) {
            $lowerAddressList[] = trim(strtolower($address));
        }

        $eaCollection = $this
            ->where(['lower' => $lowerAddressList])
            ->find();

        $exist = [];

        foreach ($eaCollection as $ea) {
            $ids[] = $ea->getId();
            $exist[] = $ea->get('lower');
        }

        foreach ($addressList as $address) {
            $address = trim($address);

            if (empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!in_array(strtolower($address), $exist)) {
                $ea = $this->getNew();

                $ea->set(Field::NAME, $address);

                $this->save($ea);

                $ids[] = $ea->getId();
            }
        }

        return $ids;
    }

    /**
     * @return stdClass[]
     */
    public function getEmailAddressData(Entity $entity): array
    {
        if (!$entity->hasId()) {
            return [];
        }

        $dataList = [];

        $emailAddressList = $this
            ->select([Field::NAME, 'lower', 'invalid', 'optOut', ['ee.primary', 'primary']])
            ->join(
                EmailAddressEntity::RELATION_ENTITY_EMAIL_ADDRESS,
                'ee',
                [
                    'ee.emailAddressId:' => 'id',
                ]
            )
            ->where([
                'ee.entityId' => $entity->getId(),
                'ee.entityType' => $entity->getEntityType(),
                'ee.deleted' => false,
            ])
            ->order('ee.primary', true)
            ->find();

        foreach ($emailAddressList as $emailAddress) {
            $item = (object) [
                'emailAddress' => $emailAddress->get(Field::NAME),
                'lower' => $emailAddress->get('lower'),
                'primary' => $emailAddress->get('primary'),
                'optOut' => $emailAddress->get('optOut'),
                'invalid' => $emailAddress->get('invalid'),
            ];

            $dataList[] = $item;
        }

        return $dataList;
    }

    public function getByAddress(string $address): ?EmailAddressEntity
    {
        /** @var ?EmailAddressEntity */
        return $this->where(['lower' => strtolower($address)])->findOne();
    }

    /**
     * @return Entity[]
     */
    public function getEntityListByAddressId(
        string $emailAddressId,
        ?Entity $exceptionEntity = null,
        ?string $entityType = null,
        bool $onlyName = false
    ): array {

        $entityList = [];

        $where = [
            'emailAddressId' => $emailAddressId,
        ];

        if ($exceptionEntity) {
            $where[] = [
                'OR' => [
                    'entityType!=' => $exceptionEntity->getEntityType(),
                    'entityId!=' => $exceptionEntity->getId(),
                ]
            ];
        }

        if ($entityType) {
            $where[] = [
                'entityType' => $entityType,
            ];
        }

        $itemList = $this->entityManager
            ->getRDBRepository(EmailAddressEntity::RELATION_ENTITY_EMAIL_ADDRESS)
            ->sth()
            ->select(['entityType', 'entityId'])
            ->where($where)
            ->limit(0, self::LOOKUP_MAX_SIZE)
            ->find();

        foreach ($itemList as $item) {
            $itemEntityType = $item->get('entityType');
            $itemEntityId = $item->get('entityId');

            if (!$itemEntityType || !$itemEntityId) {
                continue;
            }

            if (!$this->entityManager->hasRepository($itemEntityType)) {
                continue;
            }

            if ($onlyName) {
                $select = [Attribute::ID, 'name'];

                if ($itemEntityType === UserEntity::ENTITY_TYPE) {
                    $select[] = 'isActive';
                }

                $entity = $this->entityManager
                    ->getRDBRepository($itemEntityType)
                    ->select($select)
                    ->where([Attribute::ID => $itemEntityId])
                    ->findOne();
            } else {
                $entity = $this->entityManager->getEntityById($itemEntityType, $itemEntityId);
            }

            if (!$entity) {
                continue;
            }

            if ($entity instanceof UserEntity && !$entity->isActive()) {
                continue;
            }

            $entityList[] = $entity;
        }

        return $entityList;
    }

    public function getEntityByAddressId(
        string $emailAddressId,
        ?string $entityType = null,
        bool $onlyName = false
    ): ?Entity {

        $where = [
            'emailAddressId' => $emailAddressId,
        ];

        if ($entityType) {
            $where[] = ['entityType' => $entityType];
        }

        $itemList = $this->entityManager
            ->getRDBRepository(EmailAddressEntity::RELATION_ENTITY_EMAIL_ADDRESS)
            ->sth()
            ->select(['entityType', 'entityId'])
            ->where($where)
            ->limit(0, self::LOOKUP_SMALL_MAX_SIZE)
            ->order([
                ['primary', 'DESC'],
                ['LIST:entityType:User,Contact,Lead,Account'],
            ])
            ->find();

        foreach ($itemList as $item) {
            $itemEntityType = $item->get('entityType');
            $itemEntityId = $item->get('entityId');

            if (!$itemEntityType || !$itemEntityId) {
                continue;
            }

            if (!$this->entityManager->hasRepository($itemEntityType)) {
                continue;
            }

            if ($onlyName) {
                $select = ['id', 'name'];

                if ($itemEntityType === UserEntity::ENTITY_TYPE) {
                    $select[] = 'isActive';
                }

                $entity = $this->entityManager
                    ->getRDBRepository($itemEntityType)
                    ->select($select)
                    ->where([Attribute::ID => $itemEntityId])
                    ->findOne();
            } else {
                $entity = $this->entityManager->getEntityById($itemEntityType, $itemEntityId);
            }

            if ($entity) {
                if ($entity instanceof UserEntity) {
                    if (!$entity->isActive()) {
                        continue;
                    }
                }

                return $entity;
            }
        }

        return null;
    }

    /**
     * @param string[] $order
     */
    public function getEntityByAddress(string $address, ?string $entityType = null, ?array $order = null): ?Entity
    {
        $order ??= $this->config->get('emailAddressEntityLookupDefaultOrder') ?? [];

        $selectBuilder = $this->entityManager
            ->getRDBRepository(EmailAddressEntity::RELATION_ENTITY_EMAIL_ADDRESS)
            ->select();

        $selectBuilder
            ->select(['entityType', 'entityId'])
            ->sth()
            ->join(
                EmailAddressEntity::ENTITY_TYPE,
                'ea',
                ['ea.id:' => 'emailAddressId', 'ea.deleted' => false]
            )
            ->where('ea.lower=', strtolower($address))
            ->order([
                ['LIST:entityType:' . implode(',', $order)],
                ['primary', 'DESC'],
            ])
            ->limit(0, self::LOOKUP_MAX_SIZE);

        if ($entityType) {
            $selectBuilder->where('entityType=', $entityType);
        }

        foreach ($selectBuilder->find() as $item) {
            $itemEntityType = $item->get('entityType');
            $itemEntityId = $item->get('entityId');

            if (!$itemEntityType || !$itemEntityId) {
                continue;
            }

            if (!$this->entityManager->hasRepository($itemEntityType)) {
                continue;
            }

            $entity = $this->entityManager->getEntityById($itemEntityType, $itemEntityId);

            if ($entity) {
                if ($entity instanceof UserEntity) {
                    if (!$entity->isActive()) {
                        continue;
                    }
                }

                return $entity;
            }
        }

        return null;
    }

    public function markAddressOptedOut(string $address, bool $isOptedOut = true): void
    {
        $emailAddress = $this->getByAddress($address);

        if (!$emailAddress) {
            return;
        }

        $emailAddress->set('optOut', $isOptedOut);

        $this->save($emailAddress);
    }

    public function markAddressInvalid(string $address, bool $isInvalid = true): void
    {
        $emailAddress = $this->getByAddress($address);

        if (!$emailAddress) {
            return;
        }

        $emailAddress->set('invalid', $isInvalid);

        $this->save($emailAddress);
    }
}
