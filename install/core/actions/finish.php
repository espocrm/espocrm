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
 


$serverType = $systemHelper->getServerType();
$serverType = 'microsoft-iis';
$rootDir = dirname(__FILE__);
$rootDir = preg_replace('/\/install\/core\/actions\/?/', '', $rootDir, 1);
$cronFile = $rootDir.DIRECTORY_SEPARATOR.'cron.php';
$phpBinDir = (defined("PHP_BINDIR"))? PHP_BINDIR.DIRECTORY_SEPARATOR.'php' : 'php';

$cronHelp = (isset($langs['cronHelp'][$serverType]))? $langs['cronHelp'][$serverType] : $langs['cronHelp']['default'];
$cronHelp = str_replace('<cron-file>', $cronFile, $cronHelp);
$cronHelp = str_replace('<php-bin-dir>', $phpBinDir, $cronHelp);
$cronTitle = (isset($langs['cronTitle'][$serverType]))? $langs['cronTitle'][$serverType] : $langs['cronTitle']['default'];

$smarty->assign('cronTitle', $cronTitle);
$smarty->assign('cronHelp', $cronHelp);
// clean session
$installer->setSuccess();
session_unset();