<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Core\ApplicationState;

use Espo\Core\{
    Api\Request,
};

use StdClass;

class TemplateManager
{
    private $metadata;

    private $templateFileManager;

    private $applicationState;

    public function __construct(
        Metadata $metadata,
        TemplateFileManager $templateFileManager,
        ApplicationState $applicationState
    ) {
        $this->metadata = $metadata;
        $this->templateFileManager = $templateFileManager;
        $this->applicationState = $applicationState;

        if (!$this->applicationState->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function getActionGetTemplate(Request $request): StdClass
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

    public function postActionSaveTemplate(Request $request): bool
    {
        $data = $request->getParsedBody();

        $scope = null;

        if (empty($data->name)) {
            throw new BadRequest();
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

    public function postActionResetTemplate(Request $request): StdClass
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
