<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Controllers;

use Espo\{
    Entities\User,
    Tools\FieldManager\FieldManager as FieldManagerTool,
};

use Espo\Core\{
    Exceptions\Conflict,
    Exceptions\Error,
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Api\Request,
    DataManager};

class FieldManager
{
    private $user;

    private $dataManager;

    private $fieldManagerTool;

    /**
     * @throws Forbidden
     */
    public function __construct(User $user, DataManager $dataManager, FieldManagerTool $fieldManagerTool)
    {
        $this->user = $user;
        $this->dataManager = $dataManager;
        $this->fieldManagerTool = $fieldManagerTool;

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
     * @return array<string,mixed>
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
     * @return array<string,mixed>
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

        $fieldManagerTool->create($scope, $name, get_object_vars($data));

        try {
            $this->dataManager->rebuild([$scope]);
        }
        catch (Error $e) {
            $fieldManagerTool->delete($scope, $data->name);

            throw new Error($e->getMessage());
        }

        return $fieldManagerTool->read($scope, $data->name);
    }

    /**
     * @return array<string,mixed>
     * @throws BadRequest
     * @throws Error
     */
    public function patchActionUpdate(Request $request): array
    {
        return $this->putActionUpdate($request);
    }

    /**
     * @return array<string,mixed>
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
            $this->dataManager->rebuild([$scope]);
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

        $result = $this->fieldManagerTool->delete($scope, $name);

        $this->dataManager->rebuildMetadata();

        return $result;
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    public function postActionResetToDefault(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->scope) || empty($data->name)) {
            throw new BadRequest();
        }

        $this->fieldManagerTool->resetToDefault($data->scope, $data->name);

        $this->dataManager->clearCache();
        $this->dataManager->rebuildMetadata();

        return true;
    }
}
