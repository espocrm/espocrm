<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

session_start();

require_once('../bootstrap.php');


// temp save all settings
$ignore = array('desc', 'dbName', 'hostName', 'dbUserName', 'dbUserPass', 'dbDriver');

if (!empty($_REQUEST)) {
	foreach ($_REQUEST as $key => $val) {
		if (!in_array($val, $ignore))
		$_SESSION['install'][$key] = trim($val);
	}
}

// get user selected language
$userLang = (!empty($_SESSION['install']['user-lang']))? $_SESSION['install']['user-lang'] : 'en_US';

require_once 'core/Language.php';
$language = new Language();
$langs = $language->get($userLang);
//END: get user selected language

require_once 'core/SystemHelper.php';
$systemHelper = new SystemHelper();

if (!$systemHelper->initWritable()) {
	$dir = $systemHelper->getWritableDir();

	$message = $langs['messages']['Bad init Permission'];
	$message = str_replace('{*}', $dir, $message);
	$message = str_replace('{C}', $systemHelper->getPermissionCommands(array($dir, ''), '775'), $message);
	$message = str_replace('{CSU}', $systemHelper->getPermissionCommands(array($dir, ''), '775', true), $message);
	die($message);
}

require_once ('install/vendor/smarty/libs/Smarty.class.php');

require_once 'core/Installer.php';

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
$smarty->assign("langs", $langs);
$smarty->assign("langsJs", json_encode($langs));

// include actions and set tpl name
$tplName = 'main.tpl';
$actionsDir = 'core/actions';
$actionFile = '';
$action = (!empty($_REQUEST['action']))? $_REQUEST['action'] : 'main';

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

$actionFile = $actionsDir.'/'.$action.'.php';
$tplName = $action.'.tpl';
$smarty->assign('tplName', $tplName);
$smarty->assign('action', ucfirst($action));

/** config */
$config = include('core/config.php');
$smarty->assign('config', $config);

if (!empty($actionFile) && file_exists('install/'.$actionFile)) {
	include $actionFile;
}

if (!empty($actionFile) && file_exists('install/core/tpl/'.$tplName)) {
	ob_clean();
	$smarty->display('index.tpl');
}
