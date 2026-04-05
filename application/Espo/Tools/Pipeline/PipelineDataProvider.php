<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\Pipeline;

use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\ORM\EntityManager;
use Espo\Tools\Pipeline\Data\PipelineData;
use Espo\Tools\Pipeline\Data\StageData;
use stdClass;
use Throwable;

class PipelineDataProvider
{
    private const int LIMIT = 100;
    private const string CACHE_KEY = 'pipelines';

    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private SystemConfig $systemConfig,
        private DataCache $dataCache,
        private Log $log,
    ) {}

    /**
     * @return array<string, PipelineData[]>
     */
    public function get(): array
    {
        $data = null;
        $store = false;

        if ($this->systemConfig->useCache()) {
            $data = $this->getFromCache();

            if (!$data) {
                $store = true;
            }
        }

        $data ??= $this->build();

        if ($store) {
            $this->storeCache($data);
        }

        return $data;
    }

    /**
     * @return ?array<string, PipelineData[]>
     */
    private function getFromCache(): ?array
    {
        if (!$this->dataCache->has(self::CACHE_KEY)) {
            return null;
        }

        try {
            return $this->tryGetFromCache();
        } catch (Throwable $e) {
            $this->log->warning("Pipeline cache read error.", [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * @return array<string, PipelineData[]>
     */
    private function build(): array
    {
        $output = [];

        foreach ($this->getEntityTypeList() as $entityType) {
            $output[$entityType] = $this->buildForEntityType($entityType);
        }

        return $output;
    }

    /**
     * @return string[]
     */
    private function getEntityTypeList(): array
    {
        $entityTypeList = [];

        /** @var array<string, array<string, mixed>> $scopes */
        $scopes = $this->metadata->get('scopes') ?? [];

        foreach ($scopes as $scope => $defs) {
            if (
                !($defs['entity'] ?? false) ||
                !($defs['pipelines'] ?? false) ||
                ($defs['disabled'] ?? false)
            ) {
                continue;
            }

            $entityTypeList[] = $scope;
        }

        return $entityTypeList;
    }

    /**
     * @return PipelineData[]
     */
    private function buildForEntityType(string $entityType): array
    {
        $pipelines = $this->entityManager
            ->getRDBRepositoryByClass(Pipeline::class)
            ->where([
                Pipeline::FIELD_STATUS => Pipeline::STATUS_ACTIVE,
                Pipeline::FIELD_ENTITY_TYPE=> $entityType,
            ])
            ->order(Pipeline::FIELD_ORDER)
            ->limit(0, self::LIMIT)
            ->find();

        $output = [];

        foreach ($pipelines as $pipeline) {
            $output[] = new PipelineData(
                id: $pipeline->getId(),
                name: $pipeline->getName(),
                entityType: $entityType,
                stages: $this->getStages($pipeline),
                isAvailableForAll: $pipeline->isAvailableForAll(),
                teamIds: $pipeline->getTeams()->getIdList(),
                color: $pipeline->getColor(),
            );
        }

        return $output;
    }

    /**
     * @return StageData[]
     */
    private function getStages(Pipeline $pipeline): array
    {
        $stages = $this->entityManager
            ->getRDBRepositoryByClass(PipelineStage::class)
            ->where([
                PipelineStage::ATTR_PIPELINE_ID => $pipeline->getId(),
            ])
            ->order(PipelineStage::FIELD_ORDER)
            ->limit(0, self::LIMIT)
            ->find();

        $output = [];

        foreach ($stages as $stage) {
            $output[] = new StageData(
                id: $stage->getId(),
                name: $stage->getName(),
                mappedStatus: $stage->getMappedStatus(),
                style: $this->getStageStyle($pipeline, $stage),
            );
        }

        return $output;
    }

    /**
     * @param array<string, PipelineData[]> $data
     */
    private function storeCache(array $data): void
    {
        $output = (object) [];

        foreach ($data as $entityType => $pipelines) {
            $subOutput = [];

            foreach ($pipelines as $pipeline) {
                $subOutput[] = (object) [
                    'id' => $pipeline->id,
                    'name' => $pipeline->name,
                    'entityType' => $entityType,
                    'stages' => $this->serializeStages($pipeline->stages),
                    'isAvailableForAll' => $pipeline->isAvailableForAll,
                    'teamIds' => $pipeline->teamIds,
                    'color' => $pipeline->color,
                ];
            }

            $output->$entityType = $subOutput;
        }

        $this->dataCache->store(self::CACHE_KEY, $output);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function unserializePipelineData(array $data): PipelineData
    {
        $data['stages'] = $this->unserializeStages($data['stages'] ?? []);

        return new PipelineData(...$data);
    }

    /**
     * @param array<int, mixed> $stages
     * @return StageData[]
     */
    private function unserializeStages(array $stages): array
    {
        $output = [];

        foreach ($stages as $item) {
            if (!$item instanceof stdClass) {
                continue;
            }

            $output[] = new StageData(...get_object_vars($item));
        }

        return $output;
    }

    /**
     * @param StageData[] $stages
     * @return stdClass[]
     */
    private function serializeStages(array $stages): array
    {
        $output = [];

        foreach ($stages as $stage) {
            $output[] = (object) [
                'id' => $stage->id,
                'name' => $stage->name,
                'mappedStatus' => $stage->mappedStatus,
                'style' => $stage->style,
            ];
        }

        return $output;
    }

    /**
     * @return ?array<string, PipelineData[]>
     */
    private function tryGetFromCache(): ?array
    {
        $data = $this->dataCache->get(self::CACHE_KEY);

        if (!$data instanceof stdClass) {
            $this->log->warning("Bad pipeline cache.");

            return null;
        }

        $output = [];

        foreach (get_object_vars($data) as $entityType => $list) {
            if (!is_array($list)) {
                $this->log->warning("Bad pipeline cache, non-array.");

                return null;
            }

            $subOutput = [];

            foreach ($list as $item) {
                if (!$item instanceof stdClass) {
                    $this->log->warning("Bad pipeline item cache.");

                    return null;
                }

                $subOutput[] = $this->unserializePipelineData(get_object_vars($item));
            }

            $output[$entityType] = $subOutput;
        }

        return $output;
    }

    private function getStageStyle(Pipeline $pipeline, PipelineStage $stage): ?string
    {
        $entityType = $pipeline->getTargetEntityType();
        $field = $pipeline->getTargetField();
        $status = $stage->getMappedStatus();

        return $this->metadata->get("entityDefs.$entityType.fields.$field.style.$status");
    }
}
