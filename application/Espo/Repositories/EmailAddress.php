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

namespace Espo\Repositories;

use Espo\ORM\Entity;

use Espo\Entities\EmailAddress as EmailAddressEntity;

use Espo\Core\Di;

class EmailAddress extends \Espo\Core\Repositories\Database implements
    Di\ApplicationStateAware,
    Di\AclManagerAware
{
    use Di\ApplicationStateSetter;
    use Di\AclManagerSetter;

    protected $hooksDisabled = true;

    public function getIdListFormAddressList(array $addressList = []): array
    {
        return $this->getIds($addressList);
    }

    /**
     * @deprecated Use `getIdListFormAddressList`.
     */
    public function getIds(array $addressList = []): array
    {
        $ids = [];

        if (!empty($addressList)) {
            $lowerAddressList = [];

            foreach ($addressList as $address) {
                $lowerAddressList[] = trim(strtolower($address));
            }

            $eaCollection = $this
                ->where([
                    [
                        'lower' => $lowerAddressList
                    ]
                ])
                ->find();

            $ids = [];
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
                    $ea = $this->get();

                    $ea->set('name', $address);

                    $this->save($ea);

                    $ids[] = $ea->getId();
                }
            }
        }

        return $ids;
    }

    public function getEmailAddressData(Entity $entity): array
    {
        $dataList = [];

        $emailAddressList = $this
            ->select(['name', 'lower', 'invalid', 'optOut', ['ee.primary', 'primary']])
            ->join(
                'EntityEmailAddress',
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
                'emailAddress' => $emailAddress->get('name'),
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
            ->getRDBRepository('EntityEmailAddress')
            ->sth()
            ->select(['entityType', 'entityId'])
            ->where($where)
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

                if ($itemEntityType === 'User') {
                    $select[] = 'isActive';
                }

                $entity = $this->entityManager
                    ->getRDBRepository($itemEntityType)
                    ->select($select)
                    ->where(['id' => $itemEntityId])
                    ->findOne();
            }
            else {
                $entity = $this->entityManager->getEntity($itemEntityType, $itemEntityId);
            }

            if (!$entity) {
                continue;
            }

            if ($entity->getEntityType() === 'User' && !$entity->get('isActive')) {
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
            ->getRDBRepository('EntityEmailAddress')
            ->sth()
            ->select(['entityType', 'entityId'])
            ->where($where)
            ->limit(0, 20)
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

                if ($itemEntityType === 'User') {
                    $select[] = 'isActive';
                }

                $entity = $this->entityManager
                    ->getRDBRepository($itemEntityType)
                    ->select($select)
                    ->where(['id' => $itemEntityId])
                    ->findOne();
            }
            else {
                $entity = $this->entityManager->getEntity($itemEntityType, $itemEntityId);
            }

            if ($entity) {
                if ($entity->getEntityType() === 'User') {
                    if (!$entity->get('isActive')) {
                        continue;
                    }
                }

                return $entity;
            }
        }

        return null;
    }

    public function getEntityByAddress(
        string $address,
        ?string $entityType = null,
        array $order = ['User', 'Contact', 'Lead', 'Account']
    ): ?Entity {

        $selectBuilder = $this->entityManager
            ->getRDBRepository('EntityEmailAddress')
            ->select();

        $selectBuilder
            ->select(['entityType', 'entityId'])
            ->sth()
            ->join('EmailAddress', 'ea', ['ea.id:' => 'emailAddressId', 'ea.deleted' => 0])
            ->where('ea.lower=', strtolower($address))
            ->order([
                ['LIST:entityType:' . implode(',', $order)],
                ['primary', 'DESC'],
            ]);

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

            $entity = $this->entityManager->getEntity($itemEntityType, $itemEntityId);

            if ($entity) {
                if ($entity->getEntityType() === 'User') {
                    if (!$entity->get('isActive')) {
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
}
