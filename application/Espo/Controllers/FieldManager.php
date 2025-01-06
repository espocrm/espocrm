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

namespace Espo\Controllers;

use Espo\Entities\User;
use Espo\Tools\FieldManager\FieldManager as FieldManagerTool;
use Espo\Core\Api\Request;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;

/**
 * @noinspection PhpUnused
 */
class FieldManager
{
    /**
     * @throws Forbidden
     */
    public function __construct(
        private User $user,
        private DataManager $dataManager,
        private FieldManagerTool $fieldManagerTool,
    ) {
        $this->checkControllerAccess();
    }

    /**
     * @throws Forbidden
     */
    protected function checkControllerAccess(): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Error
     */
    public function getActionRead(Request $request): array
    {
        $scope = $request->getRouteParam('scope');
        $name = $request->getRouteParam('name');

        if (!$scope || !$name) {
            throw new BadRequest();
        }

        return $this->fieldManagerTool->read($scope, $name);
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Conflict
     * @throws Error
     */
    public function postActionCreate(Request $request): array
    {
        $data = $request->getParsedBody();

        $scope = $request->getRouteParam('scope');
        $name = $data->name ?? null;

        if (!$scope || !$name) {
            throw new BadRequest();
        }

        $fieldManagerTool = $this->fieldManagerTool;

        $name = $fieldManagerTool->create($scope, $name, get_object_vars($data));

        try {
            $this->rebuild($scope);
        } catch (Error $e) {
            $fieldManagerTool->delete($scope, $name);

            throw new Error($e->getMessage());
        }

        return $fieldManagerTool->read($scope, $name);
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Error
     */
    public function patchActionUpdate(Request $request): array
    {
        return $this->putActionUpdate($request);
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Error
     */
    public function putActionUpdate(Request $request): array
    {
        $data = $request->getParsedBody();

        $scope = $request->getRouteParam('scope');
        $name = $request->getRouteParam('name');

        if (!$scope || !$name) {
            throw new BadRequest();
        }

        $fieldManagerTool = $this->fieldManagerTool;

        $fieldManagerTool->update($scope, $name, get_object_vars($data));

        if ($fieldManagerTool->isChanged()) {
            $this->rebuild($scope);
        } else {
            $this->dataManager->clearCache();
        }

        return $fieldManagerTool->read($scope, $name);
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function deleteActionDelete(Request $request): bool
    {
        $scope = $request->getRouteParam('scope');
        $name = $request->getRouteParam('name');

        if (!$scope || !$name) {
            throw new BadRequest();
        }

        $this->fieldManagerTool->delete($scope, $name);

        $this->dataManager->clearCache();
        $this->dataManager->rebuildMetadata();

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionResetToDefault(Request $request): bool
    {
        $data = $request->getParsedBody();

        $scope = $data->scope ?? null;
        $name = $data->name ?? null;

        if (!$scope || !$name) {
            throw new BadRequest();
        }

        if (!is_string($scope) || !is_string($name)) {
            throw new BadRequest();
        }

        $this->fieldManagerTool->resetToDefault($scope, $name);

        $this->rebuild($scope);

        return true;
    }

    /**
     * @throws Error
     */
    private function rebuild(string $scope): void
    {
        $this->dataManager->rebuild([$scope]);
    }
}
