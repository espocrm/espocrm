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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Core\ApplicationState;
use Espo\Core\Api\Request;

use stdClass;

/**
 * @noinspection PhpUnused
 * @todo Move to a service class.
 */
class TemplateManager
{
    /**
     * @throws Forbidden
     */
    public function __construct(
        private Metadata $metadata,
        private TemplateFileManager $templateFileManager,
        private ApplicationState $applicationState,
        private Config $config
    ) {

        if (!$this->applicationState->isAdmin()) {
            throw new Forbidden();
        }
    }

    /**
     * @throws BadRequest
     */
    public function getActionGetTemplate(Request $request): stdClass
    {
        $name = $request->getQueryParam('name');

        if (empty($name)) {
            throw new BadRequest();
        }

        $scope = $request->getQueryParam('scope');

        $module = $this->metadata->get(['app', 'templates', $name, 'module']);
        $hasSubject = !$this->metadata->get(['app', 'templates', $name, 'noSubject']);

        $templateFileManager = $this->templateFileManager;

        $returnData = (object) [];

        $returnData->body = $templateFileManager->getTemplate($name, 'body', $scope, $module);

        if ($hasSubject) {
            $returnData->subject = $templateFileManager->getTemplate($name, 'subject', $scope, $module);
        }

        return $returnData;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionSaveTemplate(Request $request): bool
    {
        $data = $request->getParsedBody();

        $scope = null;

        if (empty($data->name)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new BadRequest();
        }

        if (
            $data->name === 'passwordChangeLink' &&
            $this->config->get('restrictedMode') &&
            !$this->applicationState->getUser()->isSuperAdmin()
        ) {
            throw new Forbidden();
        }

        if (!empty($data->scope)) {
            $scope = $data->scope;
        }

        $templateFileManager = $this->templateFileManager;

        if (isset($data->subject)) {
            $templateFileManager->saveTemplate($data->name, 'subject', $data->subject, $scope);
        }

        if (isset($data->body)) {
            $templateFileManager->saveTemplate($data->name, 'body', $data->body, $scope);
        }

        return true;
    }

    /**
     * @throws BadRequest
     */
    public function postActionResetTemplate(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $scope = null;

        if (empty($data->name)) {
            throw new BadRequest();
        }

        if (!empty($data->scope)) {
            $scope = $data->scope;
        }

        $module = $this->metadata->get(['app', 'templates', $data->name, 'module']);
        $hasSubject = !$this->metadata->get(['app', 'templates', $data->name, 'noSubject']);

        $templateFileManager = $this->templateFileManager;

        if ($hasSubject) {
            $templateFileManager->resetTemplate($data->name, 'subject', $scope);
        }

        $templateFileManager->resetTemplate($data->name, 'body', $scope);

        $returnData = (object) [];

        $returnData->body = $templateFileManager->getTemplate($data->name, 'body', $scope, $module);

        if ($hasSubject) {
            $returnData->subject = $templateFileManager->getTemplate($data->name, 'subject', $scope, $module);
        }

        return $returnData;
    }
}
