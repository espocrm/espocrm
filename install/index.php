<?php
session_start();

require_once('../bootstrap.php');

require_once ('install/vendor/smarty/libs/Smarty.class.php');

require_once 'core/Installer.php';

require_once 'core/SystemTest.php';

$smarty = new Smarty();
$installer = new Installer();

// check if app was installed
if ($installer->isInstalled() && !isset($_SESSION['install']['installProcess'])) {
	$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$url = str_replace('install/', '', $url);
	$url = str_replace('install', '', $url);
	$url = strtok($url, '#');
	$url = strtok($url, '?');
	header("Location: {$url}");
	exit;
}
else {
	$_SESSION['install']['installProcess'] = true;
}

$smarty->caching = false;
$smarty->setTemplateDir('install/core/tpl');



// temp save all settings
$ignore = array('desc', 'dbName', 'hostName', 'dbUserName', 'dbUserPass', 'dbDriver');
if (!empty($_REQUEST)) {
	foreach ($_REQUEST as $key => $val) {
		if (!in_array($val, $ignore))
		$_SESSION['install'][$key] = $val;
	}
}

// get user selected language
$userLang = (!empty($_SESSION['install']['user-lang']))? $_SESSION['install']['user-lang'] : 'en_US';
$langFileName = 'core/i18n/'.$userLang.'.php';
$langs = array();
if (file_exists('install/'.$langFileName)) {
	$langs = include($langFileName);
}
else {
	$langs = include('core/i18n/en_US.php');
}

$smarty->assign("langs", $langs);
$smarty->assign("langsJs", json_encode($langs));

$systemTest = new SystemTest();

// get urls for api
$ajaxUrls = $installer->getAjaxUrls();
$smarty->assign("ajaxUrls", json_encode($ajaxUrls));

// include actions and set tpl name
$tplName = 'main.tpl';
$actionsDir = 'core/actions';
$actionFile = '';
$action = (!empty($_REQUEST['action']))? $_REQUEST['action'] : 'main';

$actionFile = $actionsDir.'/'.$action.'.php';
$tplName = $action.'.tpl';
$smarty->assign('tplName', $tplName);

if (!empty($actionFile) && file_exists('install/'.$actionFile)) {
	include $actionFile;
}


if (!empty($actionFile) && file_exists('install/core/tpl/'.$tplName)) {
	ob_clean();
	$smarty->display('index.tpl');
}
