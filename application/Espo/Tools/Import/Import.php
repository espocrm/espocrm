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

namespace Espo\Tools\Import;

use Espo\Core\Job\JobSchedulerFactory;

use Espo\Tools\Import\Jobs\RunIdle;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    Exceptions\Error,
    Utils\Json,
    AclManager,
    Utils\Metadata,
    Utils\Config,
    FileStorage\Manager as FileStorageManager,
    Record\ServiceContainer as RecordServiceContainer,
    Utils\DateTime as DateTimeUtil,
    Utils\Log,
};

use Espo\ORM\EntityManager;

use Espo\Entities\User;
use Espo\Entities\Import as ImportEntity;

use stdClass;
use DateTime;
use DateTimeZone;
use Exception;

class Import
{
    private const DEFAULT_DELIMITER = ',';

    private const DEFAULT_TEXT_QUALIFIER = '"';

    private const DEFAULT_ACTION = Params::ACTION_CREATE;

    private const DEFAULT_DECIMAL_MARK = '.';

    private const DEFAULT_DATE_FORMAT = 'YYYY-MM-DD';

    private const DEFAULT_TIME_FORMAT = 'HH:mm';

    private $attributeList = [];

    private $params;

    private $id = null;

    private $attachmentId = null;

    private $entityType = null;

    private $aclManager;

    private $entityManager;

    private $metadata;

    private $config;

    private $user;

    private $fileStorageManager;

    private $jobSchedulerFactory;

    private $recordServiceContainer;

    private $log;

    public function __construct(
        AclManager $aclManager,
        EntityManager $entityManager,
        Metadata $metadata,
        Config $config,
        User $user,
        FileStorageManager $fileStorageManager,
        RecordServiceContainer $recordServiceContainer,
        JobSchedulerFactory $jobSchedulerFactory,
        Log $log
    ) {
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->user = $user;
        $this->fileStorageManager = $fileStorageManager;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->jobSchedulerFactory = $jobSchedulerFactory;
        $this->log = $log;

        $this->params = Params::create();
    }

    /**
     * Set a user. ACL restriction will be applied for that user.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set an entity type.
     */
    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * Set an attachment ID. CSV attachment should be uploaded before import.
     */
    public function setAttachmentId(string $attachmentId): self
    {
        $this->attachmentId = $attachmentId;

        return $this;
    }

    /**
     * Set an ID of import record. If an import record already exists.
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set an attribute list to parse from CSV rows.
     */
    public function setAttributeList(array $attributeList): self
    {
        $this->attributeList = $attributeList;

        return $this;
    }

    /**
     * Set import parameters.
     */
    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

    private function validate(): void
    {
        if (!$this->entityType) {
            throw new Error("Entity type is not set.");
        }

        if (!$this->attachmentId) {
            throw new Error("Attachment ID is not set.");
        }
    }

    /**
     * Run import.
     */
    public function run(): Result
    {
        $this->validate();

        $params = $this->params;

        $attributeList = $this->attributeList;

        $delimiter = str_replace(
            '\t',
            "\t",
            $params->getDelimiter() ?? self::DEFAULT_DELIMITER
        );

        $enclosure = $params->getTextQualifier() ?? self::DEFAULT_TEXT_QUALIFIER;

        if (!$this->user->isAdmin()) {
            $forbiddenAttributeList =
                $this->aclManager->getScopeForbiddenAttributeList($this->user, $this->entityType, 'edit');

            foreach ($attributeList as $i => $attribute) {
                if (in_array($attribute, $forbiddenAttributeList)) {
                    unset($attributeList[$i]);
                }
            }

            if (!$this->aclManager->checkScope($this->user, $this->entityType, 'create')) {
                throw new Error('Import: Create is forbidden.');
            }
        }

        $attachment = $this->entityManager->getEntity('Attachment', $this->attachmentId);

        if (!$attachment) {
            throw new Error('Import error');
        }

        $contents = $this->fileStorageManager->getContents($attachment);

        if (empty($contents)) {
            throw new Error('Import error');
        }

        $startFromIndex = null;

        if ($this->id) {
            $import = $this->entityManager->getEntity('Import', $this->id);

            if (!$import) {
                throw new Error('Import: Could not find import record.');
            }

            if ($params->startFromLastIndex()) {
                $startFromIndex = $import->get('lastIndex');
            }

            $import->set('status', ImportEntity::STATUS_IN_PROCESS);
        }
        else {
            $import = $this->entityManager->getEntity(ImportEntity::ENTITY_TYPE);

            $import->set([
                'entityType' => $this->entityType,
                'fileId' => $this->attachmentId,
            ]);

            $import->set('status', ImportEntity::STATUS_IN_PROCESS);

            if ($params->isManualMode()) {
                $params = $params->withIdleMode(false);

                $import->set('status', ImportEntity::STATUS_STANDBY);
            }
            else if ($params->isIdleMode()) {
                $import->set('status', ImportEntity::STATUS_PENDING);
            }

            $import->set('params', $params->getRaw());
            $import->set('attributeList', $attributeList);
        }

        $this->entityManager->saveEntity($import);

        if (!$this->id && $params->isManualMode()) {
            return Result::create()
                ->withId($import->getId())
                ->withManualMode();
        }

        if ($params->isIdleMode()) {
            $this->jobSchedulerFactory
                ->create()
                ->setClassName(RunIdle::class)
                ->setData([
                    'entityType' => $this->entityType,
                    'params' => $params->withIdleMode(false)->getRaw(),
                    'attachmentId' => $this->attachmentId,
                    'importAttributeList' => $attributeList,
                    'importId' => $import->getId(),
                    'userId' => $this->user->getId(),
                ])
                ->schedule();

            return Result::create()->withId($import->getId());
        }

        $isFailed = false;

        try {
            $result = (object) [
                'importedIds' => [],
                'updatedIds' => [],
                'duplicateIds' => [],
            ];

            $i = -1;

            $contentsPrepared = str_replace(["\r\n", "\r"], "\n", $contents);

            while ($row = $this->readCsvString($contentsPrepared, $delimiter, $enclosure)) {
                $i++;

                if ($i == 0 && $params->headerRow()) {
                    continue;
                }

                if (count($row) == 1 && empty($row[0]) && count($attributeList) > 1) {
                    continue;
                }

                if (!is_null($startFromIndex) && $i <= $startFromIndex) {
                    continue;
                }

                $rowResult = $this->importRow($attributeList, $row);

                if (!$rowResult) {
                    continue;
                }

                $import->set('lastIndex', $i);

                $this->entityManager->saveEntity($import, [
                    'skipHooks' => true,
                    'silent' => true,
                ]);

                if ($rowResult->isImported ?? false) {
                    $result->importedIds[] = $rowResult->id;
                }

                if ($rowResult->isUpdated ?? false) {
                    $result->updatedIds[] = $rowResult->id;
                }

                if ($rowResult->isDuplicate ?? false) {
                    $result->duplicateIds[] = $rowResult->id;
                }

                $this->entityManager->createEntity('ImportEntity', [
                    'entityType' => $this->entityType,
                    'entityId' => $rowResult->id,
                    'importId' => $import->getId(),
                    'isImported' => $rowResult->isImported ?? false,
                    'isUpdated' => $rowResult->isUpdated ?? false,
                    'isDuplicate' => $rowResult->isDuplicate ?? false,
                ]);
            }
        }
        catch (Exception $e) {
            $this->log->error('Import Error: ' . $e->getMessage());

            $import->set('status', ImportEntity::STATUS_FAILED);

            $isFailed = true;
        }

        if (!$isFailed) {
            $import->set('status', ImportEntity::STATUS_COMPLETE);
        }

        $this->entityManager->saveEntity($import);

        return Result::create()
            ->withId($import->getId())
            ->withCountCreated(count($result->importedIds))
            ->withCountUpdated(count($result->updatedIds));
    }

    private function importRow(array $attributeList, array $row): ?stdClass
    {
        $params = $this->params;

        $action = $params->getAction() ?? self::DEFAULT_ACTION;

        if (empty($attributeList)) {
            return null;
        }

        $updateByAttributeList = [];
        $whereClause = [];

        if (in_array($action, [Params::ACTION_CREATE_AND_UPDATE, Params::ACTION_UPDATE])) {
            $updateBy = $params->getUpdateBy();

            if (count($updateBy)) {
                foreach ($updateBy as $i) {
                    if (array_key_exists($i, $attributeList)) {
                        $updateByAttributeList[] = $attributeList[$i];

                        $whereClause[$attributeList[$i]] = $row[$i];
                    }
                }
            }
        }

        $recordService = $this->recordServiceContainer->get($this->entityType);

        if (in_array($action, [Params::ACTION_CREATE_AND_UPDATE, Params::ACTION_UPDATE])) {
            if (!count($updateByAttributeList)) {
                return null;
            }

            $entity = $this->entityManager
                ->getRDBRepository($this->entityType)
                ->where($whereClause)
                ->findOne();

            if ($entity) {
                if (!$this->user->isAdmin()) {
                    if (!$this->aclManager->checkEntity($this->user, $entity, 'edit')) {
                        return null;
                    }
                }
            }

            if (!$entity) {
                if ($action === Params::ACTION_CREATE_AND_UPDATE) {
                    $entity = $this->entityManager->getEntity($this->entityType);

                    if (array_key_exists('id', $whereClause)) {
                        $entity->set('id', $whereClause['id']);
                    }
                } else {
                    return null;
                }
            }
        } else {
            $entity = $this->entityManager->getEntity($this->entityType);
        }

        assert($entity instanceof Entity);

        $isNew = $entity->isNew();

        $entity->set($params->getDefaultValues());

        $valueMap = (object) [];

        foreach ($attributeList as $i => $attribute) {
            if (empty($attribute)) {
                continue;
            }

            if (!array_key_exists($i, $row)) {
                continue;
            }

            $value = $row[$i];
            $valueMap->$attribute = $value;
        }

        foreach ($attributeList as $i => $attribute) {
            if (empty($attribute)) {
                continue;
            }

            if (!array_key_exists($i, $row)) {
                continue;
            }

            $value = $row[$i];

            $this->processRowItem($entity, $attribute, $value, $valueMap);
        }

        $defaultCurrency = $params->getCurrency() ?? $this->config->get('defaultCurrency');

        $fieldsDefs = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields']) ?? [];

        foreach ($fieldsDefs as $field => $defs) {
            $fieldType = $defs['type'] ?? null;

            if ($fieldType === 'currency') {
                if ($entity->has($field) && !$entity->get($field . 'Currency')) {
                    $entity->set($field . 'Currency', $defaultCurrency);
                }
            }
        }

        foreach ($attributeList as $attribute) {
            if (!$entity->hasAttribute($attribute)) {
                continue;
            }

            if (
                $entity->getAttributeType($attribute) === Entity::FOREIGN &&
                $entity->getAttributeParam($attribute, 'foreign') === 'name'
            ) {
                $this->processForeignName($entity, $attribute);
            }
        }

        $result = [];

        if ($isNew) {
            $isDuplicate = false;

            if (!$params->skipDuplicateChecking()) {
                $isDuplicate = $recordService->checkIsDuplicate($entity);
            }
        }

        if ($entity->getId()) {
            $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->deleteFromDb($entity->getId(), true);
        }

        try {
            $this->entityManager->saveEntity($entity, [
                'noStream' => true,
                'noNotifications' => true,
                'import' => true,
                'silent' => $params->isSilentMode(),
            ]);

            $result['id'] = $entity->getId();

            if ($isNew) {
                $result['isImported'] = true;

                if ($isDuplicate) {
                    $result['isDuplicate'] = true;
                }
            } else {
                $result['isUpdated'] = true;
            }
        }
        catch (Exception $e) {
            $this->log->error("Import: " . $e->getMessage());
        }

        return (object) $result;
    }

    private function processForeignName(Entity $entity, string $attribute): void
    {
        $relation = $entity->getAttributeParam($attribute, 'relation');

        if (!$relation) {
            return;
        }

        $foreignEntityType = $entity->getRelationParam($relation, 'entity');

        $isPerson = false;

        if ($foreignEntityType) {
            $isPerson = $this->metadata
                ->get(['entityDefs', $foreignEntityType, 'fields', 'name', 'type']) === 'personName';
        }

        if ($attribute !== $relation . 'Name') {
            return;
        }

        if ($entity->has($relation . 'Id') && $entity->isNew()) {
            return;
        }

        if (
            $entity->has($relation . 'Id') &&
            !$entity->isNew() &&
            !$entity->isAttributeChanged($relation . 'Name')
        ) {
            return;
        }

        if (!$entity->hasRelation($relation)) {
            return;
        }

        $relationType = $entity->getRelationType($relation);

        if ($relationType !== Entity::BELONGS_TO) {
            return;
        }

        $nameValue = $entity->get($attribute);

        if ($isPerson) {
            $where = $this->parsePersonName($nameValue, $this->params->getPersonNameFormat());
        }
        else {
            $where = [
                'name' => $nameValue,
            ];
        }

        $found = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->select(['id', 'name'])
            ->where($where)
            ->findOne();

        if ($found) {
            $entity->set($relation . 'Id', $found->getId());
            $entity->set($relation . 'Name', $found->get('name'));

            return;
        }

        if (!in_array($foreignEntityType, ['User', 'Team'])) {
            // @todo Create related record with name $name and relate.
        }
    }

    private function processRowItem(Entity $entity, string $attribute, $value, stdClass $valueMap): void
    {
        $params = $this->params;

        $action = $params->getAction() ?? self::DEFAULT_ACTION;

        if ($attribute === 'id') {
            if ($action === Params::ACTION_CREATE) {
                $entity->set('id', $value);
            }

            return;
        }

        if ($entity->hasAttribute($attribute)) {
            $attributeType = $entity->getAttributeType($attribute);

            if ($value !== '') {
                $type = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'type']);

                if ($attribute === 'emailAddress' && $type === 'email') {
                    $emailAddressData = $entity->get('emailAddressData');
                    $emailAddressData = $emailAddressData ?? [];

                    $o = (object) [
                        'emailAddress' => $value,
                        'primary' => true,
                    ];

                    $emailAddressData[] = $o;

                    $entity->set('emailAddressData', $emailAddressData);

                    return;
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

                    return;
                }

                if ($type === 'personName') {
                    $firstNameAttribute = 'first' . ucfirst($attribute);
                    $lastNameAttribute = 'last' . ucfirst($attribute);
                    $middleNameAttribute = 'middle' . ucfirst($attribute);

                    $personNameData = $this->parsePersonName($value, $params->getPersonNameFormat());

                    if (!$entity->get($firstNameAttribute) && isset($personNameData['firstName'])) {
                        $personNameData['firstName'] = $this->prepareAttributeValue(
                            $entity, $firstNameAttribute, $personNameData['firstName']
                        );

                        $entity->set($firstNameAttribute, $personNameData['firstName']);
                    }

                    if (!$entity->get($lastNameAttribute)) {
                        $personNameData['lastName'] = $this->prepareAttributeValue(
                            $entity, $lastNameAttribute, $personNameData['lastName']
                        );

                        $entity->set($lastNameAttribute, $personNameData['lastName']);
                    }

                    if (!$entity->get($middleNameAttribute) && isset($personNameData['middleName'])) {
                        $personNameData['middleName'] = $this->prepareAttributeValue(
                            $entity, $middleNameAttribute, $personNameData['middleName']
                        );

                        $entity->set($middleNameAttribute, $personNameData['middleName']);
                    }

                    return;
                }
            }

            if (
                $value === '' &&
                !in_array($attributeType, [Entity::BOOL])
            ) {
                return;
            }

            $entity->set($attribute, $this->parseValue($entity, $attribute, $value));

            return;
        }

        $phoneFieldList = [];

        if (
            $entity->hasAttribute('phoneNumber') &&
            $entity->getAttributeParam('phoneNumber', 'fieldType') === 'phone'
        ) {
            $typeList = $this->metadata
                ->get(['entityDefs', $this->entityType, 'fields', 'phoneNumber', 'typeList']) ?? [];

            foreach ($typeList as $type) {
                $phoneFieldList[] = 'phoneNumber' . str_replace(' ', '_', ucfirst($type));
            }
        }

        if (in_array($attribute, $phoneFieldList) && !empty($value)) {
            $phoneNumberData = $entity->get('phoneNumberData');
            $isPrimary = false;

            if (empty($phoneNumberData)) {
                $phoneNumberData = [];

                if (empty($valueMap->phoneNumber)) {
                    $isPrimary = true;
                }
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

            return;
        }

        if (
            strpos($attribute, 'emailAddress') === 0 && $attribute !== 'emailAddress' &&
            $entity->hasAttribute('emailAddress') &&
            $entity->hasAttribute('emailAddressData') &&
            is_numeric(substr($attribute, 12)) &&
            intval(substr($attribute, 12)) >= 2 &&
            intval(substr($attribute, 12)) <= 4 &&
            !empty($value)
        ) {
            $emailAddressData = $entity->get('emailAddressData');
            $isPrimary = false;

            if (empty($emailAddressData)) {
                $emailAddressData = [];

                if (empty($valueMap->emailAddress)) {
                    $isPrimary = true;
                }
            }

            $o = (object) [
                'emailAddress' => $value,
                'primary' => $isPrimary,
            ];

            $emailAddressData[] = $o;

            $entity->set('emailAddressData', $emailAddressData);

            return;
        }
    }

    /**
     * @return mixed
     */
    private function parseValue(Entity $entity, string $attribute, $value)
    {
        $params = $this->params;

        $decimalMark = $params->getDecimalMark() ?? self::DEFAULT_DECIMAL_MARK;

        $dateFormat = DateTimeUtil::convertFormatToSystem(
            $params->getDateFormat() ?? self::DEFAULT_DATE_FORMAT
        );

        $timeFormat = DateTimeUtil::convertFormatToSystem(
            $params->getTimeFormat() ?? self::DEFAULT_TIME_FORMAT
        );

        $timezone = $params->getTimezone() ?? 'UTC';

        $type = $entity->getAttributeType($attribute);

        switch ($type) {
            case Entity::DATE:
                $dt = DateTime::createFromFormat($dateFormat, $value);

                if ($dt) {
                    return $dt->format(DateTimeUtil::SYSTEM_DATE_FORMAT);
                }

                return null;

            case Entity::DATETIME:
                $timezone = new DateTimeZone($timezone);

                $dt = DateTime::createFromFormat($dateFormat . ' ' . $timeFormat, $value, $timezone);

                if ($dt) {
                    $dt->setTimezone(new DateTimeZone('UTC'));

                    return $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
                }

                return null;

            case Entity::FLOAT:
                $a = explode($decimalMark, $value);
                $a[0] = preg_replace('/[^A-Za-z0-9\-]/', '', $a[0]);

                if (count($a) > 1) {
                    return floatval($a[0] . '.' . $a[1]);
                }

                return floatval($a[0]);

            case Entity::INT:
                return intval($value);

            case Entity::BOOL:
                if ($value && strtolower($value) !== 'false' && $value !== '0') {
                    return true;
                }

                return false;

            case Entity::JSON_OBJECT:
                $value = Json::decode($value);

                return $value;

            case Entity::JSON_ARRAY:
                if (!is_string($value)) {
                    return null;
                }

                if (!strlen($value)) {
                    return null;
                }

                if ($value[0] === '[') {
                    $value = Json::decode($value);

                    return $value;
                }

                $value = explode(',', $value);

                return $value;
        }

        return $this->prepareAttributeValue($entity, $attribute, $value);
    }

    /**
     * @return mixed
     */
    private function prepareAttributeValue(Entity $entity, string $attribute, $value)
    {
        if ($entity->getAttributeType($attribute) === $entity::VARCHAR) {
            $maxLength = $entity->getAttributeParam($attribute, 'len');

            if ($maxLength && mb_strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return $value;
    }

    private function parsePersonName($value, string $format): array
    {
        $firstName = null;
        $lastName = $value;

        $middleName = null;

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

            case 'f m l':
                $pos = strpos($value, ' ');

                if ($pos) {
                    $firstName = trim(substr($value, 0, $pos));
                    $lastName = trim(substr($value, $pos + 1));

                    $value = $lastName;

                    $pos = strpos($value, ' ');

                    if ($pos) {
                        $middleName = trim(substr($value, 0, $pos));
                        $lastName = trim(substr($value, $pos + 1));

                        return [
                            'firstName' => $firstName,
                            'middleName' => $middleName,
                            'lastName' => $lastName,
                        ];
                    }
                }

                break;

            case 'l f m':
                $pos = strpos($value, ' ');
                if ($pos) {
                    $lastName = trim(substr($value, 0, $pos));
                    $firstName = trim(substr($value, $pos + 1));

                    $value = $firstName;

                    $pos = strpos($value, ' ');

                    if ($pos) {
                        $firstName = trim(substr($value, 0, $pos));
                        $middleName = trim(substr($value, $pos + 1));

                        return [
                            'firstName' => $firstName,
                            'middleName' => $middleName,
                            'lastName' => $lastName,
                        ];
                    }
                }

                break;
        }

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];
    }

    private function readCsvString(
        string &$string,
        string $separator = ';',
        string $enclosure = '"',
        string $linebreak = "\n"
    ): array {

        $o = [];

        $cnt = strlen($string);
        $esc = false;
        $escesc = false;

        $num = 0;
        $i = 0;

        while ($i < $cnt) {
            $s = $string[$i];

            if ($s == $linebreak) {
                if ($esc) {
                    $o[$num].= $s;
                } else {
                    $i++;
                    break;
                }
            } else if ($s == $separator) {
                if ($esc) {
                    $o[$num].= $s;
                } else {
                    $num++;
                    $esc = false;
                    $escesc = false;
                }
            } else if ($s == $enclosure) {
                if ($escesc) {
                    $o[$num].= $enclosure;
                    $escesc = false;
                }
                if ($esc) {
                    $esc = false;
                    $escesc = true;
                } else {
                    $esc = true;
                    $escesc = false;
                }
            } else {
                if (!array_key_exists($num, $o)) {
                    $o[$num] = '';
                }

                if ($escesc) {
                    $o[$num] .= $enclosure;
                    $escesc = false;
                }

                $o[$num] .= $s;
            }

            $i++;
        }

        $string = substr($string, $i);

        $keys = array_keys($o);
        $maxKey = end($keys);

        for ($i = 0; $i < $maxKey; $i++) {
            if (!array_key_exists($i, $o)) {
                $o[$i] = '';
            }
        }

        return $o;
    }
}
