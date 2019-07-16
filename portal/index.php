<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

include "../bootstrap.php";

$app = new \Espo\Core\Application();
if (!$app->isInstalled()) {
    exit;
}

$url = $_SERVER['REQUEST_URI'];
$requestUri = $url;

$portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];

if (!isset($portalId)) {
    $url = $_SERVER['REDIRECT_URL'];
    $portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];
}

$a = explode('?', $url);
if (substr($a[0], -1) !== '/') {
    $url = $a[0] . '/';
    if (count($a) > 1) {
        $url .= '?' . $a[1];
    }
    header("Location: " . $url);
    exit();
}

$a = explode('?', $requestUri);
$requestUri = rtrim($a[0], '/');

if (strpos($requestUri, '/') !== false) {
    if ($portalId) {
        $app->setBasePath('../../');
    } else {
        $app->setBasePath('../');
    }
}

if (!empty($_GET['entryPoint'])) {
    $app->runEntryPoint($_GET['entryPoint']);
    exit;
}

$app->runEntryPoint('portal');
