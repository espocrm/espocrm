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

namespace Espo\Core\Utils\Metadata;

use Espo\Core\Utils\Resource\Reader as ResourceReader;
use Espo\Core\Utils\Resource\Reader\Params as ResourceReaderParams;
use Espo\Core\Utils\Util;
use stdClass;

class Builder
{
    /** @var array<int, string[]> */
    private $forceAppendPathList = [
        ['app', 'metadata', 'additionalBuilderClassNameList'],
        ['app', 'rebuild', 'actionClassNameList'],
        ['app', 'formula', 'functionList'],
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
        ['recordDefs', self::ANY_KEY, 'createInputFilterClassNameList'],
        ['recordDefs', self::ANY_KEY, 'updateInputFilterClassNameList'],
        ['recordDefs', self::ANY_KEY, 'outputFilterClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeReadHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'earlyBeforeCreateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeCreateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'earlyBeforeUpdateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeUpdateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeDeleteHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'afterCreateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'afterUpdateHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'afterDeleteHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeLinkHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'beforeUnlinkHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'afterLinkHookClassNameList'],
        ['recordDefs', self::ANY_KEY, 'afterUnlinkHookClassNameList'],
    ];

    private const ANY_KEY = '__ANY__';

    public function __construct(private ResourceReader $resourceReader) {}

    public function build(): stdClass
    {
        $readerParams = ResourceReaderParams::create()
            ->withForceAppendPathList($this->forceAppendPathList);

        $data = $this->resourceReader->read('metadata', $readerParams);

        $this->applyAdditional($data);

        return $data;
    }

    private function applyAdditional(stdClass $data): void
    {
        /** @var class-string<AdditionalBuilder>[] $builderClassNameList */
        $builderClassNameList = Util::getValueByKey($data, 'app.metadata.additionalBuilderClassNameList') ?? [];

        /** @var AdditionalBuilder[] $builderList */
        $builderList = array_map(
            fn ($className) => new $className(),
            $builderClassNameList
        );

        foreach ($builderList as $builder) {
            $builder->build($data);
        }
    }
}
