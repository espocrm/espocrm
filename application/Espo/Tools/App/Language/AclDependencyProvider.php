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

namespace Espo\Tools\App\Language;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;

class AclDependencyProvider
{
    private const CACHE_KEY = 'languageAclDependency';

    /** @var string[] */
    private array $enumFieldTypeList = [
        FieldType::ENUM,
        FieldType::MULTI_ENUM,
        FieldType::ARRAY,
        FieldType::CHECKLIST,
    ];

    /** @var ?AclDependencyItem[] */
    private ?array $data = null;
    private bool $useCache;

    public function __construct(
        private DataCache $dataCache,
        private Metadata $metadata,
        private Defs $ormDefs,
        SystemConfig $systemConfig,
    ) {
        $this->useCache = $systemConfig->useCache();
    }

    /**
     * @return AclDependencyItem[]
     */
    public function get(): array
    {
        if ($this->data === null) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    /**
     * @return AclDependencyItem[]
     */
    private function loadData(): array
    {
        if ($this->useCache && $this->dataCache->has(self::CACHE_KEY)) {
            /** @var array<string, mixed>[] $raw */
            $raw = $this->dataCache->get(self::CACHE_KEY);

            return $this->buildFromRaw($raw);
        }

        return $this->buildData();
    }

    /**
     * @return AclDependencyItem[]
     */
    private function buildData(): array
    {
        $data = [];

        foreach (($this->metadata->get(['app', 'language', 'aclDependencies']) ?? []) as $target => $item) {
            $anyScopeList = $item['anyScopeList'] ?? null;
            $scope = $item['scope'] ?? null;
            $field = $item['field'] ?? null;

            $data[] = [
                'target' => $target,
                'anyScopeList' => $anyScopeList,
                'scope' => $scope,
                'field' => $field,
            ];
        }

        foreach ($this->ormDefs->getEntityList() as $entityDefs) {
            if (!$this->metadata->get(['scopes', $entityDefs->getName(), 'object'])) {
                continue;
            }

            foreach ($entityDefs->getFieldList() as $fieldDefs) {
                $item = $this->getDataFromField($entityDefs->getName(), $fieldDefs);

                if ($item) {
                    $data[] = $item;
                }
            }
        }

        if ($this->useCache) {
            $this->dataCache->store(self::CACHE_KEY, $data);
        }

        return $this->buildFromRaw($data);
    }

    /**
     * @return ?array<string, mixed>
     */
    private function getDataFromField(string $entityType, Defs\FieldDefs $fieldDefs): ?array
    {
        if ($fieldDefs->getType() === FieldType::FOREIGN) {
            $refEntityType = $fieldDefs->getParam('link') ?
                $this->ormDefs
                    ->getEntity($entityType)
                    ->tryGetRelation($fieldDefs->getParam('link'))
                    ?->tryGetForeignEntityType() :
                null;

            $refField = $fieldDefs->getParam('field');

            if (!$refEntityType || !$refField) {
                return null;
            }

            $foreignFieldType = $this->ormDefs
                ->tryGetEntity($refEntityType)
                ?->tryGetField($refField)
                ?->getType();

            if (
                !in_array($foreignFieldType, [
                    FieldType::ENUM,
                    FieldType::MULTI_ENUM,
                    FieldType::ARRAY,
                    FieldType::CHECKLIST,
                ])
            ) {
                return null;
            }

            return [
                'target' => "$refEntityType.options.$refField",
                'anyScopeList' => null,
                'scope' => $entityType,
                'field' => $fieldDefs->getName(),
            ];
        }

        if (!in_array($fieldDefs->getType(), $this->enumFieldTypeList)) {
            return null;
        }

        $optionsReference = $fieldDefs->getParam('optionsReference');

        if (!$optionsReference || !str_contains($optionsReference, '.')) {
            return null;
        }

        [$refEntityType, $refField] = explode('.', $optionsReference);

        $target = "$refEntityType.options.$refField";

        return [
            'target' => $target,
            'anyScopeList' => null,
            'scope' => $entityType,
            'field' => $fieldDefs->getName(),
        ];
    }

    /**
     * @param array<string, mixed>[] $raw
     * @return AclDependencyItem[]
     */
    private function buildFromRaw(array $raw): array
    {
        $list = [];

        foreach ($raw as $rawItem) {
            $target = $rawItem['target'] ?? null;
            $anyScopeList = $rawItem['anyScopeList'] ?? null;
            $scope = $rawItem['scope'] ?? null;
            $field = $rawItem['field'] ?? null;

            $list[] = new AclDependencyItem($target, $anyScopeList, $scope, $field);
        }

        return $list;
    }
}
