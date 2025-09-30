<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

include "../../bootstrap.php";

use Espo\Core\Application;
use Espo\Core\Application\Runner\Params;
use Espo\Core\ApplicationRunners\EntryPoint;
use Espo\Core\ApplicationRunners\PortalClient;
use Espo\Core\Portal\Utils\Url;

$app = new Application();

if (!$app->isInstalled()) {
    exit;
}

$basePath = null;

if (Url::detectIsInPortalDir()) {
    $basePath = '../';

    if (Url::detectIsInPortalWithId()) {
        $basePath = '../../';

        $redirectUrl = Url::getRedirectUrlWithTrailingSlash();

        if ($redirectUrl) {
            header('Location: ' . $redirectUrl, true, 301);

            exit;
        }
    }
}

if ($basePath !== null) {
    $app->setClientBasePath($basePath);
}

if (filter_has_var(INPUT_GET, 'entryPoint')) {
    $app->run(EntryPoint::class);

    exit;
}

$app->run(
    PortalClient::class,
    Params::create()->with('basePath', $basePath)
);
