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

use Espo\Core\Utils\Client\LoaderParamsProvider;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Client\DevModeJsFileListProvider;
use Espo\Core\Utils\File\Manager as FileManager;

if (session_status() !== \PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($postData)) {
    require_once('install/core/PostData.php');

    $postData = new PostData();
}

$allPostData = $postData->getAll();

$action = (!empty($allPostData['action']))? $allPostData['action'] : 'main';

require_once('install/core/Utils.php');

if (!Utils::checkActionExists($action)) {
    die('This page does not exist.');
}


// temp save all settings
$ignoredFields = [
    'installProcess',
    'dbName',
    'hostName',
    'dbUserName',
    'dbUserPass',
    'dbDriver',
];


if (!empty($allPostData)) {
    foreach ($allPostData as $key => $val) {
        if (!in_array($key, $ignoredFields)) {
            $_SESSION['install'][$key] = trim($val);
        }
    }
}

// get user selected language
$userLang = (!empty($_SESSION['install']['user-lang']))? $_SESSION['install']['user-lang'] : 'en_US';

require_once 'install/core/Language.php';

$language = new Language();

$langs = $language->get($userLang);

$sanitizedLangs = Util::sanitizeHtml($langs);
//END: get user selected language

$config = include('install/core/config.php');

require_once 'install/core/SystemHelper.php';

$systemHelper = new SystemHelper();

$systemConfig = include('application/Espo/Resources/defaults/systemConfig.php');

if (
    isset($systemConfig['requiredPhpVersion']) &&
    version_compare(PHP_VERSION, $systemConfig['requiredPhpVersion'], '<')
) {
    die(
        str_replace(
            "{minVersion}",
            $systemConfig['requiredPhpVersion'],
            $sanitizedLangs['messages']['phpVersion']
        ) . ".\n"
    );
}

if (!$systemHelper->initWritable()) {
    $dir = $systemHelper->getWritableDir();

    $message = $sanitizedLangs['messages']['Bad init Permission'];
    $message = str_replace('{*}', $dir, $message);
    $message = str_replace('{C}', $systemHelper->getPermissionCommands([$dir, ''], '775'), $message);
    $message = str_replace('{CSU}', $systemHelper->getPermissionCommands([$dir, ''], '775', true), $message);

    die($message . "\n");
}

require_once 'install/vendor/smarty/libs/Smarty.class.php';

require_once 'install/core/Installer.php';
require_once 'install/core/Utils.php';

$smarty = new Smarty();
$installer = new Installer();

// check if app was installed
if ($installer->isInstalled() && !isset($_SESSION['install']['installProcess'])) {
    if (isset($_SESSION['install']['redirected']) && $_SESSION['install']['redirected']) {
        die('The installation is disabled. It can be enabled in config files.');
    }

    $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $url = preg_replace('/install\/?/', '', $url, 1);
    $url = strtok($url, '#');
    $url = strtok($url, '?');

    $_SESSION['install']['redirected'] = true;

    header("Location: {$url}");

    exit;
}

$_SESSION['install']['installProcess'] = true;

$smarty->caching = false;
$smarty->setTemplateDir('install/core/tpl');

$smarty->assign("version", $installer->getVersion());
$smarty->assign("langs", $sanitizedLangs);
$smarty->assign("langsJs", json_encode($langs));

// include actions and set tpl name
switch ($action) {
    case 'main':
        $smarty->assign("languageList", $installer->getLanguageList());

        break;

    case 'step3':
    case 'errors':
    case 'setupConfirmation':
        $smarty->assign("apiPath", $systemHelper->getApiPath());
        $modRewriteUrl = $systemHelper->getModRewriteUrl();
        $smarty->assign("modRewriteUrl", $modRewriteUrl);
        $serverType = $systemHelper->getServerType();
        $smarty->assign("serverType", $serverType);
        $os = $systemHelper->getOS();
        $smarty->assign("OS", $os);

        break;

    case 'step4':
        $defaultSettings = $installer->getDefaultSettings();
        $smarty->assign("defaultSettings", $defaultSettings);

        break;

    case 'step5':
        $defaultSettings = $installer->getDefaultSettings();
        $smarty->assign("defaultSettings", $defaultSettings);

        break;
}

$actionFile = 'install/core/actions/' . $action . '.php';

$tplName = $action . '.tpl';

$smarty->assign('tplName', $tplName);
$smarty->assign('action', ucfirst($action));

$smarty->assign('config', $config);
$smarty->assign('installerConfig', $installer->getInstallerConfigData());

$theme = $_SESSION['install']['theme'] ?? 'Espo';
$stylesheet = $installer->getMetadata()->get(['themes', $theme, 'stylesheet']);

$smarty->assign('stylesheet', $stylesheet);

if (Utils::checkActionExists($action)) {
    include $actionFile;
}

$theme = $_SESSION['install']['theme'] ?? $installer->getConfig()->get('theme');

$smarty->assign('logoSrc', $installer->getLogoSrc($theme));

$loaderParamsProvider = $installer->getInjectableFactory()->create(LoaderParamsProvider::class);

if (!empty($actionFile) && file_exists('install/core/tpl/' . $tplName)) {
    /* check if EspoCRM is built */
    $isBuilt = file_exists('client/lib/espo.js');

    $smarty->assign('isBuilt', $isBuilt);

    $libFileList = $isBuilt ?
        $installer->getMetadata()->get(['app', 'client', 'scriptList']) ?? [] :
        (new DevModeJsFileListProvider(new FileManager()))->get();

    $smarty->assign('libFileList', $libFileList);

    $loaderParams = Json::encode([
        'basePath' => '../',
        'libsConfig' => $loaderParamsProvider->getLibsConfig(),
        'aliasMap' => $loaderParamsProvider->getAliasMap(),
    ]);

    $smarty->assign('loaderParams', $loaderParams);

    $smarty->display('index.tpl');
}

