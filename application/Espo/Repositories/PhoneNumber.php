<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class PhoneNumber extends \Espo\Core\ORM\Repositories\RDB
{
    protected $processFieldsAfterSaveDisabled = true;

    protected $processFieldsBeforeSaveDisabled = true;

    protected $processFieldsAfterRemoveDisabled = true;

    const ERASED_PREFIX = 'ERASED:';

    protected function init()
    {
        parent::init();
        $this->addDependency('user');
        $this->addDependency('acl');
        $this->addDependency('aclManager');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
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

    public function getPhoneNumberData(Entity $entity)
    {
        $data = array();

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT phone_number.name, phone_number.type, entity_phone_number.primary, phone_number.opt_out AS optOut, phone_number.invalid
            FROM entity_phone_number
            JOIN phone_number ON phone_number.id = entity_phone_number.phone_number_id AND phone_number.deleted = 0
            WHERE
            entity_phone_number.entity_id = ".$pdo->quote($entity->id)." AND
            entity_phone_number.entity_type = ".$pdo->quote($entity->getEntityType())." AND
            entity_phone_number.deleted = 0
            ORDER BY entity_phone_number.primary DESC
        ";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($rows = $sth->fetchAll()) {
            foreach ($rows as $row) {
                $obj = new \StdClass();
                $obj->phoneNumber = $row['name'];
                $obj->primary = ($row['primary'] == '1') ? true : false;
                $obj->type = $row['type'];
                $obj->optOut = ($row['optOut'] == '1') ? true : false;
                $obj->invalid = ($row['invalid'] == '1') ? true : false;

                $data[] = $obj;
            }
        }

        return $data;
    }

    public function getByNumber($number)
    {
        return $this->where(array('name' => $number))->findOne();
    }

    public function getEntityListByPhoneNumberId($phoneNumberId, $exceptionEntity = null)
    {
        $entityList = [];

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT entity_phone_number.entity_type AS 'entityType', entity_phone_number.entity_id AS 'entityId'
            FROM entity_phone_number
            WHERE
                entity_phone_number.phone_number_id = ".$pdo->quote($phoneNumberId)." AND
                entity_phone_number.deleted = 0
        ";
        if ($exceptionEntity) {
            $sql .= "
                AND (
                    entity_phone_number.entity_type <> " .$pdo->quote($exceptionEntity->getEntityType()) . "
                    OR
                    entity_phone_number.entity_id <> " .$pdo->quote($exceptionEntity->id) . "
                )
            ";
        }

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch()) {
            if (empty($row['entityType']) || empty($row['entityId'])) continue;
            if (!$this->getEntityManager()->hasRepository($row['entityType'])) continue;
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
            if ($entity) {
                $entityList[] = $entity;
            }
        }

        return $entityList;
    }

    public function getEntityByPhoneNumberId($phoneNumberId, $entityType = null)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT entity_phone_number.entity_type AS 'entityType', entity_phone_number.entity_id AS 'entityId'
            FROM entity_phone_number
            WHERE
                entity_phone_number.phone_number_id = ".$pdo->quote($phoneNumberId)." AND
                entity_phone_number.deleted = 0
        ";

        if ($entityType) {
            $sql .= "
                AND entity_phone_number.entity_type = " . $pdo->quote($entityType) . "
            ";
        }

        $sql .= "
            ORDER BY entity_phone_number.primary DESC, FIELD(entity_phone_number.entity_type, 'User', 'Contact', 'Lead', 'Account')
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch()) {
            if (!empty($row['entityType']) && !empty($row['entityId'])) {
                if (!$this->getEntityManager()->hasRepository($row['entityType'])) {
                    return;
                }
                $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
                if ($entity) {
                    return $entity;
                }
            }
        }
    }

    protected function storeEntityPhoneNumberData(Entity $entity)
    {
        $pdo = $this->getEntityManager()->getPDO();

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
            if ($phoneNumber) {
                $query = "
                    DELETE FROM  entity_phone_number
                    WHERE
                        entity_id = ".$pdo->quote($entity->id)." AND
                        entity_type = ".$pdo->quote($entity->getEntityType())." AND
                        phone_number_id = ".$pdo->quote($phoneNumber->id)."
                ";
                $sth = $pdo->prepare($query);
                $sth->execute();
            }
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
                } else {
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
            } else {
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

            $query = "
                INSERT entity_phone_number
                    (entity_id, entity_type, phone_number_id, `primary`)
                    VALUES
                    (
                        ".$pdo->quote($entity->id).",
                        ".$pdo->quote($entity->getEntityType()).",
                        ".$pdo->quote($phoneNumber->id).",
                        ".$pdo->quote((int)($number === $primary))."
                    )
                ON DUPLICATE KEY UPDATE deleted = 0, `primary` = ".$pdo->quote((int)($number === $primary))."
            ";
            $this->getEntityManager()->runQuery($query, true);
        }

        if ($primary) {
            $phoneNumber = $this->getByNumber($primary);
            if ($phoneNumber) {
                $query = "
                    UPDATE entity_phone_number
                    SET `primary` = 0
                    WHERE
                        entity_id = ".$pdo->quote($entity->id)." AND
                        entity_type = ".$pdo->quote($entity->getEntityType())." AND
                        `primary` = 1 AND
                        deleted = 0
                ";
                $sth = $pdo->prepare($query);
                $sth->execute();

                $query = "
                    UPDATE entity_phone_number
                    SET `primary` = 1
                    WHERE
                        entity_id = ".$pdo->quote($entity->id)." AND
                        entity_type = ".$pdo->quote($entity->getEntityType())." AND
                        phone_number_id = ".$pdo->quote($phoneNumber->id)." AND 
                        deleted = 0
                ";
                $sth = $pdo->prepare($query);
                $sth->execute();
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
        $pdo = $this->getEntityManager()->getPDO();

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
                    $defaultType = $this->getEntityManager()->getEspoMetadata()->get('entityDefs.' .  $entity->getEntityType() . '.fields.phoneNumber.defaultType');

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

                $query = "
                    UPDATE entity_phone_number
                    SET `primary` = 1
                    WHERE
                        entity_id = ".$pdo->quote($entity->id)." AND
                        entity_type = ".$pdo->quote($entity->getEntityType())." AND
                        phone_number_id = ".$pdo->quote($phoneNumberNew->id)."
                ";
                $sth = $pdo->prepare($query);
                $sth->execute();
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

    protected function checkChangeIsForbidden($entity, $excludeEntity)
    {
        return !$this->getInjection('aclManager')->getImplementation('PhoneNumber')->checkEditInEntity($this->getInjection('user'), $entity, $excludeEntity);
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

    public function markNumberOptedOut($number, $isOptedOut = true)
    {
        $number = $this->getByNumber($number);
        if ($number) {
            $number->set('optOut', !!$isOptedOut);
            $this->save($number);
        }
    }
}
