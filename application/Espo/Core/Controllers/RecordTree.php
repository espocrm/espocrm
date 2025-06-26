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

namespace Espo\Core\Controllers;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\Entity;
use Espo\Core\Templates\Entities\CategoryTree;
use Espo\Services\RecordTree as Service;
use Espo\Core\Api\Request;
use Espo\Tools\CategoryTree\Move\MoveParams;
use Espo\Tools\CategoryTree\MoveService;
use Espo\Tools\CategoryTree\Record\ReadTreeParams;
use RuntimeException;
use stdClass;

class RecordTree extends Record
{
    /**
     * @var string
     */
    public static $defaultAction = 'list';

    /**
     * Get a category tree.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @noinspection PhpUnused
     */
    public function getActionListTree(Request $request): stdClass
    {
        $selectParams = $this->fetchSearchParamsFromRequest($request);

        $parentId = $request->getQueryParam('parentId');
        $currentId = $request->getQueryParam('currentId');
        $maxDepth = $request->getQueryParam('maxDepth');
        $onlyNotEmpty = (bool) $request->getQueryParam('onlyNotEmpty');

        if ($parentId && $currentId) {
            throw new BadRequest("Cannot have both parentId and currentId set.");
        }

        if ($maxDepth !== null) {
            $maxDepth = (int) $maxDepth;
        }

        $params = new ReadTreeParams(
            where: $selectParams->getWhere(),
            onlyNotEmpty: $onlyNotEmpty,
            currentId: $currentId,
            maxDepth: $maxDepth,
            parentId: $parentId,
        );

        $service = $this->getRecordTreeService();

        $collection = $service->getTree($params);

        if (!$collection) {
            throw new RuntimeException();
        }

        $openPath = null;

        if ($params->currentId) {
            $openPath = $service->getTreeItemPath($params->currentId);
        }

        return (object) [
            'list' => $collection->getValueMapList(),
            'path' => $service->getTreeItemPath($parentId),
            'data' => $service->getCategoryData($parentId),
            'openPath' => $openPath,
        ];
    }

    /**
     * @return string[]
     * @throws Forbidden
     * @throws BadRequest
     * @noinspection PhpUnused
     */
    public function getActionLastChildrenIdList(Request $request): array
    {
        if (!$this->acl->check($this->name, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $parentId = $request->getQueryParam('parentId');

        return $this->getRecordTreeService()->getLastChildrenIdList($parentId);
    }

    /**
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     * @throws Error
     * @noinspection PhpUnused
     */
    public function postActionMove(Request $request): bool
    {
        if (!$this->acl->check($this->name, Table::ACTION_EDIT)) {
            throw new Forbidden();
        }

        $id = $request->getParsedBody()->id ?? null;
        $referenceId = $request->getParsedBody()->referenceId ?? null;
        $type = $request->getParsedBody()->type ?? null;

        if (!is_string($id)) {
            throw new BadRequest("Bad id.");
        }

        if (!is_string($referenceId)) {
            throw new BadRequest("Bad referenceId.");
        }

        $typeInternal = match ($type) {
            'into' => MoveParams::TYPE_INTO,
            'before' => MoveParams::TYPE_BEFORE,
            'after' => MoveParams::TYPE_AFTER,
            default => null,
        };

        if ($typeInternal === null) {
            throw new BadRequest("Bad type.");
        }

        $entity = $this->getRecordService()->getEntity($id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        if (!$entity instanceof CategoryTree) {
            throw new RuntimeException("Non-tree entity.");
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden("No edit access.");
        }

        $params = new MoveParams(
            type: $typeInternal,
            referenceId: $referenceId,
        );

        $service = $this->injectableFactory->create(MoveService::class);

        $service->move($entity, $params);

        return true;
    }

    /**
     * @return Service<Entity>
     */
    protected function getRecordTreeService(): Service
    {
        $service = $this->getRecordService();

        if (!$service instanceof Service) {
            throw new RuntimeException();
        }

        return $service;
    }
}
