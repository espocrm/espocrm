<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
    public function getIds($arr = array())
    {
        $ids = array();
        if (!empty($arr)) {
            $a = array_map(function ($item) {
                    return $item;
                }, $arr);
            $phoneNumbers = $this->where(array(
                'name' => array_map(function ($item) {
                    return $item;
                }, $arr)
            ))->find();
            $ids = array();
            $exist = array();
            foreach ($phoneNumbers as $phoneNumber) {
                $ids[] = $phoneNumber->id;
                $exist[] = $phoneNumber->get('name');
            }
            foreach ($arr as $phone) {
                if (empty($phone)) {
                    continue;
                }
                if (!in_array($phone, $exist)) {
                    $phoneNumber = $this->get();
                    $phoneNumber->set('name', $phone);
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
            SELECT phone_number.name, phone_number.type, entity_phone_number.primary
            FROM entity_phone_number
            JOIN phone_number ON phone_number.id = entity_phone_number.phone_number_id AND phone_number.deleted = 0
            WHERE
            entity_phone_number.entity_id = ".$pdo->quote($entity->id)." AND
            entity_phone_number.entity_type = ".$pdo->quote($entity->getEntityName())." AND
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

                $data[] = $obj;
            }
        }

        return $data;
    }

    public function getByNumber($number)
    {
        return $this->where(array('name' => $number))->findOne();
    }

    public function storeEntityPhoneNumber(Entity $entity)
    {
            $phoneNumberValue = trim($entity->get('phoneNumber'));
            $phoneNumberData = null;

            if ($entity->has('phoneNumberData')) {
                $phoneNumberData = $entity->get('phoneNumberData');
            }

            $pdo = $this->getEntityManager()->getPDO();

            if ($phoneNumberData !== null && is_array($phoneNumberData)) {
                $previousPhoneNumberData = array();
                if (!$entity->isNew()) {
                    $previousPhoneNumberData = $this->getPhoneNumberData($entity);
                }

                $hash = array();
                foreach ($phoneNumberData as $row) {
                    $key = $row->phoneNumber;
                    if (!empty($key)) {
                        $hash[$key] = array(
                            'primary' => $row->primary ? true : false,
                            'type' => $row->type
                        );
                    }
                }

                $hashPrev = array();
                foreach ($previousPhoneNumberData as $row) {
                    $key = $row->phoneNumber;
                    if (!empty($key)) {
                        $hashPrev[$key] = array(
                            'primary' => $row->primary ? true : false,
                            'type' => $row->type,
                        );
                    }
                }

                $primary = false;
                $toCreate = array();
                $toUpdate = array();
                $toRemove = array();


                foreach ($hash as $key => $data) {
                    $new = true;
                    $changed = false;

                    if ($hash[$key]['primary']) {
                        $primary = $key;
                    }

                    if (array_key_exists($key, $hashPrev)) {
                        $new = false;
                        $changed = $hash[$key]['type'] != $hashPrev[$key]['type'];
                        if ($hash[$key]['primary']) {
                            if ($hash[$key]['primary'] == $hashPrev[$key]['primary']) {
                                $primary = false;
                            }
                        }
                    }

                    if ($new) {
                        $toCreate[] = $key;
                    }
                    if ($changed) {
                        $toUpdate[] = $key;
                    }
                }

                foreach ($hashPrev as $key => $data) {
                    if (!array_key_exists($key, $hash)) {
                        $toRemove[] = $key;
                    }
                }

                foreach ($toRemove as $number) {
                    $phoneNumber = $this->getByNumber($number);
                    if ($phoneNumber) {
                        $query = "
                            DELETE FROM  entity_phone_number
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                phone_number_id = ".$pdo->quote($phoneNumber->id)."
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    }
                }

                foreach ($toUpdate as $number) {
                    $phoneNumber = $this->getByNumber($number);
                    if ($phoneNumber) {
                        $phoneNumber->set(array(
                            'type' => $hash[$number]['type'],
                        ));
                        $this->save($phoneNumber);
                    }
                }

                foreach ($toCreate as $number) {
                    $phoneNumber = $this->getByNumber($number);
                    if (!$phoneNumber) {
                        $phoneNumber = $this->get();

                        $phoneNumber->set(array(
                            'name' => $number,
                            'type' => $hash[$number]['type'],
                        ));
                        $this->save($phoneNumber);
                    } else {
                        if ($phoneNumber->get('type') != $hash[$number]['type']) {
                            $phoneNumber->set(array(
                                'type' => $hash[$number]['type'],
                            ));
                            $this->save($phoneNumber);
                        }
                    }

                    $query = "
                        INSERT entity_phone_number
                            (entity_id, entity_type, phone_number_id, `primary`)
                            VALUES
                            (
                                ".$pdo->quote($entity->id).",
                                ".$pdo->quote($entity->getEntityName()).",
                                ".$pdo->quote($phoneNumber->id).",
                                ".$pdo->quote((int)($number === $primary))."
                            )
                        ON DUPLICATE KEY UPDATE deleted = 0, `primary` = ".$pdo->quote((int)($number === $primary))."
                    ";
                    $sth = $pdo->prepare($query);
                    $sth->execute();
                }

                if ($primary) {
                    $phoneNumber = $this->getByNumber($primary);
                    if ($phoneNumber) {
                        $query = "
                            UPDATE entity_phone_number
                            SET `primary` = 0
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
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
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                phone_number_id = ".$pdo->quote($phoneNumber->id)." AND 
                                deleted = 0
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    }
                }
            } else {
                $entityRepository = $this->getEntityManager()->getRepository($entity->getEntityName());
                if (!empty($phoneNumberValue)) {
                    if ($phoneNumberValue !== $entity->getFetched('phoneNumber')) {

                        $phoneNumberNew = $this->where(array('name' => $phoneNumberValue))->findOne();
                        $isNewPhoneNumber = false;
                        if (!$phoneNumberNew) {
                            $phoneNumberNew = $this->get();
                            $phoneNumberNew->set('name', $phoneNumberValue);
                            $defaultType = $this->getEntityManager()->getEspoMetadata()->get('entityDefs.' .  $entity->getEntityName() . '.fields.phoneNumber.defaultType');

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

                        $query = "
                            UPDATE entity_phone_number
                            SET `primary` = 1
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                phone_number_id = ".$pdo->quote($phoneNumberNew->id)."
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
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
    }
}

