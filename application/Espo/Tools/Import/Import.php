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

namespace Espo\Tools\Import;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\PhoneNumber\Sanitizer as PhoneNumberSanitizer;
use Espo\Core\FieldValidation\Exceptions\ValidationError;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Entities\Attachment;
use Espo\Entities\ImportError;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\Tools\Import\Jobs\RunIdle;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldValidation\Failure;
use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Import as ImportEntity;
use Espo\Entities\ImportEntity as ImportEntityEntity;

use stdClass;
use DateTime;
use DateTimeZone;
use Exception;
use LogicException;
use PDOException;


class Import
{
    private const DEFAULT_DELIMITER = ',';
    private const DEFAULT_TEXT_QUALIFIER = '"';
    private const DEFAULT_ACTION = Params::ACTION_CREATE;
    private const DEFAULT_DECIMAL_MARK = '.';
    private const DEFAULT_DATE_FORMAT = 'YYYY-MM-DD';
    private const DEFAULT_TIME_FORMAT = 'HH:mm';

    /** @var string[] */
    private $attributeList = [];
    private Params $params;

    private ?string $id = null;
    private ?string $attachmentId = null;
    private ?string $entityType = null;

    public function __construct(
        private AclManager $aclManager,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Config $config,
        private User $user,
        private FileStorageManager $fileStorageManager,
        private RecordServiceContainer $recordServiceContainer,
        private JobSchedulerFactory $jobSchedulerFactory,
        private Log $log,
        private FieldValidationManager $fieldValidationManager,
        private PhoneNumberSanitizer $phoneNumberSanitizer

    ) {
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
     *
     * @param string[] $attributeList
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

    /**
     * @throws Error
     */
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
     * @throws Error
     * @throws Forbidden
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

        assert(is_string($this->entityType));
        assert(is_string($this->attachmentId));

        if (!$this->user->isAdmin()) {
            $forbiddenAttributeList =
                $this->aclManager->getScopeForbiddenAttributeList($this->user, $this->entityType, Table::ACTION_EDIT);

            foreach ($attributeList as $i => $attribute) {
                if (in_array($attribute, $forbiddenAttributeList)) {
                    unset($attributeList[$i]);
                }
            }

            if (!$this->aclManager->checkScope($this->user, $this->entityType, Table::ACTION_CREATE)) {
                throw new Forbidden("Import: Create is forbidden for $this->entityType.");
            }
        }

        /** @var ?Attachment $attachment */
        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $this->attachmentId);

        if (!$attachment) {
            throw new Error('Import: Attachment not found.');
        }

        $contents = $this->fileStorageManager->getContents($attachment);

        if (empty($contents)) {
            throw new Error('Import: Empty contents.');
        }

        $startFromIndex = null;

        if ($this->id) {
            /** @var ?ImportEntity $import */
            $import = $this->entityManager->getEntityById(ImportEntity::ENTITY_TYPE, $this->id);

            if (!$import) {
                throw new Error('Import: Could not find import record.');
            }

            if ($params->startFromLastIndex()) {
                $startFromIndex = $import->get('lastIndex');
            }

            $import->set('status', ImportEntity::STATUS_IN_PROCESS);
        } else {
            /** @var ImportEntity $import */
            $import = $this->entityManager->getNewEntity(ImportEntity::ENTITY_TYPE);

            $import->set([
                'entityType' => $this->entityType,
                'fileId' => $this->attachmentId,
            ]);

            $import->set('status', ImportEntity::STATUS_IN_PROCESS);

            if ($params->isManualMode()) {
                $params = $params->withIdleMode(false);

                $import->set('status', ImportEntity::STATUS_STANDBY);
            } else if ($params->isIdleMode()) {
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
                'errorIndexes' => [],
            ];

            $i = -1;

            $contentsPrepared = str_replace(["\r\n", "\r"], "\n", $contents);

            $errorIndex = $params->headerRow() ? 1 : 0;

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

                $rowResult = $this->importRow($attributeList, $row, $i, $import, $errorIndex);

                if (!$rowResult) {
                    continue;
                }

                $import->set('lastIndex', $i);

                $this->entityManager->saveEntity($import, [
                    SaveOption::SKIP_HOOKS => true,
                    SaveOption::SILENT => true,
                ]);

                if ($rowResult['isError'] ?? false) {
                    $result->errorIndexes[] = $i;

                    continue;
                }

                $entityId = $rowResult['id'] ?? null;

                if (!$entityId) {
                    throw new LogicException();
                }

                if ($rowResult['isImported'] ?? false) {
                    $result->importedIds[] = $entityId;
                }

                if ($rowResult['isUpdated'] ?? false) {
                    $result->updatedIds[] = $entityId;
                }

                if ($rowResult['isDuplicate'] ?? false) {
                    $result->duplicateIds[] = $entityId;
                }

                $this->entityManager->createEntity(ImportEntityEntity::ENTITY_TYPE, [
                    'entityType' => $this->entityType,
                    'entityId' => $entityId,
                    'importId' => $import->getId(),
                    'isImported' => $rowResult['isImported'] ?? false,
                    'isUpdated' => $rowResult['isUpdated'] ?? false,
                    'isDuplicate' => $rowResult['isDuplicate'] ?? false,
                ]);
            }
        } catch (Exception $e) {
            $this->log->error('Import: ' . $e->getMessage());

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
            ->withCountUpdated(count($result->updatedIds))
            ->withCountDuplicate(count($result->duplicateIds))
            ->withCountError(count($result->errorIndexes));
    }

    /**
     * @param string[] $attributeList
     * @param string[] $row
     * @throws Error
     * @return array{
     *   id?: string,
     *   isError?: boolean,
     *   isDuplicate?: boolean,
     *   isImported?: boolean,
     *   isUpdated?: boolean,
     * }|null
     */
    private function importRow(
        array $attributeList,
        array $row,
        int $index,
        ImportEntity $import,
        int &$errorIndex
    ): ?array {

        assert(is_string($this->entityType));

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

        if (
            $action === Params::ACTION_CREATE_AND_UPDATE ||
            $action === Params::ACTION_UPDATE
        ) {
            if (!count($updateByAttributeList)) {
                return null;
            }

            $entity = $this->entityManager
                ->getRDBRepository($this->entityType)
                ->where($whereClause)
                ->findOne();

            if (
                $entity &&
                !$this->user->isAdmin() &&
                !$this->aclManager->checkEntityEdit($this->user, $entity)
            ) {
                $this->createError(
                    ImportError::TYPE_NO_ACCESS,
                    $index,
                    $row,
                    $import,
                    $errorIndex
                );

                return ['isError' => true];
            }

            if (!$entity && $action === Params::ACTION_UPDATE) {
                $this->createError(
                    ImportError::TYPE_NOT_FOUND,
                    $index,
                    $row,
                    $import,
                    $errorIndex
                );

                return ['isError' => true];
            }

            if (!$entity) {
                $entity = $this->entityManager->getNewEntity($this->entityType);

                if (array_key_exists('id', $whereClause)) {
                    $entity->set('id', $whereClause['id']);
                }
            }
        } else {
            $entity = $this->entityManager->getNewEntity($this->entityType);
        }

        if (!$entity instanceof CoreEntity) {
            throw new Error("Import: Only `Espo\Core\ORM\Entity` supported.");
        }

        $isNew = $entity->isNew();

        $entity->set($params->getDefaultValues());

        // Values are not supposed to be sanitized with the field Sanitizer.
        $valueMap = $this->prepareRowValueMap($attributeList, $row);

        $failureList = [];

        foreach ($attributeList as $i => $attribute) {
            if (
                empty($attribute) ||
                !array_key_exists($i, $row)
            ) {
                continue;
            }

            $value = $row[$i];

            try {
                $this->processRowItem($entity, $attribute, $value, $valueMap);
            } catch (ValidationError $e) {
                $failureList[] = $e->getFailure();
            }
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

        $this->processForeignNames($attributeList, $entity);
        $this->processForeignFields($attributeList, $entity);

        try {
            $failureList = array_merge(
                $failureList,
                $this->fieldValidationManager->processAll($entity)
            );

            if ($failureList !== []) {
                $this->createError(
                    ImportError::TYPE_VALIDATION,
                    $index,
                    $row,
                    $import,
                    $errorIndex,
                    $failureList
                );

                return ['isError' => true];
            }

            $result = [];

            if ($isNew) {
                $isDuplicate = false;

                if (!$params->skipDuplicateChecking()) {
                    $isDuplicate = $recordService->checkIsDuplicate($entity);
                }
            }

            if ($entity->hasId()) {
                /** @noinspection PhpDeprecationInspection */
                $this->entityManager
                    ->getRDBRepository($entity->getEntityType())
                    ->deleteFromDb($entity->getId(), true);
            }

            $this->entityManager->saveEntity($entity, [
                SaveOption::NO_STREAM => true,
                SaveOption::NO_NOTIFICATIONS => true,
                SaveOption::IMPORT => true,
                SaveOption::SILENT => $params->isSilentMode(),
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
        } catch (Exception $e) {
            $errorType = null;

            if ((int) $e->getCode() === 23000 && $e instanceof PDOException) {
                $errorType = ImportError::TYPE_INTEGRITY_CONSTRAINT_VIOLATION;
            }

            $msg = "Import: " . $e->getMessage();

            if (!$errorType && !$e->getMessage()) {
                $msg .= "; {$e->getFile()}, {$e->getLine()}";
            }

            $this->log->error($msg);

            $this->createError(
                $errorType,
                $index,
                $row,
                $import,
                $errorIndex
            );

            return ['isError' => true];
        }

        return $result;
    }

    private function processForeignAttribute(CoreEntity $entity, string $attribute): void
    {
        $value = $entity->get($attribute);

        if ($value === null) {
            return;
        }

        $foreignAttribute = $entity->getAttributeParam($attribute, AttributeParam::FOREIGN);
        $relation = $entity->getAttributeParam($attribute, AttributeParam::RELATION);

        if (!$relation) {
            return;
        }

        $idAttribute = $relation . 'Id';

        if ($entity->isNew() && $entity->has($idAttribute)) {
            return;
        }

        if ($foreignAttribute === 'name' && $attribute !== $relation . 'Name') {
            return;
        }

        if (!$entity->isNew() && $entity->isAttributeChanged($idAttribute)) {
            return;
        }

        if (
            !$entity->isNew() &&
            $entity->has($idAttribute) &&
            !$entity->isAttributeChanged($attribute)
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

        $foreignEntityType = $entity->getRelationParam($relation, RelationParam::ENTITY);

        if (!$foreignEntityType) {
            return;
        }

        $where = [$foreignAttribute => $value];

        if (
            $foreignAttribute === 'name' &&
            $this->getFieldType($foreignEntityType, $foreignAttribute) === FieldType::PERSON_NAME
        ) {
            $where = $this->parsePersonName($value, $this->params->getPersonNameFormat() ?? '');
        }

        $found = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->select([Attribute::ID, $foreignAttribute])
            ->where($where)
            ->findOne();

        if ($found) {
            $entity->set($idAttribute, $found->getId());
            //$entity->set($relation . 'Name', $found->get($foreignAttribute));

            //return;
        }

        //if (!in_array($foreignEntityType, ['User', 'Team'])) {
            // @todo Create related record with name $name and relate.
        //}
    }

    /**
     * @throws ValidationError
     */
    private function processRowItem(
        CoreEntity $entity,
        string $attribute,
        string $value,
        stdClass $valueMap
    ): void {

        assert(is_string($this->entityType));

        $params = $this->params;

        $action = $params->getAction() ?? self::DEFAULT_ACTION;

        if ($attribute === Attribute::ID) {
            if ($action === Params::ACTION_CREATE && $value !== '') {
                $entity->set(Attribute::ID, $value);
            }

            return;
        }

        if ($entity->hasAttribute($attribute)) {
            $attributeType = $entity->getAttributeType($attribute);

            if ($value !== '') {
                $type = $this->getFieldType($this->entityType, $attribute);

                if ($attribute === 'emailAddress' && $type === FieldType::EMAIL) {
                    $this->processFieldEmail($entity, $value);

                    return;
                }

                if ($attribute === 'phoneNumber' && $type === FieldType::PHONE) {
                    $this->processFieldPhone($params, $entity, $value);

                    return;
                }

                if ($type === FieldType::PERSON_NAME) {
                    $this->processFieldPersonName($params, $entity, $attribute, $value);

                    return;
                }
            }

            if ($value === '' && $attributeType !== Entity::BOOL && $entity->isNew()) {
                return;
            }

            $entity->set($attribute, $this->parseValue($entity, $attribute, $value));

            return;
        }

        $toExit = $this->processFieldPhoneMulti($params, $entity, $attribute, $value, $valueMap);

        if ($toExit) {
            return;
        }

        $this->processFieldEmailMulti($entity, $attribute, $value, $valueMap);
    }

    /**
     * @throws ValidationError
     */
    private function parseValue(CoreEntity $entity, string $attribute, string $value): mixed
    {
        $params = $this->params;

        /** @var non-empty-string $decimalMark */
        $decimalMark = $params->getDecimalMark() ?? self::DEFAULT_DECIMAL_MARK;

        $dateFormat = DateTimeUtil::convertFormatToSystem(
            $params->getDateFormat() ?? self::DEFAULT_DATE_FORMAT
        );

        $timeFormat = DateTimeUtil::convertFormatToSystem(
            $params->getTimeFormat() ?? self::DEFAULT_TIME_FORMAT
        );

        $timezone = $params->getTimezone() ?? 'UTC';

        $type = $entity->getAttributeType($attribute);

        if ($type !== Entity::BOOL && $value === '') {
            return null;
        }

        /*if ($type !== Entity::BOOL && strtolower($value) === 'null') {
            return null;
        }*/

        $fieldDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->tryGetField($attribute);

        if ($fieldDefs) {
            $fieldType = $fieldDefs->getType();

            if (
                $fieldType === FieldType::CURRENCY &&
                $fieldDefs->getParam(FieldParam::DECIMAL)
            ) {
                $value = $this->transformFloatString($decimalMark, $value);

                if ($value === null) {
                    throw ValidationError::create(
                        new Failure($entity->getEntityType(), $attribute, 'valid')
                    );
                }

                return $value;
            }

            if ($fieldType === FieldType::URL) {
                $value = self::encodeUrl($value);
            }
        }

        switch ($type) {
            case Entity::DATE:
                $dt = DateTime::createFromFormat($dateFormat, $value);

                $errorData = DateTime::getLastErrors();

                if (!$dt || ($errorData && $errorData['warnings'] !== [])) {
                    throw ValidationError::create(
                        new Failure($entity->getEntityType(), $attribute, 'valid')
                    );
                }

                return $dt->format(DateTimeUtil::SYSTEM_DATE_FORMAT);

            case Entity::DATETIME:
                /** @noinspection PhpUnhandledExceptionInspection */
                $timezone = new DateTimeZone($timezone);

                $dt = DateTime::createFromFormat($dateFormat . ' ' . $timeFormat, $value, $timezone);

                $errorData = DateTime::getLastErrors();

                if (!$dt || ($errorData && $errorData['warnings'] !== [])) {
                    throw ValidationError::create(
                        new Failure($entity->getEntityType(), $attribute, 'valid')
                    );
                }

                $dt->setTimezone(new DateTimeZone('UTC'));

                return $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            case Entity::FLOAT:
                $value = $this->transformFloatString($decimalMark, $value);

                if ($value === null) {
                    throw ValidationError::create(
                        new Failure($entity->getEntityType(), $attribute, 'valid')
                    );
                }

                return floatval($value);

            case Entity::INT:
                $replaceList = [
                    ' ',
                    $decimalMark === '.' ? ',' : '.',
                ];

                $value = str_replace($replaceList, '', $value);

                if (str_contains($value, $decimalMark) || !is_numeric($value)) {
                    throw ValidationError::create(
                        new Failure($entity->getEntityType(), $attribute, 'valid')
                    );
                }

                return intval($value);

            case Entity::BOOL:
                if ($value !== '0' && $value && strtolower($value) !== 'false') {
                    return true;
                }

                return false;

            case Entity::JSON_OBJECT:
                return Json::decode($value);

            case Entity::JSON_ARRAY:
                if (!strlen($value)) {
                    return [];
                }

                if ($value[0] === '[') {
                    return Json::decode($value);
                }

                return array_map(fn ($it) => trim($it), explode(',', $value));
        }

        return $this->prepareAttributeValue($entity, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function prepareAttributeValue(CoreEntity $entity, string $attribute, $value)
    {
        if ($entity->getAttributeType($attribute) === $entity::VARCHAR) {
            $maxLength = $entity->getAttributeParam($attribute, AttributeParam::LEN);

            if ($maxLength && mb_strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return $value;
    }

    /**
     * @return array{
     *   firstName: ?string,
     *   lastName: ?string,
     *   middleName?: ?string,
     * }
     */
    private function parsePersonName(string $value, string $format): array
    {
        $firstName = null;
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

    /**
     * @return string[]
     */
    private function readCsvString(
        string &$string,
        string $separator = ';',
        string $enclosure = '"'
    ): array {
        $o = [];

        $cnt = strlen($string);
        $esc = false;
        $escEsc = false;

        $num = 0;
        $i = 0;

        while ($i < $cnt) {
            $s = $string[$i];

            if ($s == "\n") {
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

                    //$esc = false;
                    $escEsc = false;
                }
            } else if ($s == $enclosure) {
                if ($escEsc) {
                    $o[$num] .= $enclosure;
                }

                if ($esc) {
                    $esc = false;

                    $escEsc = true;
                } else {
                    $esc = true;

                    $escEsc = false;
                }
            } else {
                if (!array_key_exists($num, $o)) {
                    $o[$num] = '';
                }

                if ($escEsc) {
                    $o[$num] .= $enclosure;

                    $escEsc = false;
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

        ksort($o);

        return $o;
    }

    /**
     * @param ImportError::TYPE_*|null $type
     * @param string[] $row
     * @param Failure[] $failureList
     * @noinspection PhpDocSignatureInspection
     */
    private function createError(
        ?string $type,
        int $index,
        array $row,
        ImportEntity $import,
        int &$errorIndex,
        ?array $failureList = null
    ): void {
        $validationFailures = null;

        if ($type === ImportError::TYPE_VALIDATION && $failureList !== null) {
            $validationFailures = [];

            foreach ($failureList as $failure) {
                $validationFailures[] = (object) [
                    'entityType' => $failure->getEntityType(),
                    'field' => $failure->getField(),
                    'type' => $failure->getType(),
                ];
            }
        }

        $this->entityManager->createEntity(ImportError::ENTITY_TYPE, [
            'type' => $type,
            'rowIndex' => $index,
            'exportRowIndex' => $errorIndex,
            'row' => $row,
            'importId' => $import->getId(),
            'validationFailures' => $validationFailures,
        ]);

        $errorIndex++;
    }

    private function formatPhoneNumber(string $value, Params $params): string
    {
        return $this->phoneNumberSanitizer->sanitize($value, $params->getPhoneNumberCountry());
    }

    /**
     * @param non-empty-string $decimalMark
     */
    private function transformFloatString(string $decimalMark, string $value): ?string
    {
        $a = explode($decimalMark, $value);

        $left = $a[0];
        $right = $a[1] ?? null;

        $replaceList = [
            ' ',
            $decimalMark === '.' ? ',' : '.',
        ];

        $left = str_replace($replaceList, '', $left);

        if (!is_numeric($left)) {
            return null;
        }

        if ($right !== null) {
            return $left . '.' . $right;
        }

        return $left;
    }

    private function getFieldType(string $entityType, string $field): ?string
    {
        return $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->tryGetField($field)
            ?->getType();
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function getFieldParam(string $entityType, string $field, string $param): mixed
    {
        return $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->tryGetField($field)
            ?->getParam($param);
    }

    /**
     * @param string[] $attributeList
     * @param string[] $row
     */
    private function prepareRowValueMap(array $attributeList, array $row): stdClass
    {
        $valueMap = (object) [];

        foreach ($attributeList as $i => $attribute) {
            if (empty($attribute)) {
                continue;
            }

            if (!array_key_exists($i, $row)) {
                continue;
            }

            $valueMap->$attribute = $row[$i];
        }

        return $valueMap;
    }

    /**
     * @param string[] $attributeList
     * @param CoreEntity $entity
     */
    private function processForeignNames(array $attributeList, CoreEntity $entity): void
    {
        foreach ($attributeList as $attribute) {
            if (!$entity->hasAttribute($attribute)) {
                continue;
            }

            if (
                $entity->getAttributeType($attribute) === Entity::FOREIGN &&
                $entity->getAttributeParam($attribute, AttributeParam::FOREIGN) === Field::NAME
            ) {
                $this->processForeignAttribute($entity, $attribute);
            }
        }
    }

    private static function encodeUrl(string $string): string
    {
        /** @noinspection RegExpRedundantEscape */
        $result = preg_replace_callback(
            "/[^-\._~:\/\?#\\[\\]@!\$&'\(\)\*\+,;=]+/",
            fn ($match) => rawurlencode($match[0]),
            $string
        );

        return $result ?? $string;
    }

    /**
     * @param string[] $attributeList
     * @param CoreEntity $entity
     */
    private function processForeignFields(array $attributeList, CoreEntity $entity): void
    {
        foreach ($attributeList as $attribute) {
            if (!$entity->hasAttribute($attribute)) {
                continue;
            }

            assert($this->entityType !== null);

            if (
                $entity->getAttributeType($attribute) === Entity::FOREIGN &&
                $entity->getAttributeParam($attribute, AttributeParam::FOREIGN) !== Field::NAME &&
                $this->getFieldParam($this->entityType, $attribute, 'relateOnImport')
            ) {
                $this->processForeignAttribute($entity, $attribute);
            }
        }
    }

    private function processFieldPersonName(
        Params $params,
        CoreEntity $entity,
        string $attribute,
        string $value
    ): void {

        $firstNameAttribute = 'first' . ucfirst($attribute);
        $lastNameAttribute = 'last' . ucfirst($attribute);
        $middleNameAttribute = 'middle' . ucfirst($attribute);

        $personNameData = $this->parsePersonName($value, $params->getPersonNameFormat() ?? '');

        if (!$entity->get($firstNameAttribute) && isset($personNameData['firstName'])) {
            $personNameData['firstName'] = $this->prepareAttributeValue(
                $entity,
                $firstNameAttribute,
                $personNameData['firstName']
            );

            $entity->set($firstNameAttribute, $personNameData['firstName']);
        }

        if (!$entity->get($lastNameAttribute)) {
            $personNameData['lastName'] = $this->prepareAttributeValue(
                $entity,
                $lastNameAttribute,
                $personNameData['lastName']
            );

            $entity->set($lastNameAttribute, $personNameData['lastName']);
        }

        if (!$entity->get($middleNameAttribute) && isset($personNameData['middleName'])) {
            $personNameData['middleName'] = $this->prepareAttributeValue(
                $entity,
                $middleNameAttribute,
                $personNameData['middleName']
            );

            $entity->set($middleNameAttribute, $personNameData['middleName']);
        }
    }

    private function processFieldPhone(Params $params, CoreEntity $entity, string $value): void
    {
        $phoneNumberData = $entity->get('phoneNumberData');
        $phoneNumberData = $phoneNumberData ?? [];

        if (str_starts_with($value, "'+")) {
            $value = substr($value, 1);
        }

        $o = (object)[
            'phoneNumber' => $this->formatPhoneNumber($value, $params),
            'primary' => true,
        ];

        $phoneNumberData[] = $o;
        $entity->set('phoneNumberData', $phoneNumberData);
    }

    private function processFieldEmail(CoreEntity $entity, string $value): void
    {
        $emailAddressData = $entity->get('emailAddressData');
        $emailAddressData = $emailAddressData ?? [];

        $o = (object)[
            'emailAddress' => $value,
            'primary' => true,
        ];

        $emailAddressData[] = $o;

        $entity->set('emailAddressData', $emailAddressData);
    }

    private function processFieldPhoneMulti(
        Params $params,
        CoreEntity $entity,
        string $attribute,
        string $value,
        stdClass $valueMap
    ): bool {

        assert(is_string($this->entityType));

        $phoneFieldList = [];

        if (
            $entity->hasAttribute('phoneNumber') &&
            $entity->getAttributeParam('phoneNumber', 'fieldType') === FieldType::PHONE
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

            if (str_starts_with($value, "'+")) {
                $value = substr($value, 1);
            }

            $phoneNumberData[] = (object)[
                'phoneNumber' => $this->formatPhoneNumber($value, $params),
                'type' => $type,
                'primary' => $isPrimary,
            ];

            $entity->set('phoneNumberData', $phoneNumberData);

            return true;
        }

        return false;
    }

    private function processFieldEmailMulti(
        CoreEntity $entity,
        string $attribute,
        string $value,
        stdClass $valueMap
    ): void {

        if (
            str_starts_with($attribute, 'emailAddress') && $attribute !== 'emailAddress' &&
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

            $o = (object)[
                'emailAddress' => $value,
                'primary' => $isPrimary,
            ];

            $emailAddressData[] = $o;

            $entity->set('emailAddressData', $emailAddressData);
        }
    }
}
