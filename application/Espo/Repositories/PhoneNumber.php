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

use Espo\Core\Di;

class PhoneNumber extends \Espo\Core\Repositories\Database implements
    Di\ApplicationStateAware,
    Di\AclManagerAware
{
    use Di\ApplicationStateSetter;
    use Di\AclManagerSetter;

    protected $processFieldsAfterSaveDisabled = true;

    protected $processFieldsAfterRemoveDisabled = true;

    const ERASED_PREFIX = 'ERASED:';

    protected function getAcl()
    {
        return $this->acl;
    }

    public function getIds($numberList = [])
    {
        $ids = array();
        if (!empty($numberList)) {
            $phoneNumbers = $this->where([
                [
                    'name' => $numberList,
                    'hash' => null
                ]
            ])->find();

            $ids = array();
            $exist = array();
            foreach ($phoneNumbers as $phoneNumber) {
                $ids[] = $phoneNumber->id;
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
                    $ids[] = $phoneNumber->id;
                }
            }
        }
        return $ids;
    }

    public function getPhoneNumberData(Entity $entity) : array
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
                'en.entityId' => $entity->id,
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

    public function getByNumber(string $number) : ?PhoneNumberEntity
    {
        return $this->where(['name' => $number])->findOne();
    }

    public function getEntityListByPhoneNumberId(string $phoneNumberId, ?Entity $exceptionEntity = null) : array
    {
        $entityList = [];

        $where = [
            'phoneNumberId' => $phoneNumberId,
        ];

        if ($exceptionEntity) {
            $where[] = [
                'OR' => [
                    'entityType!=' => $exceptionEntity->getEntityType(),
                    'entityId!=' => $exceptionEntity->id,
                ]
            ];
        }

        $itemList = $this->getEntityManager()->getRepository('EntityPhoneNumber')
            ->sth()
            ->select(['entityType', 'entityId'])
            ->where($where)
            ->find();

        foreach ($itemList as $item) {
            $itemEntityType = $item->get('entityType');
            $itemEntityId = $item->get('entityId');

            if (!$itemEntityType || !$itemEntityId) continue;

            if (!$this->getEntityManager()->hasRepository($itemEntityType)) continue;

            $entity = $this->getEntityManager()->getEntity($itemEntityType, $itemEntityId);

            if (!$entity) continue;

            $entityList[] = $entity;
        }

        return $entityList;
    }

    public function getEntityByPhoneNumberId(string $phoneNumberId, ?string $entityType = null) : ?Entity
    {
        $where = [
            'phoneNumberId' => $phoneNumberId,
        ];

        if ($entityType) {
            $where[] = ['entityType' => $entityType];
        }

        $itemList = $this->getEntityManager()->getRepository('EntityPhoneNumber')
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

            if (!$itemEntityType || !$itemEntityId) continue;

            if (!$this->getEntityManager()->hasRepository($itemEntityType)) continue;

            $entity = $this->getEntityManager()->getEntity($itemEntityType, $itemEntityId);

            if ($entity) {
                if ($entity->getEntityType() === 'User') {
                    if (!$entity->get('isActive')) continue;
                }
                return $entity;
            }
        }

        return null;
    }

    protected function storeEntityPhoneNumberData(Entity $entity)
    {
        $phoneNumberValue = $entity->get('phoneNumber');
        if (is_string($phoneNumberValue)) {
            $phoneNumberValue = trim($phoneNumberValue);
        }

        $phoneNumberData = null;
        if ($entity->has('phoneNumberData')) {
            $phoneNumberData = $entity->get('phoneNumberData');
        }

        if (is_null($phoneNumberData)) return;
        if (!is_array($phoneNumberData)) return;

        $keyList = [];
        $keyPreviousList = [];

        $previousPhoneNumberData = [];
        if (!$entity->isNew()) {
            $previousPhoneNumberData = $this->getPhoneNumberData($entity);
        }

        $hash = (object) [];
        $hashPrevious = (object) [];

        foreach ($phoneNumberData as $row) {
            $key = trim($row->phoneNumber);
            if (empty($key)) continue;
            if (isset($row->type)) {
                $type = $row->type;
            } else {
                $type = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', 'phoneNumber', 'defaultType']);
            }
            $hash->$key = [
                'primary' => $row->primary ? true : false,
                'type' => $type,
                'optOut' => !empty($row->optOut) ? true : false,
                'invalid' => !empty($row->invalid) ? true : false,
            ];
            $keyList[] = $key;
        }

        if (
            $entity->has('phoneNumberIsOptedOut')
            &&
            (
                $entity->isNew()
                ||
                (
                    $entity->hasFetched('phoneNumberIsOptedOut')
                    &&
                    $entity->get('phoneNumberIsOptedOut') !== $entity->getFetched('phoneNumberIsOptedOut')
                )
            )
        ) {
            if ($phoneNumberValue) {
                $key = $phoneNumberValue;
                if ($key && isset($hash->$key)) {
                    $hash->{$key}['optOut'] = $entity->get('phoneNumberIsOptedOut');
                }
            }
        }

        foreach ($previousPhoneNumberData as $row) {
            $key = $row->phoneNumber;
            if (empty($key)) continue;
            $hashPrevious->$key = [
                'primary' => $row->primary ? true : false,
                'type' => $row->type,
                'optOut' => $row->optOut ? true : false,
                'invalid' => $row->invalid ? true : false,
            ];
            $keyPreviousList[] = $key;
        }

        $primary = false;

        $toCreateList = [];
        $toUpdateList = [];
        $toRemoveList = [];

        $revertData = [];

        foreach ($keyList as $key) {
            $data = $hash->$key;

            $new = true;
            $changed = false;

            if ($hash->{$key}['primary']) {
                $primary = $key;
            }

            if (property_exists($hashPrevious, $key)) {
                $new = false;
                $changed =
                    $hash->{$key}['type'] != $hashPrevious->{$key}['type'] ||
                    $hash->{$key}['optOut'] != $hashPrevious->{$key}['optOut'] ||
                    $hash->{$key}['invalid'] != $hashPrevious->{$key}['invalid'];

                if ($hash->{$key}['primary']) {
                    if ($hash->{$key}['primary'] == $hashPrevious->{$key}['primary']) {
                        $primary = false;
                    }
                }
            }

            if ($new) {
                $toCreateList[] = $key;
            }
            if ($changed) {
                $toUpdateList[] = $key;
            }
        }

        foreach ($keyPreviousList as $key) {
            if (!property_exists($hash, $key)) {
                $toRemoveList[] = $key;
            }
        }

        foreach ($toRemoveList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if (!$phoneNumber) {
                continue;
            }

            $delete = $this->getEntityManager()->getQueryBuilder()
                ->delete()
                ->from('EntityPhoneNumber')
                ->where([
                    'entityId' => $entity->id,
                    'entityType' => $entity->getEntityType(),
                    'phoneNumberId' => $phoneNumber->id,
                ])
                ->build();

            $this->getEntityManager()->getQueryExecutor()->execute($delete);
        }

        foreach ($toUpdateList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if ($phoneNumber) {
                $skipSave = $this->checkChangeIsForbidden($phoneNumber, $entity);

                if (!$skipSave) {
                    $phoneNumber->set([
                        'type' => $hash->{$number}['type'],
                        'optOut' => $hash->{$number}['optOut'],
                        'invalid' => $hash->{$number}['invalid'],
                    ]);
                    $this->save($phoneNumber);
                }
                else {
                    $revertData[$number] = [
                        'type' => $phoneNumber->get('type'),
                        'optOut' => $phoneNumber->get('optOut'),
                        'invalid' => $phoneNumber->get('invalid'),
                    ];
                }
            }
        }

        foreach ($toCreateList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if (!$phoneNumber) {
                $phoneNumber = $this->get();

                $phoneNumber->set([
                    'name' => $number,
                    'type' => $hash->{$number}['type'],
                    'optOut' => $hash->{$number}['optOut'],
                    'invalid' => $hash->{$number}['invalid'],
                ]);
                $this->save($phoneNumber);
            }
            else {
                $skipSave = $this->checkChangeIsForbidden($phoneNumber, $entity);

                if (!$skipSave) {
                    if (
                        $phoneNumber->get('type') != $hash->{$number}['type'] ||
                        $phoneNumber->get('optOut') != $hash->{$number}['optOut'] ||
                        $phoneNumber->get('invalid') != $hash->{$number}['invalid']
                    ) {
                        $phoneNumber->set([
                            'type' => $hash->{$number}['type'],
                            'optOut' => $hash->{$number}['optOut'],
                            'invalid' => $hash->{$number}['invalid'],
                        ]);

                        $this->save($phoneNumber);
                    }
                } else {
                    $revertData[$number] = [
                        'type' => $phoneNumber->get('type'),
                        'optOut' => $phoneNumber->get('optOut'),
                        'invalid' => $phoneNumber->get('invalid'),
                    ];
                }
            }

            $entityPhoneNumber = $this->getEntityManager()->getEntity('EntityPhoneNumber');
            $entityPhoneNumber->set([
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType(),
                'phoneNumberId' => $phoneNumber->id,
                'primary' => $number === $primary,
                'deleted' => false,
            ]);

            $this->getEntityManager()->getMapper('RDB')->insertOnDuplicateUpdate($entityPhoneNumber, [
                'primary',
                'deleted',
            ]);
        }

        if ($primary) {
            $phoneNumber = $this->getByNumber($primary);

            if ($phoneNumber) {
                $update = $this->getEntityManager()->getQueryBuilder()
                    ->update()
                    ->in('EntityPhoneNumber')
                    ->set(['primary' => false])
                    ->where([
                        'entityId' => $entity->id,
                        'entityType' => $entity->getEntityType(),
                        'primary' => true,
                        'deleted' => false,
                    ])
                    ->build();

                $this->getEntityManager()->getQueryExecutor()->execute($update);

                $update = $this->getEntityManager()->getQueryBuilder()
                    ->update()
                    ->in('EntityPhoneNumber')
                    ->set(['primary' => true])
                    ->where([
                        'entityId' => $entity->id,
                        'entityType' => $entity->getEntityType(),
                        'phoneNumberId' => $phoneNumber->id,
                        'deleted' => false,
                    ])
                    ->build();

                $this->getEntityManager()->getQueryExecutor()->execute($update);
            }
        }

        if (!empty($revertData)) {
            foreach ($phoneNumberData as $row) {
                if (!empty($revertData[$row->phoneNumber])) {
                    $row->type = $revertData[$row->phoneNumber]['type'];
                    $row->optOut = $revertData[$row->phoneNumber]['optOut'];
                    $row->invalid = $revertData[$row->phoneNumber]['invalid'];
                }
            }
            $entity->set('phoneNumberData', $phoneNumberData);
        }
    }

    protected function storeEntityPhoneNumberPrimary(Entity $entity)
    {
        if (!$entity->has('phoneNumber')) return;
        $phoneNumberValue = trim($entity->get('phoneNumber'));

        $entityRepository = $this->getEntityManager()->getRepository($entity->getEntityType());
        if (!empty($phoneNumberValue)) {
            if ($phoneNumberValue !== $entity->getFetched('phoneNumber')) {

                $phoneNumberNew = $this->where(['name' => $phoneNumberValue])->findOne();
                $isNewPhoneNumber = false;
                if (!$phoneNumberNew) {
                    $phoneNumberNew = $this->get();
                    $phoneNumberNew->set('name', $phoneNumberValue);
                    if ($entity->has('phoneNumberIsOptedOut')) {
                        $phoneNumberNew->set('optOut', !!$entity->get('phoneNumberIsOptedOut'));
                    }
                    $defaultType = $this->getMetadata()->get('entityDefs.' .  $entity->getEntityType() . '.fields.phoneNumber.defaultType');

                    $phoneNumberNew->set('type', $defaultType);

                    $this->save($phoneNumberNew);
                    $isNewPhoneNumber = true;
                }

                $phoneNumberValueOld = $entity->getFetched('phoneNumber');
                if (!empty($phoneNumberValueOld)) {
                    $phoneNumberOld = $this->getByNumber($phoneNumberValueOld);
                    if ($phoneNumberOld) {
                        $entityRepository->unrelate($entity, 'phoneNumbers', $phoneNumberOld);
                    }
                }
                $entityRepository->relate($entity, 'phoneNumbers', $phoneNumberNew);

                if ($entity->has('phoneNumberIsOptedOut')) {
                    $this->markNumberOptedOut($phoneNumberValue, !!$entity->get('phoneNumberIsOptedOut'));
                }

                $update = $this->getEntityManager()->getQueryBuilder()
                    ->update()
                    ->in('EntityPhoneNumber')
                    ->set( ['primary' => true])
                    ->where([
                        'entityId' => $entity->id,
                        'entityType' => $entity->getEntityType(),
                        'phoneNumberId' => $phoneNumberNew->id,
                    ])
                    ->build();

                $this->getEntityManager()->getQueryExecutor()->execute($update);

            } else {
                if (
                    $entity->has('phoneNumberIsOptedOut')
                    &&
                    (
                        $entity->isNew()
                        ||
                        (
                            $entity->hasFetched('phoneNumberIsOptedOut')
                            &&
                            $entity->get('phoneNumberIsOptedOut') !== $entity->getFetched('phoneNumberIsOptedOut')
                        )
                    )
                ) {
                    $this->markNumberOptedOut($phoneNumberValue, !!$entity->get('phoneNumberIsOptedOut'));
                }
            }
        } else {
            $phoneNumberValueOld = $entity->getFetched('phoneNumber');
            if (!empty($phoneNumberValueOld)) {
                $phoneNumberOld = $this->getByNumber($phoneNumberValueOld);
                if ($phoneNumberOld) {
                    $entityRepository->unrelate($entity, 'phoneNumbers', $phoneNumberOld);
                }
            }
        }
    }

    public function storeEntityPhoneNumber(Entity $entity)
    {
        $phoneNumberData = null;
        if ($entity->has('phoneNumberData')) {
            $phoneNumberData = $entity->get('phoneNumberData');
        }

        if ($phoneNumberData !== null) {
            $this->storeEntityPhoneNumberData($entity);
        } else if ($entity->has('phoneNumber')) {
            $this->storeEntityPhoneNumberPrimary($entity);
        }
    }

    /**
     * @todo Move it to another place. ACL should not be in a repository.
     */
    protected function checkChangeIsForbidden($entity, $excludeEntity)
    {
        if (!$this->applicationState->hasUser()) {
            return true;
        }

        $user = $this->applicationState->getUser();

        return !$this->aclManager->getImplementation('PhoneNumber')
            ->checkEditInEntity($user, $entity, $excludeEntity);
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if ($entity->has('name')) {
            $number = $entity->get('name');
            if (is_string($number) && strpos($number, self::ERASED_PREFIX) !== 0) {
                $numeric = preg_replace('/[^0-9]/', '', $number);
            } else {
                $numeric = null;
            }
            $entity->set('numeric', $numeric);
        }
    }

    public function markNumberOptedOut(string $number, bool $isOptedOut = true)
    {
        $number = $this->getByNumber($number);

        if ($number) {
            $number->set('optOut', !!$isOptedOut);

            $this->save($number);
        }
    }
}
