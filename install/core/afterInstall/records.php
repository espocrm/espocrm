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

return [
    'EmailTemplate' => [
        [
            'name' => 'Case-to-Email auto-reply',
            'subject' => 'Case has been created',
            'body' => '<p>{Person.name},</p><p>Case \'{Case.name}\' has been created with number '.
                '{Case.number} and assigned to {User.name}.</p>',
            'isHtml ' => '1',
        ]
    ],
    'ScheduledJob' => [
        [
            'name' => 'Check Group Email Accounts',
            'job' => 'CheckInboundEmails',
            'status' => 'Active',
            'scheduling' => '*/2 * * * *',
        ],
        [
            'name' => 'Check Personal Email Accounts',
            'job' => 'CheckEmailAccounts',
            'status' => 'Active',
            'scheduling' => '*/1 * * * *',
        ],
        [
            'name' => 'Send Email Reminders',
            'job' => 'SendEmailReminders',
            'status' => 'Active',
            'scheduling' => '*/2 * * * *',
        ],
        [
            'name' => 'Send Email Notifications',
            'job' => 'SendEmailNotifications',
            'status' => 'Active',
            'scheduling' => '*/2 * * * *',
        ],
        [
            'name' => 'Clean-up',
            'job' => 'Cleanup',
            'status' => 'Active',
            'scheduling' => '1 1 * * 0',
        ],
        [
            'name' => 'Send Mass Emails',
            'job' => 'ProcessMassEmail',
            'status' => 'Active',
            'scheduling' => '10,30,50 * * * *',
        ],
        [
            'name' => 'Auth Token Control',
            'job' => 'AuthTokenControl',
            'status' => 'Active',
            'scheduling' => '*/6 * * * *',
        ],
        [
            'name' => 'Control Knowledge Base Article Status',
            'job' => 'ControlKnowledgeBaseArticleStatus',
            'status' => 'Active',
            'scheduling' => '10 1 * * *',
        ],
        [
            'name' => 'Process Webhook Queue',
            'job' => 'ProcessWebhookQueue',
            'status' => 'Active',
            'scheduling' => '*/2 * * * *',
        ],
        [
            'name' => 'Send Scheduled Emails',
            'job' => 'SendScheduledEmails',
            'status' => 'Active',
            'scheduling' => '*/10 * * * *',
        ],
    ],
];
