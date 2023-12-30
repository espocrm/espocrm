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

namespace Espo\Core\Utils\Metadata;

use Espo\Core\Utils\DataUtil;
use Espo\Core\Utils\Resource\Reader as ResourceReader;
use Espo\Core\Utils\Resource\Reader\Params as ResourceReaderParams;
use Espo\Core\Utils\Util;
use stdClass;

class Builder
{
    /** @var array<int, string[]> */
    private $forceAppendPathList = [
        ['app', 'rebuild', 'actionClassNameList'],
        ['app', 'fieldProcessing', 'readLoaderClassNameList'],
        ['app', 'fieldProcessing', 'listLoaderClassNameList'],
        ['app', 'fieldProcessing', 'saverClassNameList'],
        ['app', 'hook', 'suppressClassNameList'],
        ['app', 'api', 'globalMiddlewareClassNameList'],
        ['app', 'api', 'routeMiddlewareClassNameListMap', self::ANY_KEY],
        ['app', 'api', 'controllerMiddlewareClassNameListMap', self::ANY_KEY],
        ['app', 'api', 'controllerActionMiddlewareClassNameListMap', self::ANY_KEY],
        ['app', 'entityManager', 'createHookClassNameList'],
        ['app', 'entityManager', 'deleteHookClassNameList'],
        ['app', 'entityManager', 'updateHookClassNameList'],
        ['app', 'linkManager', 'createHookClassNameList'],
        ['app', 'linkManager', 'deleteHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'readLoaderClassNameList'],
        ['recordDefs', self::ANY_KEY, 'listLoaderClassNameList'],
        ['recordDefs', self::ANY_KEY, 'saverClassNameList'],
        ['recordDefs', self::ANY_KEY, 'selectApplierClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeReadHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeCreateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeUpdateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeDeleteHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeLinkHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeUnlinkHookClassNameList'],
    ];

    private const ANY_KEY = '__ANY__';

    public function __construct(
        private ResourceReader $resourceReader,
        private BuilderHelper $builderHelper
    ) {}

    public function build(): stdClass
    {
        $readerParams = ResourceReaderParams::create()
            ->withForceAppendPathList($this->forceAppendPathList);

        $data = $this->resourceReader->read('metadata', $readerParams);

        $this->addAdditionalField($data);

        return $data;
    }

    private function addAdditionalField(stdClass $data): void
    {
        if (!isset($data->entityDefs)) {
            return;
        }

        $fieldDefinitionList = Util::objectToArray($data->fields);

        foreach (get_object_vars($data->entityDefs) as $entityType => $entityDefsItem) {
            if (isset($data->entityDefs->$entityType->collection)) {
                /** @var stdClass $collectionItem */
                $collectionItem = $data->entityDefs->$entityType->collection;

                if (isset($collectionItem->orderBy)) {
                    $collectionItem->sortBy = $collectionItem->orderBy;
                }
                else if (isset($collectionItem->sortBy)) {
                    $collectionItem->orderBy = $collectionItem->sortBy;
                }

                if (isset($collectionItem->order)) {
                    $collectionItem->asc = $collectionItem->order === 'asc';
                }
                else if (isset($collectionItem->asc)) {
                    $collectionItem->order = $collectionItem->asc === true ? 'asc' : 'desc';
                }
            }

            if (!isset($entityDefsItem->fields)) {
                continue;
            }

            foreach (get_object_vars($entityDefsItem->fields) as $field => $fieldDefsItem) {
                $additionalFields = $this->builderHelper->getAdditionalFieldList(
                    $field,
                    Util::objectToArray($fieldDefsItem),
                    $fieldDefinitionList
                );

                if (!$additionalFields) {
                    continue;
                }

                foreach ($additionalFields as $subFieldName => $subFieldParams) {
                    $item = Util::arrayToObject($subFieldParams);

                    if (isset($entityDefsItem->fields->$subFieldName)) {
                        $data->entityDefs->$entityType->fields->$subFieldName =
                            DataUtil::merge(
                                $item,
                                $entityDefsItem->fields->$subFieldName
                            );

                        continue;
                    }

                    $data->entityDefs->$entityType->fields->$subFieldName = $item;
                }
            }
        }
    }

    /*private function setMissingFieldDefaults(stdClass $data): void
    {
        if (!isset($data->entityDefs) || !isset($data->fields)) {
            return;
        }

        foreach (get_object_vars($data->entityDefs) as $entityDefsItem) {
            if (!isset($entityDefsItem->fields)) {
                continue;
            }

            foreach (get_object_vars($entityDefsItem->fields) as $field => $fieldDefs) {
                $oFieldDefs = FieldDefs::fromRaw(Util::objectToArray($fieldDefs), $field);

                $type = $oFieldDefs->getType();

                $typeDefs = $data->fields->$type ?? null;

                if (!$typeDefs) {
                    continue;
                }

                if (!property_exists($typeDefs, 'default')) {
                    continue;
                }

                if (
                    $oFieldDefs->getParam('utility') ||
                    $oFieldDefs->getParam('disabled') ||
                    $oFieldDefs->hasParam('default')
                ) {
                    continue;
                }

                $fieldDefs->default = $typeDefs->default;
            }
        }
    }*/
}
