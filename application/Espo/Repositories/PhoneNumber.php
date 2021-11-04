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

use Espo\Entities\PhoneNumber as PhoneNumberEntity;

use Espo\Core\Repositories\Database;

use Espo\Core\Di;

/**
 * @template T of PhoneNumberEntity
 * @extends Database<PhoneNumberEntity>
 */
class PhoneNumber extends Database implements

    Di\ApplicationStateAware,
    Di\AclManagerAware
{
    use Di\ApplicationStateSetter;
    use Di\AclManagerSetter;

    protected $hooksDisabled = true;

    const ERASED_PREFIX = 'ERASED:';

    public function getIds($numberList = []): array
    {
        $ids = [];

        if (!empty($numberList)) {
            $phoneNumbers = $this
                ->where([
                    [
                        'name' => $numberList,
                        'hash' => null,
                    ]
                ])
                ->find();

            $ids = [];
            $exist = [];

            foreach ($phoneNumbers as $phoneNumber) {
                $ids[] = $phoneNumber->getId();
                $exist[] = $phoneNumber->get('name');
            }

            foreach ($numberList as $number) {
                $number = trim($number);

                if (empty($number)) {
                    continue;
                }

                if (!in_array($number, $exist)) {
                    $phoneNumber = $this->get();

                    $phoneNumber->set('name', $number);

                    $this->save($phoneNumber);

                    $ids[] = $phoneNumber->getId();
                }
            }
        }

        return $ids;
    }

    public function getPhoneNumberData(Entity $entity): array
    {
        $dataList = [];

        $numberList = $this
            ->select(['name', 'type', 'invalid', 'optOut', ['en.primary', 'primary']])
            ->join(
                'EntityPhoneNumber',
                'en',
                [
                    'en.phoneNumberId:' => 'id',
                ]
            )
            ->where([
                'en.entityId' => $entity->getId(),
                'en.entityType' => $entity->getEntityType(),
                'en.deleted' => false,
            ])
            ->order('en.primary', true)
            ->find();

        foreach ($numberList as $number) {
            $item = (object) [
                'phoneNumber' => $number->get('name'),
                'type' => $number->get('type'),
                'primary' => $number->get('primary'),
                'optOut' => $number->get('optOut'),
                'invalid' => $number->get('invalid'),
            ];

            $dataList[] = $item;
        }

        return $dataList;
    }

    public function getByNumber(string $number): ?PhoneNumberEntity
    {
        /** @var ?PhoneNumberEntity */
        return $this->where(['name' => $number])->findOne();
    }

    public function getEntityListByPhoneNumberId(string $phoneNumberId, ?Entity $exceptionEntity = null): array
    {
        $entityList = [];

        $where = [
            'phoneNumberId' => $phoneNumberId,
        ];

        if ($exceptionEntity) {
            $where[] = [
                'OR' => [
                    'entityType!=' => $exceptionEntity->getEntityType(),
                    'entityId!=' => $exceptionEntity->getId(),
                ]
            ];
        }

        $itemList = $this->entityManager
            ->getRDBRepository('EntityPhoneNumber')
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

            $entity = $this->entityManager->getEntity($itemEntityType, $itemEntityId);

            if (!$entity) {
                continue;
            }

            $entityList[] = $entity;
        }

        return $entityList;
    }

    public function getEntityByPhoneNumberId(string $phoneNumberId, ?string $entityType = null): ?Entity
    {
        $where = [
            'phoneNumberId' => $phoneNumberId,
        ];

        if ($entityType) {
            $where[] = ['entityType' => $entityType];
        }

        $itemList = $this->entityManager
            ->getRDBRepository('EntityPhoneNumber')
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

    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if ($entity->has('name')) {
            $number = $entity->get('name');

            if (is_string($number) && strpos($number, self::ERASED_PREFIX) !== 0) {
                $numeric = preg_replace('/[^0-9]/', '', $number);
            }
            else {
                $numeric = null;
            }

            $entity->set('numeric', $numeric);
        }
    }

    public function markNumberOptedOut(string $number, bool $isOptedOut = true): void
    {
        $phoneNumber = $this->getByNumber($number);

        if (!$phoneNumber) {
            return;
        }

        $phoneNumber->set('optOut', $isOptedOut);

        $this->save($phoneNumber);
    }
}
