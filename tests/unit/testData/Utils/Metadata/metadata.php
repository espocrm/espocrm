<?php

return  [
    'app' =>
     [
        'adminPanel' =>
         [
            'system' =>
             [
                'label' => 'System',
                'items' =>
                 [
                    0 =>
                     [
                        'url' => '#Admin/settings',
                        'label' => 'Settings',
                        'description' => 'System settings of application.',
                    ],
                    1 =>
                     [
                        'url' => '#ScheduledJob',
                        'label' => 'Scheduled Jobs',
                        'description' => 'Jobs which are executed by cron.',
                    ],
                    2 =>
                     [
                        'url' => '#Admin/clearCache',
                        'label' => 'Clear Cache',
                        'description' => 'Clear all backend cache.',
                    ],
                    3 =>
                     [
                        'url' => '#Admin/rebuild',
                        'label' => 'Rebuild',
                        'description' => 'Rebuild backend and clear cache.',
                    ],
                ],
            ],
            'users' =>
             [
                'label' => 'Users',
                'items' =>
                 [
                    0 =>
                     [
                        'url' => '#User',
                        'label' => 'Users',
                        'description' => 'Users management.',
                    ],
                    1 =>
                     [
                        'url' => '#Team',
                        'label' => 'Teams',
                        'description' => 'Teams management.',
                    ],
                    2 =>
                     [
                        'url' => '#Role',
                        'label' => 'Roles',
                        'description' => 'Roles management.',
                    ],
                ],
            ],
            'email' =>
             [
                'label' => 'Email',
                'items' =>
                 [
                    0 =>
                     [
                        'url' => '#Admin/outboundEmail',
                        'label' => 'Outbound Emails',
                        'description' => 'SMTP settings for outgoing emails.',
                    ],
                    1 =>
                     [
                        'url' => '#InboundEmail',
                        'label' => 'Inbound Emails',
                        'description' => 'Group IMAP email accouts. Email import and Email-to-Case.',
                    ],
                    2 =>
                     [
                        'url' => '#EmailTemplate',
                        'label' => 'Email Templates',
                        'description' => 'Templates for outbound emails.',
                    ],
                ],
            ],
            'data' =>
             [
                'label' => 'Data',
                'items' =>
                 [
                    0 =>
                     [
                        'url' => '#Admin/import',
                        'label' => 'Import',
                        'description' => 'Import data from CSV file.',
                    ],
                ],
            ],
            'customization' =>
             [
                'label' => 'Customization',
                'items' =>
                 [
                    0 =>
                     [
                        'url' => '#Admin/layouts',
                        'label' => 'Layout Manager',
                        'description' => 'Customize layouts (list, detail, edit, search, mass update).',
                    ],
                    1 =>
                     [
                        'url' => '#Admin/fields',
                        'label' => 'Field Manager',
                        'description' => 'Create new fields or customize existing ones.',
                    ],
                    2 =>
                     [
                        'url' => '#Admin/userInterface',
                        'label' => 'User Interface',
                        'description' => 'Configure UI.',
                    ],
                ],
            ],
        ],
        'defaultDashboardLayout' =>
         [
            0 =>
             [
                0 =>
                 [
                    'name' => 'Stream',
                    'id' => 'd4',
                ],
                1 =>
                 [
                    'name' => 'Calendar',
                    'id' => 'd1',
                ],
            ],
            1 =>
             [
                0 =>
                 [
                    'name' => 'Tasks',
                    'id' => 'd3',
                ],
            ],
        ],
    ],
    'customTest' =>
     [
        'CustomTest' =>
         [
            'name' => 'CustomTestModuleName',
            'var1' =>
             [
                'subvar1' => 'NEWsubval1',
                'subvar2' => 'subval2',
                'subvar55' => 'subval55',
            ],
            'module' => 'Test',
        ],
    ],
    'dashlets' =>
     [
        'Stream' =>
         [
            'module' => false,
        ],
        'Calendar' =>
         [
            'module' => 'Crm',
        ],
        'Cases' =>
         [
            'module' => 'Crm',
        ],
        'Leads' =>
         [
            'module' => 'Crm',
        ],
        'Opportunities' =>
         [
            'module' => 'Crm',
        ],
        'OpportunitiesByLeadSource' =>
         [
            'module' => 'Crm',
        ],
        'OpportunitiesByStage' =>
         [
            'module' => 'Crm',
        ],
        'SalesByMonth' =>
         [
            'module' => 'Crm',
        ],
        'SalesPipeline' =>
         [
            'module' => 'Crm',
        ],
        'Tasks' =>
         [
            'module' => 'Crm',
        ],
    ],
    'entityDefs' =>
     [
        'Attachment' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'type' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 36,
                ],
                'size' =>
                 [
                    'type' => 'int',
                    'min' => 0,
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'foreign' => 'attachments',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
            ],
        ],
        'Currency' =>
         [
            'fields' =>
             [
                'rate' =>
                 [
                    'type' => 'float',
                ],
            ],
        ],
        'Email' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'subject' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'db' => false,
                ],
                'fromName' =>
                 [
                    'type' => 'varchar',
                ],
                'from' =>
                 [
                    'type' => 'varchar',
                    'db' => false,
                    'required' => true,
                ],
                'to' =>
                 [
                    'type' => 'varchar',
                    'db' => false,
                    'required' => true,
                ],
                'cc' =>
                 [
                    'type' => 'varchar',
                    'db' => false,
                ],
                'bcc' =>
                 [
                    'type' => 'varchar',
                    'db' => false,
                ],
                'bodyPlain' =>
                 [
                    'type' => 'text',
                    'readOnly' => true,
                ],
                'body' =>
                 [
                    'type' => 'text',
                    'view' => 'Fields.Wysiwyg',
                ],
                'isHtml' =>
                 [
                    'type' => 'bool',
                    'default' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Draft',
                        1 => 'Sending',
                        2 => 'Sent',
                        3 => 'Archived',
                    ],
                    'readOnly' => true,
                ],
                'attachments' =>
                 [
                    'type' => 'linkMultiple',
                    'view' => 'Fields.AttachmentMultiple',
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'dateSent' =>
                 [
                    'type' => 'datetime',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'attachments' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'foreign' => 'parent',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'entities' =>
                     [
                        0 => 'Account',
                        1 => 'Opportunity',
                        2 => 'Case',
                    ],
                    'foreign' => 'emails',
                ],
                'fromEmailAddress' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'EmailAddress',
                ],
                'toEmailAddresses' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'EmailEmailAddress',
                    'conditions' =>
                     [
                        'addressType' => 'to',
                    ],
                    'additionalColumns' =>
                     [
                        'addressType' =>
                         [
                            'type' => 'varchar',
                            'len' => '4',
                        ],
                    ],
                ],
                'ccEmailAddresses' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'EmailEmailAddress',
                    'conditions' =>
                     [
                        'addressType' => 'cc',
                    ],
                    'additionalColumns' =>
                     [
                        'addressType' =>
                         [
                            'type' => 'varchar',
                            'len' => '4',
                        ],
                    ],
                ],
                'bccEmailAddresses' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'EmailEmailAddress',
                    'conditions' =>
                     [
                        'addressType' => 'bcc',
                    ],
                    'additionalColumns' =>
                     [
                        'addressType' =>
                         [
                            'type' => 'varchar',
                            'len' => '4',
                        ],
                    ],
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'dateSent',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'EmailAddress' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'lower' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'invalid' =>
                 [
                    'type' => 'bool',
                ],
                'optOut' =>
                 [
                    'type' => 'bool',
                ],
            ],
            'links' =>
             [
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'EmailTemplate' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'subject' =>
                 [
                    'type' => 'varchar',
                ],
                'body' =>
                 [
                    'type' => 'text',
                    'view' => 'Fields.Wysiwyg',
                ],
                'isHtml' =>
                 [
                    'type' => 'bool',
                    'default' => true,
                ],
                'attachments' =>
                 [
                    'type' => 'linkMultiple',
                    'view' => 'Fields.AttachmentMultiple',
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'attachments' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'foreign' => 'parent',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'Job' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Pending',
                        1 => 'Running',
                        2 => 'Success',
                        3 => 'Failed',
                    ],
                    'default' => 'Pending',
                ],
                'executeTime' =>
                 [
                    'type' => 'datetime',
                    'required' => true,
                ],
                'serviceName' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'len' => 100,
                ],
                'method' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'len' => 100,
                ],
                'data' =>
                 [
                    'type' => 'text',
                ],
                'scheduledJob' =>
                 [
                    'type' => 'link',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'scheduledJob' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'ScheduledJob',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
            ],
        ],
        'Note' =>
         [
            'fields' =>
             [
                'message' =>
                 [
                    'type' => 'text',
                ],
                'data' =>
                 [
                    'type' => 'text',
                ],
                'type' =>
                 [
                    'type' => 'varchar',
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'attachments' =>
                 [
                    'type' => 'linkMultiple',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'attachments' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'foreign' => 'parent',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'foreign' => 'notes',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
            ],
        ],
        'Notification' =>
         [
            'fields' =>
             [
                'data' =>
                 [
                    'type' => 'text',
                ],
                'type' =>
                 [
                    'type' => 'varchar',
                ],
                'read' =>
                 [
                    'type' => 'bool',
                ],
                'user' =>
                 [
                    'type' => 'link',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'user' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
            ],
        ],
        'OutboundEmail' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'server' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'port' =>
                 [
                    'type' => 'int',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25,
                ],
                'auth' =>
                 [
                    'type' => 'bool',
                    'default' => true,
                ],
                'security' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS',
                    ],
                ],
                'username' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'password' =>
                 [
                    'type' => 'password',
                ],
                'fromName' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'fromAddress' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'user' =>
                 [
                    'type' => 'link',
                ],
            ],
            'links' =>
             [
                'user' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
            ],
        ],
        'Preferences' =>
         [
            'fields' =>
             [
                'timeZone' =>
                 [
                    'type' => 'enum',
                    'detault' => 'UTC',
                ],
                'dateFormat' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'MM/DD/YYYY',
                        1 => 'YYYY-MM-DD',
                        2 => 'DD.MM.YYYY',
                    ],
                    'default' => 'MM/DD/YYYY',
                ],
                'timeFormat' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'HH:mm',
                        1 => 'hh:mm A',
                        2 => 'hh:mm a',
                    ],
                    'default' => 'HH:mm',
                ],
                'weekStart' =>
                 [
                    'type' => 'enumInt',
                    'options' =>
                     [
                        0 => 0,
                        1 => 1,
                    ],
                    'default' => 0,
                ],
                'thousandSeparator' =>
                 [
                    'type' => 'varchar',
                    'default' => ',',
                ],
                'decimalMark' =>
                 [
                    'type' => 'varchar',
                    'default' => '.',
                    'required' => true,
                ],
                'defaultCurrency' =>
                 [
                    'type' => 'enum',
                    'default' => 'USD',
                ],
                'dashboardLayout' =>
                 [
                    'type' => 'text',
                ],
                'dashletOptions' =>
                 [
                    'type' => 'text',
                ],
                'smtpServer' =>
                 [
                    'type' => 'varchar',
                ],
                'smtpPort' =>
                 [
                    'type' => 'int',
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25,
                ],
                'smtpAuth' =>
                 [
                    'type' => 'bool',
                    'default' => false,
                ],
                'smtpSecurity' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS',
                    ],
                ],
                'smtpUsername' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'smtpPassword' =>
                 [
                    'type' => 'password',
                ],
            ],
        ],
        'Role' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'maxLength' => 150,
                    'required' => true,
                    'type' => 'varchar',
                ],
                'data' =>
                 [
                    'type' => 'text',
                ],
            ],
            'links' =>
             [
                'users' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'roles',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'roles',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'ScheduledJob' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'job' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Active',
                        1 => 'Inactive',
                    ],
                ],
                'scheduling' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'lastRun' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'log' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'ScheduledJobLogRecord',
                    'foreign' => 'scheduledJob',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'ScheduledJobLogRecord' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'readOnly' => true,
                ],
                'status' =>
                 [
                    'type' => 'varchar',
                    'readOnly' => true,
                ],
                'executionTime' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'scheduledJob' =>
                 [
                    'type' => 'link',
                ],
            ],
            'links' =>
             [
                'scheduledJob' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'ScheduledJob',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'executionTime',
                'asc' => false,
            ],
        ],
        'Settings' =>
         [
            'fields' =>
             [
                'useCache' =>
                 [
                    'type' => 'bool',
                    'default' => true,
                ],
                'recordsPerPage' =>
                 [
                    'type' => 'int',
                    'minValue' => 1,
                    'maxValue' => 1000,
                    'default' => 20,
                    'required' => true,
                ],
                'recordsPerPageSmall' =>
                 [
                    'type' => 'int',
                    'minValue' => 1,
                    'maxValue' => 100,
                    'default' => 10,
                    'required' => true,
                ],
                'timeZone' =>
                 [
                    'type' => 'enum',
                    'detault' => 'UTC',
                    'options' =>
                     [
                        0 => 'UTC',
                        1 => 'Africa/Abidjan',
                        2 => 'Africa/Accra',
                        3 => 'Africa/Addis_Ababa',
                        4 => 'Africa/Algiers',
                        5 => 'Africa/Asmara',
                        6 => 'Africa/Bamako',
                        7 => 'Africa/Bangui',
                        8 => 'Africa/Banjul',
                        9 => 'Africa/Bissau',
                        10 => 'Africa/Blantyre',
                        11 => 'Africa/Brazzaville',
                        12 => 'Africa/Bujumbura',
                        13 => 'Africa/Cairo',
                        14 => 'Africa/Casablanca',
                        15 => 'Africa/Ceuta',
                        16 => 'Africa/Conakry',
                        17 => 'Africa/Dakar',
                        18 => 'Africa/Dar_es_Salaam',
                        19 => 'Africa/Djibouti',
                        20 => 'Africa/Douala',
                        21 => 'Africa/El_Aaiun',
                        22 => 'Africa/Freetown',
                        23 => 'Africa/Gaborone',
                        24 => 'Africa/Harare',
                        25 => 'Africa/Johannesburg',
                        26 => 'Africa/Juba',
                        27 => 'Africa/Kampala',
                        28 => 'Africa/Khartoum',
                        29 => 'Africa/Kigali',
                        30 => 'Africa/Kinshasa',
                        31 => 'Africa/Lagos',
                        32 => 'Africa/Libreville',
                        33 => 'Africa/Lome',
                        34 => 'Africa/Luanda',
                        35 => 'Africa/Lubumbashi',
                        36 => 'Africa/Lusaka',
                        37 => 'Africa/Malabo',
                        38 => 'Africa/Maputo',
                        39 => 'Africa/Maseru',
                        40 => 'Africa/Mbabane',
                        41 => 'Africa/Mogadishu',
                        42 => 'Africa/Monrovia',
                        43 => 'Africa/Nairobi',
                        44 => 'Africa/Ndjamena',
                        45 => 'Africa/Niamey',
                        46 => 'Africa/Nouakchott',
                        47 => 'Africa/Ouagadougou',
                        48 => 'Africa/Porto-Novo',
                        49 => 'Africa/Sao_Tome',
                        50 => 'Africa/Tripoli',
                        51 => 'Africa/Tunis',
                        52 => 'Africa/Windhoek',
                        53 => 'America/Adak',
                        54 => 'America/Anchorage',
                        55 => 'America/Anguilla',
                        56 => 'America/Antigua',
                        57 => 'America/Araguaina',
                        58 => 'America/Argentina/Buenos_Aires',
                        59 => 'America/Argentina/Catamarca',
                        60 => 'America/Argentina/Cordoba',
                        61 => 'America/Argentina/Jujuy',
                        62 => 'America/Argentina/La_Rioja',
                        63 => 'America/Argentina/Mendoza',
                        64 => 'America/Argentina/Rio_Gallegos',
                        65 => 'America/Argentina/Salta',
                        66 => 'America/Argentina/San_Juan',
                        67 => 'America/Argentina/San_Luis',
                        68 => 'America/Argentina/Tucuman',
                        69 => 'America/Argentina/Ushuaia',
                        70 => 'America/Aruba',
                        71 => 'America/Asuncion',
                        72 => 'America/Atikokan',
                        73 => 'America/Bahia',
                        74 => 'America/Bahia_Banderas',
                        75 => 'America/Barbados',
                        76 => 'America/Belem',
                        77 => 'America/Belize',
                        78 => 'America/Blanc-Sablon',
                        79 => 'America/Boa_Vista',
                        80 => 'America/Bogota',
                        81 => 'America/Boise',
                        82 => 'America/Cambridge_Bay',
                        83 => 'America/Campo_Grande',
                        84 => 'America/Cancun',
                        85 => 'America/Caracas',
                        86 => 'America/Cayenne',
                        87 => 'America/Cayman',
                        88 => 'America/Chicago',
                        89 => 'America/Chihuahua',
                        90 => 'America/Costa_Rica',
                        91 => 'America/Creston',
                        92 => 'America/Cuiaba',
                        93 => 'America/Curacao',
                        94 => 'America/Danmarkshavn',
                        95 => 'America/Dawson',
                        96 => 'America/Dawson_Creek',
                        97 => 'America/Denver',
                        98 => 'America/Detroit',
                        99 => 'America/Dominica',
                        100 => 'America/Edmonton',
                        101 => 'America/Eirunepe',
                        102 => 'America/El_Salvador',
                        103 => 'America/Fortaleza',
                        104 => 'America/Glace_Bay',
                        105 => 'America/Godthab',
                        106 => 'America/Goose_Bay',
                        107 => 'America/Grand_Turk',
                        108 => 'America/Grenada',
                        109 => 'America/Guadeloupe',
                        110 => 'America/Guatemala',
                        111 => 'America/Guayaquil',
                        112 => 'America/Guyana',
                        113 => 'America/Halifax',
                        114 => 'America/Havana',
                        115 => 'America/Hermosillo',
                        116 => 'America/Indiana/Indianapolis',
                        117 => 'America/Indiana/Knox',
                        118 => 'America/Indiana/Marengo',
                        119 => 'America/Indiana/Petersburg',
                        120 => 'America/Indiana/Tell_City',
                        121 => 'America/Indiana/Vevay',
                        122 => 'America/Indiana/Vincennes',
                        123 => 'America/Indiana/Winamac',
                        124 => 'America/Inuvik',
                        125 => 'America/Iqaluit',
                        126 => 'America/Jamaica',
                        127 => 'America/Juneau',
                        128 => 'America/Kentucky/Louisville',
                        129 => 'America/Kentucky/Monticello',
                        130 => 'America/Kralendijk',
                        131 => 'America/La_Paz',
                        132 => 'America/Lima',
                        133 => 'America/Los_Angeles',
                        134 => 'America/Lower_Princes',
                        135 => 'America/Maceio',
                        136 => 'America/Managua',
                        137 => 'America/Manaus',
                        138 => 'America/Marigot',
                        139 => 'America/Martinique',
                        140 => 'America/Matamoros',
                        141 => 'America/Mazatlan',
                        142 => 'America/Menominee',
                        143 => 'America/Merida',
                        144 => 'America/Metlakatla',
                        145 => 'America/Mexico_City',
                        146 => 'America/Miquelon',
                        147 => 'America/Moncton',
                        148 => 'America/Monterrey',
                        149 => 'America/Montevideo',
                        150 => 'America/Montserrat',
                        151 => 'America/Nassau',
                        152 => 'America/New_York',
                        153 => 'America/Nipigon',
                        154 => 'America/Nome',
                        155 => 'America/Noronha',
                        156 => 'America/North_Dakota/Beulah',
                        157 => 'America/North_Dakota/Center',
                        158 => 'America/North_Dakota/New_Salem',
                        159 => 'America/Ojinaga',
                        160 => 'America/Panama',
                        161 => 'America/Pangnirtung',
                        162 => 'America/Paramaribo',
                        163 => 'America/Phoenix',
                        164 => 'America/Port-au-Prince',
                        165 => 'America/Port_of_Spain',
                        166 => 'America/Porto_Velho',
                        167 => 'America/Puerto_Rico',
                        168 => 'America/Rainy_River',
                        169 => 'America/Rankin_Inlet',
                        170 => 'America/Recife',
                        171 => 'America/Regina',
                        172 => 'America/Resolute',
                        173 => 'America/Rio_Branco',
                        174 => 'America/Santa_Isabel',
                        175 => 'America/Santarem',
                        176 => 'America/Santiago',
                        177 => 'America/Santo_Domingo',
                        178 => 'America/Sao_Paulo',
                        179 => 'America/Scoresbysund',
                        180 => 'America/Sitka',
                        181 => 'America/St_Barthelemy',
                        182 => 'America/St_Johns',
                        183 => 'America/St_Kitts',
                        184 => 'America/St_Lucia',
                        185 => 'America/St_Thomas',
                        186 => 'America/St_Vincent',
                        187 => 'America/Swift_Current',
                        188 => 'America/Tegucigalpa',
                        189 => 'America/Thule',
                        190 => 'America/Thunder_Bay',
                        191 => 'America/Tijuana',
                        192 => 'America/Toronto',
                        193 => 'America/Tortola',
                        194 => 'America/Vancouver',
                        195 => 'America/Whitehorse',
                        196 => 'America/Winnipeg',
                        197 => 'America/Yakutat',
                        198 => 'America/Yellowknife',
                        199 => 'Antarctica/Casey',
                        200 => 'Antarctica/Davis',
                        201 => 'Antarctica/DumontDUrville',
                        202 => 'Antarctica/Macquarie',
                        203 => 'Antarctica/Mawson',
                        204 => 'Antarctica/McMurdo',
                        205 => 'Antarctica/Palmer',
                        206 => 'Antarctica/Rothera',
                        207 => 'Antarctica/Syowa',
                        208 => 'Antarctica/Vostok',
                        209 => 'Arctic/Longyearbyen',
                        210 => 'Asia/Aden',
                        211 => 'Asia/Almaty',
                        212 => 'Asia/Amman',
                        213 => 'Asia/Anadyr',
                        214 => 'Asia/Aqtau',
                        215 => 'Asia/Aqtobe',
                        216 => 'Asia/Ashgabat',
                        217 => 'Asia/Baghdad',
                        218 => 'Asia/Bahrain',
                        219 => 'Asia/Baku',
                        220 => 'Asia/Bangkok',
                        221 => 'Asia/Beirut',
                        222 => 'Asia/Bishkek',
                        223 => 'Asia/Brunei',
                        224 => 'Asia/Choibalsan',
                        225 => 'Asia/Chongqing',
                        226 => 'Asia/Colombo',
                        227 => 'Asia/Damascus',
                        228 => 'Asia/Dhaka',
                        229 => 'Asia/Dili',
                        230 => 'Asia/Dubai',
                        231 => 'Asia/Dushanbe',
                        232 => 'Asia/Gaza',
                        233 => 'Asia/Harbin',
                        234 => 'Asia/Hebron',
                        235 => 'Asia/Ho_Chi_Minh',
                        236 => 'Asia/Hong_Kong',
                        237 => 'Asia/Hovd',
                        238 => 'Asia/Irkutsk',
                        239 => 'Asia/Jakarta',
                        240 => 'Asia/Jayapura',
                        241 => 'Asia/Jerusalem',
                        242 => 'Asia/Kabul',
                        243 => 'Asia/Kamchatka',
                        244 => 'Asia/Karachi',
                        245 => 'Asia/Kashgar',
                        246 => 'Asia/Kathmandu',
                        247 => 'Asia/Khandyga',
                        248 => 'Asia/Kolkata',
                        249 => 'Asia/Krasnoyarsk',
                        250 => 'Asia/Kuala_Lumpur',
                        251 => 'Asia/Kuching',
                        252 => 'Asia/Kuwait',
                        253 => 'Asia/Macau',
                        254 => 'Asia/Magadan',
                        255 => 'Asia/Makassar',
                        256 => 'Asia/Manila',
                        257 => 'Asia/Muscat',
                        258 => 'Asia/Nicosia',
                        259 => 'Asia/Novokuznetsk',
                        260 => 'Asia/Novosibirsk',
                        261 => 'Asia/Omsk',
                        262 => 'Asia/Oral',
                        263 => 'Asia/Phnom_Penh',
                        264 => 'Asia/Pontianak',
                        265 => 'Asia/Pyongyang',
                        266 => 'Asia/Qatar',
                        267 => 'Asia/Qyzylorda',
                        268 => 'Asia/Rangoon',
                        269 => 'Asia/Riyadh',
                        270 => 'Asia/Sakhalin',
                        271 => 'Asia/Samarkand',
                        272 => 'Asia/Seoul',
                        273 => 'Asia/Shanghai',
                        274 => 'Asia/Singapore',
                        275 => 'Asia/Taipei',
                        276 => 'Asia/Tashkent',
                        277 => 'Asia/Tbilisi',
                        278 => 'Asia/Tehran',
                        279 => 'Asia/Thimphu',
                        280 => 'Asia/Tokyo',
                        281 => 'Asia/Ulaanbaatar',
                        282 => 'Asia/Urumqi',
                        283 => 'Asia/Ust-Nera',
                        284 => 'Asia/Vientiane',
                        285 => 'Asia/Vladivostok',
                        286 => 'Asia/Yakutsk',
                        287 => 'Asia/Yekaterinburg',
                        288 => 'Asia/Yerevan',
                        289 => 'Atlantic/Azores',
                        290 => 'Atlantic/Bermuda',
                        291 => 'Atlantic/Canary',
                        292 => 'Atlantic/Cape_Verde',
                        293 => 'Atlantic/Faroe',
                        294 => 'Atlantic/Madeira',
                        295 => 'Atlantic/Reykjavik',
                        296 => 'Atlantic/South_Georgia',
                        297 => 'Atlantic/St_Helena',
                        298 => 'Atlantic/Stanley',
                        299 => 'Australia/Adelaide',
                        300 => 'Australia/Brisbane',
                        301 => 'Australia/Broken_Hill',
                        302 => 'Australia/Currie',
                        303 => 'Australia/Darwin',
                        304 => 'Australia/Eucla',
                        305 => 'Australia/Hobart',
                        306 => 'Australia/Lindeman',
                        307 => 'Australia/Lord_Howe',
                        308 => 'Australia/Melbourne',
                        309 => 'Australia/Perth',
                        310 => 'Australia/Sydney',
                        311 => 'Europe/Amsterdam',
                        312 => 'Europe/Andorra',
                        313 => 'Europe/Athens',
                        314 => 'Europe/Belgrade',
                        315 => 'Europe/Berlin',
                        316 => 'Europe/Bratislava',
                        317 => 'Europe/Brussels',
                        318 => 'Europe/Bucharest',
                        319 => 'Europe/Budapest',
                        320 => 'Europe/Busingen',
                        321 => 'Europe/Chisinau',
                        322 => 'Europe/Copenhagen',
                        323 => 'Europe/Dublin',
                        324 => 'Europe/Gibraltar',
                        325 => 'Europe/Guernsey',
                        326 => 'Europe/Helsinki',
                        327 => 'Europe/Isle_of_Man',
                        328 => 'Europe/Istanbul',
                        329 => 'Europe/Jersey',
                        330 => 'Europe/Kaliningrad',
                        331 => 'Europe/Kiev',
                        332 => 'Europe/Lisbon',
                        333 => 'Europe/Ljubljana',
                        334 => 'Europe/London',
                        335 => 'Europe/Luxembourg',
                        336 => 'Europe/Madrid',
                        337 => 'Europe/Malta',
                        338 => 'Europe/Mariehamn',
                        339 => 'Europe/Minsk',
                        340 => 'Europe/Monaco',
                        341 => 'Europe/Moscow',
                        342 => 'Europe/Oslo',
                        343 => 'Europe/Paris',
                        344 => 'Europe/Podgorica',
                        345 => 'Europe/Prague',
                        346 => 'Europe/Riga',
                        347 => 'Europe/Rome',
                        348 => 'Europe/Samara',
                        349 => 'Europe/San_Marino',
                        350 => 'Europe/Sarajevo',
                        351 => 'Europe/Simferopol',
                        352 => 'Europe/Skopje',
                        353 => 'Europe/Sofia',
                        354 => 'Europe/Stockholm',
                        355 => 'Europe/Tallinn',
                        356 => 'Europe/Tirane',
                        357 => 'Europe/Uzhgorod',
                        358 => 'Europe/Vaduz',
                        359 => 'Europe/Vatican',
                        360 => 'Europe/Vienna',
                        361 => 'Europe/Vilnius',
                        362 => 'Europe/Volgograd',
                        363 => 'Europe/Warsaw',
                        364 => 'Europe/Zagreb',
                        365 => 'Europe/Zaporozhye',
                        366 => 'Europe/Zurich',
                        367 => 'Indian/Antananarivo',
                        368 => 'Indian/Chagos',
                        369 => 'Indian/Christmas',
                        370 => 'Indian/Cocos',
                        371 => 'Indian/Comoro',
                        372 => 'Indian/Kerguelen',
                        373 => 'Indian/Mahe',
                        374 => 'Indian/Maldives',
                        375 => 'Indian/Mauritius',
                        376 => 'Indian/Mayotte',
                        377 => 'Indian/Reunion',
                        378 => 'Pacific/Apia',
                        379 => 'Pacific/Auckland',
                        380 => 'Pacific/Chatham',
                        381 => 'Pacific/Chuuk',
                        382 => 'Pacific/Easter',
                        383 => 'Pacific/Efate',
                        384 => 'Pacific/Enderbury',
                        385 => 'Pacific/Fakaofo',
                        386 => 'Pacific/Fiji',
                        387 => 'Pacific/Funafuti',
                        388 => 'Pacific/Galapagos',
                        389 => 'Pacific/Gambier',
                        390 => 'Pacific/Guadalcanal',
                        391 => 'Pacific/Guam',
                        392 => 'Pacific/Honolulu',
                        393 => 'Pacific/Johnston',
                        394 => 'Pacific/Kiritimati',
                        395 => 'Pacific/Kosrae',
                        396 => 'Pacific/Kwajalein',
                        397 => 'Pacific/Majuro',
                        398 => 'Pacific/Marquesas',
                        399 => 'Pacific/Midway',
                        400 => 'Pacific/Nauru',
                        401 => 'Pacific/Niue',
                        402 => 'Pacific/Norfolk',
                        403 => 'Pacific/Noumea',
                        404 => 'Pacific/Pago_Pago',
                        405 => 'Pacific/Palau',
                        406 => 'Pacific/Pitcairn',
                        407 => 'Pacific/Pohnpei',
                        408 => 'Pacific/Port_Moresby',
                        409 => 'Pacific/Rarotonga',
                        410 => 'Pacific/Saipan',
                        411 => 'Pacific/Tahiti',
                        412 => 'Pacific/Tarawa',
                        413 => 'Pacific/Tongatapu',
                        414 => 'Pacific/Wake',
                        415 => 'Pacific/Wallis',
                    ],
                ],
                'dateFormat' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'MM/DD/YYYY',
                        1 => 'YYYY-MM-DD',
                        2 => 'DD.MM.YYYY',
                    ],
                    'default' => 'MM/DD/YYYY',
                ],
                'timeFormat' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'HH:mm',
                        1 => 'hh:mm A',
                        2 => 'hh:mm a',
                    ],
                    'default' => 'HH:mm',
                ],
                'weekStart' =>
                 [
                    'type' => 'enumInt',
                    'options' =>
                     [
                        0 => 0,
                        1 => 1,
                    ],
                    'default' => 0,
                ],
                'thousandSeparator' =>
                 [
                    'type' => 'varchar',
                    'default' => ',',
                ],
                'decimalMark' =>
                 [
                    'type' => 'varchar',
                    'default' => '.',
                    'required' => true,
                ],
                'currencyList' =>
                 [
                    'type' => 'array',
                    'default' =>
                     [
                        0 => 'USD',
                        1 => 'EUR',
                    ],
                    'options' =>
                     [
                        0 => 'AED',
                        1 => 'ANG',
                        2 => 'ARS',
                        3 => 'AUD',
                        4 => 'BGN',
                        5 => 'BHD',
                        6 => 'BND',
                        7 => 'BOB',
                        8 => 'BRL',
                        9 => 'BWP',
                        10 => 'CAD',
                        11 => 'CHF',
                        12 => 'CLP',
                        13 => 'CNY',
                        14 => 'COP',
                        15 => 'CRC',
                        16 => 'CZK',
                        17 => 'DKK',
                        18 => 'DOP',
                        19 => 'DZD',
                        20 => 'EEK',
                        21 => 'EGP',
                        22 => 'EUR',
                        23 => 'FJD',
                        24 => 'GBP',
                        25 => 'HKD',
                        26 => 'HNL',
                        27 => 'HRK',
                        28 => 'HUF',
                        29 => 'IDR',
                        30 => 'ILS',
                        31 => 'INR',
                        32 => 'JMD',
                        33 => 'JOD',
                        34 => 'JPY',
                        35 => 'KES',
                        36 => 'KRW',
                        37 => 'KWD',
                        38 => 'KYD',
                        39 => 'KZT',
                        40 => 'LBP',
                        41 => 'LKR',
                        42 => 'LTL',
                        43 => 'LVL',
                        44 => 'MAD',
                        45 => 'MDL',
                        46 => 'MKD',
                        47 => 'MUR',
                        48 => 'MXN',
                        49 => 'MYR',
                        50 => 'NAD',
                        51 => 'NGN',
                        52 => 'NIO',
                        53 => 'NOK',
                        54 => 'NPR',
                        55 => 'NZD',
                        56 => 'OMR',
                        57 => 'PEN',
                        58 => 'PGK',
                        59 => 'PHP',
                        60 => 'PKR',
                        61 => 'PLN',
                        62 => 'PYG',
                        63 => 'QAR',
                        64 => 'RON',
                        65 => 'RSD',
                        66 => 'RUB',
                        67 => 'SAR',
                        68 => 'SCR',
                        69 => 'SEK',
                        70 => 'SGD',
                        71 => 'SKK',
                        72 => 'SLL',
                        73 => 'SVC',
                        74 => 'THB',
                        75 => 'TND',
                        76 => 'TRY',
                        77 => 'TTD',
                        78 => 'TWD',
                        79 => 'TZS',
                        80 => 'UAH',
                        81 => 'UGX',
                        82 => 'USD',
                        83 => 'UYU',
                        84 => 'UZS',
                        85 => 'VND',
                        86 => 'YER',
                        87 => 'ZAR',
                        88 => 'ZMK',
                    ],
                    'required' => true,
                ],
                'defaultCurrency' =>
                 [
                    'type' => 'enum',
                    'default' => 'USD',
                    'required' => true,
                ],
                'outboundEmailIsShared' =>
                 [
                    'type' => 'bool',
                    'default' => false,
                ],
                'outboundEmailFromName' =>
                 [
                    'type' => 'varchar',
                    'default' => 'EspoCRM',
                    'required' => true,
                ],
                'outboundEmailFromAddress' =>
                 [
                    'type' => 'varchar',
                    'default' => 'crm@example.com',
                    'required' => true,
                ],
                'smtpServer' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'smtpPort' =>
                 [
                    'type' => 'int',
                    'required' => true,
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25,
                ],
                'smtpAuth' =>
                 [
                    'type' => 'bool',
                    'default' => true,
                ],
                'smtpSecurity' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS',
                    ],
                ],
                'smtpUsername' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'smtpPassword' =>
                 [
                    'type' => 'password',
                ],
                'tabList' =>
                 [
                    'type' => 'array',
                    'default' =>
                     [
                        0 => 'Account',
                        1 => 'Contact',
                        2 => 'Lead',
                        3 => 'Opportunity',
                        4 => 'Calendar',
                        5 => 'Meeting',
                        6 => 'Call',
                        7 => 'Task',
                        8 => 'Case',
                        9 => 'Prospect',
                    ],
                    'translation' => 'App.scopeNamesPlural',
                ],
                'quickCreateList' =>
                 [
                    'type' => 'array',
                    'default' =>
                     [
                        0 => 'Account',
                        1 => 'Contact',
                        2 => 'Lead',
                        3 => 'Opportunity',
                        4 => 'Meeting',
                        5 => 'Call',
                        6 => 'Task',
                        7 => 'Case',
                        8 => 'Prospect',
                    ],
                    'translation' => 'App.scopeNames',
                ],
            ],
        ],
        'Team' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'roles' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'users' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'teams',
                ],
                'roles' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Role',
                    'foreign' => 'teams',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'User' =>
         [
            'fields' =>
             [
                'isAdmin' =>
                 [
                    'type' => 'bool',
                ],
                'userName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 50,
                    'required' => true,
                    'unique' => true,
                ],
                'name' =>
                 [
                    'type' => 'personName',
                ],
                'password' =>
                 [
                    'type' => 'password',
                ],
                'salutationName' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Mrs.',
                        3 => 'Dr.',
                        4 => 'Drs.',
                    ],
                ],
                'firstName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'lastName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'required' => true,
                ],
                'title' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'emailAddress' =>
                 [
                    'type' => 'email',
                    'required' => false,
                ],
                'phone' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'defaultTeam' =>
                 [
                    'type' => 'link',
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
                'roles' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'defaultTeam' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Team',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'users',
                ],
                'roles' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Role',
                    'foreign' => 'users',
                ],
                'preferences' =>
                 [
                    'type' => 'hasOne',
                    'entity' => 'Preferences',
                ],
                'meetings' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'users',
                ],
                'calls' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'users',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'userName',
                'asc' => true,
            ],
        ],
        'Account' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'website' =>
                 [
                    'type' => 'url',
                ],
                'emailAddress' =>
                 [
                    'type' => 'email',
                ],
                'phone' =>
                 [
                    'type' => 'phone',
                ],
                'fax' =>
                 [
                    'type' => 'phone',
                ],
                'type' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Customer',
                        2 => 'Investor',
                        3 => 'Partner',
                        4 => 'Reseller',
                    ],
                ],
                'industry' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Apparel',
                        2 => 'Banking',
                        3 => 'Education',
                        4 => 'Electronics',
                        5 => 'Finance',
                        6 => 'Insurance',
                        7 => 'IT',
                    ],
                ],
                'sicCode' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 40,
                ],
                'billingAddress' =>
                 [
                    'type' => 'address',
                ],
                'billingAddressStreet' =>
                 [
                    'type' => 'varchar',
                ],
                'billingAddressCity' =>
                 [
                    'type' => 'varchar',
                ],
                'billingAddressState' =>
                 [
                    'type' => 'varchar',
                ],
                'billingAddressCountry' =>
                 [
                    'type' => 'varchar',
                ],
                'billingAddressPostalCode' =>
                 [
                    'type' => 'varchar',
                ],
                'shippingAddress' =>
                 [
                    'type' => 'address',
                ],
                'shippingAddressStreet' =>
                 [
                    'type' => 'varchar',
                ],
                'shippingAddressCity' =>
                 [
                    'type' => 'varchar',
                ],
                'shippingAddressState' =>
                 [
                    'type' => 'varchar',
                ],
                'shippingAddressCountry' =>
                 [
                    'type' => 'varchar',
                ],
                'shippingAddressPostalCode' =>
                 [
                    'type' => 'varchar',
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'contacts' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'account',
                ],
                'opportunities' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'account',
                ],
                'cases' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'account',
                ],
                'meetings' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                ],
                'calls' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                ],
                'tasks' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                ],
                'emails' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'Call' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Planned',
                        1 => 'Held',
                        2 => 'Not Held',
                    ],
                    'default' => 'Planned',
                    'view' => 'Fields.EnumStyled',
                    'style' =>
                     [
                        'Held' => 'success',
                        'Not Held' => 'danger',
                    ],
                ],
                'dateStart' =>
                 [
                    'type' => 'datetime',
                    'required' => true,
                    'default' => 'javascript: return this.dateTime.getNow(15);',
                ],
                'dateEnd' =>
                 [
                    'type' => 'datetime',
                    'required' => true,
                    'after' => 'dateStart',
                ],
                'duration' =>
                 [
                    'type' => 'duration',
                    'start' => 'dateStart',
                    'end' => 'dateEnd',
                    'options' =>
                     [
                        0 => 300,
                        1 => 600,
                        2 => 900,
                        3 => 1800,
                        4 => 2700,
                        5 => 3600,
                        6 => 7200,
                    ],
                    'default' => 300,
                ],
                'direction' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Outbound',
                        1 => 'Inbound',
                    ],
                    'default' => 'Outbound',
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'users' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'contacts' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'leads' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'users' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'calls',
                ],
                'contacts' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'calls',
                ],
                'leads' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'calls',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'entities' =>
                     [
                        0 => 'Account',
                        1 => 'Opportunity',
                        2 => 'Case',
                    ],
                    'foreign' => 'calls',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'dateStart',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'Case' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'number' =>
                 [
                    'type' => 'autoincrement',
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'New',
                        1 => 'Assigned',
                        2 => 'Pending',
                        3 => 'Closed',
                        4 => 'Rejected',
                        5 => 'Duplicate',
                    ],
                    'default' => 'New',
                    'view' => 'Fields.EnumStyled',
                    'style' =>
                     [
                        'Closed' => 'success',
                        'Duplicate' => 'danger',
                        'Rejected' => 'danger',
                    ],
                ],
                'priority' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Low',
                        1 => 'Normal',
                        2 => 'High',
                        3 => 'Urgent',
                    ],
                    'default' => 'Normal',
                    'audited' => true,
                ],
                'type' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Question',
                        2 => 'Incident',
                        3 => 'Problem',
                    ],
                    'audited' => true,
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'account' =>
                 [
                    'type' => 'link',
                ],
                'contact' =>
                 [
                    'type' => 'link',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'account' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                    'foreign' => 'cases',
                ],
                'contact' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Contact',
                    'foreign' => 'cases',
                ],
                'meetings' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                ],
                'calls' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                ],
                'tasks' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                ],
                'emails' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'number',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                    1 => 'open',
                ],
            ],
        ],
        'Contact' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'personName',
                ],
                'salutationName' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Mrs.',
                        3 => 'Dr.',
                        4 => 'Drs.',
                    ],
                ],
                'firstName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'lastName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'required' => true,
                ],
                'title' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'emailAddress' =>
                 [
                    'type' => 'email',
                ],
                'phone' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'fax' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'doNotCall' =>
                 [
                    'type' => 'bool',
                ],
                'phoneOffice' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'address' =>
                 [
                    'type' => 'address',
                ],
                'addressStreet' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCity' =>
                 [
                    'type' => 'varchar',
                ],
                'addressState' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCountry' =>
                 [
                    'type' => 'varchar',
                ],
                'addressPostalCode' =>
                 [
                    'type' => 'varchar',
                ],
                'account' =>
                 [
                    'type' => 'link',
                ],
                'accountType' =>
                 [
                    'type' => 'foreign',
                    'link' => 'account',
                    'field' => 'type',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'account' =>
                 [
                    'type' => 'belongsTo',
                    'jointTable' => true,
                    'entity' => 'Account',
                ],
                'opportunities' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'contacts',
                ],
                'cases' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'contact',
                ],
                'meetings' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'contacts',
                ],
                'calls' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'contacts',
                ],
                'tasks' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                ],
                'activities' =>
                 [
                    'type' => 'joint',
                    'links' =>
                     [
                        0 => 'meetings',
                        1 => 'calls',
                        2 => 'tasks',
                    ],
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'InboundEmail' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Active',
                        1 => 'Inactive',
                    ],
                ],
                'host' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'port' =>
                 [
                    'type' => 'varchar',
                    'default' => '143',
                    'required' => true,
                ],
                'username' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'password' =>
                 [
                    'type' => 'password',
                ],
                'monitoredFolders' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'default' => 'INBOX',
                ],
                'trashFolder' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                    'default' => 'INBOX.Trash',
                ],
                'assignToUser' =>
                 [
                    'type' => 'link',
                ],
                'team' =>
                 [
                    'type' => 'link',
                ],
                'createCase' =>
                 [
                    'type' => 'bool',
                ],
                'caseDistribution' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Direct-Assignment',
                        1 => 'Round-Robin',
                        2 => 'Least-Busy',
                    ],
                    'default' => 'Direct-Assignment',
                ],
                'reply' =>
                 [
                    'type' => 'bool',
                ],
                'replyEmailTemplate' =>
                 [
                    'type' => 'link',
                ],
                'replyFromAddress' =>
                 [
                    'type' => 'varchar',
                ],
                'replyFromName' =>
                 [
                    'type' => 'varchar',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignToUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'team' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Team',
                ],
                'replyEmailTemplate' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'EmailTemplate',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'name',
                'asc' => true,
            ],
        ],
        'Lead' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'personName',
                ],
                'salutationName' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Mrs.',
                        3 => 'Dr.',
                        4 => 'Drs.',
                    ],
                ],
                'firstName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'lastName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'required' => true,
                ],
                'title' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'New',
                        1 => 'Assigned',
                        2 => 'In Process',
                        3 => 'Converted',
                        4 => 'Recycled',
                        5 => 'Dead',
                    ],
                    'default' => 'New',
                    'view' => 'Fields.EnumStyled',
                    'style' =>
                     [
                        'Converted' => 'success',
                        'Recycled' => 'danger',
                        'Dead' => 'danger',
                    ],
                ],
                'source' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Call',
                        2 => 'Email',
                        3 => 'Existing Customer',
                        4 => 'Partner',
                        5 => 'Public Relations',
                        6 => 'Web Site',
                        7 => 'Campaign',
                        8 => 'Other',
                    ],
                ],
                'opportunityAmount' =>
                 [
                    'type' => 'currency',
                    'audited' => true,
                ],
                'website' =>
                 [
                    'type' => 'url',
                ],
                'address' =>
                 [
                    'type' => 'address',
                ],
                'addressStreet' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCity' =>
                 [
                    'type' => 'varchar',
                ],
                'addressState' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCountry' =>
                 [
                    'type' => 'varchar',
                ],
                'addressPostalCode' =>
                 [
                    'type' => 'varchar',
                ],
                'emailAddress' =>
                 [
                    'type' => 'email',
                ],
                'phone' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'fax' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'phoneOffice' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'doNotCall' =>
                 [
                    'type' => 'bool',
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'accountName' =>
                 [
                    'type' => 'varchar',
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
                'createdAccount' =>
                 [
                    'type' => 'link',
                    'disabled' => true,
                    'readOnly' => true,
                ],
                'createdContact' =>
                 [
                    'type' => 'link',
                    'disabled' => true,
                    'readOnly' => true,
                ],
                'createdOpportunity' =>
                 [
                    'type' => 'link',
                    'disabled' => true,
                    'readOnly' => true,
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'opportunities' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'leads',
                ],
                'meetings' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'leads',
                ],
                'calls' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'leads',
                ],
                'tasks' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                ],
                'createdAccount' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                ],
                'createdContact' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Contact',
                ],
                'createdOpportunity' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Opportunity',
                ],
            ],
            'convertFields' =>
             [
                'Contact' =>
                 [
                    'name' => 'name',
                    'title' => 'title',
                    'emailAddress' => 'emailAddress',
                    'phone' => 'phone',
                    'address' => 'address',
                    'assignedUser' => 'assignedUser',
                    'teams' => 'teams',
                ],
                'Account' =>
                 [
                    'name' => 'accountName',
                    'website' => 'website',
                    'emailAddress' => 'emailAddress',
                    'phone' => 'phoneOffice',
                    'assignedUser' => 'assignedUser',
                    'teams' => 'teams',
                ],
                'Opportunity' =>
                 [
                    'amount' => 'opportunityAmount',
                    'leadSource' => 'source',
                    'assignedUser' => 'assignedUser',
                    'teams' => 'teams',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'Meeting' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Planned',
                        1 => 'Held',
                        2 => 'Not Held',
                    ],
                    'default' => 'Planned',
                    'view' => 'Fields.EnumStyled',
                    'style' =>
                     [
                        'Held' => 'success',
                        'Not Held' => 'danger',
                    ],
                ],
                'dateStart' =>
                 [
                    'type' => 'datetime',
                    'required' => true,
                    'default' => 'javascript: return this.dateTime.getNow(15);',
                ],
                'dateEnd' =>
                 [
                    'type' => 'datetime',
                    'required' => true,
                    'after' => 'dateStart',
                ],
                'duration' =>
                 [
                    'type' => 'duration',
                    'start' => 'dateStart',
                    'end' => 'dateEnd',
                    'options' =>
                     [
                        0 => 0,
                        1 => 900,
                        2 => 1800,
                        3 => 3600,
                        4 => 7200,
                        5 => 10800,
                        6 => 86400,
                    ],
                    'default' => 3600,
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'users' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'contacts' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'leads' =>
                 [
                    'type' => 'linkMultiple',
                    'disabled' => true,
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'users' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'meetings',
                ],
                'contacts' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'meetings',
                ],
                'leads' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'meetings',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'entities' =>
                     [
                        0 => 'Account',
                        1 => 'Opportunity',
                        2 => 'Case',
                    ],
                    'foreign' => 'meetings',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'dateStart',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'Opportunity' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'amount' =>
                 [
                    'type' => 'currency',
                    'required' => true,
                    'audited' => true,
                ],
                'account' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'stage' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Prospecting',
                        1 => 'Qualification',
                        2 => 'Needs Analysis',
                        3 => 'Value Proposition',
                        4 => 'Id. Decision Makers',
                        5 => 'Perception Analysis',
                        6 => 'Proposal/Price Quote',
                        7 => 'Negotiation/Review',
                        8 => 'Closed Won',
                        9 => 'Closed Lost',
                    ],
                    'view' => 'Crm:Opportunity.Fields.Stage',
                    'default' => 'Prospecting',
                ],
                'probability' =>
                 [
                    'type' => 'int',
                    'required' => true,
                    'min' => 0,
                    'max' => 100,
                ],
                'leadSource' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Other',
                        1 => 'Call',
                        2 => 'Email',
                        3 => 'Existing Customer',
                        4 => 'Partner',
                        5 => 'Public Relations',
                        6 => 'Web Site',
                        7 => 'Campaign',
                    ],
                ],
                'closeDate' =>
                 [
                    'type' => 'date',
                    'required' => true,
                    'audited' => true,
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'account' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                    'foreign' => 'opportunities',
                ],
                'contacts' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'opportunities',
                ],
                'meetings' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                ],
                'calls' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                ],
                'tasks' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                ],
                'emails' =>
                 [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                    1 => 'open',
                ],
            ],
        ],
        'Prospect' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'personName',
                ],
                'salutationName' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Mrs.',
                        3 => 'Dr.',
                        4 => 'Drs.',
                    ],
                ],
                'firstName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'lastName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'required' => true,
                ],
                'title' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'accountName' =>
                 [
                    'type' => 'varchar',
                    'maxLength' => 100,
                ],
                'website' =>
                 [
                    'type' => 'url',
                ],
                'address' =>
                 [
                    'type' => 'address',
                ],
                'addressStreet' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCity' =>
                 [
                    'type' => 'varchar',
                ],
                'addressState' =>
                 [
                    'type' => 'varchar',
                ],
                'addressCountry' =>
                 [
                    'type' => 'varchar',
                ],
                'addressPostalCode' =>
                 [
                    'type' => 'varchar',
                ],
                'emailAddress' =>
                 [
                    'type' => 'email',
                ],
                'phone' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'fax' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'phoneOffice' =>
                 [
                    'type' => 'phone',
                    'maxLength' => 50,
                ],
                'doNotCall' =>
                 [
                    'type' => 'bool',
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                ],
            ],
        ],
        'Task' =>
         [
            'fields' =>
             [
                'name' =>
                 [
                    'type' => 'varchar',
                    'required' => true,
                ],
                'status' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Not Started',
                        1 => 'Started',
                        2 => 'Completed',
                        3 => 'Canceled',
                    ],
                    'view' => 'Fields.EnumStyled',
                    'style' =>
                     [
                        'Completed' => 'success',
                        'Canceled' => 'danger',
                    ],
                ],
                'priority' =>
                 [
                    'type' => 'enum',
                    'options' =>
                     [
                        0 => 'Low',
                        1 => 'Normal',
                        2 => 'High',
                        3 => 'Urgent',
                    ],
                    'default' => 'Normal',
                ],
                'dateStart' =>
                 [
                    'type' => 'datetime',
                    'before' => 'dateEnd',
                ],
                'dateEnd' =>
                 [
                    'type' => 'datetime',
                    'after' => 'dateStart',
                ],
                'isOverdue' =>
                 [
                    'type' => 'base',
                    'db' => false,
                    'view' => 'Crm:Task.Fields.IsOverdue',
                ],
                'description' =>
                 [
                    'type' => 'text',
                ],
                'parent' =>
                 [
                    'type' => 'linkParent',
                ],
                'createdAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'modifiedAt' =>
                 [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
                'createdBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'modifiedBy' =>
                 [
                    'type' => 'link',
                    'readOnly' => true,
                ],
                'assignedUser' =>
                 [
                    'type' => 'link',
                    'required' => true,
                ],
                'teams' =>
                 [
                    'type' => 'linkMultiple',
                ],
            ],
            'links' =>
             [
                'createdBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'modifiedBy' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'assignedUser' =>
                 [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                ],
                'teams' =>
                 [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'EntityTeam',
                ],
                'parent' =>
                 [
                    'type' => 'belongsToParent',
                    'entities' =>
                     [
                        0 => 'Account',
                        1 => 'Contact',
                        2 => 'Lead',
                        3 => 'Opportunity',
                        4 => 'Case',
                    ],
                    'foreign' => 'tasks',
                ],
            ],
            'collection' =>
             [
                'sortBy' => 'createdAt',
                'asc' => false,
                'boolFilters' =>
                 [
                    0 => 'onlyMy',
                    1 => 'active',
                ],
            ],
        ],
    ],
    'fields' =>
     [
        'address' =>
         [
            'actualFields' =>
             [
                0 => 'street',
                1 => 'city',
                2 => 'state',
                3 => 'country',
                4 => 'postalCode',
            ],
            'fields' =>
             [
                'street' =>
                 [
                    'type' => 'varchar',
                ],
                'city' =>
                 [
                    'type' => 'varchar',
                ],
                'state' =>
                 [
                    'type' => 'varchar',
                ],
                'country' =>
                 [
                    'type' => 'varchar',
                ],
                'postalCode' =>
                 [
                    'type' => 'varchar',
                ],
            ],
            'mergable' => false,
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
        ],
        'array' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'options',
                    'type' => 'array',
                ],
                2 =>
                 [
                    'name' => 'translation',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => false,
            ],
            'database' =>
             [
                'type' => 'json_array',
            ],
        ],
        'autoincrement' =>
         [
            'params' =>
             [
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'int',
                'autoincrement' => true,
                'unique' => true,
            ],
        ],
        'base' =>
         [
            'search' =>
             [
                'basic' => false,
                'advanced' => false,
            ],
            'database' =>
             [
                'notStorable' => true,
            ],
        ],
        'bool' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'default',
                    'type' => 'bool',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
        ],
        'currency' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'min',
                    'type' => 'float',
                ],
                2 =>
                 [
                    'name' => 'max',
                    'type' => 'float',
                ],
            ],
            'actualFields' =>
             [
                0 => 'currency',
                1 => '',
            ],
            'fields' =>
             [
                'currency' =>
                 [
                    'type' => 'varchar',
                    'disabled' => true,
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'float',
            ],
        ],
        'date' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                2 =>
                 [
                    'name' => 'after',
                    'type' => 'varchar',
                ],
                3 =>
                 [
                    'name' => 'before',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'notnull' => false,
            ],
        ],
        'datetime' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                2 =>
                 [
                    'name' => 'after',
                    'type' => 'varchar',
                ],
                3 =>
                 [
                    'name' => 'before',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => false,
            ],
            'database' =>
             [
                'notnull' => false,
            ],
        ],
        'duration' =>
         [
            'database' =>
             [
                'type' => 'int',
            ],
        ],
        'email' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'maxLength',
                    'type' => 'int',
                ],
            ],
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
            'database' =>
             [
                'notStorable' => true,
            ],
        ],
        'enum' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'options',
                    'type' => 'array',
                ],
                2 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                3 =>
                 [
                    'name' => 'translation',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'varchar',
            ],
        ],
        'enumFloat' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'options',
                    'type' => 'array',
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'float',
                ],
                2 =>
                 [
                    'name' => 'translation',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'float',
            ],
        ],
        'enumInt' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'options',
                    'type' => 'array',
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'int',
                ],
                2 =>
                 [
                    'name' => 'translation',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'int',
            ],
        ],
        'float' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'float',
                ],
                2 =>
                 [
                    'name' => 'min',
                    'type' => 'float',
                ],
                3 =>
                 [
                    'name' => 'max',
                    'type' => 'float',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'notnull' => false,
            ],
        ],
        'int' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'int',
                ],
                2 =>
                 [
                    'name' => 'min',
                    'type' => 'int',
                ],
                3 =>
                 [
                    'name' => 'max',
                    'type' => 'int',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
        ],
        'link' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
            'actualFields' =>
             [
                0 => 'id',
            ],
            'notActualFields' =>
             [
                0 => 'name',
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'skip' => true,
            ],
        ],
        'linkMultiple' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
            'actualFields' =>
             [
                0 => 'ids',
            ],
            'notActualFields' =>
             [
                0 => 'names',
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
        ],
        'linkParent' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
            'actualFields' =>
             [
                0 => 'id',
                1 => 'type',
            ],
            'notActualFields' =>
             [
                0 => 'name',
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => true,
            ],
            'database' =>
             [
                'notStorable' => true,
            ],
        ],
        'multienum' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'options',
                    'type' => 'array',
                ],
                2 =>
                 [
                    'name' => 'translation',
                    'type' => 'varchar',
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => false,
            ],
        ],
        'password' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
            ],
            'search' =>
             [
                'basic' => false,
                'advanced' => false,
            ],
        ],
        'personName' =>
         [
            'actualFields' =>
             [
                0 => 'salutation',
                1 => 'first',
                2 => 'last',
            ],
            'fields' =>
             [
                'salutation' =>
                 [
                    'type' => 'enum',
                ],
                'first' =>
                 [
                    'type' => 'varchar',
                ],
                'last' =>
                 [
                    'type' => 'varchar',
                ],
            ],
            'naming' => 'prefix',
            'mergable' => false,
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
        ],
        'phone' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                2 =>
                 [
                    'name' => 'maxLength',
                    'type' => 'int',
                    'defalut' => 50,
                ],
            ],
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'varchar',
            ],
        ],
        'text' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'text',
                ],
            ],
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
        ],
        'url' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                2 =>
                 [
                    'name' => 'maxLength',
                    'type' => 'int',
                ],
            ],
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
            'database' =>
             [
                'type' => 'varchar',
            ],
        ],
        'varchar' =>
         [
            'params' =>
             [
                0 =>
                 [
                    'name' => 'required',
                    'type' => 'bool',
                    'default' => false,
                ],
                1 =>
                 [
                    'name' => 'default',
                    'type' => 'varchar',
                ],
                2 =>
                 [
                    'name' => 'maxLength',
                    'type' => 'int',
                ],
            ],
            'search' =>
             [
                'basic' => true,
                'advanced' => true,
            ],
        ],
    ],
    'scopes' =>
     [
        'Attachment' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Currency' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'CustomTest' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => false,
            'acl' => false,
            'customizable' => true,
        ],
        'Email' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => true,
        ],
        'EmailAddress' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'EmailTemplate' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => true,
            'customizable' => false,
        ],
        'Job' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Note' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Notification' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'OutboundEmail' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Role' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'ScheduledJob' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'ScheduledJobLogRecord' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Stream' =>
         [
            'entity' => false,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'Team' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'customizable' => false,
        ],
        'User' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => false,
            'acl' => false,
            'customizable' => true,
        ],
        'Account' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
            'stream' => true,
        ],
        'Activities' =>
         [
            'entity' => false,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'module' => 'Crm',
            'customizable' => false,
        ],
        'Calendar' =>
         [
            'entity' => false,
            'tab' => true,
            'acl' => false,
            'module' => 'Crm',
        ],
        'Call' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
        ],
        'Case' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
            'stream' => true,
        ],
        'Contact' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
            'stream' => true,
        ],
        'InboundEmail' =>
         [
            'entity' => true,
            'layouts' => false,
            'tab' => false,
            'acl' => false,
            'module' => 'Crm',
        ],
        'Lead' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
            'stream' => true,
        ],
        'Meeting' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
        ],
        'Opportunity' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
            'stream' => true,
        ],
        'Prospect' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
        ],
        'Task' =>
         [
            'entity' => true,
            'layouts' => true,
            'tab' => true,
            'acl' => true,
            'module' => 'Crm',
            'customizable' => true,
        ],
    ],
    'viewDefs' =>
     [
        'Note' =>
         [
            'recordViews' =>
             [
                'edit' => 'Note.Record.Edit',
                'editQuick' => 'Note.Record.Edit',
            ],
        ],
        'OutboundEmail' =>
         [
            'recordViews' =>
             [
                'detail' => 'OutboundEmail.Record.Detail',
                'edit' => 'OutboundEmail.Record.Edit',
                'editQuick' => 'OutboundEmail.Record.Edit',
            ],
        ],
        'Preferences' =>
         [
            'recordViews' =>
             [
                'edit' => 'Preferences.Record.Edit',
            ],
            'ciews' =>
             [
                'edit' => 'Preferences.Edit',
            ],
        ],
        'Role' =>
         [
            'recordViews' =>
             [
                'detail' => 'Role.Record.Detail',
                'edit' => 'Role.Record.Edit',
                'editQuick' => 'Role.Record.Edit',
            ],
            'relationshipPanels' =>
             [
                'users' =>
                 [
                    'create' => false,
                ],
                'teams' =>
                 [
                    'create' => false,
                ],
            ],
        ],
        'ScheduledJob' =>
         [
            'relationshipPanels' =>
             [
                'log' =>
                 [
                    'readOnly' => true,
                ],
            ],
        ],
        'Team' =>
         [
            'relationshipPanels' =>
             [
                'users' =>
                 [
                    'create' => false,
                ],
            ],
            'recordViews' =>
             [
                'detail' => 'Team.Detail',
                'edit' => 'Team.Edit',
            ],
        ],
        'User' =>
         [
            'recordViews' =>
             [
                'detail' => 'User.Record.Detail',
                'edit' => 'User.Record.Edit',
                'editQuick' => 'User.Record.Edit',
            ],
        ],
        'Account' =>
         [
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'activities',
                        'label' => 'Activities',
                        'view' => 'Crm:Record.Panels.Activities',
                    ],
                    1 =>
                     [
                        'name' => 'history',
                        'label' => 'History',
                        'view' => 'Crm:Record.Panels.History',
                    ],
                    2 =>
                     [
                        'name' => 'tasks',
                        'label' => 'Tasks',
                        'view' => 'Crm:Record.Panels.Tasks',
                    ],
                ],
            ],
            'relationshipPanels' =>
             [
                'contacts' =>
                 [
                    'actions' =>
                     [
                    ],
                    'layout' => 'listSmall',
                ],
            ],
        ],
        'Call' =>
         [
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'attendees',
                        'label' => 'Attendees',
                        'view' => 'Record.Panels.Side',
                        'options' =>
                         [
                            'fields' =>
                             [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads',
                            ],
                            'mode' => 'detail',
                        ],
                    ],
                ],
                'edit' =>
                 [
                    0 =>
                     [
                        'name' => 'attendees',
                        'label' => 'Attendees',
                        'view' => 'Record.Panels.Side',
                        'options' =>
                         [
                            'fields' =>
                             [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads',
                            ],
                            'mode' => 'edit',
                        ],
                    ],
                ],
            ],
        ],
        'Case' =>
         [
            'bottomPanels' =>
             [
                'detail' =>
                 [
                ],
            ],
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'activities',
                        'label' => 'Activities',
                        'view' => 'Crm:Record.Panels.Activities',
                    ],
                    1 =>
                     [
                        'name' => 'history',
                        'label' => 'History',
                        'view' => 'Crm:Record.Panels.History',
                    ],
                    2 =>
                     [
                        'name' => 'tasks',
                        'label' => 'Tasks',
                        'view' => 'Crm:Record.Panels.Tasks',
                    ],
                ],
            ],
        ],
        'Contact' =>
         [
            'views' =>
             [
                'detail' => 'Crm:Contact.Detail',
            ],
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'activities',
                        'label' => 'Activities',
                        'view' => 'Crm:Record.Panels.Activities',
                    ],
                    1 =>
                     [
                        'name' => 'history',
                        'label' => 'History',
                        'view' => 'Crm:Record.Panels.History',
                    ],
                    2 =>
                     [
                        'name' => 'tasks',
                        'label' => 'Tasks',
                        'view' => 'Crm:Record.Panels.Tasks',
                    ],
                ],
            ],
        ],
        'InboundEmail' =>
         [
            'recordViews' =>
             [
                'detail' => 'Crm:InboundEmail.Record.Detail',
                'edit' => 'Crm:InboundEmail.Record.Edit',
            ],
        ],
        'Lead' =>
         [
            'views' =>
             [
                'detail' => 'Crm:Lead.Detail',
            ],
            'recordViews' =>
             [
                'detail' => 'Crm:Lead.Record.Detail',
            ],
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'activities',
                        'label' => 'Activities',
                        'view' => 'Crm:Record.Panels.Activities',
                    ],
                    1 =>
                     [
                        'name' => 'history',
                        'label' => 'History',
                        'view' => 'Crm:Record.Panels.History',
                    ],
                    2 =>
                     [
                        'name' => 'tasks',
                        'label' => 'Tasks',
                        'view' => 'Crm:Record.Panels.Tasks',
                    ],
                ],
            ],
        ],
        'Meeting' =>
         [
            'views' =>
             [
                'detail' => 'Crm:Meeting.Detail',
            ],
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'attendees',
                        'label' => 'Attendees',
                        'view' => 'Record.Panels.Side',
                        'options' =>
                         [
                            'fields' =>
                             [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads',
                            ],
                            'mode' => 'detail',
                        ],
                    ],
                ],
                'edit' =>
                 [
                    0 =>
                     [
                        'name' => 'attendees',
                        'label' => 'Attendees',
                        'view' => 'Record.Panels.Side',
                        'options' =>
                         [
                            'fields' =>
                             [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads',
                            ],
                            'mode' => 'edit',
                        ],
                    ],
                ],
            ],
        ],
        'Opportunity' =>
         [
            'views' =>
             [
                'detail' => 'Crm:Opportunity.Detail',
            ],
            'sidePanels' =>
             [
                'detail' =>
                 [
                    0 =>
                     [
                        'name' => 'activities',
                        'label' => 'Activities',
                        'view' => 'Crm:Record.Panels.Activities',
                    ],
                    1 =>
                     [
                        'name' => 'history',
                        'label' => 'History',
                        'view' => 'Crm:Record.Panels.History',
                    ],
                    2 =>
                     [
                        'name' => 'tasks',
                        'label' => 'Tasks',
                        'view' => 'Crm:Record.Panels.Tasks',
                    ],
                ],
            ],
        ],
        'Prospect' =>
         [
            'views' =>
             [
                'detail' => 'Crm:Prospect.Detail',
            ],
            'menu' =>
             [
                'detail' =>
                 [
                    'buttons' =>
                     [
                        0 =>
                         [
                            'label' => 'Convert to Lead',
                            'action' => 'convertToLead',
                            'acl' => 'edit',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

?>