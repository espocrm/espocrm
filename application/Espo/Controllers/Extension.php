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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\RecordBase;
use Espo\Core\Upgrades\ExtensionManager;

use stdClass;

class Extension extends RecordBase
{
    protected function checkAccess(): bool
    {
        return $this->user->isAdmin();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function postActionUpload(Request $request): stdClass
    {
        if ($this->config->get('restrictedMode') && !$this->user->isSuperAdmin()) {
            throw new Forbidden();
        }

        if ($this->config->get('adminUpgradeDisabled')) {
            throw new Forbidden("Disabled with 'adminUpgradeDisabled' parameter.");
        }

        $body = $request->getBodyContents();

        if ($body === null) {
            throw new BadRequest();
        }

        $manager = new ExtensionManager($this->getContainer());

        $id = $manager->upload($body);

        $manifest = $manager->getManifest();

        return (object) [
            'id' => $id,
            'version' => $manifest['version'],
            'name' => $manifest['name'],
            'description' => $manifest['description'],
        ];
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function postActionInstall(Request $request): bool
    {
        $data = $request->getParsedBody();

        if ($this->config->get('restrictedMode') && !$this->user->isSuperAdmin()) {
            throw new Forbidden();
        }

        $manager = new ExtensionManager($this->getContainer());

        $manager->install(get_object_vars($data));

        return true;
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function postActionUninstall(Request $request): bool
    {
        $data = $request->getParsedBody();

        if ($this->config->get('restrictedMode') && !$this->user->isSuperAdmin()) {
            throw new Forbidden();
        }

        $manager = new ExtensionManager($this->getContainer());

        $manager->uninstall(get_object_vars($data));

        return true;
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function deleteActionDelete(Request $request, Response $response): bool
    {
        $params = $request->getRouteParams();

        if ($this->config->get('restrictedMode') && !$this->user->isSuperAdmin()) {
            throw new Forbidden();
        }

        $manager = new ExtensionManager($this->getContainer());

        $manager->delete($params);

        return true;
    }

    public function postActionCreate(Request $request, Response $response): stdClass
    {
        throw new Forbidden();
    }

    public function putActionUpdate(Request $request, Response $response): stdClass
    {
        throw new Forbidden();
    }
}
