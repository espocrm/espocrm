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

namespace Espo\Tools\FieldManager;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Language;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\EntityManager\NameUtil;
use RuntimeException;
use stdClass;

/**
 * Field Manager tool. Administration > Entity Manager > fields.
 */
class FieldManager
{
    private bool $isChanged = false;

    // 64 - margin (for attribute name suffixes and prefixes)
    private const MAX_NAME_LENGTH = 50;

    /** @var array<string, array<string, mixed>> */
    private array $defaultParams = [
        FieldType::ENUM => [
            FieldParam::MAX_LENGTH => 100,
        ],
    ];

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private Language $language,
        private Language $baseLanguage,
        private MetadataHelper $metadataHelper,
        private NameUtil $nameUtil
    ) {}

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function read(string $scope, string $name): array
    {
        $fieldDefs = $this->getFieldDefs($scope, $name);

        if ($fieldDefs === null) {
            throw new Error("Can't read field defs $scope.$name.");
        }

        $fieldDefs['label'] = $this->language->translate($name, 'fields', $scope);

        $type = $this->metadata->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('onRead', $type, $scope, $name, $fieldDefs);

        return $fieldDefs;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @return string An actual name.
     * @throws BadRequest
     * @throws Conflict
     * @throws Error
     */
    public function create(string $scope, string $name, array $fieldDefs): string
    {
        if (strlen($name) === 0) {
            throw new BadRequest("Empty field name.");
        }

        if (!$this->isScopeCustom($scope)) {
            $name = $this->nameUtil->addCustomPrefix($name);
        }

        if (!$this->isScopeCustomizable($scope)) {
            throw new Error("Entity type $scope is not customizable.");
        }

        if (strlen(Util::camelCaseToUnderscore($name)) > self::MAX_NAME_LENGTH) {
            throw Error::createWithBody(
                "Field name should not be longer than " . self::MAX_NAME_LENGTH . ".",
                Error\Body::create()
                    ->withMessageTranslation('nameIsTooLong', 'EntityManager')
                    ->encode()
            );
        }

        if ($this->nameUtil->fieldExists($scope, $name)) {
            throw Conflict::createWithBody(
                "Field '$name' already exists in '$scope'.",
                Error\Body::create()
                    ->withMessageTranslation('fieldAlreadyExists', 'FieldManager', [
                        'field' => $name,
                        'entityType' => $scope,
                    ])
                    ->encode()
            );
        }

        if ($this->nameUtil->linkExists($scope, $name)) {
            throw Conflict::createWithBody(
                "Link with name '$name' already exists in '$scope'.",
                Error\Body::create()
                    ->withMessageTranslation('linkWithSameNameAlreadyExists', 'FieldManager', [
                        'field' => $name,
                        'entityType' => $scope,
                    ])
                    ->encode()
            );
        }

        if (
            str_ends_with($name, 'Id') && $this->nameUtil->linkExists($scope, substr($name, 0, -2)) ||
            str_ends_with($name, 'Name') && $this->nameUtil->linkExists($scope, substr($name, 0, -4)) ||
            str_ends_with($name, 'Ids') && $this->nameUtil->linkExists($scope, substr($name, 0, -3)) ||
            str_ends_with($name, 'Names') && $this->nameUtil->linkExists($scope, substr($name, 0, -5)) ||
            str_ends_with($name, 'Type') && $this->nameUtil->linkExists($scope, substr($name, 0, -4))
        ) {
            throw Conflict::createWithBody(
                "namingFieldLinkConflict",
                Error\Body::create()
                    ->withMessageTranslation('namingFieldLinkConflict', 'FieldManager', [
                        'field' => $name,
                    ])
                    ->encode()
            );
        }

        if (
            in_array($name, NameUtil::FIELD_FORBIDDEN_NAME_LIST) ||
            in_array(strtolower($name), NameUtil::FIELD_FORBIDDEN_NAME_LIST)
        ) {
            throw Conflict::createWithBody(
                "Field '$name' is not allowed.",
                Error\Body::create()
                    ->withMessageTranslation('fieldNameIsNotAllowed', 'FieldManager', [
                        'field' => $name,
                    ])
                    ->encode()
            );
        }

        $firstLatter = $name[0];

        if (is_numeric($firstLatter)) {
            throw new Error('Field name should start with a letter.');
        }

        if (preg_match('/[^a-z]/', $firstLatter)) {
            throw new Error("Field name should start with a lower case letter.");
        }

        if (preg_match('/[^a-zA-Z\d]/', $name)) {
            throw new Error("Field name should contain only letters and numbers.");
        }

        $type = $fieldDefs['type'] ?? null;

        if (!$type) {
            throw new BadRequest("No type.");
        }

        foreach (($this->defaultParams[$type] ?? []) as $param => $value) {
            if (!array_key_exists($param, $fieldDefs)) {
                $fieldDefs[$param] = $value;
            }
        }

        $this->update($scope, $name, $fieldDefs, true);

        return $name;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @throws Error
     */
    public function update(string $scope, string $name, array $fieldDefs, bool $isNew = false): void
    {
        $name = trim($name);

        $this->isChanged = false;

        if (!$this->isCore($scope, $name)) {
            $fieldDefs['isCustom'] = true;
        }

        if (!$this->isScopeCustomizable($scope)) {
            throw new Error("Entity type $scope is not customizable.");
        }

        $isCustom = false;

        if (!empty($fieldDefs['isCustom'])) {
            $isCustom = true;
        }

        $isLabelChanged = false;

        if (isset($fieldDefs['label'])) {
            $this->setLabel($scope, $name, $fieldDefs['label'], $isNew, $isCustom);

            $isLabelChanged = true;
        }

        if (isset($fieldDefs['tooltipText'])) {
            $this->setTooltipText($scope, $name, $fieldDefs['tooltipText']);

            $isLabelChanged = true;
        }

        $type = $fieldDefs['type'] ?? $this->metadata->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('beforeSave', $type, $scope, $name, $fieldDefs, ['isNew' => $isNew]);

        if ($this->metadata->get(['fields', $type, 'translatedOptions'])) {
            if (isset($fieldDefs['translatedOptions'])) {
                $translatedOptions = json_decode(Json::encode($fieldDefs['translatedOptions']), true);

                if (isset($translatedOptions['_empty_'])) {
                    $translatedOptions[''] = $translatedOptions['_empty_'];

                    unset($translatedOptions['_empty_']);
                }

                $this->setTranslatedOptions($scope, $name, $translatedOptions, $isNew, $isCustom);

                $isLabelChanged = true;
            }
        }

        if ($isNew) {
            $subFieldsDefs = $this->metadata->get(['fields', $type, 'fields']);

            if ($subFieldsDefs) {
                foreach ($subFieldsDefs as $partField => $partFieldData) {
                    $partLabel = $this->language->get('FieldManager.fieldParts.' . $type . '.' . $partField);

                    if ($partLabel) {
                        if ($this->metadata->get(['fields', $type, 'fields', 'naming']) === 'prefix') {
                            $subFieldName = $partField . ucfirst($name);
                            $subFieldLabel = $partLabel . ' ' . $fieldDefs['label'];
                        } else {
                            $subFieldName = $name . ucfirst($partField);
                            $subFieldLabel = $fieldDefs['label'] . ' ' . $partLabel;
                        }

                        $this->setLabel($scope, $subFieldName, $subFieldLabel, $isNew, $isCustom);

                        $isLabelChanged = true;
                    }
                }
            }
        }

        if ($isLabelChanged) {
            $this->language->save();

            if ($isNew || $isCustom) {
                if ($this->baseLanguage->getLanguage() !== $this->language->getLanguage()) {
                    $this->baseLanguage->save();
                }
            }
        }

        $metadataToBeSaved = false;
        $logicDefsToBeSet = false;

        $logicDefs = [];

        if (array_key_exists('dynamicLogicVisible', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicVisible'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['visible'] = $fieldDefs['dynamicLogicVisible'];

                $logicDefsToBeSet = true;
            } else if ($this->metadata->get(['logicDefs', $scope, 'fields', $name, 'visible'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['visible'] = null;
                $logicDefsToBeSet = true;
            }
        }

        if (array_key_exists('dynamicLogicReadOnly', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicReadOnly'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['readOnly'] = $fieldDefs['dynamicLogicReadOnly'];
                $logicDefsToBeSet = true;
            } else if ($this->metadata->get(['logicDefs', $scope, 'fields', $name, 'readOnly'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['readOnly'] = null;

                $logicDefsToBeSet = true;
            }
        }

        if (array_key_exists('dynamicLogicRequired', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicRequired'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['required'] = $fieldDefs['dynamicLogicRequired'];
                $logicDefsToBeSet = true;
            } else if ($this->metadata->get(['logicDefs', $scope, 'fields', $name, 'required'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['required'] = null;

                $logicDefsToBeSet = true;
            }
        }

        if (array_key_exists('dynamicLogicOptions', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicOptions'])) {
                $this->prepareLogicDefsOptions($logicDefs, $name);

                $logicDefs['options'][$name] = $fieldDefs['dynamicLogicOptions'];

                $logicDefsToBeSet = true;
            } else {
                if ($this->metadata->get(['logicDefs', $scope, 'options', $name])) {
                    $this->prepareLogicDefsOptions($logicDefs, $name);

                    $logicDefs['options'][$name] = null;

                    $logicDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicInvalid', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicInvalid'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['invalid'] = $fieldDefs['dynamicLogicInvalid'];

                $logicDefsToBeSet = true;
            } else {
                if (
                    $this->metadata->get(['logicDefs', $scope, 'fields', $name, 'invalid'])
                ) {
                    $this->prepareLogicDefsFields($logicDefs, $name);

                    $logicDefs['fields'][$name]['invalid'] = null;

                    $logicDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicReadOnlySaved', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicReadOnlySaved'])) {
                $this->prepareLogicDefsFields($logicDefs, $name);

                $logicDefs['fields'][$name]['readOnlySaved'] = $fieldDefs['dynamicLogicReadOnlySaved'];

                $logicDefsToBeSet = true;
            } else {
                if ($this->metadata->get(['logicDefs', $scope, 'fields', $name, 'readOnlySaved'])) {
                    $this->prepareLogicDefsFields($logicDefs, $name);

                    $logicDefs['fields'][$name]['readOnlySaved'] = null;

                    $logicDefsToBeSet = true;
                }
            }
        }

        if ($logicDefsToBeSet) {
            $this->metadata->set('logicDefs', $scope, $logicDefs);

            $metadataToBeSaved = true;
        }

        $entityDefs = $this->normalizeDefs($scope, $name, $fieldDefs);

        if (!empty((array) $entityDefs)) {
            $this->saveCustomEntityDefs($scope, $entityDefs);

            $this->isChanged = true;
        }

        if ($metadataToBeSaved) {
            $this->metadata->save();

            $this->isChanged = true;
        }

        if ($this->isChanged) {
            $this->processHook('afterSave', $type, $scope, $name, $fieldDefs, ['isNew' => $isNew]);
        }
    }

    /**
     * @param array<string, mixed> $logicDefs
     */
    private function prepareLogicDefsFields(array &$logicDefs, string $name): void
    {
        $logicDefs['fields'] ??= [];
        $logicDefs['fields'][$name] ??= [];
    }

    /**
     * @param array<string, mixed> $logicDefs
     */
    private function prepareLogicDefsOptions(array &$logicDefs, string $name): void
    {
        $logicDefs['options'] ??= [];
        $logicDefs['options'][$name] ??= [];
    }

    /**
     * @throws Error
     */
    public function delete(string $scope, string $name): void
    {
        if ($this->isCore($scope, $name)) {
            throw new Error("Cannot delete core field '$name' in '$scope'.");
        }

        if (!$this->isScopeCustomizable($scope)) {
            throw new Error("Entity type $scope is not customizable.");
        }

        $type = $this->metadata->get(['entityDefs', $scope, 'fields', $name, 'type']);

        if (
            in_array($type, [
                FieldType::LINK_MULTIPLE,
                FieldType::LINK,
                FieldType::LINK_ONE,
                FieldType::LINK_PARENT,
            ])
        ) {
            throw new Error("Field type $type cannot be removed.");
        }

        $this->processHook('beforeRemove', $type, $scope, $name);

        $unsets = [
            "fields.$name",
            "links.$name",
        ];

        if ($this->metadata->get("entityDefs.$scope.collection.orderBy") === $name) {
            $unsets[] = "entityDefs.$scope.collection.orderBy";
            $unsets[] = "entityDefs.$scope.collection.order";
        }

        $textFilterFields = $this->metadata->get("entityDefs.$scope.collection.textFilterFields");

        if (is_array($textFilterFields) && in_array($name, $textFilterFields)) {
            $textFilterFields = array_values(array_diff($textFilterFields, [$name]));

            $this->metadata->set('entityDefs', $scope, [
                'collection' => ['textFilterFields' => $textFilterFields]
            ]);
        }

        $this->metadata->delete('entityDefs', $scope, $unsets);

        $this->metadata->delete('logicDefs', $scope, [
            "fields.$name",
            "options.$name",
        ]);

        $this->metadata->save();

        $this->deleteLabel($scope, $name);

        $subFieldsDefs = $this->metadata->get(['fields', $type, 'fields']);

        if ($subFieldsDefs) {
            foreach ($subFieldsDefs as $partField => $partFieldData) {
                if ($this->metadata->get(['fields', $type, 'fields', 'naming']) === 'prefix') {
                    $subFieldName = $partField . ucfirst($name);
                } else {
                    $subFieldName = $name . ucfirst($partField);
                }

                $this->deleteLabel($scope, $subFieldName);
            }
        }

        $this->language->save();

        if ($this->baseLanguage->getLanguage() !== $this->language->getLanguage()) {
            $this->baseLanguage->save();
        }

        $this->processHook('afterRemove', $type, $scope, $name);
    }

    /**
     * @throws Error
     */
    public function resetToDefault(string $scope, string $name): void
    {
        if (!$this->isCore($scope, $name)) {
            throw new Error("Cannot reset to default custom field '$name' in '$scope'.");
        }

        if (!$this->metadata->get(['entityDefs', $scope, 'fields', $name])) {
            throw new Error("Not found field  field '$name' in '$scope'.");
        }

        $this->metadata->delete('entityDefs', $scope, ['fields.' . $name]);

        $this->metadata->delete('logicDefs', $scope, [
            "fields.$name",
            "options.$name",
        ]);

        $this->metadata->save();

        $this->language->delete($scope, 'fields', $name);
        $this->language->delete($scope, 'options', $name);
        $this->language->delete($scope, 'tooltips', $name);

        $this->language->save();
    }

    /**
     * @param array<string, string> $value
     */
    private function setTranslatedOptions(
        string $scope,
        string $name,
        $value,
        bool $isNew,
        bool $isCustom
    ): void {

        if ($isNew || $isCustom) {
            $this->baseLanguage->set($scope, 'options', $name, $value);
        }

        $this->language->set($scope, 'options', $name, $value);
    }

    private function setLabel(
        string $scope,
        string $name,
        string $value,
        bool $isNew,
        bool $isCustom
    ): void {

        if ($isNew || $isCustom) {
            $this->baseLanguage->set($scope, 'fields', $name, $value);
        }

        $this->language->set($scope, 'fields', $name, $value);
    }

    private function setTooltipText(string $scope, string $name, string $value): void
    {
        if ($value !== '' && $value) {
            $this->language->set($scope, 'tooltips', $name, $value);
            $this->baseLanguage->set($scope, 'tooltips', $name, $value);
        } else {
            $this->language->delete($scope, 'tooltips', $name);
            $this->baseLanguage->delete($scope, 'tooltips', $name);
        }
    }

    private function deleteLabel(string $scope, string $name): void
    {
        $this->language->delete($scope, 'fields', $name);
        $this->language->delete($scope, 'tooltips', $name);
        $this->language->delete($scope, 'options', $name);

        $this->baseLanguage->delete($scope, 'fields', $name);
        $this->baseLanguage->delete($scope, 'tooltips', $name);
        $this->baseLanguage->delete($scope, 'options', $name);
    }

    /**
     * @param ?stdClass $default
     * @return ?array<string, mixed>
     */
    private function getFieldDefs(string $scope, string $name, $default = null)
    {
        $defs = $this->metadata->getObjects(['entityDefs', $scope, 'fields', $name], $default);

        if (is_object($defs)) {
            return get_object_vars($defs);
        }

        return $defs;
    }

    /**
     * @param ?array<string, mixed> $default
     * @return ?array<string, mixed>
     */
    private function getCustomFieldDefs(string $scope, string $name, $default = null)
    {
        $customDefs = $this->metadata->getCustom('entityDefs', $scope, (object) []);

        if (isset($customDefs->fields->$name)) {
            return (array) $customDefs->fields->$name;
        }

        return $default;
    }

    /**
     * @param stdClass $newDefs
     */
    private function saveCustomEntityDefs(string $scope, $newDefs): void
    {
        $customDefs = $this->metadata->getCustom('entityDefs', $scope, (object) []);

        if (isset($newDefs->fields)) {
            foreach ($newDefs->fields as $name => $defs) {
                if (!isset($customDefs->fields)) {
                    $customDefs->fields = new stdClass();
                }

                $customDefs->fields->$name = $defs;
            }
        }

        if (isset($newDefs->links)) {
            foreach ($newDefs->links as $name => $defs) {
                if (!isset($customDefs->links)) {
                    $customDefs->links = new stdClass();
                }

                $customDefs->links->$name = $defs;
            }
        }

        $this->metadata->saveCustom('entityDefs', $scope, $customDefs);
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @return array<string, mixed>
     */
    private function prepareFieldDefs(string $scope, string $name, array $fieldDefs): array
    {
        $additionalParamList = [
            'type' => [
                FieldParam::TYPE => FieldType::VARCHAR,
            ],
            'isCustom' => [
                FieldParam::TYPE => FieldType::BOOL,
                FieldParam::DEFAULT => false,
            ],
            'isPersonalData' => [
                FieldParam::TYPE  => FieldType::BOOL,
                FieldParam::DEFAULT => false,
            ],
            'tooltip' => [
                FieldParam::TYPE => FieldType::BOOL,
                FieldParam::DEFAULT => false,
            ],
            'inlineEditDisabled' => [
                FieldParam::TYPE => FieldType::BOOL,
                FieldParam::DEFAULT => false,
            ],
            'defaultAttributes' => [
                FieldParam::TYPE => FieldType::JSON_OBJECT,
            ],
        ];

        $type = $fieldDefs[FieldParam::TYPE] ?? null;

        if (!$type) {
            throw new RuntimeException("No type.");
        }

        foreach (($fieldDefs['fieldManagerAdditionalParamList'] ?? []) as $additionalParam) {
            $additionalParamList[$additionalParam->name] = [
                'type' => $type,
            ];
        }

        $fieldDefsByType = $this->metadataHelper->getFieldDefsByType($fieldDefs);

        $paramDataList = $fieldDefsByType['params'] ?? [];

        $params = [];

        foreach ($paramDataList as $paramData) {
            $params[$paramData['name']] = $paramData;
        }

        foreach ($additionalParamList as $paramName => $paramValue) {
            if (!isset($params[$paramName])) {
                $params[$paramName] = array_merge(['name' => $paramName], $paramValue);
            }
        }

        $actualCustomFieldDefs = $this->getCustomFieldDefs($scope, $name, []);
        $actualFieldDefs = $this->getFieldDefs($scope, $name, (object) []);

        assert($actualFieldDefs !== null);
        assert($actualCustomFieldDefs !== null);

        $permittedParamList = array_unique(array_merge(
            array_keys($params),
            array_keys($this->defaultParams[$type] ?? [])
        ));

        $filteredFieldDefs = !empty($actualCustomFieldDefs) ? $actualCustomFieldDefs : [];

        foreach ($fieldDefs as $paramName => $paramValue) {
            if (!in_array($paramName, $permittedParamList)) {
                continue;
            }

            $defaultParamValue = null;

            $paramType = $params[$paramName][FieldParam::TYPE] ?? null;

            if ($paramType === FieldType::BOOL) {
                $defaultParamValue = false;
            }

            $actualValue = array_key_exists($paramName, $actualFieldDefs) ?
                $actualFieldDefs[$paramName] :
                $defaultParamValue;

            if (
                !array_key_exists($paramName, $actualCustomFieldDefs) &&
                !Util::areValuesEqual($actualValue, $paramValue)
            ) {
                $filteredFieldDefs[$paramName] = $paramValue;

                continue;
            }

            if (array_key_exists($paramName, $actualCustomFieldDefs)) {
                $filteredFieldDefs[$paramName] = $paramValue;
            }
        }

        $metaFieldDefs = $this->metadataHelper->getFieldDefsInFieldMetadata($filteredFieldDefs);

        if (isset($metaFieldDefs)) {
            $filteredFieldDefs = Util::merge($metaFieldDefs, $filteredFieldDefs);
        }

        if ($actualCustomFieldDefs) {
            $actualCustomFieldDefs = array_diff_key($actualCustomFieldDefs, array_flip($permittedParamList));

            foreach ($actualCustomFieldDefs as $paramName => $paramValue) {
                if (!array_key_exists($paramName, $filteredFieldDefs)) {
                    $filteredFieldDefs[$paramName] = $paramValue;
                }
            }
        }

        /** @var array<string, mixed> */
        return $filteredFieldDefs;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     */
    private function normalizeDefs(string $scope, string $fieldName, array $fieldDefs): stdClass
    {
        $defs = new stdClass();

        $normalizedFieldDefs = $this->prepareFieldDefs($scope, $fieldName, $fieldDefs);

        if (!empty($normalizedFieldDefs)) {
            $defs->fields = (object) [
                $fieldName => (object) $normalizedFieldDefs,
            ];
        }

        /** Save links for a field. */
        $linkDefs = $fieldDefs['linkDefs'] ?? null;
        $metaLinkDefs = $this->metadataHelper->getLinkDefsInFieldMeta($scope, $fieldDefs);

        if (isset($linkDefs) || isset($metaLinkDefs)) {
            $metaLinkDefs = $metaLinkDefs ?? [];
            $linkDefs = $linkDefs ?? [];

            $normalizedLinkedDefs = Util::merge($metaLinkDefs, $linkDefs);
            if (!empty($normalizedLinkedDefs)) {
                $defs->links = (object) [
                    $fieldName => (object) $normalizedLinkedDefs,
                ];
            }
        }

        return $defs;
    }

    public function isChanged(): bool
    {
        return $this->isChanged;
    }

    private function isCore(string $scope, string $name): bool
    {
        $existingField = $this->getFieldDefs($scope, $name);

        if (isset($existingField) && (!isset($existingField['isCustom']) || !$existingField['isCustom'])) {
            return true;
        }

        return false;
    }

    private function isScopeCustom(string $scope): bool
    {
        return (bool) $this->metadata->get("scopes.$scope.isCustom");
    }

    /**
     * @todo Add interfaces for hooks.
     *
     * @param string $name
     * @param array<string, mixed> $defs
     * @param array<string, mixed> $options
     */
    private function processHook(
        string $methodName,
        ?string $type,
        string $scope,
        string $name,
        &$defs = [],
        $options = []
    ): void {

        if (!$type) {
            return;
        }

        $hook = $this->getHook($type);

        if (!$hook) {
            return;
        }

        if (!method_exists($hook, $methodName)) {
            return;
        }

        $hook->$methodName($scope, $name, $defs, $options);
    }

    private function getHook(string $type): ?object
    {
        /** @var ?class-string $className */
        $className = $this->metadata->get(['fields', $type, 'hookClassName']);

        if (!$className) {
            return null;
        }

        return $this->injectableFactory->create($className);
    }

    private function isScopeCustomizable(string $scope): bool
    {
        if (!$this->metadata->get("scopes.$scope.customizable")) {
            return false;
        }

        if ($this->metadata->get("scopes.$scope.entityManager.fields") === false) {
            return false;
        }

        return true;
    }
}
