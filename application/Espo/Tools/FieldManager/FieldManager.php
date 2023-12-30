<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Language;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\Core\Utils\Util;

use stdClass;

/**
 * Field Manager tool. Administration > Entity Manager > fields.
 */
class FieldManager
{
    private bool $isChanged = false;

    /** @var string[] */
    private $forbiddenFieldNameList = [
        'id',
        'deleted',
        'skipDuplicateCheck',
        'isFollowed',
        'versionNumber',
        'null',
        'false',
        'true',
    ];

    /** @var string[] */
    private $forbiddenAnyCaseFieldNameList = [
        'id',
        'deleted',
        'null',
        'false',
        'true',
        'system',
    ];

    // 64 - margin (for attribute name suffixes and prefixes)
    private const MAX_NAME_LENGTH = 50;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private Language $language,
        private Language $baseLanguage,
        private MetadataHelper $metadataHelper
    ) {}

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function read(string $scope, string $name): array
    {
        $fieldDefs = $this->getFieldDefs($scope, $name);

        if ($fieldDefs === null) {
            throw new Error("Can't read field defs {$scope}.{$name}.");
        }

        $fieldDefs['label'] = $this->language->translate($name, 'fields', $scope);

        $type = $this->metadata->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('onRead', $type, $scope, $name, $fieldDefs);

        return $fieldDefs;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @return bool
     * @throws BadRequest
     * @throws Conflict
     * @throws Error
     */
    public function create(string $scope, string $name, array $fieldDefs)
    {
        if (strlen($name) === 0) {
            throw new BadRequest("Empty field name.");
        }

        if (strlen(Util::camelCaseToUnderscore($name)) > self::MAX_NAME_LENGTH) {
            throw Error::createWithBody(
                "Field name should not be longer than " . self::MAX_NAME_LENGTH . ".",
                Error\Body::create()
                    ->withMessageTranslation('nameIsTooLong', 'EntityManager')
                    ->encode()
            );
        };

        $existingField = $this->getFieldDefs($scope, $name);

        if (isset($existingField)) {
            throw Conflict::createWithBody(
                "Field '{$name}' already exists in '{$scope}'.",
                Error\Body::create()
                    ->withMessageTranslation('fieldAlreadyExists', 'FieldManager', [
                        'field' => $name,
                        'entityType' => $scope,
                    ])
                    ->encode()
            );
        }

        if ($this->metadata->get(['entityDefs', $scope, 'links', $name])) {
            throw Conflict::createWithBody(
                "Link with name '{$name}' already exists in '{$scope}'.",
                Error\Body::create()
                    ->withMessageTranslation('linkWithSameNameAlreadyExists', 'FieldManager', [
                        'field' => $name,
                        'entityType' => $scope,
                    ])
                    ->encode()
            );
        }

        if (
            in_array($name, $this->forbiddenFieldNameList) ||
            in_array(strtolower($name), $this->forbiddenAnyCaseFieldNameList)
        ) {
            throw Conflict::createWithBody(
                "Field '{$name}' is not allowed.",
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

        return $this->update($scope, $name, $fieldDefs, true);
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @return bool
     */
    public function update(string $scope, string $name, array $fieldDefs, bool $isNew = false)
    {
        $name = trim($name);

        $this->isChanged = false;

        if (!$this->isCore($scope, $name)) {
            $fieldDefs['isCustom'] = true;
        }

        $isCustom = false;

        if (!empty($fieldDefs['isCustom'])) {
            $isCustom = true;
        }

        $result = true;

        $isLabelChanged = false;

        if (isset($fieldDefs['label'])) {
            $this->setLabel($scope, $name, $fieldDefs['label'], $isNew, $isCustom);

            $isLabelChanged = true;
        }

        if (isset($fieldDefs['tooltipText'])) {
            $this->setTooltipText($scope, $name, $fieldDefs['tooltipText'], $isNew, $isCustom);

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
                        }
                        else {
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
        $clientDefsToBeSet = false;

        $clientDefs = [];

        if (array_key_exists('dynamicLogicVisible', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicVisible'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                $clientDefs['dynamicLogic']['fields'][$name]['visible'] = $fieldDefs['dynamicLogicVisible'];

                $clientDefsToBeSet = true;
            }
            else {
                if (
                    $this->metadata->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'visible'])
                ) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                    $clientDefs['dynamicLogic']['fields'][$name]['visible'] = null;
                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicReadOnly', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicReadOnly'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = $fieldDefs['dynamicLogicReadOnly'];
                $clientDefsToBeSet = true;
            }
            else {
                if (
                    $this->metadata->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'readOnly'])
                ) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                    $clientDefs['dynamicLogic']['fields'][$name]['readOnly'] = null;

                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicRequired', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicRequired'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                $clientDefs['dynamicLogic']['fields'][$name]['required'] = $fieldDefs['dynamicLogicRequired'];
                $clientDefsToBeSet = true;
            } else {
                if (
                    $this->metadata->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'required'])
                ) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                    $clientDefs['dynamicLogic']['fields'][$name]['required'] = null;

                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicOptions', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicOptions'])) {
                $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);

                $clientDefs['dynamicLogic']['options'][$name] = $fieldDefs['dynamicLogicOptions'];

                $clientDefsToBeSet = true;
            }
            else {
                if ($this->metadata->get(['clientDefs', $scope, 'dynamicLogic', 'options', $name])) {
                    $this->prepareClientDefsOptionsDynamicLogic($clientDefs, $name);

                    $clientDefs['dynamicLogic']['options'][$name] = null;

                    $clientDefsToBeSet = true;
                }
            }
        }

        if (array_key_exists('dynamicLogicInvalid', $fieldDefs)) {
            if (!is_null($fieldDefs['dynamicLogicInvalid'])) {
                $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                $clientDefs['dynamicLogic']['fields'][$name]['invalid'] = $fieldDefs['dynamicLogicInvalid'];
                $clientDefsToBeSet = true;
            }
            else {
                if (
                    $this->metadata->get(['clientDefs', $scope, 'dynamicLogic', 'fields', $name, 'invalid'])
                ) {
                    $this->prepareClientDefsFieldsDynamicLogic($clientDefs, $name);

                    $clientDefs['dynamicLogic']['fields'][$name]['invalid'] = null;

                    $clientDefsToBeSet = true;
                }
            }
        }

        if ($clientDefsToBeSet) {
            $this->metadata->set('clientDefs', $scope, $clientDefs);

            $metadataToBeSaved = true;
        }

        $entityDefs = $this->normalizeDefs($scope, $name, $fieldDefs);

        if (!empty((array) $entityDefs)) {
            $result &= $this->saveCustomEntityDefs($scope, $entityDefs);

            $this->isChanged = true;
        }

        if ($metadataToBeSaved) {
            $result &= $this->metadata->save();

            $this->isChanged = true;
        }

        if ($this->isChanged) {
            $this->processHook('afterSave', $type, $scope, $name, $fieldDefs, ['isNew' => $isNew]);
        }

        return (bool) $result;
    }

    /**
     * @param array<string, mixed> $clientDefs
     * @param string $name
     */
    protected function prepareClientDefsFieldsDynamicLogic(&$clientDefs, $name): void
    {
        if (!array_key_exists('dynamicLogic', $clientDefs)) {
            $clientDefs['dynamicLogic'] = [];
        }

        if (!array_key_exists('fields', $clientDefs['dynamicLogic'])) {
            $clientDefs['dynamicLogic']['fields'] = [];
        }

        if (!array_key_exists($name, $clientDefs['dynamicLogic']['fields'])) {
            $clientDefs['dynamicLogic']['fields'][$name] = [];
        }
    }

    /**
     * @param array<string, mixed> $clientDefs
     * @param string $name
     */
    protected function prepareClientDefsOptionsDynamicLogic(&$clientDefs, $name): void
    {
        if (!array_key_exists('dynamicLogic', $clientDefs)) {
            $clientDefs['dynamicLogic'] = array();
        }

        if (!array_key_exists('options', $clientDefs['dynamicLogic'])) {
            $clientDefs['dynamicLogic']['options'] = array();
        }

        if (!array_key_exists($name, $clientDefs['dynamicLogic']['options'])) {
            $clientDefs['dynamicLogic']['options'][$name] = array();
        }
    }

    /**
     * @return bool
     * @throws Error
     */
    public function delete(string $scope, string $name)
    {
        if ($this->isCore($scope, $name)) {
            throw new Error("Cannot delete core field '{$name}' in '{$scope}'.");
        }

        $type = $this->metadata->get(['entityDefs', $scope, 'fields', $name, 'type']);

        $this->processHook('beforeRemove', $type, $scope, $name);

        $unsets = [
            'fields.'.$name,
            'links.'.$name,
        ];

        $this->metadata->delete('entityDefs', $scope, $unsets);

        $this->metadata->delete('clientDefs', $scope, [
            'dynamicLogic.fields.' . $name,
            'dynamicLogic.options.' . $name,
        ]);

        $res = $this->metadata->save();

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

        return (bool) $res;
    }

    /**
     * @throws Error
     */
    public function resetToDefault(string $scope, string $name): void
    {
        if (!$this->isCore($scope, $name)) {
            throw new Error("Cannot reset to default custom field '{$name}' in '{$scope}'.");
        }

        if (!$this->metadata->get(['entityDefs', $scope, 'fields', $name])) {
            throw new Error("Not found field  field '{$name}' in '{$scope}'.");
        }

        $this->metadata->delete('entityDefs', $scope, ['fields.' . $name]);

        $this->metadata->delete('clientDefs', $scope, [
            'dynamicLogic.fields.' . $name,
            'dynamicLogic.options.' . $name,
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
    protected function setTranslatedOptions(
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

    protected function setLabel(
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

    protected function setTooltipText(
        string $scope,
        string $name,
        string $value,
        bool $isNew,
        bool $isCustom
    ): void {

        if ($value && $value !== '') {
            $this->language->set($scope, 'tooltips', $name, $value);
            $this->baseLanguage->set($scope, 'tooltips', $name, $value);
        }
        else {
            $this->language->delete($scope, 'tooltips', $name);
            $this->baseLanguage->delete($scope, 'tooltips', $name);
        }
    }

    protected function deleteLabel(string $scope, string $name): void
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
    protected function getFieldDefs(string $scope, string $name, $default = null)
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
    protected function getCustomFieldDefs(string $scope, string $name, $default = null)
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
    protected function saveCustomEntityDefs(string $scope, $newDefs): bool
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

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getLinkDefs(string $scope, string $name)
    {
        return $this->metadata->get('entityDefs' . '.' . $scope . '.links.' . $name);
    }

    /**
     * @param array<string, mixed> $fieldDefs
     * @return array<string, mixed>
     */
    protected function prepareFieldDefs(string $scope, string $name, $fieldDefs)
    {
        $additionalParamList = [
            'type' => [
                'type' => 'varchar',
            ],
            'isCustom' => [
                'type' => 'bool',
                'default' => false,
            ],
            'isPersonalData' => [
                'type' => 'bool',
                'default' => false,
            ],
            'tooltip' => [
                'type' => 'bool',
                'default' => false,
            ],
            'inlineEditDisabled' => [
                'type' => 'bool',
                'default' => false,
            ],
            'defaultAttributes' => [
                'type' => 'jsonObject',
            ],
        ];

        if (isset($fieldDefs['fieldManagerAdditionalParamList'])) {
            foreach ($fieldDefs['fieldManagerAdditionalParamList'] as $additionalParam) {
                $additionalParamList[$additionalParam->name] = [
                    'type' => $fieldDefs['type']
                ];
            }
        }

        $fieldDefsByType = $this->metadataHelper->getFieldDefsByType($fieldDefs);

        if (!isset($fieldDefsByType['params'])) {
            return $fieldDefs;
        }

        $params = [];

        foreach ($fieldDefsByType['params'] as $paramData) {
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

        $permittedParamList = array_keys($params);

        $filteredFieldDefs = !empty($actualCustomFieldDefs) ? $actualCustomFieldDefs : [];

        foreach ($fieldDefs as $paramName => $paramValue) {
            if (in_array($paramName, $permittedParamList)) {

                $defaultParamValue = null;

                switch ($params[$paramName]['type']) {
                    case 'bool':
                        $defaultParamValue = false;
                        break;
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
    protected function normalizeDefs(string $scope, string $fieldName, array $fieldDefs): stdClass
    {
        $defs = new stdClass();

        $normalizedFieldDefs = $this->prepareFieldDefs($scope, $fieldName, $fieldDefs);

        if (!empty($normalizedFieldDefs)) {
            $defs->fields = (object) [
                $fieldName => (object) $normalizedFieldDefs,
            ];
        }

        /** Save links for a field. */
        $linkDefs = isset($fieldDefs['linkDefs']) ? $fieldDefs['linkDefs'] : null;
        $metaLinkDefs = $this->metadataHelper->getLinkDefsInFieldMeta($scope, $fieldDefs);

        if (isset($linkDefs) || isset($metaLinkDefs)) {
            $metaLinkDefs = isset($metaLinkDefs) ? $metaLinkDefs : array();
            $linkDefs = isset($linkDefs) ? $linkDefs : array();

            $normalizedLinkedDefs = Util::merge($metaLinkDefs, $linkDefs);
            if (!empty($normalizedLinkedDefs)) {
                $defs->links = (object) array(
                    $fieldName => (object) $normalizedLinkedDefs,
                );
            }
        }

        return $defs;
    }

    public function isChanged(): bool
    {
        return $this->isChanged;
    }

    protected function isCore(string $scope, string $name): bool
    {
        $existingField = $this->getFieldDefs($scope, $name);

        if (isset($existingField) && (!isset($existingField['isCustom']) || !$existingField['isCustom'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $defs
     * @param array<string, mixed> $options
     */
    protected function processHook(
        string $methodName,
        ?string $type,
        string $scope,
        string $name,
        &$defs = null,
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

    protected function getHook(string $type): ?object
    {
        /** @var ?class-string $className */
        $className = $this->metadata->get(['fields', $type, 'hookClassName']);

        if (!$className) {
            return null;
        }

        return $this->injectableFactory->create($className);
    }
}
