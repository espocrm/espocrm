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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\BadRequest;

use Espo\ORM\Entity;

class Import extends \Espo\Services\Record
{
    const REVERT_PERMANENTLY_REMOVE_PERIOD_DAYS = 2;

    protected function init()
    {
        parent::init();

        $this->addDependency('serviceFactory');
        $this->addDependency('fileManager');
        $this->addDependency('selectManagerFactory');
        $this->addDependency('fileStorageManager');
    }

    protected $dateFormatsMap = [
        'YYYY-MM-DD' => 'Y-m-d',
        'DD-MM-YYYY' => 'd-m-Y',
        'MM-DD-YYYY' => 'm-d-Y',
        'MM/DD/YYYY' => 'm/d/Y',
        'DD/MM/YYYY' => 'd/m/Y',
        'DD.MM.YYYY' => 'd.m.Y',
        'MM.DD.YYYY' => 'm.d.Y',
        'YYYY.MM.DD' => 'Y.m.d',
    ];

    protected $timeFormatsMap = [
        'HH:mm' => 'H:i',
        'HH:mm:ss' => 'H:i:s',
        'hh:mm a' => 'h:i a',
        'hh:mma' => 'h:ia',
        'hh:mm A' => 'h:iA',
        'hh:mmA' => 'h:iA',
    ];

    protected $services = [];

    protected function getSelectManagerFactory()
    {
        return $this->injections['selectManagerFactory'];
    }

    protected function getFileStorageManager()
    {
        return $this->injections['fileStorageManager'];
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $importedCount = $this->getRepository()->countRelated($entity, 'imported');
        $duplicateCount = $this->getRepository()->countRelated($entity, 'duplicates');
        $updatedCount = $this->getRepository()->countRelated($entity, 'updated');
        $entity->set([
            'importedCount' => $importedCount,
            'duplicateCount' => $duplicateCount,
            'updatedCount' => $updatedCount
        ]);
    }

    public function findLinked($id, $link, $params)
    {
        $entity = $this->getRepository()->get($id);
        $foreignEntityType = $entity->get('entityType');

        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }
        if (!$this->getAcl()->check($foreignEntityType, 'read')) {
            throw new Forbidden();
        }

        $selectParams = $this->getSelectManager($foreignEntityType)->getSelectParams($params, true);

        if (array_key_exists($link, $this->linkSelectParams)) {
            $selectParams = array_merge($selectParams, $this->linkSelectParams[$link]);
        }

        $collection = $this->getRepository()->findRelated($entity, $link, $selectParams);

        $recordService = $this->getRecordService($foreignEntityType);

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            $recordService->prepareEntityForOutput($e);
        }

        $total = $this->getRepository()->countRelated($entity, $link, $selectParams);

        return [
            'total' => $total,
            'collection' => $collection
        ];
    }

    protected function readCsvString(&$string, $CSV_SEPARATOR = ';', $CSV_ENCLOSURE = '"', $CSV_LINEBREAK = "\n")
    {
        $o = [];
        $cnt = strlen($string);
        $esc = false;
        $escesc = false;
        $num = 0;
        $i = 0;
        while ($i < $cnt) {
            $s = $string[$i];
            if ($s == $CSV_LINEBREAK) {
                if ($esc) {
                    $o[$num].= $s;
                }
                else {
                    $i++;
                    break;
                }
            }
            elseif ($s == $CSV_SEPARATOR) {
                if ($esc) {
                    $o[$num].= $s;
                }
                else {
                    $num++;
                    $esc = false;
                    $escesc = false;
                }
            }
            elseif ($s == $CSV_ENCLOSURE) {
                if ($escesc) {
                    $o[$num].= $CSV_ENCLOSURE;
                    $escesc = false;
                }

                if ($esc) {
                    $esc = false;
                    $escesc = true;
                }
                else {
                    $esc = true;
                    $escesc = false;
                }
            }
            else {
                if (!array_key_exists($num, $o)) {
                    $o[$num] = '';
                }
                if ($escesc) {
                    $o[$num] .= $CSV_ENCLOSURE;
                    $escesc = false;
                }
                $o[$num] .= $s;
            }

            $i++;
        }
        $string = substr($string, $i);
        return $o;
    }

    public function revert($id)
    {
        $import = $this->getEntityManager()->getEntity('Import', $id);
        if (empty($import)) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($import, 'delete')) {
            throw new Forbidden();
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "SELECT * FROM import_entity WHERE import_id = ".$pdo->quote($import->id) . " AND is_imported = 1";

        $removeFromDb = false;
        $createdAt = $import->get('createdAt');
        if ($createdAt) {
            $dtNow = new \DateTime();
            $createdAtDt = new \DateTime($createdAt);
            $dayDiff = ($dtNow->getTimestamp() - $createdAtDt->getTimestamp()) / 60 / 60 / 24;
            if ($dayDiff < self::REVERT_PERMANENTLY_REMOVE_PERIOD_DAYS) {
                $removeFromDb = true;
            }
        }

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if (empty($row['entity_type']) || empty($row['entity_id'])) {
                continue;
            }
            $entityType = $row['entity_type'];
            $entityId = $row['entity_id'];

            $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
            if ($entity) {
                $this->getEntityManager()->removeEntity($entity, [
                    'noStream' => true,
                    'noNotifications' => true,
                    'import' => true
                ]);
            }
            if ($removeFromDb) {
                $this->getEntityManager()->getRepository($entityType)->deleteFromDb($entityId);
            }
        }

        $this->getEntityManager()->removeEntity($import);

        $this->processActionHistoryRecord('delete', $import);

        return true;
    }

    public function removeDuplicates($id)
    {
        $import = $this->getEntityManager()->getEntity('Import', $id);
        if (empty($import)) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($import, 'delete')) {
            throw new Forbidden();
        }

        $pdo = $this->getEntityManager()->getPDO();


        $sql = "SELECT * FROM import_entity WHERE import_id = ".$pdo->quote($import->id) . " AND is_duplicate = 1";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if (empty($row['entity_type']) || empty($row['entity_id'])) {
                continue;
            }
            $entityType = $row['entity_type'];
            $entityId = $row['entity_id'];

            $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
            if ($entity) {
                $this->getEntityManager()->removeEntity($entity);
            }
            $this->getEntityManager()->getRepository($entity->getEntityType())->deleteFromDb($entityId);
        }

        return true;
    }

    public function jobRunIdleImport($data)
    {
        if (
            empty($data->userId) ||
            empty($data->userId) ||
            !isset($data->importAttributeList) ||
            !isset($data->params) ||
            !isset($data->entityType)
        ) {
            throw new Error("Import: Bad job data.");
        }

        $entityType = $data->entityType;
        $params = json_decode(json_encode($data->params), true);
        $attachmentId = $data->attachmentId;
        $importId = $data->importId;
        $importAttributeList = $data->importAttributeList;
        $userId = $data->userId;

        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (!$user) {
            throw new Error("Import: User not found.");
        }
        if (!$user->get('isActive')) {
            throw new Error("Import: User is not active.");
        }

        $this->import($entityType, $importAttributeList, $attachmentId, $params, $importId, $user);
    }

    public function import($scope, array $importAttributeList, $attachmentId, array $params = [], $importId = null, $user = null)
    {
        $delimiter = ',';
        if (!empty($params['delimiter'])) {
            $delimiter = $params['delimiter'];
        }
        $enclosure = '"';
        if (!empty($params['textQualifier'])) {
            $enclosure = $params['textQualifier'];
        }

        if (!$user) {
            $user = $this->getUser();
        }

        if (!$user->isAdmin()) {
            $forbiddenAttrbuteList = $this->getAclManager()->getScopeForbiddenAttributeList($user, $scope, 'edit');
            foreach ($importAttributeList as $i => $attribute) {
                if (in_array($attribute, $forbiddenAttrbuteList)) {
                    unset($importAttributeList[$i]);
                }
            }

            if (!$this->getAclManager()->checkScope($user, $scope, 'create')) {
                throw new Error('Import: Create is forbidden.');
            }
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
        if (!$attachment) {
            throw new Error('Import error');
        }

        $contents = $this->getFileStorageManager()->getContents($attachment);
        if (empty($contents)) {
            throw new Error('Import error');
        }

        if ($importId) {
            $import = $this->getEntityManager()->getEntity('Import', $importId);
            if (!$import) {
                throw new Error('Import: Could not find import record.');
            }
        } else {
            $import = $this->getEntityManager()->getEntity('Import');
            $import->set([
                'entityType' => $scope,
                'fileId' => $attachmentId
            ]);
            $import->set('status', 'In Process');
        }

        $this->getEntityManager()->saveEntity($import);

        $this->processActionHistoryRecord('create', $import);

        if (!empty($params['idleMode'])) {
            $params['idleMode'] = false;

            $job = $this->getEntityManager()->getEntity('Job');
            $job->set([
                'serviceName' => 'Import',
                'methodName' => 'jobRunIdleImport',
                'data' => [
                    'entityType' => $scope,
                    'params' => $params,
                    'attachmentId' => $attachmentId,
                    'importAttributeList' => $importAttributeList,
                    'importId' => $import->id,
                    'userId' => $this->getUser()->id
                ]
            ]);
            $this->getEntityManager()->saveEntity($job);

            return [
                'id' => $import->id,
                'countCreated' => 0,
                'countUpdated' => 0
            ];
        }

        try {
            $pdo = $this->getEntityManager()->getPDO();

            $result = [
                'importedIds' => [],
                'updatedIds' => [],
                'duplicateIds' => []
            ];
            $i = -1;

            $contents = str_replace("\r\n", "\n", $contents);

            while ($arr = $this->readCsvString($contents, $delimiter, $enclosure)) {
                $i++;
                if ($i == 0 && !empty($params['headerRow'])) {
                    continue;
                }
                if (count($arr) == 1 && empty($arr[0])) {
                    continue;
                }
                $r = $this->importRow($scope, $importAttributeList, $arr, $params, $user);
                if (empty($r)) {
                    continue;
                }
                if (!empty($r['isImported'])) {
                    $result['importedIds'][] = $r['id'];
                }
                if (!empty($r['isUpdated'])) {
                    $result['updatedIds'][] = $r['id'];
                }
                if (!empty($r['isDuplicate'])) {
                    $result['duplicateIds'][] = $r['id'];
                }
                $sql = "
                    INSERT INTO import_entity
                    (entity_type, entity_id, import_id, is_imported, is_updated, is_duplicate)
                    VALUES
                    (:entityType, :entityId, :importId, :isImported, :isUpdated, :isDuplicate)
                ";
                $sth = $pdo->prepare($sql);
                $sth->bindValue(':entityType', $scope);
                $sth->bindValue(':entityId', $r['id']);
                $sth->bindValue(':importId', $import->id);
                $sth->bindValue(':isImported', !empty($r['isImported']), \PDO::PARAM_BOOL);
                $sth->bindValue(':isUpdated', !empty($r['isUpdated']), \PDO::PARAM_BOOL);
                $sth->bindValue(':isDuplicate', !empty($r['isDuplicate']), \PDO::PARAM_BOOL);

                $sth->execute();
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Import Error: '. $e->getMessage());
            $import->set('status', 'Failed');
        }

        $import->set('status', 'Complete');

        $this->getEntityManager()->saveEntity($import);

        return [
            'id' => $import->id,
            'countCreated' => count($result['importedIds']),
            'countUpdated' => count($result['updatedIds']),
        ];
    }

    public function importRow($scope, array $importAttributeList, array $row, array $params = [], $user)
    {
        $id = null;
        $action = 'create';
        if (!empty($params['action'])) {
            $action = $params['action'];
        }

        if (empty($importAttributeList)) {
            return;
        }

        if (in_array($action, ['createAndUpdate', 'update'])) {
            $updateByAttributeList = [];
            $whereClause = [];
            if (!empty($params['updateBy']) && is_array($params['updateBy'])) {
                foreach ($params['updateBy'] as $i) {
                    if (array_key_exists($i, $importAttributeList)) {
                        $updateByAttributeList[] = $importAttributeList[$i];
                        $whereClause[$importAttributeList[$i]] = $row[$i];
                    }
                }
            }
        }

        $recordService = $this->getRecordService($scope);

        if (in_array($action, ['createAndUpdate', 'update'])) {
            if (!count($updateByAttributeList)) {
                return;
            }
            $entity = $this->getEntityManager()->getRepository($scope)->where($whereClause)->findOne();

            if ($entity) {
                if (!$user->isAdmin()) {
                    if (!$this->getAclManager()->checkEntity($user, $entity, 'edit')) {
                        return;
                    }
                }
            }
            if (!$entity) {
                if ($action == 'createAndUpdate') {
                    $entity = $this->getEntityManager()->getEntity($scope);
                    if (array_key_exists('id', $whereClause)) {
                        $entity->set('id', $whereClause['id']);
                    }
                } else {
                    return;
                }
            }
        } else {
            $entity = $this->getEntityManager()->getEntity($scope);
        }

        $isNew = $entity->isNew();

        if (!empty($params['defaultValues'])) {
            if (is_object($params['defaultValues'])) {
                $v = get_object_vars($params['defaultValues']);
            } else {
                $v = $params['defaultValues'];
            }
            $entity->set($v);
        }

        $attributeDefs = $entity->getAttributes();
        $relDefs = $entity->getRelations();

        $phoneFieldList = [];
        if (
            $entity->hasAttribute('phoneNumber')
            &&
            $entity->getAttributeParam('phoneNumber', 'fieldType') === 'phone'
        ) {
            $typeList = $this->getMetadata()->get('entityDefs.' . $scope . '.fields.phoneNumber.typeList', []);
            foreach ($typeList as $type) {
                $attr = str_replace(' ', '_', ucfirst($type));
                $phoneFieldList[] = 'phoneNumber' . $attr;
            }
        }

        $valueMap = (object) [];
        foreach ($importAttributeList as $i => $attribute) {
            if (!empty($attribute)) {
                if (!array_key_exists($i, $row)) {
                    continue;
                }
                $value = $row[$i];
                $valueMap->$attribute = $value;
            }
        }

        foreach ($importAttributeList as $i => $attribute) {
            if (!empty($attribute)) {
                if (!array_key_exists($i, $row)) {
                    continue;
                }
                $value = $row[$i];
                if ($attribute == 'id') {
                    if ($params['action'] == 'create') {
                        $entity->id = $value;
                    }
                    continue;
                }
                if (array_key_exists($attribute, $attributeDefs)) {
                    if ($value !== '') {
                        $type = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $attribute, 'type']);

                        if ($attribute === 'emailAddress' && $type === 'email') {
                            $emailAddressData = $entity->get('emailAddressData');
                            $emailAddressData = $emailAddressData ?? [];
                            $o = (object) [
                                'emailAddress' => $value,
                                'primary' => true,
                            ];
                            $emailAddressData[] = $o;
                            $entity->set('emailAddressData', $emailAddressData);
                            continue;
                        }

                        if ($attribute === 'phoneNumber' && $type === 'phone') {
                            $phoneNumberData = $entity->get('phoneNumberData');
                            $phoneNumberData = $phoneNumberData ?? [];
                            $o = (object) [
                                'phoneNumber' => $value,
                                'primary' => true,
                            ];
                            $phoneNumberData[] = $o;
                            $entity->set('phoneNumberData', $phoneNumberData);
                            continue;
                        }

                        if ($type == 'personName') {
                            $firstNameAttribute = 'first' . ucfirst($attribute);
                            $lastNameAttribute = 'last' . ucfirst($attribute);

                            $personName = $this->parsePersonName($value, $params['personNameFormat']);

                            if (!$entity->get($firstNameAttribute)) {
                                $personName['firstName'] = $this->prepareAttributeValue($entity, $firstNameAttribute, $personName['firstName']);
                                $entity->set($firstNameAttribute, $personName['firstName']);
                            }
                            if (!$entity->get($lastNameAttribute)) {
                                $personName['lastName'] = $this->prepareAttributeValue($entity, $lastNameAttribute, $personName['lastName']);
                                $entity->set($lastNameAttribute, $personName['lastName']);
                            }
                            continue;
                        }

                        $entity->set($attribute, $this->parseValue($entity, $attribute, $value, $params));
                    }
                } else {
                    if (in_array($attribute, $phoneFieldList) && !empty($value)) {
                        $phoneNumberData = $entity->get('phoneNumberData');
                        $isPrimary = false;
                        if (empty($phoneNumberData)) {
                            $phoneNumberData = [];
                            if (empty($valueMap->phoneNumber)) $isPrimary = true;
                        }
                        $type = str_replace('phoneNumber', '', $attribute);
                        $type = str_replace('_', ' ', $type);
                        $o = (object) [
                            'phoneNumber' => $value,
                            'type' => $type,
                            'primary' => $isPrimary,
                        ];
                        $phoneNumberData[] = $o;
                        $entity->set('phoneNumberData', $phoneNumberData);
                    }

                    if (
                        strpos($attribute, 'emailAddress') === 0 && $attribute !== 'emailAddress'
                        &&
                        $entity->hasAttribute('emailAddress')
                        &&
                        $entity->hasAttribute('emailAddressData')
                        &&
                        is_numeric(substr($attribute, 12))
                        &&
                        intval(substr($attribute, 12)) >= 2
                        &&
                        intval(substr($attribute, 12)) <= 4
                        &&
                        !empty($value)
                    ) {
                        $emailAddressData = $entity->get('emailAddressData');
                        $isPrimary = false;
                        if (empty($emailAddressData)) {
                            $emailAddressData = [];
                            if (empty($valueMap->emailAddress)) $isPrimary = true;
                        }
                        $o = (object) [
                            'emailAddress' => $value,
                            'primary' => $isPrimary,
                        ];
                        $emailAddressData[] = $o;
                        $entity->set('emailAddressData', $emailAddressData);
                    }
                }
            }
        }

        $defaultCurrency = $this->getConfig('defaultCurrency');
        if (!empty($params['currency'])) {
            $defaultCurrency = $params['currency'];
        }

        $mFieldsDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields'], []);

        foreach ($mFieldsDefs as $field => $defs) {
            if (!empty($defs['type']) && $defs['type'] === 'currency') {
                if ($entity->has($field) && !$entity->get($field . 'Currency')) {
                    $entity->set($field . 'Currency', $defaultCurrency);
                }
            }
        }

        foreach ($importAttributeList as $i => $attribute) {
            if (!array_key_exists($attribute, $attributeDefs)) continue;;
            $defs = $attributeDefs[$attribute];
            $type = $attributeDefs[$attribute]['type'];

            if (in_array($type, [Entity::FOREIGN, Entity::VARCHAR]) && !empty($defs['foreign'])) {
                $relatedEntityIsPerson = is_array($defs['foreign']) && in_array('firstName', $defs['foreign']) && in_array('lastName', $defs['foreign']);

                if ($defs['foreign'] === 'name' || $relatedEntityIsPerson) {
                    if ($entity->has($attribute)) {
                        $relation = $defs['relation'];
                        if ($attribute == $relation . 'Name' && !$entity->has($relation . 'Id') && array_key_exists($relation, $relDefs)) {
                            if ($relDefs[$relation]['type'] == Entity::BELONGS_TO) {
                                $value = $entity->get($attribute);
                                $scope = $relDefs[$relation]['entity'];

                                if ($relatedEntityIsPerson) {
                                    $where = $this->parsePersonName($value, $params['personNameFormat']);
                                } else {
                                    $where['name'] = $value;
                                }

                                $found = $this->getEntityManager()->getRepository($scope)->where($where)->findOne();

                                if ($found) {
                                    $entity->set($relation . 'Id', $found->id);
                                    $entity->set($relation . 'Name', $found->get('name'));
                                } else {
                                    if (!in_array($scope, ['User', 'Team'])) {
                                        // TODO create related record with name $name and relate
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $result = [];

        try {
            if ($isNew) {
                $isDuplicate = false;
                if (empty($params['skipDuplicateChecking'])) {
                    $isDuplicate = $recordService->checkIsDuplicate($entity);
                }
            }
            if ($entity->id) {
                $sql = $this->getEntityManager()->getRepository($entity->getEntityType())->deleteFromDb($entity->id, true);
            }
            $saveResult = $this->getEntityManager()->saveEntity($entity, [
                'noStream' => true,
                'noNotifications' => true,
                'import' => true,
                'silent' => !empty($params['silentMode']),
            ]);
            if ($saveResult) {
                $result['id'] = $entity->id;
                if ($isNew) {
                    $result['isImported'] = true;
                    if ($isDuplicate) {
                        $result['isDuplicate'] = true;
                    }
                } else {
                    $result['isUpdated'] = true;
                }
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->error('Import: [' . $e->getCode() . '] ' .$e->getMessage());
        }

        return $result;
    }

    protected function prepareAttributeValue($entity, $attribute, $value)
    {
        if ($entity->getAttributeType($attribute) === $entity::VARCHAR) {
            $maxLength = $entity->getAttributeParam($attribute, 'len');
            if ($maxLength) {
                if (mb_strlen($value) > $maxLength) {
                    $value = substr($value, 0, $maxLength);
                }
            }
        }
        return $value;
    }

    protected function parsePersonName($value, $format)
    {
        $firstName = '';
        $lastName = $value;
        switch ($format) {
            case 'f l':
                $pos = strpos($value, ' ');
                if ($pos) {
                    $firstName = trim(substr($value, 0, $pos));
                    $lastName = trim(substr($value, $pos + 1));
                }
                break;
            case 'l f':
                $pos = strpos($value, ' ');
                if ($pos) {
                    $lastName = trim(substr($value, 0, $pos));
                    $firstName = trim(substr($value, $pos + 1));
                }
                break;
            case 'l, f':
                $pos = strpos($value, ',');
                if ($pos) {
                    $lastName = trim(substr($value, 0, $pos));
                    $firstName = trim(substr($value, $pos + 1));
                }
                break;
        }
        return ['firstName' => $firstName, 'lastName' => $lastName];
    }

    protected function parseValue(Entity $entity, $attribute, $value, $params = [])
    {
        $decimalMark = '.';
        if (!empty($params['decimalMark'])) {
            $decimalMark = $params['decimalMark'];
        }

        $dateFormat = 'Y-m-d';
        if (!empty($params['dateFormat'])) {
            if (!empty($this->dateFormatsMap[$params['dateFormat']])) {
                $dateFormat = $this->dateFormatsMap[$params['dateFormat']];
            }
        }

        $timeFormat = 'H:i';
        if (!empty($params['timeFormat'])) {
            if (!empty($this->timeFormatsMap[$params['timeFormat']])) {
                $timeFormat = $this->timeFormatsMap[$params['timeFormat']];
            }
        }

        $type = $entity->getAttributeType($attribute);

        switch ($type) {
            case Entity::DATE:
                $dt = \DateTime::createFromFormat($dateFormat, $value);
                if ($dt) {
                    return $dt->format('Y-m-d');
                }
                return null;
                break;
            case Entity::DATETIME:
                $timezone = new \DateTimeZone(isset($params['timezone']) ? $params['timezone'] : 'UTC');
                $dt = \DateTime::createFromFormat($dateFormat . ' ' . $timeFormat, $value, $timezone);
                if ($dt) {
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    return $dt->format('Y-m-d H:i:s');
                }
                return null;
                break;
            case Entity::FLOAT:
                $a = explode($decimalMark, $value);
                $a[0] = preg_replace('/[^A-Za-z0-9\-]/', '', $a[0]);

                if (count($a) > 1) {
                    return floatval($a[0] . '.' . $a[1]);
                } else {
                    return floatval($a[0]);
                }
            case Entity::INT:
                return intval($value);
            case Entity::JSON_OBJECT:
                $value = \Espo\Core\Utils\Json::decode($value);
                return $value;
            case Entity::JSON_ARRAY:
                if (!is_string($value)) return;
                if (!strlen($value)) return;
                if ($value[0] === '[') {
                    $value = \Espo\Core\Utils\Json::decode($value);
                    return $value;
                } else {
                    $value = explode(',', $value);
                    return $value;
                }
        }

        $value = $this->prepareAttributeValue($entity, $attribute, $value);

        return $value;
    }

    protected function getRecordService($scope)
    {
        if (empty($this->services[$scope])) {
            if ($this->getServiceFactory()->checkExists($scope)) {
                $service = $this->getServiceFactory()->create($scope);
            } else {
                $service = $this->getServiceFactory()->create('Record');
                $service->setEntityType($scope);
            }
            $this->services[$scope] = $service;
        }
        return $this->services[$scope];
    }

    public function unmarkAsDuplicate($id, $entityType, $entityId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            UPDATE import_entity
            SET is_duplicate = 0
            WHERE
                import_id = ".$pdo->quote($id)." AND
                entity_type = ".$pdo->quote($entityType)." AND
                entity_id = ".$pdo->quote($entityId)."
        ";

        if ($pdo->query($sql)) {
            return true;
        }
    }
}
