<?php
return (object) [
    'app' => (object) [
        'adminPanel' => (object) [
            'system' => (object) [
                'label' => 'System',
                'itemList' => [
                    0 => (object) [
                        'url' => '#Admin/settings',
                        'label' => 'Settings',
                        'description' => 'settings'
                    ],
                    1 => (object) [
                        'url' => '#Admin/userInterface',
                        'label' => 'User Interface',
                        'description' => 'userInterface'
                    ],
                    2 => (object) [
                        'url' => '#Admin/authentication',
                        'label' => 'Authentication',
                        'description' => 'authentication'
                    ],
                    3 => (object) [
                        'url' => '#ScheduledJob',
                        'label' => 'Scheduled Jobs',
                        'description' => 'scheduledJob'
                    ],
                    4 => (object) [
                        'url' => '#Admin/currency',
                        'label' => 'Currency',
                        'description' => 'currency'
                    ],
                    5 => (object) [
                        'url' => '#Admin/notifications',
                        'label' => 'Notifications',
                        'description' => 'notifications'
                    ],
                    6 => (object) [
                        'url' => '#Admin/integrations',
                        'label' => 'Integrations',
                        'description' => 'integrations'
                    ],
                    7 => (object) [
                        'url' => '#Admin/upgrade',
                        'label' => 'Upgrade',
                        'description' => 'upgrade'
                    ],
                    8 => (object) [
                        'url' => '#Admin/clearCache',
                        'label' => 'Clear Cache',
                        'description' => 'clearCache'
                    ],
                    9 => (object) [
                        'url' => '#Admin/rebuild',
                        'label' => 'Rebuild',
                        'description' => 'rebuild'
                    ]
                ],
                'order' => 0
            ],
            'users' => (object) [
                'label' => 'Users',
                'itemList' => [
                    0 => (object) [
                        'url' => '#User',
                        'label' => 'Users',
                        'description' => 'users'
                    ],
                    1 => (object) [
                        'url' => '#Team',
                        'label' => 'Teams',
                        'description' => 'teams'
                    ],
                    2 => (object) [
                        'url' => '#Role',
                        'label' => 'Roles',
                        'description' => 'roles'
                    ],
                    3 => (object) [
                        'url' => '#Admin/authTokens',
                        'label' => 'Auth Tokens',
                        'description' => 'authTokens'
                    ],
                    4 => (object) [
                        'url' => '#ActionHistoryRecord',
                        'label' => 'Action History',
                        'description' => 'actionHistory'
                    ]
                ],
                'order' => 5
            ],
            'customization' => (object) [
                'label' => 'Customization',
                'itemList' => [
                    0 => (object) [
                        'url' => '#Admin/layouts',
                        'label' => 'Layout Manager',
                        'description' => 'layoutManager'
                    ],
                    1 => (object) [
                        'url' => '#Admin/entityManager',
                        'label' => 'Entity Manager',
                        'description' => 'entityManager'
                    ],
                    2 => (object) [
                        'url' => '#Admin/labelManager',
                        'label' => 'Label Manager',
                        'description' => 'labelManager'
                    ],
                    3 => (object) [
                        'url' => '#Admin/extensions',
                        'label' => 'Extensions',
                        'description' => 'extensions'
                    ]
                ],
                'order' => 10
            ],
            'email' => (object) [
                'label' => 'Email',
                'itemList' => [
                    0 => (object) [
                        'url' => '#Admin/outboundEmails',
                        'label' => 'Outbound Emails',
                        'description' => 'outboundEmails'
                    ],
                    1 => (object) [
                        'url' => '#Admin/inboundEmails',
                        'label' => 'Inbound Emails',
                        'description' => 'inboundEmails'
                    ],
                    2 => (object) [
                        'url' => '#InboundEmail',
                        'label' => 'Group Email Accounts',
                        'description' => 'groupEmailAccounts'
                    ],
                    3 => (object) [
                        'url' => '#EmailAccount',
                        'label' => 'Personal Email Accounts',
                        'description' => 'personalEmailAccounts'
                    ],
                    4 => (object) [
                        'url' => '#EmailFilter',
                        'label' => 'Email Filters',
                        'description' => 'emailFilters'
                    ],
                    5 => (object) [
                        'url' => '#EmailTemplate',
                        'label' => 'Email Templates',
                        'description' => 'emailTemplates'
                    ]
                ],
                'order' => 15
            ],
            'portal' => (object) [
                'label' => 'Portal',
                'itemList' => [
                    0 => (object) [
                        'url' => '#Portal',
                        'label' => 'Portals',
                        'description' => 'portals'
                    ],
                    1 => (object) [
                        'url' => '#PortalUser',
                        'label' => 'Portal Users',
                        'description' => 'portalUsers'
                    ],
                    2 => (object) [
                        'url' => '#PortalRole',
                        'label' => 'Portal Roles',
                        'description' => 'portalRoles'
                    ]
                ],
                'order' => 20
            ],
            'data' => (object) [
                'label' => 'Data',
                'itemList' => [
                    0 => (object) [
                        'url' => '#Import',
                        'label' => 'Import',
                        'description' => 'import'
                    ]
                ],
                'order' => 25
            ]
        ],
        'popupNotifications' => (object) [
            'event' => (object) [
                'url' => 'Activities/action/popupNotifications',
                'interval' => 15,
                'view' => 'crm:views/meeting/popup-notification'
            ]
        ]
    ],
    'entityDefs' => (object) [
        'ActionHistoryRecord' => (object) [
            'fields' => (object) [
                'number' => (object) [
                    'type' => 'autoincrement',
                    'index' => true
                ],
                'targetType' => (object) [
                    'view' => 'views/action-history-record/fields/target-type',
                    'translation' => 'Global.scopeNames'
                ],
                'target' => (object) [
                    'type' => 'linkParent',
                    'view' => 'views/action-history-record/fields/target'
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'action' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'read',
                        1 => 'update',
                        2 => 'create',
                        3 => 'delete'
                    ]
                ],
                'createdAt' => (object) [
                    'type' => 'datetime'
                ],
                'user' => (object) [
                    'type' => 'link'
                ],
                'ipAddress' => (object) [
                    'type' => 'varchar',
                    'maxLength' => '39'
                ],
                'authToken' => (object) [
                    'type' => 'link'
                ]
            ],
            'links' => (object) [
                'user' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'target' => (object) [
                    'type' => 'belongsToParent'
                ],
                'authToken' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'AuthToken',
                    'foreignName' => 'id',
                    'foreign' => 'actionHistoryRecords'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'number',
                'asc' => false,
                'textFilterFields' => [
                    0 => 'ipAddress',
                    1 => 'userName'
                ]
            ]
        ],
        'Attachment' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'type' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'size' => (object) [
                    'type' => 'int',
                    'min' => 0
                ],
                'parent' => (object) [
                    'type' => 'linkParent'
                ],
                'related' => (object) [
                    'type' => 'linkParent',
                    'noLoad' => true
                ],
                'sourceId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 36,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'field' => (object) [
                    'type' => 'varchar',
                    'disabled' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'contents' => (object) [
                    'type' => 'text',
                    'notStorable' => true
                ],
                'role' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 36
                ],
                'storage' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 24,
                    'default' => NULL
                ],
                'storageFilePath' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 260,
                    'default' => NULL
                ],
                'global' => (object) [
                    'type' => 'bool',
                    'default' => false
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'foreign' => 'attachments'
                ],
                'related' => (object) [
                    'type' => 'belongsToParent'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'parent' => (object) [
                    'columns' => [
                        0 => 'parentType',
                        1 => 'parentId'
                    ]
                ]
            ],
            'sourceList' => [
                0 => 'Document'
            ]
        ],
        'AuthToken' => (object) [
            'fields' => (object) [
                'token' => (object) [
                    'type' => 'varchar',
                    'maxLength' => '36',
                    'index' => true,
                    'readOnly' => true
                ],
                'hash' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 150,
                    'index' => true,
                    'readOnly' => true
                ],
                'userId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => '36',
                    'readOnly' => true
                ],
                'user' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'portal' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'ipAddress' => (object) [
                    'type' => 'varchar',
                    'maxLength' => '36',
                    'readOnly' => true
                ],
                'isActive' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'lastAccess' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'user' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'portal' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Portal'
                ],
                'actionHistoryRecords' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'ActionHistoryRecord',
                    'foreign' => 'authToken'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'lastAccess',
                'asc' => false,
                'textFilterFields' => [
                    0 => 'ipAddress',
                    1 => 'userName'
                ]
            ],
            'indexes' => (object) [
                'token' => (object) [
                    'columns' => [
                        0 => 'token',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'Currency' => (object) [
            'fields' => (object) [
                'rate' => (object) [
                    'type' => 'float'
                ]
            ]
        ],
        'Email' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'subject' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'notStorable' => true,
                    'view' => 'views/email/fields/subject',
                    'disabled' => true,
                    'trim' => true
                ],
                'fromName' => (object) [
                    'type' => 'varchar'
                ],
                'fromString' => (object) [
                    'type' => 'varchar'
                ],
                'replyToString' => (object) [
                    'type' => 'varchar'
                ],
                'addressNameMap' => (object) [
                    'type' => 'jsonObject',
                    'disabled' => true,
                    'readOnly' => true
                ],
                'from' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'required' => true,
                    'view' => 'views/email/fields/from-address-varchar'
                ],
                'to' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'required' => true,
                    'view' => 'views/email/fields/email-address-varchar'
                ],
                'cc' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'view' => 'views/email/fields/email-address-varchar'
                ],
                'bcc' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'view' => 'views/email/fields/email-address-varchar'
                ],
                'replyTo' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'view' => 'views/email/fields/email-address-varchar'
                ],
                'personStringData' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'isRead' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'default' => true,
                    'readOnly' => true
                ],
                'isNotRead' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'readOnly' => true
                ],
                'isReplied' => (object) [
                    'type' => 'bool',
                    'readOnly' => true
                ],
                'isNotReplied' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'readOnly' => true
                ],
                'isImportant' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'default' => false
                ],
                'inTrash' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'default' => false
                ],
                'folderId' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'default' => false
                ],
                'isUsers' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'default' => false
                ],
                'folder' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'readOnly' => true
                ],
                'nameHash' => (object) [
                    'type' => 'text',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'typeHash' => (object) [
                    'type' => 'text',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'idHash' => (object) [
                    'type' => 'text',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'messageId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 255,
                    'readOnly' => true,
                    'index' => true
                ],
                'messageIdInternal' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 300,
                    'readOnly' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'base',
                    'notStorable' => true,
                    'view' => 'views/email/fields/email-address'
                ],
                'fromEmailAddress' => (object) [
                    'type' => 'link',
                    'view' => 'views/email/fields/from-email-address'
                ],
                'toEmailAddresses' => (object) [
                    'type' => 'linkMultiple'
                ],
                'ccEmailAddresses' => (object) [
                    'type' => 'linkMultiple'
                ],
                'bodyPlain' => (object) [
                    'type' => 'text',
                    'readOnly' => true,
                    'seeMoreDisabled' => true
                ],
                'body' => (object) [
                    'type' => 'wysiwyg',
                    'seeMoreDisabled' => true,
                    'view' => 'views/email/fields/body'
                ],
                'isHtml' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Draft',
                        1 => 'Sending',
                        2 => 'Sent',
                        3 => 'Archived',
                        4 => 'Failed'
                    ],
                    'readOnly' => true,
                    'default' => 'Archived'
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple',
                    'sourceList' => [
                        0 => 'Document'
                    ]
                ],
                'hasAttachment' => (object) [
                    'type' => 'bool',
                    'readOnly' => true
                ],
                'parent' => (object) [
                    'type' => 'linkParent',
                    'entityList' => [
                        0 => 'Account',
                        1 => 'Lead',
                        2 => 'Contact',
                        3 => 'Opportunity',
                        4 => 'Case'
                    ]
                ],
                'dateSent' => (object) [
                    'type' => 'datetime'
                ],
                'deliveryDate' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'sentBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'noLoad' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => false,
                    'view' => 'views/fields/assigned-user'
                ],
                'replied' => (object) [
                    'type' => 'link',
                    'noJoin' => true,
                    'readOnly' => true
                ],
                'replies' => (object) [
                    'type' => 'linkMultiple',
                    'readOnly' => true
                ],
                'isSystem' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'readOnly' => true
                ],
                'isJustSent' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'disabled' => true,
                    'notStorable' => true
                ],
                'isBeingImported' => (object) [
                    'type' => 'bool',
                    'disabled' => true,
                    'notStorable' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'users' => (object) [
                    'type' => 'linkMultiple',
                    'noLoad' => true,
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'readOnly' => true,
                    'columns' => (object) [
                        'inTrash' => 'inTrash',
                        'folderId' => 'folderId'
                    ]
                ],
                'assignedUsers' => (object) [
                    'type' => 'linkMultiple',
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'readOnly' => true
                ],
                'inboundEmails' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'noLoad' => true
                ],
                'emailAccounts' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'noLoad' => true
                ],
                'account' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam'
                ],
                'assignedUsers' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'relationName' => 'entityUser'
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'emails',
                    'additionalColumns' => (object) [
                        'isRead' => (object) [
                            'type' => 'bool',
                            'default' => false
                        ],
                        'isImportant' => (object) [
                            'type' => 'bool',
                            'default' => false
                        ],
                        'inTrash' => (object) [
                            'type' => 'bool',
                            'default' => false
                        ],
                        'folderId' => (object) [
                            'type' => 'varchar',
                            'default' => NULL,
                            'maxLength' => 24
                        ]
                    ]
                ],
                'sentBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'attachments' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'foreign' => 'parent',
                    'relationName' => 'attachments'
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'entityList' => [

                    ],
                    'foreign' => 'emails'
                ],
                'replied' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Email',
                    'foreign' => 'replies'
                ],
                'replies' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Email',
                    'foreign' => 'replied'
                ],
                'fromEmailAddress' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailAddress'
                ],
                'toEmailAddresses' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'emailEmailAddress',
                    'conditions' => (object) [
                        'addressType' => 'to'
                    ],
                    'additionalColumns' => (object) [
                        'addressType' => (object) [
                            'type' => 'varchar',
                            'len' => '4'
                        ]
                    ]
                ],
                'ccEmailAddresses' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'emailEmailAddress',
                    'conditions' => (object) [
                        'addressType' => 'cc'
                    ],
                    'additionalColumns' => (object) [
                        'addressType' => (object) [
                            'type' => 'varchar',
                            'len' => '4'
                        ]
                    ]
                ],
                'bccEmailAddresses' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'EmailAddress',
                    'relationName' => 'emailEmailAddress',
                    'conditions' => (object) [
                        'addressType' => 'bcc'
                    ],
                    'additionalColumns' => (object) [
                        'addressType' => (object) [
                            'type' => 'varchar',
                            'len' => '4'
                        ]
                    ]
                ],
                'inboundEmails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'InboundEmail',
                    'foreign' => 'emails'
                ],
                'emailAccounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'EmailAccount',
                    'foreign' => 'emails'
                ],
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'dateSent',
                'asc' => false,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'bodyPlain',
                    2 => 'body'
                ]
            ],
            'indexes' => (object) [
                'dateSent' => (object) [
                    'columns' => [
                        0 => 'dateSent',
                        1 => 'deleted'
                    ]
                ],
                'dateSentStatus' => (object) [
                    'columns' => [
                        0 => 'dateSent',
                        1 => 'status',
                        2 => 'deleted'
                    ]
                ]
            ]
        ],
        'EmailAccount' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 100,
                    'trim' => true,
                    'view' => 'views/email-account/fields/email-address'
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Active',
                        1 => 'Inactive'
                    ],
                    'default' => 'Active'
                ],
                'host' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'port' => (object) [
                    'type' => 'varchar',
                    'default' => '143',
                    'required' => true
                ],
                'ssl' => (object) [
                    'type' => 'bool'
                ],
                'username' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'password' => (object) [
                    'type' => 'password'
                ],
                'monitoredFolders' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'default' => 'INBOX',
                    'view' => 'views/email-account/fields/folders',
                    'tooltip' => true
                ],
                'sentFolder' => (object) [
                    'type' => 'varchar',
                    'view' => 'views/email-account/fields/folder'
                ],
                'storeSentEmails' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'keepFetchedEmailsUnread' => (object) [
                    'type' => 'bool'
                ],
                'fetchSince' => (object) [
                    'type' => 'date',
                    'required' => true
                ],
                'fetchData' => (object) [
                    'type' => 'jsonObject',
                    'readOnly' => true
                ],
                'emailFolder' => (object) [
                    'type' => 'link',
                    'view' => 'views/email-account/fields/email-folder'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => true
                ],
                'useImap' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'useSmtp' => (object) [
                    'type' => 'bool'
                ],
                'smtpHost' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'smtpPort' => (object) [
                    'type' => 'int',
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25
                ],
                'smtpAuth' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'smtpSecurity' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS'
                    ]
                ],
                'smtpUsername' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'smtpPassword' => (object) [
                    'type' => 'password'
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'filters' => (object) [
                    'type' => 'hasChildren',
                    'foreign' => 'parent',
                    'entity' => 'EmailFilter'
                ],
                'emails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Email',
                    'foreign' => 'emailAccounts'
                ],
                'emailFolder' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailFolder'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'EmailAddress' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true
                ],
                'lower' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'index' => true
                ],
                'invalid' => (object) [
                    'type' => 'bool'
                ],
                'optOut' => (object) [
                    'type' => 'bool'
                ]
            ],
            'links' => (object) [

            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'EmailFilter' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 100,
                    'tooltip' => true,
                    'trim' => true
                ],
                'from' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 255,
                    'tooltip' => true,
                    'trim' => true
                ],
                'to' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 255,
                    'tooltip' => true,
                    'trim' => true
                ],
                'subject' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 255,
                    'tooltip' => true
                ],
                'bodyContains' => (object) [
                    'type' => 'array',
                    'tooltip' => true
                ],
                'isGlobal' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'parent' => (object) [
                    'type' => 'linkParent'
                ],
                'action' => (object) [
                    'type' => 'enum',
                    'default' => 'Skip',
                    'options' => [
                        0 => 'Skip',
                        1 => 'Move to Folder'
                    ],
                    'view' => 'views/email-filter/fields/action'
                ],
                'emailFolder' => (object) [
                    'type' => 'link',
                    'view' => 'views/email-filter/fields/email-folder'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'entityList' => [
                        0 => 'User',
                        1 => 'EmailAccount',
                        2 => 'InboundEmail'
                    ]
                ],
                'emailFolder' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailFolder'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'EmailFolder' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 64,
                    'trim' => true
                ],
                'order' => (object) [
                    'type' => 'int'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'skipNotifications' => (object) [
                    'type' => 'bool'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'order',
                'asc' => true
            ]
        ],
        'EmailTemplate' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'subject' => (object) [
                    'type' => 'varchar'
                ],
                'body' => (object) [
                    'type' => 'text',
                    'view' => 'views/fields/wysiwyg'
                ],
                'isHtml' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'oneOff' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple'
                ],
                'assignedUser' => (object) [
                    'type' => 'link'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ]
            ],
            'links' => (object) [
                'attachments' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'foreign' => 'parent'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'Extension' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true
                ],
                'version' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 50
                ],
                'fileList' => (object) [
                    'type' => 'jsonArray'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'isInstalled' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'ExternalAccount' => (object) [
            'fields' => (object) [
                'id' => (object) [
                    'maxLength' => 64
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'enabled' => (object) [
                    'type' => 'bool'
                ]
            ]
        ],
        'Import' => (object) [
            'fields' => (object) [
                'entityType' => (object) [
                    'type' => 'enum',
                    'translation' => 'Global.scopeNames',
                    'required' => true,
                    'readOnly' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'In Process',
                        1 => 'Complete',
                        2 => 'Failed'
                    ],
                    'readOnly' => true,
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Complete' => 'success',
                        'Failed' => 'danger'
                    ]
                ],
                'file' => (object) [
                    'type' => 'file',
                    'required' => true,
                    'readOnly' => true
                ],
                'importedCount' => (object) [
                    'type' => 'int',
                    'readOnly' => true,
                    'notStorable' => true
                ],
                'duplicateCount' => (object) [
                    'type' => 'int',
                    'readOnly' => true,
                    'notStorable' => true
                ],
                'updatedCount' => (object) [
                    'type' => 'int',
                    'readOnly' => true,
                    'notStorable' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'InboundEmail' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 100,
                    'view' => 'views/inbound-email/fields/email-address',
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Active',
                        1 => 'Inactive'
                    ],
                    'default' => 'Active'
                ],
                'host' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'port' => (object) [
                    'type' => 'varchar',
                    'default' => '143'
                ],
                'ssl' => (object) [
                    'type' => 'bool'
                ],
                'username' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'password' => (object) [
                    'type' => 'password'
                ],
                'monitoredFolders' => (object) [
                    'type' => 'varchar',
                    'default' => 'INBOX',
                    'view' => 'views/inbound-email/fields/folders',
                    'tooltip' => true
                ],
                'fetchSince' => (object) [
                    'type' => 'date',
                    'required' => true
                ],
                'fetchData' => (object) [
                    'type' => 'jsonObject',
                    'readOnly' => true
                ],
                'assignToUser' => (object) [
                    'type' => 'link',
                    'tooltip' => true
                ],
                'team' => (object) [
                    'type' => 'link',
                    'tooltip' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'addAllTeamUsers' => (object) [
                    'type' => 'bool',
                    'tooltip' => true,
                    'default' => true
                ],
                'sentFolder' => (object) [
                    'type' => 'varchar',
                    'view' => 'views/inbound-email/fields/folder'
                ],
                'storeSentEmails' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'useImap' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'useSmtp' => (object) [
                    'type' => 'bool'
                ],
                'smtpIsShared' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'smtpIsForMassEmail' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'smtpHost' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'smtpPort' => (object) [
                    'type' => 'int',
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25
                ],
                'smtpAuth' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'smtpSecurity' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS'
                    ]
                ],
                'smtpUsername' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'smtpPassword' => (object) [
                    'type' => 'password'
                ],
                'createCase' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'caseDistribution' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Direct-Assignment',
                        2 => 'Round-Robin',
                        3 => 'Least-Busy'
                    ],
                    'default' => 'Direct-Assignment',
                    'tooltip' => true
                ],
                'targetUserPosition' => (object) [
                    'type' => 'enum',
                    'view' => 'views/inbound-email/fields/target-user-position',
                    'tooltip' => true
                ],
                'reply' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'replyEmailTemplate' => (object) [
                    'type' => 'link'
                ],
                'replyFromAddress' => (object) [
                    'type' => 'varchar'
                ],
                'replyToAddress' => (object) [
                    'type' => 'varchar',
                    'tooltip' => true
                ],
                'replyFromName' => (object) [
                    'type' => 'varchar'
                ],
                'fromName' => (object) [
                    'type' => 'varchar'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'inboundEmails'
                ],
                'assignToUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'team' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Team'
                ],
                'replyEmailTemplate' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailTemplate'
                ],
                'filters' => (object) [
                    'type' => 'hasChildren',
                    'foreign' => 'parent',
                    'entity' => 'EmailFilter'
                ],
                'emails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Email',
                    'foreign' => 'inboundEmails'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'Integration' => (object) [
            'fields' => (object) [
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'enabled' => (object) [
                    'type' => 'bool'
                ]
            ]
        ],
        'Job' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'view' => 'views/admin/job/fields/name'
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Pending',
                        1 => 'Running',
                        2 => 'Success',
                        3 => 'Failed'
                    ],
                    'default' => 'Pending'
                ],
                'executeTime' => (object) [
                    'type' => 'datetime',
                    'required' => true
                ],
                'serviceName' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 100
                ],
                'method' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 100
                ],
                'methodName' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'scheduledJob' => (object) [
                    'type' => 'link'
                ],
                'scheduledJobJob' => (object) [
                    'type' => 'foreign',
                    'link' => 'scheduledJob',
                    'field' => 'job'
                ],
                'pid' => (object) [
                    'type' => 'int'
                ],
                'attempts' => (object) [
                    'type' => 'int'
                ],
                'targetId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 48
                ],
                'targetType' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 64
                ],
                'failedAttempts' => (object) [
                    'type' => 'int'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'scheduledJob' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'ScheduledJob'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'methodName',
                    2 => 'serviceName',
                    3 => 'scheduledJobName'
                ]
            ],
            'indexes' => (object) [
                'executeTime' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'executeTime'
                    ]
                ],
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'NextNumber' => (object) [
            'fields' => (object) [
                'entityType' => (object) [
                    'type' => 'varchar',
                    'index' => true
                ],
                'fieldName' => (object) [
                    'type' => 'varchar'
                ],
                'value' => (object) [
                    'type' => 'int',
                    'default' => 1
                ]
            ]
        ],
        'Note' => (object) [
            'fields' => (object) [
                'post' => (object) [
                    'type' => 'text',
                    'rows' => 30
                ],
                'data' => (object) [
                    'type' => 'jsonObject',
                    'readOnly' => true
                ],
                'type' => (object) [
                    'type' => 'varchar',
                    'readOnly' => true,
                    'view' => 'views/fields/enum',
                    'options' => [
                        0 => 'Post'
                    ]
                ],
                'targetType' => (object) [
                    'type' => 'varchar'
                ],
                'parent' => (object) [
                    'type' => 'linkParent',
                    'readOnly' => true
                ],
                'related' => (object) [
                    'type' => 'linkParent',
                    'readOnly' => true
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple',
                    'view' => 'views/stream/fields/attachment-multiple'
                ],
                'number' => (object) [
                    'type' => 'autoincrement',
                    'index' => true,
                    'readOnly' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'noLoad' => true
                ],
                'portals' => (object) [
                    'type' => 'linkMultiple',
                    'noLoad' => true
                ],
                'users' => (object) [
                    'type' => 'linkMultiple',
                    'noLoad' => true
                ],
                'isGlobal' => (object) [
                    'type' => 'bool'
                ],
                'createdByGender' => (object) [
                    'type' => 'foreign',
                    'link' => 'createdBy',
                    'field' => 'gender'
                ],
                'notifiedUserIdList' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'isInternal' => (object) [
                    'type' => 'bool'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'attachments' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Attachment',
                    'relationName' => 'attachments',
                    'foreign' => 'parent'
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'foreign' => 'notes'
                ],
                'superParent' => (object) [
                    'type' => 'belongsToParent'
                ],
                'related' => (object) [
                    'type' => 'belongsToParent'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'notes'
                ],
                'portals' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Portal',
                    'foreign' => 'notes'
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'notes'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'number',
                'asc' => false
            ],
            'statusStyles' => (object) [
                'Lead' => (object) [
                    'Assigned' => 'primary',
                    'In Process' => 'primary',
                    'Converted' => 'success',
                    'Recycled' => 'danger',
                    'Dead' => 'danger'
                ],
                'Case' => (object) [
                    'New' => 'primary',
                    'Assigned' => 'primary',
                    'Pending' => 'default',
                    'Closed' => 'success',
                    'Rejected' => 'danger',
                    'Duplicate' => 'danger'
                ],
                'Opportunity' => (object) [
                    'Proposal' => 'primary',
                    'Negotiation' => 'primary',
                    'Closed Won' => 'success',
                    'Closed Lost' => 'danger'
                ],
                'Task' => (object) [
                    'Completed' => 'success',
                    'Started' => 'primary',
                    'Canceled' => 'danger'
                ],
                'Meeting' => (object) [
                    'Held' => 'success'
                ],
                'Call' => (object) [
                    'Held' => 'success'
                ]
            ],
            'indexes' => (object) [
                'createdAt' => (object) [
                    'type' => 'index',
                    'columns' => [
                        0 => 'createdAt'
                    ]
                ],
                'parent' => (object) [
                    'type' => 'index',
                    'columns' => [
                        0 => 'parentId',
                        1 => 'parentType'
                    ]
                ],
                'parentAndSuperParent' => (object) [
                    'type' => 'index',
                    'columns' => [
                        0 => 'parentId',
                        1 => 'parentType',
                        2 => 'superParentId',
                        3 => 'superParentType'
                    ]
                ]
            ]
        ],
        'Notification' => (object) [
            'fields' => (object) [
                'number' => (object) [
                    'type' => 'autoincrement',
                    'index' => true
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'noteData' => (object) [
                    'type' => 'jsonObject',
                    'notStorable' => true
                ],
                'type' => (object) [
                    'type' => 'varchar'
                ],
                'read' => (object) [
                    'type' => 'bool'
                ],
                'emailIsProcessed' => (object) [
                    'type' => 'bool'
                ],
                'user' => (object) [
                    'type' => 'link'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'message' => (object) [
                    'type' => 'text'
                ],
                'related' => (object) [
                    'type' => 'linkParent',
                    'readOnly' => true
                ],
                'relatedParent' => (object) [
                    'type' => 'linkParent',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'user' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'related' => (object) [
                    'type' => 'belongsToParent'
                ],
                'relatedParent' => (object) [
                    'type' => 'belongsToParent'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'number',
                'asc' => false
            ],
            'indexes' => (object) [
                'createdAt' => (object) [
                    'type' => 'index',
                    'columns' => [
                        0 => 'createdAt'
                    ]
                ],
                'user' => (object) [
                    'type' => 'index',
                    'columns' => [
                        0 => 'userId',
                        1 => 'createdAt'
                    ]
                ]
            ]
        ],
        'PasswordChangeRequest' => (object) [
            'fields' => (object) [
                'requestId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 24,
                    'index' => true
                ],
                'user' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'index' => true
                ],
                'url' => (object) [
                    'type' => 'url'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'user' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ]
        ],
        'PhoneNumber' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 36,
                    'index' => true
                ],
                'type' => (object) [
                    'type' => 'enum'
                ]
            ],
            'links' => (object) [

            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'Portal' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'trim' => true
                ],
                'logo' => (object) [
                    'type' => 'image'
                ],
                'url' => (object) [
                    'type' => 'url',
                    'notStorable' => true,
                    'readOnly' => true
                ],
                'customId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 36,
                    'view' => 'views/portal/fields/custom-id',
                    'trim' => true,
                    'index' => true
                ],
                'isActive' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'isDefault' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'notStorable' => true
                ],
                'portalRoles' => (object) [
                    'type' => 'linkMultiple'
                ],
                'tabList' => (object) [
                    'type' => 'array',
                    'view' => 'views/portal/fields/tab-list'
                ],
                'quickCreateList' => (object) [
                    'type' => 'array',
                    'translation' => 'Global.scopeNames',
                    'view' => 'views/portal/fields/quick-create-list'
                ],
                'companyLogo' => (object) [
                    'type' => 'image'
                ],
                'theme' => (object) [
                    'type' => 'enum',
                    'view' => 'views/preferences/fields/theme',
                    'translation' => 'Global.themes',
                    'default' => ''
                ],
                'language' => (object) [
                    'type' => 'enum',
                    'view' => 'views/preferences/fields/language',
                    'default' => ''
                ],
                'timeZone' => (object) [
                    'type' => 'enum',
                    'detault' => '',
                    'view' => 'views/preferences/fields/time-zone'
                ],
                'dateFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'MM/DD/YYYY',
                        1 => 'YYYY-MM-DD',
                        2 => 'DD.MM.YYYY',
                        3 => 'DD/MM/YYYY'
                    ],
                    'default' => '',
                    'view' => 'views/preferences/fields/date-format'
                ],
                'timeFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'HH:mm',
                        1 => 'hh:mma',
                        2 => 'hh:mmA',
                        3 => 'hh:mm A',
                        4 => 'hh:mm a'
                    ],
                    'default' => '',
                    'view' => 'views/preferences/fields/time-format'
                ],
                'weekStart' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 0,
                        1 => 1
                    ],
                    'default' => -1,
                    'view' => 'views/preferences/fields/week-start'
                ],
                'defaultCurrency' => (object) [
                    'type' => 'enum',
                    'default' => '',
                    'view' => 'views/preferences/fields/default-currency'
                ],
                'dashboardLayout' => (object) [
                    'type' => 'jsonArray',
                    'view' => 'views/settings/fields/dashboard-layout'
                ],
                'dashletsOptions' => (object) [
                    'type' => 'jsonObject',
                    'disabled' => true
                ],
                'customUrl' => (object) [
                    'type' => 'url'
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'portals'
                ],
                'portalRoles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'PortalRole',
                    'foreign' => 'portals'
                ],
                'notes' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Note',
                    'foreign' => 'portals'
                ],
                'articles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'KnowledgeBaseArticle',
                    'foreign' => 'portals'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'PortalRole' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'maxLength' => 150,
                    'required' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'fieldData' => (object) [
                    'type' => 'jsonObject'
                ],
                'exportPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'yes',
                        2 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ]
            ],
            'links' => (object) [
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'portalRoles'
                ],
                'portals' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Portal',
                    'foreign' => 'portalRoles'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'Preferences' => (object) [
            'fields' => (object) [
                'timeZone' => (object) [
                    'type' => 'enum',
                    'detault' => '',
                    'view' => 'views/preferences/fields/time-zone'
                ],
                'dateFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'MM/DD/YYYY',
                        1 => 'YYYY-MM-DD',
                        2 => 'DD.MM.YYYY',
                        3 => 'DD/MM/YYYY'
                    ],
                    'default' => '',
                    'view' => 'views/preferences/fields/date-format'
                ],
                'timeFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'HH:mm',
                        1 => 'hh:mma',
                        2 => 'hh:mmA',
                        3 => 'hh:mm A',
                        4 => 'hh:mm a'
                    ],
                    'default' => '',
                    'view' => 'views/preferences/fields/time-format'
                ],
                'weekStart' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 0,
                        1 => 1
                    ],
                    'default' => -1,
                    'view' => 'views/preferences/fields/week-start'
                ],
                'defaultCurrency' => (object) [
                    'type' => 'enum',
                    'default' => '',
                    'view' => 'views/preferences/fields/default-currency'
                ],
                'thousandSeparator' => (object) [
                    'type' => 'varchar',
                    'default' => ',',
                    'maxLength' => 1,
                    'view' => 'views/settings/fields/thousand-separator'
                ],
                'decimalMark' => (object) [
                    'type' => 'varchar',
                    'default' => '.',
                    'required' => true,
                    'maxLength' => 1
                ],
                'dashboardLayout' => (object) [
                    'type' => 'jsonArray',
                    'view' => 'views/settings/fields/dashboard-layout'
                ],
                'dashletsOptions' => (object) [
                    'type' => 'jsonObject'
                ],
                'sharedCalendarUserList' => (object) [
                    'type' => 'jsonArray'
                ],
                'presetFilters' => (object) [
                    'type' => 'jsonObject'
                ],
                'smtpEmailAddress' => (object) [
                    'type' => 'varchar',
                    'readOnly' => true,
                    'notStorable' => true,
                    'view' => 'views/preferences/fields/smtp-email-address',
                    'trim' => true
                ],
                'smtpServer' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'smtpPort' => (object) [
                    'type' => 'int',
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25
                ],
                'smtpAuth' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'smtpSecurity' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS'
                    ]
                ],
                'smtpUsername' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'smtpPassword' => (object) [
                    'type' => 'password'
                ],
                'language' => (object) [
                    'type' => 'enum',
                    'default' => '',
                    'view' => 'views/preferences/fields/language'
                ],
                'exportDelimiter' => (object) [
                    'type' => 'varchar',
                    'default' => ',',
                    'required' => true,
                    'maxLength' => 1
                ],
                'receiveAssignmentEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'receiveMentionEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'receiveStreamEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'autoFollowEntityTypeList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'views/preferences/fields/auto-follow-entity-type-list',
                    'translation' => 'Global.scopeNamesPlural',
                    'notStorable' => true,
                    'tooltip' => true
                ],
                'signature' => (object) [
                    'type' => 'text',
                    'view' => 'views/fields/wysiwyg'
                ],
                'defaultReminders' => (object) [
                    'type' => 'jsonArray',
                    'view' => 'crm:views/meeting/fields/reminders'
                ],
                'theme' => (object) [
                    'type' => 'enum',
                    'view' => 'views/preferences/fields/theme',
                    'translation' => 'Global.themes'
                ],
                'useCustomTabList' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'tabList' => (object) [
                    'type' => 'array',
                    'view' => 'views/preferences/fields/tab-list'
                ],
                'emailReplyToAllByDefault' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'emailReplyForceHtml' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'isPortalUser' => (object) [
                    'type' => 'bool',
                    'notStorable' => true
                ],
                'doNotFillAssignedUserIfNotRequired' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'followEntityOnStreamPost' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'followCreatedEntities' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'followCreatedEntityTypeList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'views/preferences/fields/auto-follow-entity-type-list',
                    'translation' => 'Global.scopeNamesPlural',
                    'tooltip' => true
                ]
            ]
        ],
        'Role' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'maxLength' => 150,
                    'required' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'assignmentPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'all',
                        2 => 'team',
                        3 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ],
                'userPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'all',
                        2 => 'team',
                        3 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ],
                'portalPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'yes',
                        2 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ],
                'groupEmailAccountPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'all',
                        2 => 'team',
                        3 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ],
                'exportPermission' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'not-set',
                        1 => 'yes',
                        2 => 'no'
                    ],
                    'default' => 'not-set',
                    'tooltip' => true,
                    'translation' => 'Role.options.levelList'
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'fieldData' => (object) [
                    'type' => 'jsonObject'
                ]
            ],
            'links' => (object) [
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'roles'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'roles'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'ScheduledJob' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true
                ],
                'job' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'view' => 'views/scheduled-job/fields/job'
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Active',
                        1 => 'Inactive'
                    ]
                ],
                'scheduling' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'view' => 'views/scheduled-job/fields/scheduling',
                    'tooltip' => true
                ],
                'lastRun' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'isInternal' => (object) [
                    'type' => 'bool',
                    'readOnly' => true,
                    'disabled' => true,
                    'default' => false
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'log' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'ScheduledJobLogRecord',
                    'foreign' => 'scheduledJob'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ],
            'jobSchedulingMap' => (object) [
                'CheckInboundEmails' => '*/2 * * * *',
                'CheckEmailAccounts' => '*/1 * * * *',
                'SendEmailReminders' => '*/2 * * * *',
                'Cleanup' => '1 1 * * 0',
                'AuthTokenControl' => '*/6 * * * *',
                'SendEmailNotifications' => '*/2 * * * *',
                'ProcessMassEmail' => '15 * * * *',
                'ControlKnowledgeBaseArticleStatus' => '10 1 * * *'
            ],
            'jobs' => (object) [
                'Dummy' => (object) [
                    'isSystem' => true,
                    'scheduling' => '1 */12 * * *'
                ],
                'CheckNewVersion' => (object) [
                    'name' => 'Check for New Version',
                    'isSystem' => true,
                    'scheduling' => '15 5 * * *'
                ]
            ]
        ],
        'ScheduledJobLogRecord' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'readOnly' => true
                ],
                'status' => (object) [
                    'type' => 'varchar',
                    'readOnly' => true
                ],
                'executionTime' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'scheduledJob' => (object) [
                    'type' => 'link'
                ],
                'target' => (object) [
                    'type' => 'linkParent'
                ]
            ],
            'links' => (object) [
                'scheduledJob' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'ScheduledJob'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'executionTime',
                'asc' => false
            ]
        ],
        'Settings' => (object) [
            'fields' => (object) [
                'useCache' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'recordsPerPage' => (object) [
                    'type' => 'int',
                    'min' => 1,
                    'max' => 200,
                    'default' => 20,
                    'required' => true,
                    'tooltip' => true
                ],
                'recordsPerPageSmall' => (object) [
                    'type' => 'int',
                    'min' => 1,
                    'max' => 100,
                    'default' => 10,
                    'required' => true,
                    'tooltip' => true
                ],
                'timeZone' => (object) [
                    'type' => 'enum',
                    'detault' => 'UTC',
                    'options' => [
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
                        415 => 'Pacific/Wallis'
                    ]
                ],
                'dateFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'MM/DD/YYYY',
                        1 => 'YYYY-MM-DD',
                        2 => 'DD.MM.YYYY',
                        3 => 'DD/MM/YYYY'
                    ],
                    'default' => 'MM/DD/YYYY'
                ],
                'timeFormat' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'HH:mm',
                        1 => 'hh:mma',
                        2 => 'hh:mmA',
                        3 => 'hh:mm A',
                        4 => 'hh:mm a'
                    ],
                    'default' => 'HH:mm'
                ],
                'weekStart' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 0,
                        1 => 1
                    ],
                    'default' => 0
                ],
                'thousandSeparator' => (object) [
                    'type' => 'varchar',
                    'default' => ',',
                    'maxLength' => 1,
                    'view' => 'views/settings/fields/thousand-separator'
                ],
                'decimalMark' => (object) [
                    'type' => 'varchar',
                    'default' => '.',
                    'required' => true,
                    'maxLength' => 1
                ],
                'currencyList' => (object) [
                    'type' => 'multiEnum',
                    'default' => [
                        0 => 'USD',
                        1 => 'EUR'
                    ],
                    'options' => [
                        0 => 'AED',
                        1 => 'ANG',
                        2 => 'ARS',
                        3 => 'AUD',
                        4 => 'BAM',
                        5 => 'BGN',
                        6 => 'BHD',
                        7 => 'BND',
                        8 => 'BOB',
                        9 => 'BRL',
                        10 => 'BWP',
                        11 => 'CAD',
                        12 => 'CHF',
                        13 => 'CLP',
                        14 => 'CNY',
                        15 => 'COP',
                        16 => 'CRC',
                        17 => 'CZK',
                        18 => 'DKK',
                        19 => 'DOP',
                        20 => 'DZD',
                        21 => 'EEK',
                        22 => 'EGP',
                        23 => 'EUR',
                        24 => 'FJD',
                        25 => 'GBP',
                        26 => 'HKD',
                        27 => 'HNL',
                        28 => 'HRK',
                        29 => 'HUF',
                        30 => 'IDR',
                        31 => 'ILS',
                        32 => 'INR',
                        33 => 'JMD',
                        34 => 'JOD',
                        35 => 'JPY',
                        36 => 'KES',
                        37 => 'KRW',
                        38 => 'KWD',
                        39 => 'KYD',
                        40 => 'KZT',
                        41 => 'LBP',
                        42 => 'LKR',
                        43 => 'LTL',
                        44 => 'LVL',
                        45 => 'MAD',
                        46 => 'MDL',
                        47 => 'MKD',
                        48 => 'MUR',
                        49 => 'MXN',
                        50 => 'MYR',
                        51 => 'NAD',
                        52 => 'NGN',
                        53 => 'NIO',
                        54 => 'NOK',
                        55 => 'NPR',
                        56 => 'NZD',
                        57 => 'OMR',
                        58 => 'PEN',
                        59 => 'PGK',
                        60 => 'PHP',
                        61 => 'PKR',
                        62 => 'PLN',
                        63 => 'PYG',
                        64 => 'QAR',
                        65 => 'RON',
                        66 => 'RSD',
                        67 => 'RUB',
                        68 => 'SAR',
                        69 => 'SCR',
                        70 => 'SEK',
                        71 => 'SGD',
                        72 => 'SKK',
                        73 => 'SLL',
                        74 => 'SVC',
                        75 => 'THB',
                        76 => 'TND',
                        77 => 'TRY',
                        78 => 'TTD',
                        79 => 'TWD',
                        80 => 'TZS',
                        81 => 'UAH',
                        82 => 'UGX',
                        83 => 'USD',
                        84 => 'UYU',
                        85 => 'UZS',
                        86 => 'VND',
                        87 => 'YER',
                        88 => 'ZAR',
                        89 => 'ZMK'
                    ],
                    'required' => true
                ],
                'defaultCurrency' => (object) [
                    'type' => 'enum',
                    'default' => 'USD',
                    'required' => true,
                    'view' => 'views/settings/fields/default-currency'
                ],
                'baseCurrency' => (object) [
                    'type' => 'enum',
                    'default' => 'USD',
                    'required' => true,
                    'view' => 'views/settings/fields/default-currency'
                ],
                'currencyRates' => (object) [
                    'type' => 'base',
                    'view' => 'views/settings/fields/currency-rates'
                ],
                'outboundEmailIsShared' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'outboundEmailFromName' => (object) [
                    'type' => 'varchar',
                    'default' => 'EspoCRM',
                    'trim' => true
                ],
                'outboundEmailFromAddress' => (object) [
                    'type' => 'varchar',
                    'default' => 'crm@example.com',
                    'trim' => true
                ],
                'smtpServer' => (object) [
                    'type' => 'varchar'
                ],
                'smtpPort' => (object) [
                    'type' => 'int',
                    'min' => 0,
                    'max' => 9999,
                    'default' => 25
                ],
                'smtpAuth' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'smtpSecurity' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS'
                    ]
                ],
                'smtpUsername' => (object) [
                    'type' => 'varchar',
                    'required' => true
                ],
                'smtpPassword' => (object) [
                    'type' => 'password'
                ],
                'tabList' => (object) [
                    'type' => 'array',
                    'view' => 'views/settings/fields/tab-list'
                ],
                'quickCreateList' => (object) [
                    'type' => 'array',
                    'translation' => 'Global.scopeNames',
                    'view' => 'views/settings/fields/quick-create-list'
                ],
                'language' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'en_US'
                    ],
                    'default' => 'en_US',
                    'view' => 'views/settings/fields/language',
                    'isSorted' => true
                ],
                'globalSearchEntityList' => (object) [
                    'type' => 'multiEnum',
                    'translation' => 'Global.scopeNames',
                    'view' => 'views/settings/fields/global-search-entity-list'
                ],
                'exportDelimiter' => (object) [
                    'type' => 'varchar',
                    'default' => ',',
                    'required' => true,
                    'maxLength' => 1
                ],
                'companyLogo' => (object) [
                    'type' => 'image'
                ],
                'authenticationMethod' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Espo',
                        1 => 'LDAP'
                    ],
                    'default' => 'Espo'
                ],
                'ldapHost' => (object) [
                    'type' => 'varchar',
                    'required' => true
                ],
                'ldapPort' => (object) [
                    'type' => 'varchar',
                    'default' => 389
                ],
                'ldapSecurity' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'SSL',
                        2 => 'TLS'
                    ]
                ],
                'ldapAuth' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'ldapUsername' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapPassword' => (object) [
                    'type' => 'password',
                    'tooltip' => true
                ],
                'ldapBindRequiresDn' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'ldapUserLoginFilter' => (object) [
                    'type' => 'varchar',
                    'tooltip' => true
                ],
                'ldapBaseDn' => (object) [
                    'type' => 'varchar',
                    'tooltip' => true
                ],
                'ldapAccountCanonicalForm' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Dn',
                        1 => 'Username',
                        2 => 'Backslash',
                        3 => 'Principal'
                    ],
                    'tooltip' => true
                ],
                'ldapAccountDomainName' => (object) [
                    'type' => 'varchar',
                    'tooltip' => true
                ],
                'ldapAccountDomainNameShort' => (object) [
                    'type' => 'varchar',
                    'tooltip' => true
                ],
                'ldapAccountFilterFormat' => (object) [
                    'type' => 'varchar'
                ],
                'ldapTryUsernameSplit' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'ldapOptReferrals' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'ldapCreateEspoUser' => (object) [
                    'type' => 'bool',
                    'default' => true,
                    'tooltip' => true
                ],
                'ldapUserNameAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserObjectClass' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserFirstNameAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserLastNameAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserTitleAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserEmailAddressAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserPhoneNumberAttribute' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'tooltip' => true
                ],
                'ldapUserDefaultTeam' => (object) [
                    'type' => 'link',
                    'tooltip' => true,
                    'entity' => 'Team'
                ],
                'ldapUserTeams' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true,
                    'entity' => 'Team'
                ],
                'exportDisabled' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'assignmentEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'assignmentEmailNotificationsEntityList' => (object) [
                    'type' => 'multiEnum',
                    'translation' => 'Global.scopeNamesPlural',
                    'view' => 'views/settings/fields/assignment-email-notifications-entity-list'
                ],
                'assignmentNotificationsEntityList' => (object) [
                    'type' => 'multiEnum',
                    'translation' => 'Global.scopeNamesPlural',
                    'view' => 'views/settings/fields/assignment-notifications-entity-list'
                ],
                'postEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'updateEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'mentionEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'streamEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'portalStreamEmailNotifications' => (object) [
                    'type' => 'bool',
                    'default' => true
                ],
                'streamEmailNotificationsEntityList' => (object) [
                    'type' => 'multiEnum',
                    'translation' => 'Global.scopeNamesPlural',
                    'view' => 'views/settings/fields/stream-email-notifications-entity-list'
                ],
                'b2cMode' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'avatarsDisabled' => (object) [
                    'type' => 'bool',
                    'default' => false
                ],
                'followCreatedEntities' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'adminPanelIframeUrl' => (object) [
                    'type' => 'varchar'
                ],
                'displayListViewRecordCount' => (object) [
                    'type' => 'bool'
                ],
                'userThemesDisabled' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'theme' => (object) [
                    'type' => 'enum',
                    'view' => 'views/settings/fields/theme',
                    'translation' => 'Global.themes'
                ],
                'emailMessageMaxSize' => (object) [
                    'type' => 'float',
                    'min' => 0,
                    'tooltip' => true
                ],
                'inboundEmailMaxPortionSize' => (object) [
                    'type' => 'int',
                    'min' => 1,
                    'max' => 500
                ],
                'personalEmailMaxPortionSize' => (object) [
                    'type' => 'int',
                    'min' => 1,
                    'max' => 500
                ],
                'maxEmailAccountCount' => (object) [
                    'type' => 'int'
                ],
                'massEmailMaxPerHourCount' => (object) [
                    'type' => 'int',
                    'min' => 1
                ],
                'authTokenLifetime' => (object) [
                    'type' => 'float',
                    'min' => 0,
                    'default' => 0,
                    'tooltip' => true
                ],
                'authTokenMaxIdleTime' => (object) [
                    'type' => 'float',
                    'min' => 0,
                    'default' => 0,
                    'tooltip' => true
                ],
                'dashboardLayout' => (object) [
                    'type' => 'jsonArray',
                    'view' => 'views/settings/fields/dashboard-layout'
                ],
                'dashletsOptions' => (object) [
                    'type' => 'jsonObject',
                    'disabled' => true
                ],
                'siteUrl' => (object) [
                    'type' => 'varchar'
                ],
                'applicationName' => (object) [
                    'type' => 'varchar'
                ],
                'readableDateFormatDisabled' => (object) [
                    'type' => 'bool'
                ],
                'addressFormat' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4
                    ]
                ],
                'addressPreview' => (object) [
                    'type' => 'address',
                    'notStorable' => true,
                    'readOnly' => true,
                    'view' => 'views/settings/fields/address-preview'
                ],
                'currencyFormat' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 1,
                        1 => 2
                    ]
                ],
                'currencyDecimalPlaces' => (object) [
                    'type' => 'int',
                    'tooltip' => true,
                    'min' => 0,
                    'max' => 20
                ],
                'notificationSoundsDisabled' => (object) [
                    'type' => 'bool'
                ],
                'calendarEntityList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'views/settings/fields/calendar-entity-list'
                ],
                'activitiesEntityList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'views/settings/fields/activities-entity-list'
                ],
                'historyEntityList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'views/settings/fields/history-entity-list'
                ],
                'googleMapsApiKey' => (object) [
                    'type' => 'varchar'
                ],
                'massEmailDisableMandatoryOptOutLink' => (object) [
                    'type' => 'bool'
                ],
                'aclStrictMode' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'aclAllowDeleteCreated' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'lastViewedCount' => (object) [
                    'type' => 'int',
                    'min' => 1,
                    'max' => 200,
                    'default' => 20,
                    'required' => true
                ],
                'adminNotifications' => (object) [
                    'type' => 'bool'
                ],
                'adminNotificationsNewVersion' => (object) [
                    'type' => 'bool'
                ],
                'addressPreviewStreet' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar'
                ],
                'addressPreviewCity' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPreviewState' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPreviewCountry' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPreviewPostalCode' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPreviewMap' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'map',
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ]
            ]
        ],
        'Team' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'trim' => true
                ],
                'roles' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'positionList' => (object) [
                    'type' => 'array',
                    'tooltip' => true
                ],
                'userRole' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'teams'
                ],
                'roles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Role',
                    'foreign' => 'teams'
                ],
                'notes' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Note',
                    'foreign' => 'teams'
                ],
                'inboundEmails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'InboundEmail',
                    'foreign' => 'teams'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'Template' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'body' => (object) [
                    'type' => 'text',
                    'view' => 'views/fields/wysiwyg'
                ],
                'header' => (object) [
                    'type' => 'text',
                    'view' => 'views/fields/wysiwyg'
                ],
                'footer' => (object) [
                    'type' => 'text',
                    'view' => 'views/fields/wysiwyg',
                    'tooltip' => true
                ],
                'entityType' => (object) [
                    'type' => 'enum',
                    'required' => true,
                    'translation' => 'Global.scopeNames',
                    'view' => 'views/fields/entity-type'
                ],
                'leftMargin' => (object) [
                    'type' => 'float',
                    'default' => 10
                ],
                'rightMargin' => (object) [
                    'type' => 'float',
                    'default' => 10
                ],
                'topMargin' => (object) [
                    'type' => 'float',
                    'default' => 10
                ],
                'bottomMargin' => (object) [
                    'type' => 'float',
                    'default' => 25
                ],
                'printFooter' => (object) [
                    'type' => 'bool'
                ],
                'footerPosition' => (object) [
                    'type' => 'float',
                    'default' => 15
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'variables' => (object) [
                    'type' => 'base',
                    'notStorable' => true,
                    'tooltip' => true
                ]
            ],
            'links' => (object) [
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'UniqueId' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'index' => true
                ],
                'data' => (object) [
                    'type' => 'jsonObject'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'User' => (object) [
            'fields' => (object) [
                'isAdmin' => (object) [
                    'type' => 'bool',
                    'tooltip' => true
                ],
                'userName' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 50,
                    'required' => true,
                    'view' => 'views/user/fields/user-name',
                    'tooltip' => true
                ],
                'name' => (object) [
                    'type' => 'personName',
                    'view' => 'views/user/fields/name'
                ],
                'password' => (object) [
                    'type' => 'password',
                    'maxLength' => 150,
                    'internal' => true,
                    'disabled' => true
                ],
                'passwordConfirm' => (object) [
                    'type' => 'password',
                    'maxLength' => 150,
                    'internal' => true,
                    'disabled' => true,
                    'notStorable' => true
                ],
                'salutationName' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Ms.',
                        3 => 'Mrs.',
                        4 => 'Dr.'
                    ]
                ],
                'firstName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'default' => ''
                ],
                'lastName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'required' => true,
                    'default' => ''
                ],
                'isActive' => (object) [
                    'type' => 'bool',
                    'tooltip' => true,
                    'default' => true
                ],
                'isPortalUser' => (object) [
                    'type' => 'bool'
                ],
                'isSuperAdmin' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'disabled' => true
                ],
                'title' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'trim' => true
                ],
                'position' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100,
                    'notStorable' => true,
                    'where' => (object) [
                        'LIKE' => (object) [
                            'leftJoins' => [
                                0 => [
                                    0 => 'teams',
                                    1 => 'teamsPosition'
                                ]
                            ],
                            'sql' => 'teamsPositionMiddle.role LIKE {value}',
                            'distinct' => true
                        ],
                        '=' => (object) [
                            'leftJoins' => [
                                0 => [
                                    0 => 'teams',
                                    1 => 'teamsPosition'
                                ]
                            ],
                            'sql' => 'teamsPositionMiddle.role = {value}',
                            'distinct' => true
                        ],
                        '<>' => (object) [
                            'leftJoins' => [
                                0 => [
                                    0 => 'teams',
                                    1 => 'teamsPosition'
                                ]
                            ],
                            'sql' => 'teamsPositionMiddle.role <> {value}',
                            'distinct' => true
                        ],
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => [
                                    0 => 'teams',
                                    1 => 'teamsPosition'
                                ]
                            ],
                            'sql' => 'teamsPositionMiddle.role IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => (object) [
                            'leftJoins' => [
                                0 => [
                                    0 => 'teams',
                                    1 => 'teamsPosition'
                                ]
                            ],
                            'sql' => 'teamsPositionMiddle.role IS NOT NULL',
                            'distinct' => true
                        ]
                    ],
                    'trim' => true,
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'email',
                    'required' => false
                ],
                'phoneNumber' => (object) [
                    'type' => 'phone',
                    'typeList' => [
                        0 => 'Mobile',
                        1 => 'Office',
                        2 => 'Home',
                        3 => 'Fax',
                        4 => 'Other'
                    ],
                    'defaultType' => 'Mobile'
                ],
                'token' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'authTokenId' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'ipAddress' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'defaultTeam' => (object) [
                    'type' => 'link',
                    'tooltip' => true
                ],
                'acceptanceStatus' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'exportDisabled' => true,
                    'disabled' => true
                ],
                'acceptanceStatusMeetings' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'meetings',
                    'column' => 'status'
                ],
                'acceptanceStatusCalls' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'calls',
                    'column' => 'status'
                ],
                'teamRole' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true,
                    'columns' => (object) [
                        'role' => 'userRole'
                    ],
                    'view' => 'views/user/fields/teams',
                    'default' => 'javascript: return {teamsIds: []}'
                ],
                'roles' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'portals' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'portalRoles' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'contact' => (object) [
                    'type' => 'link',
                    'view' => 'views/user/fields/contact'
                ],
                'accounts' => (object) [
                    'type' => 'linkMultiple'
                ],
                'account' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'readOnly' => true
                ],
                'portal' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'avatar' => (object) [
                    'type' => 'image',
                    'view' => 'views/user/fields/avatar',
                    'previewSize' => 'small'
                ],
                'sendAccessInfo' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'gender' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Male',
                        2 => 'Female',
                        3 => 'Neutral'
                    ],
                    'default' => ''
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ]
            ],
            'links' => (object) [
                'defaultTeam' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Team'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'foreign' => 'users',
                    'additionalColumns' => (object) [
                        'role' => (object) [
                            'type' => 'varchar',
                            'len' => 100
                        ]
                    ],
                    'layoutRelationshipsDisabled' => true
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'roles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Role',
                    'foreign' => 'users',
                    'layoutRelationshipsDisabled' => true
                ],
                'portals' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Portal',
                    'foreign' => 'users',
                    'layoutRelationshipsDisabled' => true
                ],
                'portalRoles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'PortalRole',
                    'foreign' => 'users',
                    'layoutRelationshipsDisabled' => true
                ],
                'preferences' => (object) [
                    'type' => 'hasOne',
                    'entity' => 'Preferences'
                ],
                'meetings' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'users'
                ],
                'calls' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'users'
                ],
                'emails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Email',
                    'foreign' => 'users'
                ],
                'notes' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Note',
                    'foreign' => 'users',
                    'layoutRelationshipsDisabled' => true
                ],
                'contact' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Contact',
                    'foreign' => 'portalUser'
                ],
                'accounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Account',
                    'foreign' => 'portalUsers',
                    'relationName' => 'AccountPortalUser'
                ],
                'tasks' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Task',
                    'foreign' => 'assignedUser'
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'users'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'userName',
                'asc' => true,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'userName'
                ]
            ]
        ],
        'Account' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'website' => (object) [
                    'type' => 'url'
                ],
                'emailAddress' => (object) [
                    'type' => 'email'
                ],
                'phoneNumber' => (object) [
                    'type' => 'phone',
                    'typeList' => [
                        0 => 'Office',
                        1 => 'Mobile',
                        2 => 'Fax',
                        3 => 'Other'
                    ],
                    'defaultType' => 'Office'
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Customer',
                        2 => 'Investor',
                        3 => 'Partner',
                        4 => 'Reseller'
                    ],
                    'default' => ''
                ],
                'industry' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Advertising',
                        2 => 'Aerospace',
                        3 => 'Agriculture',
                        4 => 'Apparel & Accessories',
                        5 => 'Architecture',
                        6 => 'Automotive',
                        7 => 'Banking',
                        8 => 'Biotechnology',
                        9 => 'Building Materials & Equipment',
                        10 => 'Chemical',
                        11 => 'Construction',
                        12 => 'Consulting',
                        13 => 'Computer',
                        14 => 'Culture',
                        15 => 'Creative',
                        16 => 'Defense',
                        17 => 'Education',
                        18 => 'Electronics',
                        19 => 'Electric Power',
                        20 => 'Energy',
                        21 => 'Entertainment & Leisure',
                        22 => 'Finance',
                        23 => 'Food & Beverage',
                        24 => 'Grocery',
                        25 => 'Healthcare',
                        26 => 'Hospitality',
                        27 => 'Insurance',
                        28 => 'Legal',
                        29 => 'Manufacturing',
                        30 => 'Mass Media',
                        31 => 'Marketing',
                        32 => 'Mining',
                        33 => 'Music',
                        34 => 'Publishing',
                        35 => 'Petroleum',
                        36 => 'Real Estate',
                        37 => 'Retail',
                        38 => 'Service',
                        39 => 'Sports',
                        40 => 'Software',
                        41 => 'Support',
                        42 => 'Shipping',
                        43 => 'Travel',
                        44 => 'Technology',
                        45 => 'Telecommunications',
                        46 => 'Television',
                        47 => 'Transportation',
                        48 => 'Testing, Inspection & Certification',
                        49 => 'Venture Capital',
                        50 => 'Wholesale',
                        51 => 'Water'
                    ],
                    'default' => '',
                    'isSorted' => true
                ],
                'sicCode' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 40,
                    'trim' => true
                ],
                'contactRole' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'disabled' => true
                ],
                'contactIsInactive' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'default' => false,
                    'disabled' => true
                ],
                'billingAddress' => (object) [
                    'type' => 'address',
                    'trim' => true
                ],
                'billingAddressStreet' => (object) [
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar'
                ],
                'billingAddressCity' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'billingAddressState' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'billingAddressCountry' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'billingAddressPostalCode' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'shippingAddress' => (object) [
                    'type' => 'address',
                    'view' => 'crm:views/account/fields/shipping-address'
                ],
                'shippingAddressStreet' => (object) [
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar',
                    'trim' => true
                ],
                'shippingAddressCity' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'shippingAddressState' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'shippingAddressCountry' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'shippingAddressPostalCode' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'campaign' => (object) [
                    'type' => 'link',
                    'layoutListDisabled' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'targetLists' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'importDisabled' => true,
                    'exportDisabled' => true,
                    'noLoad' => true
                ],
                'targetList' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'entity' => 'TargetList'
                ],
                'originalLead' => (object) [
                    'type' => 'link',
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'readOnly' => true,
                    'view' => 'views/fields/link-one'
                ],
                'billingAddressMap' => (object) [
                    'type' => 'map',
                    'notStorable' => true,
                    'readOnly' => true,
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ],
                'shippingAddressMap' => (object) [
                    'type' => 'map',
                    'notStorable' => true,
                    'readOnly' => true,
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'accounts'
                ],
                'opportunities' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'account'
                ],
                'cases' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'account'
                ],
                'documents' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Document',
                    'foreign' => 'accounts',
                    'audited' => true
                ],
                'meetingsPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'account',
                    'layoutRelationshipsDisabled' => true
                ],
                'emailsPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Email',
                    'foreign' => 'account',
                    'layoutRelationshipsDisabled' => true
                ],
                'callsPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'account',
                    'layoutRelationshipsDisabled' => true
                ],
                'tasksPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Task',
                    'foreign' => 'account',
                    'layoutRelationshipsDisabled' => true
                ],
                'meetings' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'calls' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'tasks' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'emails' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'accounts',
                    'noJoin' => true
                ],
                'campaignLogRecords' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'CampaignLogRecord',
                    'foreign' => 'parent'
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'accounts'
                ],
                'portalUsers' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'accounts'
                ],
                'originalLead' => (object) [
                    'type' => 'hasOne',
                    'entity' => 'Lead',
                    'foreign' => 'createdAccount'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'emailAddress'
                ]
            ],
            'indexes' => (object) [
                'name' => (object) [
                    'columns' => [
                        0 => 'name',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'Call' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Planned',
                        1 => 'Held',
                        2 => 'Not Held'
                    ],
                    'default' => 'Planned',
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Held' => 'success'
                    ],
                    'audited' => true
                ],
                'dateStart' => (object) [
                    'type' => 'datetime',
                    'required' => true,
                    'default' => 'javascript: return this.dateTime.getNow(15);',
                    'audited' => true
                ],
                'dateEnd' => (object) [
                    'type' => 'datetime',
                    'required' => true,
                    'after' => 'dateStart'
                ],
                'duration' => (object) [
                    'type' => 'duration',
                    'start' => 'dateStart',
                    'end' => 'dateEnd',
                    'options' => [
                        0 => 300,
                        1 => 600,
                        2 => 900,
                        3 => 1800,
                        4 => 2700,
                        5 => 3600,
                        6 => 7200
                    ],
                    'default' => 300,
                    'notStorable' => true,
                    'select' => 'TIMESTAMPDIFF(SECOND, call.date_start, call.date_end)',
                    'orderBy' => 'duration {direction}'
                ],
                'reminders' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'view' => 'crm:views/meeting/fields/reminders',
                    'layoutListDisabled' => true
                ],
                'direction' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Outbound',
                        1 => 'Inbound'
                    ],
                    'default' => 'Outbound'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'parent' => (object) [
                    'type' => 'linkParent',
                    'entityList' => [
                        0 => 'Account',
                        1 => 'Lead',
                        2 => 'Contact',
                        3 => 'Opportunity',
                        4 => 'Case'
                    ]
                ],
                'account' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'acceptanceStatus' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'options' => [
                        0 => 'None',
                        1 => 'Accepted',
                        2 => 'Tentative',
                        3 => 'Declined'
                    ],
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'where' => (object) [
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads'
                            ],
                            'sql' => 'contactsMiddle.status = {value} OR leadsMiddle.status = {value} OR usersMiddle.status = {value}',
                            'distinct' => true
                        ],
                        '<>' => 'call.id NOT IN (SELECT call_id FROM call_contact WHERE deleted = 0 AND status = {value}) AND call.id NOT IN (SELECT call_id FROM call_user WHERE deleted = 0 AND status = {value}) AND call.id NOT IN (SELECT call_id FROM call_lead WHERE deleted = 0 AND status = {value})',
                        'IN' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'leads',
                                2 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.status IN {value} OR leadsMiddle.status IN {value} OR usersMiddle.status IN {value}',
                            'distinct' => true
                        ],
                        'NOT IN' => 'call.id NOT IN (SELECT call_id FROM call_contact WHERE deleted = 0 AND status IN {value}) AND call.id NOT IN (SELECT call_id FROM call_user WHERE deleted = 0 AND status IN {value}) AND call.id NOT IN (SELECT call_id FROM call_lead WHERE deleted = 0 AND status IN {value})',
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads'
                            ],
                            'sql' => 'contactsMiddle.status IS NULL AND leadsMiddle.status IS NULL AND usersMiddle.status IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => 'call.id NOT IN (SELECT call_id FROM call_contact WHERE deleted = 0 AND status IS NULL) OR call.id NOT IN (SELECT call_id FROM call_user WHERE deleted = 0 AND status IS NULL) OR call.id NOT IN (SELECT call_id FROM call_lead WHERE deleted = 0 AND status IS NULL)'
                    ],
                    'view' => 'crm:views/meeting/fields/acceptance-status'
                ],
                'users' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'view' => 'crm:views/meeting/fields/users',
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'contacts' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'view' => 'crm:views/meeting/fields/contacts',
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'leads' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'view' => 'crm:views/meeting/fields/attendees',
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => true,
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ]
            ],
            'links' => (object) [
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'calls',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'calls',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'leads' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'calls',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'foreign' => 'calls'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'dateStart',
                'asc' => false
            ],
            'indexes' => (object) [
                'dateStartStatus' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'status'
                    ]
                ],
                'dateStart' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'deleted'
                    ]
                ],
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'assignedUserStatus' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'status'
                    ]
                ]
            ]
        ],
        'Campaign' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Planning',
                        1 => 'Active',
                        2 => 'Inactive',
                        3 => 'Complete'
                    ],
                    'default' => 'Planning'
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Email',
                        1 => 'Newsletter',
                        2 => 'Web',
                        3 => 'Television',
                        4 => 'Radio',
                        5 => 'Mail'
                    ],
                    'default' => 'Email'
                ],
                'startDate' => (object) [
                    'type' => 'date'
                ],
                'endDate' => (object) [
                    'type' => 'date'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'targetLists' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'excludingTargetLists' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'sentCount' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'openedCount' => (object) [
                    'view' => 'crm:views/campaign/fields/int-with-percentage',
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'clickedCount' => (object) [
                    'view' => 'crm:views/campaign/fields/int-with-percentage',
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'optedOutCount' => (object) [
                    'view' => 'crm:views/campaign/fields/int-with-percentage',
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'bouncedCount' => (object) [
                    'view' => 'crm:views/campaign/fields/int-with-percentage',
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'hardBouncedCount' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'softBouncedCount' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'leadCreatedCount' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'openedPercentage' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'clickedPercentage' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'optedOutPercentage' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'bouncedPercentage' => (object) [
                    'type' => 'int',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'revenue' => (object) [
                    'type' => 'currency',
                    'notStorable' => true,
                    'readOnly' => true,
                    'disabled' => true
                ],
                'budget' => (object) [
                    'type' => 'currency'
                ],
                'revenueCurrency' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'varchar',
                    'disabled' => true
                ],
                'revenueConverted' => (object) [
                    'notStorable' => true,
                    'readOnly' => true,
                    'type' => 'currencyConverted'
                ],
                'budgetCurrency' => (object) [
                    'type' => 'varchar',
                    'disabled' => true
                ],
                'budgetConverted' => (object) [
                    'type' => 'currencyConverted',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'campaigns'
                ],
                'excludingTargetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'campaignsExcluding',
                    'relationName' => 'campaignTargetListExcluding'
                ],
                'accounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Account',
                    'foreign' => 'campaign'
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'campaign'
                ],
                'leads' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'campaign'
                ],
                'opportunities' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'campaign'
                ],
                'campaignLogRecords' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'CampaignLogRecord',
                    'foreign' => 'campaign'
                ],
                'trackingUrls' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'CampaignTrackingUrl',
                    'foreign' => 'campaign'
                ],
                'massEmails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'MassEmail',
                    'foreign' => 'campaign',
                    'layoutRelationshipsDisabled' => true
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'createdAt' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'CampaignLogRecord' => (object) [
            'fields' => (object) [
                'action' => (object) [
                    'type' => 'enum',
                    'required' => true,
                    'maxLength' => 50,
                    'options' => [
                        0 => 'Sent',
                        1 => 'Opened',
                        2 => 'Opted Out',
                        3 => 'Bounced',
                        4 => 'Clicked',
                        5 => 'Lead Created'
                    ]
                ],
                'actionDate' => (object) [
                    'type' => 'datetime',
                    'required' => true
                ],
                'data' => (object) [
                    'type' => 'jsonObject',
                    'view' => 'crm:views/campaign-log-record/fields/data'
                ],
                'stringData' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'stringAdditionalData' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'application' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'maxLength' => 36,
                    'default' => 'Espo'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'campaign' => (object) [
                    'type' => 'link'
                ],
                'parent' => (object) [
                    'type' => 'linkParent'
                ],
                'object' => (object) [
                    'type' => 'linkParent'
                ],
                'queueItem' => (object) [
                    'type' => 'link'
                ],
                'isTest' => (object) [
                    'type' => 'bool',
                    'default' => false
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'campaignLogRecords'
                ],
                'queueItem' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailQueueItem',
                    'noJoin' => true
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'entityList' => [
                        0 => 'Account',
                        1 => 'Contact',
                        2 => 'Lead',
                        3 => 'Opportunity',
                        4 => 'User'
                    ]
                ],
                'object' => (object) [
                    'type' => 'belongsToParent',
                    'entityList' => [
                        0 => 'Email',
                        1 => 'CampaignTrackingUrl'
                    ]
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'actionDate',
                'asc' => false
            ],
            'indexes' => (object) [
                'actionDate' => (object) [
                    'columns' => [
                        0 => 'actionDate',
                        1 => 'deleted'
                    ]
                ],
                'action' => (object) [
                    'columns' => [
                        0 => 'action',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'CampaignTrackingUrl' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'url' => (object) [
                    'type' => 'url',
                    'required' => true
                ],
                'urlToUse' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'readOnly' => true
                ],
                'campaign' => (object) [
                    'type' => 'link',
                    'required' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'trackingUrls'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true
            ]
        ],
        'Case' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'number' => (object) [
                    'type' => 'autoincrement',
                    'index' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'New',
                        1 => 'Assigned',
                        2 => 'Pending',
                        3 => 'Closed',
                        4 => 'Rejected',
                        5 => 'Duplicate'
                    ],
                    'default' => 'New',
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Closed' => 'success',
                        'Duplicate' => 'danger',
                        'Rejected' => 'danger'
                    ],
                    'audited' => true
                ],
                'priority' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Low',
                        1 => 'Normal',
                        2 => 'High',
                        3 => 'Urgent'
                    ],
                    'default' => 'Normal',
                    'audited' => true
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Question',
                        2 => 'Incident',
                        3 => 'Problem'
                    ],
                    'default' => '',
                    'audited' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'account' => (object) [
                    'type' => 'link'
                ],
                'lead' => (object) [
                    'type' => 'link'
                ],
                'contact' => (object) [
                    'type' => 'link',
                    'view' => 'crm:views/case/fields/contact'
                ],
                'contacts' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'crm:views/case/fields/contacts'
                ],
                'inboundEmail' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple',
                    'layoutListDisabled' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'inboundEmail' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'InboundEmail'
                ],
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                    'foreign' => 'cases'
                ],
                'lead' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Lead',
                    'foreign' => 'cases'
                ],
                'contact' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Contact',
                    'foreign' => 'casesPrimary'
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'cases',
                    'layoutRelationshipsDisabled' => true
                ],
                'meetings' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'calls' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'tasks' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'emails' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'articles' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'KnowledgeBaseArticle',
                    'foreign' => 'cases',
                    'audited' => true
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'number',
                'asc' => false
            ],
            'indexes' => (object) [
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'assignedUserStatus' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'status'
                    ]
                ]
            ]
        ],
        'Contact' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'personName'
                ],
                'salutationName' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Ms.',
                        3 => 'Mrs.',
                        4 => 'Dr.'
                    ]
                ],
                'firstName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'default' => ''
                ],
                'lastName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'required' => true,
                    'default' => ''
                ],
                'accountId' => (object) [
                    'type' => 'varchar',
                    'where' => (object) [
                        '=' => 'contact.id IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id = {value})',
                        '<>' => 'contact.id NOT IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id = {value})',
                        'IN' => 'contact.id IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id IN {value})',
                        'NOT IN' => 'contact.id NOT IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id IN {value})',
                        'IS NULL' => 'contact.account_id IS NULL',
                        'IS NOT NULL' => 'contact.account_id IS NOT NULL'
                    ],
                    'disabled' => true
                ],
                'title' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 50,
                    'notStorable' => true,
                    'select' => 'accountContact.role',
                    'orderBy' => 'accountContact.role {direction}',
                    'where' => (object) [
                        'LIKE' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.role LIKE {value}',
                            'distinct' => true
                        ],
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.role = {value}',
                            'distinct' => true
                        ],
                        '<>' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.role <> {value}',
                            'distinct' => true
                        ],
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.role IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.role IS NOT NULL',
                            'distinct' => true
                        ]
                    ],
                    'trim' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'emailAddress' => (object) [
                    'type' => 'email'
                ],
                'phoneNumber' => (object) [
                    'type' => 'phone',
                    'typeList' => [
                        0 => 'Mobile',
                        1 => 'Office',
                        2 => 'Home',
                        3 => 'Fax',
                        4 => 'Other'
                    ],
                    'defaultType' => 'Mobile'
                ],
                'doNotCall' => (object) [
                    'type' => 'bool'
                ],
                'address' => (object) [
                    'type' => 'address'
                ],
                'addressStreet' => (object) [
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar'
                ],
                'addressCity' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressState' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressCountry' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPostalCode' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'account' => (object) [
                    'type' => 'link',
                    'view' => 'crm:views/contact/fields/account'
                ],
                'accounts' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'crm:views/contact/fields/accounts',
                    'columns' => (object) [
                        'role' => 'contactRole',
                        'isInactive' => 'contactIsInactive'
                    ]
                ],
                'accountRole' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'exportDisabled' => true,
                    'importDisabled' => true,
                    'view' => 'crm:views/contact/fields/account-role'
                ],
                'accountIsInactive' => (object) [
                    'type' => 'bool',
                    'notStorable' => true,
                    'select' => 'accountContact.is_inactive',
                    'orderBy' => 'accountContact.is_inactive {direction}',
                    'where' => (object) [
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.is_inactive = {value}',
                            'distinct' => true
                        ],
                        '<>' => (object) [
                            'leftJoins' => [
                                0 => 'accounts'
                            ],
                            'sql' => 'accountsMiddle.is_inactive <> {value}',
                            'distinct' => true
                        ]
                    ],
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true
                ],
                'accountType' => (object) [
                    'type' => 'foreign',
                    'link' => 'account',
                    'field' => 'type',
                    'readOnly' => true,
                    'view' => 'views/fields/foreign-enum'
                ],
                'opportunityRole' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'options' => [
                        0 => '',
                        1 => 'Decision Maker',
                        2 => 'Evaluator',
                        3 => 'Influencer'
                    ],
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'where' => (object) [
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'opportunities'
                            ],
                            'sql' => 'opportunitiesMiddle.role = {value}',
                            'distinct' => true
                        ],
                        '<>' => 'contact.id NOT IN (SELECT contact_id FROM contact_opportunity WHERE deleted = 0 AND role = {value})',
                        'IN' => (object) [
                            'leftJoins' => [
                                0 => 'opportunities'
                            ],
                            'sql' => 'opportunitiesMiddle.role IN {value}',
                            'distinct' => true
                        ],
                        'NOT IN' => 'contact.id NOT IN (SELECT contact_id FROM contact_opportunity WHERE deleted = 0 AND role IN {value})',
                        'LIKE' => (object) [
                            'leftJoins' => [
                                0 => 'opportunities'
                            ],
                            'sql' => 'opportunitiesMiddle.role LIKE {value}',
                            'distinct' => true
                        ],
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => 'opportunities'
                            ],
                            'sql' => 'opportunitiesMiddle.role IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => 'contact.id NOT IN (SELECT contact_id FROM contact_opportunity WHERE deleted = 0 AND role IS NULL)'
                    ],
                    'view' => 'crm:views/contact/fields/opportunity-role'
                ],
                'acceptanceStatus' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'exportDisabled' => true,
                    'disabled' => true
                ],
                'acceptanceStatusMeetings' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'meetings',
                    'column' => 'status'
                ],
                'acceptanceStatusCalls' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'calls',
                    'column' => 'status'
                ],
                'campaign' => (object) [
                    'type' => 'link',
                    'layoutListDisabled' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'targetLists' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'importDisabled' => true,
                    'noLoad' => true
                ],
                'targetList' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'exportDisabled' => true,
                    'entity' => 'TargetList'
                ],
                'portalUser' => (object) [
                    'type' => 'link',
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'readOnly' => true,
                    'notStorable' => true,
                    'view' => 'views/fields/link-one'
                ],
                'originalLead' => (object) [
                    'type' => 'link',
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'readOnly' => true,
                    'view' => 'views/fields/link-one'
                ],
                'addressMap' => (object) [
                    'type' => 'map',
                    'notStorable' => true,
                    'readOnly' => true,
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account'
                ],
                'accounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Account',
                    'foreign' => 'contacts',
                    'additionalColumns' => (object) [
                        'role' => (object) [
                            'type' => 'varchar',
                            'len' => 50
                        ],
                        'isInactive' => (object) [
                            'type' => 'bool',
                            'default' => false
                        ]
                    ],
                    'layoutRelationshipsDisabled' => true
                ],
                'opportunities' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'contacts'
                ],
                'casesPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'contact',
                    'layoutRelationshipsDisabled' => true
                ],
                'cases' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'contacts'
                ],
                'meetings' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'contacts',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'calls' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'contacts',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'tasks' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'emails' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'contacts',
                    'noJoin' => true
                ],
                'campaignLogRecords' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'CampaignLogRecord',
                    'foreign' => 'parent'
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'contacts'
                ],
                'portalUser' => (object) [
                    'type' => 'hasOne',
                    'entity' => 'User',
                    'foreign' => 'contact'
                ],
                'originalLead' => (object) [
                    'type' => 'hasOne',
                    'entity' => 'Lead',
                    'foreign' => 'createdContact'
                ],
                'documents' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Document',
                    'foreign' => 'contacts',
                    'audited' => true
                ],
                'tasksPrimary' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Task',
                    'foreign' => 'contact',
                    'layoutRelationshipsDisabled' => true
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'name',
                'asc' => true,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'emailAddress'
                ]
            ],
            'indexes' => (object) [
                'firstName' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'deleted'
                    ]
                ],
                'name' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'lastName'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'Document' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'view' => 'crm:views/document/fields/name',
                    'trim' => true
                ],
                'file' => (object) [
                    'type' => 'file',
                    'required' => true,
                    'view' => 'crm:views/document/fields/file'
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Active',
                        1 => 'Draft',
                        2 => 'Expired',
                        3 => 'Canceled'
                    ],
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Canceled' => 'danger',
                        'Expired' => 'danger'
                    ]
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Contract',
                        2 => 'NDA',
                        3 => 'EULA',
                        4 => 'License Agreement'
                    ]
                ],
                'publishDate' => (object) [
                    'type' => 'date',
                    'required' => true,
                    'default' => 'javascript: return this.dateTime.getToday();'
                ],
                'expirationDate' => (object) [
                    'type' => 'date',
                    'after' => 'publishDate'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'accounts' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'importDisabled' => true,
                    'noLoad' => true
                ],
                'folder' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/link-category-tree'
                ]
            ],
            'links' => (object) [
                'accounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Account',
                    'foreign' => 'documents'
                ],
                'opportunities' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Opportunity',
                    'foreign' => 'documents'
                ],
                'leads' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'documents'
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'documents'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'folder' => (object) [
                    'type' => 'belongsTo',
                    'foreign' => 'documents',
                    'entity' => 'DocumentFolder'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'DocumentFolder' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'parent' => (object) [
                    'type' => 'link'
                ],
                'childList' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'parent' => (object) [
                    'type' => 'belongsTo',
                    'foreign' => 'children',
                    'entity' => 'DocumentFolder'
                ],
                'children' => (object) [
                    'type' => 'hasMany',
                    'foreign' => 'parent',
                    'entity' => 'DocumentFolder'
                ],
                'documents' => (object) [
                    'type' => 'hasMany',
                    'foreign' => 'folder',
                    'entity' => 'Document'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'parent',
                'asc' => true
            ],
            'additionalTables' => (object) [
                'DocumentFolderPath' => (object) [
                    'fields' => (object) [
                        'id' => (object) [
                            'type' => 'id',
                            'dbType' => 'int',
                            'len' => '11',
                            'autoincrement' => true,
                            'unique' => true
                        ],
                        'ascendorId' => (object) [
                            'type' => 'varchar',
                            'len' => '100',
                            'index' => true
                        ],
                        'descendorId' => (object) [
                            'type' => 'varchar',
                            'len' => '24',
                            'index' => true
                        ]
                    ]
                ]
            ]
        ],
        'EmailQueueItem' => (object) [
            'fields' => (object) [
                'massEmail' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Pending',
                        1 => 'Sent',
                        2 => 'Failed',
                        3 => 'Sending'
                    ],
                    'readOnly' => true
                ],
                'attemptCount' => (object) [
                    'type' => 'int',
                    'readOnly' => true,
                    'default' => 0
                ],
                'target' => (object) [
                    'type' => 'linkParent',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'sentAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'varchar',
                    'readOnly' => true
                ],
                'isTest' => (object) [
                    'type' => 'bool'
                ]
            ],
            'links' => (object) [
                'massEmail' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'MassEmail',
                    'foreign' => 'queueItems'
                ],
                'target' => (object) [
                    'type' => 'belongsToParent'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'KnowledgeBaseArticle' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Draft',
                        1 => 'In Review',
                        2 => 'Published',
                        3 => 'Archived'
                    ],
                    'view' => 'crm:views/knowledge-base-article/fields/status',
                    'default' => 'Draft'
                ],
                'language' => (object) [
                    'type' => 'enum',
                    'view' => 'crm:views/knowledge-base-article/fields/language',
                    'default' => ''
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Article'
                    ]
                ],
                'portals' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'publishDate' => (object) [
                    'type' => 'date'
                ],
                'expirationDate' => (object) [
                    'type' => 'date',
                    'after' => 'publishDate'
                ],
                'order' => (object) [
                    'type' => 'int',
                    'disableFormatting' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'categories' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/link-multiple-category-tree'
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple'
                ],
                'body' => (object) [
                    'type' => 'wysiwyg'
                ]
            ],
            'links' => (object) [
                'cases' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'articles'
                ],
                'portals' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Portal',
                    'foreign' => 'articles'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'categories' => (object) [
                    'type' => 'hasMany',
                    'foreign' => 'articles',
                    'entity' => 'KnowledgeBaseCategory'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'order',
                'asc' => true
            ]
        ],
        'KnowledgeBaseCategory' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'order' => (object) [
                    'type' => 'int',
                    'required' => true,
                    'disableFormatting' => true
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'parent' => (object) [
                    'type' => 'link'
                ],
                'childList' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'parent' => (object) [
                    'type' => 'belongsTo',
                    'foreign' => 'children',
                    'entity' => 'KnowledgeBaseCategory'
                ],
                'children' => (object) [
                    'type' => 'hasMany',
                    'foreign' => 'parent',
                    'entity' => 'KnowledgeBaseCategory'
                ],
                'articles' => (object) [
                    'type' => 'hasMany',
                    'foreign' => 'categories',
                    'entity' => 'KnowledgeBaseArticle'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'parent',
                'sortByByColumn' => 'parentId',
                'asc' => true
            ],
            'additionalTables' => (object) [
                'KnowledgeBaseCategoryPath' => (object) [
                    'fields' => (object) [
                        'id' => (object) [
                            'type' => 'id',
                            'dbType' => 'int',
                            'len' => '11',
                            'autoincrement' => true,
                            'unique' => true
                        ],
                        'ascendorId' => (object) [
                            'type' => 'varchar',
                            'len' => '100',
                            'index' => true
                        ],
                        'descendorId' => (object) [
                            'type' => 'varchar',
                            'len' => '24',
                            'index' => true
                        ]
                    ]
                ]
            ]
        ],
        'Lead' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'personName'
                ],
                'salutationName' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Ms.',
                        3 => 'Mrs.',
                        4 => 'Dr.'
                    ]
                ],
                'firstName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'default' => ''
                ],
                'lastName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'default' => ''
                ],
                'title' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'New',
                        1 => 'Assigned',
                        2 => 'In Process',
                        3 => 'Converted',
                        4 => 'Recycled',
                        5 => 'Dead'
                    ],
                    'default' => 'New',
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Converted' => 'success',
                        'Recycled' => 'danger',
                        'Dead' => 'danger'
                    ],
                    'audited' => true
                ],
                'source' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Call',
                        2 => 'Email',
                        3 => 'Existing Customer',
                        4 => 'Partner',
                        5 => 'Public Relations',
                        6 => 'Web Site',
                        7 => 'Campaign',
                        8 => 'Other'
                    ],
                    'default' => ''
                ],
                'industry' => (object) [
                    'type' => 'enum',
                    'view' => 'crm:views/lead/fields/industry',
                    'customizationOptionsDisabled' => true,
                    'default' => '',
                    'isSorted' => true
                ],
                'opportunityAmount' => (object) [
                    'type' => 'currency',
                    'audited' => true
                ],
                'opportunityAmountConverted' => (object) [
                    'type' => 'currencyConverted',
                    'readOnly' => true
                ],
                'website' => (object) [
                    'type' => 'url'
                ],
                'address' => (object) [
                    'type' => 'address'
                ],
                'addressStreet' => (object) [
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar'
                ],
                'addressCity' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressState' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressCountry' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPostalCode' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'email'
                ],
                'phoneNumber' => (object) [
                    'type' => 'phone',
                    'typeList' => [
                        0 => 'Mobile',
                        1 => 'Office',
                        2 => 'Home',
                        3 => 'Fax',
                        4 => 'Other'
                    ],
                    'defaultType' => 'Mobile'
                ],
                'doNotCall' => (object) [
                    'type' => 'bool'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'accountName' => (object) [
                    'type' => 'varchar'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'acceptanceStatus' => (object) [
                    'type' => 'varchar',
                    'notStorable' => true,
                    'exportDisabled' => true,
                    'disabled' => true
                ],
                'acceptanceStatusMeetings' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'meetings',
                    'column' => 'status'
                ],
                'acceptanceStatusCalls' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'exportDisabled' => true,
                    'view' => 'crm:views/lead/fields/acceptance-status',
                    'link' => 'calls',
                    'column' => 'status'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'campaign' => (object) [
                    'type' => 'link',
                    'layoutListDisabled' => true
                ],
                'createdAccount' => (object) [
                    'type' => 'link',
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true
                ],
                'createdContact' => (object) [
                    'type' => 'link',
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'view' => 'crm:views/lead/fields/created-contact'
                ],
                'createdOpportunity' => (object) [
                    'type' => 'link',
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'view' => 'crm:views/lead/fields/created-opportunity'
                ],
                'targetLists' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'importDisabled' => true,
                    'noLoad' => true
                ],
                'targetList' => (object) [
                    'type' => 'link',
                    'notStorable' => true,
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'entity' => 'TargetList'
                ],
                'opportunityAmountCurrency' => (object) [
                    'type' => 'varchar',
                    'disabled' => true
                ],
                'addressMap' => (object) [
                    'type' => 'map',
                    'notStorable' => true,
                    'readOnly' => true,
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'meetings' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Meeting',
                    'foreign' => 'leads',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'calls' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Call',
                    'foreign' => 'leads',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'tasks' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'cases' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Case',
                    'foreign' => 'lead',
                    'audited' => true
                ],
                'emails' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'createdAccount' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                    'noJoin' => true,
                    'foreign' => 'originalLead'
                ],
                'createdContact' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Contact',
                    'noJoin' => true,
                    'foreign' => 'originalLead'
                ],
                'createdOpportunity' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Opportunity',
                    'noJoin' => true,
                    'foreign' => 'originalLead'
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'leads',
                    'noJoin' => true
                ],
                'campaignLogRecords' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'CampaignLogRecord',
                    'foreign' => 'parent'
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'leads'
                ],
                'documents' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Document',
                    'foreign' => 'leads',
                    'audited' => true
                ]
            ],
            'convertEntityList' => [
                0 => 'Account',
                1 => 'Contact',
                2 => 'Opportunity'
            ],
            'convertFields' => (object) [
                'Contact' => (object) [

                ],
                'Account' => (object) [
                    'name' => 'accountName',
                    'billingAddressStreet' => 'addressStreet',
                    'billingAddressCity' => 'addressCity',
                    'billingAddressState' => 'addressState',
                    'billingAddressPostalCode' => 'addressPostalCode',
                    'billingAddressCountry' => 'addressCountry'
                ],
                'Opportunity' => (object) [
                    'amount' => 'opportunityAmount',
                    'leadSource' => 'source'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false,
                'textFilterFields' => [
                    0 => 'name',
                    1 => 'accountName',
                    2 => 'emailAddress'
                ]
            ],
            'indexes' => (object) [
                'firstName' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'deleted'
                    ]
                ],
                'name' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'lastName'
                    ]
                ],
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ],
                'createdAt' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'deleted'
                    ]
                ],
                'createdAtStatus' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'status'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'assignedUserStatus' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'status'
                    ]
                ]
            ]
        ],
        'MassEmail' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Draft',
                        1 => 'Pending',
                        2 => 'Complete',
                        3 => 'In Process',
                        4 => 'Failed'
                    ],
                    'default' => 'Pending'
                ],
                'storeSentEmails' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'optOutEntirely' => (object) [
                    'type' => 'bool',
                    'default' => false,
                    'tooltip' => true
                ],
                'fromAddress' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'view' => 'crm:views/mass-email/fields/from-address'
                ],
                'fromName' => (object) [
                    'type' => 'varchar'
                ],
                'replyToAddress' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'replyToName' => (object) [
                    'type' => 'varchar'
                ],
                'startAt' => (object) [
                    'type' => 'datetime',
                    'required' => true
                ],
                'emailTemplate' => (object) [
                    'type' => 'link',
                    'required' => true,
                    'view' => 'crm:views/mass-email/fields/email-template'
                ],
                'campaign' => (object) [
                    'type' => 'link'
                ],
                'targetLists' => (object) [
                    'type' => 'linkMultiple',
                    'required' => true,
                    'tooltip' => true
                ],
                'excludingTargetLists' => (object) [
                    'type' => 'linkMultiple',
                    'tooltip' => true
                ],
                'inboundEmail' => (object) [
                    'type' => 'link'
                ],
                'smtpAccount' => (object) [
                    'type' => 'base',
                    'notStorable' => true,
                    'view' => 'crm:views/mass-email/fields/smtp-account'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'emailTemplate' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'EmailTemplate'
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'massEmails'
                ],
                'targetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'massEmails'
                ],
                'excludingTargetLists' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'TargetList',
                    'foreign' => 'massEmailsExcluding',
                    'relationName' => 'massEmailTargetListExcluding'
                ],
                'inboundEmail' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'InboundEmail'
                ],
                'queueItems' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'EmailQueueItem',
                    'foreign' => 'massEmail'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ]
        ],
        'Meeting' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Planned',
                        1 => 'Held',
                        2 => 'Not Held'
                    ],
                    'default' => 'Planned',
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Held' => 'success'
                    ],
                    'audited' => true
                ],
                'dateStart' => (object) [
                    'type' => 'datetime',
                    'required' => true,
                    'default' => 'javascript: return this.dateTime.getNow(15);',
                    'audited' => true
                ],
                'dateEnd' => (object) [
                    'type' => 'datetime',
                    'required' => true,
                    'after' => 'dateStart'
                ],
                'duration' => (object) [
                    'type' => 'duration',
                    'start' => 'dateStart',
                    'end' => 'dateEnd',
                    'options' => [
                        0 => 900,
                        1 => 1800,
                        2 => 3600,
                        3 => 7200,
                        4 => 10800,
                        5 => 86400
                    ],
                    'default' => 3600,
                    'notStorable' => true,
                    'select' => 'TIMESTAMPDIFF(SECOND, meeting.date_start, meeting.date_end)',
                    'orderBy' => 'duration {direction}'
                ],
                'reminders' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'view' => 'crm:views/meeting/fields/reminders',
                    'layoutListDisabled' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'parent' => (object) [
                    'type' => 'linkParent',
                    'entityList' => [
                        0 => 'Account',
                        1 => 'Lead',
                        2 => 'Contact',
                        3 => 'Opportunity',
                        4 => 'Case'
                    ]
                ],
                'account' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'acceptanceStatus' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'options' => [
                        0 => 'None',
                        1 => 'Accepted',
                        2 => 'Tentative',
                        3 => 'Declined'
                    ],
                    'layoutDetailDisabled' => true,
                    'layoutMassUpdateDisabled' => true,
                    'where' => (object) [
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads'
                            ],
                            'sql' => 'contactsMiddle.status = {value} OR leadsMiddle.status = {value} OR usersMiddle.status = {value}',
                            'distinct' => true
                        ],
                        '<>' => 'meeting.id NOT IN (SELECT meeting_id FROM contact_meeting WHERE deleted = 0 AND status = {value}) AND meeting.id NOT IN (SELECT meeting_id FROM meeting_user WHERE deleted = 0 AND status = {value}) AND meeting.id NOT IN (SELECT meeting_id FROM lead_meeting WHERE deleted = 0 AND status = {value})',
                        'IN' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'leads',
                                2 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.status IN {value} OR leadsMiddle.status IN {value} OR usersMiddle.status IN {value}',
                            'distinct' => true
                        ],
                        'NOT IN' => 'meeting.id NOT IN (SELECT meeting_id FROM contact_meeting WHERE deleted = 0 AND status IN {value}) AND meeting.id NOT IN (SELECT meeting_id FROM meeting_user WHERE deleted = 0 AND status IN {value}) AND meeting.id NOT IN (SELECT meeting_id FROM lead_meeting WHERE deleted = 0 AND status IN {value})',
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => 'users',
                                1 => 'contacts',
                                2 => 'leads'
                            ],
                            'sql' => 'contactsMiddle.status IS NULL AND leadsMiddle.status IS NULL AND usersMiddle.status IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => 'meeting.id NOT IN (SELECT meeting_id FROM contact_meeting WHERE deleted = 0 AND status IS NULL) OR meeting.id NOT IN (SELECT meeting_id FROM meeting_user WHERE deleted = 0 AND status IS NULL) OR meeting.id NOT IN (SELECT meeting_id FROM lead_meeting WHERE deleted = 0 AND status IS NULL)'
                    ],
                    'view' => 'crm:views/meeting/fields/acceptance-status'
                ],
                'users' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'crm:views/meeting/fields/users',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'contacts' => (object) [
                    'type' => 'linkMultiple',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'view' => 'crm:views/meeting/fields/contacts',
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'leads' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'crm:views/meeting/fields/attendees',
                    'layoutDetailDisabled' => true,
                    'layoutListDisabled' => true,
                    'columns' => (object) [
                        'status' => 'acceptanceStatus'
                    ]
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => true,
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ]
            ],
            'links' => (object) [
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account'
                ],
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'meetings',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'meetings',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'leads' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'meetings',
                    'additionalColumns' => (object) [
                        'status' => (object) [
                            'type' => 'varchar',
                            'len' => '36',
                            'default' => 'None'
                        ]
                    ]
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'foreign' => 'meetings'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'dateStart',
                'asc' => false
            ],
            'indexes' => (object) [
                'dateStartStatus' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'status'
                    ]
                ],
                'dateStart' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'deleted'
                    ]
                ],
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'assignedUserStatus' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'status'
                    ]
                ]
            ]
        ],
        'Opportunity' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'amount' => (object) [
                    'type' => 'currency',
                    'required' => true,
                    'audited' => true
                ],
                'amountConverted' => (object) [
                    'type' => 'currencyConverted',
                    'readOnly' => true
                ],
                'amountWeightedConverted' => (object) [
                    'type' => 'float',
                    'readOnly' => true,
                    'notStorable' => true,
                    'select' => 'opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100',
                    'where' => (object) [
                        '=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) = {value}',
                        '<' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) < {value}',
                        '>' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) > {value}',
                        '<=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) <= {value}',
                        '>=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) >= {value}',
                        '<>' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) <> {value}'
                    ],
                    'orderBy' => 'amountWeightedConverted {direction}',
                    'view' => 'views/fields/currency-converted'
                ],
                'account' => (object) [
                    'type' => 'link'
                ],
                'contacts' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'crm:views/opportunity/fields/contacts',
                    'columns' => (object) [
                        'role' => 'opportunityRole'
                    ]
                ],
                'stage' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Prospecting',
                        1 => 'Qualification',
                        2 => 'Proposal',
                        3 => 'Negotiation',
                        4 => 'Closed Won',
                        5 => 'Closed Lost'
                    ],
                    'view' => 'crm:views/opportunity/fields/stage',
                    'default' => 'Prospecting',
                    'audited' => true,
                    'probabilityMap' => (object) [
                        'Prospecting' => 10,
                        'Qualification' => 20,
                        'Proposal' => 50,
                        'Negotiation' => 80,
                        'Closed Won' => 100,
                        'Closed Lost' => 0
                    ],
                    'fieldManagerAdditionalParamList' => [
                        0 => (object) [
                            'name' => 'probabilityMap',
                            'view' => 'crm:views/opportunity/admin/field-manager/fields/probability-map'
                        ]
                    ]
                ],
                'probability' => (object) [
                    'type' => 'int',
                    'required' => true,
                    'min' => 0,
                    'max' => 100
                ],
                'leadSource' => (object) [
                    'type' => 'enum',
                    'view' => 'crm:views/opportunity/fields/lead-source',
                    'customizationOptionsDisabled' => true,
                    'default' => ''
                ],
                'closeDate' => (object) [
                    'type' => 'date',
                    'required' => true,
                    'audited' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'campaign' => (object) [
                    'type' => 'link'
                ],
                'originalLead' => (object) [
                    'type' => 'link',
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'readOnly' => true,
                    'view' => 'views/fields/link-one'
                ],
                'contactRole' => (object) [
                    'type' => 'enum',
                    'notStorable' => true,
                    'layoutMassUpdateDisabled' => true,
                    'layoutListDisabled' => true,
                    'layoutDetailDisabled' => true,
                    'where' => (object) [
                        '=' => (object) [
                            'leftJoins' => [
                                0 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.role = {value}',
                            'distinct' => true
                        ],
                        '<>' => 'opportunity.id NOT IN (SELECT opportunity_id FROM contact_opportunity WHERE deleted = 0 AND role = {value})',
                        'IN' => (object) [
                            'leftJoins' => [
                                0 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.role IN {value}',
                            'distinct' => true
                        ],
                        'NOT IN' => 'opportunity.id NOT IN (SELECT opportunity_id FROM contact_opportunity WHERE deleted = 0 AND role IN {value})',
                        'LIKE' => (object) [
                            'leftJoins' => [
                                0 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.role LIKE {value}',
                            'distinct' => true
                        ],
                        'IS NULL' => (object) [
                            'leftJoins' => [
                                0 => 'contacts'
                            ],
                            'sql' => 'contactsMiddle.role IS NULL',
                            'distinct' => true
                        ],
                        'IS NOT NULL' => 'opportunity.id NOT IN (SELECT opportunity_id FROM contact_opportunity WHERE deleted = 0 AND role IS NULL)'
                    ],
                    'view' => 'crm:views/opportunity/fields/contact-role',
                    'customizationOptionsDisabled' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => false,
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'amountCurrency' => (object) [
                    'type' => 'varchar',
                    'disabled' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account',
                    'foreign' => 'opportunities'
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'opportunities',
                    'additionalColumns' => (object) [
                        'role' => (object) [
                            'type' => 'varchar',
                            'len' => 50
                        ]
                    ]
                ],
                'meetings' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Meeting',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'calls' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Call',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'tasks' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Task',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true,
                    'audited' => true
                ],
                'emails' => (object) [
                    'type' => 'hasChildren',
                    'entity' => 'Email',
                    'foreign' => 'parent',
                    'layoutRelationshipsDisabled' => true
                ],
                'documents' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Document',
                    'foreign' => 'opportunities',
                    'audited' => true
                ],
                'campaign' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Campaign',
                    'foreign' => 'opportunities',
                    'noJoin' => true
                ],
                'originalLead' => (object) [
                    'type' => 'hasOne',
                    'entity' => 'Lead',
                    'foreign' => 'createdOpportunity'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'stage' => (object) [
                    'columns' => [
                        0 => 'stage',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'createdAt' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'deleted'
                    ]
                ],
                'createdAtStage' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'stage'
                    ]
                ],
                'assignedUserStage' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'stage'
                    ]
                ]
            ]
        ],
        'Reminder' => (object) [
            'fields' => (object) [
                'remindAt' => (object) [
                    'type' => 'datetime',
                    'index' => true
                ],
                'startAt' => (object) [
                    'type' => 'datetime',
                    'index' => true
                ],
                'type' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Popup',
                        1 => 'Email'
                    ],
                    'maxLength' => 36,
                    'index' => true,
                    'default' => 'Popup'
                ],
                'seconds' => (object) [
                    'type' => 'enumInt',
                    'options' => [
                        0 => 0,
                        1 => 60,
                        2 => 120,
                        3 => 300,
                        4 => 600,
                        5 => 900,
                        6 => 1800,
                        7 => 3600,
                        8 => 7200,
                        9 => 10800,
                        10 => 18000,
                        11 => 86400,
                        12 => 172800,
                        13 => 259200,
                        14 => 432000
                    ],
                    'default' => 0
                ],
                'entityType' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'entityId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 50
                ],
                'userId' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 50
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'remindAt',
                'asc' => false
            ]
        ],
        'Target' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'personName'
                ],
                'salutationName' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => '',
                        1 => 'Mr.',
                        2 => 'Mrs.',
                        3 => 'Ms.',
                        4 => 'Dr.',
                        5 => 'Drs.'
                    ]
                ],
                'firstName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'default' => ''
                ],
                'lastName' => (object) [
                    'type' => 'varchar',
                    'trim' => true,
                    'maxLength' => 100,
                    'required' => true,
                    'default' => ''
                ],
                'title' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'accountName' => (object) [
                    'type' => 'varchar',
                    'maxLength' => 100
                ],
                'website' => (object) [
                    'type' => 'url'
                ],
                'address' => (object) [
                    'type' => 'address'
                ],
                'addressStreet' => (object) [
                    'type' => 'text',
                    'maxLength' => 255,
                    'dbType' => 'varchar'
                ],
                'addressCity' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressState' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressCountry' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'addressPostalCode' => (object) [
                    'type' => 'varchar',
                    'trim' => true
                ],
                'emailAddress' => (object) [
                    'type' => 'email'
                ],
                'phoneNumber' => (object) [
                    'type' => 'phone',
                    'typeList' => [
                        0 => 'Mobile',
                        1 => 'Office',
                        2 => 'Home',
                        3 => 'Fax',
                        4 => 'Other'
                    ],
                    'defaultType' => 'Mobile'
                ],
                'doNotCall' => (object) [
                    'type' => 'bool'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'addressMap' => (object) [
                    'type' => 'map',
                    'notStorable' => true,
                    'readOnly' => true,
                    'layoutListDisabled' => true,
                    'provider' => 'Google',
                    'height' => 300
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'firstName' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'deleted'
                    ]
                ],
                'name' => (object) [
                    'columns' => [
                        0 => 'firstName',
                        1 => 'lastName'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'TargetList' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'entryCount' => (object) [
                    'type' => 'int',
                    'readOnly' => true,
                    'notStorable' => true
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'campaigns' => (object) [
                    'type' => 'link'
                ],
                'includingActionList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'crm:views/target-list/fields/including-action-list',
                    'layoutDetailDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'layoutLinkDisabled' => true,
                    'notStorable' => true,
                    'required' => true,
                    'disabled' => true
                ],
                'excludingActionList' => (object) [
                    'type' => 'multiEnum',
                    'view' => 'crm:views/target-list/fields/including-action-list',
                    'layoutDetailDisabled' => true,
                    'layoutFiltersDisabled' => true,
                    'layoutLinkDisabled' => true,
                    'notStorable' => true,
                    'disabled' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'campaigns' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Campaign',
                    'foreign' => 'targetLists'
                ],
                'massEmails' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'MassEmail',
                    'foreign' => 'targetLists'
                ],
                'campaignsExcluding' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Campaign',
                    'foreign' => 'excludingTargetLists'
                ],
                'massEmailsExcluding' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'MassEmail',
                    'foreign' => 'excludingTargetLists'
                ],
                'accounts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Account',
                    'foreign' => 'targetLists',
                    'additionalColumns' => (object) [
                        'optedOut' => (object) [
                            'type' => 'bool'
                        ]
                    ]
                ],
                'contacts' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Contact',
                    'foreign' => 'targetLists',
                    'additionalColumns' => (object) [
                        'optedOut' => (object) [
                            'type' => 'bool'
                        ]
                    ]
                ],
                'leads' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Lead',
                    'foreign' => 'targetLists',
                    'additionalColumns' => (object) [
                        'optedOut' => (object) [
                            'type' => 'bool'
                        ]
                    ]
                ],
                'users' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'User',
                    'foreign' => 'targetLists',
                    'additionalColumns' => (object) [
                        'optedOut' => (object) [
                            'type' => 'bool'
                        ]
                    ]
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'createdAt' => (object) [
                    'columns' => [
                        0 => 'createdAt',
                        1 => 'deleted'
                    ]
                ]
            ]
        ],
        'Task' => (object) [
            'fields' => (object) [
                'name' => (object) [
                    'type' => 'varchar',
                    'required' => true,
                    'trim' => true
                ],
                'status' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Not Started',
                        1 => 'Started',
                        2 => 'Completed',
                        3 => 'Canceled',
                        4 => 'Deferred'
                    ],
                    'view' => 'views/fields/enum-styled',
                    'style' => (object) [
                        'Completed' => 'success'
                    ],
                    'default' => 'Not Started',
                    'audited' => true
                ],
                'priority' => (object) [
                    'type' => 'enum',
                    'options' => [
                        0 => 'Low',
                        1 => 'Normal',
                        2 => 'High',
                        3 => 'Urgent'
                    ],
                    'default' => 'Normal',
                    'audited' => true
                ],
                'dateStart' => (object) [
                    'type' => 'datetimeOptional',
                    'before' => 'dateEnd'
                ],
                'dateEnd' => (object) [
                    'type' => 'datetimeOptional',
                    'after' => 'dateStart',
                    'view' => 'crm:views/task/fields/date-end',
                    'audited' => true
                ],
                'dateStartDate' => (object) [
                    'type' => 'date',
                    'disabled' => true
                ],
                'dateEndDate' => (object) [
                    'type' => 'date',
                    'disabled' => true
                ],
                'dateCompleted' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'isOverdue' => (object) [
                    'type' => 'bool',
                    'readOnly' => true,
                    'notStorable' => true,
                    'view' => 'crm:views/task/fields/is-overdue',
                    'disabled' => true
                ],
                'reminders' => (object) [
                    'type' => 'jsonArray',
                    'notStorable' => true,
                    'view' => 'crm:views/meeting/fields/reminders'
                ],
                'description' => (object) [
                    'type' => 'text'
                ],
                'parent' => (object) [
                    'type' => 'linkParent',
                    'entityList' => [
                        0 => 'Account',
                        1 => 'Contact',
                        2 => 'Lead',
                        3 => 'Opportunity',
                        4 => 'Case'
                    ]
                ],
                'account' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'contact' => (object) [
                    'type' => 'link',
                    'readOnly' => true
                ],
                'createdAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'modifiedAt' => (object) [
                    'type' => 'datetime',
                    'readOnly' => true
                ],
                'createdBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'modifiedBy' => (object) [
                    'type' => 'link',
                    'readOnly' => true,
                    'view' => 'views/fields/user'
                ],
                'assignedUser' => (object) [
                    'type' => 'link',
                    'required' => true,
                    'view' => 'views/fields/assigned-user'
                ],
                'teams' => (object) [
                    'type' => 'linkMultiple',
                    'view' => 'views/fields/teams'
                ],
                'attachments' => (object) [
                    'type' => 'attachmentMultiple',
                    'sourceList' => [
                        0 => 'Document'
                    ],
                    'layoutListDisabled' => true
                ]
            ],
            'links' => (object) [
                'createdBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'modifiedBy' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User'
                ],
                'assignedUser' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'User',
                    'foreign' => 'tasks'
                ],
                'teams' => (object) [
                    'type' => 'hasMany',
                    'entity' => 'Team',
                    'relationName' => 'entityTeam',
                    'layoutRelationshipsDisabled' => true
                ],
                'parent' => (object) [
                    'type' => 'belongsToParent',
                    'foreign' => 'tasks'
                ],
                'account' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Account'
                ],
                'contact' => (object) [
                    'type' => 'belongsTo',
                    'entity' => 'Contact'
                ]
            ],
            'collection' => (object) [
                'sortBy' => 'createdAt',
                'asc' => false
            ],
            'indexes' => (object) [
                'dateStartStatus' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'status'
                    ]
                ],
                'dateEndStatus' => (object) [
                    'columns' => [
                        0 => 'dateEnd',
                        1 => 'status'
                    ]
                ],
                'dateStart' => (object) [
                    'columns' => [
                        0 => 'dateStart',
                        1 => 'deleted'
                    ]
                ],
                'status' => (object) [
                    'columns' => [
                        0 => 'status',
                        1 => 'deleted'
                    ]
                ],
                'assignedUser' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'deleted'
                    ]
                ],
                'assignedUserStatus' => (object) [
                    'columns' => [
                        0 => 'assignedUserId',
                        1 => 'status'
                    ]
                ]
            ]
        ]
    ]
];
?>