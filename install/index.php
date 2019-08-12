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

session_start();
require_once('../bootstrap.php');

//action
$action = (!empty($_POST['action']))? $_POST['action'] : 'main';
require_once('core/Utils.php');
if (!Utils::checkActionExists($action)) {
	die('This page does not exist.');
}

// temp save all settings
$ignoredFields = array('installProcess', 'dbName', 'hostName', 'dbUserName', 'dbUserPass', 'dbDriver');

if (!empty($_POST)) {
	foreach ($_POST as $key => $val) {
		if (!in_array($key, $ignoredFields)) {
			$_SESSION['install'][$key] = trim($val);
		}
	}
}

// get user selected language
$userLang = (!empty($_SESSION['install']['user-lang']))? $_SESSION['install']['user-lang'] : 'en_US';

require_once 'core/Language.php';
$language = new Language();
$langs = $language->get($userLang);
$sanitizedLangs = \Espo\Core\Utils\Util::sanitizeHtml($langs);
//END: get user selected language

$config = include('core/config.php');

require_once 'core/SystemHelper.php';
$systemHelper = new SystemHelper();

$systemConfig = include('application/Espo/Core/defaults/systemConfig.php');
if (isset($systemConfig['requiredPhpVersion']) && version_compare(PHP_VERSION, $systemConfig['requiredPhpVersion'], '<')) {
    die(str_replace('{minVersion}', $systemConfig['requiredPhpVersion'], $sanitizedLangs['messages']['phpVersion']) . '.');
}

if (!$systemHelper->initWritable()) {
	$dir = $systemHelper->getWritableDir();

	$message = $sanitizedLangs['messages']['Bad init Permission'];
	$message = str_replace('{*}', $dir, $message);
	$message = str_replace('{C}', $systemHelper->getPermissionCommands(array($dir, ''), '775'), $message);
	$message = str_replace('{CSU}', $systemHelper->getPermissionCommands(array($dir, ''), '775', true), $message);
	die($message);
}

require_once ('install/vendor/smarty/libs/Smarty.class.php');

require_once 'core/Installer.php';
require_once 'core/Utils.php';

$smarty = new Smarty();
$installer = new Installer();

// check if app was installed
if ($installer->isInstalled() && !isset($_SESSION['install']['installProcess'])) {
	$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url = preg_replace('/install\/?/', '', $url, 1);
	$url = strtok($url, '#');
	$url = strtok($url, '?');
	header("Location: {$url}");
	exit;
}
else {
	// double check if infinite loop
	$_SESSION['install']['installProcess'] = true;
}

$smarty->caching = false;
$smarty->setTemplateDir('install/core/tpl');

$smarty->assign("version", $installer->getVersion());
$smarty->assign("langs", $sanitizedLangs);
$smarty->assign("langsJs", json_encode($langs));

// include actions and set tpl name
switch ($action) {
	case 'main':
		$languageList = $installer->getLanguageList();
		$smarty->assign("languageList", $languageList);
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
    	$settingsDefaults = $installer->getSettingDefaults();
		$smarty->assign("settingsDefaults", $settingsDefaults);
		break;

    case 'step5':
    	$settingsDefaults = $installer->getSettingDefaults();
		$smarty->assign("settingsDefaults", $settingsDefaults);
		break;
}

$actionFile = 'core/actions/'.$action.'.php';
$tplName = $action.'.tpl';
$smarty->assign('tplName', $tplName);
$smarty->assign('action', ucfirst($action));

/** config */
$smarty->assign('config', $config);

if (Utils::checkActionExists($action)) {
	include $actionFile;
}

if (!empty($actionFile) && file_exists('install/core/tpl/'.$tplName)) {
	/*check if EspoCRM is built*/
	$isBuilt = file_exists('client/espo.min.js');
	$smarty->assign('isBuilt', $isBuilt);

	$smarty->display('index.tpl');
}
