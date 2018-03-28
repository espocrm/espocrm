<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class EmailAddress extends \Espo\Core\ORM\Repositories\RDB
{
    protected $processFieldsAfterSaveDisabled = true;

    protected $processFieldsBeforeSaveDisabled = true;

    protected function init()
    {
        parent::init();
        $this->addDependency('user');
    }

    public function getIdListFormAddressList(array $arr = [])
    {
        return $this->getIds($arr);
    }

    public function getIds(array $arr = [])
    {
        $ids = array();
        if (!empty($arr)) {
            $a = array_map(function ($item) {
                    return strtolower($item);
                }, $arr);
            $eas = $this->where(array(
                'lower' => array_map(function ($item) {
                    return strtolower($item);
                }, $arr)
            ))->find();
            $ids = array();
            $exist = array();
            foreach ($eas as $ea) {
                $ids[] = $ea->id;
                $exist[] = $ea->get('lower');
            }
            foreach ($arr as $address) {
                if (empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                if (!in_array(strtolower($address), $exist)) {
                    $ea = $this->get();
                    $ea->set('name', $address);
                    $this->save($ea);
                    $ids[] = $ea->id;
                }
            }
        }
        return $ids;
    }

    public function getEmailAddressData(Entity $entity)
    {
        $data = array();

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT email_address.name, email_address.lower, email_address.invalid, email_address.opt_out AS optOut, entity_email_address.primary
            FROM entity_email_address
            JOIN email_address ON email_address.id = entity_email_address.email_address_id AND email_address.deleted = 0
            WHERE
                entity_email_address.entity_id = ".$pdo->quote($entity->id)." AND
                entity_email_address.entity_type = ".$pdo->quote($entity->getEntityName())." AND
                entity_email_address.deleted = 0
            ORDER BY entity_email_address.primary DESC
        ";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($rows = $sth->fetchAll()) {
            foreach ($rows as $row) {
                $obj = new \StdClass();
                $obj->emailAddress = $row['name'];
                $obj->lower = $row['lower'];
                $obj->primary = ($row['primary'] == '1') ? true : false;
                $obj->optOut = ($row['optOut'] == '1') ? true : false;
                $obj->invalid = ($row['invalid'] == '1') ? true : false;
                $data[] = $obj;
            }
        }

        return $data;
    }

    public function getByAddress($address)
    {
        return $this->where(array('lower' => strtolower($address)))->findOne();
    }

    public function getEntityByAddressId($emailAddressId, $entityType = null, $onlyName = false)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT entity_email_address.entity_type AS 'entityType', entity_email_address.entity_id AS 'entityId'
            FROM entity_email_address
            WHERE
                entity_email_address.email_address_id = ".$pdo->quote($emailAddressId)." AND
                entity_email_address.deleted = 0
        ";

        if ($entityType) {
            $sql .= "
                AND entity_email_address.entity_type = " . $pdo->quote($entityType) . "
            ";
        }

        $sql .= "
            ORDER BY entity_email_address.primary DESC, FIELD(entity_email_address.entity_type, 'User', 'Contact', 'Lead', 'Account')
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch()) {
            if (!empty($row['entityType']) && !empty($row['entityId'])) {
                if (!$this->getEntityManager()->hasRepository($row['entityType'])) {
                    return;
                }
                if ($onlyName) {
                    $entity = $this->getEntityManager()->getRepository($row['entityType'])
                        ->select(['id', 'name'])
                        ->where(['id' => $row['entityId']])
                        ->findOne();
                } else {
                    $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
                }
                if ($entity) {
                    return $entity;
                }
            }
        }
    }

    public function getEntityByAddress($address, $entityType = null, $order = ['User', 'Contact', 'Lead', 'Account'])
    {
        $pdo = $this->getEntityManager()->getPDO();
        $a = [];
        foreach ($order as $item) {
            $a[] = $pdo->quote($item);
        }

        $sql = "
            SELECT entity_email_address.entity_type AS 'entityType', entity_email_address.entity_id AS 'entityId'
            FROM entity_email_address
            JOIN email_address ON email_address.id = entity_email_address.email_address_id AND email_address.deleted = 0
            WHERE
                email_address.lower = ".$pdo->quote(strtolower($address))." AND
                entity_email_address.deleted = 0
        ";

        if ($entityType) {
            $sql .= "
                AND entity_email_address.entity_type = " . $pdo->quote($entityType) . "
            ";
        }

        $sql .= "
            ORDER BY FIELD(entity_email_address.entity_type, ".join(',', array_reverse($a)).") DESC, entity_email_address.primary DESC
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch()) {
            if (!empty($row['entityType']) && !empty($row['entityId'])) {
                $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
                if ($entity) {
                    return $entity;
                }
            }
        }
    }

    public function storeEntityEmailAddress(Entity $entity)
    {
            $emailAddressValue = trim($entity->get('emailAddress'));
            $emailAddressData = null;

            if ($entity->has('emailAddressData')) {
                $emailAddressData = $entity->get('emailAddressData');
            }

            $pdo = $this->getEntityManager()->getPDO();

            if ($emailAddressData !== null && is_array($emailAddressData)) {
                $previousEmailAddressData = array();
                if (!$entity->isNew()) {
                    $previousEmailAddressData = $this->getEmailAddressData($entity);
                }

                $hash = array();
                foreach ($emailAddressData as $row) {
                    $key = $row->emailAddress;
                    if (!empty($key)) {
                        $key = strtolower($key);
                        $hash[$key] = [
                            'primary' => !empty($row->primary) ? true : false,
                            'optOut' => !empty($row->optOut) ? true : false,
                            'invalid' => !empty($row->invalid) ? true : false,
                            'emailAddress' => $row->emailAddress
                        ];
                    }
                }

                $hashPrev = array();
                foreach ($previousEmailAddressData as $row) {
                    $key = $row->lower;
                    if (!empty($key)) {
                        $hashPrev[$key] = array(
                            'primary' => $row->primary ? true : false,
                            'optOut' => $row->optOut ? true : false,
                            'invalid' => $row->invalid ? true : false,
                            'emailAddress' => $row->emailAddress
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
                        $changed =
                                    $hash[$key]['optOut'] != $hashPrev[$key]['optOut'] ||
                                    $hash[$key]['invalid'] != $hashPrev[$key]['invalid'] ||
                                    $hash[$key]['emailAddress'] !== $hashPrev[$key]['emailAddress'];
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

                foreach ($toRemove as $address) {
                    $emailAddress = $this->getByAddress($address);
                    if ($emailAddress) {
                        $query = "
                            DELETE FROM entity_email_address
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                email_address_id = ".$pdo->quote($emailAddress->id)."
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    }
                }

                foreach ($toUpdate as $address) {
                    $emailAddress = $this->getByAddress($address);
                    if ($emailAddress) {
                        $skipSave = false;
                        if (!$this->getInjection('user')->isAdmin()) {
                            if ($this->getEntityByAddressId($emailAddress->id, 'User', true)) {
                                $skipSave = true;
                            }
                        }
                        if (!$skipSave) {
                            $emailAddress->set(array(
                                'optOut' => $hash[$address]['optOut'],
                                'invalid' => $hash[$address]['invalid'],
                                'name' => $hash[$address]['emailAddress']
                            ));
                            $this->save($emailAddress);
                        }
                    }
                }

                foreach ($toCreate as $address) {
                    $emailAddress = $this->getByAddress($address);
                    if (!$emailAddress) {
                        $emailAddress = $this->get();

                        $emailAddress->set(array(
                            'name' => $hash[$address]['emailAddress'],
                            'optOut' => $hash[$address]['optOut'],
                            'invalid' => $hash[$address]['invalid'],
                        ));
                        $this->save($emailAddress);
                    } else {
                        if (
                            $emailAddress->get('optOut') != $hash[$address]['optOut'] ||
                            $emailAddress->get('invalid') != $hash[$address]['invalid'] ||
                            $emailAddress->get('emailAddress') != $hash[$address]['emailAddress']
                        ) {
                            $emailAddress->set(array(
                                'optOut' => $hash[$address]['optOut'],
                                'invalid' => $hash[$address]['invalid'],
                                'name' => $hash[$address]['emailAddress']
                            ));
                            $this->save($emailAddress);
                        }
                    }

                    $query = "
                        INSERT entity_email_address
                            (entity_id, entity_type, email_address_id, `primary`)
                            VALUES
                            (
                                ".$pdo->quote($entity->id).",
                                ".$pdo->quote($entity->getEntityName()).",
                                ".$pdo->quote($emailAddress->id).",
                                ".$pdo->quote((int)($address === $primary))."
                            )
                        ON DUPLICATE KEY UPDATE deleted = 0, `primary` = ".$pdo->quote((int)($address === $primary))."
                    ";
                    $sth = $pdo->prepare($query);
                    $sth->execute();
                }

                if ($primary) {
                    $emailAddress = $this->getByAddress($primary);
                    if ($emailAddress) {
                        $query = "
                            UPDATE entity_email_address
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
                            UPDATE entity_email_address
                            SET `primary` = 1
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                email_address_id = ".$pdo->quote($emailAddress->id)." AND
                                deleted = 0
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    }
                }

            } else {
                $entityRepository = $this->getEntityManager()->getRepository($entity->getEntityName());
                if (!empty($emailAddressValue)) {
                    if ($emailAddressValue != $entity->getFetched('emailAddress')) {

                        $emailAddressNew = $this->where(array('lower' => strtolower($emailAddressValue)))->findOne();
                        $isNewEmailAddress = false;
                        if (!$emailAddressNew) {
                            $emailAddressNew = $this->get();
                            $emailAddressNew->set('name', $emailAddressValue);
                            $this->save($emailAddressNew);
                            $isNewEmailAddress = true;
                        }

                        $emailAddressValueOld = $entity->getFetched('emailAddress');
                        if (!empty($emailAddressValueOld)) {
                            $emailAddressOld = $this->getByAddress($emailAddressValueOld);
                            if ($emailAddressOld) {
                                $entityRepository->unrelate($entity, 'emailAddresses', $emailAddressOld);
                            }
                        }
                        $entityRepository->relate($entity, 'emailAddresses', $emailAddressNew);

                        $query = "
                            UPDATE entity_email_address
                            SET `primary` = 1
                            WHERE
                                entity_id = ".$pdo->quote($entity->id)." AND
                                entity_type = ".$pdo->quote($entity->getEntityName())." AND
                                email_address_id = ".$pdo->quote($emailAddressNew->id)."
                        ";
                        $sth = $pdo->prepare($query);
                        $sth->execute();
                    }
                } else {
                    $emailAddressValueOld = $entity->getFetched('emailAddress');
                    if (!empty($emailAddressValueOld)) {
                        $emailAddressOld = $this->getByAddress($emailAddressValueOld);
                        if ($emailAddressOld) {
                            $entityRepository->unrelate($entity, 'emailAddresses', $emailAddressOld);
                        }
                    }
                }
            }
    }
}

