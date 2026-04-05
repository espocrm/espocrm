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

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\InjectableFactory;
use Espo\Core\Name\Field;
use Espo\Core\Record\EntityProvider;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\Entities\User;
use Espo\Tools\Pipeline\MetadataProvider as PipelineMetadataProvider;

class KanbanService
{
    public function __construct(
        private User $user,
        private AclManager $aclManager,
        private InjectableFactory $injectableFactory,
        private Config $config,
        private Metadata $metadata,
        private Orderer $orderer,
        private EntityProvider $entityProvider,
        private PipelineMetadataProvider $pipelineMetadata,
    ) {}

    /**
     * @throws Error
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function getData(string $entityType, SearchParams $searchParams): Result
    {
        $this->processAccessCheck($entityType);

        $disableCount = $this->metadata
            ->get(['entityDefs', $entityType, 'collection', 'countDisabled']) ?? false;

        $orderDisabled = $this->metadata
            ->get(['scopes', $entityType, 'kanbanOrderDisabled']) ?? false;

        $maxOrderNumber = $this->config->get('kanbanMaxOrderNumber');

        $pipeline = $this->getPipeline($entityType, $searchParams);

        return $this->createKanban()
            ->setEntityType($entityType)
            ->setSearchParams($searchParams)
            ->setPipelineId($pipeline?->getId() ?? null)
            ->setCountDisabled($disableCount)
            ->setOrderDisabled($orderDisabled)
            ->setUserId($this->user->getId())
            ->setMaxOrderNumber($maxOrderNumber)
            ->getResult();
    }

    /**
     * @param string[] $ids
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function order(string $entityType, string $group, array $ids): void
    {
        $this->processAccessCheck($entityType);

        if ($this->user->isPortal()) {
            throw new ForbiddenSilent("Kanban order is not allowed for portal users.");
        }

        $isPipeline = $this->pipelineMetadata->isEnabled($entityType);


        if ($isPipeline) {
            $this->entityProvider->getByClass(PipelineStage::class, $group);
        }

        $maxOrderNumber = $this->config->get('kanbanMaxOrderNumber');

        $this->orderer
            ->setEntityType($entityType)
            ->setGroup($group)
            ->setIsPipeline($isPipeline)
            ->setUserId($this->user->getId())
            ->setMaxNumber($maxOrderNumber)
            ->order($ids);
    }

    private function createKanban(): Kanban
    {
        return $this->injectableFactory->create(Kanban::class);
    }

    /**
     * @throws ForbiddenSilent
     */
    private function processAccessCheck(string $entityType): void
    {
        if (!$this->metadata->get(['scopes', $entityType, 'object'])) {
            throw new ForbiddenSilent("Non-object entities are not supported.");
        }

        if ($this->metadata->get(['recordDefs', $entityType, 'kanbanDisabled'])) {
            throw new ForbiddenSilent("Kanban is disabled for '$entityType'.");
        }

        if (!$this->aclManager->check($this->user, $entityType, Table::ACTION_READ)) {
            throw new ForbiddenSilent();
        }
    }


    private function getPipelineId(SearchParams $searchParams): ?string
    {
        $pipelineId = null;

        if ($searchParams->getWhere()?->getType() === Type::AND) {
            foreach ($searchParams->getWhere()->getItemList() as $item) {
                if (
                    $item->getType() === Type::EQUALS &&
                    $item->getAttribute() === Field::PIPELINE . 'Id'
                ) {
                    $pipelineId = $item->getValue();

                    break;
                }
            }
        }

        if ($pipelineId !== null && !is_string($pipelineId)) {
            return null;
        }

        return $pipelineId;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function getPipeline(string $entityType, SearchParams $searchParams): ?Pipeline
    {
        if (!$this->pipelineMetadata->isEnabled($entityType)) {
            return null;
        }

        $pipeline = null;
        $pipelineId = $this->getPipelineId($searchParams);

        if ($pipelineId) {
            $pipeline = $this->entityProvider->getByClass(Pipeline::class, $pipelineId);
        }

        return $pipeline;
    }
}
