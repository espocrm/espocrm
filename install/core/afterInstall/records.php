<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

return array(
	'EmailTemplate' => array(
		0 => array(
			'name' => 'Case-to-Email auto-reply',
			'subject' => 'Case has been created',
			'body' => '<p>{Person.name},</p><p>Case \'{Case.name}\' has been created with number {Case.number} and assigned to {User.name}.</p>',
			'isHtml ' => '1',
		),
	),
	'ScheduledJob' => array(
		0 => array(
			'name' => 'Check Group Email Accounts',
			'job' => 'CheckInboundEmails',
			'status' => 'Active',
			'scheduling' => '*/4 * * * *',
		),
		1 => array(
			'name' => 'Check Personal Email Accounts',
			'job' => 'CheckEmailAccounts',
			'status' => 'Active',
			'scheduling' => '*/5 * * * *',
		),
		2 => array(
			'name' => 'Send Email Reminders',
			'job' => 'SendEmailReminders',
			'status' => 'Active',
			'scheduling' => '*/2 * * * *',
		),
		3 => array(
			'name' => 'Clean-up',
			'job' => 'Cleanup',
			'status' => 'Active',
			'scheduling' => '1 1 * * 0',
		),
		4 => array(
			'name' => 'Send Mass Emails',
			'job' => 'ProcessMassEmail',
			'status' => 'Active',
			'scheduling' => '0 1-5 * * *',
		),

	),
);