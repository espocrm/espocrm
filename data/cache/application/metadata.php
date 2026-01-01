<?php
return [
  'aclDefs' => [
    'ActionHistoryRecord' => [
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\ActionHistoryRecord\\OwnershipChecker'
    ],
    'Attachment' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Attachment\\AccessChecker',
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Attachment\\OwnershipChecker',
      'portalAccessCheckerClassName' => 'Espo\\Classes\\AclPortal\\Attachment\\AccessChecker',
      'portalOwnershipCheckerClassName' => 'Espo\\Classes\\AclPortal\\Attachment\\OwnershipChecker'
    ],
    'AuthToken' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\AuthToken\\AccessChecker'
    ],
    'CurrencyRecordRate' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\CurrencyRecordRate\\AccessChecker'
    ],
    'Email' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Email\\AccessChecker',
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Email\\OwnershipChecker',
      'portalAccessCheckerClassName' => 'Espo\\Classes\\AclPortal\\Email\\AccessChecker',
      'portalOwnershipCheckerClassName' => 'Espo\\Classes\\AclPortal\\Email\\OwnershipChecker',
      'assignmentCheckerClassName' => 'Espo\\Classes\\Acl\\Email\\AssignmentChecker',
      'readOwnerUserField' => 'users',
      'linkCheckerClassNameMap' => [
        'parent' => 'Espo\\Classes\\Acl\\Email\\LinkCheckers\\ParentLinkChecker',
        'teams' => 'Espo\\Classes\\Acl\\Email\\LinkCheckers\\TeamsLinkChecker'
      ]
    ],
    'EmailFilter' => [
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\EmailFilter\\OwnershipChecker'
    ],
    'Import' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Import\\AccessChecker'
    ],
    'ImportEml' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\ImportEml\\AccessChecker'
    ],
    'ImportError' => [
      'accessCheckerClassName' => 'Espo\\Core\\Acl\\AccessChecker\\AccessCheckers\\Foreign',
      'link' => 'import'
    ],
    'Note' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Note\\AccessChecker',
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Note\\OwnershipChecker',
      'portalAccessCheckerClassName' => 'Espo\\Classes\\AclPortal\\Note\\AccessChecker',
      'portalOwnershipCheckerClassName' => 'Espo\\Classes\\AclPortal\\Note\\OwnershipChecker'
    ],
    'Notification' => [
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Notification\\OwnershipChecker',
      'portalOwnershipCheckerClassName' => 'Espo\\Classes\\AclPortal\\Notification\\OwnershipChecker'
    ],
    'Portal' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Portal\\AccessChecker'
    ],
    'ScheduledJob' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\ScheduledJob\\AccessChecker'
    ],
    'Team' => [
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Team\\OwnershipChecker'
    ],
    'User' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\User\\AccessChecker',
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\User\\OwnershipChecker',
      'portalOwnershipCheckerClassName' => 'Espo\\Classes\\AclPortal\\User\\OwnershipChecker'
    ],
    'Webhook' => [
      'accessCheckerClassName' => 'Espo\\Classes\\Acl\\Webhook\\AccessChecker',
      'ownershipCheckerClassName' => 'Espo\\Classes\\Acl\\Webhook\\OwnershipChecker'
    ],
    'WorkingTimeRange' => [
      'assignmentCheckerClassName' => 'Espo\\Classes\\Acl\\WorkingTimeRange\\AssignmentChecker'
    ],
    'Account' => [
      'portalOwnershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\AclPortal\\Account\\OwnershipChecker'
    ],
    'Call' => [
      'accessCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Call\\AccessChecker',
      'assignmentCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Meeting\\AssignmentChecker',
      'readOwnerUserField' => 'users',
      'contactLink' => 'contacts',
      'accountLink' => 'account'
    ],
    'CampaignLogRecord' => [
      'ownershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\CampaignLogRecord\\OwnershipChecker'
    ],
    'CampaignTrackingUrl' => [
      'ownershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\CampaignTrackingUrl\\OwnershipChecker'
    ],
    'Case' => [
      'linkCheckerClassNameMap' => [
        'lead' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Case\\LinkCheckers\\LeadLinkChecker',
        'account' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Case\\LinkCheckers\\AccountLinkChecker',
        'contact' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Case\\LinkCheckers\\ContactLinkChecker',
        'contacts' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Case\\LinkCheckers\\ContactLinkChecker'
      ],
      'portalOwnershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\AclPortal\\Case\\OwnershipChecker',
      'contactLink' => 'contacts',
      'accountLink' => 'account'
    ],
    'Contact' => [
      'portalOwnershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\AclPortal\\Contact\\OwnershipChecker',
      'accountLink' => 'accounts'
    ],
    'Document' => [
      'contactLink' => 'contacts',
      'accountLink' => 'accounts'
    ],
    'KnowledgeBaseArticle' => [
      'portalAccessCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\AclPortal\\KnowledgeBaseArticle\\AccessChecker'
    ],
    'MassEmail' => [
      'ownershipCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\MassEmail\\OwnershipChecker',
      'linkCheckerClassNameMap' => [
        'inboundEmail' => 'Espo\\Modules\\Crm\\Classes\\Acl\\MassEmail\\LinkCheckers\\InboundEmailLinkChecker'
      ],
      'accessCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\MassEmail\\AccessChecker'
    ],
    'Meeting' => [
      'accessCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Meeting\\AccessChecker',
      'assignmentCheckerClassName' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Meeting\\AssignmentChecker',
      'readOwnerUserField' => 'users',
      'contactLink' => 'contacts',
      'accountLink' => 'account'
    ],
    'Opportunity' => [
      'contactLink' => 'contacts',
      'accountLink' => 'account'
    ],
    'Task' => [
      'linkCheckerClassNameMap' => [
        'parent' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Task\\LinkCheckers\\ParentLinkChecker',
        'account' => 'Espo\\Modules\\Crm\\Classes\\Acl\\Task\\LinkCheckers\\AccountLinkChecker'
      ],
      'contactLink' => 'contacts',
      'accountLink' => 'account'
    ]
  ],
  'app' => [
    'acl' => [
      'mandatory' => [
        'scopeLevel' => [
          'Note' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'Portal' => [
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'no',
            'create' => 'no'
          ],
          'Attachment' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'EmailAccount' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'EmailFilter' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'EmailFolder' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'GroupEmailFolder' => [
            'read' => 'team',
            'edit' => 'no',
            'delete' => 'no',
            'create' => 'no'
          ],
          'Preferences' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'no',
            'create' => 'no'
          ],
          'Notification' => [
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'own',
            'create' => 'no'
          ],
          'ActionHistoryRecord' => [
            'read' => 'own'
          ],
          'Role' => false,
          'PortalRole' => false,
          'ImportError' => 'Import',
          'ImportEml' => 'Import',
          'WorkingTimeRange' => 'WorkingTimeCalendar',
          'Stream' => true,
          'CurrencyRecord' => 'boolean:Currency',
          'CurrencyRecordRate' => 'Currency',
          'MassEmail' => 'Campaign',
          'CampaignLogRecord' => 'Campaign',
          'CampaignTrackingUrl' => 'Campaign',
          'EmailQueueItem' => 'Campaign'
        ],
        'fieldLevel' => [],
        'scopeFieldLevel' => [
          'EmailAccount' => [
            'assignedUser' => [
              'read' => 'yes',
              'edit' => 'no'
            ]
          ],
          'EmailFolder' => [
            'assignedUser' => [
              'read' => 'yes',
              'edit' => 'no'
            ]
          ],
          'Email' => [
            'inboundEmails' => false,
            'emailAccounts' => false
          ],
          'User' => [
            'dashboardTemplate' => false,
            'workingTimeCalendar' => [
              'read' => 'yes',
              'edit' => 'no'
            ],
            'password' => false,
            'passwordConfirm' => false,
            'auth2FA' => false,
            'authMethod' => false,
            'apiKey' => false,
            'secretKey' => false,
            'token' => false
          ],
          'ActionHistoryRecord' => [
            'authToken' => false,
            'authLogRecord' => false
          ]
        ]
      ],
      'strictDefault' => [
        'scopeLevel' => [
          'User' => [
            'read' => 'own',
            'edit' => 'no'
          ],
          'Team' => [
            'read' => 'team'
          ],
          'Import' => false,
          'Webhook' => false
        ],
        'fieldLevel' => [],
        'scopeFieldLevel' => [
          'User' => [
            'gender' => false,
            'avatarColor' => [
              'read' => 'yes',
              'edit' => 'no'
            ]
          ],
          'Meeting' => [
            'uid' => false
          ],
          'Call' => [
            'uid' => false
          ]
        ]
      ],
      'adminMandatory' => [
        'scopeLevel' => [
          'User' => [
            'create' => 'yes',
            'read' => 'all',
            'edit' => 'all',
            'delete' => 'all'
          ],
          'Team' => [
            'create' => 'yes',
            'read' => 'all',
            'edit' => 'all',
            'delete' => 'all'
          ],
          'Job' => [
            'create' => 'no',
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'all'
          ],
          'Extension' => [
            'create' => 'no',
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'all'
          ],
          'Stream' => true,
          'ImportEml' => 'Import',
          'CurrencyRecordRate' => [
            'read' => 'yes',
            'edit' => 'yes'
          ]
        ]
      ],
      'valuePermissionList' => [
        0 => 'assignmentPermission',
        1 => 'messagePermission',
        2 => 'mentionPermission',
        3 => 'userCalendarPermission',
        4 => 'auditPermission',
        5 => 'exportPermission',
        6 => 'massUpdatePermission',
        7 => 'userPermission',
        8 => 'portalPermission',
        9 => 'groupEmailAccountPermission',
        10 => 'followerManagementPermission',
        11 => 'dataPrivacyPermission'
      ],
      'valuePermissionHighestLevels' => [
        'assignmentPermission' => 'all',
        'userPermission' => 'all',
        'messagePermission' => 'all',
        'portalPermission' => 'yes',
        'groupEmailAccountPermission' => 'all',
        'exportPermission' => 'yes',
        'massUpdatePermission' => 'yes',
        'followerManagementPermission' => 'all',
        'dataPrivacyPermission' => 'yes',
        'auditPermission' => 'yes',
        'mentionPermission' => 'yes',
        'userCalendarPermission' => 'all'
      ],
      'permissionsStrictDefaults' => [
        'assignmentPermission' => 'no',
        'userPermission' => 'no',
        'messagePermission' => 'no',
        'portalPermission' => 'no',
        'groupEmailAccountPermission' => 'no',
        'exportPermission' => 'no',
        'massUpdatePermission' => 'no',
        'followerManagementPermission' => 'no',
        'dataPrivacyPermission' => 'no',
        'auditPermission' => 'no',
        'mentionPermission' => 'no',
        'userCalendarPermission' => 'no'
      ]
    ],
    'aclPortal' => [
      'mandatory' => [
        'scopeLevel' => [
          'User' => [
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'no',
            'stream' => 'no',
            'create' => 'no'
          ],
          'Team' => false,
          'Note' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'Notification' => [
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'own',
            'create' => 'no'
          ],
          'Portal' => false,
          'Attachment' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes'
          ],
          'ExternalAccount' => false,
          'Role' => false,
          'PortalRole' => false,
          'EmailFilter' => false,
          'EmailFolder' => false,
          'EmailAccount' => false,
          'EmailTemplate' => false,
          'ActionHistoryRecord' => [
            'read' => 'own'
          ],
          'Preferences' => [
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'no',
            'create' => 'no'
          ],
          'MassEmail' => 'Campaign',
          'CampaignLogRecord' => 'Campaign',
          'CampaignTrackingUrl' => 'Campaign',
          'EmailQueueItem' => false
        ],
        'fieldLevel' => [],
        'scopeFieldLevel' => [
          'Preferences' => [
            'smtpServer' => false,
            'smtpPort' => false,
            'smtpSecurity' => false,
            'smtpUsername' => false,
            'smtpPassword' => false,
            'smtpAuth' => false,
            'receiveAssignmentEmailNotifications' => false,
            'receiveMentionEmailNotifications' => false,
            'defaultReminders' => false,
            'autoFollowEntityTypeList' => false,
            'emailReplyForceHtml' => false,
            'emailReplyToAllByDefault' => false,
            'signature' => false,
            'followCreatedEntities' => false,
            'followEntityOnStreamPost' => false,
            'doNotFillAssignedUserIfNotRequired' => false,
            'useCustomTabList' => false,
            'addCustomTabs' => false,
            'tabList' => false,
            'emailUseExternalClient' => false,
            'assignmentNotificationsIgnoreEntityTypeList' => false,
            'assignmentEmailNotificationsIgnoreEntityTypeList' => false,
            'dashletsOptions' => false,
            'dashboardLayout' => false,
            'followAsCollaborator' => false
          ],
          'Call' => [
            'reminders' => false,
            'uid' => false
          ],
          'Meeting' => [
            'reminders' => false,
            'uid' => false
          ],
          'Note' => [
            'isInternal' => false,
            'isGlobal' => false
          ],
          'Email' => [
            'inboundEmails' => false,
            'emailAccounts' => false
          ],
          'User' => [
            'dashboardTemplate' => false,
            'workingTimeCalendar' => false,
            'password' => false,
            'authMethod' => false,
            'apiKey' => false,
            'secretKey' => false,
            'token' => false,
            'isAdmin' => false,
            'type' => false,
            'contact' => false,
            'accounts' => false,
            'account' => false,
            'portalRoles' => false,
            'portals' => false,
            'roles' => false,
            'defaultTeam' => false,
            'auth2FA' => false,
            'isActive' => false
          ],
          'ActionHistoryRecord' => [
            'authToken' => false,
            'authLogRecord' => false
          ],
          'Case' => [
            'isInternal' => false
          ]
        ]
      ],
      'strictDefault' => [
        'scopeLevel' => [],
        'fieldLevel' => [
          'assignedUser' => [
            'read' => 'yes',
            'edit' => 'no'
          ],
          'assignedUsers' => [
            'read' => 'yes',
            'edit' => 'no'
          ],
          'collaborators' => false,
          'teams' => false
        ],
        'scopeFieldLevel' => [
          'User' => [
            'gender' => false
          ],
          'KnowledgeBaseArticle' => [
            'portals' => false,
            'order' => [
              'read' => 'yes',
              'edit' => 'no'
            ],
            'status' => false,
            'assignedUser' => false
          ],
          'Call' => [
            'users' => [
              'read' => 'yes',
              'edit' => 'no'
            ],
            'leads' => false
          ],
          'Meeting' => [
            'users' => [
              'read' => 'yes',
              'edit' => 'no'
            ],
            'leads' => false
          ],
          'Case' => [
            'status' => [
              'read' => 'yes',
              'edit' => 'no'
            ]
          ]
        ]
      ],
      'valuePermissionList' => [
        0 => 'exportPermission',
        1 => 'massUpdatePermission'
      ],
      'permissionsStrictDefaults' => [
        'exportPermission' => 'no',
        'massUpdatePermission' => 'no'
      ]
    ],
    'actions' => [
      'convertCurrency' => [
        'implementationClassName' => 'Espo\\Core\\Action\\Actions\\ConvertCurrency'
      ],
      'merge' => [
        'implementationClassName' => 'Espo\\Core\\Action\\Actions\\Merge'
      ]
    ],
    'addressFormats' => [
      1 => [
        'formatterClassName' => 'Espo\\Classes\\AddressFormatters\\Formatter1'
      ],
      2 => [
        'formatterClassName' => 'Espo\\Classes\\AddressFormatters\\Formatter2'
      ],
      3 => [
        'formatterClassName' => 'Espo\\Classes\\AddressFormatters\\Formatter3'
      ],
      4 => [
        'formatterClassName' => 'Espo\\Classes\\AddressFormatters\\Formatter4'
      ]
    ],
    'adminPanel' => [
      'system' => [
        'label' => 'System',
        'itemList' => [
          0 => [
            'url' => '#Admin/settings',
            'label' => 'Settings',
            'iconClass' => 'fas fa-cog',
            'description' => 'settings',
            'recordView' => 'views/admin/settings'
          ],
          1 => [
            'url' => '#Admin/userInterface',
            'label' => 'User Interface',
            'iconClass' => 'fas fa-desktop',
            'description' => 'userInterface',
            'recordView' => 'views/admin/user-interface'
          ],
          2 => [
            'url' => '#Admin/authentication',
            'label' => 'Authentication',
            'iconClass' => 'fas fa-sign-in-alt',
            'description' => 'authentication',
            'recordView' => 'views/admin/authentication'
          ],
          3 => [
            'url' => '#ScheduledJob',
            'label' => 'Scheduled Jobs',
            'iconClass' => 'fas fa-clock',
            'description' => 'scheduledJob'
          ],
          4 => [
            'url' => '#Admin/currency',
            'label' => 'Currency',
            'iconClass' => 'fas fa-euro-sign',
            'description' => 'currency',
            'recordView' => 'views/admin/currency',
            'view' => 'views/admin/currency-main'
          ],
          5 => [
            'url' => '#Admin/notifications',
            'label' => 'Notifications',
            'iconClass' => 'fas fa-bell',
            'description' => 'notifications',
            'recordView' => 'views/admin/notifications'
          ],
          6 => [
            'url' => '#Admin/integrations',
            'label' => 'Integrations',
            'iconClass' => 'fas fa-network-wired',
            'description' => 'integrations'
          ],
          7 => [
            'url' => '#Admin/extensions',
            'label' => 'Extensions',
            'iconClass' => 'fas fa-upload',
            'description' => 'extensions'
          ],
          8 => [
            'url' => '#Admin/systemRequirements',
            'label' => 'System Requirements',
            'iconClass' => 'fas fa-server',
            'description' => 'systemRequirements'
          ],
          9 => [
            'url' => '#Admin/jobsSettings',
            'label' => 'Job Settings',
            'iconClass' => 'fas fa-list-ul',
            'description' => 'jobsSettings',
            'recordView' => 'views/admin/jobs-settings'
          ],
          10 => [
            'url' => '#Admin/upgrade',
            'label' => 'Upgrade',
            'iconClass' => 'fas fa-arrow-alt-circle-up',
            'description' => 'upgrade',
            'view' => 'views/admin/upgrade/index'
          ],
          11 => [
            'action' => 'clearCache',
            'label' => 'Clear Cache',
            'iconClass' => 'fas fa-broom',
            'description' => 'clearCache'
          ],
          12 => [
            'action' => 'rebuild',
            'label' => 'Rebuild',
            'iconClass' => 'fas fa-database',
            'description' => 'rebuild'
          ]
        ],
        'order' => 0
      ],
      'users' => [
        'label' => 'Users',
        'itemList' => [
          0 => [
            'url' => '#Admin/users',
            'label' => 'Users',
            'iconClass' => 'fas fa-user',
            'description' => 'users',
            'tabQuickSearch' => true
          ],
          1 => [
            'url' => '#Admin/teams',
            'label' => 'Teams',
            'iconClass' => 'fas fa-users',
            'description' => 'teams',
            'tabQuickSearch' => true
          ],
          2 => [
            'url' => '#Admin/roles',
            'label' => 'Roles',
            'iconClass' => 'fas fa-key',
            'description' => 'roles',
            'tabQuickSearch' => true
          ],
          3 => [
            'url' => '#Admin/authLog',
            'label' => 'Auth Log',
            'iconClass' => 'fas fa-sign-in-alt',
            'description' => 'authLog'
          ],
          4 => [
            'url' => '#Admin/authTokens',
            'label' => 'Auth Tokens',
            'iconClass' => 'fas fa-shield-alt',
            'description' => 'authTokens'
          ],
          5 => [
            'url' => '#Admin/actionHistory',
            'label' => 'Action History',
            'iconClass' => 'fas fa-history',
            'description' => 'actionHistory'
          ],
          6 => [
            'url' => '#Admin/apiUsers',
            'label' => 'API Users',
            'iconClass' => 'fas fa-user-cog',
            'description' => 'apiUsers'
          ]
        ],
        'order' => 5
      ],
      'customization' => [
        'label' => 'Customization',
        'itemList' => [
          0 => [
            'url' => '#Admin/entityManager',
            'label' => 'Entity Manager',
            'iconClass' => 'fas fa-tools',
            'description' => 'entityManager',
            'tabQuickSearch' => true
          ],
          1 => [
            'url' => '#Admin/layouts',
            'label' => 'Layout Manager',
            'iconClass' => 'fas fa-table',
            'description' => 'layoutManager'
          ],
          2 => [
            'url' => '#Admin/labelManager',
            'label' => 'Label Manager',
            'iconClass' => 'fas fa-language',
            'description' => 'labelManager'
          ],
          3 => [
            'url' => '#Admin/templateManager',
            'label' => 'Template Manager',
            'iconClass' => 'fas fa-envelope-open-text',
            'description' => 'templateManager'
          ]
        ],
        'order' => 10
      ],
      'email' => [
        'label' => 'Messaging',
        'itemList' => [
          0 => [
            'url' => '#Admin/outboundEmails',
            'label' => 'Outbound Emails',
            'iconClass' => 'fas fa-paper-plane',
            'description' => 'outboundEmails',
            'recordView' => 'views/admin/outbound-emails'
          ],
          1 => [
            'url' => '#Admin/inboundEmails',
            'label' => 'Inbound Emails',
            'iconClass' => 'fas fa-envelope',
            'description' => 'inboundEmails',
            'recordView' => 'views/admin/inbound-emails'
          ],
          2 => [
            'url' => '#Admin/groupEmailAccounts',
            'label' => 'Group Email Accounts',
            'iconClass' => 'fas fa-inbox',
            'description' => 'groupEmailAccounts',
            'tabQuickSearch' => true
          ],
          3 => [
            'url' => '#Admin/personalEmailAccounts',
            'label' => 'Personal Email Accounts',
            'iconClass' => 'fas fa-inbox',
            'description' => 'personalEmailAccounts',
            'tabQuickSearch' => true
          ],
          4 => [
            'url' => '#Admin/emailFilters',
            'label' => 'Email Filters',
            'iconClass' => 'fas fa-filter',
            'description' => 'emailFilters'
          ],
          5 => [
            'url' => '#Admin/groupEmailFolders',
            'label' => 'Group Email Folders',
            'iconClass' => 'fas fa-folder',
            'description' => 'groupEmailFolders'
          ],
          6 => [
            'url' => '#Admin/emailTemplates',
            'label' => 'Email Templates',
            'iconClass' => 'fas fa-envelope-square',
            'description' => 'emailTemplates'
          ],
          7 => [
            'url' => '#Admin/sms',
            'label' => 'SMS',
            'iconClass' => 'fas fa-paper-plane',
            'description' => 'sms',
            'recordView' => 'views/admin/sms'
          ]
        ],
        'order' => 15
      ],
      'portal' => [
        'label' => 'Portal',
        'itemList' => [
          0 => [
            'url' => '#Admin/portals',
            'label' => 'Portals',
            'iconClass' => 'fas fa-parking',
            'description' => 'portals'
          ],
          1 => [
            'url' => '#Admin/portalUsers',
            'label' => 'Portal Users',
            'iconClass' => 'fas fa-user',
            'description' => 'portalUsers',
            'tabQuickSearch' => true
          ],
          2 => [
            'url' => '#Admin/portalRoles',
            'label' => 'Portal Roles',
            'iconClass' => 'fas fa-key',
            'description' => 'portalRoles'
          ]
        ],
        'order' => 20
      ],
      'setup' => [
        'label' => 'Setup',
        'itemList' => [
          0 => [
            'url' => '#Admin/workingTimeCalendar',
            'label' => 'Working Time Calendars',
            'iconClass' => 'far fa-calendar-alt',
            'description' => 'workingTimeCalendars',
            'tabQuickSearch' => true
          ],
          1 => [
            'url' => '#Admin/layoutSets',
            'label' => 'Layout Sets',
            'iconClass' => 'fas fa-table',
            'description' => 'layoutSets'
          ],
          2 => [
            'url' => '#Admin/dashboardTemplates',
            'label' => 'Dashboard Templates',
            'iconClass' => 'fas fa-th-large',
            'description' => 'dashboardTemplates'
          ],
          3 => [
            'url' => '#Admin/leadCapture',
            'label' => 'Lead Capture',
            'iconClass' => 'fas fa-id-card',
            'description' => 'leadCapture'
          ],
          4 => [
            'url' => '#Admin/pdfTemplates',
            'label' => 'PDF Templates',
            'iconClass' => 'fas fa-file-pdf',
            'description' => 'pdfTemplates'
          ],
          5 => [
            'url' => '#Admin/webhooks',
            'label' => 'Webhooks',
            'iconClass' => 'fas fa-share-alt icon-rotate-90',
            'description' => 'webhooks'
          ],
          6 => [
            'url' => '#Admin/addressCountries',
            'label' => 'Address Countries',
            'iconClass' => 'far fa-flag',
            'description' => 'addressCountries'
          ],
          7 => [
            'url' => '#Admin/authenticationProviders',
            'label' => 'Authentication Providers',
            'iconClass' => 'fas fa-sign-in-alt',
            'description' => 'authenticationProviders'
          ]
        ],
        'order' => 24
      ],
      'data' => [
        'label' => 'Data',
        'itemList' => [
          0 => [
            'url' => '#Admin/import',
            'label' => 'Import',
            'iconClass' => 'fas fa-file-import',
            'description' => 'import'
          ],
          1 => [
            'url' => '#Admin/attachments',
            'label' => 'Attachments',
            'iconClass' => 'fas fa-paperclip',
            'description' => 'attachments'
          ],
          2 => [
            'url' => '#Admin/jobs',
            'label' => 'Jobs',
            'iconClass' => 'fas fa-list-ul',
            'description' => 'jobs'
          ],
          3 => [
            'url' => '#Admin/emailAddresses',
            'label' => 'Email Addresses',
            'iconClass' => 'fas fa-envelope',
            'description' => 'emailAddresses'
          ],
          4 => [
            'url' => '#Admin/phoneNumbers',
            'label' => 'Phone Numbers',
            'iconClass' => 'fas fa-phone',
            'description' => 'phoneNumbers'
          ],
          5 => [
            'url' => '#Admin/appSecrets',
            'label' => 'App Secrets',
            'iconClass' => 'fas fa-key',
            'description' => 'appSecrets'
          ],
          6 => [
            'url' => '#Admin/oAuthProviders',
            'label' => 'OAuth Providers',
            'iconClass' => 'fas fa-sign-in-alt',
            'description' => 'oAuthProviders'
          ],
          7 => [
            'url' => '#Admin/appLog',
            'label' => 'App Log',
            'iconClass' => 'fas fa-list',
            'description' => 'appLog'
          ]
        ],
        'order' => 25
      ],
      'misc' => [
        'label' => 'Misc',
        'itemList' => [
          0 => [
            'url' => '#Admin/formulaSandbox',
            'label' => 'Formula Sandbox',
            'iconClass' => 'fas fa-code',
            'description' => 'formulaSandbox',
            'view' => 'views/admin/formula-sandbox/index'
          ]
        ],
        'order' => 26
      ]
    ],
    'api' => [
      'globalMiddlewareClassNameList' => [],
      'routeMiddlewareClassNameListMap' => [],
      'controllerMiddlewareClassNameListMap' => [],
      'controllerActionMiddlewareClassNameListMap' => []
    ],
    'appParams' => [
      'templateEntityTypeList' => [
        'className' => 'Espo\\Classes\\AppParams\\TemplateEntityTypeList'
      ],
      'extensions' => [
        'className' => 'Espo\\Classes\\AppParams\\Extensions'
      ],
      'addressCountryData' => [
        'className' => 'Espo\\Classes\\AppParams\\AddressCountryData'
      ],
      'currencyRates' => [
        'className' => 'Espo\\Classes\\AppParams\\CurrencyRates'
      ]
    ],
    'authentication' => [
      'beforeLoginHookClassNameList' => [
        0 => 'Espo\\Core\\Authentication\\Hook\\Hooks\\FailedAttemptsLimit',
        1 => 'Espo\\Core\\Authentication\\Hook\\Hooks\\FailedCodeAttemptsLimit'
      ],
      'onLoginHookClassNameList' => [
        0 => 'Espo\\Core\\Authentication\\Hook\\Hooks\\IpAddressWhitelist'
      ],
      'onFailHookClassNameList' => [],
      'onSuccessHookClassNameList' => [],
      'onSuccessByTokenHookClassNameList' => [],
      'onSecondStepRequiredHookClassNameList' => []
    ],
    'authentication2FAMethods' => [
      'Totp' => [
        'settings' => [
          'isAvailable' => true
        ],
        'userApplyView' => 'views/user-security/modals/totp',
        'loginClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Totp\\TotpLogin',
        'userSetupClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Totp\\TotpUserSetup'
      ],
      'Email' => [
        'settings' => [
          'isAvailable' => true
        ],
        'userApplyView' => 'views/user-security/modals/two-factor-email',
        'loginClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Email\\EmailLogin',
        'userSetupClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Email\\EmailUserSetup'
      ],
      'Sms' => [
        'settings' => [
          'isAvailable' => true
        ],
        'userApplyView' => 'views/user-security/modals/two-factor-sms',
        'loginClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Sms\\SmsLogin',
        'userSetupClassName' => 'Espo\\Core\\Authentication\\TwoFactor\\Sms\\SmsUserSetup'
      ]
    ],
    'cleanup' => [
      'reminders' => [
        'className' => 'Espo\\Classes\\Cleanup\\Reminders',
        'order' => 10
      ],
      'webhookQueue' => [
        'className' => 'Espo\\Classes\\Cleanup\\WebhookQueue',
        'order' => 11
      ],
      'twoFactorCodes' => [
        'className' => 'Espo\\Classes\\Cleanup\\TwoFactorCodes'
      ],
      'massActions' => [
        'className' => 'Espo\\Classes\\Cleanup\\MassActions'
      ],
      'exports' => [
        'className' => 'Espo\\Classes\\Cleanup\\Exports'
      ],
      'passwordChangeRequests' => [
        'className' => 'Espo\\Classes\\Cleanup\\PasswordChangeRequests'
      ],
      'subscribers' => [
        'className' => 'Espo\\Classes\\Cleanup\\Subscribers'
      ],
      'audit' => [
        'className' => 'Espo\\Classes\\Cleanup\\Audit'
      ],
      'stars' => [
        'className' => 'Espo\\Classes\\Cleanup\\Stars'
      ],
      'appLog' => [
        'className' => 'Espo\\Classes\\Cleanup\\AppLog'
      ]
    ],
    'client' => [
      'scriptList' => [
        0 => 'client/lib/espo.js',
        1 => 'client/lib/espo-main.js'
      ],
      'developerModeScriptList' => [
        0 => 'client/src/loader.js'
      ],
      'linkList' => [
        0 => [
          'href' => 'client/fonts/inter/Inter-Regular.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ],
        1 => [
          'href' => 'client/fonts/inter/Inter-Medium.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ],
        2 => [
          'href' => 'client/fonts/inter/Inter-SemiBold.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ],
        3 => [
          'href' => 'client/fonts/inter/Inter-Bold.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ],
        4 => [
          'href' => 'client/fonts/fa-solid-900.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ],
        5 => [
          'href' => 'client/fonts/fa-regular-400.woff2',
          'as' => 'font',
          'type' => 'font/woff2',
          'rel' => 'preload',
          'noTimestamp' => true,
          'crossorigin' => true
        ]
      ]
    ],
    'clientIcons' => [
      'classList' => []
    ],
    'clientNavbar' => [
      'items' => [
        'globalSearch' => [
          'view' => 'views/global-search/global-search',
          'class' => 'navbar-form global-search-container',
          'order' => 5,
          'disabled' => false
        ],
        'quickCreate' => [
          'view' => 'views/site/navbar/quick-create',
          'class' => 'dropdown hidden-xs quick-create-container',
          'order' => 10,
          'disabled' => false
        ],
        'notificationBadge' => [
          'view' => 'views/notification/badge',
          'class' => 'dropdown notifications-badge-container',
          'order' => 15,
          'disabled' => false
        ]
      ],
      'menuItems' => [
        'admin' => [
          'order' => 0,
          'groupIndex' => 1,
          'link' => '#Admin',
          'labelTranslation' => 'Global.labels.Administration',
          'accessDataList' => [
            0 => [
              'isAdminOnly' => true
            ]
          ]
        ],
        'preferences' => [
          'order' => 1,
          'groupIndex' => 1,
          'link' => '#Preferences',
          'labelTranslation' => 'Global.labels.Preferences'
        ],
        'lastViewed' => [
          'order' => 0,
          'groupIndex' => 5,
          'link' => '#LastViewed',
          'labelTranslation' => 'Global.scopeNamesPlural.LastViewed',
          'configCheck' => '!actionHistoryDisabled',
          'handler' => 'handlers/navbar-menu',
          'actionFunction' => 'lastViewed'
        ],
        'about' => [
          'order' => 0,
          'groupIndex' => 10,
          'link' => '#About',
          'labelTranslation' => 'Global.labels.About'
        ],
        'logout' => [
          'order' => 1,
          'groupIndex' => 10,
          'labelTranslation' => 'Global.labels.Log Out',
          'handler' => 'handlers/navbar-menu',
          'actionFunction' => 'logout'
        ]
      ]
    ],
    'clientRecord' => [
      'panels' => [
        'activities' => [
          'name' => 'activities',
          'label' => 'Activities',
          'view' => 'crm:views/record/panels/activities',
          'aclScope' => 'Activities'
        ],
        'history' => [
          'name' => 'history',
          'label' => 'History',
          'view' => 'crm:views/record/panels/history',
          'aclScope' => 'Activities'
        ],
        'tasks' => [
          'name' => 'tasks',
          'label' => 'Tasks',
          'view' => 'crm:views/record/panels/tasks',
          'aclScope' => 'Task'
        ]
      ]
    ],
    'clientRoutes' => [
      'AddressMap/view/:entityType/:id/:field' => [
        'params' => [
          'controller' => 'AddressMap',
          'action' => 'view'
        ]
      ],
      'Admin/:page' => [
        'params' => [
          'controller' => 'Admin',
          'action' => 'page'
        ],
        'order' => 1
      ],
      'Admin/:page/:options' => [
        'params' => [
          'controller' => 'Admin',
          'action' => 'page'
        ],
        'order' => 1
      ],
      ':entityType/activities/:id/:targetEntityType' => [
        'params' => [
          'controller' => 'Activities',
          'action' => 'activities'
        ],
        'order' => 1
      ],
      ':entityType/history/:id/:targetEntityType' => [
        'params' => [
          'controller' => 'Activities',
          'action' => 'history'
        ],
        'order' => 1
      ]
    ],
    'complexExpression' => [
      'functionList' => [
        0 => [
          'name' => 'EQUAL',
          'insertText' => 'EQUAL:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        1 => [
          'name' => 'NOT_EQUAL',
          'insertText' => 'NOT_EQUAL:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        2 => [
          'name' => 'OR',
          'insertText' => 'OR:(EXPR1, EXPR2)',
          'returnType' => 'bool'
        ],
        3 => [
          'name' => 'AND',
          'insertText' => 'AND:(EXPR1, EXPR2)',
          'returnType' => 'bool'
        ],
        4 => [
          'name' => 'NOT',
          'insertText' => 'NOT:(EXPR)',
          'returnType' => 'bool'
        ],
        5 => [
          'name' => 'LIKE',
          'insertText' => 'LIKE:(VALUE, \'pattern%\')',
          'returnType' => 'bool'
        ],
        6 => [
          'name' => 'NOT_LIKE',
          'insertText' => 'NOT_LIKE:(VALUE, \'pattern%\')',
          'returnType' => 'bool'
        ],
        7 => [
          'name' => 'GREATER_THAN',
          'insertText' => 'GREATER_THAN:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        8 => [
          'name' => 'LESS_THAN',
          'insertText' => 'LESS_THAN:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        9 => [
          'name' => 'GREATER_THAN_OR_EQUAL',
          'insertText' => 'GREATER_THAN_OR_EQUAL:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        10 => [
          'name' => 'LESS_THAN_OR_EQUAL',
          'insertText' => 'LESS_THAN_OR_EQUAL:(VALUE1, VALUE2)',
          'returnType' => 'bool'
        ],
        11 => [
          'name' => 'IS_NULL',
          'insertText' => 'IS_NULL:(VALUE)',
          'returnType' => 'bool'
        ],
        12 => [
          'name' => 'IS_NOT_NULL',
          'insertText' => 'IS_NOT_NULL:(VALUE)',
          'returnType' => 'bool'
        ],
        13 => [
          'name' => 'IN',
          'insertText' => 'IN:(VALUE, VALUE1, VALUE2, VALUE3)',
          'returnType' => 'bool'
        ],
        14 => [
          'name' => 'NOT_IN',
          'insertText' => 'NOT_IN:(VALUE, VALUE1, VALUE2, VALUE3)',
          'returnType' => 'bool'
        ],
        15 => [
          'name' => 'IF',
          'insertText' => 'IF:(CONDITION, THEN_VALUE, ELSE_VALUE)'
        ],
        16 => [
          'name' => 'SWITCH',
          'insertText' => 'SWITCH:(CONDITION1, VALUE1, CONDITION2, VALUE2, ELSE_VALUE)'
        ],
        17 => [
          'name' => 'MAP',
          'insertText' => 'MAP:(EXPR, WHEN_VALUE1, THEN_VALUE1, WHEN_VALUE2, THEN_VALUE2, ELSE_VALUE)'
        ],
        18 => [
          'name' => 'MONTH_NUMBER',
          'insertText' => 'MONTH_NUMBER:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        19 => [
          'name' => 'WEEK_NUMBER_0',
          'insertText' => 'WEEK_NUMBER_0:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        20 => [
          'name' => 'WEEK_NUMBER_1',
          'insertText' => 'WEEK_NUMBER_1:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        21 => [
          'name' => 'DAYOFWEEK',
          'insertText' => 'DAYOFWEEK:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        22 => [
          'name' => 'DAYOFMONTH',
          'insertText' => 'DAYOFMONTH:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        23 => [
          'name' => 'YEAR',
          'insertText' => 'YEAR:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        24 => [
          'name' => 'HOUR',
          'insertText' => 'HOUR:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        25 => [
          'name' => 'MINUTE',
          'insertText' => 'MINUTE:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        26 => [
          'name' => 'MONTH',
          'insertText' => 'MONTH:(DATE_VALUE)',
          'returnType' => 'string'
        ],
        27 => [
          'name' => 'QUARTER',
          'insertText' => 'QUARTER:(DATE_VALUE)',
          'returnType' => 'string'
        ],
        28 => [
          'name' => 'WEEK',
          'insertText' => 'WEEK:(DATE_VALUE)',
          'returnType' => 'string'
        ],
        29 => [
          'name' => 'NOW',
          'insertText' => 'NOW:()',
          'returnType' => 'string'
        ],
        30 => [
          'name' => 'TZ',
          'insertText' => 'TZ:(DATE_VALUE, OFFSET)',
          'returnType' => 'string'
        ],
        31 => [
          'name' => 'UNIX_TIMESTAMP',
          'insertText' => 'UNIX_TIMESTAMP:(DATE_VALUE)',
          'returnType' => 'int'
        ],
        32 => [
          'name' => 'TIMESTAMPDIFF_YEAR',
          'insertText' => 'TIMESTAMPDIFF_YEAR:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        33 => [
          'name' => 'TIMESTAMPDIFF_MONTH',
          'insertText' => 'TIMESTAMPDIFF_MONTH:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        34 => [
          'name' => 'TIMESTAMPDIFF_WEEK',
          'insertText' => 'TIMESTAMPDIFF_WEEK:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        35 => [
          'name' => 'TIMESTAMPDIFF_DAY',
          'insertText' => 'TIMESTAMPDIFF_DAY:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        36 => [
          'name' => 'TIMESTAMPDIFF_HOUR',
          'insertText' => 'TIMESTAMPDIFF_HOUR:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        37 => [
          'name' => 'TIMESTAMPDIFF_MINUTE',
          'insertText' => 'TIMESTAMPDIFF_MINUTE:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        38 => [
          'name' => 'TIMESTAMPDIFF_SECOND',
          'insertText' => 'TIMESTAMPDIFF_SECOND:(DATE_FROM, DATE_TO)',
          'returnType' => 'int'
        ],
        39 => [
          'name' => 'CONCAT',
          'insertText' => 'CONCAT:(STRING1, STRING2)',
          'returnType' => 'string'
        ],
        40 => [
          'name' => 'LEFT',
          'insertText' => 'LEFT:(STRING, NUMBER_OF_CHARACTERS)',
          'returnType' => 'string'
        ],
        41 => [
          'name' => 'LOWER',
          'insertText' => 'LOWER:(STRING)',
          'returnType' => 'string'
        ],
        42 => [
          'name' => 'UPPER',
          'insertText' => 'UPPER:(STRING)',
          'returnType' => 'string'
        ],
        43 => [
          'name' => 'TRIM',
          'insertText' => 'TRIM:(STRING)',
          'returnType' => 'string'
        ],
        44 => [
          'name' => 'CHAR_LENGTH',
          'insertText' => 'CHAR_LENGTH:(STRING)',
          'returnType' => 'int'
        ],
        45 => [
          'name' => 'BINARY',
          'insertText' => 'BINARY:(STRING)',
          'returnType' => 'string'
        ],
        46 => [
          'name' => 'REPLACE',
          'insertText' => 'REPLACE:(HAYSTACK, NEEDLE, REPLACE_WITH)',
          'returnType' => 'string'
        ],
        47 => [
          'name' => 'ADD',
          'insertText' => 'ADD:(VALUE1, VALUE2)',
          'returnType' => 'float'
        ],
        48 => [
          'name' => 'SUB',
          'insertText' => 'SUB:(VALUE1, VALUE2)',
          'returnType' => 'float'
        ],
        49 => [
          'name' => 'MUL',
          'insertText' => 'MUL:(VALUE1, VALUE2)',
          'returnType' => 'float'
        ],
        50 => [
          'name' => 'DIV',
          'insertText' => 'DIV:(VALUE1, VALUE2)',
          'returnType' => 'float'
        ],
        51 => [
          'name' => 'MOD',
          'insertText' => 'MOD:(VALUE1, VALUE2)',
          'returnType' => 'float'
        ],
        52 => [
          'name' => 'FLOOR',
          'insertText' => 'FLOOR:(VALUE)',
          'returnType' => 'int'
        ],
        53 => [
          'name' => 'CEIL',
          'insertText' => 'CEIL:(VALUE)',
          'returnType' => 'int'
        ],
        54 => [
          'name' => 'ROUND',
          'insertText' => 'ROUND:(VALUE, PRECISION)',
          'returnType' => 'float'
        ],
        55 => [
          'name' => 'COUNT',
          'insertText' => 'COUNT:(EXPR)',
          'returnType' => 'int'
        ],
        56 => [
          'name' => 'SUM',
          'insertText' => 'SUM:(EXPR)',
          'returnType' => 'int|float'
        ],
        57 => [
          'name' => 'AVG',
          'insertText' => 'AVG:(EXPR)',
          'returnType' => 'float'
        ],
        58 => [
          'name' => 'MAX',
          'insertText' => 'MAX:(EXPR)',
          'returnType' => 'int|float'
        ],
        59 => [
          'name' => 'MIN',
          'insertText' => 'MIN:(EXPR)',
          'returnType' => 'int|float'
        ]
      ]
    ],
    'config' => [
      'entityTypeListParamList' => [
        0 => 'tabList',
        1 => 'quickCreateList',
        2 => 'globalSearchEntityList',
        3 => 'assignmentEmailNotificationsEntityList',
        4 => 'assignmentNotificationsEntityList',
        5 => 'calendarEntityList',
        6 => 'streamEmailNotificationsEntityList',
        7 => 'activitiesEntityList',
        8 => 'historyEntityList',
        9 => 'streamEmailNotificationsTypeList',
        10 => 'emailKeepParentTeamsEntityList'
      ],
      'params' => [
        'isDeveloperMode' => [
          'readOnly' => true
        ],
        'clientSecurityHeadersDisabled' => [
          'readOnly' => true
        ],
        'clientCspDisabled' => [
          'readOnly' => true
        ],
        'clientCspScriptSourceList' => [
          'readOnly' => true
        ],
        'clientStrictTransportSecurityHeaderDisabled' => [
          'readOnly' => true
        ],
        'clientXFrameOptionsHeaderDisabled' => [
          'readOnly' => true
        ],
        'systemUserId' => [
          'level' => 'admin',
          'readOnly' => true
        ],
        'smtpPassword' => [
          'level' => 'internal'
        ],
        'awsS3Storage' => [
          'level' => 'system'
        ],
        'defaultFileStorage' => [
          'level' => 'admin',
          'readOnly' => true
        ],
        'smsProvider' => [
          'level' => 'admin'
        ],
        'authAnotherUserDisabled' => [
          'level' => 'admin',
          'readOnly' => true
        ],
        'userNameRegularExpression' => [
          'readOnly' => true
        ],
        'workingTimeCalendar' => [
          'level' => 'admin'
        ],
        'ldapPassword' => [
          'level' => 'internal'
        ],
        'oidcClientId' => [
          'level' => 'admin'
        ],
        'oidcClientSecret' => [
          'level' => 'internal'
        ],
        'oidcAuthorizationEndpoint' => [
          'level' => 'admin'
        ],
        'oidcUserInfoEndpoint' => [
          'level' => 'admin'
        ],
        'oidcTokenEndpoint' => [
          'level' => 'admin'
        ],
        'oidcJwksEndpoint' => [
          'level' => 'admin'
        ],
        'oidcJwksCachePeriod' => [
          'level' => 'admin'
        ],
        'oidcJwtSignatureAlgorithmList' => [
          'level' => 'admin'
        ],
        'oidcScopes' => [
          'level' => 'admin'
        ],
        'oidcGroupClaim' => [
          'level' => 'admin'
        ],
        'oidcCreateUser' => [
          'level' => 'admin'
        ],
        'oidcUsernameClaim' => [
          'level' => 'admin'
        ],
        'oidcTeamsIds' => [
          'level' => 'admin'
        ],
        'oidcTeamsNames' => [
          'level' => 'admin'
        ],
        'oidcTeamsColumns' => [
          'level' => 'admin'
        ],
        'oidcSync' => [
          'level' => 'admin'
        ],
        'oidcSyncTeams' => [
          'level' => 'admin'
        ],
        'oidcFallback' => [
          'level' => 'admin'
        ],
        'oidcAllowRegularUserFallback' => [
          'level' => 'admin'
        ],
        'oidcAllowAdminUser' => [
          'level' => 'admin'
        ],
        'oidcAuthorizationPrompt' => [
          'level' => 'admin'
        ],
        'oidcAuthorizationMaxAge' => [
          'level' => 'admin'
        ],
        'oidcLogoutUrl' => [
          'level' => 'admin'
        ],
        'apiCorsAllowedMethodList' => [
          'level' => 'admin'
        ],
        'apiCorsAllowedHeaderList' => [
          'level' => 'admin'
        ],
        'apiCorsAllowedOriginList' => [
          'level' => 'admin'
        ],
        'apiCorsMaxAge' => [
          'level' => 'admin'
        ],
        'customExportManifest' => [
          'level' => 'admin'
        ],
        'starsLimit' => [
          'level' => 'admin'
        ],
        'authIpAddressCheck' => [
          'level' => 'superAdmin'
        ],
        'authIpAddressWhitelist' => [
          'level' => 'superAdmin'
        ],
        'authIpAddressCheckExcludedUsers' => [
          'level' => 'superAdmin'
        ],
        'availableReactions' => [
          'level' => 'default'
        ],
        'emailScheduledBatchCount' => [
          'level' => 'admin'
        ],
        'streamEmailWithContentEntityTypeList' => [
          'level' => 'admin'
        ],
        'baselineRole' => [
          'level' => 'admin'
        ],
        'currencyRates' => [
          'readOnly' => true
        ]
      ]
    ],
    'consoleCommands' => [
      'import' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\Import',
        'listed' => true
      ],
      'clearCache' => [
        'listed' => true,
        'noSystemUser' => true
      ],
      'rebuild' => [
        'listed' => true,
        'noSystemUser' => true,
        'allowedFlags' => [
          0 => 'hard',
          1 => 'y'
        ]
      ],
      'updateAppTimestamp' => [
        'listed' => true,
        'noSystemUser' => true
      ],
      'appInfo' => [
        'listed' => true
      ],
      'setPassword' => [
        'listed' => true
      ],
      'upgrade' => [
        'listed' => true
      ],
      'extension' => [
        'listed' => true
      ],
      'runJob' => [
        'listed' => true,
        'allowedOptions' => [
          0 => 'job',
          1 => 'targetId',
          2 => 'targetType'
        ]
      ],
      'version' => [
        'listed' => true,
        'noSystemUser' => true
      ],
      'createAdminUser' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\CreateAdminUser',
        'listed' => true
      ],
      'rebuildCategoryPaths' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\RebuildCategoryPaths',
        'listed' => true
      ],
      'populateArrayValues' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\PopulateArrayValues',
        'listed' => true
      ],
      'populateNumbers' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\PopulateNumbers',
        'listed' => false
      ],
      'checkFilePermissions' => [
        'className' => 'Espo\\Classes\\ConsoleCommands\\CheckFilePermissions',
        'listed' => true,
        'noSystemUser' => true
      ],
      'migrate' => [
        'listed' => true,
        'noSystemUser' => true
      ],
      'migrationVersionStep' => [
        'listed' => false,
        'noSystemUser' => true
      ]
    ],
    'containerServices' => [
      'authTokenManager' => [
        'className' => 'Espo\\Core\\Authentication\\AuthToken\\EspoManager'
      ],
      'ormMetadataData' => [
        'className' => 'Espo\\Core\\Utils\\Metadata\\OrmMetadataData'
      ],
      'classFinder' => [
        'className' => 'Espo\\Core\\Utils\\ClassFinder'
      ],
      'fileStorageManager' => [
        'className' => 'Espo\\Core\\FileStorage\\Manager'
      ],
      'jobManager' => [
        'className' => 'Espo\\Core\\Job\\JobManager'
      ],
      'webSocketSubmission' => [
        'className' => 'Espo\\Core\\WebSocket\\Submission'
      ],
      'crypt' => [
        'className' => 'Espo\\Core\\Utils\\Crypt'
      ],
      'passwordHash' => [
        'className' => 'Espo\\Core\\Utils\\PasswordHash'
      ],
      'number' => [
        'loaderClassName' => 'Espo\\Core\\Loaders\\NumberUtil'
      ],
      'selectManagerFactory' => [
        'className' => 'Espo\\Core\\Select\\SelectManagerFactory'
      ],
      'serviceFactory' => [
        'className' => 'Espo\\Core\\ServiceFactory'
      ],
      'recordServiceContainer' => [
        'className' => 'Espo\\Core\\Record\\ServiceContainer'
      ],
      'templateFileManager' => [
        'className' => 'Espo\\Core\\Utils\\TemplateFileManager'
      ],
      'webhookManager' => [
        'className' => 'Espo\\Core\\Webhook\\Manager'
      ],
      'hookManager' => [
        'className' => 'Espo\\Core\\HookManager'
      ],
      'clientManager' => [
        'className' => 'Espo\\Core\\Utils\\ClientManager'
      ],
      'themeManager' => [
        'className' => 'Espo\\Core\\Utils\\ThemeManager'
      ],
      'fieldUtil' => [
        'className' => 'Espo\\Core\\Utils\\FieldUtil'
      ],
      'emailSender' => [
        'className' => 'Espo\\Core\\Mail\\EmailSender'
      ],
      'mailSender' => [
        'className' => 'Espo\\Core\\Mail\\Sender'
      ],
      'htmlizerFactory' => [
        'className' => 'Espo\\Core\\Htmlizer\\HtmlizerFactory'
      ],
      'fieldValidationManager' => [
        'className' => 'Espo\\Core\\FieldValidation\\FieldValidationManager'
      ],
      'assignmentCheckerManager' => [
        'className' => 'Espo\\Core\\Acl\\AssignmentChecker\\AssignmentCheckerManager'
      ],
      'hasher' => [
        'className' => 'Espo\\Core\\Utils\\Hasher'
      ],
      'emailFilterManager' => [
        'className' => 'Espo\\Core\\Utils\\EmailFilterManager'
      ],
      'externalAccountClientManager' => [
        'className' => 'Espo\\Core\\ExternalAccount\\ClientManager'
      ],
      'formulaManager' => [
        'className' => 'Espo\\Core\\Formula\\Manager'
      ],
      'user' => [
        'settable' => true
      ],
      'streamService' => [
        'className' => 'Espo\\Tools\\Stream\\Service'
      ],
      'systemConfig' => [
        'className' => 'Espo\\Core\\Utils\\Config\\SystemConfig'
      ],
      'applicationConfig' => [
        'className' => 'Espo\\Core\\Utils\\Config\\ApplicationConfig'
      ]
    ],
    'currency' => [
      'symbolMap' => [
        'AED' => 'د.إ',
        'AFN' => '؋',
        'ALL' => 'L',
        'ANG' => 'ƒ',
        'AOA' => 'Kz',
        'ARS' => '$',
        'AUD' => '$',
        'AWG' => 'ƒ',
        'AZN' => '₼',
        'BAM' => 'KM',
        'BBD' => '$',
        'BDT' => '৳',
        'BGN' => 'лв',
        'BHD' => '.د.ب',
        'BIF' => 'FBu',
        'BMD' => '$',
        'BND' => '$',
        'BOB' => 'Bs.',
        'BRL' => 'R$',
        'BSD' => '$',
        'BTN' => 'Nu.',
        'BWP' => 'P',
        'BYN' => 'Br',
        'BYR' => 'p.',
        'BZD' => 'BZ$',
        'CAD' => '$',
        'CDF' => 'FC',
        'CHF' => 'Fr.',
        'CLP' => '$',
        'CNY' => '¥',
        'COP' => '$',
        'CRC' => '₡',
        'CUC' => '$',
        'CUP' => '₱',
        'CVE' => '$',
        'CZK' => 'Kč',
        'DJF' => 'Fdj',
        'DKK' => 'kr',
        'DOP' => 'RD$',
        'DZD' => 'دج',
        'EEK' => 'kr',
        'EGP' => '£',
        'ERN' => 'Nfk',
        'ETB' => 'Br',
        'EUR' => '€',
        'FJD' => '$',
        'FKP' => '£',
        'GBP' => '£',
        'GEL' => '₾',
        'GGP' => '£',
        'GHC' => '₵',
        'GHS' => 'GH₵',
        'GIP' => '£',
        'GMD' => 'D',
        'GNF' => 'FG',
        'GTQ' => 'Q',
        'GYD' => '$',
        'HKD' => '$',
        'HNL' => 'L',
        'HRK' => 'kn',
        'HTG' => 'G',
        'HUF' => 'Ft',
        'IDR' => 'Rp',
        'ILS' => '₪',
        'IMP' => '£',
        'INR' => '₹',
        'IQD' => 'ع.د',
        'IRR' => '﷼',
        'ISK' => 'kr',
        'JEP' => '£',
        'JMD' => 'J$',
        'JPY' => '¥',
        'KES' => 'KSh',
        'KGS' => 'лв',
        'KHR' => '៛',
        'KMF' => 'CF',
        'KPW' => '₩',
        'KRW' => '₩',
        'KYD' => '$',
        'KZT' => '₸',
        'LAK' => '₭',
        'LBP' => '£',
        'LKR' => '₨',
        'LRD' => '$',
        'LSL' => 'M',
        'LTL' => 'Lt',
        'LVL' => 'Ls',
        'MAD' => 'MAD',
        'MDL' => 'lei',
        'MGA' => 'Ar',
        'MKD' => 'ден',
        'MMK' => 'K',
        'MNT' => '₮',
        'MOP' => 'MOP$',
        'MUR' => '₨',
        'MVR' => 'Rf',
        'MWK' => 'MK',
        'MXN' => '$',
        'MYR' => 'RM',
        'MZN' => 'MT',
        'NAD' => '$',
        'NGN' => '₦',
        'NIO' => 'C$',
        'NOK' => 'kr',
        'NPR' => '₨',
        'NZD' => '$',
        'OMR' => '﷼',
        'PAB' => 'B/.',
        'PEN' => 'S/.',
        'PGK' => 'K',
        'PHP' => '₱',
        'PKR' => '₨',
        'PLN' => 'zł',
        'PYG' => 'Gs',
        'QAR' => '﷼',
        'RMB' => '￥',
        'RON' => 'lei',
        'RSD' => 'Дин.',
        'RUB' => '₽',
        'RWF' => 'R₣',
        'SAR' => '﷼',
        'SBD' => '$',
        'SCR' => '₨',
        'SDG' => 'ج.س.',
        'SEK' => 'kr',
        'SGD' => '$',
        'SHP' => '£',
        'SLL' => 'Le',
        'SOS' => 'S',
        'SRD' => '$',
        'SSP' => '£',
        'STD' => 'Db',
        'SVC' => '$',
        'SYP' => '£',
        'SZL' => 'E',
        'THB' => '฿',
        'TJS' => 'SM',
        'TMT' => 'T',
        'TND' => 'د.ت',
        'TOP' => 'T$',
        'TRL' => '₤',
        'TRY' => '₺',
        'TTD' => 'TT$',
        'TVD' => '$',
        'TWD' => 'NT$',
        'TZS' => 'TSh',
        'UAH' => '₴',
        'UGX' => 'USh',
        'USD' => '$',
        'UYU' => '$U',
        'UZS' => 'лв',
        'VEF' => 'Bs',
        'VND' => '₫',
        'VUV' => 'VT',
        'WST' => 'WS$',
        'XAF' => 'FCFA',
        'XBT' => 'Ƀ',
        'XCD' => '$',
        'XOF' => 'CFA',
        'XPF' => '₣',
        'YER' => '﷼',
        'ZAR' => 'R',
        'ZWD' => 'Z$',
        'BTC' => '฿'
      ],
      'list' => [
        0 => 'AFN',
        1 => 'AED',
        2 => 'ALL',
        3 => 'ANG',
        4 => 'AOA',
        5 => 'ARS',
        6 => 'AUD',
        7 => 'BAM',
        8 => 'BDT',
        9 => 'BGN',
        10 => 'BHD',
        11 => 'BND',
        12 => 'BOB',
        13 => 'BRL',
        14 => 'BWP',
        15 => 'BYN',
        16 => 'CAD',
        17 => 'CHF',
        18 => 'CLP',
        19 => 'CNY',
        20 => 'COP',
        21 => 'CRC',
        22 => 'CVE',
        23 => 'CZK',
        24 => 'DKK',
        25 => 'DOP',
        26 => 'DZD',
        27 => 'EGP',
        28 => 'EUR',
        29 => 'FJD',
        30 => 'GBP',
        31 => 'GNF',
        32 => 'GTQ',
        33 => 'HKD',
        34 => 'HNL',
        35 => 'HRK',
        36 => 'HUF',
        37 => 'IDR',
        38 => 'ILS',
        39 => 'INR',
        40 => 'IRR',
        41 => 'JMD',
        42 => 'JOD',
        43 => 'JPY',
        44 => 'KES',
        45 => 'KRW',
        46 => 'KWD',
        47 => 'KYD',
        48 => 'KZT',
        49 => 'LBP',
        50 => 'LKR',
        51 => 'MAD',
        52 => 'MDL',
        53 => 'MKD',
        54 => 'MMK',
        55 => 'MUR',
        56 => 'MXN',
        57 => 'MYR',
        58 => 'MZN',
        59 => 'NAD',
        60 => 'NGN',
        61 => 'NIO',
        62 => 'NOK',
        63 => 'NPR',
        64 => 'NZD',
        65 => 'OMR',
        66 => 'PEN',
        67 => 'PGK',
        68 => 'PHP',
        69 => 'PKR',
        70 => 'PLN',
        71 => 'PYG',
        72 => 'QAR',
        73 => 'RON',
        74 => 'RSD',
        75 => 'RUB',
        76 => 'SAR',
        77 => 'SCR',
        78 => 'SEK',
        79 => 'SGD',
        80 => 'SLL',
        81 => 'SVC',
        82 => 'THB',
        83 => 'TND',
        84 => 'TRY',
        85 => 'TTD',
        86 => 'TWD',
        87 => 'TZS',
        88 => 'UAH',
        89 => 'UGX',
        90 => 'USD',
        91 => 'UYU',
        92 => 'UZS',
        93 => 'VND',
        94 => 'XAF',
        95 => 'YER',
        96 => 'ZAR',
        97 => 'ZMW',
        98 => 'ZWL'
      ],
      'precisionMap' => [
        'BHD' => 3,
        'BIF' => 0,
        'CLP' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'ISK' => 0,
        'IQD' => 3,
        'JOD' => 3,
        'JPY' => 0,
        'KMF' => 0,
        'KRW' => 0,
        'KWD' => 3,
        'LYD' => 3,
        'OMR' => 3,
        'PYG' => 0,
        'RWF' => 0,
        'TND' => 3,
        'UGX' => 0,
        'UYI' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XAU' => 0,
        'XBA' => 0,
        'XBB' => 0,
        'XBC' => 0,
        'XDR' => 0,
        'XOF' => 0,
        'XPF' => 0
      ]
    ],
    'currencyConversion' => [
      'entityConverterClassNameMap' => []
    ],
    'databasePlatforms' => [
      'Mysql' => [
        'detailsProviderClassName' => 'Espo\\Core\\Utils\\Database\\DetailsProviders\\MysqlDetailsProvider',
        'dbalConnectionFactoryClassName' => 'Espo\\Core\\Utils\\Database\\Dbal\\Factories\\MysqlConnectionFactory',
        'indexHelperClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\IndexHelpers\\MysqlIndexHelper',
        'columnPreparatorClassName' => 'Espo\\Core\\Utils\\Database\\Schema\\ColumnPreparators\\MysqlColumnPreparator',
        'preRebuildActionClassNameList' => [
          0 => 'Espo\\Core\\Utils\\Database\\Schema\\RebuildActions\\PrepareForFulltextIndex'
        ],
        'postRebuildActionClassNameList' => [],
        'dbalTypeClassNameMap' => [
          'mediumtext' => 'Espo\\Core\\Utils\\Database\\Dbal\\Types\\MediumtextType',
          'longtext' => 'Espo\\Core\\Utils\\Database\\Dbal\\Types\\LongtextType',
          'uuid' => 'Espo\\Core\\Utils\\Database\\Dbal\\Types\\UuidType'
        ]
      ],
      'Postgresql' => [
        'detailsProviderClassName' => 'Espo\\Core\\Utils\\Database\\DetailsProviders\\PostgresqlDetailsProvider',
        'dbalConnectionFactoryClassName' => 'Espo\\Core\\Utils\\Database\\Dbal\\Factories\\PostgresqlConnectionFactory',
        'indexHelperClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\IndexHelpers\\PostgresqlIndexHelper',
        'columnPreparatorClassName' => 'Espo\\Core\\Utils\\Database\\Schema\\ColumnPreparators\\PostgresqlColumnPreparator',
        'dbalTypeClassNameMap' => [
          'uuid' => 'Espo\\Core\\Utils\\Database\\Dbal\\Types\\UuidType'
        ]
      ]
    ],
    'dateTime' => [
      'dateFormatList' => [
        0 => 'DD.MM.YYYY',
        1 => 'MM/DD/YYYY',
        2 => 'DD/MM/YYYY',
        3 => 'YYYY-MM-DD',
        4 => 'DD. MM. YYYY'
      ],
      'timeFormatList' => [
        0 => 'HH:mm',
        1 => 'hh:mma',
        2 => 'hh:mmA',
        3 => 'hh:mm A',
        4 => 'hh:mm a'
      ]
    ],
    'defaultDashboardLayouts' => [
      'Standard' => [
        0 => [
          'name' => 'My Espo',
          'layout' => [
            0 => [
              'id' => 'defaultActivities',
              'name' => 'Activities',
              'x' => 2,
              'y' => 2,
              'width' => 2,
              'height' => 2
            ],
            1 => [
              'id' => 'defaultStream',
              'name' => 'Stream',
              'x' => 0,
              'y' => 0,
              'width' => 2,
              'height' => 4
            ],
            2 => [
              'id' => 'defaultTasks',
              'name' => 'Tasks',
              'x' => 2,
              'y' => 4,
              'width' => 2,
              'height' => 2
            ]
          ]
        ]
      ]
    ],
    'defaultDashboardOptions' => [
      'Standard' => [
        'defaultStream' => [
          'displayRecords' => 10
        ]
      ]
    ],
    'emailTemplate' => [
      'placeholders' => [
        'today' => [
          'className' => 'Espo\\Tools\\EmailTemplate\\Placeholders\\Today',
          'order' => 0
        ],
        'now' => [
          'className' => 'Espo\\Tools\\EmailTemplate\\Placeholders\\Now',
          'order' => 1
        ],
        'currentYear' => [
          'className' => 'Espo\\Tools\\EmailTemplate\\Placeholders\\CurrentYear',
          'order' => 2
        ]
      ],
      'entityLinkMapping' => [
        'Contact' => [
          'Account' => 'account'
        ],
        'Opportunity' => [
          'Account' => 'account',
          'Contact' => 'contact'
        ],
        'Case' => [
          'Account' => 'account',
          'Contact' => 'contact'
        ]
      ]
    ],
    'entityManager' => [
      'createHookClassNameList' => [
        0 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\PlusCreateHook',
        1 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\EventCreateHook'
      ],
      'deleteHookClassNameList' => [
        0 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\PlusDeleteHook',
        1 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\EventDeleteHook',
        2 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\DeleteHasChildrenLinks'
      ],
      'updateHookClassNameList' => [
        0 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\AssignedUsersUpdateHook',
        1 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\CollaboratorsUpdateHook',
        2 => 'Espo\\Tools\\EntityManager\\Hook\\Hooks\\StreamUpdateHook'
      ]
    ],
    'entityManagerParams' => [
      'Global' => [
        'stars' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'optimisticConcurrencyControl' => [
          'location' => 'entityDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'preserveAuditLog' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      '@Company' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'aclContactLink' => [
          'location' => 'aclDefs',
          'param' => 'contactLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-contact-link'
          ]
        ],
        'aclAccountLink' => [
          'location' => 'aclDefs',
          'param' => 'accountLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-account-link'
          ]
        ]
      ],
      '@Person' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'aclContactLink' => [
          'location' => 'aclDefs',
          'param' => 'contactLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-contact-link'
          ]
        ],
        'aclAccountLink' => [
          'location' => 'aclDefs',
          'param' => 'accountLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true
          ],
          'view' => 'views/admin/entity-manager/fields/acl-account-link'
        ]
      ],
      '@Base' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'aclContactLink' => [
          'location' => 'aclDefs',
          'param' => 'contactLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-contact-link'
          ]
        ],
        'aclAccountLink' => [
          'location' => 'aclDefs',
          'param' => 'accountLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-account-link'
          ]
        ]
      ],
      '@BasePlus' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'aclContactLink' => [
          'location' => 'aclDefs',
          'param' => 'contactLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-contact-link'
          ]
        ],
        'aclAccountLink' => [
          'location' => 'aclDefs',
          'param' => 'accountLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-account-link'
          ]
        ]
      ],
      'Account' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Contact' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Lead' => [
        'updateDuplicateCheck' => [
          'location' => 'recordDefs',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'duplicateCheckFieldList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/duplicate-check-field-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Opportunity' => [
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Document' => [
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Case' => [
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'KnowledgeBaseArticle' => [
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'Meeting' => [
        'activityStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'historyStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'completedStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'canceledStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ]
      ],
      'Call' => [
        'activityStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'historyStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'completedStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'canceledStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ]
      ],
      'Task' => [
        'completedStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'canceledStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      'TargetList' => [
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ]
      ],
      '@Event' => [
        'activityStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'historyStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'completedStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'canceledStatusList' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'multiEnum',
            'required' => true,
            'tooltip' => true,
            'view' => 'crm:views/admin/entity-manager/fields/status-list'
          ]
        ],
        'collaborators' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'assignedUsers' => [
          'location' => 'scopes',
          'fieldDefs' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'aclContactLink' => [
          'location' => 'aclDefs',
          'param' => 'contactLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-contact-link'
          ]
        ],
        'aclAccountLink' => [
          'location' => 'aclDefs',
          'param' => 'accountLink',
          'fieldDefs' => [
            'type' => 'enum',
            'tooltip' => true,
            'view' => 'views/admin/entity-manager/fields/acl-account-link'
          ]
        ]
      ]
    ],
    'entityTemplateList' => [
      0 => 'Base',
      1 => 'BasePlus',
      2 => 'Event',
      3 => 'Person',
      4 => 'Company'
    ],
    'entityTemplates' => [
      'Base' => [
        'entityClassName' => 'Espo\\Core\\Templates\\Entities\\Base',
        'repositoryClassName' => 'Espo\\Core\\Templates\\Repositories\\Base'
      ],
      'BasePlus' => [
        'entityClassName' => 'Espo\\Core\\Templates\\Entities\\BasePlus',
        'repositoryClassName' => 'Espo\\Core\\Templates\\Repositories\\BasePlus'
      ],
      'Event' => [
        'entityClassName' => 'Espo\\Core\\Templates\\Entities\\Event',
        'repositoryClassName' => 'Espo\\Core\\Templates\\Repositories\\Event'
      ],
      'Company' => [
        'entityClassName' => 'Espo\\Core\\Templates\\Entities\\Company',
        'repositoryClassName' => 'Espo\\Core\\Templates\\Repositories\\Company'
      ],
      'Person' => [
        'entityClassName' => 'Espo\\Core\\Templates\\Entities\\Person',
        'repositoryClassName' => 'Espo\\Core\\Templates\\Repositories\\Person'
      ]
    ],
    'export' => [
      'formatList' => [
        0 => 'xlsx',
        1 => 'csv'
      ],
      'formatDefs' => [
        'csv' => [
          'processorClassName' => 'Espo\\Tools\\Export\\Format\\Csv\\Processor',
          'additionalFieldsLoaderClassName' => 'Espo\\Tools\\Export\\Format\\Csv\\AdditionalFieldsLoader',
          'mimeType' => 'text/csv',
          'fileExtension' => 'csv'
        ],
        'xlsx' => [
          'processorClassName' => 'Espo\\Tools\\Export\\Format\\Xlsx\\Processor',
          'processorParamsHandler' => 'Espo\\Tools\\Export\\Format\\Xlsx\\ParamsHandler',
          'additionalFieldsLoaderClassName' => 'Espo\\Tools\\Export\\Format\\Xlsx\\AdditionalFieldsLoader',
          'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'fileExtension' => 'xlsx',
          'cellValuePreparatorClassNameMap' => [
            'link' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Link',
            'linkOne' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Link',
            'linkParent' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Link',
            'file' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Link',
            'bool' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Boolean',
            'int' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Integer',
            'float' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Floating',
            'currency' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Currency',
            'currencyConverted' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\CurrencyConverted',
            'personName' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\PersonName',
            'date' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Date',
            'datetime' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\DateTime',
            'datetimeOptional' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\DateTimeOptional',
            'linkMultiple' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\LinkMultiple',
            'attachmentMultiple' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\LinkMultiple',
            'address' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Address',
            'duration' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Duration',
            'enum' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\Enumeration',
            'multiEnum' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\MultiEnum',
            'array' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\MultiEnum',
            'checklist' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\MultiEnum',
            'urlMultiple' => 'Espo\\Tools\\Export\\Format\\Xlsx\\CellValuePreparators\\MultiEnum'
          ],
          'params' => [
            'fields' => [
              'lite' => [
                'type' => 'bool',
                'default' => false,
                'tooltip' => true
              ],
              'recordLinks' => [
                'type' => 'bool',
                'default' => false
              ],
              'title' => [
                'type' => 'bool',
                'default' => false,
                'tooltip' => true
              ]
            ],
            'layout' => [
              0 => [
                0 => [
                  'name' => 'lite'
                ],
                1 => [
                  'name' => 'recordLinks'
                ],
                2 => [
                  'name' => 'title'
                ]
              ]
            ],
            'dynamicLogic' => [
              'recordLinks' => [
                'visible' => [
                  'conditionGroup' => [
                    0 => [
                      'type' => 'isFalse',
                      'attribute' => 'xlsxLite'
                    ]
                  ]
                ]
              ],
              'title' => [
                'visible' => [
                  'conditionGroup' => [
                    0 => [
                      'type' => 'isFalse',
                      'attribute' => 'xlsxLite'
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'fieldProcessing' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Link\\HasOneLoader',
        1 => 'Espo\\Core\\FieldProcessing\\Link\\NotJoinedLoader',
        2 => 'Espo\\Core\\FieldProcessing\\LinkMultiple\\Loader',
        3 => 'Espo\\Core\\FieldProcessing\\LinkParent\\Loader',
        4 => 'Espo\\Core\\FieldProcessing\\EmailAddress\\Loader',
        5 => 'Espo\\Core\\FieldProcessing\\PhoneNumber\\Loader',
        6 => 'Espo\\Core\\FieldProcessing\\Stream\\FollowersLoader',
        7 => 'Espo\\Core\\FieldProcessing\\Stars\\StarLoader'
      ],
      'listLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\LinkParent\\Loader',
        1 => 'Espo\\Core\\FieldProcessing\\LinkMultiple\\ListLoader'
      ],
      'saverClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\EmailAddress\\Saver',
        1 => 'Espo\\Core\\FieldProcessing\\PhoneNumber\\Saver',
        2 => 'Espo\\Core\\FieldProcessing\\Relation\\Saver',
        3 => 'Espo\\Core\\FieldProcessing\\MultiEnum\\Saver',
        4 => 'Espo\\Core\\FieldProcessing\\File\\Saver',
        5 => 'Espo\\Core\\FieldProcessing\\Wysiwyg\\Saver'
      ]
    ],
    'file' => [
      'extensionMimeTypeMap' => [
        'aac' => [
          0 => 'audio/aac'
        ],
        'abw' => [
          0 => 'application/x-abiword'
        ],
        'arc' => [
          0 => 'application/x-freearc'
        ],
        'avif' => [
          0 => 'image/avif'
        ],
        'avi' => [
          0 => 'video/x-msvideo'
        ],
        'azw' => [
          0 => 'application/vnd.amazon.ebook'
        ],
        'bin' => [
          0 => 'application/octet-stream'
        ],
        'bmp' => [
          0 => 'image/bmp'
        ],
        'bz' => [
          0 => 'application/x-bzip'
        ],
        'bz2' => [
          0 => 'application/x-bzip2'
        ],
        'cda' => [
          0 => 'application/x-cdf'
        ],
        'csh' => [
          0 => 'application/x-csh'
        ],
        'css' => [
          0 => 'text/css'
        ],
        'csv' => [
          0 => 'text/csv'
        ],
        'doc' => [
          0 => 'application/msword'
        ],
        'docx' => [
          0 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        'eot' => [
          0 => 'application/vnd.ms-fontobject'
        ],
        'epub' => [
          0 => 'application/epub+zip'
        ],
        'gz' => [
          0 => 'application/gzip'
        ],
        'gif' => [
          0 => 'image/gif'
        ],
        'htm' => [
          0 => 'text/html'
        ],
        'html' => [
          0 => 'text/html'
        ],
        'ico' => [
          0 => 'image/vnd.microsoft.icon'
        ],
        'ics' => [
          0 => 'text/calendar'
        ],
        'jar' => [
          0 => 'application/java-archive'
        ],
        'jpeg' => [
          0 => 'image/jpeg'
        ],
        'jpg' => [
          0 => 'image/jpeg'
        ],
        'js' => [
          0 => 'text/javascript'
        ],
        'json' => [
          0 => 'application/json'
        ],
        'jsonld' => [
          0 => 'application/ld+json'
        ],
        'mid' => [
          0 => 'audio/midi',
          1 => 'audio/x-midi'
        ],
        'midi' => [
          0 => 'audio/midi',
          1 => 'audio/x-midi'
        ],
        'mjs' => [
          0 => 'text/javascript'
        ],
        'mp3' => [
          0 => 'audio/mpeg'
        ],
        'mp4' => [
          0 => 'video/mp4'
        ],
        'mpeg' => [
          0 => 'video/mpeg'
        ],
        'mpkg' => [
          0 => 'application/vnd.apple.installer+xml'
        ],
        'odp' => [
          0 => 'application/vnd.oasis.opendocument.presentation'
        ],
        'ods' => [
          0 => 'application/vnd.oasis.opendocument.spreadsheet'
        ],
        'odt' => [
          0 => 'application/vnd.oasis.opendocument.text'
        ],
        'oga' => [
          0 => 'audio/ogg'
        ],
        'ogv' => [
          0 => 'video/ogg'
        ],
        'ogx' => [
          0 => 'application/ogg'
        ],
        'opus' => [
          0 => 'audio/opus'
        ],
        'otf' => [
          0 => 'font/otf'
        ],
        'png' => [
          0 => 'image/png'
        ],
        'pdf' => [
          0 => 'application/pdf'
        ],
        'php' => [
          0 => 'application/x-httpd-php'
        ],
        'ppt' => [
          0 => 'application/vnd.ms-powerpoint'
        ],
        'pptx' => [
          0 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ],
        'rar' => [
          0 => 'application/vnd.rar'
        ],
        'rtf' => [
          0 => 'application/rtf'
        ],
        'sh' => [
          0 => 'application/x-sh'
        ],
        'svg' => [
          0 => 'image/svg+xml'
        ],
        'swf' => [
          0 => 'application/x-shockwave-flash'
        ],
        'tar' => [
          0 => 'application/x-tar'
        ],
        'tif' => [
          0 => 'image/tiff'
        ],
        'tiff' => [
          0 => 'image/tiff'
        ],
        'ts' => [
          0 => 'video/mp2t'
        ],
        'ttf' => [
          0 => 'font/ttf'
        ],
        'txt' => [
          0 => 'text/plain'
        ],
        'vsd' => [
          0 => 'application/vnd.visio'
        ],
        'wav' => [
          0 => 'audio/wav'
        ],
        'weba' => [
          0 => 'audio/webm'
        ],
        'webm' => [
          0 => 'video/webm'
        ],
        'webp' => [
          0 => 'image/webp'
        ],
        'woff' => [
          0 => 'font/woff'
        ],
        'woff2' => [
          0 => 'font/woff2'
        ],
        'xhtml' => [
          0 => 'application/xhtml+xml'
        ],
        'xls' => [
          0 => 'application/vnd.ms-excel'
        ],
        'xlsx' => [
          0 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        'xml' => [
          0 => 'application/xml'
        ],
        'xul' => [
          0 => 'application/vnd.mozilla.xul+xml'
        ],
        'zip' => [
          0 => 'application/zip'
        ],
        '3gp' => [
          0 => 'video/3gpp',
          1 => 'audio/3gpp'
        ],
        '3g2' => [
          0 => 'video/3gpp2',
          1 => 'audio/3gpp2'
        ],
        '7z' => [
          0 => 'application/x-7z-compressed'
        ],
        'md' => [
          0 => 'text/markdown'
        ]
      ],
      'inlineMimeTypeList' => [
        0 => 'application/pdf',
        1 => 'text/plain',
        2 => 'audio/wav',
        3 => 'audio/mpeg',
        4 => 'audio/ogg',
        5 => 'audio/webm',
        6 => 'video/mpeg',
        7 => 'video/mp4',
        8 => 'video/ogg',
        9 => 'video/webm',
        10 => 'image/jpeg',
        11 => 'image/png',
        12 => 'image/gif',
        13 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        14 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        15 => 'application/vnd.ms-word',
        16 => 'application/vnd.ms-excel',
        17 => 'application/vnd.oasis.opendocument.text',
        18 => 'application/vnd.oasis.opendocument.spreadsheet',
        19 => 'application/msword',
        20 => 'application/msexcel'
      ]
    ],
    'fileStorage' => [
      'implementationClassNameMap' => [
        'EspoUploadDir' => 'Espo\\Core\\FileStorage\\Storages\\EspoUploadDir',
        'AwsS3' => 'Espo\\Core\\FileStorage\\Storages\\AwsS3'
      ]
    ],
    'formula' => [
      'functionList' => [
        0 => [
          'name' => 'ifThenElse',
          'insertText' => 'ifThenElse(CONDITION, CONSEQUENT, ALTERNATIVE)'
        ],
        1 => [
          'name' => 'ifThen',
          'insertText' => 'ifThen(CONDITION, CONSEQUENT)'
        ],
        2 => [
          'name' => 'list',
          'insertText' => 'list()',
          'returnType' => 'array'
        ],
        3 => [
          'name' => 'string\\concatenate',
          'insertText' => 'string\\concatenate(STRING_1, STRING_2)',
          'returnType' => 'string'
        ],
        4 => [
          'name' => 'string\\substring',
          'insertText' => 'string\\substring(STRING, START, LENGTH)',
          'returnType' => 'string'
        ],
        5 => [
          'name' => 'string\\contains',
          'insertText' => 'string\\contains(STRING, NEEDLE)',
          'returnType' => 'bool'
        ],
        6 => [
          'name' => 'string\\pos',
          'insertText' => 'string\\pos(STRING, NEEDLE)',
          'returnType' => 'int'
        ],
        7 => [
          'name' => 'string\\pad',
          'insertText' => 'string\\pad(STRING, LENGTH, PAD_STRING)',
          'returnType' => 'string'
        ],
        8 => [
          'name' => 'string\\test',
          'insertText' => 'string\\test(STRING, REGULAR_EXPRESSION)',
          'returnType' => 'bool'
        ],
        9 => [
          'name' => 'string\\length',
          'insertText' => 'string\\length(STRING)',
          'returnType' => 'int'
        ],
        10 => [
          'name' => 'string\\trim',
          'insertText' => 'string\\trim(STRING)',
          'returnType' => 'string'
        ],
        11 => [
          'name' => 'string\\lowerCase',
          'insertText' => 'string\\lowerCase(STRING)',
          'returnType' => 'string'
        ],
        12 => [
          'name' => 'string\\upperCase',
          'insertText' => 'string\\upperCase(STRING)',
          'returnType' => 'string'
        ],
        13 => [
          'name' => 'string\\match',
          'insertText' => 'string\\match(STRING, REGULAR_EXPRESSION)',
          'returnType' => 'string|null'
        ],
        14 => [
          'name' => 'string\\matchAll',
          'insertText' => 'string\\matchAll(STRING, REGULAR_EXPRESSION)',
          'returnType' => 'string[]|null'
        ],
        15 => [
          'name' => 'string\\matchExtract',
          'insertText' => 'string\\matchExtract(STRING, REGULAR_EXPRESSION)',
          'returnType' => 'string[]|null'
        ],
        16 => [
          'name' => 'string\\replace',
          'insertText' => 'string\\replace(STRING, SEARCH, REPLACE)',
          'returnType' => 'string'
        ],
        17 => [
          'name' => 'string\\split',
          'insertText' => 'string\\split(STRING, SEPARATOR)',
          'returnType' => 'string[]'
        ],
        18 => [
          'name' => 'datetime\\today',
          'insertText' => 'datetime\\today()',
          'returnType' => 'string'
        ],
        19 => [
          'name' => 'datetime\\now',
          'insertText' => 'datetime\\now()',
          'returnType' => 'string'
        ],
        20 => [
          'name' => 'datetime\\format',
          'insertText' => 'datetime\\format(VALUE)',
          'returnType' => 'string'
        ],
        21 => [
          'name' => 'datetime\\date',
          'insertText' => 'datetime\\date(VALUE)',
          'returnType' => 'int'
        ],
        22 => [
          'name' => 'datetime\\month',
          'insertText' => 'datetime\\month(VALUE)',
          'returnType' => 'int'
        ],
        23 => [
          'name' => 'datetime\\year',
          'insertText' => 'datetime\\year(VALUE)',
          'returnType' => 'int'
        ],
        24 => [
          'name' => 'datetime\\hour',
          'insertText' => 'datetime\\hour(VALUE)',
          'returnType' => 'int'
        ],
        25 => [
          'name' => 'datetime\\minute',
          'insertText' => 'datetime\\minute(VALUE)',
          'returnType' => 'int'
        ],
        26 => [
          'name' => 'datetime\\dayOfWeek',
          'insertText' => 'datetime\\dayOfWeek(VALUE)',
          'returnType' => 'int'
        ],
        27 => [
          'name' => 'datetime\\addMinutes',
          'insertText' => 'datetime\\addMinutes(VALUE, MINUTES)',
          'returnType' => 'string'
        ],
        28 => [
          'name' => 'datetime\\addHours',
          'insertText' => 'datetime\\addHours(VALUE, HOURS)',
          'returnType' => 'string'
        ],
        29 => [
          'name' => 'datetime\\addDays',
          'insertText' => 'datetime\\addDays(VALUE, DAYS)',
          'returnType' => 'string'
        ],
        30 => [
          'name' => 'datetime\\addWeeks',
          'insertText' => 'datetime\\addWeeks(VALUE, WEEKS)',
          'returnType' => 'string'
        ],
        31 => [
          'name' => 'datetime\\addMonths',
          'insertText' => 'datetime\\addMonths(VALUE, MONTHS)',
          'returnType' => 'string'
        ],
        32 => [
          'name' => 'datetime\\addYears',
          'insertText' => 'datetime\\addYears(VALUE, YEARS)',
          'returnType' => 'string'
        ],
        33 => [
          'name' => 'datetime\\diff',
          'insertText' => 'datetime\\diff(VALUE_1, VALUE_2, INTERVAL_TYPE)',
          'returnType' => 'int'
        ],
        34 => [
          'name' => 'datetime\\closest',
          'insertText' => 'datetime\\closest(VALUE, TYPE, TARGET, IS_PAST, TIMEZONE)',
          'returnType' => 'string'
        ],
        35 => [
          'name' => 'number\\format',
          'insertText' => 'number\\format(VALUE)',
          'returnType' => 'string'
        ],
        36 => [
          'name' => 'number\\abs',
          'insertText' => 'number\\abs(VALUE)'
        ],
        37 => [
          'name' => 'number\\power',
          'insertText' => 'number\\power(VALUE, EXP)',
          'returnType' => 'int|float'
        ],
        38 => [
          'name' => 'number\\round',
          'insertText' => 'number\\round(VALUE, PRECISION)',
          'returnType' => 'int|float'
        ],
        39 => [
          'name' => 'number\\floor',
          'insertText' => 'number\\floor(VALUE)',
          'returnType' => 'int'
        ],
        40 => [
          'name' => 'number\\ceil',
          'insertText' => 'number\\ceil(VALUE)',
          'returnType' => 'int'
        ],
        41 => [
          'name' => 'number\\randomInt',
          'insertText' => 'number\\randomInt(MIN, MAX)',
          'returnType' => 'int'
        ],
        42 => [
          'name' => 'number\\parseInt',
          'insertText' => 'number\\parseInt(STRING)',
          'returnType' => 'int'
        ],
        43 => [
          'name' => 'number\\parseFloat',
          'insertText' => 'number\\parseFloat(STRING)',
          'returnType' => 'float'
        ],
        44 => [
          'name' => 'entity\\isNew',
          'insertText' => 'entity\\isNew()',
          'returnType' => 'bool'
        ],
        45 => [
          'name' => 'entity\\isAttributeChanged',
          'insertText' => 'entity\\isAttributeChanged(\'ATTRIBUTE\')',
          'returnType' => 'bool'
        ],
        46 => [
          'name' => 'entity\\isAttributeNotChanged',
          'insertText' => 'entity\\isAttributeNotChanged(\'ATTRIBUTE\')',
          'returnType' => 'bool'
        ],
        47 => [
          'name' => 'entity\\attribute',
          'insertText' => 'entity\\attribute(\'ATTRIBUTE\')',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        48 => [
          'name' => 'entity\\attributeFetched',
          'insertText' => 'entity\\attributeFetched(\'ATTRIBUTE\')',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        49 => [
          'name' => 'entity\\setAttribute',
          'insertText' => 'entity\\setAttribute(\'ATTRIBUTE\', VALUE)',
          'unsafe' => true
        ],
        50 => [
          'name' => 'entity\\clearAttribute',
          'insertText' => 'entity\\clearAttribute(\'ATTRIBUTE\')',
          'unsafe' => true
        ],
        51 => [
          'name' => 'entity\\addLinkMultipleId',
          'insertText' => 'entity\\addLinkMultipleId(LINK, ID)',
          'unsafe' => true
        ],
        52 => [
          'name' => 'entity\\hasLinkMultipleId',
          'insertText' => 'entity\\hasLinkMultipleId(LINK, ID)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        53 => [
          'name' => 'entity\\removeLinkMultipleId',
          'insertText' => 'entity\\removeLinkMultipleId(LINK, ID)',
          'unsafe' => true
        ],
        54 => [
          'name' => 'entity\\getLinkColumn',
          'insertText' => 'entity\\getLinkColumn(LINK, ID, COLUMN)',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        55 => [
          'name' => 'entity\\setLinkMultipleColumn',
          'insertText' => 'entity\\setLinkMultipleColumn(LINK, ID, COLUMN, VALUE)',
          'unsafe' => true
        ],
        56 => [
          'name' => 'entity\\isRelated',
          'insertText' => 'entity\\isRelated(LINK, ID)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        57 => [
          'name' => 'entity\\sumRelated',
          'insertText' => 'entity\\sumRelated(LINK, FIELD, FILTER)',
          'returnType' => 'int|float',
          'unsafe' => true
        ],
        58 => [
          'name' => 'entity\\countRelated',
          'insertText' => 'entity\\countRelated(LINK, FILTER)',
          'returnType' => 'int',
          'unsafe' => true
        ],
        59 => [
          'name' => 'record\\exists',
          'insertText' => 'record\\exists(ENTITY_TYPE, KEY, VALUE)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        60 => [
          'name' => 'record\\count',
          'insertText' => 'record\\count(ENTITY_TYPE, KEY, VALUE)',
          'returnType' => 'int',
          'unsafe' => true
        ],
        61 => [
          'name' => 'record\\attribute',
          'insertText' => 'record\\attribute(ENTITY_TYPE, ID, ATTRIBUTE)',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        62 => [
          'name' => 'record\\findOne',
          'insertText' => 'record\\findOne(ENTITY_TYPE, ORDER_BY, ORDER, KEY1, VALUE1, KEY2, VALUE2)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        63 => [
          'name' => 'record\\findMany',
          'insertText' => 'record\\findMany(ENTITY_TYPE, LIMIT, ORDER_BY, ORDER, KEY1, VALUE1, KEY2, VALUE2)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        64 => [
          'name' => 'record\\findRelatedOne',
          'insertText' => 'record\\findRelatedOne(ENTITY_TYPE, ID, LINK, ORDER_BY, ORDER, KEY1, VALUE1, KEY2, VALUE2)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        65 => [
          'name' => 'record\\findRelatedMany',
          'insertText' => 'record\\findRelatedMany(ENTITY_TYPE, ID, LINK, LIMIT, ORDER_BY, ORDER, KEY1, VALUE1, KEY2, VALUE2)',
          'returnType' => 'string[]',
          'unsafe' => true
        ],
        66 => [
          'name' => 'record\\fetch',
          'insertText' => 'record\\fetch(ENTITY_TYPE, ID)',
          'returnType' => '?object',
          'unsafe' => true
        ],
        67 => [
          'name' => 'record\\relate',
          'insertText' => 'record\\relate(ENTITY_TYPE, ID, LINK, FOREIGN_ID)',
          'unsafe' => true
        ],
        68 => [
          'name' => 'record\\unrelate',
          'insertText' => 'record\\unrelate(ENTITY_TYPE, ID, LINK, FOREIGN_ID)',
          'unsafe' => true
        ],
        69 => [
          'name' => 'record\\create',
          'insertText' => 'record\\create(ENTITY_TYPE, ATTRIBUTE1, VALUE1, ATTRIBUTE2, VALUE2)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        70 => [
          'name' => 'record\\update',
          'insertText' => 'record\\update(ENTITY_TYPE, ID, ATTRIBUTE1, VALUE1, ATTRIBUTE2, VALUE2)',
          'unsafe' => true
        ],
        71 => [
          'name' => 'record\\delete',
          'insertText' => 'record\\delete(ENTITY_TYPE, ID)',
          'unsafe' => true
        ],
        72 => [
          'name' => 'record\\relationColumn',
          'insertText' => 'record\\relationColumn(ENTITY_TYPE, ID, LINK, FOREIGN_ID, COLUMN)',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        73 => [
          'name' => 'record\\updateRelationColumn',
          'insertText' => 'record\\updateRelationColumn(ENTITY_TYPE, ID, LINK, FOREIGN_ID, COLUMN, VALUE)',
          'unsafe' => true
        ],
        74 => [
          'name' => 'env\\userAttribute',
          'insertText' => 'env\\userAttribute(\'ATTRIBUTE\')',
          'returnType' => 'mixed',
          'unsafe' => true
        ],
        75 => [
          'name' => 'util\\generateId',
          'insertText' => 'util\\generateId()',
          'returnType' => 'string'
        ],
        76 => [
          'name' => 'util\\generateRecordId',
          'insertText' => 'util\\generateRecordId()',
          'returnType' => 'string'
        ],
        77 => [
          'name' => 'util\\base64Encode',
          'insertText' => 'util\\base64Encode(STRING)',
          'returnType' => 'string'
        ],
        78 => [
          'name' => 'util\\base64Decode',
          'insertText' => 'util\\base64Decode(STRING)',
          'returnType' => 'string'
        ],
        79 => [
          'name' => 'object\\create',
          'insertText' => 'object\\create()',
          'returnType' => 'object'
        ],
        80 => [
          'name' => 'object\\get',
          'insertText' => 'object\\get(OBJECT, KEY)',
          'returnType' => 'mixed'
        ],
        81 => [
          'name' => 'object\\has',
          'insertText' => 'object\\has(OBJECT, KEY)',
          'returnType' => 'bool'
        ],
        82 => [
          'name' => 'object\\set',
          'insertText' => 'object\\set(OBJECT, KEY, VALUE)'
        ],
        83 => [
          'name' => 'object\\clear',
          'insertText' => 'object\\clear(OBJECT, KEY)',
          'returnType' => 'object'
        ],
        84 => [
          'name' => 'object\\cloneDeep',
          'insertText' => 'object\\cloneDeep(OBJECT)',
          'returnType' => 'object'
        ],
        85 => [
          'name' => 'password\\generate',
          'insertText' => 'password\\generate()',
          'returnType' => 'string'
        ],
        86 => [
          'name' => 'password\\hash',
          'insertText' => 'password\\hash(PASSWORD)',
          'returnType' => 'string'
        ],
        87 => [
          'name' => 'array\\includes',
          'insertText' => 'array\\includes(LIST, VALUE)',
          'returnType' => 'bool'
        ],
        88 => [
          'name' => 'array\\push',
          'insertText' => 'array\\push(LIST, VALUE)'
        ],
        89 => [
          'name' => 'array\\length',
          'insertText' => 'array\\length(LIST)',
          'returnType' => 'int'
        ],
        90 => [
          'name' => 'array\\at',
          'insertText' => 'array\\at(LIST, INDEX)',
          'returnType' => 'mixed'
        ],
        91 => [
          'name' => 'array\\join',
          'insertText' => 'array\\join(LIST, SEPARATOR)',
          'returnType' => 'string'
        ],
        92 => [
          'name' => 'array\\indexOf',
          'insertText' => 'array\\indexOf(LIST, ELEMENT)',
          'returnType' => '?int'
        ],
        93 => [
          'name' => 'array\\removeAt',
          'insertText' => 'array\\removeAt(LIST, INDEX)',
          'returnType' => 'array'
        ],
        94 => [
          'name' => 'array\\unique',
          'insertText' => 'array\\unique(LIST)',
          'returnType' => 'array'
        ],
        95 => [
          'name' => 'language\\translate',
          'insertText' => 'language\\translate(LABEL, CATEGORY, SCOPE)',
          'returnType' => 'string'
        ],
        96 => [
          'name' => 'language\\translateOption',
          'insertText' => 'language\\translateOption(OPTION, FIELD, SCOPE)',
          'returnType' => 'string'
        ],
        97 => [
          'name' => 'log\\info',
          'insertText' => 'log\\info(MESSAGE)',
          'unsafe' => true
        ],
        98 => [
          'name' => 'log\\notice',
          'insertText' => 'log\\notice(MESSAGE)',
          'unsafe' => true
        ],
        99 => [
          'name' => 'log\\warning',
          'insertText' => 'log\\warning(MESSAGE)',
          'unsafe' => true
        ],
        100 => [
          'name' => 'log\\error',
          'insertText' => 'log\\error(MESSAGE)',
          'unsafe' => true
        ],
        101 => [
          'name' => 'json\\retrieve',
          'insertText' => 'json\\retrieve(JSON, PATH)',
          'returnType' => 'mixed'
        ],
        102 => [
          'name' => 'json\\encode',
          'insertText' => 'json\\encode(VALUE)',
          'returnType' => 'string'
        ],
        103 => [
          'name' => 'ext\\email\\send',
          'insertText' => 'ext\\email\\send(EMAIL_ID)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        104 => [
          'name' => 'ext\\sms\\send',
          'insertText' => 'ext\\sms\\send(SMS_ID)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        105 => [
          'name' => 'ext\\email\\applyTemplate',
          'insertText' => 'ext\\email\\applyTemplate(EMAIL_ID, EMAIL_TEMPLATE_ID)',
          'unsafe' => true
        ],
        106 => [
          'name' => 'ext\\markdown\\transform',
          'insertText' => 'ext\\markdown\\transform(STRING)',
          'returnType' => 'string'
        ],
        107 => [
          'name' => 'ext\\pdf\\generate',
          'insertText' => 'ext\\pdf\\generate(ENTITY_TYPE, ENTITY_ID, TEMPLATE_ID, FILENAME)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        108 => [
          'name' => 'ext\\workingTime\\addWorkingDays',
          'insertText' => 'ext\\workingTime\\addWorkingDays(DATE, DAYS)',
          'returnType' => 'string|null'
        ],
        109 => [
          'name' => 'ext\\workingTime\\findClosestWorkingTime',
          'insertText' => 'ext\\workingTime\\findClosestWorkingTime(DATE)',
          'returnType' => 'string|null'
        ],
        110 => [
          'name' => 'ext\\workingTime\\getSummedWorkingHours',
          'insertText' => 'ext\\workingTime\\getSummedWorkingHours(FROM, TO)',
          'returnType' => 'float'
        ],
        111 => [
          'name' => 'ext\\workingTime\\getWorkingDays',
          'insertText' => 'ext\\workingTime\\getWorkingDays(FROM, TO)',
          'returnType' => 'int'
        ],
        112 => [
          'name' => 'ext\\workingTime\\hasWorkingTime',
          'insertText' => 'ext\\workingTime\\hasWorkingTime(FROM, TO)',
          'returnType' => 'bool'
        ],
        113 => [
          'name' => 'ext\\workingTime\\isWorkingDay',
          'insertText' => 'ext\\workingTime\\isWorkingDay(DATE)',
          'returnType' => 'bool'
        ],
        114 => [
          'name' => 'ext\\user\\sendAccessInfo',
          'insertText' => 'ext\\user\\sendAccessInfo(USER_ID)',
          'unsafe' => true
        ],
        115 => [
          'name' => 'ext\\email\\send',
          'insertText' => 'ext\\email\\send(EMAIL_ID)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        116 => [
          'name' => 'ext\\currency\\convert',
          'insertText' => 'ext\\currency\\convert(AMOUNT, FROM_CODE)',
          'returnType' => 'string'
        ],
        117 => [
          'name' => 'ext\\acl\\checkEntity',
          'insertText' => 'ext\\acl\\checkEntity(USER_ID, ENTITY_TYPE, ID, ACTION)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        118 => [
          'name' => 'ext\\acl\\checkScope',
          'insertText' => 'ext\\acl\\checkScope(USER_ID, SCOPE, ACTION)',
          'returnType' => 'bool',
          'unsafe' => true
        ],
        119 => [
          'name' => 'ext\\acl\\getLevel',
          'insertText' => 'ext\\acl\\getLevel(USER_ID, SCOPE, ACTION)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        120 => [
          'name' => 'ext\\acl\\getPermissionLevel',
          'insertText' => 'ext\\acl\\getPermissionLevel(USER_ID, PERMISSION)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        121 => [
          'name' => 'ext\\oauth\\getAccessToken',
          'insertText' => 'ext\\oauth\\getAccessToken(ID)',
          'returnType' => 'string',
          'unsafe' => true
        ],
        122 => [
          'name' => 'ext\\appSecret\\get',
          'insertText' => 'ext\\appSecret\\get(STRING)',
          'returnType' => 'string'
        ],
        123 => [
          'name' => 'ext\\account\\findByEmailAddress',
          'insertText' => 'ext\\account\\findByEmailAddress(EMAIL_ADDRESS)',
          'returnType' => 'string'
        ],
        124 => [
          'name' => 'ext\\calendar\\userIsBusy',
          'insertText' => 'ext\\calendar\\userIsBusy(USER_ID, FROM, TO)',
          'returnType' => 'bool'
        ]
      ],
      'functionClassNameMap' => [
        'log\\info' => 'Espo\\Core\\Formula\\Functions\\LogGroup\\InfoType',
        'log\\notice' => 'Espo\\Core\\Formula\\Functions\\LogGroup\\NoticeType',
        'log\\warning' => 'Espo\\Core\\Formula\\Functions\\LogGroup\\WarningType',
        'log\\error' => 'Espo\\Core\\Formula\\Functions\\LogGroup\\ErrorType',
        'ext\\acl\\checkEntity' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\AclGroup\\CheckEntityType',
        'ext\\acl\\checkScope' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\AclGroup\\CheckScopeType',
        'ext\\acl\\getLevel' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\AclGroup\\GetLevelType',
        'ext\\acl\\getPermissionLevel' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\AclGroup\\GetPermissionLevelType',
        'ext\\appSecret\\get' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\SecretGroup\\GetType',
        'util\\base64Encode' => 'Espo\\Core\\Formula\\Functions\\UtilGroup\\Base64EncodeType',
        'util\\base64Decode' => 'Espo\\Core\\Formula\\Functions\\UtilGroup\\Base64DecodeType',
        'ext\\oauth\\getAccessToken' => 'Espo\\Core\\Formula\\Functions\\ExtGroup\\OauthGroup\\GetAccessTokenType',
        'ext\\account\\findByEmailAddress' => 'Espo\\Modules\\Crm\\Classes\\FormulaFunctions\\ExtGroup\\AccountGroup\\FindByEmailAddressType',
        'ext\\calendar\\userIsBusy' => 'Espo\\Modules\\Crm\\Classes\\FormulaFunctions\\ExtGroup\\CalendarGroup\\UserIsBusyType'
      ]
    ],
    'hook' => [
      'suppressClassNameList' => []
    ],
    'image' => [
      'allowedFileTypeList' => [
        0 => 'image/jpeg',
        1 => 'image/png',
        2 => 'image/gif',
        3 => 'image/webp',
        4 => 'image/svg+xml',
        5 => 'image/avif'
      ],
      'resizableFileTypeList' => [
        0 => 'image/jpeg',
        1 => 'image/png',
        2 => 'image/gif',
        3 => 'image/webp'
      ],
      'fixOrientationFileTypeList' => [
        0 => 'image/jpeg'
      ],
      'previewFileTypeList' => [
        0 => 'image/jpeg',
        1 => 'image/png',
        2 => 'image/gif',
        3 => 'image/webp',
        4 => 'image/svg+xml',
        5 => 'image/avif'
      ],
      'sizes' => [
        'xxx-small' => [
          0 => 18,
          1 => 18
        ],
        'xx-small' => [
          0 => 32,
          1 => 32
        ],
        'x-small' => [
          0 => 64,
          1 => 64
        ],
        'small' => [
          0 => 128,
          1 => 128
        ],
        'medium' => [
          0 => 256,
          1 => 256
        ],
        'large' => [
          0 => 512,
          1 => 512
        ],
        'x-large' => [
          0 => 864,
          1 => 864
        ],
        'xx-large' => [
          0 => 1024,
          1 => 1024
        ],
        'small-logo' => [
          0 => 181,
          1 => 44
        ]
      ]
    ],
    'jsLibs' => [
      'jquery' => [
        'exposeAs' => '$'
      ],
      'backbone' => [
        'exportsTo' => 'window',
        'exportsAs' => 'Backbone'
      ],
      'bullbone' => [
        'exposeAs' => 'Bull'
      ],
      'handlebars' => [
        'exposeAs' => 'Handlebars'
      ],
      'underscore' => [
        'exposeAs' => '_'
      ],
      'marked' => [],
      'dompurify' => [
        'exposeAs' => 'DOMPurify'
      ],
      'js-base64' => [
        'exportsTo' => 'window',
        'exportsAs' => 'Base64'
      ],
      'moment' => [
        'exportsTo' => 'window',
        'exportsAs' => 'moment'
      ],
      'flotr2' => [
        'path' => 'client/lib/flotr2.js',
        'devPath' => 'client/lib/original/flotr2.js',
        'exportsTo' => 'window',
        'exportsAs' => 'Flotr',
        'sourceMap' => true,
        'aliases' => [
          0 => 'lib!Flotr'
        ]
      ],
      'espo-funnel-chart' => [
        'path' => 'client/lib/espo-funnel-chart.js',
        'exportsTo' => 'window',
        'exportsAs' => 'EspoFunnel'
      ],
      'summernote' => [
        'path' => 'client/lib/summernote.js',
        'devPath' => 'client/lib/original/summernote.js',
        'exportsTo' => '$.fn',
        'exportsAs' => 'summernote',
        'sourceMap' => true
      ],
      'jquery-ui' => [
        'exportsTo' => '$',
        'exportsAs' => 'ui'
      ],
      'jquery-ui-touch-punch' => [
        'exportsTo' => '$',
        'exportsAs' => 'ui'
      ],
      'autocomplete' => [
        'exportsTo' => '$.fn',
        'exportsAs' => 'autocomplete'
      ],
      'timepicker' => [
        'exportsTo' => '$.fn',
        'exportsAs' => 'timepicker'
      ],
      'bootstrap-datepicker' => [
        'exportsTo' => '$.fn',
        'exportsAs' => 'datepicker'
      ],
      'selectize' => [
        'path' => 'client/lib/selectize.js',
        'devPath' => 'client/lib/original/selectize.js',
        'exportsTo' => 'window',
        'exportsAs' => 'Selectize'
      ],
      '@shopify/draggable' => [
        'devPath' => 'client/lib/original/shopify-draggable.js'
      ],
      '@textcomplete/core' => [
        'devPath' => 'client/lib/original/textcomplete-core.js'
      ],
      '@textcomplete/textarea' => [
        'devPath' => 'client/lib/original/textcomplete-textarea.js'
      ],
      'autonumeric' => [],
      'intl-tel-input' => [
        'exportsTo' => 'window',
        'exportsAs' => 'intlTelInput'
      ],
      'intl-tel-input-utils' => [
        'exportsTo' => 'window',
        'exportsAs' => 'intlTelInputUtils'
      ],
      'intl-tel-input-globals' => [
        'exportsTo' => 'window',
        'exportsAs' => 'intlTelInputGlobals'
      ],
      'cronstrue' => [
        'path' => 'client/lib/cronstrue-i18n.js',
        'devPath' => 'client/lib/original/cronstrue-i18n.js',
        'sourceMap' => true
      ],
      'cropper' => [
        'path' => 'client/lib/cropper.js',
        'exportsTo' => '$.fn',
        'exportsAs' => 'cropper',
        'sourceMap' => true
      ],
      'gridstack' => [
        'exportsTo' => 'window',
        'exportsAs' => 'GridStack'
      ],
      'bootstrap-colorpicker' => [
        'path' => 'client/lib/bootstrap-colorpicker.js',
        'exportsTo' => '$.fn',
        'exportsAs' => 'colorpicker',
        'aliases' => [
          0 => 'lib!Colorpicker'
        ]
      ],
      'exif-js' => [
        'path' => 'client/lib/exif.js',
        'devPath' => 'client/lib/original/exif.js',
        'sourceMap' => true
      ],
      'jsbarcode' => [
        'path' => 'client/lib/JsBarcode.all.js',
        'devPath' => 'client/lib/original/JsBarcode.all.js',
        'exportsTo' => 'window',
        'exportsAs' => 'JsBarcode',
        'sourceMap' => true
      ],
      'qrcodejs' => [
        'path' => 'client/lib/qrcode.js',
        'exportsTo' => 'window',
        'exportsAs' => 'QRCode'
      ],
      'turndown' => [
        'path' => 'client/lib/turndown.browser.umd.js',
        'devPath' => 'client/lib/turndown.browser.umd.js',
        'sourceMap' => true
      ],
      'ace' => [
        'path' => 'client/lib/ace.js',
        'exportsTo' => 'window',
        'exportsAs' => 'ace'
      ],
      'ace-mode-css' => [
        'path' => 'client/lib/ace-mode-css.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/mode/css'
      ],
      'ace-mode-html' => [
        'path' => 'client/lib/ace-mode-html.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/mode/html'
      ],
      'ace-mode-handlebars' => [
        'path' => 'client/lib/ace-mode-handlebars.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/mode/handlebars'
      ],
      'ace-mode-javascript' => [
        'path' => 'client/lib/ace-mode-javascript.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/mode/javascript'
      ],
      'ace-mode-json' => [
        'path' => 'client/lib/ace-mode-json.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/mode/json'
      ],
      'ace-ext-language_tools' => [
        'path' => 'client/lib/ace-ext-language_tools.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/ext/language_tools'
      ],
      'ace-theme-tomorrow_night' => [
        'path' => 'client/lib/ace-theme-tomorrow_night.js',
        'exportsTo' => 'ace.require.define.modules',
        'exportsAs' => 'ace/theme/tomorrow_night'
      ],
      'fullcalendar' => [
        'path' => 'client/modules/crm/lib/fullcalendar.js',
        'devPath' => 'client/modules/crm/lib/original/fullcalendar.js',
        'exportsTo' => 'window',
        'exportsAs' => 'FullCalendar',
        'sourceMap' => true
      ],
      '@fullcalendar/moment' => [
        'path' => 'client/modules/crm/lib/fullcalendar-moment.js',
        'devPath' => 'client/modules/crm/lib/original/fullcalendar-moment.js',
        'exportsTo' => 'FullCalendar',
        'exportsAs' => 'Moment',
        'sourceMap' => true
      ],
      '@fullcalendar/moment-timezone' => [
        'path' => 'client/modules/crm/lib/fullcalendar-moment-timezone.js',
        'devPath' => 'client/modules/crm/lib/original/fullcalendar-moment-timezone.js',
        'exportsTo' => 'FullCalendar',
        'exportsAs' => 'MomentTimezone',
        'sourceMap' => true
      ],
      'vis-timeline' => [
        'path' => 'client/modules/crm/lib/vis-timeline.js',
        'devPath' => 'client/modules/crm/lib/original/vis-timeline.js',
        'sourceMap' => true
      ],
      'vis-data' => [
        'path' => 'client/modules/crm/lib/vis-data.js',
        'devPath' => 'client/modules/crm/lib/original/vis-data.js',
        'aliases' => [
          0 => 'vis-data/peer/umd/vis-data.js'
        ],
        'sourceMap' => true
      ]
    ],
    'language' => [
      'list' => [
        0 => 'en_US',
        1 => 'ar_AR',
        2 => 'bg_BG',
        3 => 'en_GB',
        4 => 'es_MX',
        5 => 'cs_CZ',
        6 => 'da_DK',
        7 => 'de_DE',
        8 => 'es_ES',
        9 => 'hr_HR',
        10 => 'hu_HU',
        11 => 'fa_IR',
        12 => 'fr_FR',
        13 => 'ja_JP',
        14 => 'id_ID',
        15 => 'it_IT',
        16 => 'lt_LT',
        17 => 'lv_LV',
        18 => 'nb_NO',
        19 => 'nl_NL',
        20 => 'th_TH',
        21 => 'tr_TR',
        22 => 'sk_SK',
        23 => 'sl_SI',
        24 => 'sr_RS',
        25 => 'sv_SE',
        26 => 'ro_RO',
        27 => 'ru_RU',
        28 => 'pl_PL',
        29 => 'pt_BR',
        30 => 'pt_PT',
        31 => 'uk_UA',
        32 => 'vi_VN',
        33 => 'zh_CN',
        34 => 'zh_TW'
      ],
      'aclDependencies' => [
        'Meeting' => [
          'anyScopeList' => [
            0 => 'Call'
          ]
        ]
      ]
    ],
    'layouts' => [],
    'linkManager' => [
      'createHookClassNameList' => [
        0 => 'Espo\\Tools\\LinkManager\\Hook\\Hooks\\TargetListCreate'
      ],
      'deleteHookClassNameList' => [
        0 => 'Espo\\Tools\\LinkManager\\Hook\\Hooks\\TargetListDelete',
        1 => 'Espo\\Tools\\LinkManager\\Hook\\Hooks\\ForeignFieldDelete'
      ]
    ],
    'mapProviders' => [
      'Google' => [
        'renderer' => 'handlers/map/google-maps-renderer'
      ]
    ],
    'massActions' => [
      'convertCurrency' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassConvertCurrency'
      ],
      'follow' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassFollow'
      ],
      'unfollow' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassUnfollow'
      ],
      'recalculateFormula' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassRecalculateFormula'
      ],
      'update' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassUpdate'
      ],
      'delete' => [
        'implementationClassName' => 'Espo\\Core\\MassAction\\Actions\\MassDelete'
      ]
    ],
    'metadata' => [
      'frontendHiddenPathList' => [
        0 => [
          0 => 'app',
          1 => 'metadata'
        ],
        1 => [
          0 => 'app',
          1 => 'containerServices'
        ],
        2 => [
          0 => 'app',
          1 => 'portalContainerServices'
        ],
        3 => [
          0 => 'app',
          1 => 'consoleCommands'
        ],
        4 => [
          0 => 'app',
          1 => 'formula',
          2 => 'functionClassNameMap'
        ],
        5 => [
          0 => 'app',
          1 => 'fileStorage',
          2 => 'implementationClassNameMap'
        ],
        6 => [
          0 => 'app',
          1 => 'client'
        ],
        7 => [
          0 => 'app',
          1 => 'language',
          2 => 'aclDependencies'
        ],
        8 => [
          0 => 'app',
          1 => 'templateHelpers'
        ],
        9 => [
          0 => 'app',
          1 => 'appParams'
        ],
        10 => [
          0 => 'app',
          1 => 'cleanup'
        ],
        11 => [
          0 => 'app',
          1 => 'authentication'
        ],
        12 => [
          0 => 'app',
          1 => 'pdfEngines',
          2 => '__ANY__',
          3 => 'implementationClassNameMap'
        ],
        13 => [
          0 => 'app',
          1 => 'addressFormats',
          2 => '__ANY__',
          3 => 'formatterClassName'
        ],
        14 => [
          0 => 'app',
          1 => 'authentication2FAMethods',
          2 => '__ANY__',
          3 => 'loginClassName'
        ],
        15 => [
          0 => 'app',
          1 => 'authentication2FAMethods',
          2 => '__ANY__',
          3 => 'userSetupClassName'
        ],
        16 => [
          0 => 'app',
          1 => 'select'
        ],
        17 => [
          0 => 'app',
          1 => 'massActions',
          2 => '__ANY__',
          3 => 'implementationClassName'
        ],
        18 => [
          0 => 'app',
          1 => 'actions',
          2 => '__ANY__',
          3 => 'implementationClassName'
        ],
        19 => [
          0 => 'app',
          1 => 'fieldProcessing'
        ],
        20 => [
          0 => 'app',
          1 => 'scheduledJobs'
        ],
        21 => [
          0 => 'app',
          1 => 'webSocket',
          2 => 'messagers'
        ],
        22 => [
          0 => 'app',
          1 => 'config'
        ],
        23 => [
          0 => 'app',
          1 => 'rebuild'
        ],
        24 => [
          0 => 'app',
          1 => 'smsProviders',
          2 => '__ANY__',
          3 => 'senderClassName'
        ],
        25 => [
          0 => 'app',
          1 => 'orm'
        ],
        26 => [
          0 => 'app',
          1 => 'relationships'
        ],
        27 => [
          0 => 'app',
          1 => 'linkManager'
        ],
        28 => [
          0 => 'app',
          1 => 'hook'
        ],
        29 => [
          0 => 'app',
          1 => 'api'
        ],
        30 => [
          0 => 'app',
          1 => 'databasePlatforms'
        ],
        31 => [
          0 => 'app',
          1 => 'recordId'
        ],
        32 => [
          0 => 'app',
          1 => 'currencyConversion'
        ],
        33 => [
          0 => 'selectDefs'
        ],
        34 => [
          0 => 'pdfDefs'
        ],
        35 => [
          0 => 'notificationDefs',
          1 => '__ANY__',
          2 => 'assignmentNotificatorClassName'
        ],
        36 => [
          0 => 'authenticationMethods',
          1 => '__ANY__',
          2 => 'implementationClassName'
        ],
        37 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'accessCheckerClassName'
        ],
        38 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'portalAccessCheckerClassName'
        ],
        39 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'ownershipCheckerClassName'
        ],
        40 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'portalOwnershipCheckerClassName'
        ],
        41 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'assignmentCheckerClassName'
        ],
        42 => [
          0 => 'aclDefs',
          1 => '__ANY__',
          2 => 'linkCheckerClassNameMap'
        ],
        43 => [
          0 => 'app',
          1 => 'calendar',
          2 => 'additionalAttributeList'
        ]
      ],
      'frontendNonAdminHiddenPathList' => [
        0 => [
          0 => 'recordDefs'
        ]
      ],
      'additionalBuilderClassNameList' => [
        0 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\Fields',
        1 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\FilterFields',
        2 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\DeleteIdField',
        3 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\DisableAssignedUser',
        4 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\StreamUpdatedAtField',
        5 => 'Espo\\Core\\Utils\\Metadata\\AdditionalBuilder\\LogicDefsBc'
      ],
      'aclDependencies' => []
    ],
    'orm' => [
      'platforms' => [
        'Mysql' => [
          'queryComposerClassName' => 'Espo\\ORM\\QueryComposer\\MysqlQueryComposer',
          'pdoFactoryClassName' => 'Espo\\ORM\\PDO\\MysqlPDOFactory',
          'functionConverterClassNameMap' => [
            'ABS' => 'Espo\\Core\\ORM\\QueryComposer\\Part\\FunctionConverters\\Abs'
          ]
        ],
        'Postgresql' => [
          'queryComposerClassName' => 'Espo\\ORM\\QueryComposer\\PostgresqlQueryComposer',
          'pdoFactoryClassName' => 'Espo\\ORM\\PDO\\PostgresqlPDOFactory',
          'functionConverterClassNameMap' => [
            'ABS' => 'Espo\\Core\\ORM\\QueryComposer\\Part\\FunctionConverters\\Abs'
          ]
        ]
      ]
    ],
    'pdfEngines' => [
      'Dompdf' => [
        'implementationClassNameMap' => [
          'entity' => 'Espo\\Tools\\Pdf\\Dompdf\\EntityPrinter'
        ],
        'fontFaceList' => [
          0 => 'Courier',
          1 => 'Helvetica',
          2 => 'Times',
          3 => 'Symbol',
          4 => 'ZapfDingbats',
          5 => 'DejaVu Sans',
          6 => 'DejaVu Serif',
          7 => 'DejaVu Sans Mono'
        ]
      ]
    ],
    'portalContainerServices' => [
      'layoutProvider' => [
        'className' => 'Espo\\Tools\\Layout\\PortalLayoutProvider'
      ],
      'themeManager' => [
        'className' => 'Espo\\Core\\Portal\\Utils\\ThemeManager'
      ]
    ],
    'reactions' => [
      'list' => [
        0 => [
          'type' => 'Smile',
          'iconClass' => 'far fa-face-smile'
        ],
        1 => [
          'type' => 'Surprise',
          'iconClass' => 'far fa-face-surprise'
        ],
        2 => [
          'type' => 'Laugh',
          'iconClass' => 'far fa-face-laugh'
        ],
        3 => [
          'type' => 'Meh',
          'iconClass' => 'far fa-face-meh'
        ],
        4 => [
          'type' => 'Sad',
          'iconClass' => 'far fa-face-frown'
        ],
        5 => [
          'type' => 'Love',
          'iconClass' => 'far fa-heart'
        ],
        6 => [
          'type' => 'Like',
          'iconClass' => 'far fa-thumbs-up'
        ],
        7 => [
          'type' => 'Dislike',
          'iconClass' => 'far fa-thumbs-down'
        ]
      ]
    ],
    'rebuild' => [
      'actionClassNameList' => [
        0 => 'Espo\\Core\\Rebuild\\Actions\\AddSystemUser',
        1 => 'Espo\\Core\\Rebuild\\Actions\\AddSystemData',
        2 => 'Espo\\Core\\Rebuild\\Actions\\ScheduledJobs',
        3 => 'Espo\\Core\\Rebuild\\Actions\\ConfigMetadataCheck',
        4 => 'Espo\\Core\\Rebuild\\Actions\\GenerateInstanceId',
        5 => 'Espo\\Core\\Rebuild\\Actions\\SetIntegrationDefaults',
        6 => 'Espo\\Core\\Rebuild\\Actions\\SyncCurrency',
        7 => 'Espo\\Core\\Rebuild\\Actions\\CurrencyRates'
      ]
    ],
    'record' => [
      'selectApplierClassNameList' => [
        0 => 'Espo\\Core\\Select\\Applier\\AdditionalAppliers\\IsStarred'
      ]
    ],
    'recordId' => [
      'length' => 17
    ],
    'regExpPatterns' => [
      'noBadCharacters' => [
        'pattern' => '[^<>=]+'
      ],
      'noAsciiSpecialCharacters' => [
        'pattern' => '[^`~!@#$%^&*()_+={}\\[\\]|\\\\:;"\'<,>.?]+'
      ],
      'latinLetters' => [
        'pattern' => '[A-Za-z]+'
      ],
      'latinLettersDigits' => [
        'pattern' => '[A-Za-z0-9]+'
      ],
      'latinLettersDigitsWhitespace' => [
        'pattern' => '[A-Za-z0-9 ]+'
      ],
      'latinLettersWhitespace' => [
        'pattern' => '[A-Za-z ]+'
      ],
      'digits' => [
        'pattern' => '[0-9]+'
      ],
      'id' => [
        'pattern' => '[A-Za-z0-9_=\\-\\.]+',
        'isSystem' => true
      ],
      'phoneNumberLoose' => [
        'pattern' => '[0-9A-Za-z_@:#\\+\\(\\)\\-\\. ]+',
        'isSystem' => true
      ],
      'uriOptionalProtocol' => [
        'pattern' => '([a-zA-Z0-9]+\\:\\/\\/)?[a-zA-Z0-9%\\.\\/\\?\\:@\\-_=#$!+*\\(\\)\',]+\\.([a-zA-Z0-9%\\&\\.\\/\\?\\:@\\-_=#$!+*\\(\\)\',~])*',
        'isSystem' => true
      ],
      'uri' => [
        'pattern' => '([a-zA-Z0-9]+\\:\\/\\/){1}[a-zA-Z0-9%\\.\\/\\?\\:@\\-_=#$!+*\\(\\)\',]+\\.([a-zA-Z0-9%\\&\\.\\/\\?\\:@\\-_=#$!+*\\(\\)\',~])*',
        'isSystem' => true
      ]
    ],
    'relationships' => [
      'attachments' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\Attachments'
      ],
      'emailEmailAddress' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\EmailEmailAddress'
      ],
      'entityTeam' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\EntityTeam'
      ],
      'entityUser' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\EntityUser'
      ],
      'entityCollaborator' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\EntityCollaborator'
      ],
      'smsPhoneNumber' => [
        'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\LinkConverters\\SmsPhoneNumber'
      ]
    ],
    'scheduledJobs' => [
      'ProcessJobGroup' => [
        'name' => 'Process Job Group',
        'isSystem' => true,
        'scheduling' => '* * * * *',
        'jobClassName' => 'Espo\\Core\\Job\\Job\\Jobs\\ProcessJobGroup',
        'preparatorClassName' => 'Espo\\Core\\Job\\Preparator\\Preparators\\ProcessJobGroupPreparator'
      ],
      'ProcessJobQueueQ0' => [
        'name' => 'Process Job Queue q0',
        'isSystem' => true,
        'scheduling' => '* * * * *',
        'jobClassName' => 'Espo\\Core\\Job\\Job\\Jobs\\ProcessJobQueueQ0'
      ],
      'ProcessJobQueueQ1' => [
        'name' => 'Process Job Queue q1',
        'isSystem' => true,
        'scheduling' => '*/1 * * * *',
        'jobClassName' => 'Espo\\Core\\Job\\Job\\Jobs\\ProcessJobQueueQ1'
      ],
      'ProcessJobQueueE0' => [
        'name' => 'Process Job Queue e0',
        'isSystem' => true,
        'scheduling' => '* * * * *',
        'jobClassName' => 'Espo\\Core\\Job\\Job\\Jobs\\ProcessJobQueueE0'
      ],
      'Dummy' => [
        'isSystem' => true,
        'scheduling' => '1 */12 * * *',
        'jobClassName' => 'Espo\\Classes\\Jobs\\Dummy'
      ],
      'CheckNewVersion' => [
        'name' => 'Check for New Version',
        'isSystem' => true,
        'scheduling' => '15 5 * * *',
        'jobClassName' => 'Espo\\Classes\\Jobs\\CheckNewVersion'
      ],
      'CheckNewExtensionVersion' => [
        'name' => 'Check for New Versions of Installed Extensions',
        'isSystem' => true,
        'scheduling' => '25 5 * * *',
        'jobClassName' => 'Espo\\Classes\\Jobs\\CheckNewExtensionVersion'
      ],
      'SyncCurrencyRates' => [
        'name' => 'Sync Currency Rates',
        'jobClassName' => 'Espo\\Classes\\Jobs\\SyncCurrencyRates',
        'scheduling' => '2 0 * * *',
        'isSystem' => true
      ],
      'Cleanup' => [
        'jobClassName' => 'Espo\\Classes\\Jobs\\Cleanup'
      ],
      'AuthTokenControl' => [
        'jobClassName' => 'Espo\\Classes\\Jobs\\AuthTokenControl'
      ],
      'SendEmailNotifications' => [
        'jobClassName' => 'Espo\\Classes\\Jobs\\SendEmailNotifications'
      ],
      'ProcessWebhookQueue' => [
        'jobClassName' => 'Espo\\Classes\\Jobs\\ProcessWebhookQueue'
      ],
      'CheckEmailAccounts' => [
        'preparatorClassName' => 'Espo\\Classes\\JobPreparators\\CheckEmailAccounts',
        'jobClassName' => 'Espo\\Classes\\Jobs\\CheckEmailAccounts'
      ],
      'CheckInboundEmails' => [
        'preparatorClassName' => 'Espo\\Classes\\JobPreparators\\CheckInboundEmails',
        'jobClassName' => 'Espo\\Classes\\Jobs\\CheckInboundEmails'
      ],
      'SendScheduledEmails' => [
        'jobClassName' => 'Espo\\Classes\\Jobs\\SendScheduledEmails'
      ]
    ],
    'select' => [
      'whereItemConverterClassNameMap' => [
        'inCategory' => 'Espo\\Core\\Select\\Where\\ItemConverters\\InCategory',
        'isUserFromTeams' => 'Espo\\Core\\Select\\Where\\ItemConverters\\IsUserFromTeams'
      ]
    ],
    'smsProviders' => [],
    'templateHelpers' => [
      'googleMapsImage' => 'Espo\\Classes\\TemplateHelpers\\GoogleMaps',
      'markdownText' => 'Espo\\Classes\\TemplateHelpers\\MarkdownText',
      'tableTag' => 'Espo\\Classes\\TemplateHelpers\\TableTag',
      'trTag' => 'Espo\\Classes\\TemplateHelpers\\TrTag',
      'tdTag' => 'Espo\\Classes\\TemplateHelpers\\TdTag',
      'currencySymbol' => 'Espo\\Classes\\TemplateHelpers\\CurrencySymbol'
    ],
    'templates' => [
      'accessInfo' => [
        'scope' => 'User'
      ],
      'accessInfoPortal' => [
        'scope' => 'User'
      ],
      'assignment' => [
        'scopeListConfigParam' => 'assignmentEmailNotificationsEntityList'
      ],
      'mention' => [
        'scope' => 'Note'
      ],
      'noteEmailReceived' => [
        'scope' => 'Note'
      ],
      'notePost' => [
        'scope' => 'Note'
      ],
      'notePostNoParent' => [
        'scope' => 'Note'
      ],
      'noteStatus' => [
        'scope' => 'Note'
      ],
      'passwordChangeLink' => [
        'scope' => 'User'
      ],
      'twoFactorCode' => [
        'scope' => 'User'
      ],
      'invitation' => [
        'scopeList' => [
          0 => 'Meeting',
          1 => 'Call'
        ],
        'module' => 'Crm'
      ],
      'cancellation' => [
        'scopeList' => [
          0 => 'Meeting',
          1 => 'Call'
        ],
        'module' => 'Crm'
      ],
      'reminder' => [
        'scopeList' => [
          0 => 'Meeting',
          1 => 'Call',
          2 => 'Task'
        ],
        'module' => 'Crm'
      ]
    ],
    'webSocket' => [
      'categories' => [
        'newNotification' => [],
        'appParamsUpdate' => [],
        'recordUpdate' => [
          'paramList' => [
            0 => 'scope',
            1 => 'id'
          ],
          'accessCheckCommand' => 'AclCheck --userId=:userId --scope=:scope --id=:id --action=read'
        ],
        'streamUpdate' => [
          'paramList' => [
            0 => 'scope',
            1 => 'id'
          ],
          'accessCheckCommand' => 'AclCheck --userId=:userId --scope=:scope --id=:id --action=stream'
        ],
        'popupNotifications.event' => [],
        'calendarUpdate' => [
          'accessCheckCommand' => 'AclCheck --userId=:userId --scope=Calendar'
        ]
      ],
      'messagers' => [
        'ZeroMQ' => [
          'senderClassName' => 'Espo\\Core\\WebSocket\\ZeroMQSender',
          'subscriberClassName' => 'Espo\\Core\\WebSocket\\ZeroMQSubscriber'
        ]
      ]
    ],
    'calendar' => [
      'additionalAttributeList' => [
        0 => 'color'
      ]
    ],
    'popupNotifications' => [
      'event' => [
        'grouped' => true,
        'providerClassName' => 'Espo\\Modules\\Crm\\Tools\\Activities\\PopupNotificationsProvider',
        'useWebSocket' => true,
        'portalDisabled' => true,
        'view' => 'crm:views/meeting/popup-notification'
      ]
    ]
  ],
  'authenticationMethods' => [
    'ApiKey' => [
      'api' => true,
      'credentialsHeader' => 'X-Api-Key'
    ],
    'Espo' => [
      'portalDefault' => true,
      'settings' => [
        'isAvailable' => true
      ]
    ],
    'Hmac' => [
      'api' => true,
      'credentialsHeader' => 'X-Hmac-Authorization'
    ],
    'LDAP' => [
      'implementationClassName' => 'Espo\\Core\\Authentication\\Ldap\\LdapLogin',
      'portalDefault' => true,
      'settings' => [
        'isAvailable' => true,
        'layout' => [
          'label' => 'LDAP',
          'rows' => [
            0 => [
              0 => [
                'name' => 'ldapHost'
              ],
              1 => [
                'name' => 'ldapPort'
              ]
            ],
            1 => [
              0 => [
                'name' => 'ldapAuth'
              ],
              1 => [
                'name' => 'ldapSecurity'
              ]
            ],
            2 => [
              0 => [
                'name' => 'ldapUsername',
                'fullWidth' => true
              ]
            ],
            3 => [
              0 => [
                'name' => 'ldapPassword'
              ],
              1 => [
                'name' => 'testConnection',
                'customLabel' => NULL,
                'view' => 'views/admin/authentication/fields/test-connection'
              ]
            ],
            4 => [
              0 => [
                'name' => 'ldapUserNameAttribute'
              ],
              1 => [
                'name' => 'ldapUserObjectClass'
              ]
            ],
            5 => [
              0 => [
                'name' => 'ldapAccountCanonicalForm'
              ],
              1 => [
                'name' => 'ldapBindRequiresDn'
              ]
            ],
            6 => [
              0 => [
                'name' => 'ldapBaseDn',
                'fullWidth' => true
              ]
            ],
            7 => [
              0 => [
                'name' => 'ldapUserLoginFilter',
                'fullWidth' => true
              ]
            ],
            8 => [
              0 => [
                'name' => 'ldapAccountDomainName'
              ],
              1 => [
                'name' => 'ldapAccountDomainNameShort'
              ]
            ],
            9 => [
              0 => [
                'name' => 'ldapTryUsernameSplit'
              ],
              1 => [
                'name' => 'ldapOptReferrals'
              ]
            ],
            10 => [
              0 => [
                'name' => 'ldapCreateEspoUser'
              ],
              1 => false
            ],
            11 => [
              0 => [
                'name' => 'ldapUserFirstNameAttribute'
              ],
              1 => [
                'name' => 'ldapUserLastNameAttribute'
              ]
            ],
            12 => [
              0 => [
                'name' => 'ldapUserTitleAttribute'
              ],
              1 => false
            ],
            13 => [
              0 => [
                'name' => 'ldapUserEmailAddressAttribute'
              ],
              1 => [
                'name' => 'ldapUserPhoneNumberAttribute'
              ]
            ],
            14 => [
              0 => [
                'name' => 'ldapUserTeams'
              ],
              1 => [
                'name' => 'ldapUserDefaultTeam'
              ]
            ],
            15 => [
              0 => [
                'name' => 'ldapPortalUserLdapAuth'
              ],
              1 => false
            ],
            16 => [
              0 => [
                'name' => 'ldapPortalUserPortals'
              ],
              1 => [
                'name' => 'ldapPortalUserRoles'
              ]
            ]
          ]
        ],
        'fieldList' => [
          0 => 'ldapHost',
          1 => 'ldapPort',
          2 => 'ldapAuth',
          3 => 'ldapSecurity',
          4 => 'ldapUsername',
          5 => 'ldapPassword',
          6 => 'ldapBindRequiresDn',
          7 => 'ldapUserLoginFilter',
          8 => 'ldapBaseDn',
          9 => 'ldapAccountCanonicalForm',
          10 => 'ldapAccountDomainName',
          11 => 'ldapAccountDomainNameShort',
          12 => 'ldapAccountDomainName',
          13 => 'ldapAccountDomainNameShort',
          14 => 'ldapTryUsernameSplit',
          15 => 'ldapOptReferrals',
          16 => 'ldapCreateEspoUser',
          17 => 'ldapPortalUserLdapAuth'
        ],
        'dynamicLogic' => [
          'fields' => [
            'ldapHost' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'LDAP'
                  ]
                ]
              ]
            ],
            'ldapUserNameAttribute' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'LDAP'
                  ]
                ]
              ]
            ],
            'ldapUserObjectClass' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'LDAP'
                  ]
                ]
              ]
            ],
            'ldapUsername' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapAuth'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapAuth'
                  ],
                  1 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'LDAP'
                  ]
                ]
              ]
            ],
            'ldapPassword' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapAuth'
                  ]
                ]
              ]
            ],
            'testConnection' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapAuth'
                  ]
                ]
              ]
            ],
            'ldapAccountDomainName' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'in',
                    'attribute' => 'ldapAccountCanonicalForm',
                    'value' => [
                      0 => 'Backslash',
                      1 => 'Principal'
                    ]
                  ]
                ]
              ]
            ],
            'ldapAccountDomainNameShort' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'in',
                    'attribute' => 'ldapAccountCanonicalForm',
                    'value' => [
                      0 => 'Backslash',
                      1 => 'Principal'
                    ]
                  ]
                ]
              ]
            ],
            'ldapUserTitleAttribute' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserFirstNameAttribute' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserLastNameAttribute' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserEmailAddressAttribute' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserPhoneNumberAttribute' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ],
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserTeams' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapUserDefaultTeam' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapCreateEspoUser'
                  ]
                ]
              ]
            ],
            'ldapPortalUserPortals' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapPortalUserLdapAuth'
                  ]
                ]
              ]
            ],
            'ldapPortalUserRoles' => [
              'visible' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'ldapPortalUserLdapAuth'
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'Oidc' => [
      'implementationClassName' => 'Espo\\Core\\Authentication\\Oidc\\Login',
      'logoutClassName' => 'Espo\\Core\\Authentication\\Oidc\\Logout',
      'login' => [
        'handler' => 'handlers/login/oidc',
        'fallbackConfigParam' => 'oidcFallback'
      ],
      'provider' => [
        'isAvailable' => true
      ],
      'settings' => [
        'isAvailable' => true,
        'layout' => [
          'label' => 'OIDC',
          'rows' => [
            0 => [
              0 => [
                'name' => 'oidcClientId'
              ],
              1 => [
                'name' => 'oidcClientSecret'
              ]
            ],
            1 => [
              0 => [
                'name' => 'oidcAuthorizationRedirectUri',
                'view' => 'views/settings/fields/oidc-redirect-uri',
                'params' => [
                  'readOnly' => true,
                  'copyToClipboard' => true
                ]
              ],
              1 => false
            ],
            2 => [
              0 => [
                'name' => 'oidcAuthorizationEndpoint'
              ],
              1 => [
                'name' => 'oidcTokenEndpoint'
              ]
            ],
            3 => [
              0 => [
                'name' => 'oidcJwksEndpoint'
              ],
              1 => [
                'name' => 'oidcJwtSignatureAlgorithmList'
              ]
            ],
            4 => [
              0 => [
                'name' => 'oidcUserInfoEndpoint'
              ],
              1 => false
            ],
            5 => [
              0 => [
                'name' => 'oidcScopes'
              ],
              1 => [
                'name' => 'oidcUsernameClaim'
              ]
            ],
            6 => [
              0 => [
                'name' => 'oidcCreateUser'
              ],
              1 => [
                'name' => 'oidcSync'
              ]
            ],
            7 => [
              0 => [
                'name' => 'oidcTeams'
              ],
              1 => [
                'name' => 'oidcGroupClaim'
              ]
            ],
            8 => [
              0 => [
                'name' => 'oidcSyncTeams'
              ],
              1 => false
            ],
            9 => [
              0 => [
                'name' => 'oidcFallback'
              ],
              1 => [
                'name' => 'oidcAllowRegularUserFallback'
              ]
            ],
            10 => [
              0 => [
                'name' => 'oidcAllowAdminUser'
              ],
              1 => [
                'name' => 'oidcLogoutUrl'
              ]
            ],
            11 => [
              0 => [
                'name' => 'oidcAuthorizationPrompt'
              ],
              1 => false
            ]
          ]
        ],
        'fieldList' => [
          0 => 'oidcClientId',
          1 => 'oidcClientSecret',
          2 => 'oidcAuthorizationEndpoint',
          3 => 'oidcTokenEndpoint',
          4 => 'oidcJwksEndpoint',
          5 => 'oidcJwtSignatureAlgorithmList',
          6 => 'oidcScopes',
          7 => 'oidcGroupClaim',
          8 => 'oidcCreateUser',
          9 => 'oidcUsernameClaim',
          10 => 'oidcTeams',
          11 => 'oidcSync',
          12 => 'oidcSyncTeams',
          13 => 'oidcAuthorizationRedirectUri',
          14 => 'oidcFallback',
          15 => 'oidcAllowRegularUserFallback',
          16 => 'oidcAllowAdminUser',
          17 => 'oidcLogoutUrl'
        ],
        'dynamicLogic' => [
          'fields' => [
            'oidcClientId' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ]
                ]
              ]
            ],
            'oidcAuthorizationEndpoint' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ]
                ]
              ]
            ],
            'oidcTokenEndpoint' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ]
                ]
              ]
            ],
            'oidcUsernameClaim' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ]
                ]
              ]
            ],
            'oidcJwtSignatureAlgorithmList' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ]
                ]
              ]
            ],
            'oidcJwksEndpoint' => [
              'required' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ],
                  1 => [
                    'type' => 'or',
                    'value' => [
                      0 => [
                        'type' => 'contains',
                        'attribute' => 'oidcJwtSignatureAlgorithmList',
                        'value' => 'RS256'
                      ],
                      1 => [
                        'type' => 'contains',
                        'attribute' => 'oidcJwtSignatureAlgorithmList',
                        'value' => 'RS384'
                      ],
                      2 => [
                        'type' => 'contains',
                        'attribute' => 'oidcJwtSignatureAlgorithmList',
                        'value' => 'RS512'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'oidcAllowRegularUserFallback' => [
              'invalid' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ],
                  1 => [
                    'type' => 'isTrue',
                    'attribute' => 'oidcAllowRegularUserFallback'
                  ],
                  2 => [
                    'type' => 'isFalse',
                    'attribute' => 'oidcFallback'
                  ]
                ]
              ]
            ],
            'oidcAllowAdminUser' => [
              'invalid' => [
                'conditionGroup' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'authenticationMethod',
                    'value' => 'Oidc'
                  ],
                  1 => [
                    'type' => 'isFalse',
                    'attribute' => 'oidcAllowAdminUser'
                  ],
                  2 => [
                    'type' => 'isFalse',
                    'attribute' => 'oidcFallback'
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ]
  ],
  'clientDefs' => [
    'ActionHistoryRecord' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'recordViews' => [
        'list' => 'views/action-history-record/record/list'
      ],
      'modalViews' => [
        'detail' => 'views/action-history-record/modals/detail'
      ]
    ],
    'AddressCountry' => [
      'controller' => 'controllers/record',
      'duplicateDisabled' => true,
      'mergeDisabled' => true,
      'menu' => [
        'list' => [
          'dropdown' => [
            0 => [
              'name' => 'populateDefaults',
              'labelTranslation' => 'AddressCountry.strings.populateDefaults',
              'handler' => 'handlers/admin/address-country/populate-defaults',
              'actionFunction' => 'populate'
            ]
          ]
        ]
      ]
    ],
    'AddressMap' => [
      'controller' => 'controllers/address-map'
    ],
    'ApiUser' => [
      'controller' => 'controllers/api-user',
      'views' => [
        'detail' => 'views/user/detail',
        'list' => 'views/api-user/list'
      ],
      'recordViews' => [
        'list' => 'views/user/record/list',
        'detail' => 'views/user/record/detail',
        'edit' => 'views/user/record/edit',
        'detailSmall' => 'views/user/record/detail-quick',
        'editSmall' => 'views/user/record/edit-quick'
      ],
      'defaultSidePanelFieldLists' => [
        'detail' => [
          0 => 'avatar',
          1 => 'createdAt',
          2 => 'lastAccess'
        ],
        'detailSmall' => [
          0 => 'avatar',
          1 => 'createdAt'
        ],
        'edit' => [
          0 => 'avatar'
        ],
        'editSmall' => [
          0 => 'avatar'
        ]
      ],
      'filterList' => [],
      'boolFilterList' => []
    ],
    'AppLogRecord' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'mergeDisabled' => true,
      'recordViews' => [
        'list' => 'views/admin/app-log-record/record/list'
      ],
      'filterList' => [
        0 => [
          'name' => 'errors'
        ]
      ]
    ],
    'AppSecret' => [
      'controller' => 'controllers/record',
      'mergeDisabled' => true,
      'exportDisabled' => true,
      'massUpdateDisabled' => true
    ],
    'Attachment' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'recordViews' => [
        'list' => 'views/attachment/record/list',
        'detail' => 'views/attachment/record/detail'
      ],
      'modalViews' => [
        'detail' => 'views/attachment/modals/detail'
      ],
      'filterList' => [
        0 => 'orphan'
      ]
    ],
    'AuthLogRecord' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'list' => 'views/admin/auth-log-record/record/list',
        'detail' => 'views/admin/auth-log-record/record/detail',
        'detailSmall' => 'views/admin/auth-log-record/record/detail-small'
      ],
      'modalViews' => [
        'detail' => 'views/admin/auth-log-record/modals/detail'
      ],
      'filterList' => [
        0 => 'accepted',
        1 => 'denied'
      ],
      'createDisabled' => true,
      'relationshipPanels' => [
        'actionHistoryRecords' => [
          'createDisabled' => true,
          'selectDisabled' => true,
          'unlinkDisabled' => true,
          'rowActionsView' => 'views/record/row-actions/relationship-view-only'
        ]
      ]
    ],
    'AuthToken' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'list' => 'views/admin/auth-token/record/list',
        'detail' => 'views/admin/auth-token/record/detail',
        'detailSmall' => 'views/admin/auth-token/record/detail-small'
      ],
      'modalViews' => [
        'detail' => 'views/admin/auth-token/modals/detail'
      ],
      'filterList' => [
        0 => 'active',
        1 => 'inactive'
      ],
      'createDisabled' => true,
      'relationshipPanels' => [
        'actionHistoryRecords' => [
          'createDisabled' => true,
          'selectDisabled' => true,
          'unlinkDisabled' => true,
          'rowActionsView' => 'views/record/row-actions/relationship-view-only'
        ]
      ]
    ],
    'AuthenticationProvider' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'detail' => 'views/authentication-provider/record/detail',
        'edit' => 'views/authentication-provider/record/edit'
      ],
      'searchPanelDisabled' => true,
      'inlineEditDisabled' => true,
      'duplicateDisabled' => true,
      'massUpdateDisabled' => true,
      'massRemoveDisabled' => true,
      'mergeDisabled' => true
    ],
    'CurrencyRecord' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'removeDisabled' => true,
      'nameAttribute' => 'code',
      'defaultFilterData' => [
        'primary' => 'active'
      ],
      'filterList' => [
        0 => 'active'
      ],
      'viewSetupHandlers' => [
        'record/detail' => [
          0 => 'handlers/currency-record/record-detail'
        ]
      ],
      'relationshipPanels' => [
        'rates' => [
          'layout' => 'listForRecord',
          'createAttributeMap' => [
            'code' => 'recordName'
          ],
          'view' => 'views/currency-record/record/panels/rates',
          'unlinkDisabled' => true
        ]
      ]
    ],
    'CurrencyRecordRate' => [
      'controller' => 'controllers/record',
      'modelDefaultsPreparator' => 'handlers/currency-record-rate/default-preparator',
      'acl' => 'acl/currency-record-rate',
      'textFilterDisabled' => true
    ],
    'Dashboard' => [
      'controller' => 'controllers/dashboard',
      'iconClass' => 'fas fa-th-large'
    ],
    'DashboardTemplate' => [
      'controller' => 'controllers/record',
      'views' => [
        'detail' => 'views/dashboard-template/detail'
      ],
      'recordViews' => [
        'list' => 'views/dashboard-template/record/list'
      ],
      'menu' => [
        'detail' => [
          'buttons' => [
            0 => [
              'action' => 'deployToUsers',
              'label' => 'Deploy to Users'
            ],
            1 => [
              'action' => 'deployToTeam',
              'label' => 'Deploy to Team'
            ]
          ]
        ]
      ],
      'searchPanelDisabled' => true
    ],
    'DynamicLogic' => [
      'itemTypes' => [
        'and' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/group-base',
          'operator' => 'and'
        ],
        'or' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/group-base',
          'operator' => 'or'
        ],
        'not' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/group-not',
          'operator' => 'not'
        ],
        'equals' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '='
        ],
        'notEquals' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&ne;'
        ],
        'greaterThan' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&gt;'
        ],
        'lessThan' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&lt;'
        ],
        'greaterThanOrEquals' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&ge;'
        ],
        'lessThanOrEquals' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&le;'
        ],
        'isEmpty' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= &empty;'
        ],
        'isNotEmpty' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '&ne; &empty;'
        ],
        'isTrue' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= 1'
        ],
        'isFalse' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= 0'
        ],
        'in' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-multiple-values-base',
          'operatorString' => '&isin;'
        ],
        'notIn' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-multiple-values-base',
          'operatorString' => '&notin;'
        ],
        'isToday' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-is-today',
          'operatorString' => '='
        ],
        'inFuture' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-in-future',
          'operatorString' => '&isin;'
        ],
        'inPast' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-in-past',
          'operatorString' => '&isin;'
        ],
        'contains' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-link',
          'operatorString' => '&niv;'
        ],
        'notContains' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-link',
          'operatorString' => '&notni;'
        ],
        'has' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-enum',
          'operatorString' => '&niv;'
        ],
        'notHas' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-enum',
          'operatorString' => '&notni;'
        ],
        'startsWith' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-varchar'
        ],
        'endsWith' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-varchar'
        ],
        'matches' => [
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-varchar'
        ]
      ],
      'fieldTypes' => [
        'bool' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isTrue',
            1 => 'isFalse'
          ]
        ],
        'varchar' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty',
            4 => 'contains',
            5 => 'notContains',
            6 => 'startsWith',
            7 => 'endsWith',
            8 => 'matches'
          ],
          'conditionTypes' => [
            'contains' => [
              'valueType' => 'field',
              'itemView' => 'views/admin/dynamic-logic/conditions-string/item-base'
            ],
            'notContains' => [
              'valueType' => 'field',
              'itemView' => 'views/admin/dynamic-logic/conditions-string/item-base'
            ]
          ]
        ],
        'url' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty'
          ]
        ],
        'email' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'phone' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'text' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'contains',
            3 => 'notContains',
            4 => 'matches'
          ],
          'conditionTypes' => [
            'contains' => [
              'valueType' => 'varchar',
              'itemView' => 'views/admin/dynamic-logic/conditions-string/item-value-varchar'
            ],
            'notContains' => [
              'valueType' => 'varchar',
              'itemView' => 'views/admin/dynamic-logic/conditions-string/item-value-varchar'
            ]
          ]
        ],
        'int' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
            4 => 'greaterThan',
            5 => 'lessThan',
            6 => 'greaterThanOrEquals',
            7 => 'lessThanOrEquals'
          ]
        ],
        'float' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
            4 => 'greaterThan',
            5 => 'lessThan',
            6 => 'greaterThanOrEquals',
            7 => 'lessThanOrEquals'
          ]
        ],
        'decimal' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'greaterThan',
            3 => 'lessThan',
            4 => 'greaterThanOrEquals',
            5 => 'lessThanOrEquals'
          ]
        ],
        'currency' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
            4 => 'greaterThan',
            5 => 'lessThan',
            6 => 'greaterThanOrEquals',
            7 => 'lessThanOrEquals'
          ]
        ],
        'date' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast',
            5 => 'equals',
            6 => 'notEquals'
          ]
        ],
        'datetime' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast'
          ]
        ],
        'datetimeOptional' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast'
          ]
        ],
        'enum' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/enum',
          'typeList' => [
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty',
            4 => 'in',
            5 => 'notIn'
          ]
        ],
        'link' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals'
          ]
        ],
        'linkOne' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals'
          ]
        ],
        'file' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'image' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'linkParent' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link-parent',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals'
          ]
        ],
        'linkMultiple' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link-multiple',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'contains',
            3 => 'notContains'
          ]
        ],
        'foreign' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => [
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty'
          ]
        ],
        'id' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/enum',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'multiEnum' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/multi-enum',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'has',
            3 => 'notHas'
          ]
        ],
        'array' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/multi-enum',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'has',
            3 => 'notHas'
          ]
        ],
        'checklist' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/multi-enum',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'has',
            3 => 'notHas'
          ]
        ],
        'urlMultiple' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/multi-enum',
          'typeList' => [
            0 => 'isEmpty',
            1 => 'isNotEmpty'
          ]
        ],
        'currentUser' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/current-user',
          'typeList' => [
            0 => 'equals',
            1 => 'notEquals'
          ]
        ],
        'currentUserTeams' => [
          'view' => 'views/admin/dynamic-logic/conditions/field-types/current-user-teams',
          'typeList' => [
            0 => 'contains',
            1 => 'notContains'
          ]
        ]
      ],
      'conditionTypes' => [
        'isTrue' => [
          'valueType' => 'empty'
        ],
        'isFalse' => [
          'valueType' => 'empty'
        ],
        'isEmpty' => [
          'valueType' => 'empty'
        ],
        'isNotEmpty' => [
          'valueType' => 'empty'
        ],
        'equals' => [
          'valueType' => 'field'
        ],
        'notEquals' => [
          'valueType' => 'field'
        ],
        'greaterThan' => [
          'valueType' => 'field'
        ],
        'lessThan' => [
          'valueType' => 'field'
        ],
        'greaterThanOrEquals' => [
          'valueType' => 'field'
        ],
        'lessThanOrEquals' => [
          'valueType' => 'field'
        ],
        'in' => [
          'valueType' => 'field'
        ],
        'notIn' => [
          'valueType' => 'field'
        ],
        'contains' => [
          'valueType' => 'custom'
        ],
        'notContains' => [
          'valueType' => 'custom'
        ],
        'inPast' => [
          'valueType' => 'empty'
        ],
        'isFuture' => [
          'valueType' => 'empty'
        ],
        'isToday' => [
          'valueType' => 'empty'
        ],
        'has' => [
          'valueType' => 'field'
        ],
        'notHas' => [
          'valueType' => 'field'
        ],
        'startsWith' => [
          'valueType' => 'varchar'
        ],
        'endsWith' => [
          'valueType' => 'varchar'
        ],
        'matches' => [
          'valueType' => 'varchar-matches'
        ]
      ]
    ],
    'Email' => [
      'controller' => 'controllers/email',
      'acl' => 'acl/email',
      'views' => [
        'list' => 'views/email/list',
        'detail' => 'views/email/detail'
      ],
      'recordViews' => [
        'list' => 'views/email/record/list',
        'detail' => 'views/email/record/detail',
        'edit' => 'views/email/record/edit',
        'editQuick' => 'views/email/record/edit-quick',
        'detailQuick' => 'views/email/record/detail-quick',
        'compose' => 'views/email/record/compose',
        'listRelated' => 'views/email/record/list-related'
      ],
      'modalViews' => [
        'detail' => 'views/email/modals/detail',
        'compose' => 'views/modals/compose-email'
      ],
      'quickCreateModalType' => 'compose',
      'defaultSidePanelView' => 'views/email/record/panels/default-side',
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'event',
            'label' => 'Event',
            'view' => 'views/email/record/panels/event',
            'isForm' => true,
            'hidden' => true
          ]
        ]
      ],
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Compose',
              'action' => 'composeEmail',
              'style' => 'danger',
              'acl' => 'create',
              'className' => 'btn-s-wide',
              'title' => 'Ctrl+Space'
            ]
          ],
          'dropdown' => [
            0 => [
              'name' => 'archiveEmail',
              'label' => 'Archive Email',
              'link' => '#Email/create',
              'acl' => 'create'
            ],
            1 => [
              'name' => 'importEml',
              'label' => 'Import EML',
              'handler' => 'handlers/email/list-actions',
              'checkVisibilityFunction' => 'checkImportEml',
              'actionFunction' => 'importEml'
            ],
            2 => false,
            3 => [
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate'
            ],
            4 => [
              'label' => 'Folders',
              'link' => '#EmailFolder',
              'configCheck' => '!emailFoldersDisabled',
              'accessDataList' => [
                0 => [
                  'inPortalDisabled' => true
                ]
              ]
            ],
            5 => [
              'label' => 'Group Folders',
              'link' => '#GroupEmailFolder',
              'configCheck' => '!emailFoldersDisabled',
              'accessDataList' => [
                0 => [
                  'inPortalDisabled' => true
                ],
                1 => [
                  'isAdminOnly' => true
                ]
              ]
            ],
            6 => [
              'label' => 'Filters',
              'link' => '#EmailFilter',
              'accessDataList' => [
                0 => [
                  'inPortalDisabled' => true
                ]
              ]
            ]
          ]
        ],
        'detail' => [
          'dropdown' => [
            0 => [
              'label' => 'Reply',
              'action' => 'reply',
              'acl' => 'read'
            ],
            1 => [
              'label' => 'Reply to All',
              'action' => 'replyToAll',
              'acl' => 'read'
            ],
            2 => [
              'label' => 'Forward',
              'action' => 'forward',
              'acl' => 'read'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'users' => [
          'selectHandler' => 'handlers/email/select-user'
        ]
      ],
      'filterList' => [],
      'defaultFilterData' => [],
      'boolFilterList' => [],
      'iconClass' => 'fas fa-envelope',
      'layoutBottomPanelsDetailDisabled' => true,
      'layoutDetailDisabled' => true,
      'layoutDetailSmallDisabled' => true,
      'layoutSidePanelsDetailSmallDisabled' => true,
      'layoutSidePanelsEditSmallDisabled' => true
    ],
    'EmailAccount' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'list' => 'views/email-account/record/list',
        'detail' => 'views/email-account/record/detail',
        'edit' => 'views/email-account/record/edit'
      ],
      'views' => [
        'list' => 'views/email-account/list'
      ],
      'inlineEditDisabled' => true,
      'filterList' => [
        0 => [
          'name' => 'active'
        ]
      ],
      'relationshipPanels' => [
        'filters' => [
          'select' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-edit-and-remove',
          'unlinkDisabled' => true
        ],
        'emails' => [
          'select' => false,
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/remove-only',
          'unlinkDisabled' => true
        ]
      ]
    ],
    'EmailAddress' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'exportDisabled' => true,
      'mergeDisabled' => true,
      'filterList' => [
        0 => 'orphan'
      ]
    ],
    'EmailFilter' => [
      'controller' => 'controllers/email-filter',
      'dynamicHandler' => 'handlers/email-filter',
      'modalViews' => [
        'edit' => 'views/email-filter/modals/edit'
      ],
      'recordViews' => [
        'list' => 'views/email-filter/record/list'
      ],
      'inlineEditDisabled' => true,
      'searchPanelDisabled' => false,
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Emails',
              'link' => '#Email',
              'style' => 'default',
              'aclScope' => 'Email'
            ]
          ]
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ]
    ],
    'EmailFolder' => [
      'controller' => 'controllers/record',
      'views' => [
        'list' => 'views/email-folder/list'
      ],
      'recordViews' => [
        'list' => 'views/email-folder/record/list',
        'editQuick' => 'views/email-folder/record/edit-small'
      ],
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Emails',
              'link' => '#Email',
              'style' => 'default',
              'aclScope' => 'Email'
            ]
          ]
        ]
      ],
      'searchPanelDisabled' => true
    ],
    'EmailTemplate' => [
      'controller' => 'controllers/record',
      'forceListViewSettings' => true,
      'views' => [
        'list' => 'views/email-template/list'
      ],
      'recordViews' => [
        'edit' => 'views/email-template/record/edit',
        'detail' => 'views/email-template/record/detail',
        'editQuick' => 'views/email-template/record/edit-quick'
      ],
      'modalViews' => [
        'select' => 'views/modals/select-records-with-categories'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'information',
            'label' => 'Info',
            'view' => 'views/email-template/record/panels/information'
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'information',
            'label' => 'Info',
            'view' => 'views/email-template/record/panels/information'
          ]
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'filterList' => [
        0 => 'actual'
      ],
      'placeholderList' => [
        0 => 'optOutUrl',
        1 => 'optOutLink'
      ],
      'iconClass' => 'fas fa-envelope-square'
    ],
    'EmailTemplateCategory' => [
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => [
        'listTree' => [
          'buttons' => [
            0 => [
              'label' => 'List View',
              'link' => '#EmailTemplateCategory/list',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate',
              'style' => 'default'
            ]
          ]
        ],
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Tree View',
              'link' => '#EmailTemplateCategory',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate',
              'style' => 'default'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'emailTemplates' => [
          'create' => false
        ],
        'children' => [
          'selectDisabled' => true,
          'unlinkDisabled' => true
        ]
      ]
    ],
    'ExternalAccount' => [
      'controller' => 'controllers/external-account'
    ],
    'Global' => [
      'detailActionList' => [
        0 => [
          'name' => 'viewAuditLog',
          'label' => 'View Audit Log',
          'actionFunction' => 'show',
          'checkVisibilityFunction' => 'isAvailable',
          'handler' => 'handlers/record/view-audit-log',
          'groupIndex' => 4
        ],
        1 => [
          'name' => 'viewUserAccess',
          'label' => 'View User Access',
          'actionFunction' => 'show',
          'checkVisibilityFunction' => 'isAvailable',
          'handler' => 'handlers/record/view-user-access',
          'groupIndex' => 4
        ]
      ]
    ],
    'GlobalStream' => [
      'controller' => 'controllers/global-stream',
      'iconClass' => 'fas fa-rss-square'
    ],
    'GroupEmailFolder' => [
      'controller' => 'controllers/record',
      'views' => [
        'list' => 'views/group-email-folder/list'
      ],
      'recordViews' => [
        'list' => 'views/group-email-folder/record/list',
        'editQuick' => 'views/email-folder/record/edit-small'
      ],
      'searchPanelDisabled' => true,
      'massUpdateDisabled' => true,
      'mergeDisabled' => true,
      'massRemoveDisabled' => true,
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'name' => 'emails',
              'labelTranslation' => 'Global.scopeNamesPlural.Email',
              'link' => '#Email',
              'style' => 'default',
              'aclScope' => 'Email'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'emails' => [
          'select' => false,
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/remove-only',
          'unlinkDisabled' => true
        ]
      ]
    ],
    'Home' => [
      'iconClass' => 'fas fa-th-large'
    ],
    'Import' => [
      'controller' => 'controllers/import',
      'acl' => 'acl/import',
      'recordViews' => [
        'list' => 'views/import/record/list',
        'detail' => 'views/import/record/detail'
      ],
      'views' => [
        'list' => 'views/import/list',
        'detail' => 'views/import/detail'
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'imported',
            'label' => 'Imported',
            'view' => 'views/import/record/panels/imported',
            'createDisabled' => true,
            'selectDisabled' => true,
            'unlinkDisabled' => true
          ],
          1 => [
            'name' => 'duplicates',
            'label' => 'Duplicates',
            'view' => 'views/import/record/panels/duplicates',
            'rowActionsView' => 'views/import/record/row-actions/duplicates',
            'createDisabled' => true,
            'selectDisabled' => true,
            'unlinkDisabled' => true
          ],
          2 => [
            'name' => 'updated',
            'label' => 'Updated',
            'view' => 'views/import/record/panels/updated',
            'createDisabled' => true,
            'selectDisabled' => true,
            'unlinkDisabled' => true
          ]
        ]
      ],
      'textFilterDisabled' => true,
      'relationshipPanels' => [
        'errors' => [
          'unlinkDisabled' => true,
          'actionList' => [
            0 => [
              'name' => 'export',
              'label' => 'Export',
              'handler' => 'handlers/import',
              'actionFunction' => 'errorExport'
            ]
          ]
        ]
      ],
      'iconClass' => 'fas fa-file-import',
      'dateFormatList' => [
        0 => 'YYYY-MM-DD',
        1 => 'DD-MM-YYYY',
        2 => 'MM-DD-YYYY',
        3 => 'MM/DD/YYYY',
        4 => 'DD/MM/YYYY',
        5 => 'DD.MM.YYYY',
        6 => 'MM.DD.YYYY',
        7 => 'YYYY.MM.DD',
        8 => 'DD. MM. YYYY'
      ],
      'timeFormatList' => [
        0 => 'HH:mm:ss',
        1 => 'HH:mm',
        2 => 'hh:mm a',
        3 => 'hh:mma',
        4 => 'hh:mm A',
        5 => 'hh:mmA',
        6 => 'hh:mm:ss a',
        7 => 'hh:mm:ssa',
        8 => 'hh:mm:ss A',
        9 => 'hh:mm:ssA'
      ]
    ],
    'ImportError' => [
      'controller' => 'controllers/record',
      'acl' => 'acl/foreign',
      'searchPanelDisabled' => true,
      'createDisabled' => true,
      'editDisabled' => true
    ],
    'InboundEmail' => [
      'recordViews' => [
        'detail' => 'views/inbound-email/record/detail',
        'edit' => 'views/inbound-email/record/edit',
        'list' => 'views/inbound-email/record/list'
      ],
      'inlineEditDisabled' => true,
      'searchPanelDisabled' => true,
      'relationshipPanels' => [
        'filters' => [
          'select' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-edit-and-remove',
          'unlinkDisabled' => true
        ],
        'emails' => [
          'select' => false,
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/remove-only',
          'unlinkDisabled' => true
        ]
      ],
      'defaultSidePanelFieldLists' => [
        'detail' => [],
        'detailSmall' => [],
        'edit' => [],
        'editSmall' => []
      ]
    ],
    'Job' => [
      'modalViews' => [
        'detail' => 'views/admin/job/modals/detail'
      ],
      'recordViews' => [
        'list' => 'views/admin/job/record/list',
        'detailQuick' => 'views/admin/job/record/detail-small'
      ]
    ],
    'LastViewed' => [
      'controller' => 'controllers/last-viewed',
      'views' => [
        'list' => 'views/last-viewed/list'
      ],
      'recordViews' => [
        'list' => 'views/last-viewed/record/list'
      ]
    ],
    'LayoutSet' => [
      'controller' => 'controllers/layout-set',
      'recordViews' => [
        'list' => 'views/layout-set/record/list'
      ],
      'searchPanelDisabled' => true,
      'duplicateDisabled' => true,
      'relationshipPanels' => [
        'teams' => [
          'createDisabled' => true,
          'viewDisabled' => true,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only'
        ]
      ]
    ],
    'LeadCapture' => [
      'controller' => 'controllers/record',
      'searchPanelDisabled' => true,
      'recordViews' => [
        'detail' => 'views/lead-capture/record/detail',
        'list' => 'views/lead-capture/record/list'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'request',
            'label' => 'Request',
            'isForm' => true,
            'view' => 'views/lead-capture/record/panels/request',
            'notRefreshable' => true
          ],
          1 => [
            'name' => 'form',
            'label' => 'Web Form',
            'isForm' => true,
            'view' => 'views/lead-capture/record/panels/form',
            'notRefreshable' => true
          ]
        ]
      ],
      'relationshipPanels' => [
        'logRecords' => [
          'rowActionsView' => 'views/record/row-actions/view-and-remove',
          'layout' => 'listForLeadCapture',
          'select' => false,
          'create' => false
        ]
      ]
    ],
    'LeadCaptureLogRecord' => [
      'modalViews' => [
        'detail' => 'views/lead-capture-log-record/modals/detail'
      ]
    ],
    'Note' => [
      'controller' => 'controllers/note',
      'collection' => 'collections/note',
      'recordViews' => [
        'edit' => 'views/note/record/edit',
        'editQuick' => 'views/note/record/edit',
        'listRelated' => 'views/stream/record/list'
      ],
      'modalViews' => [
        'edit' => 'views/note/modals/edit'
      ],
      'itemViews' => [
        'Post' => 'views/stream/notes/post',
        'EventConfirmation' => 'crm:views/stream/notes/event-confirmation'
      ],
      'viewSetupHandlers' => [
        'record/detail' => [
          0 => 'handlers/note/record-detail-setup'
        ]
      ]
    ],
    'Notification' => [
      'controller' => 'controllers/notification',
      'acl' => 'acl/notification',
      'aclPortal' => 'acl-portal/notification',
      'collection' => 'collections/note',
      'itemViews' => [
        'System' => 'views/notification/items/system',
        'EmailInbox' => 'views/notification/items/email-inbox',
        'EventAttendee' => 'crm:views/notification/items/event-attendee'
      ]
    ],
    'OAuthAccount' => [
      'controller' => 'controllers/record',
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'connection',
            'label' => 'Connection',
            'view' => 'views/o-auth-account/records/panels/connection',
            'notRefreshable' => true
          ]
        ]
      ]
    ],
    'OAuthProvider' => [
      'controller' => 'controllers/record',
      'relationshipPanels' => [
        'accounts' => [
          'layout' => 'listForProvider',
          'selectDisabled' => true,
          'unlinkDisabled' => true
        ]
      ]
    ],
    'PasswordChangeRequest' => [
      'controller' => 'controllers/password-change-request'
    ],
    'PhoneNumber' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'exportDisabled' => true,
      'mergeDisabled' => true,
      'filterList' => [
        0 => 'orphan'
      ]
    ],
    'Portal' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'list' => 'views/portal/record/list'
      ],
      'relationshipPanels' => [
        'users' => [
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
          'layout' => 'listSmall',
          'selectPrimaryFilterName' => 'activePortal'
        ],
        'authenticationProvider' => [
          'createDisabled' => true
        ]
      ],
      'searchPanelDisabled' => true
    ],
    'PortalRole' => [
      'recordViews' => [
        'detail' => 'views/portal-role/record/detail',
        'edit' => 'views/portal-role/record/edit',
        'editQuick' => 'views/portal-role/record/edit',
        'list' => 'views/portal-role/record/list'
      ],
      'relationshipPanels' => [
        'users' => [
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only'
        ]
      ],
      'views' => [
        'list' => 'views/portal-role/list'
      ]
    ],
    'PortalUser' => [
      'controller' => 'controllers/portal-user',
      'views' => [
        'detail' => 'views/user/detail',
        'list' => 'views/portal-user/list'
      ],
      'recordViews' => [
        'list' => 'views/user/record/list',
        'detail' => 'views/user/record/detail',
        'edit' => 'views/user/record/edit',
        'detailSmall' => 'views/user/record/detail-quick',
        'editSmall' => 'views/user/record/edit-quick'
      ],
      'defaultSidePanelFieldLists' => [
        'detail' => [
          0 => 'avatar',
          1 => 'createdAt',
          2 => 'lastAccess'
        ],
        'detailSmall' => [
          0 => 'avatar',
          1 => 'createdAt'
        ],
        'edit' => [
          0 => 'avatar'
        ],
        'editSmall' => [
          0 => 'avatar'
        ]
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ]
        ]
      ],
      'filterList' => [
        0 => 'activePortal'
      ],
      'boolFilterList' => [],
      'selectDefaultFilters' => [
        'filter' => 'activePortal'
      ],
      'iconClass' => 'far fa-user-circle'
    ],
    'Preferences' => [
      'recordViews' => [
        'edit' => 'views/preferences/record/edit'
      ],
      'views' => [
        'edit' => 'views/preferences/edit'
      ],
      'acl' => 'acl/preferences',
      'aclPortal' => 'acl-portal/preferences'
    ],
    'Role' => [
      'recordViews' => [
        'detail' => 'views/role/record/detail',
        'edit' => 'views/role/record/edit',
        'editQuick' => 'views/role/record/edit',
        'list' => 'views/role/record/list'
      ],
      'relationshipPanels' => [
        'users' => [
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only'
        ],
        'teams' => [
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only'
        ]
      ],
      'views' => [
        'list' => 'views/role/list'
      ]
    ],
    'ScheduledJob' => [
      'controller' => 'controllers/record',
      'relationshipPanels' => [
        'log' => [
          'readOnly' => true,
          'view' => 'views/scheduled-job/record/panels/log',
          'createDisabled' => true,
          'selectDisabled' => true,
          'viewDisabled' => true,
          'unlinkDisabled' => true
        ]
      ],
      'recordViews' => [
        'list' => 'views/scheduled-job/record/list',
        'detail' => 'views/scheduled-job/record/detail'
      ],
      'views' => [
        'list' => 'views/scheduled-job/list'
      ],
      'jobWithTargetList' => [
        0 => 'CheckEmailAccounts',
        1 => 'CheckInboundEmails'
      ],
      'dynamicLogic' => [
        'fields' => [
          'job' => [
            'readOnly' => [
              'conditionGroup' => [
                0 => [
                  'type' => 'isNotEmpty',
                  'attribute' => 'id'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'ScheduledJobLogRecord' => [
      'controller' => 'controllers/record'
    ],
    'Stream' => [
      'controller' => 'controllers/stream',
      'iconClass' => 'fas fa-rss'
    ],
    'Team' => [
      'acl' => 'acl/team',
      'defaultSidePanel' => [
        'edit' => false,
        'editSmall' => false
      ],
      'mergeDisabled' => true,
      'massUpdateDisabled' => true,
      'defaultSidePanelFieldLists' => [
        'detail' => [
          0 => 'createdAt'
        ]
      ],
      'relationshipPanels' => [
        'users' => [
          'createDisabled' => true,
          'editDisabled' => true,
          'removeDisabled' => true,
          'layout' => 'listForTeam',
          'selectPrimaryFilterName' => 'active',
          'filterList' => [
            0 => 'all',
            1 => 'active'
          ],
          'rowActionList' => [
            0 => 'changeTeamPosition'
          ],
          'selectMandatoryAttributeList' => [
            0 => 'teamRole'
          ]
        ]
      ],
      'recordViews' => [
        'detail' => 'views/team/record/detail',
        'edit' => 'views/team/record/edit',
        'list' => 'views/team/record/list'
      ],
      'modalViews' => [
        'detail' => 'views/team/modals/detail'
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'iconClass' => 'fas fa-users'
    ],
    'Template' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'detail' => 'views/template/record/detail',
        'edit' => 'views/template/record/edit'
      ],
      'mergeDisabled' => true,
      'filterList' => [
        0 => 'active'
      ],
      'selectDefaultFilters' => [
        'filter' => 'active'
      ],
      'iconClass' => 'fas fa-file-pdf'
    ],
    'User' => [
      'controller' => 'controllers/user',
      'model' => 'models/user',
      'acl' => 'acl/user',
      'views' => [
        'detail' => 'views/user/detail',
        'list' => 'views/user/list'
      ],
      'recordViews' => [
        'detail' => 'views/user/record/detail',
        'detailSmall' => 'views/user/record/detail-quick',
        'edit' => 'views/user/record/edit',
        'editSmall' => 'views/user/record/edit-quick',
        'list' => 'views/user/record/list'
      ],
      'modalViews' => [
        'selectFollowers' => 'views/user/modals/select-followers',
        'detail' => 'views/user/modals/detail',
        'massUpdate' => 'views/user/modals/mass-update'
      ],
      'rowActionDefs' => [
        'changeTeamPosition' => [
          'labelTranslation' => 'User.actions.changePosition',
          'handler' => 'handlers/user/change-team-position-row-action',
          'groupIndex' => 3
        ]
      ],
      'defaultSidePanel' => [
        'detail' => [
          'name' => 'default',
          'label' => false,
          'view' => 'views/user/record/panels/default-side',
          'isForm' => true
        ],
        'detailSmall' => [
          'name' => 'default',
          'label' => false,
          'view' => 'views/user/record/panels/default-side',
          'isForm' => true
        ],
        'edit' => [
          'name' => 'default',
          'label' => false,
          'view' => 'views/user/record/panels/default-side',
          'isForm' => true
        ],
        'editSmall' => [
          'name' => 'default',
          'label' => false,
          'view' => 'views/user/record/panels/default-side',
          'isForm' => true
        ]
      ],
      'defaultSidePanelFieldLists' => [
        'detail' => [
          0 => 'avatar',
          1 => 'createdAt',
          2 => 'lastAccess',
          3 => 'auth2FA'
        ],
        'detailSmall' => [
          0 => 'avatar',
          1 => 'lastAccess'
        ],
        'edit' => [
          0 => 'avatar'
        ],
        'editSmall' => [
          0 => 'avatar'
        ]
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks',
            'view' => 'crm:views/user/record/panels/tasks'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks',
            'view' => 'crm:views/user/record/panels/tasks'
          ]
        ]
      ],
      'relationshipPanels' => [
        'targetLists' => [
          'create' => false,
          'rowActionsView' => 'crm:views/record/row-actions/relationship-target',
          'layout' => 'listForTarget',
          'view' => 'crm:views/record/panels/target-lists'
        ],
        'contact' => [
          'selectHandler' => 'handlers/user/select-contact'
        ]
      ],
      'layoutDefaultSidePanelDisabled' => true,
      'filterList' => [
        0 => 'active'
      ],
      'boolFilterList' => [
        0 => 'onlyMyTeam'
      ],
      'selectDefaultFilters' => [
        'filter' => 'active'
      ],
      'selectRecords' => [
        'orderBy' => 'userNameOwnFirst'
      ],
      'iconClass' => 'fas fa-user-circle'
    ],
    'Webhook' => [
      'controller' => 'controllers/record',
      'inlineEditDisabled' => true,
      'recordViews' => [
        'list' => 'views/webhook/record/list'
      ],
      'menu' => [
        'list' => [
          'dropdown' => [
            0 => [
              'labelTranslation' => 'Global.scopeNamesPlural.WebhookQueueItem',
              'link' => '#WebhookQueueItem',
              'aclScope' => 'WebhookQueueItem'
            ],
            1 => [
              'labelTranslation' => 'Global.scopeNamesPlural.WebhookEventQueueItem',
              'link' => '#WebhookEventQueueItem',
              'aclScope' => 'WebhookEventQueueItem'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'queueItems' => [
          'unlinkDisabled' => true,
          'createDisabled' => true,
          'selectDisabled' => true,
          'layout' => 'listForWebhook'
        ]
      ]
    ],
    'WebhookEventQueueItem' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'mergeDisabled' => true,
      'exportDisabled' => true,
      'textFilterDisabled' => true,
      'forceListViewSettings' => true,
      'menu' => [
        'list' => [
          'dropdown' => [
            0 => [
              'labelTranslation' => 'Global.scopeNamesPlural.Webhook',
              'link' => '#Webhook',
              'aclScope' => 'Webhook'
            ]
          ]
        ]
      ]
    ],
    'WebhookQueueItem' => [
      'controller' => 'controllers/record',
      'createDisabled' => true,
      'editDisabled' => true,
      'mergeDisabled' => true,
      'exportDisabled' => true,
      'textFilterDisabled' => true,
      'menu' => [
        'list' => [
          'dropdown' => [
            0 => [
              'labelTranslation' => 'Global.scopeNamesPlural.Webhook',
              'link' => '#Webhook',
              'aclScope' => 'Webhook'
            ]
          ]
        ]
      ]
    ],
    'WorkingTimeCalendar' => [
      'controller' => 'controllers/record',
      'searchPanelDisabled' => true,
      'massUpdateDisabled' => true,
      'mergeDisabled' => true,
      'massRemoveDisabled' => true,
      'iconClass' => 'fas fa-calendar-week',
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'name' => 'ranges',
              'labelTranslation' => 'WorkingTimeCalendar.links.ranges',
              'link' => '#WorkingTimeRange'
            ]
          ]
        ]
      ]
    ],
    'WorkingTimeRange' => [
      'controller' => 'controllers/record',
      'viewSetupHandlers' => [
        'record/edit' => [
          0 => 'handlers/working-time-range'
        ]
      ],
      'mergeDisabled' => true,
      'massUpdateDisabled' => true,
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'name' => 'calendars',
              'label' => 'Calendars',
              'link' => '#WorkingTimeCalendar'
            ]
          ]
        ]
      ],
      'filterList' => [
        0 => 'actual'
      ]
    ],
    'Account' => [
      'controller' => 'controllers/record',
      'aclPortal' => 'crm:acl-portal/account',
      'views' => [
        'detail' => 'crm:views/account/detail'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'disabled' => true
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history',
            'disabled' => true
          ]
        ]
      ],
      'relationshipPanels' => [
        'contacts' => [
          'filterList' => [
            0 => 'all',
            1 => 'accountActive'
          ],
          'layout' => 'listForAccount',
          'orderBy' => 'name',
          'createAttributeMap' => [
            'billingAddressCity' => 'addressCity',
            'billingAddressStreet' => 'addressStreet',
            'billingAddressPostalCode' => 'addressPostalCode',
            'billingAddressState' => 'addressState',
            'billingAddressCountry' => 'addressCountry',
            'id' => 'accountId',
            'name' => 'accountName'
          ]
        ],
        'opportunities' => [
          'layout' => 'listForAccount'
        ],
        'campaignLogRecords' => [
          'rowActionsView' => 'views/record/row-actions/empty',
          'select' => false,
          'create' => false
        ],
        'targetLists' => [
          'rowActionsView' => 'crm:views/record/row-actions/relationship-target',
          'layout' => 'listForTarget',
          'view' => 'crm:views/record/panels/target-lists'
        ],
        'cases' => [
          'layout' => 'listForAccount'
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'recentlyCreated'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'additionalLayouts' => [
        'detailConvert' => [
          'type' => 'detail'
        ]
      ],
      'color' => '#edc755',
      'iconClass' => 'fas fa-building'
    ],
    'Activities' => [
      'controller' => 'crm:controllers/activities'
    ],
    'Calendar' => [
      'colors' => [
        'Meeting' => '#558BBD',
        'Call' => '#CF605D',
        'Task' => '#70c173'
      ],
      'scopeList' => [
        0 => 'Meeting',
        1 => 'Call',
        2 => 'Task'
      ],
      'modeList' => [
        0 => 'month',
        1 => 'agendaWeek',
        2 => 'timeline',
        3 => 'agendaDay'
      ],
      'sharedViewModeList' => [
        0 => 'basicWeek',
        1 => 'month',
        2 => 'basicDay'
      ],
      'additionalColorList' => [
        0 => '#AB78AD',
        1 => '#CC9B45'
      ],
      'iconClass' => 'far fa-calendar-alt',
      'slotDuration' => 30
    ],
    'Call' => [
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/call',
      'views' => [
        'detail' => 'crm:views/call/detail'
      ],
      'recordViews' => [
        'list' => 'crm:views/call/record/list',
        'detail' => 'crm:views/call/record/detail',
        'editSmall' => 'crm:views/call/record/edit-small'
      ],
      'modalViews' => [
        'detail' => 'crm:views/meeting/modals/detail'
      ],
      'viewSetupHandlers' => [
        'record/detail' => [
          0 => 'crm:handlers/event/reminders-handler'
        ],
        'record/edit' => [
          0 => 'crm:handlers/event/reminders-handler'
        ]
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'editSmall' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'disabled' => false,
            'order' => 3
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'disabled' => false,
            'order' => 1
          ]
        ],
        'editSmall' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'disabled' => false,
            'order' => 1
          ]
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'planned'
        ],
        1 => [
          'name' => 'held',
          'style' => 'success'
        ],
        2 => [
          'name' => 'todays'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'activityDefs' => [
        'link' => 'calls',
        'activitiesCreate' => true,
        'historyCreate' => true
      ],
      'forcePatchAttributeDependencyMap' => [
        'dateEnd' => [
          0 => 'dateStart'
        ],
        'dateEndDate' => [
          0 => 'dateStartDate'
        ]
      ],
      'relationshipPanels' => [
        'contacts' => [
          'selectHandler' => 'handlers/select-related/same-account-many'
        ]
      ],
      'additionalLayouts' => [
        'bottomPanelsEditSmall' => [
          'type' => 'bottomPanelsEditSmall'
        ]
      ],
      'iconClass' => 'fas fa-phone'
    ],
    'Campaign' => [
      'controller' => 'controllers/record',
      'menu' => [
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Target Lists',
              'link' => '#TargetList',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'TargetList'
            ]
          ],
          'dropdown' => [
            0 => [
              'label' => 'Mass Emails',
              'link' => '#MassEmail',
              'acl' => 'read',
              'aclScope' => 'MassEmail'
            ],
            1 => [
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate'
            ],
            2 => [
              'label' => 'Tracking URLs',
              'labelTranslation' => 'Campaign.links.trackingUrls',
              'link' => '#CampaignTrackingUrl',
              'acl' => 'read',
              'aclScope' => 'CampaignTrackingUrl'
            ]
          ]
        ]
      ],
      'recordViews' => [
        'detail' => 'crm:views/campaign/record/detail'
      ],
      'views' => [
        'detail' => 'crm:views/campaign/detail'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'statistics',
            'label' => 'Statistics',
            'view' => 'crm:views/campaign/record/panels/campaign-stats',
            'hidden' => false,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ]
      ],
      'relationshipPanels' => [
        'campaignLogRecords' => [
          'view' => 'crm:views/campaign/record/panels/campaign-log-records',
          'layout' => 'listForCampaign',
          'rowActionsView' => 'views/record/row-actions/remove-only',
          'selectDisabled' => false,
          'createDisabled' => true
        ],
        'massEmails' => [
          'createAttributeMap' => [
            'targetListsIds' => 'targetListsIds',
            'targetListsNames' => 'targetListsNames',
            'excludingTargetListsIds' => 'excludingTargetListsIds',
            'excludingTargetListsNames' => 'excludingTargetListsNames'
          ],
          'createHandler' => 'crm:handlers/campaign/mass-emails-create',
          'selectDisabled' => true,
          'recordListView' => 'crm:views/mass-email/record/list-for-campaign',
          'rowActionsView' => 'crm:views/mass-email/record/row-actions/for-campaign'
        ],
        'trackingUrls' => [
          'selectDisabled' => true,
          'rowActionsView' => 'views/record/row-actions/relationship-no-unlink'
        ]
      ],
      'filterList' => [
        0 => 'active'
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'iconClass' => 'fas fa-chart-line'
    ],
    'CampaignLogRecord' => [
      'acl' => 'crm:acl/campaign-tracking-url'
    ],
    'CampaignTrackingUrl' => [
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/campaign-tracking-url',
      'recordViews' => [
        'edit' => 'crm:views/campaign-tracking-url/record/edit',
        'editQuick' => 'crm:views/campaign-tracking-url/record/edit-small'
      ],
      'defaultSidePanel' => [
        'edit' => false,
        'editSmall' => false
      ]
    ],
    'Case' => [
      'controller' => 'controllers/record',
      'recordViews' => [
        'detail' => 'crm:views/case/record/detail'
      ],
      'detailActionList' => [
        0 => [
          'name' => 'close',
          'label' => 'Close',
          'handler' => 'crm:handlers/case/detail-actions',
          'actionFunction' => 'close',
          'checkVisibilityFunction' => 'isCloseAvailable'
        ],
        1 => [
          'name' => 'reject',
          'label' => 'Reject',
          'handler' => 'crm:handlers/case/detail-actions',
          'actionFunction' => 'reject',
          'checkVisibilityFunction' => 'isRejectAvailable'
        ]
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'view' => 'crm:views/case/record/panels/activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'view' => 'crm:views/case/record/panels/activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'view' => 'crm:views/case/record/panels/activities',
            'disabled' => true
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history',
            'disabled' => true
          ]
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'open'
        ],
        1 => [
          'name' => 'closed',
          'style' => 'success'
        ]
      ],
      'relationshipPanels' => [
        'articles' => [
          'createDisabled' => true,
          'editDisabled' => true,
          'removeDisabled' => true,
          'rowActionList' => [
            0 => 'sendInEmail'
          ]
        ],
        'contacts' => [
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName'
          ],
          'selectHandler' => 'handlers/select-related/same-account-many'
        ],
        'contact' => [
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName'
          ],
          'selectHandler' => 'handlers/select-related/same-account-many'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'selectDefaultFilters' => [
        'filter' => 'open'
      ],
      'allowInternalNotes' => true,
      'additionalLayouts' => [
        'detailPortal' => [
          'type' => 'detail'
        ],
        'detailSmallPortal' => [
          'type' => 'detail'
        ],
        'listPortal' => [
          'type' => 'list'
        ],
        'listForAccount' => [
          'type' => 'listSmall'
        ],
        'listForContact' => [
          'type' => 'listSmall'
        ]
      ],
      'iconClass' => 'fas fa-briefcase'
    ],
    'Contact' => [
      'controller' => 'controllers/record',
      'aclPortal' => 'crm:acl-portal/contact',
      'views' => [
        'detail' => 'crm:views/contact/detail'
      ],
      'recordViews' => [
        'detail' => 'crm:views/contact/record/detail',
        'detailQuick' => 'crm:views/contact/record/detail-small'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'disabled' => true
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history',
            'disabled' => true
          ]
        ]
      ],
      'relationshipPanels' => [
        'campaignLogRecords' => [
          'rowActionsView' => 'views/record/row-actions/empty',
          'select' => false,
          'create' => false
        ],
        'opportunities' => [
          'layout' => 'listForContact',
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName',
            'id' => 'contactId',
            'name' => 'contactName'
          ],
          'selectHandler' => 'handlers/select-related/same-account'
        ],
        'cases' => [
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName',
            'id' => 'contactId',
            'name' => 'contactName'
          ],
          'selectHandler' => 'handlers/select-related/same-account',
          'layout' => 'listForContact'
        ],
        'targetLists' => [
          'create' => false,
          'rowActionsView' => 'crm:views/record/row-actions/relationship-target',
          'layout' => 'listForTarget',
          'view' => 'crm:views/record/panels/target-lists'
        ],
        'meetings' => [
          'createHandler' => 'handlers/create-related/set-parent'
        ],
        'calls' => [
          'createHandler' => 'handlers/create-related/set-parent'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'additionalLayouts' => [
        'detailConvert' => [
          'type' => 'detail'
        ],
        'listForAccount' => [
          'type' => 'listSmall'
        ]
      ],
      'filterList' => [
        0 => 'portalUsers'
      ],
      'color' => '#a4c5e0',
      'iconClass' => 'fas fa-id-badge'
    ],
    'Document' => [
      'aclPortal' => 'crm:acl-portal/document',
      'controller' => 'controllers/record',
      'views' => [
        'list' => 'crm:views/document/list'
      ],
      'modalViews' => [
        'select' => 'crm:views/document/modals/select-records'
      ],
      'viewSetupHandlers' => [
        'list' => [
          0 => 'crm:view-setup-handlers/document/record-list-drag-n-drop'
        ]
      ],
      'filterList' => [
        0 => 'active',
        1 => 'draft'
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'selectDefaultFilters' => [
        'filter' => 'active'
      ],
      'iconClass' => 'far fa-file-alt'
    ],
    'DocumentFolder' => [
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => [
        'listTree' => [
          'buttons' => [
            0 => [
              'label' => 'List View',
              'link' => '#DocumentFolder/list',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Documents',
              'link' => '#Document',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'Document'
            ]
          ]
        ],
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Tree View',
              'link' => '#DocumentFolder',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Documents',
              'link' => '#Document',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'Document'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'children' => [
          'selectDisabled' => true,
          'unlinkDisabled' => true
        ]
      ]
    ],
    'EmailQueueItem' => [
      'controller' => 'controllers/record',
      'views' => [
        'list' => 'crm:views/email-queue-item/list'
      ],
      'recordViews' => [
        'list' => 'crm:views/email-queue-item/record/list'
      ],
      'createDisabled' => true,
      'mergeDisabled' => true,
      'massUpdateDisabled' => true
    ],
    'KnowledgeBaseArticle' => [
      'controller' => 'controllers/record',
      'views' => [
        'list' => 'crm:views/knowledge-base-article/list'
      ],
      'recordViews' => [
        'editQuick' => 'crm:views/knowledge-base-article/record/edit-quick',
        'detailQuick' => 'crm:views/knowledge-base-article/record/detail-quick',
        'detail' => 'crm:views/knowledge-base-article/record/detail',
        'edit' => 'crm:views/knowledge-base-article/record/edit',
        'list' => 'crm:views/knowledge-base-article/record/list'
      ],
      'modalViews' => [
        'select' => 'crm:views/knowledge-base-article/modals/select-records'
      ],
      'rowActionDefs' => [
        'moveToTop' => [
          'label' => 'Move to Top',
          'handler' => 'crm:handlers/knowledge-base-article/move',
          'acl' => 'edit'
        ],
        'moveUp' => [
          'label' => 'Move Up',
          'handler' => 'crm:handlers/knowledge-base-article/move',
          'acl' => 'edit'
        ],
        'moveDown' => [
          'label' => 'Move Down',
          'handler' => 'crm:handlers/knowledge-base-article/move',
          'acl' => 'edit'
        ],
        'moveToBottom' => [
          'labelTranslation' => 'KnowledgeBaseArticle.labels.Move to Bottom',
          'handler' => 'crm:handlers/knowledge-base-article/move',
          'acl' => 'edit'
        ],
        'sendInEmail' => [
          'label' => 'Send in Email',
          'handler' => 'crm:handlers/knowledge-base-article/send-in-email'
        ]
      ],
      'rowActionList' => [
        0 => 'moveToTop',
        1 => 'moveUp',
        2 => 'moveDown',
        3 => 'moveToBottom'
      ],
      'filterList' => [
        0 => [
          'name' => 'published',
          'accessDataList' => [
            0 => [
              'inPortalDisabled' => true
            ]
          ]
        ]
      ],
      'boolFilterList' => [
        0 => [
          'name' => 'onlyMy',
          'accessDataList' => [
            0 => [
              'inPortalDisabled' => true
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'cases' => [
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-view-and-unlink'
        ]
      ],
      'additionalLayouts' => [
        'detailPortal' => [
          'type' => 'detail'
        ],
        'detailSmallPortal' => [
          'type' => 'detail'
        ],
        'listPortal' => [
          'type' => 'list'
        ]
      ],
      'iconClass' => 'fas fa-book'
    ],
    'KnowledgeBaseCategory' => [
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => [
        'listTree' => [
          'buttons' => [
            0 => [
              'label' => 'List View',
              'link' => '#KnowledgeBaseCategory/list',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Articles',
              'link' => '#KnowledgeBaseArticle',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'KnowledgeBaseArticle'
            ]
          ]
        ],
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Tree View',
              'link' => '#KnowledgeBaseCategory',
              'acl' => 'read',
              'style' => 'default'
            ],
            1 => [
              'label' => 'Articles',
              'link' => '#KnowledgeBaseArticle',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'KnowledgeBaseArticle'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'children' => [
          'selectDisabled' => true,
          'unlinkDisabled' => true
        ]
      ]
    ],
    'Lead' => [
      'controller' => 'crm:controllers/lead',
      'views' => [
        'detail' => 'crm:views/lead/detail'
      ],
      'recordViews' => [
        'detail' => 'crm:views/lead/record/detail'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
            'style' => 'success',
            'isForm' => true
          ],
          1 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          2 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          3 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
            'style' => 'success',
            'isForm' => true
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
            'style' => 'success',
            'isForm' => true
          ],
          1 => [
            'name' => 'activities',
            'reference' => 'activities'
          ],
          2 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          3 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'editSmall' => [
          0 => [
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
            'style' => 'success',
            'isForm' => true
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'disabled' => true
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history',
            'disabled' => true
          ]
        ]
      ],
      'relationshipPanels' => [
        'campaignLogRecords' => [
          'rowActionsView' => 'views/record/row-actions/empty',
          'select' => false,
          'create' => false
        ],
        'targetLists' => [
          'create' => false,
          'rowActionsView' => 'crm:views/record/row-actions/relationship-target',
          'layout' => 'listForTarget',
          'view' => 'crm:views/record/panels/target-lists'
        ],
        'meetings' => [
          'createHandler' => 'handlers/create-related/set-parent'
        ],
        'calls' => [
          'createHandler' => 'handlers/create-related/set-parent'
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'actual'
        ],
        1 => [
          'name' => 'converted',
          'style' => 'success'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'dynamicLogic' => [
        'fields' => [
          'name' => [
            'required' => [
              'conditionGroup' => [
                0 => [
                  'type' => 'isEmpty',
                  'attribute' => 'accountName'
                ],
                1 => [
                  'type' => 'isEmpty',
                  'attribute' => 'emailAddress'
                ],
                2 => [
                  'type' => 'isEmpty',
                  'attribute' => 'phoneNumber'
                ]
              ]
            ]
          ],
          'convertedAt' => [
            'visible' => [
              'conditionGroup' => [
                0 => [
                  'type' => 'and',
                  'value' => [
                    0 => [
                      'type' => 'equals',
                      'attribute' => 'status',
                      'value' => 'Converted'
                    ],
                    1 => [
                      'type' => 'isNotEmpty',
                      'attribute' => 'convertedAt'
                    ]
                  ]
                ]
              ]
            ]
          ]
        ],
        'panels' => [
          'convertedTo' => [
            'visible' => [
              'conditionGroup' => [
                0 => [
                  'type' => 'equals',
                  'attribute' => 'status',
                  'value' => 'Converted'
                ]
              ]
            ]
          ]
        ]
      ],
      'color' => '#d6a2c9',
      'iconClass' => 'fas fa-address-card'
    ],
    'MassEmail' => [
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/mass-email',
      'recordViews' => [
        'detail' => 'crm:views/mass-email/record/detail',
        'edit' => 'crm:views/mass-email/record/edit',
        'editQuick' => 'crm:views/mass-email/record/edit-small'
      ],
      'views' => [
        'detail' => 'crm:views/mass-email/detail'
      ],
      'defaultSidePanel' => [
        'edit' => false,
        'editSmall' => false
      ],
      'menu' => [
        'list' => [
          'dropdown' => [
            0 => [
              'labelTranslation' => 'Global.scopeNamesPlural.EmailQueueItem',
              'link' => '#EmailQueueItem',
              'accessDataList' => [
                0 => [
                  'isAdminOnly' => true
                ]
              ]
            ]
          ]
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'actual'
        ],
        1 => [
          'name' => 'complete',
          'style' => 'success'
        ]
      ],
      'relationshipPanels' => [
        'queueItems' => [
          'unlinkDisabled' => true,
          'viewDisabled' => true,
          'editDisabled' => true
        ]
      ]
    ],
    'Meeting' => [
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/meeting',
      'views' => [
        'detail' => 'crm:views/meeting/detail'
      ],
      'recordViews' => [
        'list' => 'crm:views/meeting/record/list',
        'detail' => 'crm:views/meeting/record/detail',
        'editSmall' => 'crm:views/meeting/record/edit-small'
      ],
      'modalViews' => [
        'detail' => 'crm:views/meeting/modals/detail'
      ],
      'viewSetupHandlers' => [
        'record/detail' => [
          0 => 'crm:handlers/event/reminders-handler'
        ],
        'record/edit' => [
          0 => 'crm:handlers/event/reminders-handler'
        ]
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'options' => [
              'fieldList' => [
                0 => 'users',
                1 => 'contacts',
                2 => 'leads'
              ]
            ],
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ],
        'editSmall' => [
          0 => [
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
            'isForm' => true,
            'notRefreshable' => true
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'order' => 3
          ]
        ],
        'edit' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'order' => 1
          ]
        ],
        'editSmall' => [
          0 => [
            'name' => 'scheduler',
            'label' => 'Scheduler',
            'view' => 'crm:views/meeting/record/panels/scheduler',
            'order' => 1
          ]
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'planned'
        ],
        1 => [
          'name' => 'held',
          'style' => 'success'
        ],
        2 => [
          'name' => 'todays'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'activityDefs' => [
        'link' => 'meetings',
        'activitiesCreate' => true,
        'historyCreate' => true
      ],
      'forcePatchAttributeDependencyMap' => [
        'dateEnd' => [
          0 => 'dateStart'
        ],
        'dateEndDate' => [
          0 => 'dateStartDate'
        ]
      ],
      'relationshipPanels' => [
        'contacts' => [
          'selectHandler' => 'handlers/select-related/same-account-many'
        ]
      ],
      'additionalLayouts' => [
        'bottomPanelsEditSmall' => [
          'type' => 'bottomPanelsEditSmall'
        ]
      ],
      'dynamicLogic' => [
        'fields' => [
          'duration' => [
            'readOnly' => [
              'conditionGroup' => [
                0 => [
                  'type' => 'isTrue',
                  'attribute' => 'isAllDay'
                ]
              ]
            ]
          ]
        ]
      ],
      'iconClass' => 'fas fa-calendar-check'
    ],
    'Opportunity' => [
      'controller' => 'controllers/record',
      'modelDefaultsPreparator' => 'crm:handlers/opportunity/defaults-preparator',
      'views' => [
        'detail' => 'crm:views/opportunity/detail'
      ],
      'recordViews' => [
        'edit' => 'crm:views/opportunity/record/edit',
        'editSmall' => 'crm:views/opportunity/record/edit-small',
        'list' => 'crm:views/opportunity/record/list',
        'kanban' => 'crm:views/opportunity/record/kanban'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'view' => 'crm:views/opportunity/record/panels/activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ],
        'detailSmall' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'view' => 'crm:views/opportunity/record/panels/activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history'
          ],
          2 => [
            'name' => 'tasks',
            'reference' => 'tasks'
          ]
        ]
      ],
      'bottomPanels' => [
        'detail' => [
          0 => [
            'name' => 'activities',
            'reference' => 'activities',
            'disabled' => true,
            'view' => 'crm:views/opportunity/record/panels/activities'
          ],
          1 => [
            'name' => 'history',
            'reference' => 'history',
            'disabled' => true
          ]
        ]
      ],
      'filterList' => [
        0 => [
          'name' => 'open'
        ],
        1 => [
          'name' => 'won',
          'style' => 'success'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'additionalLayouts' => [
        'detailConvert' => [
          'type' => 'detail'
        ],
        'listForAccount' => [
          'type' => 'listSmall'
        ],
        'listForContact' => [
          'type' => 'listSmall'
        ]
      ],
      'kanbanViewMode' => true,
      'relationshipPanels' => [
        'contacts' => [
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName'
          ],
          'createHandler' => 'crm:handlers/opportunity/contacts-create',
          'selectHandler' => 'handlers/select-related/same-account-many'
        ],
        'contact' => [
          'createAttributeMap' => [
            'accountId' => 'accountId',
            'accountName' => 'accountName'
          ],
          'selectHandler' => 'handlers/select-related/same-account-many'
        ],
        'documents' => [
          'selectHandler' => 'handlers/select-related/same-account-many'
        ]
      ],
      'color' => '#9fc77e',
      'iconClass' => 'fas fa-dollar-sign'
    ],
    'TargetList' => [
      'controller' => 'controllers/record',
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'sidePanels' => [
        'detail' => [
          0 => [
            'name' => 'optedOut',
            'label' => 'Opted Out',
            'view' => 'crm:views/target-list/record/panels/opted-out'
          ]
        ]
      ],
      'views' => [
        'list' => 'views/list-with-categories'
      ],
      'recordViews' => [
        'detail' => 'crm:views/target-list/record/detail'
      ],
      'modalViews' => [
        'select' => 'views/modals/select-records-with-categories'
      ],
      'relationshipPanels' => [
        'contacts' => [
          'actionList' => [
            0 => [
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => [
                'link' => 'contacts'
              ]
            ]
          ],
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
          'removeDisabled' => true,
          'massSelect' => true
        ],
        'leads' => [
          'actionList' => [
            0 => [
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => [
                'link' => 'leads'
              ]
            ]
          ],
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
          'removeDisabled' => true,
          'massSelect' => true
        ],
        'accounts' => [
          'actionList' => [
            0 => [
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => [
                'link' => 'accounts'
              ]
            ]
          ],
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
          'removeDisabled' => true,
          'massSelect' => true
        ],
        'users' => [
          'create' => false,
          'actionList' => [
            0 => [
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => [
                'link' => 'users'
              ]
            ]
          ],
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
          'removeDisabled' => true,
          'massSelect' => true
        ]
      ],
      'iconClass' => 'fas fa-crosshairs'
    ],
    'TargetListCategory' => [
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => [
        'listTree' => [
          'buttons' => [
            0 => [
              'label' => 'List View',
              'link' => '#TargetListCategory/list',
              'acl' => 'read'
            ],
            1 => [
              'labelTranslation' => 'Global.scopeNamesPlural.TargetList',
              'link' => '#TargetList',
              'acl' => 'read',
              'aclScope' => 'TargetList'
            ]
          ]
        ],
        'list' => [
          'buttons' => [
            0 => [
              'label' => 'Tree View',
              'link' => '#TargetListCategory',
              'acl' => 'read'
            ],
            1 => [
              'labelTranslation' => 'Global.scopeNamesPlural.TargetList',
              'link' => '#TargetList',
              'acl' => 'read',
              'aclScope' => 'TargetList'
            ]
          ]
        ]
      ],
      'relationshipPanels' => [
        'children' => [
          'selectDisabled' => true,
          'unlinkDisabled' => true
        ]
      ]
    ],
    'Task' => [
      'controller' => 'crm:controllers/task',
      'recordViews' => [
        'list' => 'crm:views/task/record/list',
        'detail' => 'crm:views/task/record/detail'
      ],
      'views' => [
        'list' => 'crm:views/task/list',
        'detail' => 'crm:views/task/detail'
      ],
      'modalViews' => [
        'detail' => 'crm:views/task/modals/detail'
      ],
      'viewSetupHandlers' => [
        'record/detail' => [
          0 => 'crm:handlers/task/reminders-handler'
        ],
        'record/edit' => [
          0 => 'crm:handlers/task/reminders-handler'
        ]
      ],
      'menu' => [
        'detail' => [
          'buttons' => [
            0 => [
              'label' => 'Complete',
              'name' => 'setCompletedMain',
              'iconHtml' => '<span class="fas fa-check fa-sm"></span>',
              'acl' => 'edit',
              'handler' => 'crm:handlers/task/menu',
              'actionFunction' => 'complete',
              'checkVisibilityFunction' => 'isCompleteAvailable'
            ]
          ]
        ]
      ],
      'modalDetailActionList' => [
        0 => [
          'name' => 'complete',
          'label' => 'Complete',
          'acl' => 'edit',
          'handler' => 'crm:handlers/task/detail-actions',
          'actionFunction' => 'complete',
          'checkVisibilityFunction' => 'isCompleteAvailable'
        ]
      ],
      'filterList' => [
        0 => 'actual',
        1 => [
          'name' => 'completed',
          'style' => 'success'
        ],
        2 => [
          'name' => 'todays'
        ],
        3 => [
          'name' => 'overdue',
          'style' => 'danger'
        ],
        4 => [
          'name' => 'deferred'
        ]
      ],
      'boolFilterList' => [
        0 => 'onlyMy'
      ],
      'iconClass' => 'fas fa-tasks',
      'kanbanViewMode' => true
    ]
  ],
  'dashlets' => [
    'Emails' => [
      'view' => 'views/dashlets/emails',
      'aclScope' => 'Email',
      'entityType' => 'Email',
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'folder' => [
            'type' => 'enum',
            'view' => 'views/dashlets/fields/emails/folder'
          ]
        ],
        'defaults' => [
          'orderBy' => 'dateSent',
          'order' => 'desc',
          'displayRecords' => 5,
          'folder' => NULL,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'subject',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'dateSent',
                  'view' => 'views/fields/datetime-short'
                ],
                1 => [
                  'name' => 'personStringData',
                  'view' => 'views/email/fields/person-string-data-for-expanded'
                ]
              ]
            ]
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'folder'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ],
    'Iframe' => [
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar'
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'url' => [
            'type' => 'url',
            'required' => true
          ]
        ],
        'defaults' => [
          'autorefreshInterval' => 0
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'url'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'Memo' => [
      'view' => 'views/dashlets/memo',
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar'
          ],
          'text' => [
            'type' => 'text'
          ]
        ],
        'defaults' => [],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'text'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'Records' => [
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 40
          ],
          'entityType' => [
            'type' => 'enum',
            'view' => 'views/dashlets/fields/records/entity-type',
            'translation' => 'Global.scopeNames'
          ],
          'primaryFilter' => [
            'type' => 'enum',
            'view' => 'views/dashlets/fields/records/primary-filter'
          ],
          'boolFilterList' => [
            'type' => 'multiEnum',
            'view' => 'views/dashlets/fields/records/bool-filter-list'
          ],
          'sortBy' => [
            'type' => 'enum',
            'view' => 'views/dashlets/fields/records/sort-by'
          ],
          'sortDirection' => [
            'type' => 'enum',
            'view' => 'views/dashlets/fields/records/sort-direction',
            'options' => [
              0 => 'asc',
              1 => 'desc'
            ],
            'translation' => 'DashletOptions.options.sortDirection'
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ]
        ],
        'defaults' => [
          'displayRecords' => 10,
          'autorefreshInterval' => 0.5,
          'expandedLayout' => [
            'rows' => []
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'entityType'
                ],
                1 => [
                  'name' => 'displayRecords'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'primaryFilter'
                ],
                1 => [
                  'name' => 'sortBy'
                ]
              ],
              3 => [
                0 => [
                  'name' => 'boolFilterList'
                ],
                1 => [
                  'name' => 'sortDirection'
                ]
              ],
              4 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ]
    ],
    'Stream' => [
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'skipOwn' => [
            'type' => 'bool',
            'tooltip' => true
          ]
        ],
        'defaults' => [
          'displayRecords' => 10,
          'autorefreshInterval' => 0.5,
          'skipOwn' => false
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'skipOwn'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ]
    ],
    'Activities' => [
      'view' => 'crm:views/dashlets/activities',
      'options' => [
        'view' => 'crm:views/dashlets/options/activities',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'enabledScopeList' => [
            'type' => 'multiEnum',
            'translation' => 'Global.scopeNamesPlural',
            'required' => true
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'futureDays' => [
            'type' => 'int',
            'min' => 0,
            'required' => true
          ],
          'includeShared' => [
            'type' => 'bool'
          ]
        ],
        'defaults' => [
          'displayRecords' => 10,
          'autorefreshInterval' => 0.5,
          'futureDays' => 3,
          'enabledScopeList' => [
            0 => 'Meeting',
            1 => 'Call',
            2 => 'Task'
          ],
          'includeShared' => false
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'enabledScopeList'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'futureDays'
                ],
                1 => [
                  'name' => 'includeShared'
                ]
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ],
    'Calendar' => [
      'view' => 'crm:views/dashlets/calendar',
      'aclScope' => 'Calendar',
      'options' => [
        'view' => 'crm:views/dashlets/options/calendar',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'enabledScopeList' => [
            'type' => 'multiEnum',
            'translation' => 'Global.scopeNamesPlural',
            'required' => true
          ],
          'mode' => [
            'type' => 'enum',
            'options' => [
              0 => 'basicWeek',
              1 => 'agendaWeek',
              2 => 'timeline',
              3 => 'month',
              4 => 'basicDay',
              5 => 'agendaDay'
            ]
          ],
          'users' => [
            'type' => 'linkMultiple',
            'entity' => 'User',
            'view' => 'crm:views/calendar/fields/users',
            'sortable' => true
          ],
          'teams' => [
            'type' => 'linkMultiple',
            'entity' => 'Team',
            'view' => 'crm:views/calendar/fields/teams'
          ]
        ],
        'defaults' => [
          'autorefreshInterval' => 0.5,
          'mode' => 'basicWeek',
          'enabledScopeList' => [
            0 => 'Meeting',
            1 => 'Call',
            2 => 'Task'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'mode'
                ],
                1 => [
                  'name' => 'enabledScopeList'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'users'
                ],
                1 => false
              ],
              3 => [
                0 => [
                  'name' => 'teams'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ],
    'Calls' => [
      'view' => 'crm:views/dashlets/calls',
      'aclScope' => 'Call',
      'entityType' => 'Call',
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ]
        ],
        'defaults' => [
          'orderBy' => 'dateStart',
          'order' => 'asc',
          'displayRecords' => 5,
          'populateAssignedUser' => true,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'name',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'dateStart',
                  'soft' => true
                ],
                1 => [
                  'name' => 'parent'
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'planned',
            'advanced' => [
              1 => [
                'type' => 'or',
                'value' => [
                  1 => [
                    'type' => 'today',
                    'field' => 'dateStart',
                    'dateTime' => true
                  ],
                  2 => [
                    'type' => 'future',
                    'field' => 'dateEnd',
                    'dateTime' => true
                  ],
                  3 => [
                    'type' => 'lastXDays',
                    'field' => 'dateStart',
                    'value' => 5,
                    'dateTime' => true
                  ]
                ]
              ]
            ]
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => false
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ],
    'Cases' => [
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Case',
      'entityType' => 'Case',
      'options' => [
        'view' => 'views/dashlets/options/record-list',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ],
          'includeShared' => [
            'type' => 'bool'
          ]
        ],
        'defaults' => [
          'orderBy' => 'number',
          'order' => 'desc',
          'displayRecords' => 5,
          'populateAssignedUser' => true,
          'includeShared' => false,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'number'
                ],
                1 => [
                  'name' => 'name',
                  'link' => true
                ],
                2 => [
                  'name' => 'type'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'status'
                ],
                1 => [
                  'name' => 'priority',
                  'soft' => true
                ],
                2 => [
                  'name' => 'account'
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'open'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'includeShared'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ]
    ],
    'Leads' => [
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Lead',
      'entityType' => 'Lead',
      'options' => [
        'view' => 'views/dashlets/options/record-list',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ],
          'includeShared' => [
            'type' => 'bool'
          ]
        ],
        'defaults' => [
          'orderBy' => 'createdAt',
          'order' => 'desc',
          'displayRecords' => 5,
          'populateAssignedUser' => true,
          'includeShared' => false,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'name',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'status'
                ],
                1 => [
                  'name' => 'source',
                  'soft' => true,
                  'small' => true
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'actual'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'includeShared'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ]
    ],
    'Meetings' => [
      'view' => 'crm:views/dashlets/meetings',
      'aclScope' => 'Meeting',
      'entityType' => 'Meeting',
      'options' => [
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ]
        ],
        'defaults' => [
          'orderBy' => 'dateStart',
          'order' => 'asc',
          'displayRecords' => 5,
          'populateAssignedUser' => true,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'name',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'dateStart',
                  'soft' => true
                ],
                1 => [
                  'name' => 'parent'
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'planned',
            'advanced' => [
              1 => [
                'type' => 'or',
                'value' => [
                  1 => [
                    'type' => 'today',
                    'field' => 'dateStart',
                    'dateTime' => true
                  ],
                  2 => [
                    'type' => 'future',
                    'field' => 'dateEnd',
                    'dateTime' => true
                  ]
                ]
              ]
            ]
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => false
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ],
    'Opportunities' => [
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Opportunity',
      'entityType' => 'Opportunity',
      'options' => [
        'view' => 'views/dashlets/options/record-list',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ],
          'includeShared' => [
            'type' => 'bool'
          ]
        ],
        'defaults' => [
          'orderBy' => 'closeDate',
          'order' => 'asc',
          'displayRecords' => 5,
          'populateAssignedUser' => true,
          'includeShared' => false,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'name',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'stage'
                ],
                1 => [
                  'name' => 'amount'
                ],
                2 => [
                  'name' => 'closeDate',
                  'soft' => true
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'open'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'includeShared'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ]
    ],
    'OpportunitiesByLeadSource' => [
      'view' => 'crm:views/dashlets/opportunities-by-lead-source',
      'aclScope' => 'Opportunity',
      'options' => [
        'view' => 'crm:views/dashlets/options/chart',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'dateFrom' => [
            'type' => 'date',
            'required' => true
          ],
          'dateTo' => [
            'type' => 'date',
            'required' => true
          ],
          'dateFilter' => [
            'type' => 'enum',
            'options' => [
              0 => 'currentYear',
              1 => 'currentQuarter',
              2 => 'currentMonth',
              3 => 'currentFiscalYear',
              4 => 'currentFiscalQuarter',
              5 => 'ever',
              6 => 'between'
            ],
            'default' => 'currentYear',
            'translation' => 'Global.options.dateSearchRanges'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'dateFilter'
                ],
                1 => false
              ],
              2 => [
                0 => [
                  'name' => 'dateFrom'
                ],
                1 => [
                  'name' => 'dateTo'
                ]
              ]
            ]
          ]
        ],
        'defaults' => [
          'dateFilter' => 'currentYear'
        ]
      ]
    ],
    'OpportunitiesByStage' => [
      'view' => 'crm:views/dashlets/opportunities-by-stage',
      'aclScope' => 'Opportunity',
      'options' => [
        'view' => 'crm:views/dashlets/options/chart',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'dateFrom' => [
            'type' => 'date',
            'required' => true
          ],
          'dateTo' => [
            'type' => 'date',
            'required' => true
          ],
          'dateFilter' => [
            'type' => 'enum',
            'options' => [
              0 => 'currentYear',
              1 => 'currentQuarter',
              2 => 'currentMonth',
              3 => 'currentFiscalYear',
              4 => 'currentFiscalQuarter',
              5 => 'ever',
              6 => 'between'
            ],
            'default' => 'currentYear',
            'translation' => 'Global.options.dateSearchRanges'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'dateFilter'
                ],
                1 => false
              ],
              2 => [
                0 => [
                  'name' => 'dateFrom'
                ],
                1 => [
                  'name' => 'dateTo'
                ]
              ]
            ]
          ]
        ],
        'defaults' => [
          'dateFilter' => 'currentYear'
        ]
      ]
    ],
    'SalesByMonth' => [
      'view' => 'crm:views/dashlets/sales-by-month',
      'aclScope' => 'Opportunity',
      'options' => [
        'view' => 'crm:views/dashlets/options/chart',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'dateFrom' => [
            'type' => 'date',
            'required' => true
          ],
          'dateTo' => [
            'type' => 'date',
            'required' => true
          ],
          'dateFilter' => [
            'type' => 'enum',
            'options' => [
              0 => 'currentYear',
              1 => 'currentQuarter',
              2 => 'currentFiscalYear',
              3 => 'currentFiscalQuarter',
              4 => 'between'
            ],
            'default' => 'currentYear',
            'translation' => 'Global.options.dateSearchRanges'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'dateFilter'
                ],
                1 => false
              ],
              2 => [
                0 => [
                  'name' => 'dateFrom'
                ],
                1 => [
                  'name' => 'dateTo'
                ]
              ]
            ]
          ]
        ],
        'defaults' => [
          'dateFilter' => 'currentYear'
        ]
      ]
    ],
    'SalesPipeline' => [
      'view' => 'crm:views/dashlets/sales-pipeline',
      'aclScope' => 'Opportunity',
      'options' => [
        'view' => 'crm:views/dashlets/options/sales-pipeline',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'dateFrom' => [
            'type' => 'date',
            'required' => true
          ],
          'dateTo' => [
            'type' => 'date',
            'required' => true
          ],
          'dateFilter' => [
            'type' => 'enum',
            'options' => [
              0 => 'currentYear',
              1 => 'currentQuarter',
              2 => 'currentMonth',
              3 => 'currentFiscalYear',
              4 => 'currentFiscalQuarter',
              5 => 'ever',
              6 => 'between'
            ],
            'default' => 'currentYear',
            'translation' => 'Global.options.dateSearchRanges'
          ],
          'useLastStage' => [
            'type' => 'bool'
          ],
          'team' => [
            'type' => 'link',
            'entity' => 'Team',
            'view' => 'crm:views/dashlets/options/sales-pipeline/fields/team'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => false
              ],
              1 => [
                0 => [
                  'name' => 'dateFilter'
                ],
                1 => [
                  'name' => 'useLastStage'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'dateFrom'
                ],
                1 => [
                  'name' => 'dateTo'
                ]
              ],
              3 => [
                0 => [
                  'name' => 'team'
                ],
                1 => false
              ]
            ]
          ]
        ],
        'defaults' => [
          'dateFilter' => 'currentYear',
          'teamId' => NULL,
          'teamName' => NULL
        ]
      ]
    ],
    'Tasks' => [
      'view' => 'crm:views/dashlets/tasks',
      'aclScope' => 'Task',
      'entityType' => 'Task',
      'options' => [
        'view' => 'views/dashlets/options/record-list',
        'fields' => [
          'title' => [
            'type' => 'varchar',
            'required' => true
          ],
          'autorefreshInterval' => [
            'type' => 'enumFloat',
            'options' => [
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10
            ]
          ],
          'displayRecords' => [
            'type' => 'int',
            'min' => 1,
            'max' => 20
          ],
          'expandedLayout' => [
            'type' => 'base',
            'view' => 'views/dashlets/fields/records/expanded-layout'
          ],
          'includeShared' => [
            'type' => 'bool'
          ]
        ],
        'defaults' => [
          'orderBy' => 'dateUpcoming',
          'order' => 'asc',
          'displayRecords' => 5,
          'includeShared' => false,
          'expandedLayout' => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'name',
                  'link' => true
                ]
              ],
              1 => [
                0 => [
                  'name' => 'status'
                ],
                1 => [
                  'name' => 'dateEnd',
                  'soft' => true
                ],
                2 => [
                  'name' => 'parent'
                ]
              ]
            ]
          ],
          'searchData' => [
            'bool' => [
              'onlyMy' => true
            ],
            'primary' => 'actualStartingNotInFuture'
          ]
        ],
        'layout' => [
          0 => [
            'rows' => [
              0 => [
                0 => [
                  'name' => 'title'
                ],
                1 => [
                  'name' => 'autorefreshInterval'
                ]
              ],
              1 => [
                0 => [
                  'name' => 'displayRecords'
                ],
                1 => [
                  'name' => 'includeShared'
                ]
              ],
              2 => [
                0 => [
                  'name' => 'expandedLayout'
                ],
                1 => false
              ]
            ]
          ]
        ]
      ],
      'accessDataList' => [
        0 => [
          'inPortalDisabled' => true
        ]
      ]
    ]
  ],
  'entityAcl' => [
    'AppSecret' => [
      'fields' => [
        'value' => [
          'internal' => true
        ]
      ]
    ],
    'Attachment' => [
      'fields' => [
        'storage' => [
          'readOnly' => true
        ],
        'source' => [
          'readOnly' => true
        ],
        'sourceId' => [
          'readOnly' => true
        ]
      ]
    ],
    'AuthLogRecord' => [
      'fields' => [
        'username' => [
          'readOnly' => true
        ],
        'portal' => [
          'readOnly' => true
        ],
        'user' => [
          'readOnly' => true
        ],
        'ipAddress' => [
          'readOnly' => true
        ],
        'authToken' => [
          'readOnly' => true
        ],
        'isDenied' => [
          'readOnly' => true
        ],
        'denialReason' => [
          'readOnly' => true
        ],
        'microtime' => [
          'readOnly' => true
        ],
        'requestUrl' => [
          'readOnly' => true
        ],
        'requestMethod' => [
          'readOnly' => true
        ]
      ]
    ],
    'AuthToken' => [
      'fields' => [
        'hash' => [
          'forbidden' => true,
          'readOnly' => true
        ],
        'token' => [
          'forbidden' => true,
          'readOnly' => true
        ],
        'secret' => [
          'forbidden' => true,
          'readOnly' => true
        ],
        'portal' => [
          'readOnly' => true
        ],
        'user' => [
          'readOnly' => true
        ],
        'ipAddress' => [
          'readOnly' => true
        ],
        'lastAccess' => [
          'readOnly' => true
        ],
        'createdAt' => [
          'readOnly' => true
        ],
        'modifiedAt' => [
          'readOnly' => true
        ]
      ]
    ],
    'Email' => [
      'fields' => [
        'users' => [
          'readOnly' => true
        ],
        'messageId' => [
          'readOnly' => true
        ],
        'tasks' => [
          'readOnly' => true
        ]
      ],
      'links' => [
        'users' => [
          'nonAdminReadOnly' => true
        ],
        'tasks' => [
          'readOnly' => true
        ]
      ]
    ],
    'EmailAccount' => [
      'fields' => [
        'password' => [
          'internal' => true
        ],
        'smtpPassword' => [
          'internal' => true
        ],
        'imapHandler' => [
          'forbidden' => true
        ],
        'smtpHandler' => [
          'forbidden' => true
        ],
        'fetchData' => [
          'readOnly' => true
        ]
      ]
    ],
    'InboundEmail' => [
      'fields' => [
        'password' => [
          'internal' => true
        ],
        'smtpPassword' => [
          'internal' => true
        ],
        'imapHandler' => [
          'internal' => true
        ],
        'smtpHandler' => [
          'internal' => true
        ],
        'fetchData' => [
          'readOnly' => true
        ]
      ]
    ],
    'Note' => [
      'links' => [
        'teams' => [
          'readOnly' => true
        ],
        'users' => [
          'readOnly' => true
        ]
      ]
    ],
    'OAuthAccount' => [
      'fields' => [
        'accessToken' => [
          'forbidden' => true
        ],
        'refreshToken' => [
          'forbidden' => true
        ],
        'expiresAt' => [
          'forbidden' => true
        ]
      ]
    ],
    'OAuthProvider' => [
      'fields' => [
        'clientSecret' => [
          'internal' => true
        ]
      ]
    ],
    'Preferences' => [
      'fields' => [
        'data' => [
          'forbidden' => true
        ]
      ]
    ],
    'User' => [
      'fields' => [
        'userName' => [
          'nonAdminReadOnly' => true
        ],
        'apiKey' => [
          'onlyAdmin' => true,
          'readOnly' => true,
          'nonAdminReadOnly' => true
        ],
        'password' => [
          'internal' => true,
          'nonAdminReadOnly' => true
        ],
        'passwordConfirm' => [
          'internal' => true,
          'nonAdminReadOnly' => true
        ],
        'authLogRecordId' => [
          'forbidden' => true
        ],
        'authMethod' => [
          'onlyAdmin' => true
        ],
        'secretKey' => [
          'readOnly' => true,
          'onlyAdmin' => true
        ],
        'isActive' => [
          'nonAdminReadOnly' => true
        ],
        'emailAddress' => [
          'nonAdminReadOnly' => true
        ],
        'teams' => [
          'nonAdminReadOnly' => true
        ],
        'defaultTeam' => [
          'nonAdminReadOnly' => true
        ],
        'roles' => [
          'nonAdminReadOnly' => true
        ],
        'portals' => [
          'nonAdminReadOnly' => true
        ],
        'portalRoles' => [
          'nonAdminReadOnly' => true
        ],
        'contact' => [
          'nonAdminReadOnly' => true
        ],
        'workingTimeCalendar' => [
          'nonAdminReadOnly' => true
        ],
        'layoutSet' => [
          'onlyAdmin' => true
        ],
        'accounts' => [
          'nonAdminReadOnly' => true
        ],
        'type' => [
          'nonAdminReadOnly' => true
        ],
        'auth2FA' => [
          'onlyAdmin' => true
        ],
        'userData' => [
          'forbidden' => true
        ],
        'deleteId' => [
          'forbidden' => true
        ]
      ],
      'links' => [
        'teams' => [
          'nonAdminReadOnly' => true
        ],
        'roles' => [
          'onlyAdmin' => true
        ],
        'workingTimeRanges' => [
          'nonAdminReadOnly' => true
        ],
        'portalRoles' => [
          'onlyAdmin' => true
        ],
        'accounts' => [
          'onlyAdmin' => true
        ],
        'defaultTeam' => [
          'onlyAdmin' => true
        ],
        'dashboardTemplate' => [
          'onlyAdmin' => true
        ],
        'userData' => [
          'forbidden' => true
        ]
      ]
    ],
    'Webhook' => [
      'fields' => [
        'user' => [
          'onlyAdmin' => true
        ],
        'secretKey' => [
          'readOnly' => true
        ]
      ]
    ],
    'Case' => [
      'fields' => [
        'inboundEmail' => [
          'readOnly' => true
        ]
      ],
      'links' => [
        'inboundEmail' => [
          'readOnly' => true
        ],
        'collaborators' => [
          'readOnly' => true
        ]
      ]
    ],
    'Contact' => [
      'fields' => [
        'inboundEmail' => [
          'readOnly' => true
        ],
        'portalUser' => [
          'readOnly' => true
        ]
      ]
    ],
    'KnowledgeBaseArticle' => [
      'fields' => [
        'order' => [
          'readOnly' => true
        ]
      ]
    ],
    'Task' => [
      'fields' => [
        'email' => [
          'readOnly' => true
        ]
      ],
      'links' => [
        'collaborators' => [
          'readOnly' => true
        ]
      ]
    ]
  ],
  'entityDefs' => [
    'ActionHistoryRecord' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'index' => true,
          'dbType' => 'bigint'
        ],
        'targetType' => [
          'type' => 'varchar',
          'view' => 'views/action-history-record/fields/target-type',
          'translation' => 'Global.scopeNames'
        ],
        'target' => [
          'type' => 'linkParent',
          'view' => 'views/action-history-record/fields/target'
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'action' => [
          'type' => 'enum',
          'options' => [
            0 => 'read',
            1 => 'update',
            2 => 'create',
            3 => 'delete'
          ]
        ],
        'createdAt' => [
          'type' => 'datetime',
          'hasSeconds' => true
        ],
        'user' => [
          'type' => 'link',
          'view' => 'views/fields/user'
        ],
        'userType' => [
          'type' => 'foreign',
          'link' => 'user',
          'field' => 'type',
          'view' => 'views/fields/foreign-enum',
          'notStorable' => true
        ],
        'ipAddress' => [
          'type' => 'varchar',
          'maxLength' => 39
        ],
        'authToken' => [
          'type' => 'link'
        ],
        'authLogRecord' => [
          'type' => 'link'
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'target' => [
          'type' => 'belongsToParent'
        ],
        'authToken' => [
          'type' => 'belongsTo',
          'entity' => 'AuthToken',
          'foreignName' => 'id',
          'foreign' => 'actionHistoryRecords'
        ],
        'authLogRecord' => [
          'type' => 'belongsTo',
          'entity' => 'AuthLogRecord',
          'foreignName' => 'id',
          'foreign' => 'actionHistoryRecords'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'ipAddress',
          1 => 'userName'
        ],
        'countDisabled' => true,
        'sortBy' => 'number',
        'asc' => false
      ],
      'hooksDisabled' => true
    ],
    'AddressCountry' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'code' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 2,
          'tooltip' => true,
          'pattern' => '[A-Z]{2}',
          'sanitizerClassNameList' => [
            0 => 'Espo\\Classes\\FieldSanitizers\\StringUpperCase'
          ],
          'options' => [
            0 => 'AF',
            1 => 'AX',
            2 => 'AL',
            3 => 'DZ',
            4 => 'AS',
            5 => 'AD',
            6 => 'AO',
            7 => 'AI',
            8 => 'AQ',
            9 => 'AG',
            10 => 'AR',
            11 => 'AM',
            12 => 'AW',
            13 => 'AU',
            14 => 'AT',
            15 => 'AZ',
            16 => 'BS',
            17 => 'BH',
            18 => 'BD',
            19 => 'BB',
            20 => 'BY',
            21 => 'BE',
            22 => 'BZ',
            23 => 'BJ',
            24 => 'BM',
            25 => 'BT',
            26 => 'BO',
            27 => 'BA',
            28 => 'BW',
            29 => 'BV',
            30 => 'BR',
            31 => 'IO',
            32 => 'BN',
            33 => 'BG',
            34 => 'BF',
            35 => 'BI',
            36 => 'KH',
            37 => 'CM',
            38 => 'CA',
            39 => 'CV',
            40 => 'KY',
            41 => 'CF',
            42 => 'TD',
            43 => 'CL',
            44 => 'CN',
            45 => 'CX',
            46 => 'CC',
            47 => 'CO',
            48 => 'KM',
            49 => 'CG',
            50 => 'CD',
            51 => 'CK',
            52 => 'CR',
            53 => 'CI',
            54 => 'HR',
            55 => 'CU',
            56 => 'CY',
            57 => 'CZ',
            58 => 'DK',
            59 => 'DJ',
            60 => 'DM',
            61 => 'DO',
            62 => 'EC',
            63 => 'EG',
            64 => 'SV',
            65 => 'GQ',
            66 => 'ER',
            67 => 'EE',
            68 => 'ET',
            69 => 'FK',
            70 => 'FO',
            71 => 'FJ',
            72 => 'FI',
            73 => 'FR',
            74 => 'GF',
            75 => 'PF',
            76 => 'TF',
            77 => 'GA',
            78 => 'GM',
            79 => 'GE',
            80 => 'DE',
            81 => 'GH',
            82 => 'GI',
            83 => 'GR',
            84 => 'GL',
            85 => 'GD',
            86 => 'GP',
            87 => 'GU',
            88 => 'GT',
            89 => 'GG',
            90 => 'GN',
            91 => 'GW',
            92 => 'GY',
            93 => 'HT',
            94 => 'HM',
            95 => 'VA',
            96 => 'HN',
            97 => 'HK',
            98 => 'HU',
            99 => 'IS',
            100 => 'IN',
            101 => 'ID',
            102 => 'IR',
            103 => 'IQ',
            104 => 'IE',
            105 => 'IM',
            106 => 'IL',
            107 => 'IT',
            108 => 'JM',
            109 => 'JP',
            110 => 'JE',
            111 => 'JO',
            112 => 'KZ',
            113 => 'KE',
            114 => 'KI',
            115 => 'KR',
            116 => 'KW',
            117 => 'KG',
            118 => 'LA',
            119 => 'LV',
            120 => 'LB',
            121 => 'LS',
            122 => 'LR',
            123 => 'LY',
            124 => 'LI',
            125 => 'LT',
            126 => 'LU',
            127 => 'MO',
            128 => 'MK',
            129 => 'MG',
            130 => 'MW',
            131 => 'MY',
            132 => 'MV',
            133 => 'ML',
            134 => 'MT',
            135 => 'MH',
            136 => 'MQ',
            137 => 'MR',
            138 => 'MU',
            139 => 'YT',
            140 => 'MX',
            141 => 'FM',
            142 => 'MD',
            143 => 'MC',
            144 => 'MN',
            145 => 'ME',
            146 => 'MS',
            147 => 'MA',
            148 => 'MZ',
            149 => 'MM',
            150 => 'NA',
            151 => 'NR',
            152 => 'NP',
            153 => 'NL',
            154 => 'AN',
            155 => 'NC',
            156 => 'NZ',
            157 => 'NI',
            158 => 'NE',
            159 => 'NG',
            160 => 'NU',
            161 => 'NF',
            162 => 'MP',
            163 => 'NO',
            164 => 'OM',
            165 => 'PK',
            166 => 'PW',
            167 => 'PS',
            168 => 'PA',
            169 => 'PG',
            170 => 'PY',
            171 => 'PE',
            172 => 'PH',
            173 => 'PN',
            174 => 'PL',
            175 => 'PT',
            176 => 'PR',
            177 => 'QA',
            178 => 'RE',
            179 => 'RO',
            180 => 'RU',
            181 => 'RW',
            182 => 'BL',
            183 => 'SH',
            184 => 'KN',
            185 => 'LC',
            186 => 'MF',
            187 => 'PM',
            188 => 'VC',
            189 => 'WS',
            190 => 'SM',
            191 => 'ST',
            192 => 'SA',
            193 => 'SN',
            194 => 'RS',
            195 => 'SC',
            196 => 'SL',
            197 => 'SG',
            198 => 'SK',
            199 => 'SI',
            200 => 'SB',
            201 => 'SO',
            202 => 'ZA',
            203 => 'GS',
            204 => 'ES',
            205 => 'LK',
            206 => 'SD',
            207 => 'SR',
            208 => 'SJ',
            209 => 'SZ',
            210 => 'SE',
            211 => 'CH',
            212 => 'SY',
            213 => 'TW',
            214 => 'TJ',
            215 => 'TZ',
            216 => 'TH',
            217 => 'TL',
            218 => 'TG',
            219 => 'TK',
            220 => 'TO',
            221 => 'TT',
            222 => 'TN',
            223 => 'TR',
            224 => 'TM',
            225 => 'TC',
            226 => 'TV',
            227 => 'UG',
            228 => 'UA',
            229 => 'AE',
            230 => 'GB',
            231 => 'US',
            232 => 'UM',
            233 => 'UY',
            234 => 'UZ',
            235 => 'VU',
            236 => 'VE',
            237 => 'VN',
            238 => 'VG',
            239 => 'VI',
            240 => 'WF',
            241 => 'EH',
            242 => 'YE',
            243 => 'ZM',
            244 => 'ZW'
          ]
        ],
        'isPreferred' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'preferredName' => [
          'type' => 'base',
          'notStorable' => true,
          'utility' => true
        ]
      ],
      'links' => [],
      'collection' => [
        'orderBy' => 'preferredName',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'code'
        ],
        'sortBy' => 'preferredName',
        'asc' => true
      ],
      'indexes' => [
        'name' => [
          'unique' => true,
          'columns' => [
            0 => 'name'
          ]
        ]
      ],
      'noDeletedAttribute' => true
    ],
    'AppLogRecord' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'dbType' => 'bigint'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'message' => [
          'type' => 'text',
          'readOnly' => true,
          'orderDisabled' => true
        ],
        'level' => [
          'type' => 'enum',
          'options' => [
            0 => 'Debug',
            1 => 'Info',
            2 => 'Notice',
            3 => 'Warning',
            4 => 'Error',
            5 => 'Critical',
            6 => 'Alert',
            7 => 'Emergency'
          ],
          'style' => [
            'Info' => 'info',
            'Notice' => 'primary',
            'Warning' => 'warning',
            'Error' => 'danger',
            'Critical' => 'danger',
            'Alert' => 'danger',
            'Emergency' => 'danger'
          ],
          'maxLength' => 9,
          'readOnly' => true,
          'index' => true
        ],
        'code' => [
          'type' => 'int',
          'readOnly' => true
        ],
        'exceptionClass' => [
          'type' => 'varchar',
          'maxLength' => 512,
          'readOnly' => true
        ],
        'file' => [
          'type' => 'varchar',
          'maxLength' => 512,
          'readOnly' => true
        ],
        'line' => [
          'type' => 'int',
          'readOnly' => true
        ],
        'requestMethod' => [
          'type' => 'enum',
          'maxLength' => 7,
          'options' => [
            0 => 'GET',
            1 => 'POST',
            2 => 'PUT',
            3 => 'UPDATE',
            4 => 'DELETE',
            5 => 'PATCH',
            6 => 'HEAD',
            7 => 'OPTIONS',
            8 => 'TRACE'
          ],
          'readOnly' => true
        ],
        'requestResourcePath' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'readOnly' => true
        ],
        'requestUrl' => [
          'type' => 'varchar',
          'maxLength' => 512,
          'readOnly' => true
        ]
      ],
      'links' => [],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'message'
        ],
        'sortBy' => 'number',
        'asc' => false
      ],
      'indexes' => [],
      'hooksDisabled' => true
    ],
    'AppSecret' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'pattern' => '[a-zA-Z]{1}[a-zA-Z0-9_]+',
          'index' => true,
          'tooltip' => true,
          'copyToClipboard' => true
        ],
        'value' => [
          'type' => 'text',
          'required' => true,
          'view' => 'views/admin/app-secret/fields/value'
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'deleteId' => [
          'type' => 'varchar',
          'maxLength' => 17,
          'readOnly' => true,
          'notNull' => true,
          'default' => '0',
          'utility' => true,
          'customizationDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name'
        ],
        'sortBy' => 'name',
        'asc' => true
      ],
      'indexes' => [
        'nameDeleteId' => [
          'type' => 'unique',
          'columns' => [
            0 => 'name',
            1 => 'deleteId'
          ]
        ]
      ],
      'deleteId' => true
    ],
    'ArrayValue' => [
      'fields' => [
        'value' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'attribute' => [
          'type' => 'varchar',
          'maxLength' => 100
        ]
      ],
      'indexes' => [
        'entityTypeValue' => [
          'columns' => [
            0 => 'entityType',
            1 => 'value'
          ]
        ],
        'entityValue' => [
          'columns' => [
            0 => 'entityType',
            1 => 'entityId',
            2 => 'value'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'Attachment' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/attachment/fields/name',
          'maxLength' => 255
        ],
        'type' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'size' => [
          'type' => 'int',
          'dbType' => 'bigint',
          'min' => 0
        ],
        'parent' => [
          'type' => 'linkParent',
          'view' => 'views/attachment/fields/parent'
        ],
        'related' => [
          'type' => 'linkParent',
          'noLoad' => true,
          'view' => 'views/attachment/fields/parent',
          'validatorClassName' => 'Espo\\Classes\\FieldValidators\\Attachment\\Related'
        ],
        'source' => [
          'type' => 'link',
          'readOnly' => true,
          'utility' => true
        ],
        'field' => [
          'type' => 'varchar',
          'utility' => true
        ],
        'isBeingUploaded' => [
          'type' => 'bool',
          'default' => false
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'contents' => [
          'type' => 'text',
          'notStorable' => true,
          'sanitizerClassNameList' => [],
          'sanitizerSuppressClassNameList' => [
            0 => 'Espo\\Classes\\FieldSanitizers\\EmptyStringToNull'
          ]
        ],
        'role' => [
          'type' => 'enum',
          'maxLength' => 36,
          'options' => [
            0 => 'Attachment',
            1 => 'Inline Attachment',
            2 => 'Import File',
            3 => 'Export File',
            4 => 'Mail Merge',
            5 => 'Mass Pdf'
          ]
        ],
        'storage' => [
          'type' => 'varchar',
          'maxLength' => 24,
          'default' => NULL
        ],
        'storageFilePath' => [
          'type' => 'varchar',
          'maxLength' => 260,
          'default' => NULL
        ],
        'global' => [
          'type' => 'bool',
          'default' => false
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'foreign' => 'attachments'
        ],
        'related' => [
          'type' => 'belongsToParent'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'id',
          1 => 'name'
        ],
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'parent' => [
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
    'AuthLogRecord' => [
      'fields' => [
        'username' => [
          'type' => 'varchar',
          'readOnly' => true,
          'maxLength' => 100
        ],
        'portal' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'user' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'authToken' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'ipAddress' => [
          'type' => 'varchar',
          'maxLength' => 45,
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ],
        'isDenied' => [
          'type' => 'bool',
          'readOnly' => true
        ],
        'denialReason' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'CREDENTIALS',
            2 => 'INACTIVE_USER',
            3 => 'IS_PORTAL_USER',
            4 => 'IS_NOT_PORTAL_USER',
            5 => 'USER_IS_NOT_IN_PORTAL',
            6 => 'IS_SYSTEM_USER',
            7 => 'FORBIDDEN'
          ],
          'readOnly' => true
        ],
        'requestTime' => [
          'type' => 'float',
          'readOnly' => true
        ],
        'requestUrl' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'requestMethod' => [
          'type' => 'varchar',
          'readOnly' => true,
          'maxLength' => 15
        ],
        'authTokenIsActive' => [
          'type' => 'foreign',
          'link' => 'authToken',
          'field' => 'isActive',
          'readOnly' => true,
          'view' => 'views/fields/foreign-bool'
        ],
        'authenticationMethod' => [
          'type' => 'enum',
          'view' => 'views/admin/auth-log-record/fields/authentication-method',
          'translation' => 'Settings.options.authenticationMethod'
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'portal' => [
          'type' => 'belongsTo',
          'entity' => 'Portal'
        ],
        'authToken' => [
          'type' => 'belongsTo',
          'entity' => 'AuthToken',
          'foreignName' => 'id'
        ],
        'actionHistoryRecords' => [
          'type' => 'hasMany',
          'entity' => 'ActionHistoryRecord',
          'foreign' => 'authLogRecord'
        ]
      ],
      'collection' => [
        'orderBy' => 'requestTime',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'ipAddress',
          1 => 'username'
        ],
        'sortBy' => 'requestTime',
        'asc' => false
      ],
      'indexes' => [
        'ipAddress' => [
          'columns' => [
            0 => 'ipAddress'
          ]
        ],
        'ipAddressRequestTime' => [
          'columns' => [
            0 => 'ipAddress',
            1 => 'requestTime'
          ]
        ],
        'requestTime' => [
          'columns' => [
            0 => 'requestTime'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'AuthToken' => [
      'fields' => [
        'token' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'index' => true,
          'readOnly' => true
        ],
        'hash' => [
          'type' => 'varchar',
          'maxLength' => 150,
          'index' => true,
          'readOnly' => true
        ],
        'secret' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'readOnly' => true
        ],
        'user' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'portal' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'ipAddress' => [
          'type' => 'varchar',
          'maxLength' => 45,
          'readOnly' => true
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true
        ],
        'lastAccess' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'portal' => [
          'type' => 'belongsTo',
          'entity' => 'Portal'
        ],
        'actionHistoryRecords' => [
          'type' => 'hasMany',
          'entity' => 'ActionHistoryRecord',
          'foreign' => 'authToken'
        ]
      ],
      'collection' => [
        'orderBy' => 'lastAccess',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'ipAddress',
          1 => 'userName'
        ],
        'sortBy' => 'lastAccess',
        'asc' => false
      ],
      'indexes' => [
        'token' => [
          'columns' => [
            0 => 'token',
            1 => 'deleted'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'AuthenticationProvider' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'method' => [
          'type' => 'enum',
          'view' => 'views/authentication-provider/fields/method',
          'translation' => 'Settings.options.authenticationMethod',
          'required' => true,
          'validatorClassNameMap' => [
            'valid' => 'Espo\\Classes\\FieldValidators\\AuthenticationProvider\\MethodValid'
          ]
        ],
        'oidcAuthorizationRedirectUri' => [
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true
        ],
        'oidcClientId' => [
          'type' => 'varchar'
        ],
        'oidcClientSecret' => [
          'type' => 'password'
        ],
        'oidcAuthorizationEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcUserInfoEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcTokenEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcJwksEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcJwtSignatureAlgorithmList' => [
          'type' => 'multiEnum',
          'optionsPath' => 'entityDefs.Settings.fields.oidcJwtSignatureAlgorithmList.options',
          'default' => [
            0 => 'RS256'
          ]
        ],
        'oidcScopes' => [
          'type' => 'multiEnum',
          'allowCustomOptions' => true,
          'optionsPath' => 'entityDefs.Settings.fields.oidcScopes.options',
          'default' => [
            0 => 'profile',
            1 => 'email',
            2 => 'phone'
          ]
        ],
        'oidcCreateUser' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcUsernameClaim' => [
          'type' => 'varchar',
          'optionsPath' => 'entityDefs.Settings.fields.oidcUsernameClaim.options',
          'tooltip' => true,
          'default' => 'sub'
        ],
        'oidcSync' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcLogoutUrl' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'oidcAuthorizationPrompt' => [
          'type' => 'enum',
          'options' => [
            0 => 'none',
            1 => 'consent',
            2 => 'login',
            3 => 'select_account'
          ],
          'maxLength' => 14
        ]
      ]
    ],
    'Autofollow' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'integer',
          'autoincrement' => true
        ],
        'entityType' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'index' => true
        ],
        'user' => [
          'type' => 'link'
        ]
      ]
    ],
    'Currency' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'maxLength' => 3,
          'dbType' => 'string'
        ],
        'rate' => [
          'type' => 'float'
        ]
      ],
      'noDeletedAttribute' => true
    ],
    'CurrencyRecord' => [
      'fields' => [
        'code' => [
          'type' => 'varchar',
          'maxLength' => 3,
          'required' => true,
          'readOnly' => true,
          'index' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'default' => 'Active',
          'maxLength' => 8,
          'style' => [
            'Inactive' => 'info'
          ]
        ],
        'label' => [
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true,
          'orderDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\CurrencyRecord\\Label'
        ],
        'symbol' => [
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true,
          'orderDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\CurrencyRecord\\Symbol'
        ],
        'rateDate' => [
          'type' => 'date',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\CurrencyRecord\\RateDate'
        ],
        'rate' => [
          'type' => 'decimal',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'decimalPlaces' => 6,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\CurrencyRecord\\Rate',
          'view' => 'views/currency-record-rate/fields/rate'
        ],
        'isBase' => [
          'type' => 'bool',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\CurrencyRecord\\IsBase'
        ],
        'deleteId' => [
          'type' => 'varchar',
          'maxLength' => 17,
          'readOnly' => true,
          'notNull' => true,
          'default' => '0',
          'utility' => true,
          'customizationDisabled' => true
        ]
      ],
      'links' => [
        'rates' => [
          'type' => 'hasMany',
          'entity' => 'CurrencyRecordRate',
          'foreign' => 'record',
          'readOnly' => true,
          'orderBy' => 'date',
          'order' => 'desc'
        ]
      ],
      'indexes' => [
        'codeDeleteId' => [
          'type' => 'unique',
          'columns' => [
            0 => 'code',
            1 => 'deleteId'
          ]
        ]
      ],
      'deleteId' => true,
      'collection' => [
        'textFilterFields' => [
          0 => 'code'
        ],
        'orderBy' => 'code',
        'order' => 'asc',
        'sortBy' => 'code',
        'asc' => true
      ]
    ],
    'CurrencyRecordRate' => [
      'fields' => [
        'record' => [
          'type' => 'link',
          'required' => true,
          'readOnlyAfterCreate' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\CurrencyRecordRate\\Record\\NonBase'
          ]
        ],
        'baseCode' => [
          'type' => 'varchar',
          'readOnly' => true,
          'maxLength' => 3
        ],
        'date' => [
          'type' => 'date',
          'required' => true,
          'readOnlyAfterCreate' => true,
          'default' => 'javascript: return this.dateTime.getToday();'
        ],
        'rate' => [
          'type' => 'decimal',
          'decimalPlaces' => 6,
          'min' => 0.0001,
          'precision' => 15,
          'scale' => 8,
          'required' => true,
          'audited' => true,
          'view' => 'views/currency-record-rate/fields/rate'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'deleteId' => [
          'type' => 'varchar',
          'maxLength' => 17,
          'readOnly' => true,
          'notNull' => true,
          'default' => '0',
          'utility' => true,
          'customizationDisabled' => true
        ]
      ],
      'links' => [
        'record' => [
          'type' => 'belongsTo',
          'entity' => 'CurrencyRecord',
          'foreignName' => 'code'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'indexes' => [
        'recordIdBaseCodeDate' => [
          'type' => 'unique',
          'columns' => [
            0 => 'recordId',
            1 => 'baseCode',
            2 => 'date',
            3 => 'deleteId'
          ]
        ]
      ],
      'deleteId' => true,
      'collection' => [
        'orderBy' => 'date',
        'order' => 'desc',
        'sortBy' => 'date',
        'asc' => false
      ]
    ],
    'DashboardTemplate' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true
        ],
        'layout' => [
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout',
          'inlineEditDisabled' => true,
          'required' => true
        ],
        'dashletsOptions' => [
          'type' => 'jsonObject',
          'utility' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'Email' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'subject' => [
          'type' => 'varchar',
          'required' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'views/email/fields/subject',
          'layoutAvailabilityList' => [
            0 => 'list'
          ],
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true
        ],
        'fromName' => [
          'type' => 'varchar',
          'readOnly' => true,
          'notStorable' => true,
          'textFilterDisabled' => true,
          'layoutFiltersDisabled' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'fromAddress' => [
          'type' => 'varchar',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'textFilterDisabled' => true,
          'layoutFiltersDisabled' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'fromString' => [
          'type' => 'varchar',
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true,
          'textFilterDisabled' => true
        ],
        'replyToString' => [
          'type' => 'varchar',
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true,
          'textFilterDisabled' => true
        ],
        'replyToName' => [
          'type' => 'varchar',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'textFilterDisabled' => true,
          'layoutFiltersDisabled' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'replyToAddress' => [
          'type' => 'varchar',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'textFilterDisabled' => true,
          'layoutFiltersDisabled' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'addressNameMap' => [
          'type' => 'jsonObject',
          'utility' => true,
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'from' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'required' => true,
          'view' => 'views/email/fields/from-address-varchar',
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'detail',
            1 => 'filters'
          ],
          'massUpdateDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader'
        ],
        'to' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'required' => true,
          'view' => 'views/email/fields/email-address-varchar',
          'validatorClassName' => 'Espo\\Classes\\FieldValidators\\Email\\EmailAddresses',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\Valid',
            1 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\MaxCount'
          ],
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'detail',
            1 => 'filters'
          ],
          'massUpdateDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader'
        ],
        'cc' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'views/email/fields/email-address-varchar',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\Valid',
            1 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\MaxCount'
          ],
          'customizationDisabled' => true,
          'textFilterDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'detail',
            1 => 'filters'
          ],
          'massUpdateDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader'
        ],
        'bcc' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'views/email/fields/email-address-varchar',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\Valid',
            1 => 'Espo\\Classes\\FieldValidators\\Email\\Addresses\\MaxCount'
          ],
          'customizationDisabled' => true,
          'textFilterDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'detail'
          ],
          'massUpdateDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader'
        ],
        'replyTo' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'views/email/fields/email-address-varchar',
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'detail'
          ],
          'massUpdateDisabled' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader'
        ],
        'personStringData' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'views/email/fields/person-string-data',
          'layoutAvailabilityList' => [
            0 => 'list'
          ],
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'isRead' => [
          'type' => 'bool',
          'notStorable' => true,
          'default' => true,
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'isNotRead' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'isReplied' => [
          'type' => 'bool',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'isNotReplied' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'isImportant' => [
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'inTrash' => [
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'inArchive' => [
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'folderId' => [
          'type' => 'varchar',
          'notStorable' => true,
          'default' => NULL,
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'readOnly' => true
        ],
        'isUsers' => [
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'isUsersSent' => [
          'type' => 'bool',
          'notStorable' => true,
          'readOnly' => true,
          'utility' => true
        ],
        'folder' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'entity' => 'EmailFolder',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'folderString' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'entity' => 'EmailFolder',
          'customizationDisabled' => true,
          'view' => 'views/email/fields/folder-string',
          'layoutAvailabilityList' => [
            0 => 'defaultSidePanel'
          ]
        ],
        'nameHash' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'typeHash' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'readOnly' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'idHash' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'messageId' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'readOnly' => true,
          'index' => true,
          'textFilterDisabled' => true,
          'customizationDisabled' => true
        ],
        'messageIdInternal' => [
          'type' => 'varchar',
          'maxLength' => 300,
          'readOnly' => true,
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'emailAddress' => [
          'type' => 'varchar',
          'notStorable' => true,
          'view' => 'views/email/fields/email-address',
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'fromEmailAddress' => [
          'type' => 'link',
          'view' => 'views/email/fields/from-email-address',
          'textFilterDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'toEmailAddresses' => [
          'type' => 'linkMultiple',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'ccEmailAddresses' => [
          'type' => 'linkMultiple',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'bccEmailAddresses' => [
          'type' => 'linkMultiple',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'replyToEmailAddresses' => [
          'type' => 'linkMultiple',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'bodyPlain' => [
          'type' => 'text',
          'seeMoreDisabled' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'massUpdateDisabled' => true,
          'readOnly' => true
        ],
        'body' => [
          'type' => 'wysiwyg',
          'view' => 'views/email/fields/body',
          'attachmentField' => 'attachments',
          'useIframe' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'isHtml' => [
          'type' => 'bool',
          'default' => true,
          'fieldManagerParamList' => [
            0 => 'default',
            1 => 'tooltipText'
          ],
          'inlineEditDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Draft',
            1 => 'Sending',
            2 => 'Sent',
            3 => 'Archived',
            4 => 'Failed'
          ],
          'default' => 'Archived',
          'clientReadOnly' => true,
          'style' => [
            'Draft' => 'warning',
            'Failed' => 'danger',
            'Sending' => 'warning'
          ],
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'attachments' => [
          'type' => 'attachmentMultiple',
          'sourceList' => [
            0 => 'Document'
          ],
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'hasAttachment' => [
          'type' => 'bool',
          'readOnly' => true,
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'layoutDetailDisabled' => true
        ],
        'parent' => [
          'type' => 'linkParent',
          'fieldManagerParamList' => [
            0 => 'required',
            1 => 'entityList',
            2 => 'autocompleteOnEmpty',
            3 => 'audited',
            4 => 'tooltipText'
          ],
          'entityList' => [
            0 => 'Account',
            1 => 'Lead',
            2 => 'Contact',
            3 => 'Opportunity',
            4 => 'Case'
          ]
        ],
        'dateSent' => [
          'type' => 'datetime',
          'customizationDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'massUpdateDisabled' => true,
          'view' => 'views/email/fields/date-sent'
        ],
        'deliveryDate' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'sendAt' => [
          'type' => 'datetime',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'list'
          ],
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Email\\SendAt\\Future'
          ]
        ],
        'isAutoReply' => [
          'type' => 'bool',
          'readOnly' => true,
          'fieldManagerParamList' => [],
          'layoutDefaultSidePanelDisabled' => true,
          'layoutDetailDisabled' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'customizationDisabled' => true
        ],
        'sentBy' => [
          'type' => 'link',
          'readOnly' => true,
          'noLoad' => true,
          'customizationDisabled' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'customizationDisabled' => true
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => false,
          'view' => 'views/fields/assigned-user',
          'massUpdateDisabled' => true
        ],
        'replied' => [
          'type' => 'link',
          'noJoin' => true,
          'view' => 'views/email/fields/replied',
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'replies' => [
          'type' => 'linkMultiple',
          'readOnly' => true,
          'orderBy' => 'dateSent',
          'view' => 'views/email/fields/replies',
          'customizationDisabled' => true,
          'columns' => [
            'status' => 'status'
          ],
          'massUpdateDisabled' => true
        ],
        'isSystem' => [
          'type' => 'bool',
          'default' => false,
          'readOnly' => true,
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'isJustSent' => [
          'type' => 'bool',
          'default' => false,
          'readOnly' => true,
          'utility' => true,
          'notStorable' => true,
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'isBeingImported' => [
          'type' => 'bool',
          'readOnly' => true,
          'utility' => true,
          'notStorable' => true,
          'customizationDisabled' => true,
          'massUpdateDisabled' => true
        ],
        'skipNotificationMap' => [
          'type' => 'jsonObject',
          'utility' => true,
          'readOnly' => true,
          'notStorable' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'users' => [
          'type' => 'linkMultiple',
          'noLoad' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'readOnly' => true,
          'columns' => [
            'inTrash' => 'inTrash',
            'folderId' => 'folderId',
            'inArchive' => 'inArchive',
            'isRead' => 'isRead'
          ],
          'customizationDisabled' => true,
          'additionalAttributeList' => [
            0 => 'columns'
          ]
        ],
        'assignedUsers' => [
          'type' => 'linkMultiple',
          'layoutListDisabled' => true,
          'readOnly' => true,
          'customizationDisabled' => true,
          'view' => 'views/fields/assigned-users'
        ],
        'inboundEmails' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'noLoad' => true,
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'default'
          ]
        ],
        'emailAccounts' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'noLoad' => true,
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'default'
          ]
        ],
        'icsContents' => [
          'type' => 'text',
          'readOnly' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'icsEventData' => [
          'type' => 'jsonObject',
          'readOnly' => true,
          'directAccessDisabled' => true,
          'notStorable' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'icsEventUid' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'index' => true,
          'readOnly' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'icsEventDateStart' => [
          'type' => 'datetimeOptional',
          'readOnly' => true,
          'notStorable' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'createEvent' => [
          'type' => 'base',
          'utility' => true,
          'notStorable' => true,
          'view' => 'views/email/fields/create-event',
          'customizationDisabled' => true,
          'massUpdateDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'createdEvent' => [
          'type' => 'linkParent',
          'readOnly' => true,
          'view' => 'views/email/fields/created-event',
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ],
          'layoutAvailabilityList' => []
        ],
        'groupFolder' => [
          'type' => 'link',
          'massUpdateDisabled' => true,
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'audited'
          ],
          'audited' => true
        ],
        'groupStatusFolder' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Archive',
            2 => 'Trash'
          ],
          'maxLength' => 7,
          'readOnly' => true,
          'customizationDisabled' => true,
          'index' => true
        ],
        'account' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'tasks' => [
          'type' => 'linkMultiple',
          'readOnly' => true,
          'columns' => [
            'status' => 'status'
          ],
          'view' => 'crm:views/task/fields/tasks',
          'customizationDefaultDisabled' => true
        ],
        'icsEventDateStartDate' => [
          'readOnly' => true,
          'notStorable' => true,
          'customizationDisabled' => true,
          'type' => 'date',
          'utility' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'icsEventDateStart'
          ]
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam'
        ],
        'assignedUsers' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'relationName' => 'entityUser'
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'emails',
          'additionalColumns' => [
            'isRead' => [
              'type' => 'bool',
              'default' => false
            ],
            'isImportant' => [
              'type' => 'bool',
              'default' => false
            ],
            'inTrash' => [
              'type' => 'bool',
              'default' => false
            ],
            'inArchive' => [
              'type' => 'bool',
              'default' => false
            ],
            'folderId' => [
              'type' => 'foreignId',
              'default' => NULL
            ]
          ]
        ],
        'sentBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'attachments' => [
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'foreign' => 'parent',
          'relationName' => 'attachments'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'entityList' => [],
          'foreign' => 'emails'
        ],
        'replied' => [
          'type' => 'belongsTo',
          'entity' => 'Email',
          'foreign' => 'replies'
        ],
        'replies' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'replied'
        ],
        'fromEmailAddress' => [
          'type' => 'belongsTo',
          'entity' => 'EmailAddress'
        ],
        'toEmailAddresses' => [
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => [
            'addressType' => 'to'
          ],
          'additionalColumns' => [
            'addressType' => [
              'type' => 'varchar',
              'len' => '4'
            ]
          ]
        ],
        'ccEmailAddresses' => [
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => [
            'addressType' => 'cc'
          ],
          'additionalColumns' => [
            'addressType' => [
              'type' => 'varchar',
              'len' => '4'
            ]
          ],
          'layoutDefaultSidePanelDisabled' => true
        ],
        'bccEmailAddresses' => [
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => [
            'addressType' => 'bcc'
          ],
          'additionalColumns' => [
            'addressType' => [
              'type' => 'varchar',
              'len' => '4'
            ]
          ],
          'layoutDefaultSidePanelDisabled' => true
        ],
        'replyToEmailAddresses' => [
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => [
            'addressType' => 'rto'
          ],
          'additionalColumns' => [
            'addressType' => [
              'type' => 'varchar',
              'len' => '4'
            ]
          ]
        ],
        'inboundEmails' => [
          'type' => 'hasMany',
          'entity' => 'InboundEmail',
          'foreign' => 'emails'
        ],
        'emailAccounts' => [
          'type' => 'hasMany',
          'entity' => 'EmailAccount',
          'foreign' => 'emails'
        ],
        'createdEvent' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Meeting'
          ]
        ],
        'groupFolder' => [
          'type' => 'belongsTo',
          'entity' => 'GroupEmailFolder',
          'foreign' => 'emails'
        ],
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account'
        ],
        'tasks' => [
          'type' => 'hasMany',
          'entity' => 'Task',
          'foreign' => 'email'
        ]
      ],
      'collection' => [
        'orderBy' => 'dateSent',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'bodyPlain',
          2 => 'body'
        ],
        'countDisabled' => true,
        'fullTextSearch' => true,
        'fullTextSearchOrderType' => 'original',
        'sortBy' => 'dateSent',
        'asc' => false
      ],
      'indexes' => [
        'createdById' => [
          'columns' => [
            0 => 'createdById'
          ]
        ],
        'dateSent' => [
          'columns' => [
            0 => 'dateSent',
            1 => 'deleted'
          ]
        ],
        'dateSentStatus' => [
          'columns' => [
            0 => 'dateSent',
            1 => 'status',
            2 => 'deleted'
          ]
        ]
      ]
    ],
    'EmailAccount' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'emailAddress' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'tooltip' => true,
          'view' => 'views/email-account/fields/email-address'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'style' => [
            'Inactive' => 'info'
          ],
          'default' => 'Active'
        ],
        'host' => [
          'type' => 'varchar'
        ],
        'port' => [
          'type' => 'int',
          'min' => 0,
          'max' => 65535,
          'default' => 993,
          'disableFormatting' => true
        ],
        'security' => [
          'type' => 'enum',
          'default' => 'SSL',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'username' => [
          'type' => 'varchar'
        ],
        'password' => [
          'type' => 'password'
        ],
        'monitoredFolders' => [
          'type' => 'array',
          'default' => [
            0 => 'INBOX'
          ],
          'view' => 'views/email-account/fields/folders',
          'displayAsList' => true,
          'noEmptyString' => true,
          'duplicateIgnore' => true,
          'tooltip' => true,
          'fullNameAdditionalAttributeList' => [
            0 => 'folderMap'
          ]
        ],
        'sentFolder' => [
          'type' => 'varchar',
          'view' => 'views/email-account/fields/folder',
          'duplicateIgnore' => true
        ],
        'folderMap' => [
          'type' => 'jsonObject',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\EmailAccount\\FolderMap\\Valid'
          ]
        ],
        'storeSentEmails' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'keepFetchedEmailsUnread' => [
          'type' => 'bool'
        ],
        'fetchSince' => [
          'type' => 'date',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\InboundEmail\\FetchSince\\Required'
          ],
          'forceValidation' => true
        ],
        'fetchData' => [
          'type' => 'jsonObject',
          'readOnly' => true,
          'duplicateIgnore' => true
        ],
        'emailFolder' => [
          'type' => 'link',
          'view' => 'views/email-account/fields/email-folder',
          'duplicateIgnore' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user'
        ],
        'connectedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'useImap' => [
          'type' => 'bool',
          'default' => true
        ],
        'useSmtp' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'smtpHost' => [
          'type' => 'varchar'
        ],
        'smtpPort' => [
          'type' => 'int',
          'min' => 0,
          'max' => 65535,
          'default' => 587,
          'disableFormatting' => true
        ],
        'smtpAuth' => [
          'type' => 'bool',
          'default' => true
        ],
        'smtpSecurity' => [
          'type' => 'enum',
          'default' => 'TLS',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'smtpUsername' => [
          'type' => 'varchar'
        ],
        'smtpPassword' => [
          'type' => 'password'
        ],
        'smtpAuthMechanism' => [
          'type' => 'enum',
          'options' => [
            0 => 'login',
            1 => 'crammd5',
            2 => 'plain'
          ],
          'default' => 'login'
        ],
        'imapHandler' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'smtpHandler' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'filters' => [
          'type' => 'hasChildren',
          'foreign' => 'parent',
          'entity' => 'EmailFilter'
        ],
        'emails' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'emailAccounts'
        ],
        'emailFolder' => [
          'type' => 'belongsTo',
          'entity' => 'EmailFolder'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'EmailAddress' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 255
        ],
        'lower' => [
          'type' => 'varchar',
          'required' => true,
          'index' => true
        ],
        'invalid' => [
          'type' => 'bool'
        ],
        'optOut' => [
          'type' => 'bool'
        ],
        'primary' => [
          'type' => 'bool',
          'notStorable' => true
        ]
      ],
      'links' => [],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ],
      'hooksDisabled' => true
    ],
    'EmailFilter' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'tooltip' => true,
          'pattern' => '$noBadCharacters'
        ],
        'from' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true
        ],
        'to' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true
        ],
        'subject' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true
        ],
        'bodyContains' => [
          'type' => 'array',
          'tooltip' => true
        ],
        'bodyContainsAll' => [
          'type' => 'array',
          'tooltip' => true
        ],
        'isGlobal' => [
          'type' => 'bool',
          'tooltip' => true,
          'default' => false,
          'readOnlyAfterCreate' => true
        ],
        'parent' => [
          'type' => 'linkParent',
          'view' => 'views/email-filter/fields/parent',
          'readOnlyAfterCreate' => true
        ],
        'action' => [
          'type' => 'enum',
          'default' => 'Skip',
          'options' => [
            0 => 'Skip',
            1 => 'Move to Folder',
            2 => 'Move to Group Folder',
            3 => 'None'
          ]
        ],
        'emailFolder' => [
          'type' => 'link',
          'view' => 'views/email-filter/fields/email-folder'
        ],
        'groupEmailFolder' => [
          'type' => 'link'
        ],
        'markAsRead' => [
          'type' => 'bool'
        ],
        'skipNotification' => [
          'type' => 'bool'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'User',
            1 => 'EmailAccount',
            2 => 'InboundEmail'
          ]
        ],
        'emailFolder' => [
          'type' => 'belongsTo',
          'entity' => 'EmailFolder'
        ],
        'groupEmailFolder' => [
          'type' => 'belongsTo',
          'entity' => 'GroupEmailFolder'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ]
    ],
    'EmailFolder' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 64,
          'pattern' => '$noBadCharacters'
        ],
        'order' => [
          'type' => 'int'
        ],
        'assignedUser' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'skipNotifications' => [
          'type' => 'bool'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'order',
        'order' => 'asc',
        'sortBy' => 'order',
        'asc' => true
      ]
    ],
    'EmailTemplate' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'audited' => true
        ],
        'subject' => [
          'type' => 'varchar',
          'audited' => true
        ],
        'body' => [
          'type' => 'wysiwyg',
          'view' => 'views/email-template/fields/body',
          'useIframe' => true,
          'attachmentField' => 'attachments',
          'audited' => true
        ],
        'isHtml' => [
          'type' => 'bool',
          'default' => true,
          'inlineEditDisabled' => true,
          'audited' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'default' => 'Active',
          'style' => [
            'Inactive' => 'info'
          ],
          'maxLength' => 8,
          'audited' => true
        ],
        'oneOff' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'attachments' => [
          'type' => 'attachmentMultiple',
          'audited' => true
        ],
        'category' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'audited' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ]
      ],
      'links' => [
        'attachments' => [
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'foreign' => 'parent'
        ],
        'category' => [
          'type' => 'belongsTo',
          'foreign' => 'emailTemplates',
          'entity' => 'EmailTemplateCategory'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name'
        ],
        'sortBy' => 'name',
        'asc' => true
      ],
      'optimisticConcurrencyControl' => true
    ],
    'EmailTemplateCategory' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'order' => [
          'type' => 'int',
          'minValue' => 1,
          'readOnly' => true,
          'textFilterDisabled' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'teams' => [
          'type' => 'linkMultiple'
        ],
        'parent' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'childList' => [
          'type' => 'jsonArray',
          'notStorable' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'parent' => [
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'EmailTemplateCategory'
        ],
        'children' => [
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'EmailTemplateCategory',
          'readOnly' => true
        ],
        'emailTemplates' => [
          'type' => 'hasMany',
          'foreign' => 'category',
          'entity' => 'EmailTemplate'
        ]
      ],
      'collection' => [
        'orderBy' => 'parent',
        'order' => 'asc',
        'sortBy' => 'parent',
        'asc' => true
      ],
      'additionalTables' => [
        'EmailTemplateCategoryPath' => [
          'attributes' => [
            'id' => [
              'type' => 'id',
              'dbType' => 'integer',
              'len' => 11,
              'autoincrement' => true
            ],
            'ascendorId' => [
              'type' => 'foreignId',
              'index' => true
            ],
            'descendorId' => [
              'type' => 'foreignId',
              'index' => true
            ]
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\CategoryTree'
    ],
    'Export' => [
      'fields' => [
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Pending',
            1 => 'Running',
            2 => 'Success',
            3 => 'Failed'
          ],
          'default' => 'Pending'
        ],
        'params' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'required' => true
        ],
        'notifyOnFinish' => [
          'type' => 'bool',
          'default' => false
        ],
        'attachment' => [
          'type' => 'link',
          'entity' => 'Attachment'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'Extension' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'version' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 50
        ],
        'fileList' => [
          'type' => 'jsonArray'
        ],
        'licenseStatus' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Valid',
            2 => 'Invalid',
            3 => 'Expired',
            4 => 'Soft-Expired'
          ],
          'index' => true,
          'maxLength' => 36
        ],
        'licenseStatusMessage' => [
          'type' => 'varchar'
        ],
        'description' => [
          'type' => 'text'
        ],
        'isInstalled' => [
          'type' => 'bool',
          'default' => false
        ],
        'checkVersionUrl' => [
          'type' => 'url'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'hooksDisabled' => true
    ],
    'ExternalAccount' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'string',
          'maxLength' => 64
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'enabled' => [
          'type' => 'bool'
        ],
        'isLocked' => [
          'type' => 'bool'
        ]
      ]
    ],
    'GroupEmailFolder' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 64,
          'pattern' => '$noBadCharacters'
        ],
        'order' => [
          'type' => 'int'
        ],
        'teams' => [
          'type' => 'linkMultiple'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'groupEmailFolders'
        ],
        'emails' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'groupFolder'
        ]
      ],
      'collection' => [
        'orderBy' => 'order',
        'order' => 'asc',
        'sortBy' => 'order',
        'asc' => true
      ]
    ],
    'Import' => [
      'fields' => [
        'entityType' => [
          'type' => 'enum',
          'translation' => 'Global.scopeNames',
          'required' => true,
          'readOnly' => true,
          'view' => 'views/fields/entity-type'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Standby',
            1 => 'Pending',
            2 => 'In Process',
            3 => 'Complete',
            4 => 'Failed'
          ],
          'readOnly' => true,
          'displayAsLabel' => true,
          'labelType' => 'state',
          'style' => [
            'Complete' => 'success',
            'Failed' => 'danger'
          ]
        ],
        'file' => [
          'type' => 'file',
          'required' => true,
          'readOnly' => true
        ],
        'importedCount' => [
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true
        ],
        'duplicateCount' => [
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true
        ],
        'updatedCount' => [
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true
        ],
        'lastIndex' => [
          'type' => 'int',
          'readOnly' => true
        ],
        'params' => [
          'type' => 'jsonObject',
          'readOnly' => true
        ],
        'attributeList' => [
          'type' => 'jsonArray',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'errors' => [
          'type' => 'hasMany',
          'entity' => 'ImportError',
          'foreign' => 'import',
          'readOnly' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ]
    ],
    'ImportEml' => [
      'fields' => [
        'file' => [
          'type' => 'file'
        ]
      ],
      'skipRebuild' => true
    ],
    'ImportEntity' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'bigint',
          'autoincrement' => true
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'import' => [
          'type' => 'link'
        ],
        'isImported' => [
          'type' => 'bool'
        ],
        'isUpdated' => [
          'type' => 'bool'
        ],
        'isDuplicate' => [
          'type' => 'bool'
        ]
      ],
      'indexes' => [
        'entityImport' => [
          'columns' => [
            0 => 'importId',
            1 => 'entityType'
          ]
        ]
      ]
    ],
    'ImportError' => [
      'fields' => [
        'import' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'entityType' => [
          'type' => 'foreign',
          'link' => 'import',
          'field' => 'entityType'
        ],
        'rowIndex' => [
          'type' => 'int',
          'readOnly' => true,
          'tooltip' => true
        ],
        'exportRowIndex' => [
          'type' => 'int',
          'readOnly' => true
        ],
        'lineNumber' => [
          'type' => 'int',
          'readOnly' => true,
          'tooltip' => true,
          'notStorable' => true,
          'view' => 'views/import-error/fields/line-number'
        ],
        'exportLineNumber' => [
          'type' => 'int',
          'readOnly' => true,
          'tooltip' => true,
          'notStorable' => true,
          'view' => 'views/import-error/fields/line-number'
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Validation',
            2 => 'No-Access',
            3 => 'Not-Found',
            4 => 'Integrity-Constraint-Violation'
          ],
          'readOnly' => true
        ],
        'validationFailures' => [
          'type' => 'jsonArray',
          'readOnly' => true,
          'view' => 'views/import-error/fields/validation-failures'
        ],
        'row' => [
          'type' => 'array',
          'readOnly' => true,
          'displayAsList' => true,
          'doNotStoreArrayValues' => true
        ]
      ],
      'links' => [
        'import' => [
          'type' => 'belongsTo',
          'entity' => 'Import',
          'foreign' => 'errors',
          'foreignName' => 'id'
        ]
      ],
      'collection' => [
        'orderBy' => 'rowIndex',
        'sortBy' => 'rowIndex'
      ],
      'indexes' => [
        'rowIndex' => [
          'columns' => [
            0 => 'rowIndex'
          ]
        ],
        'importRowIndex' => [
          'columns' => [
            0 => 'importId',
            1 => 'rowIndex'
          ]
        ]
      ]
    ],
    'InboundEmail' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters',
          'view' => 'views/inbound-email/fields/name'
        ],
        'emailAddress' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'view' => 'views/inbound-email/fields/email-address'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'style' => [
            'Inactive' => 'info'
          ],
          'default' => 'Active'
        ],
        'host' => [
          'type' => 'varchar'
        ],
        'port' => [
          'type' => 'int',
          'min' => 0,
          'max' => 65535,
          'default' => 993,
          'disableFormatting' => true
        ],
        'security' => [
          'type' => 'enum',
          'default' => 'SSL',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'username' => [
          'type' => 'varchar'
        ],
        'password' => [
          'type' => 'password'
        ],
        'monitoredFolders' => [
          'type' => 'array',
          'default' => [
            0 => 'INBOX'
          ],
          'view' => 'views/inbound-email/fields/folders',
          'displayAsList' => true,
          'noEmptyString' => true,
          'duplicateIgnore' => true
        ],
        'fetchSince' => [
          'type' => 'date',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\InboundEmail\\FetchSince\\Required'
          ],
          'forceValidation' => true
        ],
        'fetchData' => [
          'type' => 'jsonObject',
          'readOnly' => true,
          'duplicateIgnore' => true
        ],
        'assignToUser' => [
          'type' => 'link',
          'tooltip' => true
        ],
        'team' => [
          'type' => 'link',
          'tooltip' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'tooltip' => true
        ],
        'addAllTeamUsers' => [
          'type' => 'bool',
          'tooltip' => true,
          'default' => true
        ],
        'isSystem' => [
          'type' => 'bool',
          'notStorable' => true,
          'readOnly' => true,
          'directAccessDisabled' => true,
          'tooltip' => true
        ],
        'sentFolder' => [
          'type' => 'varchar',
          'view' => 'views/inbound-email/fields/folder',
          'duplicateIgnore' => true
        ],
        'storeSentEmails' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'keepFetchedEmailsUnread' => [
          'type' => 'bool'
        ],
        'connectedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'excludeFromReply' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'useImap' => [
          'type' => 'bool',
          'default' => true
        ],
        'useSmtp' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'smtpIsShared' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'smtpIsForMassEmail' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'smtpHost' => [
          'type' => 'varchar'
        ],
        'smtpPort' => [
          'type' => 'int',
          'min' => 0,
          'max' => 65535,
          'default' => 587,
          'disableFormatting' => true
        ],
        'smtpAuth' => [
          'type' => 'bool',
          'default' => true
        ],
        'smtpSecurity' => [
          'type' => 'enum',
          'default' => 'TLS',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'smtpUsername' => [
          'type' => 'varchar'
        ],
        'smtpPassword' => [
          'type' => 'password'
        ],
        'smtpAuthMechanism' => [
          'type' => 'enum',
          'options' => [
            0 => 'login',
            1 => 'crammd5',
            2 => 'plain'
          ],
          'default' => 'login'
        ],
        'createCase' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'caseDistribution' => [
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
        'targetUserPosition' => [
          'type' => 'enum',
          'view' => 'views/inbound-email/fields/target-user-position',
          'tooltip' => true
        ],
        'reply' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'replyEmailTemplate' => [
          'type' => 'link'
        ],
        'replyFromAddress' => [
          'type' => 'varchar'
        ],
        'replyToAddress' => [
          'type' => 'varchar',
          'view' => 'views/fields/email-address',
          'tooltip' => true
        ],
        'replyFromName' => [
          'type' => 'varchar'
        ],
        'fromName' => [
          'type' => 'varchar'
        ],
        'groupEmailFolder' => [
          'type' => 'link',
          'tooltip' => true
        ],
        'imapHandler' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'smtpHandler' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'inboundEmails'
        ],
        'assignToUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'team' => [
          'type' => 'belongsTo',
          'entity' => 'Team'
        ],
        'replyEmailTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'EmailTemplate'
        ],
        'filters' => [
          'type' => 'hasChildren',
          'foreign' => 'parent',
          'entity' => 'EmailFilter'
        ],
        'emails' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'inboundEmails'
        ],
        'groupEmailFolder' => [
          'type' => 'belongsTo',
          'entity' => 'GroupEmailFolder'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'Integration' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'string',
          'maxLength' => 24
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'enabled' => [
          'type' => 'bool'
        ]
      ]
    ],
    'Job' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/admin/job/fields/name'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Pending',
            1 => 'Ready',
            2 => 'Running',
            3 => 'Success',
            4 => 'Failed'
          ],
          'default' => 'Pending',
          'style' => [
            'Success' => 'success',
            'Failed' => 'danger',
            'Running' => 'warning',
            'Ready' => 'warning'
          ],
          'maxLength' => 16
        ],
        'executeTime' => [
          'type' => 'datetime',
          'required' => true,
          'hasSeconds' => true
        ],
        'number' => [
          'type' => 'int',
          'index' => true,
          'readOnly' => true,
          'view' => 'views/fields/autoincrement',
          'dbType' => 'bigint',
          'unique' => true,
          'autoincrement' => true
        ],
        'className' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 255
        ],
        'serviceName' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'methodName' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'job' => [
          'type' => 'varchar',
          'view' => 'views/scheduled-job/fields/job'
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'scheduledJob' => [
          'type' => 'link'
        ],
        'scheduledJobJob' => [
          'type' => 'foreign',
          'link' => 'scheduledJob',
          'field' => 'job'
        ],
        'queue' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'default' => NULL
        ],
        'group' => [
          'type' => 'varchar',
          'maxLength' => 128,
          'default' => NULL
        ],
        'targetGroup' => [
          'type' => 'varchar',
          'maxLength' => 128,
          'default' => NULL
        ],
        'startedAt' => [
          'type' => 'datetime',
          'hasSeconds' => true
        ],
        'executedAt' => [
          'type' => 'datetime',
          'hasSeconds' => true
        ],
        'pid' => [
          'type' => 'int'
        ],
        'attempts' => [
          'type' => 'int'
        ],
        'targetId' => [
          'type' => 'varchar',
          'maxLength' => 48
        ],
        'targetType' => [
          'type' => 'varchar',
          'maxLength' => 64
        ],
        'failedAttempts' => [
          'type' => 'int'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ]
      ],
      'links' => [
        'scheduledJob' => [
          'type' => 'belongsTo',
          'entity' => 'ScheduledJob'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'id',
          1 => 'name',
          2 => 'methodName',
          3 => 'serviceName'
        ],
        'countDisabled' => true,
        'sortBy' => 'number',
        'asc' => false
      ],
      'indexes' => [
        'executeTime' => [
          'columns' => [
            0 => 'status',
            1 => 'executeTime'
          ]
        ],
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'statusScheduledJobId' => [
          'columns' => [
            0 => 'status',
            1 => 'scheduledJobId'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'KanbanOrder' => [
      'fields' => [
        'order' => [
          'type' => 'int',
          'dbType' => 'smallint'
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'group' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'user' => [
          'type' => 'link'
        ]
      ],
      'links' => [
        'entity' => [
          'type' => 'belongsToParent'
        ]
      ],
      'indexes' => [
        'entityUserId' => [
          'columns' => [
            0 => 'entityType',
            1 => 'entityId',
            2 => 'userId'
          ]
        ],
        'entityType' => [
          'columns' => [
            0 => 'entityType'
          ]
        ],
        'entityTypeUserId' => [
          'columns' => [
            0 => 'entityType',
            1 => 'userId'
          ]
        ]
      ]
    ],
    'LayoutRecord' => [
      'fields' => [
        'name' => [
          'type' => 'varchar'
        ],
        'layoutSet' => [
          'type' => 'link'
        ],
        'data' => [
          'type' => 'text'
        ]
      ],
      'links' => [
        'layoutSet' => [
          'type' => 'belongsTo',
          'entity' => 'LayoutSet',
          'foreign' => 'layoutRecords'
        ]
      ],
      'indexes' => [
        'nameLayoutSetId' => [
          'columns' => [
            0 => 'name',
            1 => 'layoutSetId'
          ]
        ]
      ]
    ],
    'LayoutSet' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'layoutList' => [
          'type' => 'multiEnum',
          'displayAsList' => true,
          'view' => 'views/layout-set/fields/layout-list'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'layoutRecords' => [
          'type' => 'hasMany',
          'entity' => 'LayoutRecord',
          'foreign' => 'layoutSet'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'layoutSet'
        ],
        'portals' => [
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'layoutSet'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'LeadCapture' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'campaign' => [
          'type' => 'link',
          'audited' => true
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true,
          'audited' => true
        ],
        'subscribeToTargetList' => [
          'type' => 'bool',
          'default' => true,
          'audited' => true
        ],
        'subscribeContactToTargetList' => [
          'type' => 'bool',
          'default' => true,
          'audited' => true
        ],
        'targetList' => [
          'type' => 'link',
          'audited' => true
        ],
        'fieldList' => [
          'type' => 'multiEnum',
          'default' => [
            0 => 'firstName',
            1 => 'lastName',
            2 => 'emailAddress'
          ],
          'view' => 'views/lead-capture/fields/field-list',
          'displayAsList' => true,
          'required' => true,
          'ignoreFieldList' => [
            0 => 'targetList',
            1 => 'targetLists',
            2 => 'acceptanceStatus',
            3 => 'acceptanceStatusMeetings',
            4 => 'acceptanceStatusCalls',
            5 => 'campaign',
            6 => 'source',
            7 => 'teams',
            8 => 'createdOpportunity',
            9 => 'createdAccount',
            10 => 'createdContact',
            11 => 'emailAddressIsOptedOut',
            12 => 'emailAddressIsInvalid',
            13 => 'phoneNumberIsOptedOut',
            14 => 'phoneNumberIsInvalid',
            15 => 'opportunityAmountCurrency',
            16 => 'originalEmail'
          ],
          'webFormFieldTypeList' => [
            0 => 'varchar',
            1 => 'email',
            2 => 'phone',
            3 => 'text',
            4 => 'personName',
            5 => 'enum',
            6 => 'multiEnum',
            7 => 'array',
            8 => 'checklist',
            9 => 'int',
            10 => 'float',
            11 => 'currency',
            12 => 'date',
            13 => 'datetime',
            14 => 'bool',
            15 => 'url',
            16 => 'urlMultiple',
            17 => 'address'
          ],
          'audited' => true
        ],
        'fieldParams' => [
          'type' => 'jsonObject',
          'audited' => true
        ],
        'duplicateCheck' => [
          'type' => 'bool',
          'default' => true,
          'audited' => true
        ],
        'optInConfirmation' => [
          'type' => 'bool',
          'audited' => true
        ],
        'optInConfirmationEmailTemplate' => [
          'type' => 'link',
          'audited' => true
        ],
        'optInConfirmationLifetime' => [
          'type' => 'int',
          'default' => 48,
          'min' => 1,
          'audited' => true
        ],
        'optInConfirmationSuccessMessage' => [
          'type' => 'text',
          'tooltip' => true,
          'audited' => true
        ],
        'createLeadBeforeOptInConfirmation' => [
          'type' => 'bool',
          'audited' => true
        ],
        'skipOptInConfirmationIfSubscribed' => [
          'type' => 'bool',
          'audited' => true
        ],
        'leadSource' => [
          'type' => 'enum',
          'customizationOptionsDisabled' => true,
          'optionsPath' => 'entityDefs.Lead.fields.source.options',
          'translation' => 'Lead.options.source',
          'default' => 'Web Site',
          'audited' => true
        ],
        'apiKey' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'readOnly' => true
        ],
        'formId' => [
          'type' => 'varchar',
          'maxLength' => 17,
          'readOnly' => true
        ],
        'formEnabled' => [
          'type' => 'bool',
          'audited' => true
        ],
        'formTitle' => [
          'type' => 'varchar',
          'maxLength' => 80
        ],
        'formTheme' => [
          'type' => 'enum',
          'maxLength' => 64,
          'view' => 'views/lead-capture/fields/form-theme',
          'translation' => 'Global.themes'
        ],
        'formText' => [
          'type' => 'text',
          'tooltip' => 'optInConfirmationSuccessMessage'
        ],
        'formSuccessText' => [
          'type' => 'text',
          'tooltip' => 'optInConfirmationSuccessMessage'
        ],
        'formSuccessRedirectUrl' => [
          'type' => 'url',
          'audited' => true
        ],
        'formLanguage' => [
          'type' => 'enum',
          'maxLength' => 5,
          'view' => 'views/preferences/fields/language',
          'audited' => true
        ],
        'formFrameAncestors' => [
          'type' => 'urlMultiple',
          'audited' => true
        ],
        'formCaptcha' => [
          'type' => 'bool',
          'audited' => true,
          'tooltip' => true
        ],
        'targetTeam' => [
          'type' => 'link',
          'audited' => true
        ],
        'exampleRequestUrl' => [
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true,
          'copyToClipboard' => true
        ],
        'exampleRequestMethod' => [
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true
        ],
        'exampleRequestPayload' => [
          'type' => 'text',
          'notStorable' => true,
          'readOnly' => true,
          'seeMoreDisabled' => true
        ],
        'exampleRequestHeaders' => [
          'type' => 'array',
          'notStorable' => true,
          'readOnly' => true
        ],
        'formUrl' => [
          'type' => 'url',
          'notStorable' => true,
          'readOnly' => true,
          'copyToClipboard' => true
        ],
        'inboundEmail' => [
          'type' => 'link',
          'audited' => true
        ],
        'smtpAccount' => [
          'type' => 'base',
          'notStorable' => true,
          'view' => 'views/lead-capture/fields/smtp-account'
        ],
        'phoneNumberCountry' => [
          'type' => 'enum',
          'view' => 'views/lead-capture/fields/phone-number-country',
          'maxLength' => 2
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'targetList' => [
          'type' => 'belongsTo',
          'entity' => 'TargetList'
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign'
        ],
        'targetTeam' => [
          'type' => 'belongsTo',
          'entity' => 'Team'
        ],
        'inboundEmail' => [
          'type' => 'belongsTo',
          'entity' => 'InboundEmail'
        ],
        'optInConfirmationEmailTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'EmailTemplate'
        ],
        'logRecords' => [
          'type' => 'hasMany',
          'entity' => 'LeadCaptureLogRecord',
          'foreign' => 'leadCapture'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'LeadCaptureLogRecord' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'index' => true,
          'readOnly' => true
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'isCreated' => [
          'type' => 'bool'
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'leadCapture' => [
          'type' => 'link'
        ],
        'target' => [
          'type' => 'linkParent'
        ]
      ],
      'links' => [
        'leadCapture' => [
          'type' => 'belongsTo',
          'entity' => 'LeadCapture',
          'foreign' => 'logRecords'
        ],
        'target' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Contact',
            1 => 'Lead'
          ]
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'sortBy' => 'number',
        'asc' => false
      ]
    ],
    'MassAction' => [
      'fields' => [
        'entityType' => [
          'type' => 'varchar',
          'required' => true
        ],
        'action' => [
          'type' => 'varchar',
          'required' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Pending',
            1 => 'Running',
            2 => 'Success',
            3 => 'Failed'
          ],
          'default' => 'Pending'
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'params' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'required' => true
        ],
        'processedCount' => [
          'type' => 'int'
        ],
        'notifyOnFinish' => [
          'type' => 'bool',
          'default' => false
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'NextNumber' => [
      'fields' => [
        'entityType' => [
          'type' => 'varchar',
          'index' => true,
          'maxLength' => 100
        ],
        'fieldName' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'value' => [
          'type' => 'int',
          'default' => 1
        ]
      ],
      'indexes' => [
        'entityTypeFieldName' => [
          'columns' => [
            0 => 'entityType',
            1 => 'fieldName'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'Note' => [
      'fields' => [
        'post' => [
          'type' => 'text',
          'rows' => 100000,
          'view' => 'views/note/fields/post',
          'preview' => true,
          'attachmentField' => 'attachments',
          'customizationDefaultDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationTooltipTextDisabled' => true,
          'customizationSeeMoreDisabledDisabled' => true,
          'customizationRowsDisabled' => true,
          'customizationDisplayRawTextDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'dynamicLogicDisabled' => true
        ],
        'data' => [
          'type' => 'jsonObject',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'type' => [
          'type' => 'enum',
          'readOnly' => true,
          'view' => 'views/note/fields/type',
          'options' => [
            0 => 'Post',
            1 => 'Create',
            2 => 'CreateRelated',
            3 => 'Update',
            4 => 'Assign',
            5 => 'Relate',
            6 => 'Unrelate',
            7 => 'EmailReceived',
            8 => 'EmailSent'
          ],
          'maxLength' => 24,
          'customizationDisabled' => true,
          'default' => 'Post'
        ],
        'targetType' => [
          'type' => 'enum',
          'options' => [
            0 => 'self',
            1 => 'all',
            2 => 'teams',
            3 => 'users',
            4 => 'portals'
          ],
          'maxLength' => 7,
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'parent' => [
          'type' => 'linkParent',
          'customizationDisabled' => true,
          'view' => 'views/note/fields/parent',
          'readOnlyAfterCreate' => true
        ],
        'related' => [
          'type' => 'linkParent',
          'readOnly' => true,
          'customizationDisabled' => true,
          'view' => 'views/note/fields/related'
        ],
        'attachments' => [
          'type' => 'attachmentMultiple',
          'view' => 'views/stream/fields/attachment-multiple',
          'customizationRequiredDisabled' => true,
          'customizationPreviewSizeDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationTooltipTextDisabled' => true,
          'dynamicLogicDisabled' => true
        ],
        'number' => [
          'type' => 'autoincrement',
          'index' => true,
          'dbType' => 'bigint',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'noLoad' => true,
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'portals' => [
          'type' => 'linkMultiple',
          'noLoad' => true,
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'users' => [
          'type' => 'linkMultiple',
          'noLoad' => true,
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'isGlobal' => [
          'type' => 'bool',
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'createdByGender' => [
          'type' => 'foreign',
          'link' => 'createdBy',
          'field' => 'gender',
          'customizationDisabled' => true
        ],
        'notifiedUserIdList' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'utility' => true,
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'isInternal' => [
          'type' => 'bool',
          'customizationDisabled' => true,
          'readOnlyAfterCreate' => true
        ],
        'isPinned' => [
          'type' => 'bool',
          'customizationDisabled' => true,
          'readOnly' => true
        ],
        'reactionCounts' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'readOnly' => true,
          'customizationDisabled' => true,
          'utility' => true
        ],
        'myReactions' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'readOnly' => true,
          'customizationDisabled' => true,
          'utility' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationDisabled' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationDisabled' => true,
          'view' => 'views/fields/user'
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationDisabled' => true,
          'view' => 'views/fields/user'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'attachments' => [
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'relationName' => 'attachments',
          'foreign' => 'parent'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'foreign' => 'notes'
        ],
        'superParent' => [
          'type' => 'belongsToParent'
        ],
        'related' => [
          'type' => 'belongsToParent'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'notes'
        ],
        'portals' => [
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'notes'
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'notes'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'post'
        ],
        'fullTextSearch' => true,
        'fullTextSearchOrderType' => 'original',
        'sortBy' => 'number',
        'asc' => false
      ],
      'statusStyles' => [
        'Lead' => [],
        'Case' => [],
        'Opportunity' => [],
        'Task' => []
      ],
      'indexes' => [
        'createdAt' => [
          'type' => 'index',
          'columns' => [
            0 => 'createdAt'
          ]
        ],
        'createdByNumber' => [
          'columns' => [
            0 => 'createdById',
            1 => 'number'
          ]
        ],
        'type' => [
          'type' => 'index',
          'columns' => [
            0 => 'type'
          ]
        ],
        'targetType' => [
          'type' => 'index',
          'columns' => [
            0 => 'targetType'
          ]
        ],
        'parentId' => [
          'type' => 'index',
          'columns' => [
            0 => 'parentId'
          ]
        ],
        'parentType' => [
          'type' => 'index',
          'columns' => [
            0 => 'parentType'
          ]
        ],
        'relatedId' => [
          'type' => 'index',
          'columns' => [
            0 => 'relatedId'
          ]
        ],
        'relatedType' => [
          'type' => 'index',
          'columns' => [
            0 => 'relatedType'
          ]
        ],
        'superParentType' => [
          'type' => 'index',
          'columns' => [
            0 => 'superParentType'
          ]
        ],
        'superParentId' => [
          'type' => 'index',
          'columns' => [
            0 => 'superParentId'
          ]
        ]
      ]
    ],
    'Notification' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'dbType' => 'bigint',
          'index' => true
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'noteData' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'orderDisabled' => true
        ],
        'type' => [
          'type' => 'varchar'
        ],
        'read' => [
          'type' => 'bool'
        ],
        'emailIsProcessed' => [
          'type' => 'bool'
        ],
        'user' => [
          'type' => 'link'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'message' => [
          'type' => 'text'
        ],
        'related' => [
          'type' => 'linkParent',
          'readOnly' => true
        ],
        'relatedParent' => [
          'type' => 'linkParent',
          'readOnly' => true
        ],
        'actionId' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'readOnly' => true,
          'index' => true
        ],
        'groupedCount' => [
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'utility' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User',
          'noJoin' => true
        ],
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'related' => [
          'type' => 'belongsToParent'
        ],
        'relatedParent' => [
          'type' => 'belongsToParent'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'sortBy' => 'number',
        'asc' => false
      ],
      'indexes' => [
        'createdAt' => [
          'type' => 'index',
          'columns' => [
            0 => 'createdAt'
          ]
        ],
        'user' => [
          'type' => 'index',
          'columns' => [
            0 => 'userId',
            1 => 'number'
          ]
        ],
        'userIdReadRelatedParentType' => [
          'type' => 'index',
          'columns' => [
            0 => 'userId',
            1 => 'deleted',
            2 => 'read',
            3 => 'relatedParentType'
          ]
        ]
      ]
    ],
    'OAuthAccount' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'provider' => [
          'type' => 'link',
          'required' => true,
          'readOnlyAfterCreate' => true
        ],
        'user' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'hasAccessToken' => [
          'type' => 'bool',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'select' => [
            'select' => 'IS_NOT_NULL:(accessToken)'
          ]
        ],
        'providerIsActive' => [
          'type' => 'foreign',
          'link' => 'provider',
          'field' => 'isActive'
        ],
        'data' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'directAccessDisabled' => true,
          'readOnly' => true
        ],
        'accessToken' => [
          'type' => 'password',
          'readOnly' => true,
          'dbType' => 'text'
        ],
        'refreshToken' => [
          'type' => 'password',
          'readOnly' => true,
          'dbType' => 'text'
        ],
        'description' => [
          'type' => 'text'
        ],
        'expiresAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'provider' => [
          'type' => 'belongsTo',
          'entity' => 'OAuthProvider',
          'foreign' => 'accounts'
        ],
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'OAuthProvider' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true
        ],
        'clientId' => [
          'type' => 'varchar',
          'maxLength' => 150
        ],
        'clientSecret' => [
          'type' => 'password',
          'maxLength' => 512,
          'dbType' => 'text'
        ],
        'authorizationEndpoint' => [
          'type' => 'url',
          'maxLength' => 512,
          'dbType' => 'text',
          'strip' => false
        ],
        'tokenEndpoint' => [
          'type' => 'url',
          'maxLength' => 512,
          'dbType' => 'text',
          'strip' => false
        ],
        'authorizationRedirectUri' => [
          'type' => 'url',
          'notStorable' => true,
          'readOnly' => true,
          'copyToClipboard' => true,
          'directAccessDisabled' => true
        ],
        'authorizationPrompt' => [
          'type' => 'enum',
          'default' => 'none',
          'options' => [
            0 => 'none',
            1 => 'consent',
            2 => 'login',
            3 => 'select_account'
          ],
          'maxLength' => 14
        ],
        'scopes' => [
          'type' => 'array',
          'noEmptyString' => true,
          'allowCustomOptions' => true,
          'storeArrayValues' => false,
          'displayAsList' => true,
          'maxItemLength' => 255
        ],
        'authorizationParams' => [
          'type' => 'jsonObject',
          'view' => 'views/o-auth-provider/fields/authorization-params',
          'tooltip' => true
        ],
        'scopeSeparator' => [
          'type' => 'varchar',
          'maxLength' => 1
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'OAuthAccount',
          'foreign' => 'provider',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'PasswordChangeRequest' => [
      'fields' => [
        'requestId' => [
          'type' => 'varchar',
          'maxLength' => 64,
          'index' => true
        ],
        'user' => [
          'type' => 'link',
          'readOnly' => true,
          'index' => true
        ],
        'url' => [
          'type' => 'url'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'PhoneNumber' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 36,
          'index' => true
        ],
        'type' => [
          'type' => 'enum'
        ],
        'numeric' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'index' => true
        ],
        'invalid' => [
          'type' => 'bool'
        ],
        'optOut' => [
          'type' => 'bool'
        ],
        'primary' => [
          'type' => 'bool',
          'notStorable' => true
        ]
      ],
      'links' => [],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ],
      'hooksDisabled' => true
    ],
    'Portal' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters'
        ],
        'logo' => [
          'type' => 'image'
        ],
        'url' => [
          'type' => 'url',
          'notStorable' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Classes\\FieldProcessing\\Portal\\UrlLoader'
        ],
        'customId' => [
          'type' => 'varchar',
          'maxLength' => 36,
          'view' => 'views/portal/fields/custom-id',
          'index' => true
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true
        ],
        'isDefault' => [
          'type' => 'bool',
          'default' => false,
          'notStorable' => true
        ],
        'portalRoles' => [
          'type' => 'linkMultiple'
        ],
        'tabList' => [
          'type' => 'array',
          'view' => 'views/portal/fields/tab-list',
          'validationList' => [
            0 => 'array',
            1 => 'required'
          ],
          'suppressValidationList' => [
            0 => 'arrayOfString'
          ],
          'doNotStoreArrayValues' => true
        ],
        'quickCreateList' => [
          'type' => 'array',
          'translation' => 'Global.scopeNames',
          'view' => 'views/portal/fields/quick-create-list'
        ],
        'applicationName' => [
          'type' => 'varchar'
        ],
        'companyLogo' => [
          'type' => 'image'
        ],
        'theme' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/theme',
          'translation' => 'Global.themes'
        ],
        'themeParams' => [
          'type' => 'jsonObject'
        ],
        'language' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/language'
        ],
        'timeZone' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/time-zone'
        ],
        'dateFormat' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/date-format'
        ],
        'timeFormat' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/time-format'
        ],
        'weekStart' => [
          'type' => 'enumInt',
          'options' => [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6
          ],
          'default' => -1,
          'view' => 'views/preferences/fields/week-start'
        ],
        'defaultCurrency' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/default-currency'
        ],
        'dashboardLayout' => [
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout'
        ],
        'dashletsOptions' => [
          'type' => 'jsonObject',
          'utility' => true
        ],
        'customUrl' => [
          'type' => 'url'
        ],
        'layoutSet' => [
          'type' => 'link',
          'tooltip' => true
        ],
        'authenticationProvider' => [
          'type' => 'link'
        ],
        'authTokenLifetime' => [
          'type' => 'float',
          'min' => 0,
          'tooltip' => 'Settings.authTokenMaxIdleTime'
        ],
        'authTokenMaxIdleTime' => [
          'type' => 'float',
          'min' => 0,
          'tooltip' => 'Settings.authTokenMaxIdleTime'
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'portals'
        ],
        'portalRoles' => [
          'type' => 'hasMany',
          'entity' => 'PortalRole',
          'foreign' => 'portals'
        ],
        'notes' => [
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'portals'
        ],
        'layoutSet' => [
          'type' => 'belongsTo',
          'entity' => 'LayoutSet',
          'foreign' => 'portals'
        ],
        'authenticationProvider' => [
          'type' => 'belongsTo',
          'entity' => 'AuthenticationProvider'
        ],
        'articles' => [
          'type' => 'hasMany',
          'entity' => 'KnowledgeBaseArticle',
          'foreign' => 'portals'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'PortalRole' => [
      'fields' => [
        'name' => [
          'maxLength' => 150,
          'required' => true,
          'type' => 'varchar',
          'pattern' => '$noBadCharacters'
        ],
        'data' => [
          'type' => 'jsonObject',
          'audited' => true
        ],
        'fieldData' => [
          'type' => 'jsonObject',
          'audited' => true
        ],
        'exportPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => 'Role.exportPermission',
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'massUpdatePermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => 'Role.massUpdatePermission',
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'portalRoles'
        ],
        'portals' => [
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'portalRoles'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'Preferences' => [
      'fields' => [
        'timeZone' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/time-zone'
        ],
        'dateFormat' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/date-format'
        ],
        'timeFormat' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/time-format'
        ],
        'weekStart' => [
          'type' => 'enumInt',
          'options' => [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6
          ],
          'default' => -1,
          'view' => 'views/preferences/fields/week-start'
        ],
        'defaultCurrency' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/default-currency'
        ],
        'thousandSeparator' => [
          'type' => 'varchar',
          'default' => ',',
          'maxLength' => 1,
          'view' => 'views/settings/fields/thousand-separator',
          'options' => [
            0 => '.',
            1 => ',',
            2 => '\''
          ],
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Settings\\ThousandSeparator\\Valid'
          ]
        ],
        'decimalMark' => [
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
          'maxLength' => 1,
          'options' => [
            0 => '.',
            1 => ','
          ]
        ],
        'dashboardLayout' => [
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout'
        ],
        'dashletsOptions' => [
          'type' => 'jsonObject'
        ],
        'dashboardLocked' => [
          'type' => 'bool'
        ],
        'importParams' => [
          'type' => 'jsonObject'
        ],
        'sharedCalendarUserList' => [
          'type' => 'jsonArray'
        ],
        'calendarViewDataList' => [
          'type' => 'jsonArray'
        ],
        'presetFilters' => [
          'type' => 'jsonObject'
        ],
        'language' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/language'
        ],
        'exportDelimiter' => [
          'type' => 'varchar',
          'default' => ',',
          'required' => true,
          'maxLength' => 1,
          'options' => [
            0 => ',',
            1 => ';',
            2 => '\\t',
            3 => '|'
          ]
        ],
        'receiveAssignmentEmailNotifications' => [
          'type' => 'bool',
          'default' => true
        ],
        'receiveMentionEmailNotifications' => [
          'type' => 'bool',
          'default' => true
        ],
        'receiveStreamEmailNotifications' => [
          'type' => 'bool',
          'default' => true
        ],
        'assignmentNotificationsIgnoreEntityTypeList' => [
          'type' => 'checklist',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/preferences/fields/assignment-notifications-ignore-entity-type-list',
          'default' => []
        ],
        'assignmentEmailNotificationsIgnoreEntityTypeList' => [
          'type' => 'checklist',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/preferences/fields/assignment-email-notifications-ignore-entity-type-list'
        ],
        'reactionNotifications' => [
          'type' => 'bool',
          'default' => true
        ],
        'reactionNotificationsNotFollowed' => [
          'type' => 'bool',
          'default' => false
        ],
        'autoFollowEntityTypeList' => [
          'type' => 'multiEnum',
          'view' => 'views/preferences/fields/auto-follow-entity-type-list',
          'translation' => 'Global.scopeNamesPlural',
          'notStorable' => true,
          'tooltip' => true
        ],
        'signature' => [
          'type' => 'wysiwyg',
          'view' => 'views/preferences/fields/signature'
        ],
        'defaultReminders' => [
          'type' => 'jsonArray',
          'view' => 'crm:views/meeting/fields/reminders',
          'default' => [],
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\Valid',
            1 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\MaxCount'
          ]
        ],
        'defaultRemindersTask' => [
          'type' => 'jsonArray',
          'view' => 'crm:views/meeting/fields/reminders',
          'default' => [],
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\Valid',
            1 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\MaxCount'
          ]
        ],
        'theme' => [
          'type' => 'enum',
          'view' => 'views/preferences/fields/theme',
          'translation' => 'Global.themes'
        ],
        'themeParams' => [
          'type' => 'jsonObject'
        ],
        'pageContentWidth' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Wide'
          ]
        ],
        'useCustomTabList' => [
          'type' => 'bool',
          'default' => false
        ],
        'addCustomTabs' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'tabList' => [
          'type' => 'array',
          'view' => 'views/preferences/fields/tab-list',
          'validationList' => [
            0 => 'array',
            1 => 'required'
          ],
          'mandatoryValidationList' => [
            0 => 'array'
          ]
        ],
        'emailReplyToAllByDefault' => [
          'type' => 'bool',
          'default' => true
        ],
        'emailReplyForceHtml' => [
          'type' => 'bool',
          'default' => true
        ],
        'isPortalUser' => [
          'type' => 'bool',
          'notStorable' => true
        ],
        'doNotFillAssignedUserIfNotRequired' => [
          'type' => 'bool',
          'tooltip' => true,
          'default' => true
        ],
        'followEntityOnStreamPost' => [
          'type' => 'bool',
          'default' => true
        ],
        'followCreatedEntities' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'followCreatedEntityTypeList' => [
          'type' => 'multiEnum',
          'view' => 'views/preferences/fields/auto-follow-entity-type-list',
          'translation' => 'Global.scopeNamesPlural',
          'default' => [],
          'tooltip' => true
        ],
        'followAsCollaborator' => [
          'type' => 'bool',
          'default' => true
        ],
        'emailUseExternalClient' => [
          'type' => 'bool',
          'default' => false
        ],
        'scopeColorsDisabled' => [
          'type' => 'bool',
          'default' => false
        ],
        'tabColorsDisabled' => [
          'type' => 'bool',
          'default' => false
        ],
        'textSearchStoringDisabled' => [
          'type' => 'bool',
          'default' => false
        ],
        'calendarSlotDuration' => [
          'type' => 'enumInt',
          'options' => [
            0 => '',
            1 => 15,
            2 => 30
          ],
          'default' => NULL,
          'view' => 'views/preferences/fields/calendar-slot-duration'
        ],
        'calendarScrollHour' => [
          'type' => 'enumInt',
          'options' => [
            0 => '',
            1 => 0,
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 4,
            6 => 5,
            7 => 6,
            8 => 7,
            9 => 8,
            10 => 9,
            11 => 10,
            12 => 11,
            13 => 12,
            14 => 14,
            15 => 15
          ],
          'default' => NULL,
          'view' => 'views/preferences/fields/calendar-scroll-hour'
        ]
      ],
      'noDeletedAttribute' => true,
      'modifierClassName' => 'Espo\\Core\\Utils\\Database\\Schema\\EntityDefsModifiers\\JsonData'
    ],
    'Role' => [
      'fields' => [
        'name' => [
          'maxLength' => 150,
          'required' => true,
          'type' => 'varchar',
          'pattern' => '$noBadCharacters'
        ],
        'info' => [
          'type' => 'base',
          'orderDisabled' => true,
          'notStorable' => true,
          'readOnly' => true,
          'view' => 'views/role/fields/info'
        ],
        'assignmentPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'userPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'messagePermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'portalPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'groupEmailAccountPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'exportPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'massUpdatePermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'dataPrivacyPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'followerManagementPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'auditPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'yes',
            2 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'mentionPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'userCalendarPermission' => [
          'type' => 'enum',
          'options' => [
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no'
          ],
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
          'view' => 'views/role/fields/permission',
          'audited' => true
        ],
        'data' => [
          'type' => 'jsonObject',
          'audited' => true
        ],
        'fieldData' => [
          'type' => 'jsonObject',
          'audited' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'roles'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'roles'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'ScheduledJob' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'job' => [
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/scheduled-job/fields/job'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'default' => 'Active',
          'style' => [
            'Inactive' => 'info'
          ],
          'audited' => true
        ],
        'scheduling' => [
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/scheduled-job/fields/scheduling',
          'tooltip' => true,
          'audited' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\ScheduledJob\\Scheduling\\Valid'
          ]
        ],
        'lastRun' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'isInternal' => [
          'type' => 'bool',
          'readOnly' => true,
          'disabled' => true,
          'default' => false
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'log' => [
          'type' => 'hasMany',
          'entity' => 'ScheduledJobLogRecord',
          'foreign' => 'scheduledJob'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ],
      'jobSchedulingMap' => [
        'CheckInboundEmails' => '*/2 * * * *',
        'CheckEmailAccounts' => '*/1 * * * *',
        'SendEmailReminders' => '*/2 * * * *',
        'Cleanup' => '1 1 * * 0',
        'AuthTokenControl' => '*/6 * * * *',
        'SendEmailNotifications' => '*/2 * * * *',
        'ProcessWebhookQueue' => '*/2 * * * *',
        'SendScheduledEmails' => '*/10 * * * *',
        'ProcessMassEmail' => '10,30,50 * * * *',
        'ControlKnowledgeBaseArticleStatus' => '10 1 * * *'
      ],
      'jobs' => [
        'SubmitPopupReminders' => [
          'name' => 'Submit Popup Reminders',
          'isSystem' => true,
          'scheduling' => '* * * * *'
        ]
      ]
    ],
    'ScheduledJobLogRecord' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'readOnly' => true
        ],
        'status' => [
          'type' => 'enum',
          'readOnly' => true,
          'options' => [
            0 => 'Success',
            1 => 'Failed'
          ],
          'style' => [
            'Success' => 'success',
            'Failed' => 'danger'
          ]
        ],
        'executionTime' => [
          'type' => 'datetime',
          'readOnly' => true,
          'hasSeconds' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'scheduledJob' => [
          'type' => 'link'
        ],
        'target' => [
          'type' => 'linkParent'
        ]
      ],
      'links' => [
        'scheduledJob' => [
          'type' => 'belongsTo',
          'entity' => 'ScheduledJob'
        ]
      ],
      'collection' => [
        'orderBy' => 'executionTime',
        'order' => 'desc',
        'sortBy' => 'executionTime',
        'asc' => false
      ],
      'indexes' => [
        'scheduledJobIdExecutionTime' => [
          'type' => 'index',
          'columns' => [
            0 => 'scheduledJobId',
            1 => 'executionTime'
          ]
        ]
      ]
    ],
    'Settings' => [
      'skipRebuild' => true,
      'fields' => [
        'useCache' => [
          'type' => 'bool',
          'default' => true,
          'tooltip' => true
        ],
        'recordsPerPage' => [
          'type' => 'int',
          'min' => 1,
          'max' => 200,
          'default' => 20,
          'required' => true,
          'tooltip' => true
        ],
        'recordsPerPageSmall' => [
          'type' => 'int',
          'min' => 1,
          'max' => 100,
          'default' => 5,
          'required' => true,
          'tooltip' => true
        ],
        'recordsPerPageSelect' => [
          'type' => 'int',
          'min' => 1,
          'max' => 100,
          'default' => 10,
          'required' => true,
          'tooltip' => true
        ],
        'recordsPerPageKanban' => [
          'type' => 'int',
          'min' => 1,
          'max' => 100,
          'required' => true,
          'tooltip' => true
        ],
        'timeZone' => [
          'type' => 'enum',
          'default' => 'UTC',
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
            331 => 'Europe/Kyiv',
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
            357 => 'Europe/Vaduz',
            358 => 'Europe/Vatican',
            359 => 'Europe/Vienna',
            360 => 'Europe/Vilnius',
            361 => 'Europe/Volgograd',
            362 => 'Europe/Warsaw',
            363 => 'Europe/Zagreb',
            364 => 'Europe/Zurich',
            365 => 'Indian/Antananarivo',
            366 => 'Indian/Chagos',
            367 => 'Indian/Christmas',
            368 => 'Indian/Cocos',
            369 => 'Indian/Comoro',
            370 => 'Indian/Kerguelen',
            371 => 'Indian/Mahe',
            372 => 'Indian/Maldives',
            373 => 'Indian/Mauritius',
            374 => 'Indian/Mayotte',
            375 => 'Indian/Reunion',
            376 => 'Pacific/Apia',
            377 => 'Pacific/Auckland',
            378 => 'Pacific/Chatham',
            379 => 'Pacific/Chuuk',
            380 => 'Pacific/Easter',
            381 => 'Pacific/Efate',
            382 => 'Pacific/Enderbury',
            383 => 'Pacific/Fakaofo',
            384 => 'Pacific/Fiji',
            385 => 'Pacific/Funafuti',
            386 => 'Pacific/Galapagos',
            387 => 'Pacific/Gambier',
            388 => 'Pacific/Guadalcanal',
            389 => 'Pacific/Guam',
            390 => 'Pacific/Honolulu',
            391 => 'Pacific/Johnston',
            392 => 'Pacific/Kiritimati',
            393 => 'Pacific/Kosrae',
            394 => 'Pacific/Kwajalein',
            395 => 'Pacific/Majuro',
            396 => 'Pacific/Marquesas',
            397 => 'Pacific/Midway',
            398 => 'Pacific/Nauru',
            399 => 'Pacific/Niue',
            400 => 'Pacific/Norfolk',
            401 => 'Pacific/Noumea',
            402 => 'Pacific/Pago_Pago',
            403 => 'Pacific/Palau',
            404 => 'Pacific/Pitcairn',
            405 => 'Pacific/Pohnpei',
            406 => 'Pacific/Port_Moresby',
            407 => 'Pacific/Rarotonga',
            408 => 'Pacific/Saipan',
            409 => 'Pacific/Tahiti',
            410 => 'Pacific/Tarawa',
            411 => 'Pacific/Tongatapu',
            412 => 'Pacific/Wake',
            413 => 'Pacific/Wallis'
          ],
          'view' => 'views/settings/fields/time-zone'
        ],
        'dateFormat' => [
          'type' => 'enum',
          'default' => 'DD.MM.YYYY',
          'view' => 'views/settings/fields/date-format'
        ],
        'timeFormat' => [
          'type' => 'enum',
          'default' => 'HH:mm',
          'view' => 'views/settings/fields/time-format'
        ],
        'weekStart' => [
          'type' => 'enumInt',
          'options' => [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6
          ],
          'default' => 0,
          'translation' => 'Global.lists.dayNames'
        ],
        'fiscalYearShift' => [
          'type' => 'enumInt',
          'default' => 0,
          'view' => 'views/settings/fields/fiscal-year-shift'
        ],
        'thousandSeparator' => [
          'type' => 'varchar',
          'default' => ',',
          'maxLength' => 1,
          'view' => 'views/settings/fields/thousand-separator',
          'options' => [
            0 => '.',
            1 => ',',
            2 => '\''
          ],
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Settings\\ThousandSeparator\\Valid'
          ]
        ],
        'decimalMark' => [
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
          'maxLength' => 1,
          'options' => [
            0 => '.',
            1 => ','
          ]
        ],
        'currencyList' => [
          'type' => 'multiEnum',
          'default' => [
            0 => 'USD',
            1 => 'EUR'
          ],
          'required' => true,
          'view' => 'views/settings/fields/currency-list',
          'tooltip' => true
        ],
        'defaultCurrency' => [
          'type' => 'enum',
          'default' => 'USD',
          'required' => true,
          'view' => 'views/settings/fields/default-currency'
        ],
        'baseCurrency' => [
          'type' => 'enum',
          'default' => 'USD',
          'required' => true,
          'view' => 'views/settings/fields/default-currency'
        ],
        'currencyRates' => [
          'type' => 'base'
        ],
        'outboundEmailIsShared' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'outboundEmailFromName' => [
          'type' => 'varchar',
          'default' => 'EspoCRM'
        ],
        'outboundEmailFromAddress' => [
          'type' => 'varchar',
          'default' => 'crm@example.com',
          'tooltip' => true,
          'view' => 'views/settings/fields/outbound-email-from-address'
        ],
        'emailAddressLookupEntityTypeList' => [
          'type' => 'multiEnum',
          'tooltip' => true,
          'view' => 'views/settings/fields/email-address-lookup-entity-type-list'
        ],
        'emailAddressSelectEntityTypeList' => [
          'type' => 'multiEnum',
          'tooltip' => true,
          'view' => 'views/settings/fields/email-address-lookup-entity-type-list'
        ],
        'smtpServer' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'smtpPort' => [
          'type' => 'int',
          'min' => 0,
          'max' => 65535,
          'default' => 587
        ],
        'smtpAuth' => [
          'type' => 'bool'
        ],
        'smtpSecurity' => [
          'type' => 'enum',
          'default' => 'TLS',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'smtpUsername' => [
          'type' => 'varchar'
        ],
        'smtpPassword' => [
          'type' => 'password'
        ],
        'tabList' => [
          'type' => 'array',
          'view' => 'views/settings/fields/tab-list',
          'validationList' => [
            0 => 'array',
            1 => 'required'
          ],
          'mandatoryValidationList' => [
            0 => 'array'
          ]
        ],
        'quickCreateList' => [
          'type' => 'array',
          'translation' => 'Global.scopeNames',
          'view' => 'views/settings/fields/quick-create-list'
        ],
        'language' => [
          'type' => 'enum',
          'default' => 'en_US',
          'view' => 'views/settings/fields/language',
          'isSorted' => true
        ],
        'globalSearchEntityList' => [
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNames',
          'view' => 'views/settings/fields/global-search-entity-list',
          'tooltip' => true
        ],
        'exportDelimiter' => [
          'type' => 'varchar',
          'default' => ',',
          'required' => true,
          'maxLength' => 1
        ],
        'companyLogo' => [
          'type' => 'image'
        ],
        'authenticationMethod' => [
          'type' => 'enum',
          'default' => 'Espo',
          'view' => 'views/settings/fields/authentication-method'
        ],
        'auth2FA' => [
          'type' => 'bool'
        ],
        'auth2FAMethodList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/auth-two-fa-method-list'
        ],
        'auth2FAForced' => [
          'type' => 'bool'
        ],
        'auth2FAInPortal' => [
          'type' => 'bool'
        ],
        'passwordRecoveryDisabled' => [
          'type' => 'bool'
        ],
        'passwordRecoveryForAdminDisabled' => [
          'type' => 'bool'
        ],
        'passwordRecoveryForInternalUsersDisabled' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'passwordRecoveryNoExposure' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'passwordGenerateLength' => [
          'type' => 'int',
          'min' => 6,
          'max' => 150,
          'required' => true
        ],
        'passwordStrengthLength' => [
          'type' => 'int',
          'max' => 150,
          'min' => 1
        ],
        'passwordStrengthLetterCount' => [
          'type' => 'int',
          'max' => 150,
          'min' => 0
        ],
        'passwordStrengthNumberCount' => [
          'type' => 'int',
          'max' => 150,
          'min' => 0
        ],
        'passwordStrengthSpecialCharacterCount' => [
          'type' => 'int',
          'max' => 50,
          'min' => 0
        ],
        'passwordStrengthBothCases' => [
          'type' => 'bool'
        ],
        'ldapHost' => [
          'type' => 'varchar'
        ],
        'ldapPort' => [
          'type' => 'varchar',
          'default' => 389
        ],
        'ldapSecurity' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'SSL',
            2 => 'TLS'
          ]
        ],
        'ldapAuth' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'ldapUsername' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapPassword' => [
          'type' => 'password',
          'tooltip' => true
        ],
        'ldapBindRequiresDn' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'ldapUserLoginFilter' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapBaseDn' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapAccountCanonicalForm' => [
          'type' => 'enum',
          'options' => [
            0 => 'Dn',
            1 => 'Username',
            2 => 'Backslash',
            3 => 'Principal'
          ],
          'tooltip' => true
        ],
        'ldapAccountDomainName' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapAccountDomainNameShort' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapAccountFilterFormat' => [
          'type' => 'varchar'
        ],
        'ldapTryUsernameSplit' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'ldapOptReferrals' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'ldapPortalUserLdapAuth' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'ldapCreateEspoUser' => [
          'type' => 'bool',
          'default' => true,
          'tooltip' => true
        ],
        'ldapUserNameAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserObjectClass' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserFirstNameAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserLastNameAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserTitleAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserEmailAddressAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserPhoneNumberAttribute' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'ldapUserDefaultTeam' => [
          'type' => 'link',
          'tooltip' => true,
          'entity' => 'Team'
        ],
        'ldapUserTeams' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'entity' => 'Team'
        ],
        'ldapPortalUserPortals' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'entity' => 'Portal'
        ],
        'ldapPortalUserRoles' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'entity' => 'PortalRole'
        ],
        'exportDisabled' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'emailNotificationsDelay' => [
          'type' => 'int',
          'min' => 0,
          'max' => 18000,
          'tooltip' => true
        ],
        'assignmentEmailNotifications' => [
          'type' => 'bool',
          'default' => false
        ],
        'assignmentEmailNotificationsEntityList' => [
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/assignment-email-notifications-entity-list'
        ],
        'assignmentNotificationsEntityList' => [
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/assignment-notifications-entity-list'
        ],
        'postEmailNotifications' => [
          'type' => 'bool',
          'default' => false
        ],
        'updateEmailNotifications' => [
          'type' => 'bool',
          'default' => false
        ],
        'mentionEmailNotifications' => [
          'type' => 'bool',
          'default' => false
        ],
        'streamEmailNotifications' => [
          'type' => 'bool',
          'default' => false
        ],
        'portalStreamEmailNotifications' => [
          'type' => 'bool',
          'default' => true
        ],
        'streamEmailNotificationsEntityList' => [
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/stream-email-notifications-entity-list',
          'tooltip' => true
        ],
        'streamEmailNotificationsTypeList' => [
          'type' => 'multiEnum',
          'options' => [
            0 => 'Post',
            1 => 'Status',
            2 => 'EmailReceived'
          ]
        ],
        'streamEmailWithContentEntityTypeList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/stream-email-with-content-entity-type-list'
        ],
        'newNotificationCountInTitle' => [
          'type' => 'bool'
        ],
        'b2cMode' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'avatarsDisabled' => [
          'type' => 'bool',
          'default' => false
        ],
        'followCreatedEntities' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'adminPanelIframeUrl' => [
          'type' => 'varchar'
        ],
        'displayListViewRecordCount' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'userThemesDisabled' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'theme' => [
          'type' => 'enum',
          'view' => 'views/settings/fields/theme',
          'translation' => 'Global.themes'
        ],
        'themeParams' => [
          'type' => 'jsonObject'
        ],
        'attachmentUploadMaxSize' => [
          'type' => 'float',
          'min' => 0
        ],
        'attachmentUploadChunkSize' => [
          'type' => 'float',
          'min' => 0
        ],
        'emailMessageMaxSize' => [
          'type' => 'float',
          'min' => 0,
          'tooltip' => true
        ],
        'inboundEmailMaxPortionSize' => [
          'type' => 'int',
          'min' => 1,
          'max' => 500
        ],
        'personalEmailMaxPortionSize' => [
          'type' => 'int',
          'min' => 1,
          'max' => 500
        ],
        'maxEmailAccountCount' => [
          'type' => 'int'
        ],
        'massEmailMaxPerHourCount' => [
          'type' => 'int',
          'min' => 1,
          'required' => true
        ],
        'massEmailMaxPerBatchCount' => [
          'type' => 'int',
          'min' => 1
        ],
        'massEmailVerp' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'emailScheduledBatchCount' => [
          'type' => 'int',
          'min' => 1,
          'required' => true
        ],
        'authTokenLifetime' => [
          'type' => 'float',
          'min' => 0,
          'default' => 0,
          'tooltip' => true
        ],
        'authTokenMaxIdleTime' => [
          'type' => 'float',
          'min' => 0,
          'default' => 0,
          'tooltip' => true
        ],
        'authTokenPreventConcurrent' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'dashboardLayout' => [
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout'
        ],
        'dashletsOptions' => [
          'type' => 'jsonObject',
          'disabled' => true
        ],
        'siteUrl' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'applicationName' => [
          'type' => 'varchar'
        ],
        'readableDateFormatDisabled' => [
          'type' => 'bool'
        ],
        'addressFormat' => [
          'type' => 'enumInt',
          'options' => [
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4
          ]
        ],
        'addressPreview' => [
          'type' => 'address',
          'notStorable' => true,
          'readOnly' => true,
          'view' => 'views/settings/fields/address-preview'
        ],
        'personNameFormat' => [
          'type' => 'enum',
          'options' => [
            0 => 'firstLast',
            1 => 'lastFirst',
            2 => 'firstMiddleLast',
            3 => 'lastFirstMiddle'
          ]
        ],
        'currencyFormat' => [
          'type' => 'enumInt',
          'options' => [
            0 => 1,
            1 => 2,
            2 => 3
          ]
        ],
        'currencyDecimalPlaces' => [
          'type' => 'int',
          'tooltip' => true,
          'min' => 0,
          'max' => 20
        ],
        'notificationSoundsDisabled' => [
          'type' => 'bool'
        ],
        'calendarEntityList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/calendar-entity-list',
          'tooltip' => true
        ],
        'activitiesEntityList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/activities-entity-list',
          'tooltip' => true
        ],
        'historyEntityList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/history-entity-list',
          'tooltip' => true
        ],
        'busyRangesEntityList' => [
          'type' => 'multiEnum',
          'tooltip' => true,
          'view' => 'views/settings/fields/busy-ranges-entity-list'
        ],
        'googleMapsApiKey' => [
          'type' => 'varchar'
        ],
        'massEmailDisableMandatoryOptOutLink' => [
          'type' => 'bool'
        ],
        'massEmailOpenTracking' => [
          'type' => 'bool'
        ],
        'aclAllowDeleteCreated' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'lastViewedCount' => [
          'type' => 'int',
          'min' => 1,
          'max' => 200,
          'default' => 20,
          'required' => true
        ],
        'adminNotifications' => [
          'type' => 'bool'
        ],
        'adminNotificationsNewVersion' => [
          'type' => 'bool'
        ],
        'adminNotificationsNewExtensionVersion' => [
          'type' => 'bool'
        ],
        'textFilterUseContainsForVarchar' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'phoneNumberNumericSearch' => [
          'type' => 'bool'
        ],
        'phoneNumberInternational' => [
          'type' => 'bool'
        ],
        'phoneNumberExtensions' => [
          'type' => 'bool'
        ],
        'phoneNumberPreferredCountryList' => [
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/phone-number-preferred-country-list'
        ],
        'scopeColorsDisabled' => [
          'type' => 'bool'
        ],
        'tabColorsDisabled' => [
          'type' => 'bool'
        ],
        'tabIconsDisabled' => [
          'type' => 'bool'
        ],
        'emailAddressIsOptedOutByDefault' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'outboundEmailBccAddress' => [
          'type' => 'varchar',
          'view' => 'views/fields/email-address'
        ],
        'cleanupDeletedRecords' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'addressCityList' => [
          'type' => 'multiEnum',
          'tooltip' => true
        ],
        'addressStateList' => [
          'type' => 'multiEnum',
          'tooltip' => true
        ],
        'jobRunInParallel' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'jobMaxPortion' => [
          'type' => 'int',
          'tooltip' => true
        ],
        'jobPoolConcurrencyNumber' => [
          'type' => 'int',
          'tooltip' => true,
          'min' => 1
        ],
        'jobForceUtc' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'daemonInterval' => [
          'type' => 'int',
          'tooltip' => true
        ],
        'daemonMaxProcessNumber' => [
          'type' => 'int',
          'tooltip' => true,
          'min' => 1
        ],
        'daemonProcessTimeout' => [
          'type' => 'int',
          'tooltip' => true
        ],
        'cronDisabled' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'maintenanceMode' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'useWebSocket' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'awsS3Storage' => [
          'type' => 'jsonObject'
        ],
        'outboundSmsFromNumber' => [
          'type' => 'varchar'
        ],
        'smsProvider' => [
          'type' => 'enum',
          'view' => 'views/settings/fields/sms-provider'
        ],
        'workingTimeCalendar' => [
          'type' => 'link',
          'tooltip' => true,
          'entity' => 'WorkingTimeCalendar'
        ],
        'oidcClientId' => [
          'type' => 'varchar'
        ],
        'oidcClientSecret' => [
          'type' => 'password'
        ],
        'oidcAuthorizationEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcUserInfoEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcTokenEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcJwksEndpoint' => [
          'type' => 'url',
          'strip' => false
        ],
        'oidcJwtSignatureAlgorithmList' => [
          'type' => 'multiEnum',
          'options' => [
            0 => 'RS256',
            1 => 'RS384',
            2 => 'RS512',
            3 => 'HS256',
            4 => 'HS384',
            5 => 'HS512'
          ]
        ],
        'oidcScopes' => [
          'type' => 'multiEnum',
          'allowCustomOptions' => true,
          'options' => [
            0 => 'profile',
            1 => 'email',
            2 => 'phone',
            3 => 'address'
          ]
        ],
        'oidcGroupClaim' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'oidcCreateUser' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcUsernameClaim' => [
          'type' => 'varchar',
          'options' => [
            0 => 'sub',
            1 => 'preferred_username',
            2 => 'email'
          ],
          'tooltip' => true
        ],
        'oidcTeams' => [
          'type' => 'linkMultiple',
          'entity' => 'Team',
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'view' => 'views/settings/fields/oidc-teams',
          'tooltip' => true
        ],
        'oidcSync' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcSyncTeams' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcFallback' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'oidcAllowRegularUserFallback' => [
          'type' => 'bool'
        ],
        'oidcAllowAdminUser' => [
          'type' => 'bool'
        ],
        'oidcLogoutUrl' => [
          'type' => 'varchar',
          'tooltip' => true
        ],
        'oidcAuthorizationPrompt' => [
          'type' => 'enum',
          'options' => [
            0 => 'none',
            1 => 'consent',
            2 => 'login',
            3 => 'select_account'
          ]
        ],
        'pdfEngine' => [
          'type' => 'enum',
          'view' => 'views/settings/fields/pdf-engine'
        ],
        'quickSearchFullTextAppendWildcard' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'authIpAddressCheck' => [
          'type' => 'bool'
        ],
        'authIpAddressWhitelist' => [
          'type' => 'array',
          'allowCustomOptions' => true,
          'noEmptyString' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Settings\\AuthIpAddressWhitelist\\Valid'
          ],
          'tooltip' => true
        ],
        'authIpAddressCheckExcludedUsers' => [
          'type' => 'linkMultiple',
          'entity' => 'User',
          'tooltip' => true
        ],
        'availableReactions' => [
          'type' => 'array',
          'maxCount' => 9,
          'view' => 'views/settings/fields/available-reactions',
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\Settings\\AvailableReactions\\Valid'
          ]
        ],
        'baselineRole' => [
          'type' => 'link',
          'entity' => 'Role',
          'tooltip' => true,
          'view' => 'views/settings/fields/baseline-role'
        ],
        'addressPreviewStreet' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'addressPreview'
          ]
        ],
        'addressPreviewCity' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'addressPreview'
          ]
        ],
        'addressPreviewState' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'addressPreview'
          ]
        ],
        'addressPreviewCountry' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'addressPreview'
          ]
        ],
        'addressPreviewPostalCode' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'addressPreview'
          ]
        ],
        'addressPreviewMap' => [
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'map',
          'orderDisabled' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ]
      ]
    ],
    'Sms' => [
      'fields' => [
        'from' => [
          'type' => 'varchar',
          'notStorable' => true,
          'required' => true,
          'textFilterDisabled' => true
        ],
        'fromName' => [
          'type' => 'varchar'
        ],
        'to' => [
          'type' => 'varchar',
          'notStorable' => true,
          'required' => true,
          'textFilterDisabled' => true
        ],
        'fromPhoneNumber' => [
          'type' => 'link',
          'textFilterDisabled' => true
        ],
        'toPhoneNumbers' => [
          'type' => 'linkMultiple'
        ],
        'body' => [
          'type' => 'text'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Draft',
            1 => 'Sending',
            2 => 'Sent',
            3 => 'Archived',
            4 => 'Failed'
          ],
          'default' => 'Archived',
          'clientReadOnly' => true,
          'style' => [
            'Draft' => 'warning',
            'Failed' => 'danger',
            'Sending' => 'warning'
          ]
        ],
        'parent' => [
          'type' => 'linkParent'
        ],
        'dateSent' => [
          'type' => 'datetime'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'replied' => [
          'type' => 'link',
          'noJoin' => true,
          'readOnly' => true,
          'view' => 'views/email/fields/replied'
        ],
        'replies' => [
          'type' => 'linkMultiple',
          'readOnly' => true,
          'orderBy' => 'dateSent',
          'view' => 'views/email/fields/replies'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity'
          ],
          'foreign' => 'emails'
        ],
        'replied' => [
          'type' => 'belongsTo',
          'entity' => 'Sms',
          'foreign' => 'replies',
          'foreignName' => 'id'
        ],
        'replies' => [
          'type' => 'hasMany',
          'entity' => 'Sms',
          'foreign' => 'replied'
        ],
        'fromPhoneNumber' => [
          'type' => 'belongsTo',
          'entity' => 'PhoneNumber'
        ],
        'toPhoneNumbers' => [
          'type' => 'hasMany',
          'entity' => 'PhoneNumber',
          'relationName' => 'smsPhoneNumber',
          'conditions' => [
            'addressType' => 'to'
          ],
          'additionalColumns' => [
            'addressType' => [
              'type' => 'varchar',
              'len' => '4'
            ]
          ]
        ]
      ],
      'collection' => [
        'orderBy' => 'dateSent',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'body'
        ],
        'sortBy' => 'dateSent',
        'asc' => false
      ],
      'indexes' => [
        'dateSent' => [
          'columns' => [
            0 => 'dateSent',
            1 => 'deleted'
          ]
        ],
        'dateSentStatus' => [
          'columns' => [
            0 => 'dateSent',
            1 => 'status',
            2 => 'deleted'
          ]
        ]
      ]
    ],
    'StarSubscription' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'bigint',
          'autoincrement' => true
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'user' => [
          'type' => 'link'
        ],
        'createdAt' => [
          'type' => 'datetime'
        ]
      ],
      'indexes' => [
        'userEntity' => [
          'unique' => true,
          'columns' => [
            0 => 'userId',
            1 => 'entityId',
            2 => 'entityType'
          ]
        ],
        'userEntityType' => [
          'columns' => [
            0 => 'userId',
            1 => 'entityType'
          ]
        ]
      ]
    ],
    'StreamSubscription' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'bigint',
          'autoincrement' => true
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'user' => [
          'type' => 'link'
        ]
      ],
      'indexes' => [
        'userEntity' => [
          'columns' => [
            0 => 'userId',
            1 => 'entityId',
            2 => 'entityType'
          ]
        ]
      ]
    ],
    'SystemData' => [
      'fields' => [
        'id' => [
          'type' => 'id',
          'dbType' => 'string',
          'maxLength' => 1
        ],
        'lastPasswordRecoveryDate' => [
          'type' => 'datetime'
        ]
      ]
    ],
    'Team' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'audited' => true
        ],
        'roles' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'audited' => true
        ],
        'positionList' => [
          'type' => 'array',
          'displayAsList' => true,
          'tooltip' => true,
          'audited' => true
        ],
        'userRole' => [
          'type' => 'varchar',
          'notStorable' => true,
          'utility' => true
        ],
        'layoutSet' => [
          'type' => 'link',
          'tooltip' => true,
          'audited' => true
        ],
        'workingTimeCalendar' => [
          'type' => 'link',
          'tooltip' => true,
          'audited' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'teams',
          'columnAttributeMap' => [
            'role' => 'userRole'
          ],
          'apiSpecDisabled' => true
        ],
        'roles' => [
          'type' => 'hasMany',
          'entity' => 'Role',
          'foreign' => 'teams'
        ],
        'notes' => [
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'teams'
        ],
        'inboundEmails' => [
          'type' => 'hasMany',
          'entity' => 'InboundEmail',
          'foreign' => 'teams'
        ],
        'layoutSet' => [
          'type' => 'belongsTo',
          'entity' => 'LayoutSet',
          'foreign' => 'teams'
        ],
        'workingTimeCalendar' => [
          'type' => 'belongsTo',
          'entity' => 'WorkingTimeCalendar',
          'foreign' => 'teams'
        ],
        'groupEmailFolders' => [
          'type' => 'hasMany',
          'entity' => 'GroupEmailFolder',
          'foreign' => 'teams'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'Template' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'body' => [
          'type' => 'wysiwyg',
          'view' => 'views/template/fields/body'
        ],
        'header' => [
          'type' => 'wysiwyg',
          'view' => 'views/template/fields/body'
        ],
        'footer' => [
          'type' => 'wysiwyg',
          'view' => 'views/template/fields/body',
          'tooltip' => true
        ],
        'entityType' => [
          'type' => 'enum',
          'required' => true,
          'translation' => 'Global.scopeNames',
          'view' => 'views/template/fields/entity-type'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Active',
            1 => 'Inactive'
          ],
          'default' => 'Active',
          'style' => [
            'Inactive' => 'info'
          ],
          'maxLength' => 8
        ],
        'leftMargin' => [
          'type' => 'float',
          'default' => 10
        ],
        'rightMargin' => [
          'type' => 'float',
          'default' => 10
        ],
        'topMargin' => [
          'type' => 'float',
          'default' => 10
        ],
        'bottomMargin' => [
          'type' => 'float',
          'default' => 20
        ],
        'printFooter' => [
          'type' => 'bool',
          'inlineEditDisabled' => true
        ],
        'printHeader' => [
          'type' => 'bool',
          'inlineEditDisabled' => true
        ],
        'footerPosition' => [
          'type' => 'float',
          'default' => 10
        ],
        'headerPosition' => [
          'type' => 'float',
          'default' => 0
        ],
        'style' => [
          'type' => 'text',
          'view' => 'views/template/fields/style'
        ],
        'teams' => [
          'type' => 'linkMultiple'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'variables' => [
          'type' => 'base',
          'notStorable' => true,
          'tooltip' => true
        ],
        'pageOrientation' => [
          'type' => 'enum',
          'options' => [
            0 => 'Portrait',
            1 => 'Landscape'
          ],
          'default' => 'Portrait'
        ],
        'pageFormat' => [
          'type' => 'enum',
          'options' => [
            0 => 'A3',
            1 => 'A4',
            2 => 'A5',
            3 => 'A6',
            4 => 'A7',
            5 => 'Custom'
          ],
          'default' => 'A4'
        ],
        'pageWidth' => [
          'type' => 'float',
          'min' => 1
        ],
        'pageHeight' => [
          'type' => 'float',
          'min' => 1
        ],
        'fontFace' => [
          'type' => 'enum',
          'view' => 'views/template/fields/font-face'
        ],
        'title' => [
          'type' => 'varchar'
        ],
        'filename' => [
          'type' => 'varchar',
          'maxLength' => 150,
          'tooltip' => true
        ]
      ],
      'links' => [
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ],
      'optimisticConcurrencyControl' => true
    ],
    'TwoFactorCode' => [
      'fields' => [
        'code' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'method' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'attemptsLeft' => [
          'type' => 'int'
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'indexes' => [
        'createdAt' => [
          'columns' => [
            0 => 'createdAt'
          ]
        ],
        'userIdMethod' => [
          'columns' => [
            0 => 'userId',
            1 => 'method'
          ]
        ],
        'userIdMethodIsActive' => [
          'columns' => [
            0 => 'userId',
            1 => 'method',
            2 => 'isActive'
          ]
        ],
        'userIdMethodCreatedAt' => [
          'columns' => [
            0 => 'userId',
            1 => 'method',
            2 => 'createdAt'
          ]
        ]
      ]
    ],
    'UniqueId' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'index' => true
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'terminateAt' => [
          'type' => 'datetime'
        ],
        'target' => [
          'type' => 'linkParent'
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'target' => [
          'type' => 'belongsToParent'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'hooksDisabled' => true
    ],
    'User' => [
      'fields' => [
        'userName' => [
          'type' => 'varchar',
          'maxLength' => 50,
          'required' => true,
          'view' => 'views/user/fields/user-name',
          'tooltip' => true,
          'fieldManagerParamList' => [
            0 => 'maxLength',
            1 => 'tooltipText',
            2 => 'inlineEditDisabled'
          ],
          'index' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\User\\UserName\\Valid'
          ]
        ],
        'name' => [
          'type' => 'personName',
          'view' => 'views/user/fields/name',
          'dependeeAttributeList' => [
            0 => 'userName'
          ],
          'dynamicLogicVisibleDisabled' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'regular',
            1 => 'admin',
            2 => 'portal',
            3 => 'system',
            4 => 'super-admin',
            5 => 'api'
          ],
          'default' => 'regular',
          'maxLength' => 24,
          'index' => true,
          'inlineEditDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ]
        ],
        'password' => [
          'type' => 'password',
          'maxLength' => 150,
          'internal' => true,
          'utility' => true,
          'directAccessDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ],
          'orderDisabled' => true
        ],
        'passwordConfirm' => [
          'type' => 'password',
          'maxLength' => 150,
          'internal' => true,
          'utility' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'fieldManagerParamList' => []
        ],
        'authMethod' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'ApiKey',
            2 => 'Hmac'
          ],
          'maxLength' => 24,
          'layoutMassUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutListDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ]
        ],
        'apiKey' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'readOnly' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutListDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ],
          'copyToClipboard' => true,
          'dynamicLogicVisibleDisabled' => true,
          'orderDisabled' => true
        ],
        'secretKey' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutListDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ],
          'copyToClipboard' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'salutationName' => [
          'type' => 'enum',
          'customizationOptionsReferenceDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'options' => [
            0 => '',
            1 => 'Mr.',
            2 => 'Ms.',
            3 => 'Mrs.',
            4 => 'Dr.'
          ]
        ],
        'firstName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100,
          'suppressValidationList' => [
            0 => 'required'
          ]
        ],
        'lastName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100,
          'required' => true,
          'suppressValidationList' => [
            0 => 'required'
          ]
        ],
        'isActive' => [
          'type' => 'bool',
          'layoutDetailDisabled' => true,
          'tooltip' => true,
          'default' => true,
          'customizationAuditedDisabled' => true,
          'audited' => true
        ],
        'title' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'customizationAuditedDisabled' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'position' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'notStorable' => true,
          'orderDisabled' => true,
          'where' => [
            'LIKE' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'TeamUser',
                  'select' => [
                    0 => 'userId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role*' => '{value}'
                  ]
                ]
              ]
            ],
            'NOT LIKE' => [
              'whereClause' => [
                'id!=s' => [
                  'from' => 'TeamUser',
                  'select' => [
                    0 => 'userId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role*' => '{value}'
                  ]
                ]
              ]
            ],
            '=' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'TeamUser',
                  'select' => [
                    0 => 'userId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role' => '{value}'
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'id=!s' => [
                  'from' => 'TeamUser',
                  'select' => [
                    0 => 'userId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role' => '{value}'
                  ]
                ]
              ]
            ],
            'IS NULL' => [
              'whereClause' => [
                'NOT' => [
                  'EXISTS' => [
                    'from' => 'User',
                    'fromAlias' => 'sq',
                    'select' => [
                      0 => 'id'
                    ],
                    'leftJoins' => [
                      0 => [
                        0 => 'teams',
                        1 => 'm',
                        2 => [],
                        3 => [
                          'onlyMiddle' => true
                        ]
                      ]
                    ],
                    'whereClause' => [
                      'm.role!=' => NULL,
                      'sq.id:' => 'user.id'
                    ]
                  ]
                ]
              ]
            ],
            'IS NOT NULL' => [
              'whereClause' => [
                'EXISTS' => [
                  'from' => 'User',
                  'fromAlias' => 'sq',
                  'select' => [
                    0 => 'id'
                  ],
                  'leftJoins' => [
                    0 => [
                      0 => 'teams',
                      1 => 'm',
                      2 => [],
                      3 => [
                        'onlyMiddle' => true
                      ]
                    ]
                  ],
                  'whereClause' => [
                    'm.role!=' => NULL,
                    'sq.id:' => 'user.id'
                  ]
                ]
              ]
            ]
          ],
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'importDisabled' => true,
          'exportDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'textFilterDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'tooltipText'
          ]
        ],
        'emailAddress' => [
          'type' => 'email',
          'required' => false,
          'layoutMassUpdateDisabled' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'phoneNumber' => [
          'type' => 'phone',
          'typeList' => [
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other'
          ],
          'defaultType' => 'Mobile',
          'dynamicLogicVisibleDisabled' => true
        ],
        'token' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'authTokenId' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'authLogRecordId' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'ipAddress' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'defaultTeam' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'tooltip' => true,
          'customizationAuditedDisabled' => true,
          'customizationAutocompleteOnEmptyDisabled' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Classes\\FieldValidators\\User\\DefaultTeam\\IsUserTeam'
          ],
          'view' => 'views/user/fields/default-team',
          'dynamicLogicVisibleDisabled' => true
        ],
        'acceptanceStatus' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'exportDisabled' => true,
          'utility' => true,
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusMeetings' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'meetings',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusCalls' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'calls',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'teamRole' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'fieldManagerParamList' => []
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'columns' => [
            'role' => 'userRole'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'layoutDetailDisabled' => true,
          'view' => 'views/user/fields/teams',
          'audited' => true
        ],
        'roles' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'tooltip' => true,
          'audited' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'portals' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'tooltip' => true,
          'audited' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'portalRoles' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'tooltip' => true,
          'audited' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'contact' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'view' => 'views/user/fields/contact',
          'fieldManagerParamList' => [
            0 => 'inlineEditDisabled',
            1 => 'tooltipText'
          ],
          'audited' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'accounts' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'inlineEditDisabled',
            1 => 'tooltipText'
          ],
          'audited' => true,
          'dynamicLogicVisibleDisabled' => true
        ],
        'account' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'foreignAccessDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'readOnly' => true,
          'audited' => true
        ],
        'portal' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'customizationDisabled' => true,
          'utility' => true
        ],
        'avatar' => [
          'type' => 'image',
          'view' => 'views/user/fields/avatar',
          'layoutDetailDisabled' => true,
          'previewSize' => 'small',
          'customizationAuditedDisabled' => true,
          'defaultAttributes' => [
            'avatarId' => NULL
          ],
          'layoutAvailabilityList' => []
        ],
        'avatarColor' => [
          'type' => 'colorpicker',
          'dynamicLogicDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'sendAccessInfo' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true
        ],
        'gender' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Male',
            2 => 'Female',
            3 => 'Neutral'
          ],
          'dynamicLogicVisibleDisabled' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'dashboardTemplate' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'customizationAuditedDisabled' => true
        ],
        'workingTimeCalendar' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'customizationAuditedDisabled' => true
        ],
        'layoutSet' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'customizationAuditedDisabled' => true,
          'tooltip' => true
        ],
        'auth2FA' => [
          'type' => 'foreign',
          'link' => 'userData',
          'field' => 'auth2FA',
          'readOnly' => true,
          'view' => 'views/fields/foreign-bool'
        ],
        'userData' => [
          'type' => 'linkOne',
          'utility' => true,
          'customizationDisabled' => true
        ],
        'lastAccess' => [
          'type' => 'datetime',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'directAccessDisabled' => true,
          'exportDisabled' => true
        ],
        'emailAddressList' => [
          'type' => 'array',
          'utility' => true,
          'notStorable' => true,
          'readOnly' => true
        ],
        'userEmailAddressList' => [
          'type' => 'array',
          'utility' => true,
          'notStorable' => true,
          'readOnly' => true
        ],
        'excludeFromReplyEmailAddressList' => [
          'type' => 'array',
          'utility' => true,
          'notStorable' => true,
          'readOnly' => true
        ],
        'recordAccessLevels' => [
          'type' => 'jsonObject',
          'utility' => true,
          'notStorable' => true,
          'readOnly' => true
        ],
        'targetListIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'fieldManagerParamList' => []
        ],
        'middleName' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ]
        ],
        'emailAddressIsOptedOut' => [
          'layoutMassUpdateDisabled' => true,
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'emailAddressIsInvalid' => [
          'layoutMassUpdateDisabled' => true,
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'phoneNumberIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'phoneNumberIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'deleteId' => [
          'type' => 'varchar',
          'maxLength' => 17,
          'readOnly' => true,
          'notNull' => true,
          'default' => '0',
          'utility' => true,
          'customizationDisabled' => true
        ]
      ],
      'links' => [
        'defaultTeam' => [
          'type' => 'belongsTo',
          'entity' => 'Team'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'users',
          'additionalColumns' => [
            'role' => [
              'type' => 'varchar',
              'len' => 100
            ]
          ],
          'layoutRelationshipsDisabled' => true,
          'columnAttributeMap' => [
            'role' => 'teamRole'
          ],
          'dynamicLogicVisibleDisabled' => true
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'roles' => [
          'type' => 'hasMany',
          'entity' => 'Role',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true
        ],
        'portals' => [
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true
        ],
        'portalRoles' => [
          'type' => 'hasMany',
          'entity' => 'PortalRole',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true
        ],
        'dashboardTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'DashboardTemplate'
        ],
        'workingTimeCalendar' => [
          'type' => 'belongsTo',
          'entity' => 'WorkingTimeCalendar',
          'noJoin' => true
        ],
        'workingTimeRanges' => [
          'type' => 'hasMany',
          'foreign' => 'users',
          'entity' => 'WorkingTimeRange'
        ],
        'layoutSet' => [
          'type' => 'belongsTo',
          'entity' => 'LayoutSet',
          'noJoin' => true
        ],
        'userData' => [
          'type' => 'hasOne',
          'entity' => 'UserData',
          'foreign' => 'user',
          'foreignName' => 'id'
        ],
        'meetings' => [
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'users',
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'calls' => [
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'users',
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'emails' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'users'
        ],
        'notes' => [
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true
        ],
        'contact' => [
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'portalUser'
        ],
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'portalUsers',
          'relationName' => 'AccountPortalUser'
        ],
        'tasks' => [
          'type' => 'hasMany',
          'entity' => 'Task',
          'foreign' => 'assignedUser'
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'users',
          'columnAttributeMap' => [
            'optedOut' => 'targetListIsOptedOut'
          ]
        ]
      ],
      'collection' => [
        'orderBy' => 'userName',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'userName',
          2 => 'emailAddress'
        ],
        'sortBy' => 'userName',
        'asc' => true
      ],
      'indexes' => [
        'userNameDeleteId' => [
          'type' => 'unique',
          'columns' => [
            0 => 'userName',
            1 => 'deleteId'
          ]
        ]
      ],
      'deleteId' => true
    ],
    'UserData' => [
      'fields' => [
        'auth2FA' => [
          'type' => 'bool'
        ],
        'auth2FAMethod' => [
          'type' => 'enum'
        ],
        'auth2FATotpSecret' => [
          'type' => 'varchar',
          'maxLength' => 32
        ],
        'auth2FAEmailAddress' => [
          'type' => 'varchar'
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ]
    ],
    'UserReaction' => [
      'fields' => [
        'type' => [
          'type' => 'varchar',
          'maxLength' => 10
        ],
        'user' => [
          'type' => 'link'
        ],
        'parent' => [
          'type' => 'linkParent'
        ],
        'createdAt' => [
          'type' => 'datetime'
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Note'
          ]
        ]
      ],
      'indexes' => [
        'parentUserType' => [
          'unique' => true,
          'columns' => [
            0 => 'parentId',
            1 => 'parentType',
            2 => 'userId',
            3 => 'type'
          ]
        ]
      ],
      'noDeletedAttribute' => true
    ],
    'Webhook' => [
      'fields' => [
        'event' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'view' => 'views/webhook/fields/event'
        ],
        'url' => [
          'type' => 'varchar',
          'maxLength' => 512,
          'required' => true,
          'copyToClipboard' => true
        ],
        'isActive' => [
          'type' => 'bool',
          'default' => true
        ],
        'user' => [
          'type' => 'link',
          'view' => 'views/webhook/fields/user'
        ],
        'entityType' => [
          'type' => 'varchar',
          'readOnly' => true,
          'view' => 'views/fields/entity-type'
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'create',
            1 => 'update',
            2 => 'fieldUpdate',
            3 => 'delete'
          ],
          'readOnly' => true
        ],
        'field' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'secretKey' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'readOnly' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutListDisabled' => true
        ],
        'skipOwn' => [
          'type' => 'bool',
          'tooltip' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'queueItems' => [
          'type' => 'hasMany',
          'entity' => 'WebhookQueueItem',
          'foreign' => 'webhook',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'event'
        ],
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'event' => [
          'columns' => [
            0 => 'event'
          ]
        ],
        'entityTypeType' => [
          'columns' => [
            0 => 'entityType',
            1 => 'type'
          ]
        ],
        'entityTypeField' => [
          'columns' => [
            0 => 'entityType',
            1 => 'field'
          ]
        ]
      ],
      'hooksDisabled' => true
    ],
    'WebhookEventQueueItem' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'dbType' => 'bigint'
        ],
        'event' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'view' => 'views/webhook/fields/event'
        ],
        'target' => [
          'type' => 'linkParent'
        ],
        'user' => [
          'type' => 'link'
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'isProcessed' => [
          'type' => 'bool'
        ]
      ],
      'links' => [
        'target' => [
          'type' => 'belongsToParent'
        ],
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'sortBy' => 'number',
        'asc' => false
      ]
    ],
    'WebhookQueueItem' => [
      'fields' => [
        'number' => [
          'type' => 'autoincrement',
          'dbType' => 'bigint'
        ],
        'event' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'view' => 'views/webhook/fields/event'
        ],
        'webhook' => [
          'type' => 'link'
        ],
        'target' => [
          'type' => 'linkParent'
        ],
        'data' => [
          'type' => 'jsonObject'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'hasSeconds' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Pending',
            1 => 'Success',
            2 => 'Failed'
          ],
          'default' => 'Pending',
          'maxLength' => 7,
          'style' => [
            'Success' => 'success',
            'Failed' => 'danger'
          ]
        ],
        'processedAt' => [
          'type' => 'datetime',
          'hasSeconds' => true
        ],
        'attempts' => [
          'type' => 'int',
          'default' => 0
        ],
        'processAt' => [
          'type' => 'datetime'
        ]
      ],
      'links' => [
        'target' => [
          'type' => 'belongsToParent'
        ],
        'webhook' => [
          'type' => 'belongsTo',
          'entity' => 'Webhook',
          'foreignName' => 'id',
          'foreign' => 'queueItems'
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'sortBy' => 'number',
        'asc' => false
      ]
    ],
    'WorkingTimeCalendar' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'timeZone' => [
          'type' => 'enum',
          'default' => '',
          'view' => 'views/preferences/fields/time-zone'
        ],
        'timeRanges' => [
          'type' => 'jsonArray',
          'default' => [
            0 => [
              0 => '9:00',
              1 => '17:00'
            ]
          ],
          'view' => 'views/working-time-calendar/fields/time-ranges',
          'required' => true
        ],
        'weekday0' => [
          'type' => 'bool',
          'default' => false
        ],
        'weekday1' => [
          'type' => 'bool',
          'default' => true
        ],
        'weekday2' => [
          'type' => 'bool',
          'default' => true
        ],
        'weekday3' => [
          'type' => 'bool',
          'default' => true
        ],
        'weekday4' => [
          'type' => 'bool',
          'default' => true
        ],
        'weekday5' => [
          'type' => 'bool',
          'default' => true
        ],
        'weekday6' => [
          'type' => 'bool',
          'default' => false
        ],
        'weekday0TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday1TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday2TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday3TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday4TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday5TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'weekday6TimeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'ranges' => [
          'type' => 'hasMany',
          'foreign' => 'calendars',
          'entity' => 'WorkingTimeRange'
        ],
        'teams' => [
          'type' => 'hasMany',
          'foreign' => 'workingTimeCalendar',
          'entity' => 'Team',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name'
        ],
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'WorkingTimeRange' => [
      'fields' => [
        'timeRanges' => [
          'type' => 'jsonArray',
          'default' => NULL,
          'view' => 'views/working-time-calendar/fields/time-ranges'
        ],
        'dateStart' => [
          'type' => 'date',
          'required' => true
        ],
        'dateEnd' => [
          'type' => 'date',
          'required' => true,
          'view' => 'views/working-time-range/fields/date-end',
          'after' => 'dateStart',
          'afterOrEqual' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'Non-working',
            1 => 'Working'
          ],
          'default' => 'Non-working',
          'index' => true,
          'maxLength' => 11
        ],
        'name' => [
          'type' => 'varchar'
        ],
        'description' => [
          'type' => 'text'
        ],
        'calendars' => [
          'type' => 'linkMultiple',
          'tooltip' => true
        ],
        'users' => [
          'type' => 'linkMultiple',
          'view' => 'views/working-time-range/fields/users',
          'tooltip' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'calendars' => [
          'type' => 'hasMany',
          'foreign' => 'ranges',
          'entity' => 'WorkingTimeCalendar'
        ],
        'users' => [
          'type' => 'hasMany',
          'foreign' => 'workingTimeRanges',
          'entity' => 'User'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ]
      ],
      'collection' => [
        'orderBy' => 'dateStart',
        'order' => 'desc',
        'sortBy' => 'dateStart',
        'asc' => false
      ],
      'indexes' => [
        'typeRange' => [
          'columns' => [
            0 => 'type',
            1 => 'dateStart',
            2 => 'dateEnd'
          ]
        ],
        'type' => [
          'columns' => [
            0 => 'type'
          ]
        ]
      ]
    ],
    'Account' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'maxLength' => 249,
          'required' => true,
          'pattern' => '$noBadCharacters',
          'audited' => true
        ],
        'website' => [
          'type' => 'url',
          'strip' => true
        ],
        'emailAddress' => [
          'type' => 'email',
          'isPersonalData' => true
        ],
        'phoneNumber' => [
          'type' => 'phone',
          'typeList' => [
            0 => 'Office',
            1 => 'Mobile',
            2 => 'Fax',
            3 => 'Other'
          ],
          'defaultType' => 'Office'
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Customer',
            2 => 'Investor',
            3 => 'Partner',
            4 => 'Reseller'
          ],
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'industry' => [
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
          'isSorted' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'sicCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'noSpellCheck' => true
        ],
        'contactRole' => [
          'type' => 'varchar',
          'notStorable' => true,
          'utility' => true,
          'orderDisabled' => true,
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'layoutMassUpdateDisabled' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutSearchDisabled' => true,
          'fieldManagerParamList' => [
            0 => 'pattern'
          ]
        ],
        'contactIsInactive' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'default' => false,
          'utility' => true
        ],
        'billingAddress' => [
          'type' => 'address'
        ],
        'billingAddressStreet' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'billingAddress'
          ]
        ],
        'billingAddressCity' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'billingAddress'
          ]
        ],
        'billingAddressState' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'billingAddress'
          ]
        ],
        'billingAddressCountry' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'billingAddress'
          ]
        ],
        'billingAddressPostalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'billingAddress'
          ]
        ],
        'shippingAddress' => [
          'type' => 'address',
          'view' => 'crm:views/account/fields/shipping-address'
        ],
        'shippingAddressStreet' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'shippingAddress'
          ]
        ],
        'shippingAddressCity' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'shippingAddress'
          ]
        ],
        'shippingAddressState' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'shippingAddress'
          ]
        ],
        'shippingAddressCountry' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'shippingAddress'
          ]
        ],
        'shippingAddressPostalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'shippingAddress'
          ]
        ],
        'description' => [
          'type' => 'text'
        ],
        'campaign' => [
          'type' => 'link'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'targetLists' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'importDisabled' => true,
          'exportDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateEnabled' => true,
          'filtersEnabled' => true,
          'noLoad' => true
        ],
        'targetList' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'entity' => 'TargetList',
          'directAccessDisabled' => true,
          'importEnabled' => true
        ],
        'originalLead' => [
          'type' => 'linkOne',
          'readOnly' => true,
          'view' => 'views/fields/link-one'
        ],
        'targetListIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true
        ],
        'emailAddressIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'emailAddressIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'phoneNumberIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'phoneNumberIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'billingAddressMap' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'shippingAddressMap' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'accounts',
          'columnAttributeMap' => [
            'role' => 'contactRole',
            'isInactive' => 'contactIsInactive'
          ]
        ],
        'contactsPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true
        ],
        'opportunities' => [
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'account'
        ],
        'cases' => [
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'account'
        ],
        'documents' => [
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'accounts',
          'audited' => true
        ],
        'meetingsPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true
        ],
        'emailsPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true
        ],
        'callsPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true
        ],
        'tasksPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Task',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true
        ],
        'meetings' => [
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'audited' => true
        ],
        'calls' => [
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'audited' => true
        ],
        'tasks' => [
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent'
        ],
        'emails' => [
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'accounts'
        ],
        'campaignLogRecords' => [
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent'
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'accounts',
          'columnAttributeMap' => [
            'optedOut' => 'targetListIsOptedOut'
          ]
        ],
        'portalUsers' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'accounts'
        ],
        'originalLead' => [
          'type' => 'hasOne',
          'entity' => 'Lead',
          'foreign' => 'createdAccount'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'emailAddress'
        ],
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ],
        'createdAtId' => [
          'unique' => true,
          'columns' => [
            0 => 'createdAt',
            1 => 'id'
          ]
        ],
        'name' => [
          'columns' => [
            0 => 'name',
            1 => 'deleted'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ]
      ],
      'optimisticConcurrencyControl' => true
    ],
    'Call' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Planned',
            1 => 'Held',
            2 => 'Not Held'
          ],
          'default' => 'Planned',
          'style' => [
            'Held' => 'success',
            'Not Held' => 'info'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'audited' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'dateStart' => [
          'type' => 'datetime',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
          'audited' => true,
          'view' => 'crm:views/call/fields/date-start'
        ],
        'dateEnd' => [
          'type' => 'datetime',
          'required' => true,
          'after' => 'dateStart',
          'afterOrEqual' => true
        ],
        'duration' => [
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
          'select' => [
            'select' => 'TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)'
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)',
                1 => '{direction}'
              ]
            ]
          ]
        ],
        'reminders' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'crm:views/meeting/fields/reminders',
          'layoutListDisabled' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\Valid',
            1 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\MaxCount'
          ],
          'dynamicLogicDisabled' => true,
          'duplicateIgnore' => true
        ],
        'direction' => [
          'type' => 'enum',
          'options' => [
            0 => 'Outbound',
            1 => 'Inbound'
          ],
          'default' => 'Outbound'
        ],
        'description' => [
          'type' => 'text'
        ],
        'parent' => [
          'type' => 'linkParent',
          'entityList' => [
            0 => 'Account',
            1 => 'Lead',
            2 => 'Contact',
            3 => 'Opportunity',
            4 => 'Case'
          ]
        ],
        'account' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ],
        'uid' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'index' => true,
          'readOnly' => true,
          'duplicateIgnore' => true
        ],
        'acceptanceStatus' => [
          'type' => 'enum',
          'notStorable' => true,
          'options' => [
            0 => 'None',
            1 => 'Accepted',
            2 => 'Tentative',
            3 => 'Declined'
          ],
          'style' => [
            'Accepted' => 'success',
            'Declined' => 'danger',
            'Tentative' => 'warning'
          ],
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'orderDisabled' => true,
          'importDisabled' => true,
          'exportDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'where' => [
            '=' => [
              'whereClause' => [
                'OR' => [
                  0 => [
                    'id=s' => [
                      'from' => 'CallContact',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id=s' => [
                      'from' => 'CallLead',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id=s' => [
                      'from' => 'CallUser',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'AND' => [
                  0 => [
                    'id!=s' => [
                      'from' => 'CallContact',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id!=s' => [
                      'from' => 'CallLead',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id!=s' => [
                      'from' => 'CallUser',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'IN' => [
              'whereClause' => [
                'OR' => [
                  0 => [
                    'id=s' => [
                      'from' => 'CallContact',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id=s' => [
                      'from' => 'CallLead',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id=s' => [
                      'from' => 'CallUser',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'NOT IN' => [
              'whereClause' => [
                'AND' => [
                  0 => [
                    'id!=s' => [
                      'from' => 'CallContact',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id!=s' => [
                      'from' => 'CallLead',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id!=s' => [
                      'from' => 'CallUser',
                      'select' => [
                        0 => 'callId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ],
          'view' => 'crm:views/meeting/fields/acceptance-status'
        ],
        'users' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/users',
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees',
          'audited' => true
        ],
        'contacts' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/call/fields/contacts',
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees',
          'audited' => true
        ],
        'leads' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/call/fields/leads',
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees',
          'audited' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'phoneNumbersMap' => [
          'type' => 'jsonObject',
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true,
          'customizationDisabled' => true
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ]
      ],
      'links' => [
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'calls',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'calls',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'leads' => [
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'calls',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'foreign' => 'calls'
        ]
      ],
      'collection' => [
        'orderBy' => 'dateStart',
        'order' => 'desc',
        'sortBy' => 'dateStart',
        'asc' => false
      ],
      'indexes' => [
        'dateStartStatus' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'status'
          ]
        ],
        'dateStart' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'deleted'
          ]
        ],
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'assignedUserStatus' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'status'
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\Event'
    ],
    'Campaign' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Planning',
            1 => 'Active',
            2 => 'Inactive',
            3 => 'Complete'
          ],
          'default' => 'Planning',
          'style' => [
            'Active' => 'primary',
            'Inactive' => 'info',
            'Complete' => 'success'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'Email',
            1 => 'Newsletter',
            2 => 'Informational Email',
            3 => 'Web',
            4 => 'Television',
            5 => 'Radio',
            6 => 'Mail'
          ],
          'default' => 'Email',
          'maxLength' => 64,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'startDate' => [
          'type' => 'date',
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Campaign\\StartDate\\BeforeEndDate'
          ],
          'audited' => true
        ],
        'endDate' => [
          'type' => 'date',
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Campaign\\EndDate\\AfterStartDate'
          ],
          'audited' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
          'audited' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
          'audited' => true
        ],
        'targetLists' => [
          'type' => 'linkMultiple',
          'tooltip' => true
        ],
        'excludingTargetLists' => [
          'type' => 'linkMultiple',
          'tooltip' => true
        ],
        'sentCount' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'openedCount' => [
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'clickedCount' => [
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'optedInCount' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'optedOutCount' => [
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'bouncedCount' => [
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'hardBouncedCount' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'softBouncedCount' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'leadCreatedCount' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'openedPercentage' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'clickedPercentage' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'optedOutPercentage' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'bouncedPercentage' => [
          'type' => 'int',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'utility' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'revenue' => [
          'type' => 'currency',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'loaderClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Campaign\\StatsLoader'
        ],
        'budget' => [
          'type' => 'currency'
        ],
        'contactsTemplate' => [
          'type' => 'link',
          'view' => 'crm:views/campaign/fields/template',
          'targetEntityType' => 'Contact'
        ],
        'leadsTemplate' => [
          'type' => 'link',
          'view' => 'crm:views/campaign/fields/template',
          'targetEntityType' => 'Lead'
        ],
        'accountsTemplate' => [
          'type' => 'link',
          'view' => 'crm:views/campaign/fields/template',
          'targetEntityType' => 'Account'
        ],
        'usersTemplate' => [
          'type' => 'link',
          'view' => 'crm:views/campaign/fields/template',
          'targetEntityType' => 'User'
        ],
        'mailMergeOnlyWithAddress' => [
          'type' => 'bool',
          'default' => true
        ],
        'revenueCurrency' => [
          'notStorable' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'type' => 'enum',
          'view' => 'views/fields/currency-list',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationDisplayAsLabelDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationDefaultView' => 'views/admin/field-manager/fields/currency-default',
          'customizationTooltipTextDisabled' => true,
          'maxLength' => 3,
          'apiSpecDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'revenue'
          ]
        ],
        'revenueConverted' => [
          'notStorable' => true,
          'directAccessDisabled' => true,
          'directUpdateDisabled' => true,
          'readOnly' => true,
          'type' => 'currencyConverted',
          'importDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'budgetCurrency' => [
          'type' => 'enum',
          'view' => 'views/fields/currency-list',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationDisplayAsLabelDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationDefaultView' => 'views/admin/field-manager/fields/currency-default',
          'customizationTooltipTextDisabled' => true,
          'maxLength' => 3,
          'apiSpecDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'budget'
          ]
        ],
        'budgetConverted' => [
          'type' => 'currencyConverted',
          'readOnly' => true,
          'importDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'campaigns'
        ],
        'excludingTargetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'campaignsExcluding',
          'relationName' => 'campaignTargetListExcluding'
        ],
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'campaign'
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'campaign'
        ],
        'leads' => [
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'campaign'
        ],
        'opportunities' => [
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'campaign'
        ],
        'campaignLogRecords' => [
          'type' => 'hasMany',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'campaign'
        ],
        'trackingUrls' => [
          'type' => 'hasMany',
          'entity' => 'CampaignTrackingUrl',
          'foreign' => 'campaign'
        ],
        'massEmails' => [
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'campaign'
        ],
        'contactsTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'Template',
          'noJoin' => true
        ],
        'leadsTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'Template',
          'noJoin' => true
        ],
        'accountsTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'Template',
          'noJoin' => true
        ],
        'usersTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'Template',
          'noJoin' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ]
      ]
    ],
    'CampaignLogRecord' => [
      'fields' => [
        'action' => [
          'type' => 'enum',
          'required' => true,
          'maxLength' => 50,
          'options' => [
            0 => 'Sent',
            1 => 'Opened',
            2 => 'Opted Out',
            3 => 'Bounced',
            4 => 'Clicked',
            5 => 'Opted In',
            6 => 'Lead Created'
          ]
        ],
        'actionDate' => [
          'type' => 'datetime',
          'required' => true
        ],
        'data' => [
          'type' => 'jsonObject',
          'view' => 'crm:views/campaign-log-record/fields/data'
        ],
        'stringData' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'stringAdditionalData' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'application' => [
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 36,
          'default' => 'Espo'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'campaign' => [
          'type' => 'link'
        ],
        'parent' => [
          'type' => 'linkParent'
        ],
        'object' => [
          'type' => 'linkParent'
        ],
        'queueItem' => [
          'type' => 'link'
        ],
        'isTest' => [
          'type' => 'bool',
          'default' => false
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'campaignLogRecords'
        ],
        'queueItem' => [
          'type' => 'belongsTo',
          'entity' => 'EmailQueueItem',
          'noJoin' => true
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'User'
          ]
        ],
        'object' => [
          'type' => 'belongsToParent',
          'entityList' => [
            0 => 'Email',
            1 => 'CampaignTrackingUrl'
          ]
        ]
      ],
      'collection' => [
        'textFilterFields' => [
          0 => 'queueItem.id',
          1 => 'queueItem.emailAddress'
        ],
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'actionDate' => [
          'columns' => [
            0 => 'actionDate',
            1 => 'deleted'
          ]
        ],
        'action' => [
          'columns' => [
            0 => 'action',
            1 => 'deleted'
          ]
        ]
      ]
    ],
    'CampaignTrackingUrl' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'url' => [
          'type' => 'url',
          'tooltip' => true
        ],
        'urlToUse' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'copyToClipboard' => true
        ],
        'campaign' => [
          'type' => 'link',
          'readOnlyAfterCreate' => true
        ],
        'action' => [
          'type' => 'enum',
          'options' => [
            0 => 'Redirect',
            1 => 'Show Message'
          ],
          'default' => 'Redirect',
          'maxLength' => 12
        ],
        'message' => [
          'type' => 'text',
          'tooltip' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'trackingUrls'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ]
    ],
    'Case' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'number' => [
          'type' => 'autoincrement',
          'index' => true
        ],
        'status' => [
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
          'style' => [
            'Closed' => 'success',
            'Assigned' => 'primary',
            'Duplicate' => 'info',
            'Pending' => 'warning',
            'Rejected' => 'info'
          ],
          'audited' => true,
          'displayAsLabel' => true,
          'labelType' => 'state',
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'notActualOptions',
              'view' => 'views/admin/field-manager/fields/not-actual-options'
            ]
          ],
          'notActualOptions' => [
            0 => 'Closed',
            1 => 'Rejected',
            2 => 'Duplicate'
          ],
          'customizationOptionsReferenceDisabled' => true
        ],
        'priority' => [
          'type' => 'enum',
          'options' => [
            0 => 'Low',
            1 => 'Normal',
            2 => 'High',
            3 => 'Urgent'
          ],
          'default' => 'Normal',
          'displayAsLabel' => true,
          'style' => [
            'High' => 'warning',
            'Urgent' => 'danger'
          ],
          'audited' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Question',
            2 => 'Incident',
            3 => 'Problem'
          ],
          'audited' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'description' => [
          'type' => 'text',
          'preview' => true,
          'attachmentField' => 'attachments',
          'cutHeight' => 500
        ],
        'account' => [
          'type' => 'link'
        ],
        'lead' => [
          'type' => 'link'
        ],
        'contact' => [
          'type' => 'link'
        ],
        'contacts' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/link-multiple-with-primary',
          'orderBy' => 'name',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'contact'
          ],
          'primaryLink' => 'contact'
        ],
        'inboundEmail' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'originalEmail' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'entity' => 'Email',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'directAccessDisabled' => true
        ],
        'isInternal' => [
          'type' => 'bool'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'attachments' => [
          'type' => 'attachmentMultiple'
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'inboundEmail' => [
          'type' => 'belongsTo',
          'entity' => 'InboundEmail'
        ],
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'cases',
          'deferredLoad' => true
        ],
        'lead' => [
          'type' => 'belongsTo',
          'entity' => 'Lead',
          'foreign' => 'cases',
          'deferredLoad' => true
        ],
        'contact' => [
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'casesPrimary',
          'deferredLoad' => true
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'cases',
          'layoutRelationshipsDisabled' => true
        ],
        'meetings' => [
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'audited' => true
        ],
        'calls' => [
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'audited' => true
        ],
        'tasks' => [
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'audited' => true
        ],
        'emails' => [
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true
        ],
        'articles' => [
          'type' => 'hasMany',
          'entity' => 'KnowledgeBaseArticle',
          'foreign' => 'cases',
          'audited' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'number',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'number',
          2 => 'description'
        ],
        'fullTextSearch' => true,
        'sortBy' => 'number',
        'asc' => false
      ],
      'indexes' => [
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'assignedUserStatus' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'status'
          ]
        ]
      ],
      'optimisticConcurrencyControl' => true
    ],
    'Contact' => [
      'fields' => [
        'name' => [
          'type' => 'personName',
          'isPersonalData' => true
        ],
        'salutationName' => [
          'type' => 'enum',
          'customizationOptionsReferenceDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'options' => [
            0 => '',
            1 => 'Mr.',
            2 => 'Ms.',
            3 => 'Mrs.',
            4 => 'Dr.'
          ]
        ],
        'firstName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100
        ],
        'lastName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100,
          'required' => true
        ],
        'accountAnyId' => [
          'notStorable' => true,
          'orderDisabled' => true,
          'customizationDisabled' => true,
          'utility' => true,
          'type' => 'varchar',
          'where' => [
            '=' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'accountId' => '{value}'
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'id!=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'accountId' => '{value}'
                  ]
                ]
              ]
            ],
            'IN' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'accountId' => '{value}'
                  ]
                ]
              ]
            ],
            'NOT IN' => [
              'whereClause' => [
                'id!=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'accountId' => '{value}'
                  ]
                ]
              ]
            ],
            'IS NULL' => [
              'whereClause' => [
                'accountId' => NULL
              ]
            ],
            'IS NOT NULL' => [
              'whereClause' => [
                'accountId!=' => NULL
              ]
            ]
          ]
        ],
        'title' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'crm:views/contact/fields/title',
          'directUpdateDisabled' => true,
          'notStorable' => true,
          'select' => [
            'select' => 'accountContactPrimary.role',
            'leftJoins' => [
              0 => [
                0 => 'AccountContact',
                1 => 'accountContactPrimary',
                2 => [
                  'contact.id:' => 'accountContactPrimary.contactId',
                  'contact.accountId:' => 'accountContactPrimary.accountId',
                  'accountContactPrimary.deleted' => false
                ]
              ]
            ]
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'accountContactPrimary.role',
                1 => '{direction}'
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'AccountContact',
                1 => 'accountContactPrimary',
                2 => [
                  'contact.id:' => 'accountContactPrimary.contactId',
                  'contact.accountId:' => 'accountContactPrimary.accountId',
                  'accountContactPrimary.deleted' => false
                ]
              ]
            ]
          ],
          'where' => [
            'LIKE' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role*' => '{value}'
                  ]
                ]
              ]
            ],
            'NOT LIKE' => [
              'whereClause' => [
                'id!=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role*' => '{value}'
                  ]
                ]
              ]
            ],
            '=' => [
              'whereClause' => [
                'id=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role' => '{value}'
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'id!=s' => [
                  'from' => 'AccountContact',
                  'select' => [
                    0 => 'contactId'
                  ],
                  'whereClause' => [
                    'deleted' => false,
                    'role' => '{value}'
                  ]
                ]
              ]
            ],
            'IS NULL' => [
              'whereClause' => [
                'NOT' => [
                  'EXISTS' => [
                    'from' => 'Contact',
                    'fromAlias' => 'sq',
                    'select' => [
                      0 => 'id'
                    ],
                    'leftJoins' => [
                      0 => [
                        0 => 'accounts',
                        1 => 'm',
                        2 => [],
                        3 => [
                          'onlyMiddle' => true
                        ]
                      ]
                    ],
                    'whereClause' => [
                      'AND' => [
                        0 => [
                          'm.role!=' => NULL
                        ],
                        1 => [
                          'm.role!=' => ''
                        ],
                        2 => [
                          'sq.id:' => 'contact.id'
                        ]
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'IS NOT NULL' => [
              'whereClause' => [
                'EXISTS' => [
                  'from' => 'Contact',
                  'fromAlias' => 'sq',
                  'select' => [
                    0 => 'id'
                  ],
                  'leftJoins' => [
                    0 => [
                      0 => 'accounts',
                      1 => 'm',
                      2 => [],
                      3 => [
                        'onlyMiddle' => true
                      ]
                    ]
                  ],
                  'whereClause' => [
                    'AND' => [
                      0 => [
                        'm.role!=' => NULL
                      ],
                      1 => [
                        'm.role!=' => ''
                      ],
                      2 => [
                        'sq.id:' => 'contact.id'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ],
          'customizationOptionsDisabled' => true,
          'textFilterDisabled' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'emailAddress' => [
          'type' => 'email',
          'isPersonalData' => true
        ],
        'phoneNumber' => [
          'type' => 'phone',
          'typeList' => [
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other'
          ],
          'defaultType' => 'Mobile',
          'isPersonalData' => true
        ],
        'doNotCall' => [
          'type' => 'bool'
        ],
        'address' => [
          'type' => 'address',
          'isPersonalData' => true
        ],
        'addressStreet' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCity' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressState' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCountry' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressPostalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'account' => [
          'type' => 'link',
          'view' => 'crm:views/contact/fields/account'
        ],
        'accounts' => [
          'type' => 'linkMultiple',
          'view' => 'crm:views/contact/fields/accounts',
          'columns' => [
            'role' => 'contactRole',
            'isInactive' => 'contactIsInactive'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'account'
          ]
        ],
        'accountRole' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'listForAccount'
          ],
          'exportDisabled' => true,
          'importDisabled' => true,
          'view' => 'crm:views/contact/fields/account-role',
          'customizationOptionsDisabled' => true,
          'textFilterDisabled' => true
        ],
        'accountIsInactive' => [
          'type' => 'bool',
          'notStorable' => true,
          'mergeDisabled' => true,
          'foreignAccessDisabled' => true,
          'select' => [
            'select' => 'accountContactPrimary.isInactive',
            'leftJoins' => [
              0 => [
                0 => 'AccountContact',
                1 => 'accountContactPrimary',
                2 => [
                  'contact.id:' => 'accountContactPrimary.contactId',
                  'contact.accountId:' => 'accountContactPrimary.accountId',
                  'accountContactPrimary.deleted' => false
                ]
              ]
            ]
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'accountContactPrimary.isInactive',
                1 => '{direction}'
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'AccountContact',
                1 => 'accountContactPrimary',
                2 => [
                  'contact.id:' => 'accountContactPrimary.contactId',
                  'contact.accountId:' => 'accountContactPrimary.accountId',
                  'accountContactPrimary.deleted' => false
                ]
              ]
            ]
          ],
          'where' => [
            '= TRUE' => [
              'leftJoins' => [
                0 => [
                  0 => 'AccountContact',
                  1 => 'accountContactFilterIsInactive',
                  2 => [
                    'accountContactFilterIsInactive.contactId:' => 'id',
                    'accountContactFilterIsInactive.accountId:' => 'accountId',
                    'accountContactFilterIsInactive.deleted' => false
                  ]
                ]
              ],
              'whereClause' => [
                'accountContactFilterIsInactive.isInactive' => true
              ]
            ],
            '= FALSE' => [
              'leftJoins' => [
                0 => [
                  0 => 'AccountContact',
                  1 => 'accountContactFilterIsInactive',
                  2 => [
                    'accountContactFilterIsInactive.contactId:' => 'id',
                    'accountContactFilterIsInactive.accountId:' => 'accountId',
                    'accountContactFilterIsInactive.deleted' => false
                  ]
                ]
              ],
              'whereClause' => [
                'OR' => [
                  0 => [
                    'accountContactFilterIsInactive.isInactive!=' => true
                  ],
                  1 => [
                    'accountContactFilterIsInactive.isInactive=' => NULL
                  ]
                ]
              ]
            ]
          ],
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true
        ],
        'accountType' => [
          'type' => 'foreign',
          'link' => 'account',
          'field' => 'type',
          'readOnly' => true,
          'view' => 'views/fields/foreign-enum'
        ],
        'opportunityRole' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'options' => [
            0 => '',
            1 => 'Decision Maker',
            2 => 'Evaluator',
            3 => 'Influencer'
          ],
          'layoutMassUpdateDisabled' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'converterClassName' => 'Espo\\Classes\\FieldConverters\\RelationshipRole',
          'converterData' => [
            'column' => 'role',
            'link' => 'opportunities',
            'relationName' => 'contactOpportunity',
            'nearKey' => 'contactId'
          ],
          'directUpdateDisabled' => true,
          'view' => 'crm:views/contact/fields/opportunity-role'
        ],
        'acceptanceStatus' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'exportDisabled' => true,
          'utility' => true,
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusMeetings' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'directUpdateDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'meetings',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusCalls' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'calls',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'campaign' => [
          'type' => 'link'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'targetLists' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'importDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'directUpdateEnabled' => true,
          'noLoad' => true
        ],
        'targetList' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'exportDisabled' => true,
          'entity' => 'TargetList',
          'directAccessDisabled' => true,
          'importEnabled' => true
        ],
        'portalUser' => [
          'type' => 'linkOne',
          'readOnly' => true,
          'notStorable' => true,
          'view' => 'views/fields/link-one'
        ],
        'hasPortalUser' => [
          'type' => 'bool',
          'notStorable' => true,
          'readOnly' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'foreignAccessDisabled' => true,
          'select' => [
            'select' => 'IS_NOT_NULL:(portalUser.id)',
            'leftJoins' => [
              0 => [
                0 => 'portalUser',
                1 => 'portalUser'
              ]
            ]
          ],
          'where' => [
            '= TRUE' => [
              'whereClause' => [
                'portalUser.id!=' => NULL
              ],
              'leftJoins' => [
                0 => [
                  0 => 'portalUser',
                  1 => 'portalUser'
                ]
              ]
            ],
            '= FALSE' => [
              'whereClause' => [
                'portalUser.id=' => NULL
              ],
              'leftJoins' => [
                0 => [
                  0 => 'portalUser',
                  1 => 'portalUser'
                ]
              ]
            ]
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'portalUser.id',
                1 => '{direction}'
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'portalUser',
                1 => 'portalUser'
              ]
            ],
            'additionalSelect' => [
              0 => 'portalUser.id'
            ]
          ]
        ],
        'originalLead' => [
          'type' => 'linkOne',
          'readOnly' => true,
          'view' => 'views/fields/link-one'
        ],
        'targetListIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true
        ],
        'originalEmail' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'entity' => 'Email',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'directAccessDisabled' => true
        ],
        'middleName' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ]
        ],
        'emailAddressIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'emailAddressIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'phoneNumberIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'phoneNumberIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'addressMap' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account',
          'deferredLoad' => true
        ],
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'contacts',
          'additionalColumns' => [
            'role' => [
              'type' => 'varchar',
              'len' => 100
            ],
            'isInactive' => [
              'type' => 'bool',
              'default' => false
            ]
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'layoutRelationshipsDisabled' => true,
          'columnAttributeMap' => [
            'role' => 'accountRole',
            'isInactive' => 'accountIsInactive'
          ]
        ],
        'opportunities' => [
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'contacts',
          'columnAttributeMap' => [
            'role' => 'opportunityRole'
          ]
        ],
        'opportunitiesPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'contact',
          'layoutRelationshipsDisabled' => true
        ],
        'casesPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'contact',
          'layoutRelationshipsDisabled' => true
        ],
        'cases' => [
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'contacts'
        ],
        'meetings' => [
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'contacts',
          'audited' => true,
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'calls' => [
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'contacts',
          'audited' => true,
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'tasks' => [
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'audited' => true
        ],
        'emails' => [
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'contacts'
        ],
        'campaignLogRecords' => [
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent'
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'contacts',
          'columnAttributeMap' => [
            'optedOut' => 'targetListIsOptedOut'
          ]
        ],
        'portalUser' => [
          'type' => 'hasOne',
          'entity' => 'User',
          'foreign' => 'contact'
        ],
        'originalLead' => [
          'type' => 'hasOne',
          'entity' => 'Lead',
          'foreign' => 'createdContact'
        ],
        'documents' => [
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'contacts',
          'audited' => true
        ],
        'tasksPrimary' => [
          'type' => 'hasMany',
          'entity' => 'Task',
          'foreign' => 'contact',
          'layoutRelationshipsDisabled' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'emailAddress'
        ],
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ],
        'createdAtId' => [
          'unique' => true,
          'columns' => [
            0 => 'createdAt',
            1 => 'id'
          ]
        ],
        'firstName' => [
          'columns' => [
            0 => 'firstName',
            1 => 'deleted'
          ]
        ],
        'name' => [
          'columns' => [
            0 => 'firstName',
            1 => 'lastName'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ]
      ]
    ],
    'Document' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'view' => 'crm:views/document/fields/name',
          'pattern' => '$noBadCharacters'
        ],
        'file' => [
          'type' => 'file',
          'required' => true,
          'view' => 'crm:views/document/fields/file',
          'accept' => [
            0 => '.pdf',
            1 => '.odt',
            2 => '.ods',
            3 => '.odp',
            4 => '.docx',
            5 => '.xlsx',
            6 => '.pptx',
            7 => '.doc',
            8 => '.xls',
            9 => '.ppt',
            10 => '.rtf',
            11 => '.csv',
            12 => '.md',
            13 => '.txt'
          ],
          'audited' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Draft',
            1 => 'Active',
            2 => 'Canceled',
            3 => 'Expired'
          ],
          'style' => [
            'Active' => 'primary',
            'Canceled' => 'info',
            'Expired' => 'danger'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'default' => 'Active',
          'audited' => true,
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'activeOptions',
              'view' => 'views/admin/field-manager/fields/not-actual-options'
            ]
          ],
          'activeOptions' => [
            0 => 'Active'
          ],
          'customizationOptionsReferenceDisabled' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'Contract',
            2 => 'NDA',
            3 => 'EULA',
            4 => 'License Agreement'
          ],
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'publishDate' => [
          'type' => 'date',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getToday();',
          'audited' => true
        ],
        'expirationDate' => [
          'type' => 'date',
          'after' => 'publishDate',
          'audited' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
          'audited' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'accounts' => [
          'type' => 'linkMultiple',
          'importDisabled' => true,
          'exportDisabled' => true,
          'noLoad' => true,
          'directUpdateDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ]
        ],
        'folder' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree',
          'audited' => true
        ]
      ],
      'links' => [
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'documents'
        ],
        'opportunities' => [
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'documents'
        ],
        'leads' => [
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'documents'
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'documents'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'folder' => [
          'type' => 'belongsTo',
          'foreign' => 'documents',
          'entity' => 'DocumentFolder'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ]
    ],
    'DocumentFolder' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'parent' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'childList' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'orderDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'parent' => [
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'DocumentFolder'
        ],
        'children' => [
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'DocumentFolder',
          'readOnly' => true
        ],
        'documents' => [
          'type' => 'hasMany',
          'foreign' => 'folder',
          'entity' => 'Document'
        ]
      ],
      'collection' => [
        'orderBy' => 'name',
        'order' => 'asc',
        'sortBy' => 'name',
        'asc' => true
      ],
      'additionalTables' => [
        'DocumentFolderPath' => [
          'attributes' => [
            'id' => [
              'type' => 'id',
              'dbType' => 'integer',
              'len' => 11,
              'autoincrement' => true
            ],
            'ascendorId' => [
              'type' => 'foreignId',
              'index' => true
            ],
            'descendorId' => [
              'type' => 'foreignId',
              'index' => true
            ]
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\CategoryTree'
    ],
    'EmailQueueItem' => [
      'fields' => [
        'massEmail' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Pending',
            1 => 'Sent',
            2 => 'Failed',
            3 => 'Sending'
          ],
          'readOnly' => true
        ],
        'attemptCount' => [
          'type' => 'int',
          'readOnly' => true,
          'default' => 0
        ],
        'target' => [
          'type' => 'linkParent',
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'sentAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'index' => true
        ],
        'emailAddress' => [
          'type' => 'varchar',
          'readOnly' => true
        ],
        'isTest' => [
          'type' => 'bool'
        ]
      ],
      'links' => [
        'massEmail' => [
          'type' => 'belongsTo',
          'entity' => 'MassEmail',
          'foreign' => 'queueItems'
        ],
        'target' => [
          'type' => 'belongsToParent'
        ]
      ],
      'collection' => [
        'textFilterFields' => [
          0 => 'id',
          1 => 'emailAddress'
        ],
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ]
    ],
    'KnowledgeBaseArticle' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Draft',
            1 => 'In Review',
            2 => 'Published',
            3 => 'Archived'
          ],
          'style' => [
            'Published' => 'primary',
            'Archived' => 'info'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'view' => 'crm:views/knowledge-base-article/fields/status',
          'default' => 'Draft',
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'activeOptions',
              'view' => 'views/admin/field-manager/fields/not-actual-options'
            ]
          ],
          'activeOptions' => [
            0 => 'Published'
          ],
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'language' => [
          'type' => 'enum',
          'view' => 'crm:views/knowledge-base-article/fields/language',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'audited' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'Article'
          ],
          'default' => 'Article'
        ],
        'portals' => [
          'type' => 'linkMultiple',
          'tooltip' => true,
          'audited' => true
        ],
        'publishDate' => [
          'type' => 'date',
          'audited' => true
        ],
        'expirationDate' => [
          'type' => 'date',
          'after' => 'publishDate',
          'audited' => true
        ],
        'order' => [
          'type' => 'int',
          'disableFormatting' => true,
          'textFilterDisabled' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
          'audited' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'categories' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/link-multiple-category-tree'
        ],
        'attachments' => [
          'type' => 'attachmentMultiple'
        ],
        'body' => [
          'type' => 'wysiwyg'
        ],
        'bodyPlain' => [
          'type' => 'text',
          'readOnly' => true,
          'directUpdateDisabled' => true,
          'fieldManagerParamList' => []
        ]
      ],
      'links' => [
        'cases' => [
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'articles'
        ],
        'portals' => [
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'articles'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'categories' => [
          'type' => 'hasMany',
          'foreign' => 'articles',
          'entity' => 'KnowledgeBaseCategory'
        ]
      ],
      'collection' => [
        'orderBy' => 'order',
        'order' => 'asc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'bodyPlain'
        ],
        'fullTextSearch' => true,
        'sortBy' => 'order',
        'asc' => true
      ],
      'optimisticConcurrencyControl' => true
    ],
    'KnowledgeBaseCategory' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'fieldManagerParamList' => []
        ],
        'order' => [
          'type' => 'int',
          'minValue' => 1,
          'readOnly' => true,
          'disableFormatting' => true,
          'textFilterDisabled' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'parent' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'childList' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'orderDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'parent' => [
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'KnowledgeBaseCategory'
        ],
        'children' => [
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'KnowledgeBaseCategory'
        ],
        'articles' => [
          'type' => 'hasMany',
          'foreign' => 'categories',
          'entity' => 'KnowledgeBaseArticle'
        ]
      ],
      'collection' => [
        'orderBy' => 'parent',
        'orderByColumn' => 'parentId',
        'order' => 'asc',
        'sortBy' => 'parent',
        'asc' => true
      ],
      'additionalTables' => [
        'KnowledgeBaseCategoryPath' => [
          'attributes' => [
            'id' => [
              'type' => 'id',
              'dbType' => 'integer',
              'len' => 11,
              'autoincrement' => true
            ],
            'ascendorId' => [
              'type' => 'foreignId',
              'index' => true
            ],
            'descendorId' => [
              'type' => 'foreignId',
              'index' => true
            ]
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\CategoryTree'
    ],
    'Lead' => [
      'fields' => [
        'name' => [
          'type' => 'personName',
          'isPersonalData' => true,
          'dependeeAttributeList' => [
            0 => 'emailAddress',
            1 => 'phoneNumber',
            2 => 'accountName'
          ]
        ],
        'salutationName' => [
          'type' => 'enum',
          'customizationOptionsReferenceDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'options' => [
            0 => '',
            1 => 'Mr.',
            2 => 'Ms.',
            3 => 'Mrs.',
            4 => 'Dr.'
          ]
        ],
        'firstName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100
        ],
        'lastName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100
        ],
        'title' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
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
          'style' => [
            'In Process' => 'primary',
            'Converted' => 'success',
            'Recycled' => 'info',
            'Dead' => 'info'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'audited' => true,
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'notActualOptions',
              'view' => 'views/admin/field-manager/fields/not-actual-options'
            ]
          ],
          'notActualOptions' => [
            0 => 'Converted',
            1 => 'Recycled',
            2 => 'Dead'
          ],
          'customizationOptionsReferenceDisabled' => true
        ],
        'source' => [
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
          'customizationOptionsReferenceDisabled' => true
        ],
        'industry' => [
          'type' => 'enum',
          'view' => 'crm:views/lead/fields/industry',
          'customizationOptionsDisabled' => true,
          'optionsReference' => 'Account.industry',
          'isSorted' => true
        ],
        'opportunityAmount' => [
          'type' => 'currency',
          'min' => 0,
          'decimal' => false,
          'audited' => true
        ],
        'opportunityAmountConverted' => [
          'type' => 'currencyConverted',
          'readOnly' => true,
          'importDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'website' => [
          'type' => 'url',
          'strip' => true
        ],
        'address' => [
          'type' => 'address',
          'isPersonalData' => true
        ],
        'addressStreet' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCity' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressState' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCountry' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressPostalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'emailAddress' => [
          'type' => 'email',
          'isPersonalData' => true
        ],
        'phoneNumber' => [
          'type' => 'phone',
          'typeList' => [
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other'
          ],
          'defaultType' => 'Mobile',
          'isPersonalData' => true
        ],
        'doNotCall' => [
          'type' => 'bool',
          'audited' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'convertedAt' => [
          'type' => 'datetime',
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'accountName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters'
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'acceptanceStatus' => [
          'type' => 'varchar',
          'notStorable' => true,
          'orderDisabled' => true,
          'exportDisabled' => true,
          'utility' => true,
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusMeetings' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'meetings',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'acceptanceStatusCalls' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'layoutAvailabilityList' => [
            0 => 'filters'
          ],
          'importDisabled' => true,
          'exportDisabled' => true,
          'view' => 'crm:views/lead/fields/acceptance-status',
          'link' => 'calls',
          'column' => 'status',
          'fieldManagerParamList' => []
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'campaign' => [
          'type' => 'link'
        ],
        'createdAccount' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true
        ],
        'createdContact' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'view' => 'crm:views/lead/fields/created-contact'
        ],
        'createdOpportunity' => [
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'view' => 'crm:views/lead/fields/created-opportunity'
        ],
        'targetLists' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'importDisabled' => true,
          'directAccessDisabled' => true,
          'directUpdateEnabled' => true,
          'filtersEnabled' => true,
          'noLoad' => true
        ],
        'targetList' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'entity' => 'TargetList',
          'directAccessDisabled' => true,
          'importEnabled' => true
        ],
        'targetListIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'utility' => true
        ],
        'originalEmail' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'entity' => 'Email',
          'customizationDisabled' => true,
          'layoutAvailabilityList' => [],
          'directAccessDisabled' => true
        ],
        'middleName' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ]
        ],
        'opportunityAmountCurrency' => [
          'type' => 'enum',
          'view' => 'views/fields/currency-list',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationDisplayAsLabelDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationDefaultView' => 'views/admin/field-manager/fields/currency-default',
          'customizationTooltipTextDisabled' => true,
          'maxLength' => 3,
          'apiSpecDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'opportunityAmount'
          ]
        ],
        'addressMap' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'emailAddressIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'emailAddressIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'phoneNumberIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'phoneNumberIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'meetings' => [
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'leads',
          'audited' => true,
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'calls' => [
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'leads',
          'audited' => true,
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'tasks' => [
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'audited' => true
        ],
        'cases' => [
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'lead',
          'audited' => true
        ],
        'emails' => [
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true
        ],
        'createdAccount' => [
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'originalLead'
        ],
        'createdContact' => [
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'originalLead'
        ],
        'createdOpportunity' => [
          'type' => 'belongsTo',
          'entity' => 'Opportunity',
          'foreign' => 'originalLead'
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'leads'
        ],
        'campaignLogRecords' => [
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent'
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'leads',
          'columnAttributeMap' => [
            'optedOut' => 'targetListIsOptedOut'
          ]
        ],
        'documents' => [
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
      'convertFields' => [
        'Contact' => [],
        'Account' => [
          'name' => 'accountName',
          'billingAddressStreet' => 'addressStreet',
          'billingAddressCity' => 'addressCity',
          'billingAddressState' => 'addressState',
          'billingAddressPostalCode' => 'addressPostalCode',
          'billingAddressCountry' => 'addressCountry'
        ],
        'Opportunity' => [
          'amount' => 'opportunityAmount',
          'leadSource' => 'source'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'textFilterFields' => [
          0 => 'name',
          1 => 'accountName',
          2 => 'emailAddress'
        ],
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'firstName' => [
          'columns' => [
            0 => 'firstName',
            1 => 'deleted'
          ]
        ],
        'name' => [
          'columns' => [
            0 => 'firstName',
            1 => 'lastName'
          ]
        ],
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ],
        'createdAtStatus' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'status'
          ]
        ],
        'createdAtId' => [
          'unique' => true,
          'columns' => [
            0 => 'createdAt',
            1 => 'id'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'assignedUserStatus' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'status'
          ]
        ]
      ]
    ],
    'MassEmail' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Draft',
            1 => 'Pending',
            2 => 'Complete',
            3 => 'In Process',
            4 => 'Failed'
          ],
          'style' => [
            'In Process' => 'warning',
            'Pending' => 'primary',
            'Failed' => 'danger',
            'Complete' => 'success'
          ],
          'default' => 'Pending'
        ],
        'storeSentEmails' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'optOutEntirely' => [
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        'fromAddress' => [
          'type' => 'varchar',
          'view' => 'crm:views/mass-email/fields/from-address'
        ],
        'fromName' => [
          'type' => 'varchar'
        ],
        'replyToAddress' => [
          'type' => 'varchar'
        ],
        'replyToName' => [
          'type' => 'varchar'
        ],
        'startAt' => [
          'type' => 'datetime',
          'required' => true
        ],
        'emailTemplate' => [
          'type' => 'link',
          'required' => true,
          'createButton' => true,
          'view' => 'crm:views/mass-email/fields/email-template'
        ],
        'campaign' => [
          'type' => 'link',
          'readOnlyAfterCreate' => true
        ],
        'targetLists' => [
          'type' => 'linkMultiple',
          'required' => true,
          'tooltip' => true
        ],
        'excludingTargetLists' => [
          'type' => 'linkMultiple',
          'tooltip' => true
        ],
        'inboundEmail' => [
          'type' => 'link'
        ],
        'smtpAccount' => [
          'type' => 'base',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'crm:views/mass-email/fields/smtp-account'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'emailTemplate' => [
          'type' => 'belongsTo',
          'entity' => 'EmailTemplate'
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'massEmails'
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'massEmails'
        ],
        'excludingTargetLists' => [
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'massEmailsExcluding',
          'relationName' => 'massEmailTargetListExcluding'
        ],
        'inboundEmail' => [
          'type' => 'belongsTo',
          'entity' => 'InboundEmail'
        ],
        'queueItems' => [
          'type' => 'hasMany',
          'entity' => 'EmailQueueItem',
          'foreign' => 'massEmail'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ]
    ],
    'Meeting' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Planned',
            1 => 'Held',
            2 => 'Not Held'
          ],
          'default' => 'Planned',
          'style' => [
            'Held' => 'success',
            'Not Held' => 'info'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'audited' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'dateStart' => [
          'type' => 'datetimeOptional',
          'view' => 'crm:views/meeting/fields/date-start',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
          'audited' => true
        ],
        'dateEnd' => [
          'type' => 'datetimeOptional',
          'view' => 'crm:views/meeting/fields/date-end',
          'required' => true,
          'after' => 'dateStart',
          'suppressValidationList' => [
            0 => 'required'
          ]
        ],
        'isAllDay' => [
          'type' => 'bool',
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true
        ],
        'duration' => [
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
          'select' => [
            'select' => 'TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)'
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)',
                1 => '{direction}'
              ]
            ]
          ]
        ],
        'reminders' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'crm:views/meeting/fields/reminders',
          'layoutListDisabled' => true,
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\Valid',
            1 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\MaxCount'
          ],
          'dynamicLogicDisabled' => true,
          'duplicateIgnore' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'parent' => [
          'type' => 'linkParent',
          'entityList' => [
            0 => 'Account',
            1 => 'Lead',
            2 => 'Contact',
            3 => 'Opportunity',
            4 => 'Case'
          ]
        ],
        'account' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ],
        'uid' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'index' => true,
          'readOnly' => true,
          'duplicateIgnore' => true
        ],
        'joinUrl' => [
          'type' => 'url',
          'dbType' => 'text',
          'maxLength' => 320,
          'readOnly' => true,
          'copyToClipboard' => true,
          'duplicateIgnore' => true,
          'default' => NULL,
          'customizationDefaultDisabled' => true
        ],
        'acceptanceStatus' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'options' => [
            0 => 'None',
            1 => 'Accepted',
            2 => 'Tentative',
            3 => 'Declined'
          ],
          'style' => [
            'Accepted' => 'success',
            'Declined' => 'danger',
            'Tentative' => 'warning'
          ],
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'importDisabled' => true,
          'exportDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'directAccessDisabled' => true,
          'filtersEnabled' => true,
          'where' => [
            '=' => [
              'whereClause' => [
                'OR' => [
                  0 => [
                    'id=s' => [
                      'from' => 'ContactMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id=s' => [
                      'from' => 'LeadMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id=s' => [
                      'from' => 'MeetingUser',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'AND' => [
                  0 => [
                    'id!=s' => [
                      'from' => 'ContactMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id!=s' => [
                      'from' => 'LeadMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id!=s' => [
                      'from' => 'MeetingUser',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'IN' => [
              'whereClause' => [
                'OR' => [
                  0 => [
                    'id=s' => [
                      'from' => 'ContactMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id=s' => [
                      'from' => 'LeadMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id=s' => [
                      'from' => 'MeetingUser',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ],
            'NOT IN' => [
              'whereClause' => [
                'AND' => [
                  0 => [
                    'id!=s' => [
                      'from' => 'ContactMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  1 => [
                    'id!=s' => [
                      'from' => 'LeadMeeting',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ],
                  2 => [
                    'id!=s' => [
                      'from' => 'MeetingUser',
                      'select' => [
                        0 => 'meetingId'
                      ],
                      'whereClause' => [
                        'deleted' => false,
                        'status' => '{value}'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ],
          'view' => 'crm:views/meeting/fields/acceptance-status'
        ],
        'users' => [
          'type' => 'linkMultiple',
          'view' => 'crm:views/meeting/fields/users',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'audited' => true,
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees'
        ],
        'contacts' => [
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/contacts',
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'audited' => true,
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees'
        ],
        'leads' => [
          'type' => 'linkMultiple',
          'view' => 'crm:views/meeting/fields/attendees',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'columns' => [
            'status' => 'acceptanceStatus'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'orderBy' => 'name',
          'audited' => true,
          'duplicatorClassName' => 'Espo\\Modules\\Crm\\Classes\\FieldDuplicators\\Meeting\\Attendees'
        ],
        'sourceEmail' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'exportDisabled' => true,
          'importDisabled' => true,
          'entity' => 'Email',
          'directAccessDisabled' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'dateStartDate' => [
          'type' => 'date',
          'utility' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'dateStart'
          ]
        ],
        'dateEndDate' => [
          'type' => 'date',
          'utility' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'dateEnd'
          ]
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account'
        ],
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'meetings',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'meetings',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'leads' => [
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'meetings',
          'additionalColumns' => [
            'status' => [
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None'
            ]
          ],
          'columnAttributeMap' => [
            'status' => 'acceptanceStatus'
          ]
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'foreign' => 'meetings'
        ]
      ],
      'collection' => [
        'orderBy' => 'dateStart',
        'order' => 'desc',
        'sortBy' => 'dateStart',
        'asc' => false
      ],
      'indexes' => [
        'dateStartStatus' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'status'
          ]
        ],
        'dateStart' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'deleted'
          ]
        ],
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'assignedUserStatus' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'status'
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\Event'
    ],
    'Opportunity' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'amount' => [
          'type' => 'currency',
          'required' => true,
          'min' => 0,
          'decimal' => false,
          'audited' => true
        ],
        'amountConverted' => [
          'type' => 'currencyConverted',
          'readOnly' => true,
          'importDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'amountWeightedConverted' => [
          'type' => 'float',
          'readOnly' => true,
          'notStorable' => true,
          'select' => [
            'select' => 'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)',
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRate',
                2 => [
                  'amountCurrencyRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          'where' => [
            '=' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)=' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            '<' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)<' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            '>' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)>' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            '<=' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)<=' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            '>=' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)>=' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            '<>' => [
              'whereClause' => [
                'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)!=' => '{value}'
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            'IS NULL' => [
              'whereClause' => [
                'IS_NULL:(amount)' => true
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ],
            'IS NOT NULL' => [
              'whereClause' => [
                'IS_NOT_NULL:(amount)' => true
              ],
              'leftJoins' => [
                0 => [
                  0 => 'Currency',
                  1 => 'amountCurrencyRate',
                  2 => [
                    'amountCurrencyRate.id:' => 'amountCurrency'
                  ]
                ]
              ]
            ]
          ],
          'order' => [
            'order' => [
              0 => [
                0 => 'DIV:(MUL:(amount, probability, amountCurrencyRate.rate), 100)',
                1 => '{direction}'
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRate',
                2 => [
                  'amountCurrencyRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          'view' => 'views/fields/currency-converted'
        ],
        'account' => [
          'type' => 'link',
          'audited' => true
        ],
        'contacts' => [
          'type' => 'linkMultiple',
          'view' => 'crm:views/opportunity/fields/contacts',
          'columns' => [
            'role' => 'opportunityRole'
          ],
          'additionalAttributeList' => [
            0 => 'columns'
          ],
          'primaryLink' => 'contact',
          'orderBy' => 'name',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'contact'
          ]
        ],
        'contact' => [
          'type' => 'link'
        ],
        'stage' => [
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
          'probabilityMap' => [
            'Prospecting' => 10,
            'Qualification' => 20,
            'Proposal' => 50,
            'Negotiation' => 80,
            'Closed Won' => 100,
            'Closed Lost' => 0
          ],
          'style' => [
            'Proposal' => 'primary',
            'Negotiation' => 'warning',
            'Closed Won' => 'success',
            'Closed Lost' => 'info'
          ],
          'displayAsLabel' => true,
          'labelType' => 'state',
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'probabilityMap',
              'view' => 'crm:views/opportunity/admin/field-manager/fields/probability-map'
            ]
          ],
          'customizationOptionsReferenceDisabled' => true
        ],
        'lastStage' => [
          'type' => 'enum',
          'view' => 'crm:views/opportunity/fields/last-stage',
          'customizationOptionsDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'probability' => [
          'type' => 'int',
          'min' => 0,
          'max' => 100
        ],
        'leadSource' => [
          'type' => 'enum',
          'view' => 'crm:views/opportunity/fields/lead-source',
          'customizationOptionsDisabled' => true,
          'optionsReference' => 'Lead.source',
          'audited' => true
        ],
        'closeDate' => [
          'type' => 'date',
          'required' => true,
          'audited' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'campaign' => [
          'type' => 'link',
          'audited' => true
        ],
        'originalLead' => [
          'type' => 'linkOne',
          'readOnly' => true,
          'view' => 'views/fields/link-one'
        ],
        'contactRole' => [
          'type' => 'enum',
          'notStorable' => true,
          'orderDisabled' => true,
          'directUpdateDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutAvailabilityList' => [
            0 => 'listForContact'
          ],
          'customizationDefaultDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationOptionsDisabled' => true,
          'converterClassName' => 'Espo\\Classes\\FieldConverters\\RelationshipRole',
          'converterData' => [
            'column' => 'role',
            'link' => 'contacts',
            'relationName' => 'contactOpportunity',
            'nearKey' => 'opportunityId'
          ],
          'view' => 'crm:views/opportunity/fields/contact-role',
          'optionsReference' => 'Contact.opportunityRole'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => false,
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'amountCurrency' => [
          'type' => 'enum',
          'view' => 'views/fields/currency-list',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationDisplayAsLabelDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationDefaultView' => 'views/admin/field-manager/fields/currency-default',
          'customizationTooltipTextDisabled' => true,
          'maxLength' => 3,
          'apiSpecDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'amount'
          ]
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'opportunities'
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'opportunities',
          'additionalColumns' => [
            'role' => [
              'type' => 'varchar',
              'len' => 50
            ]
          ],
          'columnAttributeMap' => [
            'role' => 'contactRole'
          ]
        ],
        'contact' => [
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'opportunitiesPrimary'
        ],
        'meetings' => [
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'audited' => true
        ],
        'calls' => [
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'audited' => true
        ],
        'tasks' => [
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'audited' => true
        ],
        'emails' => [
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true
        ],
        'documents' => [
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'opportunities',
          'audited' => true
        ],
        'campaign' => [
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'opportunities'
        ],
        'originalLead' => [
          'type' => 'hasOne',
          'entity' => 'Lead',
          'foreign' => 'createdOpportunity'
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'stage' => [
          'columns' => [
            0 => 'stage',
            1 => 'deleted'
          ]
        ],
        'lastStage' => [
          'columns' => [
            0 => 'lastStage'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ],
        'createdAtStage' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'stage'
          ]
        ],
        'createdAtId' => [
          'unique' => true,
          'columns' => [
            0 => 'createdAt',
            1 => 'id'
          ]
        ],
        'assignedUserStage' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'stage'
          ]
        ]
      ],
      'optimisticConcurrencyControl' => true
    ],
    'Reminder' => [
      'fields' => [
        'remindAt' => [
          'type' => 'datetime',
          'index' => true
        ],
        'startAt' => [
          'type' => 'datetime',
          'index' => true
        ],
        'type' => [
          'type' => 'enum',
          'options' => [
            0 => 'Popup',
            1 => 'Email'
          ],
          'maxLength' => 36,
          'index' => true,
          'default' => 'Popup'
        ],
        'seconds' => [
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
            9 => 18000,
            10 => 86400,
            11 => 604800
          ],
          'default' => 0
        ],
        'user' => [
          'type' => 'link'
        ],
        'entity' => [
          'type' => 'linkParent'
        ],
        'isSubmitted' => [
          'type' => 'bool'
        ]
      ],
      'links' => [
        'user' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'entity' => [
          'type' => 'belongsToParent'
        ]
      ],
      'collection' => [
        'orderBy' => 'remindAt',
        'order' => 'desc',
        'sortBy' => 'remindAt',
        'asc' => false
      ]
    ],
    'Target' => [
      'fields' => [
        'name' => [
          'type' => 'personName'
        ],
        'salutationName' => [
          'type' => 'enum',
          'customizationOptionsReferenceDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'options' => [
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
            5 => 'Drs.'
          ]
        ],
        'firstName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100,
          'default' => ''
        ],
        'lastName' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ],
          'maxLength' => 100,
          'required' => true,
          'default' => ''
        ],
        'title' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'accountName' => [
          'type' => 'varchar',
          'maxLength' => 100
        ],
        'website' => [
          'type' => 'url'
        ],
        'address' => [
          'type' => 'address'
        ],
        'addressStreet' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCity' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressState' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressCountry' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'addressPostalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'address'
          ]
        ],
        'emailAddress' => [
          'type' => 'email'
        ],
        'phoneNumber' => [
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
        'doNotCall' => [
          'type' => 'bool'
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user'
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'middleName' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters',
          'detailLayoutIncompatibleFieldList' => [
            0 => 'name'
          ]
        ],
        'addressMap' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ],
        'emailAddressIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'emailAddressIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'emailAddress'
          ]
        ],
        'phoneNumberIsOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ],
        'phoneNumberIsInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'phoneNumber'
          ]
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'firstName' => [
          'columns' => [
            0 => 'firstName',
            1 => 'deleted'
          ]
        ],
        'name' => [
          'columns' => [
            0 => 'firstName',
            1 => 'lastName'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ]
      ]
    ],
    'TargetList' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'category' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'entryCount' => [
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutMassUpdateDisabled' => true
        ],
        'optedOutCount' => [
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'layoutListDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutMassUpdateDisabled' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'sourceCampaign' => [
          'type' => 'link',
          'notStorable' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true,
          'customizationDisabled' => true,
          'layoutAvailabilityList' => []
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
          'audited' => true
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'includingActionList' => [
          'type' => 'multiEnum',
          'view' => 'crm:views/target-list/fields/including-action-list',
          'layoutDetailDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutLinkDisabled' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true
        ],
        'excludingActionList' => [
          'type' => 'multiEnum',
          'view' => 'crm:views/target-list/fields/including-action-list',
          'layoutDetailDisabled' => true,
          'layoutFiltersDisabled' => true,
          'layoutLinkDisabled' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'utility' => true
        ],
        'targetStatus' => [
          'type' => 'enum',
          'options' => [
            0 => 'Listed',
            1 => 'Opted Out'
          ],
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'exportDisabled' => true,
          'importDisabled' => true,
          'view' => 'crm:views/target-list/fields/target-status'
        ],
        'isOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'exportDisabled' => true,
          'importDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'category' => [
          'type' => 'belongsTo',
          'foreign' => 'category',
          'entity' => 'TargetListCategory'
        ],
        'campaigns' => [
          'type' => 'hasMany',
          'entity' => 'Campaign',
          'foreign' => 'targetLists',
          'layoutRelationshipsDisabled' => true
        ],
        'massEmails' => [
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'targetLists',
          'layoutRelationshipsDisabled' => true
        ],
        'campaignsExcluding' => [
          'type' => 'hasMany',
          'entity' => 'Campaign',
          'foreign' => 'excludingTargetLists',
          'layoutRelationshipsDisabled' => true
        ],
        'massEmailsExcluding' => [
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'excludingTargetLists',
          'layoutRelationshipsDisabled' => true
        ],
        'accounts' => [
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'targetLists',
          'additionalColumns' => [
            'optedOut' => [
              'type' => 'bool'
            ]
          ],
          'columnAttributeMap' => [
            'optedOut' => 'isOptedOut'
          ]
        ],
        'contacts' => [
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'targetLists',
          'additionalColumns' => [
            'optedOut' => [
              'type' => 'bool'
            ]
          ],
          'columnAttributeMap' => [
            'optedOut' => 'isOptedOut'
          ]
        ],
        'leads' => [
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'targetLists',
          'additionalColumns' => [
            'optedOut' => [
              'type' => 'bool'
            ]
          ],
          'columnAttributeMap' => [
            'optedOut' => 'isOptedOut'
          ]
        ],
        'users' => [
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'targetLists',
          'additionalColumns' => [
            'optedOut' => [
              'type' => 'bool'
            ]
          ],
          'columnAttributeMap' => [
            'optedOut' => 'isOptedOut'
          ]
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'createdAt' => [
          'columns' => [
            0 => 'createdAt',
            1 => 'deleted'
          ]
        ]
      ]
    ],
    'TargetListCategory' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true
        ],
        'order' => [
          'type' => 'int',
          'minValue' => 1,
          'readOnly' => true,
          'textFilterDisabled' => true
        ],
        'description' => [
          'type' => 'text'
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true
        ],
        'teams' => [
          'type' => 'linkMultiple'
        ],
        'parent' => [
          'type' => 'link',
          'view' => 'views/fields/link-category-tree'
        ],
        'childList' => [
          'type' => 'jsonArray',
          'notStorable' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'parent' => [
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'TargetListCategory'
        ],
        'children' => [
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'TargetListCategory',
          'readOnly' => true
        ],
        'targetLists' => [
          'type' => 'hasMany',
          'foreign' => 'category',
          'entity' => 'TargetList'
        ]
      ],
      'collection' => [
        'orderBy' => 'parent',
        'order' => 'asc',
        'sortBy' => 'parent',
        'asc' => true
      ],
      'additionalTables' => [
        'TargetListCategoryPath' => [
          'attributes' => [
            'id' => [
              'type' => 'id',
              'dbType' => 'integer',
              'len' => 11,
              'autoincrement' => true
            ],
            'ascendorId' => [
              'type' => 'foreignId',
              'index' => true
            ],
            'descendorId' => [
              'type' => 'foreignId',
              'index' => true
            ]
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\CategoryTree'
    ],
    'Task' => [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'required' => true,
          'pattern' => '$noBadCharacters'
        ],
        'status' => [
          'type' => 'enum',
          'options' => [
            0 => 'Not Started',
            1 => 'Started',
            2 => 'Completed',
            3 => 'Canceled',
            4 => 'Deferred'
          ],
          'style' => [
            'Completed' => 'success',
            'Started' => 'primary',
            'Canceled' => 'info'
          ],
          'default' => 'Not Started',
          'displayAsLabel' => true,
          'labelType' => 'state',
          'audited' => true,
          'fieldManagerAdditionalParamList' => [
            0 => [
              'name' => 'notActualOptions',
              'view' => 'views/admin/field-manager/fields/not-actual-options'
            ]
          ],
          'notActualOptions' => [
            0 => 'Completed',
            1 => 'Canceled',
            2 => 'Deferred'
          ],
          'customizationOptionsReferenceDisabled' => true
        ],
        'priority' => [
          'type' => 'enum',
          'options' => [
            0 => 'Low',
            1 => 'Normal',
            2 => 'High',
            3 => 'Urgent'
          ],
          'default' => 'Normal',
          'displayAsLabel' => true,
          'style' => [
            'High' => 'warning',
            'Urgent' => 'danger'
          ],
          'audited' => true,
          'customizationOptionsReferenceDisabled' => true
        ],
        'dateStart' => [
          'type' => 'datetimeOptional',
          'before' => 'dateEnd'
        ],
        'dateEnd' => [
          'type' => 'datetimeOptional',
          'after' => 'dateStart',
          'view' => 'crm:views/task/fields/date-end',
          'audited' => true
        ],
        'dateStartDate' => [
          'type' => 'date',
          'utility' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'dateStart'
          ]
        ],
        'dateEndDate' => [
          'type' => 'date',
          'utility' => true,
          'detailLayoutIncompatibleFieldList' => [
            0 => 'dateEnd'
          ]
        ],
        'dateCompleted' => [
          'type' => 'datetime',
          'readOnly' => true
        ],
        'isOverdue' => [
          'type' => 'bool',
          'readOnly' => true,
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'crm:views/task/fields/is-overdue',
          'utility' => true
        ],
        'reminders' => [
          'type' => 'jsonArray',
          'notStorable' => true,
          'orderDisabled' => true,
          'view' => 'crm:views/meeting/fields/reminders',
          'dateField' => 'dateEnd',
          'validatorClassNameList' => [
            0 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\Valid',
            1 => 'Espo\\Modules\\Crm\\Classes\\FieldValidators\\Event\\Reminders\\MaxCount'
          ],
          'duplicateIgnore' => true,
          'dynamicLogicDisabled' => true
        ],
        'description' => [
          'type' => 'text',
          'preview' => true,
          'attachmentField' => 'attachments',
          'cutHeight' => 500
        ],
        'parent' => [
          'type' => 'linkParent',
          'entityList' => [
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'Case'
          ]
        ],
        'account' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ],
        'contact' => [
          'type' => 'link',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ],
        'originalEmail' => [
          'type' => 'link',
          'notStorable' => true,
          'entity' => 'Email',
          'utility' => true,
          'orderDisabled' => true,
          'directAccessDisabled' => true
        ],
        'createdAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'modifiedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'fieldManagerParamList' => [
            0 => 'useNumericFormat'
          ]
        ],
        'createdBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'modifiedBy' => [
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
          'fieldManagerParamList' => []
        ],
        'assignedUser' => [
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user'
        ],
        'teams' => [
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams'
        ],
        'attachments' => [
          'type' => 'attachmentMultiple',
          'sourceList' => [
            0 => 'Document'
          ]
        ],
        'streamUpdatedAt' => [
          'type' => 'datetime',
          'readOnly' => true,
          'customizationReadOnlyDisabled' => true
        ]
      ],
      'links' => [
        'createdBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'modifiedBy' => [
          'type' => 'belongsTo',
          'entity' => 'User'
        ],
        'assignedUser' => [
          'type' => 'belongsTo',
          'entity' => 'User',
          'foreign' => 'tasks'
        ],
        'teams' => [
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true
        ],
        'parent' => [
          'type' => 'belongsToParent',
          'foreign' => 'tasks'
        ],
        'account' => [
          'type' => 'belongsTo',
          'entity' => 'Account'
        ],
        'contact' => [
          'type' => 'belongsTo',
          'entity' => 'Contact'
        ],
        'email' => [
          'type' => 'belongsTo',
          'entity' => 'Email',
          'foreign' => 'tasks',
          'noForeignName' => true
        ]
      ],
      'collection' => [
        'orderBy' => 'createdAt',
        'order' => 'desc',
        'sortBy' => 'createdAt',
        'asc' => false
      ],
      'indexes' => [
        'dateStartStatus' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'status'
          ]
        ],
        'dateEndStatus' => [
          'columns' => [
            0 => 'dateEnd',
            1 => 'status'
          ]
        ],
        'dateStart' => [
          'columns' => [
            0 => 'dateStart',
            1 => 'deleted'
          ]
        ],
        'status' => [
          'columns' => [
            0 => 'status',
            1 => 'deleted'
          ]
        ],
        'assignedUser' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'deleted'
          ]
        ],
        'assignedUserStatus' => [
          'columns' => [
            0 => 'assignedUserId',
            1 => 'status'
          ]
        ]
      ],
      'repositoryClassName' => 'Espo\\Core\\Repositories\\Event',
      'optimisticConcurrencyControl' => true
    ]
  ],
  'fields' => [
    'address' => [
      'actualFields' => [
        0 => 'street',
        1 => 'city',
        2 => 'state',
        3 => 'country',
        4 => 'postalCode'
      ],
      'fields' => [
        'street' => [
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar'
        ],
        'city' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-city',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters'
        ],
        'state' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-state',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters'
        ],
        'country' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'view' => 'views/fields/address-country',
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'pattern' => '$noBadCharacters'
        ],
        'postalCode' => [
          'type' => 'varchar',
          'maxLength' => 40,
          'pattern' => '$noBadCharacters'
        ],
        'map' => [
          'type' => 'map',
          'notStorable' => true,
          'orderDisabled' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
          'exportDisabled' => true,
          'importDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ]
      ],
      'params' => [
        0 => [
          'name' => 'viewMap',
          'type' => 'bool'
        ]
      ],
      'notMergeable' => true,
      'notCreatable' => false,
      'filter' => true,
      'skipOrmDefs' => true,
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\Address\\AddressFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\Address\\AddressAttributeExtractor'
    ],
    'array' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options',
          'tooltip' => 'optionsArray'
        ],
        2 => [
          'name' => 'optionsReference',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/options-reference',
          'tooltip' => true
        ],
        3 => [
          'name' => 'default',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/options/default-multi'
        ],
        4 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ],
        5 => [
          'name' => 'allowCustomOptions',
          'type' => 'bool',
          'hidden' => true
        ],
        6 => [
          'name' => 'noEmptyString',
          'type' => 'bool',
          'default' => true
        ],
        7 => [
          'name' => 'displayAsList',
          'type' => 'bool',
          'tooltip' => true
        ],
        8 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ],
        9 => [
          'name' => 'itemsEditable',
          'type' => 'bool',
          'tooltip' => true
        ],
        10 => [
          'name' => 'pattern',
          'type' => 'varchar',
          'tooltip' => true,
          'view' => 'views/admin/field-manager/fields/pattern'
        ],
        11 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        12 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        13 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        14 => [
          'name' => 'optionsPath',
          'type' => 'varchar',
          'hidden' => true
        ],
        15 => [
          'name' => 'keepItems',
          'type' => 'bool',
          'hidden' => true
        ],
        16 => [
          'name' => 'maxItemLength',
          'type' => 'int',
          'hidden' => true
        ]
      ],
      'validationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'required',
        4 => 'maxCount',
        5 => 'maxItemLength',
        6 => 'pattern',
        7 => 'noEmptyString'
      ],
      'mandatoryValidationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'maxItemLength'
      ],
      'filter' => true,
      'notCreatable' => false,
      'notSortable' => true,
      'fieldDefs' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true
      ],
      'translatedOptions' => true,
      'dynamicLogicOptions' => true,
      'personalData' => true,
      'massUpdateActionList' => [
        0 => 'update',
        1 => 'add',
        2 => 'remove'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\ArrayFromNull',
        1 => 'Espo\\Classes\\FieldSanitizers\\ArrayStringTrim'
      ],
      'default' => []
    ],
    'arrayInt' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'options',
          'type' => 'arrayInt'
        ],
        2 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ],
        3 => [
          'name' => 'noEmptyString',
          'type' => 'bool',
          'default' => false
        ],
        4 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'array'
      ],
      'mandatoryValidationList' => [
        0 => 'array'
      ],
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => [
        'type' => 'jsonArray'
      ]
    ],
    'attachmentMultiple' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'audited',
          'type' => 'bool',
          'tooltip' => true
        ],
        2 => [
          'name' => 'sourceList',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/source-list'
        ],
        3 => [
          'name' => 'maxFileSize',
          'type' => 'float',
          'tooltip' => true,
          'min' => 0
        ],
        4 => [
          'name' => 'previewSize',
          'type' => 'enum',
          'default' => 'medium',
          'options' => [
            0 => '',
            1 => 'x-small',
            2 => 'small',
            3 => 'medium',
            4 => 'large'
          ]
        ],
        5 => [
          'name' => 'accept',
          'type' => 'multiEnum',
          'noEmptyString' => true,
          'allowCustomOptions' => true,
          'options' => [
            0 => 'image/*',
            1 => 'audio/*',
            2 => 'video/*',
            3 => '.zip',
            4 => '.pdf',
            5 => '.odt',
            6 => '.ods',
            7 => '.odp',
            8 => '.docx',
            9 => '.xlsx',
            10 => '.pptx',
            11 => '.doc',
            12 => '.xls',
            13 => '.ppt',
            14 => '.rtf',
            15 => '.csv',
            16 => '.md',
            17 => '.txt'
          ],
          'tooltip' => 'fileAccept'
        ],
        6 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ]
      ],
      'actualFields' => [
        0 => 'ids'
      ],
      'notActualFields' => [
        0 => 'names',
        1 => 'types'
      ],
      'linkDefs' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreign' => 'parent',
        'layoutRelationshipsDisabled' => true,
        'relationName' => 'attachments',
        'utility' => true
      ],
      'notSortable' => true,
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\AttachmentMultiple',
      'filter' => true,
      'personalData' => true,
      'validationList' => [
        0 => 'required',
        1 => 'pattern',
        2 => 'maxCount'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern'
      ],
      'validatorClassName' => 'Espo\\Classes\\FieldValidators\\LinkMultipleType',
      'duplicatorClassName' => 'Espo\\Classes\\FieldDuplicators\\AttachmentMultiple',
      'massUpdateActionList' => [
        0 => 'update',
        1 => 'add'
      ]
    ],
    'autoincrement' => [
      'params' => [],
      'notCreatable' => false,
      'filter' => true,
      'fieldDefs' => [
        'type' => 'int',
        'autoincrement' => true,
        'unique' => true
      ],
      'hookClassName' => 'Espo\\Tools\\FieldManager\\Hooks\\AutoincrementType',
      'textFilter' => true,
      'readOnly' => true,
      'default' => NULL
    ],
    'barcode' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'codeType',
          'type' => 'enum',
          'options' => [
            0 => 'CODE128',
            1 => 'CODE128A',
            2 => 'CODE128B',
            3 => 'CODE128C',
            4 => 'EAN13',
            5 => 'EAN8',
            6 => 'EAN5',
            7 => 'EAN2',
            8 => 'UPC',
            9 => 'UPCE',
            10 => 'ITF14',
            11 => 'pharmacode',
            12 => 'QRcode'
          ],
          'translation' => 'FieldManager.options.barcodeType'
        ],
        2 => [
          'name' => 'lastChar',
          'type' => 'varchar',
          'maxLength' => 1,
          'tooltip' => 'barcodeLastChar'
        ],
        3 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxLength'
      ],
      'mandatoryValidationList' => [
        0 => 'maxLength'
      ],
      'filter' => true,
      'textFilter' => true,
      'textFilterForeign' => true,
      'fieldDefs' => [
        'type' => 'varchar',
        'len' => 255
      ],
      'validatorClassName' => 'Espo\\Classes\\FieldValidators\\VarcharType',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'default' => NULL
    ],
    'base' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool'
        ]
      ],
      'filter' => false,
      'notCreatable' => true,
      'fieldDefs' => [
        'notStorable' => true
      ]
    ],
    'bool' => [
      'params' => [
        0 => [
          'name' => 'default',
          'type' => 'bool'
        ],
        1 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        2 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'filter' => true,
      'fieldDefs' => [
        'notNull' => true
      ],
      'default' => false
    ],
    'checklist' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options',
          'noEmptyString' => true,
          'required' => true,
          'tooltip' => true
        ],
        2 => [
          'name' => 'optionsReference',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/options-reference',
          'tooltip' => true
        ],
        3 => [
          'name' => 'default',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/options/default-multi'
        ],
        4 => [
          'name' => 'isSorted',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ],
        6 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ],
        7 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        9 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        10 => [
          'name' => 'optionsPath',
          'type' => 'varchar',
          'hidden' => true
        ]
      ],
      'validationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'required',
        4 => 'maxCount'
      ],
      'mandatoryValidationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid'
      ],
      'filter' => true,
      'notCreatable' => false,
      'notSortable' => true,
      'fieldDefs' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true
      ],
      'translatedOptions' => true,
      'dynamicLogicOptions' => true,
      'personalData' => true,
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\ArrayFromNull',
        1 => 'Espo\\Classes\\FieldSanitizers\\ArrayStringTrim'
      ],
      'default' => []
    ],
    'colorpicker' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'colorpicker'
        ],
        2 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'filter' => false,
      'fieldDefs' => [
        'type' => 'varchar',
        'maxLength' => 7
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'notCreatable' => true
    ],
    'currency' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'float'
        ],
        2 => [
          'name' => 'min',
          'type' => 'float'
        ],
        3 => [
          'name' => 'max',
          'type' => 'float'
        ],
        4 => [
          'name' => 'onlyDefaultCurrency',
          'type' => 'bool',
          'default' => false
        ],
        5 => [
          'name' => 'conversionDisabled',
          'type' => 'bool',
          'default' => false,
          'tooltip' => true
        ],
        6 => [
          'name' => 'decimal',
          'type' => 'bool',
          'readOnlyNotNew' => true,
          'tooltip' => 'currencyDecimal'
        ],
        7 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        9 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        10 => [
          'name' => 'precision',
          'type' => 'int',
          'hidden' => true
        ],
        11 => [
          'name' => 'scale',
          'type' => 'int',
          'hidden' => true
        ]
      ],
      'actualFields' => [
        0 => 'currency',
        1 => ''
      ],
      'fields' => [
        'currency' => [
          'type' => 'enum',
          'view' => 'views/fields/currency-list',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'customizationRequiredDisabled' => true,
          'customizationOptionsDisabled' => true,
          'customizationOptionsReferenceDisabled' => true,
          'customizationIsSortedDisabled' => true,
          'customizationDisplayAsLabelDisabled' => true,
          'customizationAuditedDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'customizationDefaultView' => 'views/admin/field-manager/fields/currency-default',
          'customizationTooltipTextDisabled' => true,
          'maxLength' => 3,
          'apiSpecDisabled' => true
        ],
        'converted' => [
          'type' => 'currencyConverted',
          'readOnly' => true,
          'importDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true,
          'detailLayoutIncompatibleFieldList' => []
        ]
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\Currency',
      'validationList' => [
        0 => 'required',
        1 => 'min',
        2 => 'max'
      ],
      'mandatoryValidationList' => [
        0 => 'valid',
        1 => 'validCurrency',
        2 => 'inPermittedRange'
      ],
      'filter' => true,
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\Currency\\CurrencyFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\Currency\\CurrencyAttributeExtractor'
    ],
    'currencyConverted' => [
      'params' => [],
      'filter' => true,
      'notCreatable' => true,
      'skipOrmDefs' => true
    ],
    'date' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'enum',
          'view' => 'views/admin/field-manager/fields/date/default',
          'options' => [
            0 => '',
            1 => 'javascript: return this.dateTime.getToday();',
            2 => 'javascript: return this.dateTime.getDateShiftedFromToday(1, \'days\');',
            3 => 'javascript: return this.dateTime.getDateShiftedFromToday(2, \'days\');',
            4 => 'javascript: return this.dateTime.getDateShiftedFromToday(3, \'days\');',
            5 => 'javascript: return this.dateTime.getDateShiftedFromToday(4, \'days\');',
            6 => 'javascript: return this.dateTime.getDateShiftedFromToday(5, \'days\');',
            7 => 'javascript: return this.dateTime.getDateShiftedFromToday(6, \'days\');',
            8 => 'javascript: return this.dateTime.getDateShiftedFromToday(7, \'days\');',
            9 => 'javascript: return this.dateTime.getDateShiftedFromToday(8, \'days\');',
            10 => 'javascript: return this.dateTime.getDateShiftedFromToday(9, \'days\');',
            11 => 'javascript: return this.dateTime.getDateShiftedFromToday(10, \'days\');',
            12 => 'javascript: return this.dateTime.getDateShiftedFromToday(30, \'days\');',
            13 => 'javascript: return this.dateTime.getDateShiftedFromToday(1, \'weeks\');',
            14 => 'javascript: return this.dateTime.getDateShiftedFromToday(2, \'weeks\');',
            15 => 'javascript: return this.dateTime.getDateShiftedFromToday(3, \'weeks\');',
            16 => 'javascript: return this.dateTime.getDateShiftedFromToday(1, \'months\');',
            17 => 'javascript: return this.dateTime.getDateShiftedFromToday(2, \'months\');',
            18 => 'javascript: return this.dateTime.getDateShiftedFromToday(3, \'months\');',
            19 => 'javascript: return this.dateTime.getDateShiftedFromToday(4, \'months\');',
            20 => 'javascript: return this.dateTime.getDateShiftedFromToday(5, \'months\');',
            21 => 'javascript: return this.dateTime.getDateShiftedFromToday(6, \'months\');',
            22 => 'javascript: return this.dateTime.getDateShiftedFromToday(7, \'months\');',
            23 => 'javascript: return this.dateTime.getDateShiftedFromToday(8, \'months\');',
            24 => 'javascript: return this.dateTime.getDateShiftedFromToday(9, \'months\');',
            25 => 'javascript: return this.dateTime.getDateShiftedFromToday(10, \'months\');',
            26 => 'javascript: return this.dateTime.getDateShiftedFromToday(11, \'months\');',
            27 => 'javascript: return this.dateTime.getDateShiftedFromToday(1, \'year\');'
          ],
          'translation' => 'FieldManager.options.dateDefault'
        ],
        2 => [
          'name' => 'after',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        3 => [
          'name' => 'before',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        4 => [
          'type' => 'bool',
          'name' => 'afterOrEqual',
          'hidden' => true
        ],
        5 => [
          'type' => 'bool',
          'name' => 'useNumericFormat'
        ],
        6 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required'
      ],
      'mandatoryValidationList' => [
        0 => 'valid'
      ],
      'filter' => true,
      'fieldDefs' => [
        'notNull' => false
      ],
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\Date\\DateFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\Date\\DateAttributeExtractor',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\Date'
      ],
      'default' => NULL
    ],
    'datetime' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'enum',
          'view' => 'views/admin/field-manager/fields/date/default',
          'options' => [
            0 => '',
            1 => 'javascript: return this.dateTime.getNow(1);',
            2 => 'javascript: return this.dateTime.getNow(5);',
            3 => 'javascript: return this.dateTime.getNow(15);',
            4 => 'javascript: return this.dateTime.getNow(30);',
            5 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'hours\', 15);',
            6 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'hours\', 15);',
            7 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'hours\', 15);',
            8 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'hours\', 15);',
            9 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'hours\', 15);',
            10 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'hours\', 15);',
            11 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(7, \'hours\', 15);',
            12 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(8, \'hours\', 15);',
            13 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(9, \'hours\', 15);',
            14 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(10, \'hours\', 15);',
            15 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(11, \'hours\', 15);',
            16 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(12, \'hours\', 15);',
            17 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'days\', 15);',
            18 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'days\', 15);',
            19 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'days\', 15);',
            20 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'days\', 15);',
            21 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'days\', 15);',
            22 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'days\', 15);',
            23 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'week\', 15);'
          ],
          'translation' => 'FieldManager.options.dateTimeDefault'
        ],
        2 => [
          'name' => 'after',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        3 => [
          'name' => 'before',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        4 => [
          'type' => 'bool',
          'name' => 'afterOrEqual',
          'hidden' => true
        ],
        5 => [
          'type' => 'bool',
          'name' => 'useNumericFormat'
        ],
        6 => [
          'type' => 'bool',
          'name' => 'hasSeconds',
          'hidden' => true
        ],
        7 => [
          'type' => 'enumInt',
          'name' => 'minuteStep',
          'options' => [
            0 => 30,
            1 => 15,
            2 => 60,
            3 => 10,
            4 => 5,
            5 => 1
          ]
        ],
        8 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        9 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        10 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required'
      ],
      'mandatoryValidationList' => [
        0 => 'valid'
      ],
      'filter' => true,
      'fieldDefs' => [
        'notNull' => false
      ],
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\DateTime\\DateTimeFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\DateTime\\DateTimeAttributeExtractor',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\Datetime'
      ],
      'default' => NULL
    ],
    'datetimeOptional' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'enum',
          'view' => 'views/admin/field-manager/fields/date/default',
          'options' => [
            0 => '',
            1 => 'javascript: return this.dateTime.getNow(1);',
            2 => 'javascript: return this.dateTime.getNow(5);',
            3 => 'javascript: return this.dateTime.getNow(15);',
            4 => 'javascript: return this.dateTime.getNow(30);',
            5 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'hours\', 15);',
            6 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'hours\', 15);',
            7 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'hours\', 15);',
            8 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'hours\', 15);',
            9 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'hours\', 15);',
            10 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'hours\', 15);',
            11 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(7, \'hours\', 15);',
            12 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(8, \'hours\', 15);',
            13 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(9, \'hours\', 15);',
            14 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(10, \'hours\', 15);',
            15 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(11, \'hours\', 15);',
            16 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(12, \'hours\', 15);',
            17 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'days\', 15);',
            18 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'days\', 15);',
            19 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'days\', 15);',
            20 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'days\', 15);',
            21 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'days\', 15);',
            22 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'days\', 15);',
            23 => 'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'week\', 15);'
          ],
          'translation' => 'FieldManager.options.dateTimeDefault'
        ],
        2 => [
          'name' => 'after',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        3 => [
          'name' => 'before',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/after-before'
        ],
        4 => [
          'type' => 'bool',
          'name' => 'useNumericFormat'
        ],
        5 => [
          'type' => 'enumInt',
          'name' => 'minuteStep',
          'options' => [
            0 => 30,
            1 => 15,
            2 => 60,
            3 => 10,
            4 => 5,
            5 => 1
          ]
        ],
        6 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => '',
        1 => 'date'
      ],
      'fields' => [
        'date' => [
          'type' => 'date',
          'utility' => true
        ]
      ],
      'validationList' => [
        0 => 'required'
      ],
      'mandatoryValidationList' => [
        0 => 'valid'
      ],
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => [
        'type' => 'datetime',
        'notNull' => false
      ],
      'view' => 'views/fields/datetime-optional',
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\DateTimeOptional\\DateTimeOptionalFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\DateTimeOptional\\DateTimeOptionalAttributeExtractor',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\Datetime',
        1 => 'Espo\\Classes\\FieldSanitizers\\DatetimeOptionalDate'
      ],
      'default' => NULL
    ],
    'decimal' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'decimal'
        ],
        2 => [
          'name' => 'min',
          'type' => 'decimal'
        ],
        3 => [
          'name' => 'max',
          'type' => 'decimal'
        ],
        4 => [
          'name' => 'decimalPlaces',
          'type' => 'int'
        ],
        5 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'precision',
          'type' => 'int',
          'hidden' => true
        ],
        9 => [
          'name' => 'scale',
          'type' => 'int',
          'hidden' => true
        ]
      ],
      'filter' => true,
      'validationList' => [
        0 => 'required',
        1 => 'min',
        2 => 'max'
      ],
      'mandatoryValidationList' => [
        0 => 'valid'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\Decimal'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\Decimal'
    ],
    'duration' => [
      'params' => [
        0 => [
          'name' => 'default',
          'type' => 'int'
        ],
        1 => [
          'name' => 'options',
          'type' => 'arrayInt'
        ]
      ],
      'notCreatable' => true,
      'notMergeable' => true,
      'fieldDefs' => [
        'type' => 'int'
      ]
    ],
    'email' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        2 => [
          'name' => 'onlyPrimary',
          'type' => 'bool',
          'hidden' => true
        ]
      ],
      'actualFields' => [
        0 => 'isOptedOut',
        1 => 'isInvalid',
        2 => '',
        3 => 'data'
      ],
      'notActualFields' => [],
      'fields' => [
        'isOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true
        ],
        'isInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true
        ]
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\Email',
      'validationList' => [
        0 => 'required',
        1 => 'emailAddress',
        2 => 'maxLength'
      ],
      'mandatoryValidationList' => [
        0 => 'emailAddress',
        1 => 'maxLength',
        2 => 'maxCount'
      ],
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => [
        'notStorable' => true
      ],
      'textFilter' => true,
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\EmailAddress\\EmailAddressGroupFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\EmailAddress\\EmailAddressGroupAttributeExtractor',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'default' => NULL
    ],
    'enum' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options-with-style',
          'tooltip' => true
        ],
        2 => [
          'name' => 'default',
          'type' => 'enum',
          'view' => 'views/admin/field-manager/fields/options/default'
        ],
        3 => [
          'name' => 'optionsReference',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/options-reference',
          'tooltip' => true
        ],
        4 => [
          'name' => 'isSorted',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ],
        6 => [
          'name' => 'optionsPath',
          'type' => 'varchar',
          'hidden' => true
        ],
        7 => [
          'name' => 'style',
          'type' => 'jsonObject',
          'hidden' => true
        ],
        8 => [
          'name' => 'displayAsLabel',
          'type' => 'bool'
        ],
        9 => [
          'name' => 'labelType',
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'state'
          ]
        ],
        10 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        11 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        12 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'valid',
        2 => 'maxLength'
      ],
      'mandatoryValidationList' => [
        0 => 'valid',
        1 => 'maxLength'
      ],
      'filter' => true,
      'fieldDefs' => [
        'type' => 'varchar'
      ],
      'translatedOptions' => true,
      'dynamicLogicOptions' => true,
      'personalData' => true
    ],
    'enumFloat' => [
      'params' => [
        0 => [
          'name' => 'options',
          'type' => 'array'
        ],
        1 => [
          'name' => 'default',
          'type' => 'float'
        ],
        2 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => [
        'type' => 'float'
      ]
    ],
    'enumInt' => [
      'params' => [
        0 => [
          'name' => 'options',
          'type' => 'array'
        ],
        1 => [
          'name' => 'default',
          'type' => 'int'
        ],
        2 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ]
      ],
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => [
        'type' => 'int'
      ]
    ],
    'file' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'sourceList',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/source-list'
        ],
        2 => [
          'name' => 'maxFileSize',
          'type' => 'float',
          'tooltip' => true,
          'min' => 0
        ],
        3 => [
          'name' => 'accept',
          'type' => 'multiEnum',
          'noEmptyString' => true,
          'allowCustomOptions' => true,
          'options' => [
            0 => 'image/*',
            1 => 'audio/*',
            2 => 'video/*',
            3 => '.zip',
            4 => '.pdf',
            5 => '.odt',
            6 => '.ods',
            7 => '.odp',
            8 => '.docx',
            9 => '.xlsx',
            10 => '.pptx',
            11 => '.doc',
            12 => '.xls',
            13 => '.ppt',
            14 => '.rtf',
            15 => '.csv',
            16 => '.md',
            17 => '.txt'
          ],
          'tooltip' => 'fileAccept'
        ],
        4 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'audited',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => 'id'
      ],
      'notActualFields' => [
        0 => 'name'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\File',
      'validationList' => [
        0 => 'required',
        1 => 'pattern'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern'
      ],
      'filter' => true,
      'linkDefs' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'skipOrmDefs' => true,
        'utility' => true
      ],
      'personalData' => true,
      'duplicatorClassName' => 'Espo\\Classes\\FieldDuplicators\\File'
    ],
    'float' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'float'
        ],
        2 => [
          'name' => 'min',
          'type' => 'float'
        ],
        3 => [
          'name' => 'max',
          'type' => 'float'
        ],
        4 => [
          'name' => 'decimalPlaces',
          'type' => 'int'
        ],
        5 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'min',
        2 => 'max'
      ],
      'mandatoryValidationList' => [
        0 => 'valid'
      ],
      'filter' => true,
      'fieldDefs' => [
        'notNull' => false
      ]
    ],
    'foreign' => [
      'params' => [
        0 => [
          'name' => 'link',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/foreign/link',
          'required' => true
        ],
        1 => [
          'name' => 'field',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/foreign/field',
          'required' => true
        ],
        2 => [
          'name' => 'relateOnImport',
          'type' => 'bool',
          'tooltip' => true
        ],
        3 => [
          'name' => 'view',
          'type' => 'varchar',
          'hidden' => true
        ]
      ],
      'fieldTypeList' => [
        0 => 'varchar',
        1 => 'enum',
        2 => 'enumInt',
        3 => 'enumFloat',
        4 => 'int',
        5 => 'float',
        6 => 'url',
        7 => 'date',
        8 => 'datetime',
        9 => 'text',
        10 => 'number',
        11 => 'bool',
        12 => 'email',
        13 => 'phone',
        14 => 'array',
        15 => 'multiEnum',
        16 => 'checklist',
        17 => 'urlMultiple',
        18 => 'currencyConverted'
      ],
      'fieldTypeViewMap' => [
        'varchar' => 'views/fields/foreign-varchar',
        'enum' => 'views/fields/foreign-enum',
        'enumInt' => 'views/fields/foreign-int',
        'enumFloat' => 'views/fields/foreign-float',
        'int' => 'views/fields/foreign-int',
        'float' => 'views/fields/foreign-float',
        'url' => 'views/fields/foreign-url',
        'date' => 'views/fields/foreign-date',
        'datetime' => 'views/fields/foreign-datetime',
        'text' => 'views/fields/foreign-text',
        'number' => 'views/fields/foreign-varchar',
        'bool' => 'views/fields/foreign-bool',
        'email' => 'views/fields/foreign-email',
        'phone' => 'views/fields/foreign-phone',
        'array' => 'views/fields/foreign-array',
        'checklist' => 'views/fields/foreign-checklist',
        'multiEnum' => 'views/fields/foreign-multi-enum',
        'urlMultiple' => 'views/fields/foreign-url-multiple',
        'currencyConverted' => 'views/fields/foreign-currency-converted'
      ],
      'filter' => true,
      'notCreatable' => false,
      'fieldDefs' => [
        'readOnly' => true
      ]
    ],
    'image' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'previewSize',
          'type' => 'enum',
          'default' => 'small',
          'options' => [
            0 => 'x-small',
            1 => 'small',
            2 => 'medium',
            3 => 'large'
          ]
        ],
        2 => [
          'name' => 'listPreviewSize',
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'small',
            2 => 'medium'
          ],
          'translation' => 'Admin.options.previewSize'
        ],
        3 => [
          'name' => 'maxFileSize',
          'type' => 'float',
          'tooltip' => true,
          'min' => 0
        ],
        4 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'audited',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => 'id'
      ],
      'notActualFields' => [
        0 => 'name'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\File',
      'validationList' => [
        0 => 'required',
        1 => 'pattern'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern'
      ],
      'filter' => true,
      'linkDefs' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'skipOrmDefs' => true,
        'utility' => true
      ],
      'personalData' => true,
      'duplicatorClassName' => 'Espo\\Classes\\FieldDuplicators\\File'
    ],
    'int' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'int'
        ],
        2 => [
          'name' => 'min',
          'type' => 'int',
          'view' => 'views/admin/field-manager/fields/int/max'
        ],
        3 => [
          'name' => 'max',
          'type' => 'int',
          'view' => 'views/admin/field-manager/fields/int/max'
        ],
        4 => [
          'name' => 'disableFormatting',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'min',
        2 => 'max'
      ],
      'mandatoryValidationList' => [
        0 => 'valid',
        1 => 'rangeInternal'
      ],
      'filter' => true,
      'textFilter' => true,
      'textFilterForeign' => true,
      'personalData' => true
    ],
    'jsonArray' => [
      'notCreatable' => true,
      'notMergeable' => true,
      'notSortable' => true,
      'filter' => false,
      'validationList' => [
        0 => 'array'
      ],
      'mandatoryValidationList' => [
        0 => 'array'
      ]
    ],
    'jsonObject' => [
      'notCreatable' => true,
      'notMergeable' => true,
      'filter' => false
    ],
    'link' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        2 => [
          'name' => 'readOnly',
          'type' => 'bool',
          'tooltip' => 'linkReadOnly'
        ],
        3 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'default',
          'type' => 'link',
          'view' => 'views/admin/field-manager/fields/link/default'
        ],
        5 => [
          'name' => 'createButton',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'autocompleteOnEmpty',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => 'id'
      ],
      'notActualFields' => [
        0 => 'name'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\Link',
      'validationList' => [
        0 => 'required',
        1 => 'pattern'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern'
      ],
      'filter' => true,
      'notCreatable' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\Link\\LinkFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\Link\\LinkAttributeExtractor'
    ],
    'linkMultiple' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'readOnly',
          'type' => 'bool',
          'tooltip' => 'linkReadOnly'
        ],
        2 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'audited',
          'type' => 'bool',
          'tooltip' => true
        ],
        4 => [
          'name' => 'default',
          'type' => 'linkMultiple',
          'view' => 'views/admin/field-manager/fields/link-multiple/default'
        ],
        5 => [
          'name' => 'createButton',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'autocompleteOnEmpty',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ],
        8 => [
          'name' => 'sortable',
          'type' => 'bool',
          'default' => false,
          'hidden' => true
        ]
      ],
      'actualFields' => [
        0 => 'ids'
      ],
      'notActualFields' => [
        0 => 'names'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\LinkMultiple',
      'validationList' => [
        0 => 'required',
        1 => 'pattern',
        2 => 'columnsValid',
        3 => 'maxCount'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern',
        1 => 'columnsValid'
      ],
      'notCreatable' => true,
      'notSortable' => true,
      'filter' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\LinkMultiple\\LinkMultipleFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\LinkMultiple\\LinkMultipleAttributeExtractor',
      'duplicatorClassName' => 'Espo\\Classes\\FieldDuplicators\\LinkMultiple',
      'massUpdateActionList' => [
        0 => 'update',
        1 => 'add',
        2 => 'remove'
      ]
    ],
    'linkOne' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        2 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'createButton',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'autocompleteOnEmpty',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => 'id'
      ],
      'notActualFields' => [
        0 => 'name'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\LinkOne',
      'validationList' => [
        0 => 'required',
        1 => 'pattern'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern'
      ],
      'validatorClassName' => 'Espo\\Classes\\FieldValidators\\LinkType',
      'filter' => true,
      'notCreatable' => true
    ],
    'linkParent' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'entityList',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/entity-list'
        ],
        2 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        3 => [
          'name' => 'readOnly',
          'type' => 'bool',
          'tooltip' => 'linkReadOnly'
        ],
        4 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'autocompleteOnEmpty',
          'type' => 'bool'
        ]
      ],
      'actualFields' => [
        0 => 'id',
        1 => 'type'
      ],
      'notActualFields' => [
        0 => 'name'
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\LinkParent',
      'validationList' => [
        0 => 'required',
        1 => 'pattern',
        2 => 'valid'
      ],
      'mandatoryValidationList' => [
        0 => 'pattern',
        1 => 'valid'
      ],
      'filter' => true,
      'notCreatable' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\LinkParent\\LinkParentFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\LinkParent\\LinkParentAttributeExtractor'
    ],
    'map' => [
      'params' => [
        0 => [
          'name' => 'height',
          'type' => 'int',
          'default' => 300
        ]
      ],
      'filter' => false,
      'notCreatable' => true,
      'notSortable' => true,
      'fieldDefs' => [
        'notStorable' => true,
        'orderDisabled' => true
      ]
    ],
    'multiEnum' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options-with-style',
          'tooltip' => true
        ],
        2 => [
          'name' => 'optionsReference',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/options-reference',
          'tooltip' => true
        ],
        3 => [
          'name' => 'default',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/options/default-multi'
        ],
        4 => [
          'name' => 'isSorted',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true
        ],
        6 => [
          'name' => 'allowCustomOptions',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ],
        8 => [
          'name' => 'style',
          'type' => 'jsonObject',
          'hidden' => true
        ],
        9 => [
          'name' => 'displayAsLabel',
          'type' => 'bool'
        ],
        10 => [
          'name' => 'labelType',
          'type' => 'enum',
          'options' => [
            0 => '',
            1 => 'state'
          ]
        ],
        11 => [
          'name' => 'displayAsList',
          'type' => 'bool',
          'tooltip' => true
        ],
        12 => [
          'name' => 'pattern',
          'type' => 'varchar',
          'tooltip' => true,
          'view' => 'views/admin/field-manager/fields/pattern'
        ],
        13 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        14 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        15 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        16 => [
          'name' => 'optionsPath',
          'type' => 'varchar',
          'hidden' => true
        ]
      ],
      'validationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'required',
        4 => 'maxCount',
        5 => 'maxItemLength',
        6 => 'pattern',
        7 => 'noEmptyString'
      ],
      'mandatoryValidationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'maxItemLength',
        4 => 'noEmptyString'
      ],
      'filter' => true,
      'notCreatable' => false,
      'notSortable' => true,
      'fieldDefs' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true
      ],
      'translatedOptions' => true,
      'dynamicLogicOptions' => true,
      'personalData' => true,
      'massUpdateActionList' => [
        0 => 'update',
        1 => 'add',
        2 => 'remove'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\ArrayFromNull',
        1 => 'Espo\\Classes\\FieldSanitizers\\ArrayStringTrim'
      ],
      'default' => []
    ],
    'number' => [
      'params' => [
        0 => [
          'name' => 'prefix',
          'type' => 'varchar',
          'maxLength' => 16
        ],
        1 => [
          'name' => 'nextNumber',
          'type' => 'int',
          'min' => 0,
          'max' => 2147483647,
          'required' => true,
          'default' => 1
        ],
        2 => [
          'name' => 'padLength',
          'type' => 'int',
          'default' => 5,
          'required' => true,
          'min' => 1,
          'max' => 20
        ],
        3 => [
          'name' => 'copyToClipboard',
          'type' => 'bool',
          'default' => false
        ],
        4 => [
          'name' => 'suppressHook',
          'type' => 'bool',
          'default' => false
        ]
      ],
      'filter' => true,
      'fieldDefs' => [
        'type' => 'varchar',
        'len' => 36,
        'notNull' => false,
        'unique' => false
      ],
      'hookClassName' => 'Espo\\Tools\\FieldManager\\Hooks\\NumberType',
      'textFilter' => true,
      'readOnly' => true,
      'default' => NULL
    ],
    'password' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'maxLength',
          'type' => 'int',
          'max' => 255
        ]
      ],
      'validationList' => [
        0 => 'valid',
        1 => 'maxLength'
      ],
      'mandatoryValidationList' => [
        0 => 'valid',
        1 => 'maxLength'
      ],
      'notSortable' => true,
      'notCreatable' => true,
      'filter' => false,
      'validatorClassName' => 'Espo\\Classes\\FieldValidators\\PasswordType'
    ],
    'personName' => [
      'actualFields' => [
        0 => 'salutation',
        1 => 'first',
        2 => 'last',
        3 => 'middle'
      ],
      'notActualFields' => [
        0 => ''
      ],
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ]
      ],
      'fields' => [
        'salutation' => [
          'type' => 'enum',
          'customizationOptionsReferenceDisabled' => true
        ],
        'first' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters'
        ],
        'last' => [
          'type' => 'varchar',
          'pattern' => '$noBadCharacters'
        ],
        'middle' => [
          'type' => 'varchar',
          'maxLength' => 100,
          'pattern' => '$noBadCharacters'
        ]
      ],
      'naming' => 'prefix',
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\PersonName',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => true,
      'skipOrmDefs' => false,
      'personalData' => true,
      'textFilter' => true,
      'fullTextSearch' => true,
      'validationList' => [
        0 => 'required'
      ],
      'fullTextSearchColumnList' => [
        0 => 'first',
        1 => 'last'
      ]
    ],
    'phone' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'typeList',
          'type' => 'array',
          'default' => [
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other'
          ],
          'view' => 'views/admin/field-manager/fields/options'
        ],
        2 => [
          'name' => 'defaultType',
          'type' => 'enum',
          'default' => 'Mobile',
          'view' => 'views/admin/field-manager/fields/phone/default'
        ],
        3 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'onlyPrimary',
          'type' => 'bool',
          'hidden' => true
        ]
      ],
      'actualFields' => [
        0 => 'isOptedOut',
        1 => 'isInvalid',
        2 => '',
        3 => 'data'
      ],
      'notActualFields' => [],
      'fields' => [
        'isOptedOut' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true
        ],
        'isInvalid' => [
          'type' => 'bool',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutDefaultSidePanelDisabled' => true,
          'mergeDisabled' => true,
          'customizationDefaultDisabled' => true,
          'customizationReadOnlyDisabled' => true,
          'customizationInlineEditDisabledDisabled' => true
        ]
      ],
      'converterClassName' => 'Espo\\Core\\Utils\\Database\\Orm\\FieldConverters\\Phone',
      'validationList' => [
        0 => 'required',
        1 => 'valid',
        2 => 'maxLength'
      ],
      'mandatoryValidationList' => [
        0 => 'valid',
        1 => 'maxLength',
        2 => 'maxCount'
      ],
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => [
        'notStorable' => true
      ],
      'translatedOptions' => true,
      'textFilter' => true,
      'personalData' => true,
      'valueFactoryClassName' => 'Espo\\Core\\Field\\PhoneNumber\\PhoneNumberGroupFactory',
      'attributeExtractorClassName' => 'Espo\\Core\\Field\\PhoneNumber\\PhoneNumberGroupAttributeExtractor',
      'sanitizerClassName' => 'Espo\\Classes\\FieldSanitizers\\Phone',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'default' => NULL
    ],
    'rangeCurrency' => [
      'actualFields' => [
        0 => 'from',
        1 => 'to'
      ],
      'fields' => [
        'from' => [
          'type' => 'currency',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ],
        'to' => [
          'type' => 'currency',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ]
      ],
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'skipOrmDefs' => true
    ],
    'rangeFloat' => [
      'actualFields' => [
        0 => 'from',
        1 => 'to'
      ],
      'fields' => [
        'from' => [
          'type' => 'float',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ],
        'to' => [
          'type' => 'float',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ]
      ],
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'skipOrmDefs' => true
    ],
    'rangeInt' => [
      'actualFields' => [
        0 => 'from',
        1 => 'to'
      ],
      'fields' => [
        'from' => [
          'type' => 'int',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ],
        'to' => [
          'type' => 'int',
          'layoutAvailabilityList' => [
            0 => 'filters',
            1 => 'massUpdate'
          ]
        ]
      ],
      'params' => [
        0 => [
          'name' => 'disableFormatting',
          'type' => 'bool'
        ]
      ],
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'skipOrmDefs' => true
    ],
    'text' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'text'
        ],
        2 => [
          'name' => 'maxLength',
          'type' => 'int'
        ],
        3 => [
          'name' => 'seeMoreDisabled',
          'type' => 'bool',
          'tooltip' => true
        ],
        4 => [
          'name' => 'rows',
          'type' => 'int',
          'min' => 1
        ],
        5 => [
          'name' => 'rowsMin',
          'type' => 'int',
          'default' => 2,
          'min' => 1,
          'hidden' => true
        ],
        6 => [
          'name' => 'cutHeight',
          'type' => 'int',
          'default' => 200,
          'min' => 1,
          'tooltip' => true
        ],
        7 => [
          'name' => 'displayRawText',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'preview',
          'type' => 'bool',
          'tooltip' => true
        ],
        9 => [
          'name' => 'attachmentField',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/text/attachment-field'
        ],
        10 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        11 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        12 => [
          'name' => 'audited',
          'type' => 'bool',
          'tooltip' => true
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxLength'
      ],
      'filter' => true,
      'personalData' => true,
      'textFilter' => true,
      'textFilterForeign' => true,
      'fullTextSearch' => true,
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\EmptyStringToNull'
      ],
      'default' => NULL
    ],
    'url' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'varchar'
        ],
        2 => [
          'name' => 'maxLength',
          'type' => 'int'
        ],
        3 => [
          'name' => 'strip',
          'type' => 'bool',
          'tooltip' => 'urlStrip'
        ],
        4 => [
          'name' => 'copyToClipboard',
          'type' => 'bool',
          'default' => false
        ],
        5 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        7 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxLength',
        2 => 'valid'
      ],
      'mandatoryValidationList' => [
        0 => 'maxLength',
        1 => 'valid'
      ],
      'filter' => true,
      'fieldDefs' => [
        'type' => 'varchar'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'personalData' => true,
      'default' => NULL
    ],
    'urlMultiple' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'maxCount',
          'type' => 'int',
          'min' => 1,
          'tooltip' => true
        ],
        2 => [
          'name' => 'strip',
          'type' => 'bool',
          'default' => false,
          'tooltip' => 'urlStrip'
        ],
        3 => [
          'name' => 'audited',
          'type' => 'bool'
        ],
        4 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxCount'
      ],
      'mandatoryValidationList' => [
        0 => 'array',
        1 => 'arrayOfString',
        2 => 'valid',
        3 => 'maxItemLength',
        4 => 'pattern',
        5 => 'noEmptyString'
      ],
      'filter' => true,
      'notCreatable' => false,
      'notSortable' => true,
      'fieldDefs' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true
      ],
      'personalData' => true,
      'massUpdateActionList' => [
        0 => 'update',
        1 => 'add',
        2 => 'remove'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\ArrayFromNull',
        1 => 'Espo\\Classes\\FieldSanitizers\\ArrayStringTrim'
      ],
      'default' => []
    ],
    'varchar' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'varchar'
        ],
        2 => [
          'name' => 'maxLength',
          'type' => 'int',
          'default' => 100,
          'min' => 1,
          'max' => 65535
        ],
        3 => [
          'name' => 'options',
          'type' => 'multiEnum',
          'tooltip' => 'optionsVarchar'
        ],
        4 => [
          'name' => 'pattern',
          'type' => 'varchar',
          'default' => NULL,
          'tooltip' => true,
          'view' => 'views/admin/field-manager/fields/pattern'
        ],
        5 => [
          'name' => 'copyToClipboard',
          'type' => 'bool',
          'default' => false
        ],
        6 => [
          'name' => 'audited',
          'type' => 'bool',
          'tooltip' => true
        ],
        7 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        9 => [
          'name' => 'noSpellCheck',
          'type' => 'bool',
          'default' => false,
          'hidden' => true
        ],
        10 => [
          'name' => 'optionsPath',
          'type' => 'varchar',
          'hidden' => true
        ]
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxLength',
        2 => 'pattern'
      ],
      'mandatoryValidationList' => [
        0 => 'maxLength'
      ],
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\StringTrim'
      ],
      'filter' => true,
      'personalData' => true,
      'textFilter' => true,
      'textFilterForeign' => true,
      'dynamicLogicOptions' => true,
      'fullTextSearch' => true,
      'default' => NULL
    ],
    'wysiwyg' => [
      'params' => [
        0 => [
          'name' => 'required',
          'type' => 'bool',
          'default' => false
        ],
        1 => [
          'name' => 'default',
          'type' => 'text'
        ],
        2 => [
          'name' => 'height',
          'type' => 'int'
        ],
        3 => [
          'name' => 'minHeight',
          'type' => 'int'
        ],
        4 => [
          'name' => 'readOnly',
          'type' => 'bool'
        ],
        5 => [
          'name' => 'readOnlyAfterCreate',
          'type' => 'bool'
        ],
        6 => [
          'name' => 'attachmentField',
          'type' => 'varchar',
          'hidden' => true
        ],
        7 => [
          'name' => 'useIframe',
          'type' => 'bool'
        ],
        8 => [
          'name' => 'maxLength',
          'type' => 'int'
        ],
        9 => [
          'name' => 'audited',
          'type' => 'bool',
          'tooltip' => true
        ]
      ],
      'filter' => true,
      'fieldDefs' => [
        'type' => 'text'
      ],
      'validationList' => [
        0 => 'required',
        1 => 'maxLength'
      ],
      'personalData' => true,
      'textFilter' => true,
      'fullTextSearch' => true,
      'duplicatorClassName' => 'Espo\\Classes\\FieldDuplicators\\Wysiwyg',
      'validatorClassName' => 'Espo\\Classes\\FieldValidators\\TextType',
      'sanitizerClassNameList' => [
        0 => 'Espo\\Classes\\FieldSanitizers\\EmptyStringToNull'
      ],
      'default' => NULL
    ]
  ],
  'integrations' => [
    'GoogleMaps' => [
      'fields' => [
        'apiKey' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'required' => true
        ],
        'mapId' => [
          'type' => 'varchar',
          'maxLength' => 64,
          'required' => false
        ]
      ],
      'allowUserAccounts' => false,
      'view' => 'views/admin/integrations/google-maps',
      'authMethod' => 'GoogleMaps'
    ],
    'GoogleReCaptcha' => [
      'fields' => [
        'siteKey' => [
          'type' => 'varchar',
          'maxLength' => 255,
          'required' => true
        ],
        'secretKey' => [
          'type' => 'password',
          'maxLength' => 255,
          'required' => true
        ],
        'scoreThreshold' => [
          'type' => 'float',
          'min' => 0.0,
          'max' => 1.0,
          'default' => 0.3,
          'required' => true
        ]
      ],
      'allowUserAccounts' => false,
      'view' => 'views/admin/integrations/edit'
    ]
  ],
  'logicDefs' => [
    'CurrencyRecordRate' => [
      'fields' => [
        'baseCode' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ]
      ]
    ],
    'Email' => [
      'fields' => [
        'replied' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'repliedId',
                'data' => [
                  'field' => 'replied'
                ]
              ]
            ]
          ]
        ],
        'replies' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'repliesIds',
                'data' => [
                  'field' => 'replies'
                ]
              ]
            ]
          ]
        ],
        'folderString' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'or',
                'value' => [
                  0 => [
                    'type' => 'and',
                    'value' => [
                      0 => [
                        'type' => 'isTrue',
                        'attribute' => 'isUsers'
                      ]
                    ]
                  ],
                  1 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'groupFolderId'
                  ]
                ]
              ]
            ]
          ]
        ],
        'sendAt' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'in',
                'attribute' => 'status',
                'value' => [
                  0 => 'Draft'
                ]
              ]
            ]
          ]
        ]
      ],
      'panels' => [
        'event' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'icsEventDateStart'
              ]
            ]
          ]
        ]
      ]
    ],
    'EmailAccount' => [
      'fields' => [
        'smtpUsername' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'and',
                'value' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'useSmtp'
                  ],
                  1 => [
                    'type' => 'isTrue',
                    'attribute' => 'smtpAuth'
                  ]
                ]
              ]
            ]
          ]
        ],
        'fetchSince' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useImap'
              ]
            ]
          ],
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'fetchData'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useImap'
              ]
            ]
          ]
        ],
        'sentFolder' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'storeSentEmails'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'storeSentEmails'
              ]
            ]
          ]
        ]
      ]
    ],
    'EmailFilter' => [
      'fields' => [
        'parent' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'isGlobal',
                'type' => 'isFalse'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'isGlobal',
                'type' => 'isFalse'
              ]
            ]
          ]
        ],
        'emailFolder' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'action',
                'type' => 'equals',
                'value' => 'Move to Folder'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'action',
                'type' => 'equals',
                'value' => 'Move to Folder'
              ]
            ]
          ]
        ],
        'groupEmailFolder' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'action',
                'type' => 'equals',
                'value' => 'Move to Group Folder'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'action',
                'type' => 'equals',
                'value' => 'Move to Group Folder'
              ]
            ]
          ]
        ],
        'markAsRead' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'parentType',
                'type' => 'equals',
                'value' => 'User'
              ]
            ]
          ]
        ],
        'skipNotification' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'parentType',
                'type' => 'equals',
                'value' => 'User'
              ]
            ]
          ]
        ]
      ],
      'options' => [
        'action' => [
          0 => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'isGlobal',
                'type' => 'isTrue'
              ]
            ],
            'optionList' => [
              0 => 'Skip'
            ]
          ],
          1 => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'parentType',
                'type' => 'equals',
                'value' => 'User'
              ]
            ],
            'optionList' => [
              0 => 'Skip',
              1 => 'Move to Folder',
              2 => 'None'
            ]
          ],
          2 => [
            'conditionGroup' => [
              0 => [
                'attribute' => 'parentType',
                'type' => 'equals',
                'value' => 'InboundEmail'
              ]
            ],
            'optionList' => [
              0 => 'Skip',
              1 => 'Move to Group Folder'
            ]
          ],
          3 => [
            'conditionGroup' => [],
            'optionList' => [
              0 => 'Skip'
            ]
          ]
        ]
      ]
    ],
    'InboundEmail' => [
      'fields' => [
        'smtpUsername' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'and',
                'value' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'useSmtp'
                  ],
                  1 => [
                    'type' => 'isTrue',
                    'attribute' => 'smtpAuth'
                  ]
                ]
              ]
            ]
          ]
        ],
        'fetchSince' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useImap'
              ]
            ]
          ],
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'fetchData'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useImap'
              ]
            ]
          ]
        ],
        'isSystem' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'replyEmailTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'reply'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'reply'
              ]
            ]
          ]
        ],
        'replyFromAddress' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'reply'
              ]
            ]
          ]
        ],
        'replyFromName' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'reply'
              ]
            ]
          ]
        ],
        'sentFolder' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'storeSentEmails'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'storeSentEmails'
              ]
            ]
          ]
        ]
      ]
    ],
    'LeadCapture' => [
      'fields' => [
        'targetList' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'subscribeToTargetList'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'subscribeToTargetList'
              ]
            ]
          ]
        ],
        'subscribeContactToTargetList' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'subscribeToTargetList'
              ]
            ]
          ]
        ],
        'optInConfirmationLifetime' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ]
        ],
        'optInConfirmationSuccessMessage' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ]
        ],
        'createLeadBeforeOptInConfirmation' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ]
        ],
        'smtpAccount' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ]
        ],
        'skipOptInConfirmationIfSubscribed' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'and',
                'value' => [
                  0 => [
                    'type' => 'isTrue',
                    'attribute' => 'optInConfirmation'
                  ],
                  1 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'targetListId',
                    'data' => [
                      'field' => 'targetList'
                    ]
                  ]
                ]
              ]
            ]
          ]
        ],
        'optInConfirmationEmailTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'optInConfirmation'
              ]
            ]
          ]
        ],
        'apiKey' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'phoneNumberCountry' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'contains',
                'attribute' => 'fieldList',
                'value' => 'phoneNumber'
              ]
            ]
          ]
        ],
        'formSuccessText' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formTitle' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formTheme' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formText' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formSuccessRedirectUrl' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formLanguage' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formFrameAncestors' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ],
        'formCaptcha' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ]
      ],
      'panels' => [
        'form' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ],
              1 => [
                'type' => 'isTrue',
                'attribute' => 'formEnabled'
              ]
            ]
          ]
        ]
      ]
    ],
    'OAuthProvider' => [
      'fields' => [
        'authorizationRedirectUri' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'clientId' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'isActive'
              ]
            ]
          ]
        ],
        'clientSecret' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'isActive'
              ]
            ]
          ]
        ],
        'authorizationEndpoint' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'isActive'
              ]
            ]
          ]
        ],
        'tokenEndpoint' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'isActive'
              ]
            ]
          ]
        ]
      ]
    ],
    'Preferences' => [
      'fields' => [
        'tabList' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useCustomTabList'
              ]
            ]
          ]
        ],
        'addCustomTabs' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'useCustomTabList'
              ]
            ]
          ]
        ],
        'assignmentEmailNotificationsIgnoreEntityTypeList' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'receiveAssignmentEmailNotifications'
              ]
            ]
          ]
        ],
        'reactionNotificationsNotFollowed' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'reactionNotifications'
              ]
            ]
          ]
        ]
      ]
    ],
    'Template' => [
      'fields' => [
        'entityType' => [
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'footer' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'printFooter'
              ]
            ]
          ]
        ],
        'footerPosition' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'printFooter'
              ]
            ]
          ]
        ],
        'header' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'printHeader'
              ]
            ]
          ]
        ],
        'headerPosition' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'printHeader'
              ]
            ]
          ]
        ],
        'body' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'entityType'
              ]
            ]
          ]
        ],
        'pageWidth' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'pageFormat',
                'value' => 'Custom'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'pageFormat',
                'value' => 'Custom'
              ]
            ]
          ]
        ],
        'pageHeight' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'pageFormat',
                'value' => 'Custom'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'pageFormat',
                'value' => 'Custom'
              ]
            ]
          ]
        ]
      ]
    ],
    'User' => [
      'fields' => [
        'avatarColor' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'avatarId',
                'value' => NULL,
                'data' => [
                  'field' => 'avatar'
                ]
              ],
              1 => [
                'type' => 'in',
                'attribute' => 'type',
                'value' => [
                  0 => 'regular',
                  1 => 'admin',
                  2 => 'api'
                ]
              ]
            ]
          ]
        ]
      ],
      'options' => [
        'authMethod' => [
          0 => [
            'optionList' => [
              0 => 'ApiKey',
              1 => 'Hmac'
            ],
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'api'
              ]
            ]
          ]
        ]
      ]
    ],
    'Webhook' => [
      'fields' => [
        'event' => [
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'secretKey' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'skipOwn' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'userId',
                'data' => [
                  'field' => 'user'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'WorkingTimeCalendar' => [
      'fields' => [
        'weekday0TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday0'
              ]
            ]
          ]
        ],
        'weekday1TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday1'
              ]
            ]
          ]
        ],
        'weekday2TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday2'
              ]
            ]
          ]
        ],
        'weekday3TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday3'
              ]
            ]
          ]
        ],
        'weekday4TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday4'
              ]
            ]
          ]
        ],
        'weekday5TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday5'
              ]
            ]
          ]
        ],
        'weekday6TimeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'weekday6'
              ]
            ]
          ]
        ],
        'teams' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'teamsIds'
              ]
            ]
          ]
        ]
      ]
    ],
    'WorkingTimeRange' => [
      'fields' => [
        'timeRanges' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Working'
              ]
            ]
          ]
        ],
        'users' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'or',
                'value' => [
                  0 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'id'
                  ],
                  1 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'usersIds'
                  ],
                  2 => [
                    'type' => 'isEmpty',
                    'attribute' => 'calendarsIds'
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'Campaign' => [
      'fields' => [
        'targetLists' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'or',
                'value' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Email'
                  ],
                  1 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Newsletter'
                  ],
                  2 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Informational Email'
                  ],
                  3 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Mail'
                  ]
                ]
              ]
            ]
          ]
        ],
        'excludingTargetLists' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'or',
                'value' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Email'
                  ],
                  1 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Newsletter'
                  ],
                  2 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Informational Email'
                  ],
                  3 => [
                    'type' => 'equals',
                    'attribute' => 'type',
                    'value' => 'Mail'
                  ]
                ]
              ]
            ]
          ]
        ],
        'contactsTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Mail'
              ]
            ]
          ]
        ],
        'leadsTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Mail'
              ]
            ]
          ]
        ],
        'accountsTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Mail'
              ]
            ]
          ]
        ],
        'usersTemplate' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Mail'
              ]
            ]
          ]
        ],
        'mailMergeOnlyWithAddress' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'type',
                'value' => 'Mail'
              ]
            ]
          ]
        ]
      ],
      'panels' => [
        'massEmails' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'in',
                'attribute' => 'type',
                'value' => [
                  0 => 'Email',
                  1 => 'Newsletter',
                  2 => 'Informational Email'
                ]
              ]
            ]
          ]
        ],
        'trackingUrls' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'in',
                'attribute' => 'type',
                'value' => [
                  0 => 'Email',
                  1 => 'Newsletter'
                ]
              ]
            ]
          ]
        ],
        'mailMerge' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'in',
                'attribute' => 'type',
                'value' => [
                  0 => 'Mail'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'CampaignTrackingUrl' => [
      'fields' => [
        'url' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'action',
                'value' => 'Redirect'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'action',
                'value' => 'Redirect'
              ]
            ]
          ]
        ],
        'message' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'action',
                'value' => 'Show Message'
              ]
            ]
          ],
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'action',
                'value' => 'Show Message'
              ]
            ]
          ]
        ]
      ]
    ],
    'Case' => [
      'fields' => [
        'number' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ]
      ]
    ],
    'Contact' => [
      'fields' => [
        'title' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'accountId'
              ]
            ]
          ]
        ],
        'portalUser' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'portalUserId',
                'data' => [
                  'field' => 'portalUser'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'MassEmail' => [
      'fields' => [
        'status' => [
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'and',
                'value' => [
                  0 => [
                    'type' => 'or',
                    'value' => [
                      0 => [
                        'type' => 'equals',
                        'attribute' => 'status',
                        'value' => 'Complete'
                      ],
                      1 => [
                        'type' => 'equals',
                        'attribute' => 'status',
                        'value' => 'In Process'
                      ],
                      2 => [
                        'type' => 'equals',
                        'attribute' => 'status',
                        'value' => 'Failed'
                      ]
                    ]
                  ],
                  1 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'id'
                  ]
                ]
              ]
            ]
          ]
        ]
      ],
      'options' => [
        'status' => [
          0 => [
            'optionList' => [
              0 => 'Draft',
              1 => 'Pending'
            ],
            'conditionGroup' => [
              0 => [
                'type' => 'in',
                'attribute' => 'status',
                'value' => [
                  0 => 'Draft',
                  1 => 'Pending'
                ]
              ]
            ]
          ]
        ]
      ]
    ],
    'Opportunity' => [
      'fields' => [
        'lastStage' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'stage',
                'value' => 'Closed Lost'
              ]
            ]
          ]
        ]
      ]
    ],
    'TargetList' => [
      'fields' => [
        'entryCount' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ],
        'optedOutCount' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ]
      ]
    ],
    'Task' => [
      'fields' => [
        'dateCompleted' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'status',
                'value' => 'Completed'
              ]
            ]
          ]
        ]
      ]
    ],
    'ScheduledJob' => [
      'fields' => [
        'job' => [
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isNotEmpty',
                'attribute' => 'id'
              ]
            ]
          ]
        ]
      ]
    ],
    'Lead' => [
      'fields' => [
        'name' => [
          'required' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isEmpty',
                'attribute' => 'accountName'
              ],
              1 => [
                'type' => 'isEmpty',
                'attribute' => 'emailAddress'
              ],
              2 => [
                'type' => 'isEmpty',
                'attribute' => 'phoneNumber'
              ]
            ]
          ]
        ],
        'convertedAt' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'and',
                'value' => [
                  0 => [
                    'type' => 'equals',
                    'attribute' => 'status',
                    'value' => 'Converted'
                  ],
                  1 => [
                    'type' => 'isNotEmpty',
                    'attribute' => 'convertedAt'
                  ]
                ]
              ]
            ]
          ]
        ]
      ],
      'panels' => [
        'convertedTo' => [
          'visible' => [
            'conditionGroup' => [
              0 => [
                'type' => 'equals',
                'attribute' => 'status',
                'value' => 'Converted'
              ]
            ]
          ]
        ]
      ]
    ],
    'Meeting' => [
      'fields' => [
        'duration' => [
          'readOnly' => [
            'conditionGroup' => [
              0 => [
                'type' => 'isTrue',
                'attribute' => 'isAllDay'
              ]
            ]
          ]
        ]
      ]
    ]
  ],
  'notificationDefs' => [
    'Email' => [
      'assignmentNotificatorClassName' => 'Espo\\Classes\\AssignmentNotificators\\Email',
      'forceAssignmentNotificator' => true
    ],
    'Call' => [
      'assignmentNotificatorClassName' => 'Espo\\Modules\\Crm\\Classes\\AssignmentNotificators\\Meeting',
      'forceAssignmentNotificator' => true
    ],
    'Case' => [
      'emailNotificationHandlerClassNameMap' => [
        'notePost' => 'Espo\\Modules\\Crm\\Classes\\EmailNotificationHandlers\\CaseObj'
      ]
    ],
    'Meeting' => [
      'assignmentNotificatorClassName' => 'Espo\\Modules\\Crm\\Classes\\AssignmentNotificators\\Meeting',
      'forceAssignmentNotificator' => true
    ]
  ],
  'recordDefs' => [
    'ActionHistoryRecord' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\LinkParent\\TargetLoader'
      ],
      'listLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\LinkParent\\TargetLoader'
      ],
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'forceSelectAllAttributes' => true,
      'actionHistoryDisabled' => true
    ],
    'AddressCountry' => [
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General',
      'updateDuplicateCheck' => true,
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\AddressCountry\\BeforeSave'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\AddressCountry\\BeforeSave'
      ]
    ],
    'AppLogRecord' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'actionHistoryDisabled' => true
    ],
    'AppSecret' => [
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\AppSecret\\ValueInputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\AppSecret\\ValueInputFilter'
      ],
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General'
    ],
    'Attachment' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Attachment\\CreateInputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Attachment\\UpdateInputFilter'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Attachment\\BeforeCreate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Attachment\\AfterCreate'
      ]
    ],
    'AuthLogRecord' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'actionHistoryDisabled' => true,
      'forceSelectAllAttributes' => true
    ],
    'AuthToken' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'actionHistoryDisabled' => true,
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\AuthToken\\UpdateInputFilter'
      ]
    ],
    'CurrencyRecordRate' => [
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\CurrencyRecordRate\\BeforeSaveValidation'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\CurrencyRecordRate\\BeforeSaveValidation'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\CurrencyRecordRate\\AfterSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\CurrencyRecordRate\\AfterSave'
      ],
      'afterDeleteHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\CurrencyRecordRate\\AfterDelete'
      ]
    ],
    'DashboardTemplate' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'Email' => [
      'loadAdditionalFieldsAfterUpdate' => true,
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\Email\\AddressLoader',
        1 => 'Espo\\Classes\\FieldProcessing\\Email\\AddressDataLoader',
        2 => 'Espo\\Classes\\FieldProcessing\\Email\\UserColumnsLoader',
        3 => 'Espo\\Classes\\FieldProcessing\\Email\\FolderDataLoader',
        4 => 'Espo\\Classes\\FieldProcessing\\Email\\IcsDataLoader'
      ],
      'listLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\Email\\StringDataLoader'
      ],
      'selectApplierClassNameList' => [
        0 => 'Espo\\Classes\\Select\\Email\\AdditionalAppliers\\Main'
      ],
      'massActions' => [
        'moveToFolder' => [
          'implementationClassName' => 'Espo\\Classes\\MassAction\\Email\\MoveToFolder'
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'name',
        1 => 'createdById',
        2 => 'dateSent',
        3 => 'fromString',
        4 => 'fromEmailAddressId',
        5 => 'fromEmailAddressName',
        6 => 'parentId',
        7 => 'parentType',
        8 => 'isHtml',
        9 => 'isReplied',
        10 => 'status',
        11 => 'accountId',
        12 => 'folderId',
        13 => 'messageId',
        14 => 'sentById',
        15 => 'replyToString',
        16 => 'hasAttachment',
        17 => 'groupFolderId',
        18 => 'groupStatusFolder'
      ],
      'beforeReadHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Email\\MarkAsRead'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Email\\CheckFromAddress',
        1 => 'Espo\\Classes\\RecordHooks\\Email\\BeforeCreate',
        2 => 'Espo\\Classes\\RecordHooks\\Email\\BeforeSave'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Email\\CheckFromAddress',
        1 => 'Espo\\Classes\\RecordHooks\\Email\\MarkAsReadBeforeUpdate',
        2 => 'Espo\\Classes\\RecordHooks\\Email\\BeforeUpdate',
        3 => 'Espo\\Classes\\RecordHooks\\Email\\BeforeSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Email\\AfterUpdate'
      ]
    ],
    'EmailAccount' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\InboundEmail\\PasswordsInputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\InboundEmail\\PasswordsInputFilter'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\EmailAccount\\BeforeCreate',
        1 => 'Espo\\Classes\\RecordHooks\\EmailAccount\\BeforeSave'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\EmailAccount\\BeforeSave'
      ]
    ],
    'EmailAddress' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'EmailFilter' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\EmailFilter\\BeforeSave'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\EmailFilter\\BeforeSave'
      ]
    ],
    'EmailFolder' => [
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\EmailFolder\\BeforeCreate'
      ]
    ],
    'EmailTemplate' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'actions' => [
        'merge' => [
          'allowed' => true
        ]
      ]
    ],
    'EmailTemplateCategory' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ]
    ],
    'Import' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\Import\\CountsLoader'
      ],
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'InboundEmail' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\InboundEmail\\PasswordsInputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\InboundEmail\\PasswordsInputFilter'
      ],
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\InboundEmail\\IsSystemLoader'
      ],
      'listLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\InboundEmail\\IsSystemLoader'
      ]
    ],
    'Job' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'forceSelectAllAttributes' => true
    ],
    'LayoutSet' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'LeadCapture' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\LeadCapture\\ExampleLoader'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\LeadCapture\\BeforeCreate'
      ],
      'loadAdditionalFieldsAfterUpdate' => true
    ],
    'LeadCaptureLogRecord' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'Note' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\Note\\AdditionalFieldsLoader'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Note\\UpdateInputFilter'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Note\\AssignmentCheck',
        1 => 'Espo\\Classes\\RecordHooks\\Note\\BeforeCreate'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Note\\BeforeUpdate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Note\\AfterCreate'
      ]
    ],
    'Notification' => [
      'exportDisabled' => true,
      'actionHistoryDisabled' => true
    ],
    'OAuthAccount' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\OAuthAccount\\DataLoader'
      ]
    ],
    'OAuthProvider' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\OAuthProvider\\AuthorizationRedirectUriLoader'
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\OAuthProvider\\GeneralFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\OAuthProvider\\GeneralFilter'
      ],
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General'
    ],
    'PhoneNumber' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'Portal' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'customUrl',
        1 => 'customId'
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Portal\\InputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Portal\\InputFilter'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Portal\\AfterUpdate'
      ]
    ],
    'PortalRole' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'forceSelectAllAttributes' => true,
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\BeforeSaveValidate'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\BeforeSaveValidate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\PortalRole\\AfterSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\PortalRole\\AfterSave'
      ]
    ],
    'Preferences' => [
      'actionsDisabled' => true
    ],
    'Role' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'forceSelectAllAttributes' => true,
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\BeforeSaveValidate'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\BeforeSaveValidate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\AfterSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Role\\AfterSave'
      ]
    ],
    'ScheduledJob' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'relationships' => [
        'log' => [
          'countDisabled' => true
        ]
      ]
    ],
    'Team' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Team\\AfterUpdate'
      ],
      'beforeLinkHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Team\\BeforeLinkUserCheck'
      ],
      'afterLinkHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Team\\ClearCacheAfterLink'
      ],
      'afterUnlinkHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Team\\ClearCacheAfterUnlink',
        1 => 'Espo\\Classes\\RecordHooks\\Team\\UnsetUserDefaultTeam'
      ]
    ],
    'Template' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ]
    ],
    'User' => [
      'massActions' => [
        'update' => [
          'implementationClassName' => 'Espo\\Classes\\MassAction\\User\\MassUpdate'
        ],
        'delete' => [
          'implementationClassName' => 'Espo\\Classes\\MassAction\\User\\MassDelete'
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'isActive',
        1 => 'userName',
        2 => 'type'
      ],
      'createInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\User\\InputFilter'
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\User\\InputFilter'
      ],
      'outputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\User\\OutputFilter'
      ],
      'readLoaderClassNameList' => [
        0 => 'Espo\\Classes\\FieldProcessing\\User\\LastAccessLoader'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\User\\BeforeCreate'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\User\\BeforeUpdate'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\User\\AfterUpdate'
      ],
      'beforeDeleteHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\User\\BeforeDelete'
      ],
      'deletedRestorerClassName' => 'Espo\\Classes\\Record\\User\\DeletedRestorer'
    ],
    'Webhook' => [
      'defaultsPopulatorClassName' => 'Espo\\Classes\\Record\\Webhook\\DefaultsPopulator',
      'massActions' => [
        'delete' => [
          'allowed' => true
        ],
        'update' => [
          'allowed' => true
        ]
      ],
      'updateInputFilterClassNameList' => [
        0 => 'Espo\\Classes\\Record\\Webhook\\UpdateInputFilter'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Webhook\\BeforeSave'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Webhook\\BeforeSave'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Webhook\\AfterSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Webhook\\AfterSave'
      ],
      'afterDeleteHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Webhook\\AfterDelete'
      ]
    ],
    'WebhookEventQueueItem' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'WebhookQueueItem' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'WorkingTimeRange' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ]
    ],
    'Account' => [
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General',
      'relationships' => [
        'contacts' => [
          'mandatoryAttributeList' => [
            0 => 'accountIsInactive'
          ]
        ],
        'targetLists' => [
          'mandatoryAttributeList' => [
            0 => 'isOptedOut'
          ]
        ],
        'opportunities' => [
          'linkOnlyNotLinked' => true
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Call' => [
      'listLoaderClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Meeting\\AcceptanceStatusLoader'
      ],
      'readLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Loader',
        1 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Call\\PhoneNumberMapLoader'
      ],
      'saverClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Saver'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Event\\BeforeUpdatePreserveDuration'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Call\\AfterUpdate'
      ],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Campaign' => [
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Campaign\\BeforeUpdate'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'CampaignLogRecord' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'forceSelectAllAttributes' => true,
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'CampaignTrackingUrl' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'campaignId'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\CampaignTrackingUrl\\BeforeCreate'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Case' => [
      'relationships' => [
        'articles' => [
          'linkRequiredAccess' => 'edit',
          'linkRequiredForeignAccess' => 'read'
        ]
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Case\\BeforeCreate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Case\\AfterCreate'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Contact' => [
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General',
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Contact\\AfterCreate'
      ],
      'relationships' => [
        'targetLists' => [
          'mandatoryAttributeList' => [
            0 => 'isOptedOut'
          ]
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'accountId',
        1 => 'accountName'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'DocumentFolder' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'EmailQueueItem' => [
      'massActions' => [
        'delete' => [
          'allowed' => true
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'KnowledgeBaseCategory' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Lead' => [
      'duplicateWhereBuilderClassName' => 'Espo\\Classes\\DuplicateWhereBuilders\\General',
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Lead\\AfterCreate'
      ],
      'relationships' => [
        'targetLists' => [
          'mandatoryAttributeList' => [
            0 => 'isOptedOut'
          ]
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'MassEmail' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'campaignId'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\MassEmail\\BeforeCreate'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Meeting' => [
      'listLoaderClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Meeting\\AcceptanceStatusLoader'
      ],
      'readLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Loader'
      ],
      'saverClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Saver',
        1 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\Meeting\\SourceEmailSaver'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Meeting\\BeforeCreateSourceEmailCheck'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Classes\\RecordHooks\\Event\\BeforeUpdatePreserveDuration'
      ],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Opportunity' => [
      'massActions' => [
        'update' => [
          'implementationClassName' => 'Espo\\Modules\\Crm\\Classes\\MassAction\\Opportunity\\MassUpdate'
        ]
      ],
      'mandatoryAttributeList' => [
        0 => 'accountId',
        1 => 'accountName'
      ],
      'beforeUpdateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Opportunity\\BeforeUpdate'
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'TargetList' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\TargetList\\EntryCountLoader',
        1 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\TargetList\\OptedOutCountLoader'
      ],
      'listLoaderClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\TargetList\\EntryCountLoader',
        1 => 'Espo\\Modules\\Crm\\Classes\\FieldProcessing\\TargetList\\OptedOutCountLoader'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\TargetList\\AfterCreate',
        1 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\TargetList\\AfterCreateDuplicate'
      ],
      'relationships' => [
        'users' => [
          'massLink' => true,
          'linkRequiredForeignAccess' => 'read',
          'mandatoryAttributeList' => [
            0 => 'targetListIsOptedOut'
          ]
        ],
        'leads' => [
          'massLink' => true,
          'linkRequiredForeignAccess' => 'read',
          'mandatoryAttributeList' => [
            0 => 'targetListIsOptedOut'
          ]
        ],
        'contacts' => [
          'massLink' => true,
          'linkRequiredForeignAccess' => 'read',
          'mandatoryAttributeList' => [
            0 => 'targetListIsOptedOut'
          ]
        ],
        'accounts' => [
          'massLink' => true,
          'linkRequiredForeignAccess' => 'read',
          'mandatoryAttributeList' => [
            0 => 'targetListIsOptedOut'
          ]
        ]
      ],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'TargetListCategory' => [
      'massActions' => [
        'update' => [
          'allowed' => true
        ],
        'delete' => [
          'allowed' => true
        ]
      ],
      'readLoaderClassNameList' => [],
      'listLoaderClassNameList' => [],
      'saverClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'beforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterCreateHookClassNameList' => [],
      'afterUpdateHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ],
    'Task' => [
      'readLoaderClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Loader'
      ],
      'saverClassNameList' => [
        0 => 'Espo\\Core\\FieldProcessing\\Reminder\\Saver'
      ],
      'beforeCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Task\\BeforeCreate'
      ],
      'afterCreateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Task\\AfterSave'
      ],
      'afterUpdateHookClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\RecordHooks\\Task\\AfterSave'
      ],
      'listLoaderClassNameList' => [],
      'selectApplierClassNameList' => [],
      'createInputFilterClassNameList' => [],
      'updateInputFilterClassNameList' => [],
      'outputFilterClassNameList' => [],
      'beforeReadHookClassNameList' => [],
      'earlyBeforeCreateHookClassNameList' => [],
      'earlyBeforeUpdateHookClassNameList' => [],
      'beforeUpdateHookClassNameList' => [],
      'beforeDeleteHookClassNameList' => [],
      'afterDeleteHookClassNameList' => [],
      'beforeLinkHookClassNameList' => [],
      'beforeUnlinkHookClassNameList' => [],
      'afterLinkHookClassNameList' => [],
      'afterUnlinkHookClassNameList' => []
    ]
  ],
  'scopes' => [
    'ActionHistoryRecord' => [
      'entity' => true
    ],
    'AddressCountry' => [
      'entity' => true,
      'importable' => true,
      'exportFormatList' => [
        0 => 'csv'
      ],
      'duplicateCheckFieldList' => [
        0 => 'name',
        1 => 'code'
      ]
    ],
    'AppLogRecord' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'AppSecret' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
      'duplicateCheckFieldList' => [
        0 => 'name'
      ]
    ],
    'ArrayValue' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'Attachment' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'AuthLogRecord' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'AuthToken' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'AuthenticationProvider' => [
      'entity' => true,
      'exportFormatList' => [
        0 => 'csv'
      ]
    ],
    'Autofollow' => [
      'entity' => true
    ],
    'Currency' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => true,
      'aclActionList' => [
        0 => 'read',
        1 => 'edit'
      ],
      'aclLevelList' => [
        0 => 'yes',
        1 => 'no'
      ],
      'aclHighestLevel' => 'yes',
      'aclFieldLevelDisabled' => true,
      'customizable' => false
    ],
    'CurrencyRecord' => [
      'entity' => true,
      'tab' => true
    ],
    'CurrencyRecordRate' => [
      'entity' => true,
      'preserveAuditLog' => true
    ],
    'Dashboard' => [
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'DashboardTemplate' => [
      'entity' => true,
      'exportFormatList' => [
        0 => 'csv'
      ],
      'importable' => true
    ],
    'Email' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => true,
      'aclPortalLevelList' => [
        0 => 'account',
        1 => 'contact',
        2 => 'own',
        3 => 'no'
      ],
      'aclPortalActionList' => [
        0 => 'read'
      ],
      'notifications' => true,
      'object' => true,
      'customizable' => true,
      'activity' => true,
      'activityStatusList' => [
        0 => 'Draft'
      ],
      'historyStatusList' => [
        0 => 'Archived',
        1 => 'Sent'
      ]
    ],
    'EmailAccount' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false
    ],
    'EmailAccountScope' => [
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean'
    ],
    'EmailAddress' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'EmailFilter' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false
    ],
    'EmailFolder' => [
      'entity' => true
    ],
    'EmailTemplate' => [
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'aclFieldLevelDisabled' => true,
      'customizable' => false,
      'importable' => true,
      'lastViewed' => true,
      'stars' => true
    ],
    'EmailTemplateCategory' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => true,
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'aclFieldLevelDisabled' => true,
      'customizable' => false,
      'importable' => false,
      'type' => 'CategoryTree',
      'notifications' => false
    ],
    'Export' => [
      'languageIsGlobal' => true
    ],
    'Extension' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'ExternalAccount' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean',
      'aclPortal' => false,
      'aclFieldLevelDisabled' => true,
      'customizable' => false,
      'languageIsGlobal' => true
    ],
    'Formula' => [
      'languageIsGlobal' => true
    ],
    'GlobalStream' => [
      'entity' => false,
      'layouts' => false,
      'tab' => true,
      'acl' => 'boolean',
      'customizable' => false
    ],
    'GroupEmailFolder' => [
      'entity' => true
    ],
    'Import' => [
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => 'boolean',
      'aclFieldLevelDisabled' => true,
      'customizable' => false
    ],
    'ImportEml' => [
      'entity' => false
    ],
    'ImportError' => [
      'entity' => true
    ],
    'InboundEmail' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false
    ],
    'Integration' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
      'languageAclDisabled' => true
    ],
    'Job' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'LastViewed' => [
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'LayoutRecord' => [
      'entity' => true
    ],
    'LayoutSet' => [
      'entity' => true
    ],
    'LeadCapture' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'LeadCaptureLogRecord' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'MassAction' => [
      'languageIsGlobal' => true
    ],
    'Note' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => true,
      'entityManager' => [
        'edit' => false,
        'fields' => true,
        'relationships' => false,
        'formula' => false,
        'layouts' => false,
        'addField' => false
      ]
    ],
    'Notification' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'OAuthAccount' => [
      'entity' => true
    ],
    'OAuthProvider' => [
      'entity' => true,
      'duplicateCheckFieldList' => [
        0 => 'name'
      ]
    ],
    'OpenApi' => [
      'acl' => 'boolean'
    ],
    'PasswordChangeRequest' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'PhoneNumber' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'Portal' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
      'preserveAuditLog' => true
    ],
    'PortalRole' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
      'importable' => true,
      'exportFormatList' => [
        0 => 'csv'
      ],
      'preserveAuditLog' => true
    ],
    'PortalUser' => [
      'tab' => true,
      'tabAclPermission' => 'portalPermission'
    ],
    'Preferences' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'Role' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
      'importable' => true,
      'exportFormatList' => [
        0 => 'csv'
      ],
      'preserveAuditLog' => true
    ],
    'ScheduledJob' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'ScheduledJobLogRecord' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'Stream' => [
      'entity' => false,
      'layouts' => false,
      'tab' => true,
      'acl' => false,
      'customizable' => false
    ],
    'StreamSubscription' => [
      'entity' => true
    ],
    'Team' => [
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'aclActionList' => [
        0 => 'read'
      ],
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'importable' => true,
      'customizable' => false,
      'preserveAuditLog' => true
    ],
    'Template' => [
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'aclPortal' => true,
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'no'
      ],
      'aclPortalActionList' => [
        0 => 'read'
      ],
      'aclFieldLevelDisabled' => true,
      'aclPortalFieldLevelDisabled' => true,
      'customizable' => false,
      'importable' => true,
      'disabled' => false,
      'lastViewed' => true
    ],
    'UniqueId' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false
    ],
    'User' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclActionList' => [
        0 => 'read',
        1 => 'edit'
      ],
      'aclActionLevelListMap' => [
        'edit' => [
          0 => 'own',
          1 => 'no'
        ]
      ],
      'customizable' => true,
      'object' => true,
      'preserveAuditLog' => true
    ],
    'UserData' => [
      'entity' => true
    ],
    'UserReaction' => [
      'entity' => true
    ],
    'Webhook' => [
      'entity' => true,
      'acl' => 'boolean',
      'aclFieldLevelDisabled' => true
    ],
    'WebhookEventQueueItem' => [
      'entity' => true
    ],
    'WebhookQueueItem' => [
      'entity' => true
    ],
    'WorkingTimeCalendar' => [
      'entity' => true,
      'acl' => 'boolean',
      'aclFieldLevelDisabled' => true,
      'tab' => true,
      'layouts' => false,
      'customizable' => false
    ],
    'WorkingTimeRange' => [
      'entity' => true,
      'acl' => false,
      'tab' => false,
      'layouts' => false,
      'customizable' => false
    ],
    'Account' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'stars' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
      'hasPersonalData' => true,
      'duplicateCheckFieldList' => [
        0 => 'name',
        1 => 'emailAddress'
      ]
    ],
    'Activities' => [
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean',
      'aclPortal' => 'boolean',
      'module' => 'Crm',
      'customizable' => false
    ],
    'Calendar' => [
      'entity' => false,
      'tab' => true,
      'acl' => 'boolean',
      'module' => 'Crm'
    ],
    'Call' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => true,
      'notifications' => true,
      'calendar' => true,
      'activity' => true,
      'object' => true,
      'activityStatusList' => [
        0 => 'Planned'
      ],
      'historyStatusList' => [
        0 => 'Held',
        1 => 'Not Held'
      ],
      'completedStatusList' => [
        0 => 'Held'
      ],
      'canceledStatusList' => [
        0 => 'Not Held'
      ],
      'statusField' => 'status',
      'statusFieldLocked' => true,
      'attendeeLinkMap' => [
        'Contact' => 'contacts',
        'Lead' => 'leads',
        'User' => 'users'
      ]
    ],
    'Campaign' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => false,
      'importable' => false,
      'object' => true,
      'statusField' => 'status'
    ],
    'CampaignLogRecord' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'stream' => false,
      'importable' => false
    ],
    'CampaignTrackingUrl' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'stream' => false,
      'importable' => false
    ],
    'Case' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
      'statusField' => 'status'
    ],
    'Contact' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'stars' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
      'hasPersonalData' => true,
      'duplicateCheckFieldList' => [
        0 => 'name',
        1 => 'emailAddress'
      ]
    ],
    'Document' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => true,
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'account',
        2 => 'contact',
        3 => 'own',
        4 => 'no'
      ],
      'module' => 'Crm',
      'customizable' => true,
      'importable' => false,
      'notifications' => true,
      'object' => true
    ],
    'DocumentFolder' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'no'
      ],
      'acl' => true,
      'aclPortal' => true,
      'aclPortalActionList' => [
        0 => 'read'
      ],
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false,
      'type' => 'CategoryTree',
      'stream' => false,
      'notifications' => false,
      'categoryParentEntityType' => 'Document',
      'categoryField' => 'folder'
    ],
    'EmailQueueItem' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false,
      'module' => 'Crm'
    ],
    'KnowledgeBaseArticle' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => true,
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'no'
      ],
      'aclPortalActionList' => [
        0 => 'read'
      ],
      'module' => 'Crm',
      'customizable' => true,
      'importable' => true,
      'notifications' => false,
      'object' => true
    ],
    'KnowledgeBaseCategory' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'no'
      ],
      'acl' => true,
      'aclPortal' => true,
      'aclPortalActionList' => [
        0 => 'read'
      ],
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false,
      'type' => 'CategoryTree',
      'stream' => false,
      'notifications' => false,
      'categoryParentEntityType' => 'KnowledgeBaseArticle',
      'categoryField' => 'categories'
    ],
    'Lead' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
      'statusField' => 'status',
      'hasPersonalData' => true,
      'duplicateCheckFieldList' => [
        0 => 'name',
        1 => 'emailAddress'
      ]
    ],
    'MassEmail' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false,
      'module' => 'Crm'
    ],
    'Meeting' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => true,
      'notifications' => true,
      'stream' => true,
      'calendar' => true,
      'activity' => true,
      'object' => true,
      'activityStatusList' => [
        0 => 'Planned'
      ],
      'historyStatusList' => [
        0 => 'Held',
        1 => 'Not Held'
      ],
      'completedStatusList' => [
        0 => 'Held'
      ],
      'canceledStatusList' => [
        0 => 'Not Held'
      ],
      'statusField' => 'status',
      'statusFieldLocked' => true,
      'attendeeLinkMap' => [
        'Contact' => 'contacts',
        'Lead' => 'leads',
        'User' => 'users'
      ]
    ],
    'Opportunity' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
      'statusField' => 'stage',
      'kanbanStatusIgnoreList' => [
        0 => 'Closed Lost'
      ],
      'currencyConversionAccessRequiredFieldList' => [
        0 => 'amount'
      ]
    ],
    'Reminder' => [
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false
    ],
    'Target' => [
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false,
      'notifications' => false,
      'object' => true,
      'disabled' => true
    ],
    'TargetList' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => false,
      'importable' => false,
      'notifications' => true,
      'object' => true,
      'targetLinkList' => [
        0 => 'accounts',
        1 => 'contacts',
        2 => 'leads',
        3 => 'users'
      ]
    ],
    'TargetListCategory' => [
      'entity' => true,
      'acl' => true,
      'aclLevelList' => [
        0 => 'all',
        1 => 'team',
        2 => 'no'
      ],
      'module' => 'Crm',
      'customizable' => false,
      'entityManager' => [
        'fields' => false,
        'formula' => false,
        'relationships' => false,
        'addField' => false,
        'edit' => false,
        'layouts' => false
      ],
      'type' => 'CategoryTree',
      'categoryParentEntityType' => 'TargetList',
      'categoryField' => 'category'
    ],
    'Task' => [
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => true,
      'aclPortalLevelList' => [
        0 => 'all',
        1 => 'account',
        2 => 'contact',
        3 => 'own',
        4 => 'no'
      ],
      'activityStatusList' => [
        0 => 'Not Started',
        1 => 'Started'
      ],
      'historyStatusList' => [
        0 => 'Completed'
      ],
      'completedStatusList' => [
        0 => 'Completed'
      ],
      'canceledStatusList' => [
        0 => 'Canceled'
      ],
      'module' => 'Crm',
      'customizable' => true,
      'importable' => true,
      'notifications' => true,
      'calendar' => true,
      'calendarOneDay' => true,
      'object' => true,
      'statusField' => 'status',
      'stream' => true,
      'kanbanStatusIgnoreList' => [
        0 => 'Canceled',
        1 => 'Deferred'
      ],
      'statusFieldLocked' => true
    ]
  ],
  'selectDefs' => [
    'ActionHistoryRecord' => [
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Classes\\Select\\ActionHistoryRecord\\AccessControlFilters\\OnlyOwn'
      ],
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Classes\\Select\\ActionHistoryRecord\\BoolFilters\\OnlyMy'
      ]
    ],
    'AddressCountry' => [
      'ordererClassNameMap' => [
        'preferredName' => 'Espo\\Classes\\Select\\AddressCountry\\PreferredNameOrderer'
      ]
    ],
    'AppLogRecord' => [
      'primaryFilterClassNameMap' => [
        'errors' => 'Espo\\Classes\\Select\\AppLogRecord\\PrimaryFilters\\Errors'
      ]
    ],
    'Attachment' => [
      'primaryFilterClassNameMap' => [
        'orphan' => 'Espo\\Classes\\Select\\Attachment\\PrimaryFilters\\Orphan'
      ]
    ],
    'AuthLogRecord' => [
      'primaryFilterClassNameMap' => [
        'denied' => 'Espo\\Classes\\Select\\AuthLogRecord\\PrimaryFilters\\Denied',
        'accepted' => 'Espo\\Classes\\Select\\AuthLogRecord\\PrimaryFilters\\Accepted'
      ]
    ],
    'AuthToken' => [
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Classes\\Select\\AuthToken\\PrimaryFilters\\Active',
        'inactive' => 'Espo\\Classes\\Select\\AuthToken\\PrimaryFilters\\Inactive'
      ]
    ],
    'CurrencyRecord' => [
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Classes\\Select\\CurrencyRecord\\PrimaryFilters\\Active'
      ],
      'selectAttributesDependencyMap' => [
        'id' => [
          0 => 'code'
        ]
      ],
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Boolean'
    ],
    'CurrencyRecordRate' => [
      'selectAttributesDependencyMap' => [
        'id' => [
          0 => 'recordId',
          1 => 'recordName',
          2 => 'baseCode'
        ]
      ],
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Boolean'
    ],
    'Email' => [
      'whereItemConverterClassNameMap' => [
        'folderId_inFolder' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\InFolder',
        'emailAddress_equals' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\EmailAddressEquals',
        'from_equals' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\FromEquals',
        'to_equals' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\ToEquals',
        'cc_equals' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\CcEquals',
        'isNotReplied_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotRepliedIsTrue',
        'isNotReplied_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotRepliedIsFalse',
        'isNotRead_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotReadIsTrue',
        'isNotRead_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotReadIsFalse',
        'isRead_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotReadIsFalse',
        'isRead_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsNotReadIsTrue',
        'inTrash_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\InTrashIsFalse',
        'inTrash_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\InTrashIsTrue',
        'inArchive_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\InArchiveIsFalse',
        'inArchive_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\InArchiveIsTrue',
        'isImportant_isTrue' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsImportantIsTrue',
        'isImportant_isFalse' => 'Espo\\Classes\\Select\\Email\\Where\\ItemConverters\\IsImportantIsFalse'
      ],
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Classes\\Select\\Email\\AccessControlFilters\\OnlyOwn',
        'portalOnlyOwn' => 'Espo\\Classes\\Select\\Email\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Classes\\Select\\Email\\AccessControlFilters\\OnlyTeam',
        'portalOnlyContact' => 'Espo\\Classes\\Select\\Email\\AccessControlFilters\\PortalOnlyContact',
        'portalOnlyAccount' => 'Espo\\Classes\\Select\\Email\\AccessControlFilters\\PortalOnlyAccount'
      ],
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Classes\\Select\\Email\\BoolFilters\\OnlyMy'
      ],
      'textFilterClassName' => 'Espo\\Classes\\Select\\Email\\TextFilter',
      'textFilterUseContainsAttributeList' => [
        0 => 'name'
      ],
      'selectAttributesDependencyMap' => [
        'subject' => [
          0 => 'name',
          1 => 'isAutoReply',
          2 => 'hasAttachment'
        ],
        'personStringData' => [
          0 => 'fromString',
          1 => 'fromEmailAddressId'
        ],
        'replyToName' => [
          0 => 'replyToString'
        ]
      ]
    ],
    'EmailAccount' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\EmailAccount\\AccessControlFilters\\Mandatory'
      ],
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Classes\\Select\\EmailAccount\\PrimaryFilters\\Active'
      ]
    ],
    'EmailAddress' => [
      'primaryFilterClassNameMap' => [
        'orphan' => 'Espo\\Classes\\Select\\EmailAddress\\PrimaryFilters\\Orphan'
      ]
    ],
    'EmailFilter' => [
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Classes\\Select\\EmailFilter\\AccessControlFilters\\OnlyOwn'
      ],
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Classes\\Select\\EmailFilter\\BoolFilters\\OnlyMy'
      ]
    ],
    'EmailFolder' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\EmailFolder\\AccessControlFilters\\Mandatory'
      ]
    ],
    'EmailTemplate' => [
      'primaryFilterClassNameMap' => [
        'actual' => 'Espo\\Classes\\Select\\EmailTemplate\\PrimaryFilters\\Actual'
      ]
    ],
    'GroupEmailFolder' => [
      'accessControlFilterClassNameMap' => [
        'onlyTeam' => 'Espo\\Classes\\Select\\GroupEmailFolder\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'Import' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\Import\\AccessControlFilters\\Mandatory'
      ],
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Bypass'
    ],
    'ImportError' => [
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Core\\Select\\AccessControl\\Filters\\ForeignOnlyOwn'
      ],
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\BooleanOwn',
      'selectAttributesDependencyMap' => [
        'lineNumber' => [
          0 => 'rowIndex'
        ],
        'exportLineNumber' => [
          0 => 'exportRowIndex'
        ]
      ],
      'orderItemConverterClassNameMap' => [
        'lineNumber' => 'Espo\\Classes\\Select\\ImportError\\OrderItemConverters\\LineNumber',
        'exportLineNumber' => 'Espo\\Classes\\Select\\ImportError\\OrderItemConverters\\ExportLineNumber'
      ]
    ],
    'InboundEmail' => [
      'selectAttributesDependencyMap' => [
        'name' => [
          0 => 'emailAddress',
          1 => 'useSmtp',
          2 => 'status'
        ]
      ]
    ],
    'Note' => [
      'primaryFilterClassNameMap' => [
        'posts' => 'Espo\\Classes\\Select\\Note\\PrimaryFilters\\Posts',
        'updates' => 'Espo\\Classes\\Select\\Note\\PrimaryFilters\\Updates'
      ],
      'boolFilterClassNameMap' => [
        'skipOwn' => 'Espo\\Classes\\Select\\Note\\BoolFilters\\SkipOwn'
      ]
    ],
    'PhoneNumber' => [
      'primaryFilterClassNameMap' => [
        'orphan' => 'Espo\\Classes\\Select\\PhoneNumber\\PrimaryFilters\\Orphan'
      ]
    ],
    'ScheduledJob' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\ScheduledJob\\AccessControlFilters\\Mandatory'
      ]
    ],
    'Team' => [
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Classes\\Select\\Team\\BoolFilters\\OnlyMy'
      ],
      'accessControlFilterClassNameMap' => [
        'onlyTeam' => 'Espo\\Classes\\Select\\Team\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'Template' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\Template\\AccessControlFilters\\Mandatory'
      ],
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Classes\\Select\\Template\\PrimaryFilters\\Active'
      ]
    ],
    'User' => [
      'whereItemConverterClassNameMap' => [
        'id_isOfType' => 'Espo\\Classes\\Select\\User\\Where\\ItemConverters\\IsOfType'
      ],
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\User\\AccessControlFilters\\Mandatory',
        'onlyTeam' => 'Espo\\Classes\\Select\\User\\AccessControlFilters\\OnlyTeam',
        'onlyOwn' => 'Espo\\Classes\\Select\\User\\AccessControlFilters\\OnlyOwn',
        'portalOnlyOwn' => 'Espo\\Classes\\Select\\User\\AccessControlFilters\\PortalOnlyOwn'
      ],
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\Active',
        'activePortal' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\ActivePortal',
        'activeApi' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\ActiveApi',
        'portal' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\Portal',
        'api' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\Api',
        'internal' => 'Espo\\Classes\\Select\\User\\PrimaryFilters\\Internal'
      ],
      'boolFilterClassNameMap' => [
        'onlyMyTeam' => 'Espo\\Classes\\Select\\User\\BoolFilters\\OnlyMyTeam',
        'onlyMe' => 'Espo\\Classes\\Select\\User\\BoolFilters\\OnlyMe'
      ],
      'orderItemConverterClassNameMap' => [
        'userNameOwnFirst' => 'Espo\\Classes\\Select\\User\\OrderItemConverters\\UserNameOwnFirst'
      ]
    ],
    'Webhook' => [
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Classes\\Select\\Webhook\\AccessControlFilters\\Mandatory'
      ],
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Bypass'
    ],
    'WorkingTimeCalendar' => [
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Boolean'
    ],
    'WorkingTimeRange' => [
      'accessControlFilterResolverClassName' => 'Espo\\Core\\Select\\AccessControl\\FilterResolvers\\Boolean',
      'primaryFilterClassNameMap' => [
        'actual' => 'Espo\\Classes\\Select\\WorkingTimeRange\\PrimaryFilters\\Actual'
      ]
    ],
    'Account' => [
      'primaryFilterClassNameMap' => [
        'customers' => 'Espo\\Modules\\Crm\\Classes\\Select\\Account\\PrimaryFilters\\Customers',
        'resellers' => 'Espo\\Modules\\Crm\\Classes\\Select\\Account\\PrimaryFilters\\Resellers',
        'partners' => 'Espo\\Modules\\Crm\\Classes\\Select\\Account\\PrimaryFilters\\Partners',
        'recentlyCreated' => 'Espo\\Modules\\Crm\\Classes\\Select\\Account\\PrimaryFilters\\RecentlyCreated'
      ],
      'accessControlFilterClassNameMap' => [
        'portalOnlyAccount' => 'Espo\\Modules\\Crm\\Classes\\Select\\Account\\AccessControlFilters\\PortalOnlyAccount'
      ]
    ],
    'Call' => [
      'selectAttributesDependencyMap' => [
        'duration' => [
          0 => 'dateStart',
          1 => 'dateEnd'
        ],
        'dateStart' => [
          0 => 'dateEnd'
        ]
      ],
      'primaryFilterClassNameMap' => [
        'planned' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\PrimaryFilters\\Planned',
        'held' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\PrimaryFilters\\Held',
        'todays' => 'Espo\\Modules\\Crm\\Classes\\Select\\Call\\PrimaryFilters\\Todays'
      ],
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\BoolFilters\\OnlyMy'
      ],
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'Campaign' => [
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Modules\\Crm\\Classes\\Select\\Campaign\\PrimaryFilters\\Active'
      ]
    ],
    'CampaignLogRecord' => [
      'primaryFilterClassNameMap' => [
        'opened' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\Opened',
        'sent' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\Sent',
        'clicked' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\Clicked',
        'optedOut' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\OptedOut',
        'optedIn' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\OptedIn',
        'bounced' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\Bounced',
        'leadCreated' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\PrimaryFilters\\LeadCreated'
      ],
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignLogRecord\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'CampaignTrackingUrl' => [
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignTrackingUrl\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Modules\\Crm\\Classes\\Select\\CampaignTrackingUrl\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'Case' => [
      'primaryFilterClassNameMap' => [
        'open' => 'Espo\\Modules\\Crm\\Classes\\Select\\CaseObj\\PrimaryFilters\\Open',
        'closed' => 'Espo\\Modules\\Crm\\Classes\\Select\\CaseObj\\PrimaryFilters\\Closed'
      ],
      'boolFilterClassNameMap' => [
        'open' => 'Espo\\Modules\\Crm\\Classes\\Select\\CaseObj\\BoolFilters\\Open'
      ],
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Modules\\Crm\\Classes\\Select\\Case\\AccessControlFilters\\Mandatory'
      ],
      'selectAttributesDependencyMap' => [
        'contactsIds' => [
          0 => 'contactId'
        ]
      ]
    ],
    'Contact' => [
      'primaryFilterClassNameMap' => [
        'portalUsers' => 'Espo\\Modules\\Crm\\Classes\\Select\\Contact\\PrimaryFilters\\PortalUsers',
        'notPortalUsers' => 'Espo\\Modules\\Crm\\Classes\\Select\\Contact\\PrimaryFilters\\NotPortalUsers',
        'accountActive' => 'Espo\\Modules\\Crm\\Classes\\Select\\Contact\\PrimaryFilters\\AccountActive'
      ],
      'accessControlFilterClassNameMap' => [
        'portalOnlyContact' => 'Espo\\Modules\\Crm\\Classes\\Select\\Contact\\AccessControlFilters\\PortalOnlyContact'
      ],
      'selectAttributesDependencyMap' => [
        'accountId' => [
          0 => 'accountIsInactive'
        ]
      ]
    ],
    'Document' => [
      'primaryFilterClassNameMap' => [
        'active' => 'Espo\\Modules\\Crm\\Classes\\Select\\Document\\PrimaryFilters\\Active',
        'draft' => 'Espo\\Modules\\Crm\\Classes\\Select\\Document\\PrimaryFilters\\Draft'
      ]
    ],
    'EmailQueueItem' => [
      'primaryFilterClassNameMap' => [
        'pending' => 'Espo\\Modules\\Crm\\Classes\\Select\\EmailQueueItem\\PrimaryFilters\\Pending',
        'failed' => 'Espo\\Modules\\Crm\\Classes\\Select\\EmailQueueItem\\PrimaryFilters\\Failed',
        'sent' => 'Espo\\Modules\\Crm\\Classes\\Select\\EmailQueueItem\\PrimaryFilters\\Sent'
      ]
    ],
    'KnowledgeBaseArticle' => [
      'primaryFilterClassNameMap' => [
        'published' => 'Espo\\Modules\\Crm\\Classes\\Select\\KnowledgeBaseArticle\\PrimaryFilters\\Published'
      ],
      'accessControlFilterClassNameMap' => [
        'mandatory' => 'Espo\\Modules\\Crm\\Classes\\Select\\KnowledgeBaseArticle\\AccessControlFilters\\Mandatory'
      ]
    ],
    'Lead' => [
      'primaryFilterClassNameMap' => [
        'actual' => 'Espo\\Modules\\Crm\\Classes\\Select\\Lead\\PrimaryFilters\\Actual',
        'active' => 'Espo\\Modules\\Crm\\Classes\\Select\\Lead\\PrimaryFilters\\Actual',
        'converted' => 'Espo\\Modules\\Crm\\Classes\\Select\\Lead\\PrimaryFilters\\Converted'
      ]
    ],
    'MassEmail' => [
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Modules\\Crm\\Classes\\Select\\MassEmail\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Modules\\Crm\\Classes\\Select\\MassEmail\\AccessControlFilters\\OnlyTeam'
      ],
      'primaryFilterClassNameMap' => [
        'actual' => 'Espo\\Modules\\Crm\\Classes\\Select\\MassEmail\\PrimaryFilters\\Actual',
        'complete' => 'Espo\\Modules\\Crm\\Classes\\Select\\MassEmail\\PrimaryFilters\\Complete'
      ]
    ],
    'Meeting' => [
      'whereDateTimeItemTransformerClassName' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\Where\\DateTimeItemTransformer',
      'selectAttributesDependencyMap' => [
        'duration' => [
          0 => 'dateStart',
          1 => 'dateEnd'
        ],
        'dateStart' => [
          0 => 'dateEnd'
        ],
        'dateStartDate' => [
          0 => 'dateEnd',
          1 => 'dateEndDate'
        ]
      ],
      'primaryFilterClassNameMap' => [
        'planned' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\PrimaryFilters\\Planned',
        'held' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\PrimaryFilters\\Held',
        'todays' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\PrimaryFilters\\Todays'
      ],
      'boolFilterClassNameMap' => [
        'onlyMy' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\BoolFilters\\OnlyMy'
      ],
      'accessControlFilterClassNameMap' => [
        'onlyOwn' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\AccessControlFilters\\OnlyOwn',
        'onlyTeam' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\AccessControlFilters\\OnlyTeam'
      ]
    ],
    'Opportunity' => [
      'primaryFilterClassNameMap' => [
        'open' => 'Espo\\Modules\\Crm\\Classes\\Select\\Opportunity\\PrimaryFilters\\Open',
        'won' => 'Espo\\Modules\\Crm\\Classes\\Select\\Opportunity\\PrimaryFilters\\Won',
        'lost' => 'Espo\\Modules\\Crm\\Classes\\Select\\Opportunity\\PrimaryFilters\\Lost'
      ],
      'selectAttributesDependencyMap' => [
        'contactsIds' => [
          0 => 'contactId'
        ]
      ]
    ],
    'TargetList' => [
      'selectAttributesDependencyMap' => [
        'targetStatus' => [
          0 => 'isOptedOut'
        ]
      ]
    ],
    'Task' => [
      'whereDateTimeItemTransformerClassName' => 'Espo\\Modules\\Crm\\Classes\\Select\\Meeting\\Where\\DateTimeItemTransformer',
      'selectAttributesDependencyMap' => [
        'dateEnd' => [
          0 => 'status'
        ]
      ],
      'primaryFilterClassNameMap' => [
        'actual' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\Actual',
        'completed' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\Completed',
        'deferred' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\Deferred',
        'todays' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\Todays',
        'actualStartingNotInFuture' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\ActualStartingNotInFuture',
        'overdue' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\PrimaryFilters\\Overdue'
      ],
      'boolFilterClassNameMap' => [
        'actual' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\BoolFilters\\Actual',
        'completed' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\BoolFilters\\Completed'
      ],
      'ordererClassNameMap' => [
        'dateUpcoming' => 'Espo\\Modules\\Crm\\Classes\\Select\\Task\\Orderers\\DateUpcoming'
      ]
    ]
  ],
  'themes' => [
    'Dark' => [
      'stylesheet' => 'client/css/espo/dark.css',
      'stylesheetIframe' => 'client/css/espo/dark-iframe.css',
      'stylesheetIframeFallback' => 'client/css/espo/hazyblue-iframe.css',
      'logo' => 'client/img/logo.svg',
      'textColor' => '#dedede',
      'chartGridColor' => '#646060',
      'chartTickColor' => '#575454',
      'chartSuccessColor' => '#5d8a55',
      'chartColorList' => [
        0 => '#7492cc',
        1 => '#c29c4a',
        2 => '#a1404a',
        3 => '#6a5f96',
        4 => '#b07e53',
        5 => '#3a5691',
        6 => '#7c593d',
        7 => '#a17a97',
        8 => '#858585'
      ],
      'chartColorAlternativeList' => [
        0 => '#7492cc',
        1 => '#c29c4a',
        2 => '#a1404a',
        3 => '#6a5f96',
        4 => '#b07e53'
      ],
      'calendarColors' => [
        '' => '#a58dc7a0',
        'bg' => '#323a49b3',
        'Meeting' => '#4c5972',
        'Call' => '#9b4260',
        'Task' => '#52744c'
      ],
      'isDark' => true
    ],
    'Espo' => [
      'stylesheet' => 'client/css/espo/espo.css',
      'stylesheetIframe' => 'client/css/espo/espo-iframe.css',
      'logo' => 'client/img/logo-light.svg',
      'params' => [
        'navbar' => [
          'type' => 'enum',
          'default' => 'side',
          'options' => [
            0 => 'side',
            1 => 'top'
          ]
        ]
      ],
      'mappedParams' => [
        'navbarHeight' => [
          'param' => 'navbar',
          'valueMap' => [
            'side' => 32,
            'top' => 43
          ]
        ]
      ],
      'dashboardCellHeight' => 40,
      'dashboardCellMargin' => 16,
      'navbarHeight' => 43,
      'modalFooterAtTheTop' => true,
      'modalFullHeight' => true,
      'fontSize' => 14,
      'textColor' => '#333',
      'hoverColor' => '#FF3F19',
      'chartGridColor' => '#ddd',
      'chartSuccessColor' => '#6fc374',
      'chartTickColor' => '#e8eced',
      'chartColorList' => [
        0 => '#6FA8D6',
        1 => '#4E6CAD',
        2 => '#EDC555',
        3 => '#ED8F42',
        4 => '#DE6666',
        5 => '#7CC4A4',
        6 => '#8A7CC2',
        7 => '#D4729B',
        8 => '#bfbfbf'
      ],
      'chartColorAlternativeList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#ED8F42',
        3 => '#7CC4A4',
        4 => '#D4729B'
      ],
      'calendarColors' => [
        '' => '#a58dc7a0',
        'bg' => '#d5ddf6a0'
      ],
      'isDark' => false
    ],
    'EspoRtl' => [
      'stylesheet' => 'client/css/espo/espo-rtl.css',
      'stylesheetIframe' => 'client/css/espo/espo-rtl-iframe.css',
      'logo' => 'client/img/logo-light.svg',
      'params' => [
        'navbar' => [
          'type' => 'enum',
          'default' => 'top',
          'options' => [
            0 => 'top',
            1 => 'side'
          ]
        ]
      ]
    ],
    'Glass' => [
      'stylesheet' => 'client/css/espo/glass.css',
      'stylesheetIframe' => 'client/css/espo/glass-iframe.css',
      'stylesheetIframeFallback' => 'client/css/espo/hazyblue-iframe.css',
      'logo' => 'client/img/logo.svg',
      'textColor' => '#dedede',
      'chartGridColor' => '#646060',
      'chartTickColor' => '#575454',
      'chartSuccessColor' => '#5d8a55',
      'chartColorList' => [
        0 => '#7492cc',
        1 => '#c29c4a',
        2 => '#a1404a',
        3 => '#6a5f96',
        4 => '#b07e53',
        5 => '#3a5691',
        6 => '#7c593d',
        7 => '#a17a97',
        8 => '#858585'
      ],
      'chartColorAlternativeList' => [
        0 => '#7492cc',
        1 => '#c29c4a',
        2 => '#a1404a',
        3 => '#6a5f96',
        4 => '#b07e53'
      ],
      'calendarColors' => [
        '' => '#a58dc7a0',
        'bg' => '#45528166',
        'Meeting' => '#6680b3d1',
        'Call' => '#a1404ad1',
        'Task' => '#5d8a55d1'
      ],
      'isDark' => true
    ],
    'Hazyblue' => [
      'stylesheet' => 'client/css/espo/hazyblue.css',
      'stylesheetIframe' => 'client/css/espo/hazyblue-iframe.css',
      'logo' => 'client/img/logo-light.svg',
      'textColor' => '#333',
      'chartGridColor' => '#ddd',
      'chartTickColor' => '#e8eced',
      'chartSuccessColor' => '#85b75f',
      'chartColorList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#DE6666',
        3 => '#8A7CC2',
        4 => '#c1834d',
        5 => '#4E6CAD',
        6 => '#ED8F42',
        7 => '#d69cc7',
        8 => '#bfbfbf'
      ],
      'chartColorAlternativeList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#DE6666',
        3 => '#8A7CC2',
        4 => '#c1834d'
      ]
    ],
    'Light' => [
      'logo' => 'client/img/logo-light.svg',
      'stylesheet' => 'client/css/espo/light.css',
      'stylesheetIframe' => 'client/css/espo/light-iframe.css',
      'textColor' => '#0f0f0f',
      'chartGridColor' => '#ddd',
      'chartTickColor' => '#e8eced',
      'chartSuccessColor' => '#80ce8e',
      'chartColorList' => [
        0 => '#6FA8D6',
        1 => '#4E6CAD',
        2 => '#EDC555',
        3 => '#ED8F42',
        4 => '#DE6666',
        5 => '#7CC4A4',
        6 => '#8A7CC2',
        7 => '#D4729B',
        8 => '#bfbfbf'
      ],
      'chartColorAlternativeList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#ED8F42',
        3 => '#7CC4A4',
        4 => '#D4729B'
      ],
      'calendarColors' => [
        '' => '#a58dc7a0',
        'bg' => '#d5ddf6a0',
        'Call' => '#ca859f',
        'Meeting' => '#7da0c8',
        'Task' => '#88ce9b'
      ]
    ],
    'Sakura' => [
      'stylesheet' => 'client/css/espo/sakura.css',
      'stylesheetIframe' => 'client/css/espo/sakura-iframe.css',
      'logo' => 'client/img/logo-light.svg',
      'textColor' => '#424242',
      'chartGridColor' => '#ddd',
      'chartTickColor' => '#e8eced',
      'chartSuccessColor' => '#83CD77',
      'chartColorList' => [
        0 => '#6FA8D6',
        1 => '#4E6CAD',
        2 => '#EDC555',
        3 => '#ED8F42',
        4 => '#DE6666',
        5 => '#7CC4A4',
        6 => '#8A7CC2',
        7 => '#D4729B',
        8 => '#bfbfbf'
      ],
      'chartColorAlternativeList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#ED8F42',
        3 => '#7CC4A4',
        4 => '#D4729B'
      ]
    ],
    'Violet' => [
      'stylesheet' => 'client/css/espo/violet.css',
      'stylesheetIframe' => 'client/css/espo/violet-iframe.css',
      'logo' => 'client/img/logo-light.svg',
      'textColor' => '#424242',
      'chartGridColor' => '#ddd',
      'chartTickColor' => '#e8eced',
      'chartSuccessColor' => '#6fc374',
      'chartColorList' => [
        0 => '#6FA8D6',
        1 => '#4E6CAD',
        2 => '#EDC555',
        3 => '#ED8F42',
        4 => '#DE6666',
        5 => '#7CC4A4',
        6 => '#8A7CC2',
        7 => '#D4729B',
        8 => '#bfbfbf'
      ],
      'chartColorAlternativeList' => [
        0 => '#6FA8D6',
        1 => '#EDC555',
        2 => '#ED8F42',
        3 => '#7CC4A4',
        4 => '#D4729B'
      ]
    ]
  ],
  'pdfDefs' => [
    'Account' => [
      'dataLoaderClassNameList' => [
        0 => 'Espo\\Modules\\Crm\\Classes\\Pdf\\Account\\ExampleDataLoader'
      ]
    ]
  ],
  'streamDefs' => [
    'Call' => [
      'followingUsersField' => 'users',
      'subscribersCleanup' => [
        'enabled' => true,
        'dateField' => 'dateStart',
        'statusList' => [
          0 => 'Held',
          1 => 'Not Held'
        ]
      ]
    ],
    'Meeting' => [
      'followingUsersField' => 'users',
      'subscribersCleanup' => [
        'enabled' => true,
        'dateField' => 'dateStart',
        'statusList' => [
          0 => 'Held',
          1 => 'Not Held'
        ]
      ]
    ],
    'Task' => [
      'subscribersCleanup' => [
        'enabled' => true,
        'statusList' => [
          0 => 'Completed',
          1 => 'Canceled'
        ]
      ]
    ]
  ]
];
