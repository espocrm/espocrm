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

namespace Espo\Tools\Kanban;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Metadata;
use Espo\Tools\Pipeline\Data\PipelineData;
use Espo\Tools\Pipeline\MetadataProvider as PipelineMetadataProvider;
use Espo\Tools\Pipeline\PipelineDataProvider;

class MetadataProvider
{
    public function __construct(
        private Metadata $metadata,
        private PipelineMetadataProvider $pipelineMetadata,
        private PipelineDataProvider $pipelineDataProvider,
    ) {}

    /**
     * @return string[]
     * @throws Error
     * @throws BadRequest
     */
    public function getStatusList(string $entityType, ?string $pipelineId = null): array
    {
        if ($this->pipelineMetadata->isEnabled($entityType)) {
            if (!$pipelineId) {
                throw BadRequest::createWithBody(
                    'noPipeline',
                    Error\Body::create()->withMessageTranslation('noPipeline')
                );
            }

            return $this->getPipelineStatusList($entityType, $pipelineId);
        }

        $field = $this->getStatusField($entityType);

        $statusList = $this->metadata->get("entityDefs.$entityType.fields.$field.options");
        $optionsReference = $this->metadata->get("entityDefs.$entityType.fields.$field.optionsReference");

        if (is_string($optionsReference) && str_contains($optionsReference, '.')) {
            [$refEntityType, $refField] = explode('.', $optionsReference);

            $statusList = $this->metadata->get("entityDefs.$refEntityType.fields.$refField.options");
        }

        if (!$statusList) {
            throw new Error("No options for status field for entity type '$entityType'.");
        }

        $statusList = array_diff($statusList, $this->getStatusIgnoreList($entityType));
        $statusList = array_filter($statusList, fn ($it) => $it !== '');

        return array_values($statusList);
    }

    /**
     * @throws Error
     */
    public function getStatusField(string $entityType): string
    {
        if ($this->pipelineMetadata->isEnabled($entityType)) {
            return Field::PIPELINE_STAGE . 'Id';
        }

        $statusField = $this->metadata->get("scopes.$entityType.statusField");

        if (!$statusField) {
            throw new Error("No status field for entity type '$entityType'.");
        }

        return $statusField;
    }

    /**
     * @return string[]
     */
    public function getStatusIgnoreList(string $entityType): array
    {
        return $this->metadata->get("scopes.$entityType.kanbanStatusIgnoreList") ?? [];
    }

    private function getPipelineData(string $entityType, string $pipelineId): ?PipelineData
    {
        $pipeline = null;

        $pipelines = $this->pipelineDataProvider->get()[$entityType] ?? [];

        foreach ($pipelines as $it) {
            if ($it->id === $pipelineId) {
                $pipeline = $it;

                break;
            }
        }

        return $pipeline;
    }

    /**
     * @return string[]
     */
    private function getPipelineStatusList(string $entityType, string $pipelineId): array
    {
        $pipeline = $this->getPipelineData($entityType, $pipelineId);

        if (!$pipeline) {
            return [];
        }

        $ignoreStatusList = $this->getStatusIgnoreList($entityType);
        $output = [];

        foreach ($pipeline->stages as $stage) {
            if (in_array($stage->mappedStatus, $ignoreStatusList)) {
                continue;
            }

            $output[] = $stage->id;
        }

        return $output;
    }
}
