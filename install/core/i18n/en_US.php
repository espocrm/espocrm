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

return array(
	'Main page title' => 'Welcome to EspoCRM',
	'Main page header' => '',
	'Bad init Permission' => 'Permission denied for "{*}" directory. Please set 775 for "{*}" or just execute this command in the terminal <pre><b>{C}</b></pre>',
	'Start page title' => 'License Agreement',
	'Step1 page title' => 'License Agreement',
	'License Agreement' => 'License Agreement',
	'I accept the agreement' => 'I accept the agreement',
	'Choose your language:' => 'Choose your language:',
	'Database Name' => 'Database Name',
	'Host Name' => 'Host Name',
	'Database User Name' => 'Database User Name',
	'Database User Password' => 'Database User Password',
	'Database driver' => 'Database driver',
	'Step2 page title' => 'Database configuration',
	'Step3 page title' => 'Administrator Setup',
	'Step4 page title' => 'System Settings',
	'User Name' => 'User Name',
	'Password' => 'Password',
	'Confirm Password' => 'Confirm your Password',
	'Errors page title' => 'Errors',
	'Finish page title' => 'Installation is complete',
	'Congratulation! Welcome to EspoCRM!' => 'Congratulation! EspoCRM has been successfully installed.',
	'admin' => 'admin',
	'localhost' => 'localhost',

	'Locale' => 'Locale',
	'Outbound Email Configuration' => 'Outbound Email Configuration',
	'SMTP' => 'SMTP',
	'From Address' => 'From Address',
	'From Name' => 'From Name',
	'Is Shared' => 'Is Shared',
	'Date Format' => 'Date Format',
	'Time Format' => 'Time Format',
	'Time Zone' => 'Time Zone',
	'First Day of Week' => 'First Day of Week',
	'Thousand Separator' => 'Thousand Separator',
	'Decimal Mark' => 'Decimal Mark',
	'Default Currency' => 'Default Currency',
	'Currency List' => 'Currency List',
	'Language' => 'Language',

	'smtpServer' => 'Server',
	'smtpPort' => 'Port',
	'smtpAuth' => 'Auth',
	'smtpSecurity' => 'Security',
	'smtpUsername' => 'Username',
	'emailAddress' => 'Email',
	'smtpPassword' => 'Password',


	// messages
	'Some errors occurred!' => 'Some errors occurred!',
	'Supported php version >=' => 'Supported php version >=',
	'The PHP extension was not found...' => 'The <b><ext-name></b> PHP extension was not found...',
	'All Settings correct' => 'All Settings are correct',
	'Failed to connect to database' => 'Failed to connect to database',
	'PHP version:' => 'PHP version:',
	'You must agree to the license agreement' => 'You must agree to the license agreement',
	'Passwords do not match' => 'Passwords do not match',
	'Enable mod_rewrite in Apache server' => 'Enable mod_rewrite in Apache server',
	'checkWritable error' => 'checkWritable error',
	'applySett error' => 'applySett error',
	'buildDatabse error' => 'buildDatabse error',
	'createUser error' => 'createUser error',
	'checkAjaxPermission error' => 'checkAjaxPermission error',
	'Ajax failed' => 'Ajax failed',
	'Cannot create user' => 'Cannot create user',
	'Permission denied' => 'Permission denied',
	'permissionInstruction' => '<br>Run this in Terminal<pre><b>"{C}"</b></pre>',
	'Cannot write to files' => 'Cannot write to file(s)',
	'Can not save settings' => 'Can not save settings',
	'Cannot save preferences' => 'Cannot save preferences',

	'db driver' => array(
		'mysqli' => 'MySQLi',
		'pdo_mysql' => 'PDO MySQL',
	),

	'modRewriteInstruction' => array(
		'apache' => array(
			'linux' => '<br><br>Run those commands in Terminal<pre><b>1. a2enmod rewrite <br>2. service apache2 restart</b></pre>',
			'windows' => '<br> <pre>1. Find the httpd.conf file (usually you will find it in a folder called conf, config or something along those lines)<br>
2. Inside the httpd.conf file uncomment the line LoadModule rewrite_module modules/mod_rewrite.so (remove the pound \'#\' sign from in front of the line)<br>
3. Also find the line ClearModuleList is uncommented then find and make sure that the line AddModule mod_rewrite.c is not commented out.
</pre>',
		),
		'microsoft-iis' => array(
			'windows' => '',

		),
	),

	'modRewriteHelp' => array(
		'apache' => 'Enable "mod_rewrite" in Apache server',
		'nginx' => 'Add this code to Nginx Host Config (inside "server" block):<br>
<pre>
location /api/v1/ {
    if (!-e $request_filename){
        rewrite ^/api/v1/(.*)$ /api/v1/index.php last; break;
    }
}

location / {
    rewrite reset/?$ reset.html break;
}</pre>',
		'microsoft-iis' => 'Enable "URL Rewrite" Module in IIS server',
		'default' => 'Enable Rewrite Module in your server (e.g. mod_rewrite in Apache)',
	),

	'cronTitle' => array(
		'apache' => 'To Setup Crontab:',
		'nginx' => 'To Setup Crontab:',
		'microsoft-iis' => 'To Setup Scheduled Task:',
		'default' => 'To Setup Cron job:',
	),

	'cronHelp' => array(
		'apache' => 'Note: Add this line to the crontab file to run Espo Scheduled Jobs:
* * * * * <php-bin-dir> -f <cron-file> > /dev/null 2>&1',
		'nginx' => 'Note: Add this line to Tasks to run Espo Scheduled Jobs:
* * * * * <php-bin-dir> -f <cron-file> > /dev/null 2>&1',
		'microsoft-iis' => 'Note: Create a batch file with the following commands to run Espo Scheduled Jobs using Windows Scheduled Tasks:
<php-bin-dir>.exe -f <cron-file>',
		'default' => 'Run command <cron-file>',
	),

	// controll
	'Start' => 'Start',
	'Back' => 'Back',
	'Next' => 'Next',
	'Go to EspoCRM' => 'Go to EspoCRM',
	'Re-check' => 'Re-check',
	'Test settings' => 'Test Connection',

	// db errors
	'1049' => 'Unknown database',
	'2005' => 'Unknown MySQL server host',
	'1045' => 'Access denied for user',
);
