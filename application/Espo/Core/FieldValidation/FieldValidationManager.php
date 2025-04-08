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

namespace Espo\Core\FieldValidation;

use Espo\Core\Utils\Log;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\Core\FieldValidation\Exceptions\ValidationError;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\FieldUtil;
use Espo\Tools\DynamicLogic\ConditionCheckerFactory;
use Espo\Tools\DynamicLogic\Exceptions\BadCondition;
use Espo\Tools\DynamicLogic\Item as LogicItem;

use LogicException;
use stdClass;
use ReflectionClass;

/**
 * A field validation manager.
 */
class FieldValidationManager
{
    /** @var array<string, ?object> */
    private array $checkerCache = [];
    /** @var array<string, ?Validator<Entity>> */
    private array $validatorCache = [];

    private CheckerFactory $checkerFactory;

    public function __construct(
        private Metadata $metadata,
        private FieldUtil $fieldUtil,
        CheckerFactory $factory,
        private ValidatorFactory $validatorFactory,
        private Defs $defs,
        private ConditionCheckerFactory $conditionCheckerFactory,
        private Log $log,
    ) {
        $this->checkerFactory = $factory;
    }

    /**
     * Process validation.
     *
     * @param Entity $entity An entity.
     * @param ?stdClass $data Raw request payload data.
     * @param ?FieldValidationParams $params Validation additional parameters.
     *
     * @throws ValidationError On the first invalid check.
     */
    public function process(Entity $entity, ?stdClass $data = null, ?FieldValidationParams $params = null): void
    {
        $this->processInternal($entity, $data, $params, true);
    }

    /**
     * Process validation w/o exception throwing.
     *
     * @param Entity $entity An entity.
     * @param ?stdClass $data Raw request payload data.
     * @param ?FieldValidationParams $params Validation additional parameters.
     * @return Failure[] A list of validation failures.
     */
    public function processAll(Entity $entity, ?stdClass $data = null, ?FieldValidationParams $params = null): array
    {
        try {
            return $this->processInternal($entity, $data, $params, false);
        } catch (ValidationError) {
            throw new LogicException();
        }
    }

    /**
     * @return Failure[]
     * @throws ValidationError On the first invalid check.
     */
    private function processInternal(
        Entity $entity,
        ?stdClass $data,
        ?FieldValidationParams $params,
        bool $throw
    ): array {

        $dataIsSet = $data !== null;
        $entityType = $entity->getEntityType();

        $data ??= (object) [];
        $params ??= new FieldValidationParams();
        $failureList = [];
        $fieldList = $this->getFieldList($entityType, $params);
        $entityDefs = $this->defs->getEntity($entityType);

        foreach ($fieldList as $field) {
            $typeList = null;

            if (
                !$entity->isNew() &&
                $dataIsSet &&
                !$entityDefs->tryGetField($field)?->getParam('forceValidation') &&
                !$this->isFieldSetInData($entityType, $field, $data)
            ) {
                if (!$this->hasRequiredLogic($entityType, $field)) {
                    continue;
                }

                $typeList = [Type::REQUIRED];
            }

            $itemFailureList = $this->processField(
                entity: $entity,
                field: $field,
                params: $params,
                data: $data,
                throw: $throw,
                typeList: $typeList,
            );

            $failureList = array_merge($failureList, $itemFailureList);
        }

        return $failureList;
    }

    /**
     * @return string[]
     */
    private function getMandatoryValidationList(string $entityType, string $field): array
    {
        /** @var ?string $fieldType */
        $fieldType = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

        return
            $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'mandatoryValidationList']) ??
            $this->metadata->get(['fields', $fieldType ?? '', 'mandatoryValidationList']) ?? [];
    }

    /**
     * @return string[]
     */
    private function getValidationList(string $entityType, string $field): array
    {
        /** @var ?string $fieldType */
        $fieldType = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

        return
            $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'validationList']) ??
            $this->metadata->get(['fields', $fieldType ?? '', 'validationList']) ?? [];
    }

    /**
     * Check a specific field for a specific validation type.
     *
     * @param Entity $entity An entity to check.
     * @param string $field A field to check.
     * @param string $type A validation type.
     * @param ?stdClass $data A payload.
     * @param mixed $value To override a validation value.
     */
    public function check(
        Entity $entity,
        string $field,
        string $type,
        ?stdClass $data = null,
        mixed $value = null
    ): bool {

        $data ??= (object) [];
        $entityType = $entity->getEntityType();

        $result = $this->processValidator($entity, $field, $type, new Data($data));

        if (!$result) {
            return false;
        }

        $validationValue = $value ?? $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, $type);
        $isMandatory = in_array($type, $this->getMandatoryValidationList($entityType, $field));

        if (
            $type === Type::REQUIRED &&
            !$validationValue &&
            $this->checkDynamicLogicRequired($entity, $field)
        ) {
            $validationValue = true;
        }

        $skip = !$isMandatory && (is_null($validationValue) || $validationValue === false);

        if ($skip) {
            return true;
        }

        $result1 = $this->processFieldCheck($entityType, $type, $entity, $field, $validationValue);

        if (!$result1) {
            return false;
        }

        $result2 = $this->processFieldRawCheck($entityType, $type, $data, $field, $validationValue);

        if (!$result2) {
            return false;
        }

        return true;
    }

    private function processValidator(Entity $entity, string $field, string $type, Data $data): bool
    {
        $validator = $this->getValidator($entity->getEntityType(), $field, $type);

        if (!$validator) {
            return true;
        }

        $failure = $validator->validate($entity, $field, $data);

        if ($failure) {
            return false;
        }

        return true;
    }

    /**
     * @return ?Validator<Entity>
     */
    private function getValidator(string $entityType, string $field, string $type): ?Validator
    {
        $key = $entityType . '_' . $field . '_' . $type;

        if (array_key_exists($key, $this->validatorCache)) {
            return $this->validatorCache[$key];
        }

        if (!$this->validatorFactory->isCreatable($entityType, $field, $type)) {
            $this->validatorCache[$key] = null;

            return null;
        }

        $validator = $this->validatorFactory->create($entityType, $field, $type);

        $this->validatorCache[$key] = $validator;

        return $validator;
    }

    /**
     * @param ?string[] $typeList
     * @return Failure[]
     * @throws ValidationError
     */
    private function processField(
        Entity $entity,
        string $field,
        FieldValidationParams $params,
        stdClass $data,
        bool $throw,
        ?array $typeList = null,
    ): array {

        $validationList = $this->getAllValidationList($entity->getEntityType(), $field, $params);

        foreach ($validationList as $type) {
            if ($typeList !== null && !in_array($type, $typeList)) {
                continue;
            }

            $result = $this->check($entity, $field, $type, $data);

            if ($result) {
                continue;
            }

            $failure = new Failure($entity->getEntityType(), $field, $type);

            if ($throw) {
                throw ValidationError::create($failure);
            }

            return [$failure];
        }

        $failure = $this->checkAdditional($entity, $field, new Data($data));

        if (!$failure) {
            return [];
        }

        if ($throw) {
            throw ValidationError::create($failure);
        }

        return [$failure];
    }

    /**
     * @return string[]
     */
    private function getAllValidationList(string $entityType, string $field, FieldValidationParams $params): array
    {
        $validationList = array_unique(array_merge(
            $this->getValidationList($entityType, $field),
            $this->getMandatoryValidationList($entityType, $field)
        ));

        /** @var string[] $suppressList */
        $suppressList = $this->metadata->get("entityDefs.$entityType.fields.$field.suppressValidationList") ?? [];

        $validationList = array_filter(
            $validationList,
            fn ($type) => !in_array($type, $suppressList)
        );

        $validationList = array_filter(
            $validationList,
            fn ($type) => !in_array($field, $params->getTypeSkipFieldList($type))
        );

        return array_values($validationList);
    }

    /**
     * @param mixed $validationValue
     */
    private function processFieldCheck(
        string $entityType,
        string $type,
        Entity $entity,
        string $field,
        $validationValue
    ): bool {

        $checker = $this->getFieldTypeChecker($entityType, $field);

        if (!$checker) {
            return true;
        }

        $methodName = 'check' . ucfirst($type);

        if (!method_exists($checker, $methodName)) {
            return true;
        }

        return $checker->$methodName($entity, $field, $validationValue);
    }

    /**
     * @param mixed $validationValue
     */
    private function processFieldRawCheck(
        string $entityType,
        string $type,
        stdClass $data,
        string $field,
        $validationValue
    ): bool {

        $checker = $this->getFieldTypeChecker($entityType, $field);

        if (!$checker) {
            return true;
        }

        $methodName = 'rawCheck' . ucfirst($type);

        if (!method_exists($checker, $methodName)) {
            return true;
        }

        return $checker->$methodName($data, $field, $validationValue);
    }

    private function getFieldTypeChecker(string $entityType, string $field): ?object
    {
        $key = $entityType . '_' . $field;

        if (!array_key_exists($key, $this->checkerCache)) {
            $this->loadFieldTypeChecker($entityType, $field);
        }

        return $this->checkerCache[$key];
    }

    private function loadFieldTypeChecker(string $entityType, string $field): void
    {
        $key = $entityType . '_' . $field;

        if (!$this->checkerFactory->isCreatable($entityType, $field)) {
            $this->checkerCache[$key] = null;

            return;
        }

        $this->checkerCache[$key] = $this->checkerFactory->create($entityType, $field);
    }

    private function isFieldSetInData(string $entityType, string $field, stdClass $data): bool
    {
        $attributeList = $this->fieldUtil->getActualAttributeList($entityType, $field);

        $isSet = false;

        foreach ($attributeList as $attribute) {
            if (property_exists($data, $attribute)) {
                $isSet = true;

                break;
            }
        }

        return $isSet;
    }

    private function checkAdditional(Entity $entity, string $field, Data $data): ?Failure
    {
        $validatorList = $this->validatorFactory->createAdditionalList($entity->getEntityType(), $field);

        foreach ($validatorList as $validator) {
            $itemFailure = $validator->validate($entity, $field, $data);

            if (!$itemFailure) {
                continue;
            }

            $type = lcfirst((new ReflectionClass($validator))->getShortName());

            return new Failure($entity->getEntityType(), $field, $type);
        }

        return null;
    }

    private function checkDynamicLogicRequired(Entity $entity, string $field): bool
    {
        $entityType = $entity->getEntityType();

        /** @var stdClass[] $group */
        $group = $this->metadata->getObjects("logicDefs.$entityType.fields.$field.required.conditionGroup");

        if (!is_array($group)) {
            return false;
        }

        $checker = $this->conditionCheckerFactory->create($entity);

        try {
            $item = LogicItem::fromGroupDefinition($group);

            return $checker->check($item);
        } catch (BadCondition $e) {
            $this->log->warning("Bad logic condition for $entityType $field.", ['exception' => $e]);
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType, FieldValidationParams $params): array
    {
        return array_filter(
            $this->fieldUtil->getEntityTypeFieldList($entityType),
            fn($field) => !in_array($field, $params->getSkipFieldList())
        );
    }

    private function hasRequiredLogic(string $entityType, string $field): bool
    {
        return (bool) $this->metadata->get("logicDefs.$entityType.fields.$field.required");
    }
}
