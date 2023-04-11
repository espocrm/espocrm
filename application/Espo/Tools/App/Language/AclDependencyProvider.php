<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\App\Language;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Metadata;

class AclDependencyProvider
{
    private const CACHE_KEY = 'languageAclDependency';

    /** @var ?AclDependencyItem[] */
    private ?array $data = null;
    private bool $useCache;

    public function __construct(
        private DataCache $dataCache,
        private Metadata $metadata,
        Config $config
    ) {
        $this->useCache = $config->get('useCache');
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

        if ($this->useCache) {
            $this->dataCache->store(self::CACHE_KEY, $data);
        }

        return $this->buildFromRaw($data);
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
