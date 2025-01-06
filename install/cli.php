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

if (substr(php_sapi_name(), 0, 3) != 'cli') {
    die('The file can be run only via CLI.');
}

$options = getopt("a:d:");

if (empty($options['a'])) {
    fwrite(\STDOUT, "Error: the option [-a] is required.\n");

    exit;
}

$allPostData = [];

if (!empty($options['d'])) {
    parse_str($options['d'], $allPostData);

    if (empty($allPostData) || !is_array($allPostData)) {
        fwrite(\STDOUT, "Error: Incorrect input data.\n");

        exit;
    }
}

$action = $options['a'];
$allPostData['action'] = $action;

chdir(dirname(__FILE__));
set_include_path(dirname(__FILE__));

require_once('../bootstrap.php');

$_SERVER['SERVER_SOFTWARE'] = 'Cli';

require_once('core/PostData.php');

$postData = new PostData();
$postData->set($allPostData);

require_once('core/InstallerConfig.php');

$installerConfig = new InstallerConfig();

if ($installerConfig->get('isInstalled')) {
    fwrite(\STDOUT, "Error: EspoCRM is already installed.\n");

    exit;
}

if (session_status() != \PHP_SESSION_ACTIVE) {
    if (!$installerConfig->get('cliSessionId')) {
        session_start();

        $installerConfig->set('cliSessionId', session_id());
        $installerConfig->save();
    } else {
        session_id($installerConfig->get('cliSessionId'));
    }
}

ob_start();

try {
    require('entry.php');
} catch (\Throwable $e) {
    fwrite(\STDOUT, "Error: ". $e->getMessage() .".\n");

    exit;
}

$result = ob_get_contents();
ob_end_clean();

if (preg_match('/"success":false/i', $result)) {
    $resultData = json_decode($result, true);

    if (empty($resultData)) {
        fwrite(\STDOUT, "Error: Unexpected error occurred.\n");

        exit;
    }

    fwrite(
        \STDOUT,
        "Error: ". (!empty($resultData['errors']) ?
            print_r($resultData['errors'], true) :
            $resultData['errorMsg']) ."\n"
    );

    exit;
}
