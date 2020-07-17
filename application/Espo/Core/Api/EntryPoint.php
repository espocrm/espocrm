<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Api;

use Espo\Core\{
    Api\Request,
    Api\Response,
};

use Espo\Core\{
    EntryPointManager,
    ApplicationUser,
};

use StdClass;


/**
 *
 */
class EntryPoint
{
    protected $authRequired;

    protected $authNotStrict;

    protected $entryPointManager;

    public function __construct(
        EntryPointManager $entryPointManager,
        ApplicationUser $applicationUser,
        bool $authRequired = true,
        bool $authNotStrict = false,
    ) {
        $this->entryPointManager = $entryPointManager;
        $this->applicationUser = $applicationUser;
        $this->authRequired = $authRequired;
        $this->authNotStrict = $authNotStrict;
        $this->entryPoint = $entryPoint;
        $this->data = $data;
    }

    public function process(string $entryPoint, Request $request, Response $response, ?StdClass $data)
    {
        $authentication = $this->injectableFactory->createWith(Authentication::class, [
            'allowAnyAccess' => $this->authNotStrict,
        ]);

        $apiAuth = ApiAuth::createForEntryPoint($authentication, $this->authRequired);

        $apiAuth->process($request, $response);

        if (!$apiAuth->isResolved()) {
            return;
        }
        if ($apiAuth->isResolvedUseNoAuth()) {
            $this->applicationUser->setupSystemUser();
        }

        ob_start();
        $this->entryPointManager->run($entryPoint, $request, $response, $data);
        $contents = ob_get_clean();

        if ($contents) {
            $response->writeBody($contents);
        }
    }
}
