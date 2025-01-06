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

namespace Espo\Core\FieldSanitize;

use Espo\Core\FieldSanitize\Sanitizer\Data;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use stdClass;

class SanitizeManager
{
    public function __construct(
        private Metadata $metadata,
        private FieldUtil $fieldUtil,
        private InjectableFactory $injectableFactory
    ) {}

    public function process(string $entityType, stdClass $rawData): void
    {
        $data = new Data($rawData);

        foreach ($this->fieldUtil->getEntityTypeFieldList($entityType) as $field) {
            if (!$this->isFieldSetInData($entityType, $field, $rawData)) {
                continue;
            }

            $this->processField($entityType, $field, $data);
        }
    }

    private function processField(string $entityType, string $field, Data $data): void
    {
        foreach ($this->getSanitizerList($entityType, $field) as $sanitizer) {
            $sanitizer->sanitize($data, $field);
        }
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

    /**
     * @return Sanitizer[]
     */
    private function getSanitizerList(string $entityType, string $field): array
    {
        $classNameList = $this->getClassNameList($entityType, $field);

        return array_map(
            fn ($className) => $this->injectableFactory->createWith($className, ['entityType' => $entityType]),
            $classNameList
        );
    }

    /**
     * @return class-string<Sanitizer>[]
     */
    private function getClassNameList(string $entityType, string $field): array
    {
        $fieldType = $this->fieldUtil->getFieldType($entityType, $field);

        if (!$fieldType) {
            return [];
        }

        $classNameList = [];

        /** @var ?class-string<Sanitizer> $className */
        $className = $this->metadata->get("fields.$fieldType.sanitizerClassName");

        if ($className) {
            $classNameList[] = $className;
        }

        /** @var class-string<Sanitizer>[] $typeClassNameList */
        $typeClassNameList = $this->metadata->get("fields.$fieldType.sanitizerClassNameList") ?? [];

        $classNameList = array_merge($classNameList, $typeClassNameList);

        /** @var class-string<Sanitizer>[] $fieldClassNameList */
        $fieldClassNameList = $this->metadata->get("entityDefs.$entityType.fields.$field.sanitizerClassNameList") ?? [];
        $ignoreList = $this->metadata->get("entityDefs.$entityType.fields.$field.sanitizerSuppressClassNameList") ?? [];

        $list = array_merge($classNameList, $fieldClassNameList);

        if ($ignoreList === []) {
            return $list;
        }

        $list = array_diff($list, $ignoreList);

        return array_values($list);
    }
}
