<?php
return array (
  'app' => 
  array (
    'acl' => 
    array (
      'mandatory' => 
      array (
        'scopeLevel' => 
        array (
          'User' => 
          array (
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'no',
            'stream' => 'no',
            'create' => 'no',
          ),
          'Team' => 
          array (
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'no',
            'create' => 'no',
          ),
          'Note' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'Portal' => 
          array (
            'read' => 'all',
            'edit' => 'no',
            'delete' => 'no',
            'create' => 'no',
          ),
          'Attachment' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'EmailAccount' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'EmailFilter' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'EmailFolder' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'Preferences' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'no',
            'create' => 'no',
          ),
          'Notification' => 
          array (
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'own',
            'create' => 'no',
          ),
          'Role' => false,
          'PortalRole' => false,
          'MassEmail' => 'Campaign',
          'CampaignLogRecord' => 'Campaign',
          'CampaignTrackingUrl' => 'Campaign',
          'EmailQueueItem' => false,
        ),
        'fieldLevel' => 
        array (
        ),
        'scopeFieldLevel' => 
        array (
          'Attachment' => 
          array (
            'parent' => false,
          ),
          'User' => 
          array (
            'gender' => false,
          ),
          'EmailFolder' => 
          array (
            'assignedUser' => false,
          ),
          'Email' => 
          array (
            'inboundEmails' => false,
            'emailAccounts' => false,
          ),
        ),
      ),
      'default' => 
      array (
        'scopeLevel' => 
        array (
        ),
        'fieldLevel' => 
        array (
        ),
        'assignmentPermission' => 'all',
        'userPermission' => 'no',
        'portalPermission' => 'no',
      ),
      'scopeLevelTypesDefaults' => 
      array (
        'boolean' => true,
        'record' => 
        array (
          'read' => 'all',
          'stream' => 'all',
          'edit' => 'all',
          'delete' => 'no',
          'create' => 'yes',
        ),
      ),
    ),
    'aclPortal' => 
    array (
      'mandatory' => 
      array (
        'scopeLevel' => 
        array (
          'User' => 
          array (
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'no',
            'stream' => 'no',
            'create' => 'no',
          ),
          'Team' => false,
          'Note' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'Notification' => 
          array (
            'read' => 'own',
            'edit' => 'no',
            'delete' => 'own',
            'create' => 'no',
          ),
          'Portal' => false,
          'Attachment' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'own',
            'create' => 'yes',
          ),
          'EmailAccount' => false,
          'ExternalAccount' => false,
          'Role' => false,
          'PortalRole' => false,
          'EmailFilter' => false,
          'EmailFolder' => false,
          'EmailTemplate' => false,
          'Preferences' => 
          array (
            'read' => 'own',
            'edit' => 'own',
            'delete' => 'no',
            'create' => 'no',
          ),
          'MassEmail' => 'Campaign',
          'CampaignLogRecord' => 'Campaign',
          'CampaignTrackingUrl' => 'Campaign',
          'EmailQueueItem' => false,
        ),
        'fieldLevel' => 
        array (
        ),
        'scopeFieldLevel' => 
        array (
          'Preferences' => 
          array (
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
          ),
          'Call' => 
          array (
            'reminders' => false,
          ),
          'Meeting' => 
          array (
            'reminders' => false,
          ),
          'Attachment' => 
          array (
            'parent' => false,
          ),
          'Note' => 
          array (
            'isInternal' => false,
            'isGlobal' => false,
          ),
          'User' => 
          array (
            'gender' => false,
          ),
        ),
      ),
      'default' => 
      array (
        'scopeLevel' => 
        array (
        ),
        'fieldLevel' => 
        array (
          'assignedUser' => 
          array (
            'read' => 'yes',
            'edit' => 'no',
          ),
          'assignedUsers' => 
          array (
            'read' => 'yes',
            'edit' => 'no',
          ),
          'teams' => false,
        ),
        'scopeFieldLevel' => 
        array (
          'Call' => 
          array (
            'users' => 
            array (
              'read' => 'yes',
              'edit' => 'no',
            ),
            'leads' => false,
          ),
          'Meeting' => 
          array (
            'users' => 
            array (
              'read' => 'yes',
              'edit' => 'no',
            ),
            'leads' => false,
          ),
          'KnowledgeBaseArticle' => 
          array (
            'portals' => false,
            'order' => false,
          ),
          'Case' => 
          array (
            'status' => 
            array (
              'read' => 'yes',
              'edit' => 'no',
            ),
          ),
        ),
      ),
      'scopeLevelTypesDefaults' => 
      array (
        'boolean' => false,
        'record' => false,
      ),
    ),
    'adminPanel' => 
    array (
      'system' => 
      array (
        'label' => 'System',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#Admin/settings',
            'label' => 'Settings',
            'description' => 'settings',
          ),
          1 => 
          array (
            'url' => '#Admin/userInterface',
            'label' => 'User Interface',
            'description' => 'userInterface',
          ),
          2 => 
          array (
            'url' => '#Admin/authentication',
            'label' => 'Authentication',
            'description' => 'authentication',
          ),
          3 => 
          array (
            'url' => '#ScheduledJob',
            'label' => 'Scheduled Jobs',
            'description' => 'scheduledJob',
          ),
          4 => 
          array (
            'url' => '#Admin/currency',
            'label' => 'Currency',
            'description' => 'currency',
          ),
          5 => 
          array (
            'url' => '#Admin/notifications',
            'label' => 'Notifications',
            'description' => 'notifications',
          ),
          6 => 
          array (
            'url' => '#Admin/integrations',
            'label' => 'Integrations',
            'description' => 'integrations',
          ),
          7 => 
          array (
            'url' => '#Admin/upgrade',
            'label' => 'Upgrade',
            'description' => 'upgrade',
          ),
          8 => 
          array (
            'url' => '#Admin/clearCache',
            'label' => 'Clear Cache',
            'description' => 'clearCache',
          ),
          9 => 
          array (
            'url' => '#Admin/rebuild',
            'label' => 'Rebuild',
            'description' => 'rebuild',
          ),
        ),
      ),
      'users' => 
      array (
        'label' => 'Users',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#User',
            'label' => 'Users',
            'description' => 'users',
          ),
          1 => 
          array (
            'url' => '#Team',
            'label' => 'Teams',
            'description' => 'teams',
          ),
          2 => 
          array (
            'url' => '#Role',
            'label' => 'Roles',
            'description' => 'roles',
          ),
          3 => 
          array (
            'url' => '#Admin/authTokens',
            'label' => 'Auth Tokens',
            'description' => 'authTokens',
          ),
        ),
      ),
      'customization' => 
      array (
        'label' => 'Customization',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#Admin/layouts',
            'label' => 'Layout Manager',
            'description' => 'layoutManager',
          ),
          1 => 
          array (
            'url' => '#Admin/entityManager',
            'label' => 'Entity Manager',
            'description' => 'entityManager',
          ),
          2 => 
          array (
            'url' => '#Admin/extensions',
            'label' => 'Extensions',
            'description' => 'extensions',
          ),
        ),
      ),
      'email' => 
      array (
        'label' => 'Email',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#Admin/outboundEmails',
            'label' => 'Outbound Emails',
            'description' => 'outboundEmails',
          ),
          1 => 
          array (
            'url' => '#Admin/inboundEmails',
            'label' => 'Inbound Emails',
            'description' => 'inboundEmails',
          ),
          2 => 
          array (
            'url' => '#InboundEmail',
            'label' => 'Group Email Accounts',
            'description' => 'groupEmailAccounts',
          ),
          3 => 
          array (
            'url' => '#EmailAccount',
            'label' => 'Personal Email Accounts',
            'description' => 'personalEmailAccounts',
          ),
          4 => 
          array (
            'url' => '#EmailFilter',
            'label' => 'Email Filters',
            'description' => 'emailFilters',
          ),
          5 => 
          array (
            'url' => '#EmailTemplate',
            'label' => 'Email Templates',
            'description' => 'emailTemplates',
          ),
        ),
      ),
      'portal' => 
      array (
        'label' => 'Portal',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#Portal',
            'label' => 'Portals',
            'description' => 'portals',
          ),
          1 => 
          array (
            'url' => '#PortalRole',
            'label' => 'Portal Roles',
            'description' => 'portalRoles',
          ),
        ),
      ),
      'data' => 
      array (
        'label' => 'Data',
        'items' => 
        array (
          0 => 
          array (
            'url' => '#Import',
            'label' => 'Import',
            'description' => 'import',
          ),
        ),
      ),
    ),
    'defaultDashboardLayouts' => 
    array (
      'Standard' => 
      array (
        0 => 
        array (
          'name' => 'My Espo',
          'layout' => 
          array (
            0 => 
            array (
              'id' => 'defaultActivities',
              'name' => 'Activities',
              'x' => 2,
              'y' => 2,
              'width' => 2,
              'height' => 2,
            ),
            1 => 
            array (
              'id' => 'defaultStream',
              'name' => 'Stream',
              'x' => 0,
              'y' => 0,
              'width' => 2,
              'height' => 4,
            ),
            2 => 
            array (
              'id' => 'defaultTasks',
              'name' => 'Tasks',
              'x' => 2,
              'y' => 4,
              'width' => 2,
              'height' => 2,
            ),
          ),
        ),
      ),
    ),
    'defaultDashboardOptions' => 
    array (
      'Standard' => 
      array (
        'defaultStream' => 
        array (
          'displayRecords' => 10,
        ),
      ),
    ),
    'entityTemplateList' => 
    array (
      0 => 'Base',
      1 => 'Person',
      2 => 'Event',
    ),
    'jsLibs' => 
    array (
      'Flotr' => 
      array (
        'path' => 'client/lib/flotr2.js',
        'exportsTo' => 'window',
        'exportsAs' => 'Flotr',
      ),
      'Summernote' => 
      array (
        'path' => 'client/lib/summernote.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'summernote',
      ),
      'Textcomplete' => 
      array (
        'path' => 'client/lib/jquery.textcomplete.js',
        'exportsTo' => '$',
        'exportsAs' => 'textcomplete',
      ),
      'Select2' => 
      array (
        'path' => 'client/lib/select2.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'select2',
      ),
      'Selectize' => 
      array (
        'path' => 'client/lib/selectize.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'selectize',
      ),
      'Cropper' => 
      array (
        'path' => 'client/lib/cropper.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'cropper',
      ),
      'gridstack' => 
      array (
        'path' => 'client/lib/gridstack.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'gridstack',
      ),
      'full-calendar' => 
      array (
        'path' => 'client/modules/crm/lib/fullcalendar.min.js',
        'exportsTo' => '$',
        'exportsAs' => 'fullCalendar',
      ),
      'vis' => 
      array (
        'path' => 'client/modules/crm/lib/vis.min.js',
        'exportsAs' => 'vis',
      ),
    ),
    'popupNotifications' => 
    array (
      'event' => 
      array (
        'url' => 'Activities/action/popupNotifications',
        'interval' => 15,
        'view' => 'crm:views/meeting/popup-notification',
      ),
    ),
  ),
  'clientDefs' => 
  array (
    'AuthToken' => 
    array (
      'recordViews' => 
      array (
        'list' => 'Admin.AuthToken.Record.List',
      ),
    ),
    'Dashboard' => 
    array (
      'controller' => 'Controllers.Dashboard',
    ),
    'DynamicLogic' => 
    array (
      'itemTypes' => 
      array (
        'and' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/group-base',
          'operator' => 'and',
        ),
        'or' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/group-base',
          'operator' => 'or',
        ),
        'not' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/group-not',
          'operator' => 'not',
        ),
        'equals' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '=',
        ),
        'notEquals' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&ne;',
        ),
        'greaterThan' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&gt;',
        ),
        'lessThan' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&lt;',
        ),
        'greaterThanOrEquals' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&ge;',
        ),
        'lessThanOrEquals' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-base',
          'operatorString' => '&le;',
        ),
        'isEmpty' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= &empty;',
        ),
        'isNotEmpty' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '&ne; &empty;',
        ),
        'isTrue' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= 1',
        ),
        'isFalse' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-operator-only-base',
          'operatorString' => '= 0',
        ),
        'in' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-multiple-values-base',
          'operatorString' => '&isin;',
        ),
        'notIn' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-multiple-values-base',
          'operatorString' => '&notin;',
        ),
        'isToday' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-is-today',
          'operatorString' => '=',
        ),
        'inFuture' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-in-future',
          'operatorString' => '&isin;',
        ),
        'inPast' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-in-past',
          'operatorString' => '&isin;',
        ),
        'contains' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-link',
          'operatorString' => '&niv;',
        ),
        'notContains' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions-string/item-value-link',
          'operatorString' => '&notni',
        ),
      ),
      'fieldTypes' => 
      array (
        'bool' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => 
          array (
            0 => 'isTrue',
            1 => 'isFalse',
          ),
        ),
        'varchar' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => 
          array (
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty',
          ),
        ),
        'text' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
          ),
        ),
        'int' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
            4 => 'greaterThan',
            5 => 'lessThan',
            6 => 'greaterThanOrEquals',
            7 => 'lessThanOrEquals',
          ),
        ),
        'float' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/base',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
            4 => 'greaterThan',
            5 => 'lessThan',
            6 => 'greaterThanOrEquals',
            7 => 'lessThanOrEquals',
          ),
        ),
        'date' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast',
            5 => 'equals',
            6 => 'notEquals',
          ),
        ),
        'datetime' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast',
          ),
        ),
        'datetimeOptional' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/date',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'isToday',
            3 => 'inFuture',
            4 => 'inPast',
          ),
        ),
        'enum' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/enum',
          'typeList' => 
          array (
            0 => 'equals',
            1 => 'notEquals',
            2 => 'isEmpty',
            3 => 'isNotEmpty',
            4 => 'in',
            5 => 'notIn',
          ),
        ),
        'link' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
          ),
        ),
        'linkParent' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link-parent',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'equals',
            3 => 'notEquals',
          ),
        ),
        'linkMultiple' => 
        array (
          'view' => 'views/admin/dynamic-logic/conditions/field-types/link-multiple',
          'typeList' => 
          array (
            0 => 'isEmpty',
            1 => 'isNotEmpty',
            2 => 'contains',
            3 => 'notContains',
          ),
        ),
      ),
      'conditionTypes' => 
      array (
        'isTrue' => 
        array (
          'valueType' => 'empty',
        ),
        'isFalse' => 
        array (
          'valueType' => 'empty',
        ),
        'isEmpty' => 
        array (
          'valueType' => 'empty',
        ),
        'isNotEmpty' => 
        array (
          'valueType' => 'empty',
        ),
        'equals' => 
        array (
          'valueType' => 'field',
        ),
        'notEquals' => 
        array (
          'valueType' => 'field',
        ),
        'greaterThan' => 
        array (
          'valueType' => 'field',
        ),
        'lessThan' => 
        array (
          'valueType' => 'field',
        ),
        'greaterThanOrEquals' => 
        array (
          'valueType' => 'field',
        ),
        'lessThanOrEquals' => 
        array (
          'valueType' => 'field',
        ),
        'in' => 
        array (
          'valueType' => 'field',
        ),
        'notIn' => 
        array (
          'valueType' => 'field',
        ),
        'contains' => 
        array (
          'valueType' => 'custom',
        ),
        'notContains' => 
        array (
          'valueType' => 'custom',
        ),
        'inPast' => 
        array (
          'valueType' => 'empty',
        ),
        'isFuture' => 
        array (
          'valueType' => 'empty',
        ),
        'isToday' => 
        array (
          'valueType' => 'empty',
        ),
      ),
    ),
    'Email' => 
    array (
      'controller' => 'controllers/record',
      'acl' => 'acl/email',
      'model' => 'models/email',
      'views' => 
      array (
        'list' => 'views/email/list',
        'detail' => 'views/email/detail',
      ),
      'recordViews' => 
      array (
        'list' => 'views/email/record/list',
        'detail' => 'views/email/record/detail',
        'edit' => 'views/email/record/edit',
        'editQuick' => 'views/email/record/edit-quick',
        'detailQuick' => 'views/email/record/detail-quick',
        'compose' => 'views/email/record/compose',
      ),
      'modalViews' => 
      array (
        'detail' => 'views/email/modals/detail',
        'compose' => 'views/modals/compose-email',
      ),
      'quickCreateModalType' => 'compose',
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Compose',
              'action' => 'composeEmail',
              'style' => 'danger',
              'acl' => 'create',
            ),
          ),
          'dropdown' => 
          array (
            0 => 
            array (
              'label' => 'Archive Email',
              'link' => '#Email/create',
              'acl' => 'create',
            ),
            1 => 
            array (
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate',
            ),
            2 => 
            array (
              'label' => 'Folders',
              'link' => '#EmailFolder',
            ),
            3 => 
            array (
              'label' => 'Filters',
              'link' => '#EmailFilter',
            ),
            4 => 
            array (
              'label' => 'Email Accounts',
              'link' => '#EmailAccount',
              'aclScope' => 'EmailAccountScope',
            ),
          ),
        ),
        'detail' => 
        array (
          'dropdown' => 
          array (
            0 => 
            array (
              'label' => 'Reply',
              'action' => 'reply',
              'acl' => 'read',
            ),
            1 => 
            array (
              'label' => 'Reply to All',
              'action' => 'replyToAll',
              'acl' => 'read',
            ),
            2 => 
            array (
              'label' => 'Forward',
              'action' => 'forward',
              'acl' => 'read',
            ),
          ),
        ),
      ),
      'filterList' => 
      array (
      ),
      'defaultFilterData' => 
      array (
      ),
      'boolFilterList' => 
      array (
      ),
    ),
    'EmailAccount' => 
    array (
      'controller' => 'controllers/email-account',
      'recordViews' => 
      array (
        'list' => 'views/email-account/record/list',
        'detail' => 'views/email-account/record/detail',
        'edit' => 'views/email-account/record/edit',
      ),
      'views' => 
      array (
        'list' => 'views/email-account/list',
      ),
      'searchPanelDisabled' => true,
      'formDependency' => 
      array (
        'storeSentEmails' => 
        array (
          'map' => 
          array (
            'true' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'sentFolder',
                ),
              ),
              1 => 
              array (
                'action' => 'setRequired',
                'fields' => 
                array (
                  0 => 'sentFolder',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'sentFolder',
              ),
            ),
            1 => 
            array (
              'action' => 'setNotRequired',
              'fields' => 
              array (
                0 => 'sentFolder',
              ),
            ),
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'filters' => 
        array (
          'select' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-edit-and-remove',
        ),
        'emails' => 
        array (
          'select' => false,
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/remove-only',
        ),
      ),
    ),
    'EmailFilter' => 
    array (
      'controller' => 'controllers/record',
      'modalViews' => 
      array (
        'edit' => 'views/email-filter/modals/edit',
      ),
      'recordViews' => 
      array (
        'detail' => 'views/email-filter/record/detail',
        'edit' => 'views/email-filter/record/edit',
        'editQuick' => 'views/email-filter/record/edit-small',
        'detailQuick' => 'views/email-filter/record/detail-small',
      ),
      'searchPanelDisabled' => false,
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Emails',
              'link' => '#Email',
              'style' => 'default',
              'aclScope' => 'Email',
            ),
          ),
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'EmailFolder' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'list' => 'views/email-folder/list',
      ),
      'recordViews' => 
      array (
        'list' => 'views/email-folder/record/list',
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Emails',
              'link' => '#Email',
              'style' => 'default',
              'aclScope' => 'Email',
            ),
          ),
        ),
      ),
      'searchPanelDisabled' => true,
    ),
    'EmailTemplate' => 
    array (
      'controller' => 'controllers/record',
      'recordViews' => 
      array (
        'edit' => 'views/email-template/record/edit',
        'detail' => 'views/email-template/record/detail',
        'editQuick' => 'views/email-template/record/edit-quick',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'information',
            'label' => 'Info',
            'view' => 'views/email-template/record/panels/information',
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'information',
            'label' => 'Info',
            'view' => 'views/email-template/record/panels/information',
          ),
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'filterList' => 
      array (
        0 => 'actual',
      ),
    ),
    'ExternalAccount' => 
    array (
      'controller' => 'controllers/external-account',
    ),
    'Import' => 
    array (
      'controller' => 'controllers/import',
      'recordViews' => 
      array (
        'list' => 'Import.Record.List',
        'detail' => 'Import.Record.Detail',
      ),
      'views' => 
      array (
        'list' => 'Import.List',
        'detail' => 'Import.Detail',
      ),
      'bottomPanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'imported',
            'label' => 'Imported',
            'view' => 'views/import/record/panels/imported',
          ),
          1 => 
          array (
            'name' => 'duplicates',
            'label' => 'Duplicates',
            'view' => 'views/import/record/panels/duplicates',
            'rowActionsView' => 'views/import/record/row-actions/duplicates',
          ),
          2 => 
          array (
            'name' => 'updated',
            'label' => 'Updated',
            'view' => 'views/import/record/panels/updated',
          ),
        ),
      ),
      'searchPanelDisabled' => true,
    ),
    'InboundEmail' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'views/inbound-email/record/detail',
        'edit' => 'views/inbound-email/record/edit',
        'list' => 'views/inbound-email/record/list',
      ),
      'formDependency' => 
      array (
        'createCase' => 
        array (
          'map' => 
          array (
            'true' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'caseDistribution',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'caseDistribution',
              ),
            ),
          ),
        ),
        'caseDistribution' => 
        array (
          'map' => 
          array (
            'Round-Robin' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'targetUserPosition',
                ),
              ),
            ),
            'Least-Busy' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'targetUserPosition',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'targetUserPosition',
              ),
            ),
          ),
        ),
        'reply' => 
        array (
          'map' => 
          array (
            'true' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'replyEmailTemplate',
                  1 => 'replyFromAddress',
                  2 => 'replyFromName',
                ),
              ),
              1 => 
              array (
                'action' => 'setRequired',
                'fields' => 
                array (
                  0 => 'replyEmailTemplate',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'replyEmailTemplate',
                1 => 'replyFromAddress',
                2 => 'replyFromName',
              ),
            ),
            1 => 
            array (
              'action' => 'setNotRequired',
              'fields' => 
              array (
                0 => 'replyEmailTemplate',
              ),
            ),
          ),
        ),
      ),
      'searchPanelDisabled' => true,
      'relationshipPanels' => 
      array (
        'filters' => 
        array (
          'select' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-edit-and-remove',
        ),
        'emails' => 
        array (
          'select' => false,
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/remove-only',
        ),
      ),
    ),
    'Job' => 
    array (
      'modalViews' => 
      array (
        'detail' => 'Admin.Job.Modals.Detail',
      ),
      'recordViews' => 
      array (
        'list' => 'Admin.Job.Record.List',
        'detailQuick' => 'Admin.Job.Record.DetailSmall',
      ),
    ),
    'Note' => 
    array (
      'collection' => 'collections/note',
      'recordViews' => 
      array (
        'edit' => 'views/note/record/edit',
        'editQuick' => 'views/note/record/edit',
      ),
      'modalViews' => 
      array (
        'edit' => 'views/note/modals/edit',
      ),
      'itemViews' => 
      array (
        'Post' => 'views/stream/notes/post',
      ),
    ),
    'Notification' => 
    array (
      'controller' => 'controllers/notification',
      'collection' => 'collections/note',
      'itemViews' => 
      array (
        'System' => 'views/notification/items/system',
      ),
    ),
    'PasswordChangeRequest' => 
    array (
      'controller' => 'controllers/password-change-request',
    ),
    'Portal' => 
    array (
      'controller' => 'controllers/record',
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
          'layout' => 'listSmall',
          'selectPrimaryFilterName' => 'activePortal',
        ),
      ),
      'searchPanelDisabled' => true,
    ),
    'PortalRole' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'views/portal-role/record/detail',
        'edit' => 'views/portal-role/record/edit',
        'editQuick' => 'views/portal-role/record/edit',
        'list' => 'views/portal-role/record/list',
      ),
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
        ),
      ),
      'views' => 
      array (
        'list' => 'views/portal-role/list',
      ),
    ),
    'Preferences' => 
    array (
      'recordViews' => 
      array (
        'edit' => 'views/preferences/record/edit',
      ),
      'views' => 
      array (
        'edit' => 'views/preferences/edit',
      ),
      'acl' => 'acl/preferences',
      'aclPortal' => 'acl-portal/preferences',
    ),
    'Role' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'views/role/record/detail',
        'edit' => 'views/role/record/edit',
        'editQuick' => 'views/role/record/edit',
        'list' => 'views/role/record/list',
      ),
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
        ),
        'teams' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
        ),
      ),
      'views' => 
      array (
        'list' => 'views/role/list',
      ),
    ),
    'ScheduledJob' => 
    array (
      'controller' => 'controllers/record',
      'relationshipPanels' => 
      array (
        'log' => 
        array (
          'readOnly' => true,
        ),
      ),
      'recordViews' => 
      array (
        'list' => 'views/scheduled-job/record/list',
        'detail' => 'views/scheduled-job/record/detail',
      ),
      'views' => 
      array (
        'list' => 'views/scheduled-job/list',
      ),
    ),
    'ScheduledJobLogRecord' => 
    array (
      'controller' => 'controllers/record',
    ),
    'Stream' => 
    array (
      'controller' => 'controllers/stream',
    ),
    'Team' => 
    array (
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
          'layout' => 'listForTeam',
          'selectPrimaryFilterName' => 'active',
        ),
      ),
      'recordViews' => 
      array (
        'detail' => 'views/team/record/detail',
        'edit' => 'views/team/record/edit',
        'list' => 'views/team/record/list',
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'Template' => 
    array (
      'controller' => 'controllers/record',
      'recordViews' => 
      array (
        'detail' => 'Template.Record.Detail',
      ),
      'formDependency' => 
      array (
        'printFooter' => 
        array (
          'map' => 
          array (
            'true' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'footer',
                  1 => 'footerPosition',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'footer',
                1 => 'footerPosition',
              ),
            ),
          ),
        ),
      ),
    ),
    'User' => 
    array (
      'views' => 
      array (
        'detail' => 'views/user/detail',
        'list' => 'views/user/list',
      ),
      'recordViews' => 
      array (
        'detail' => 'views/user/record/detail',
        'detailQuick' => 'views/user/record/detail-quick',
        'edit' => 'views/user/record/edit',
        'editQuick' => 'views/user/record/edit-quick',
        'list' => 'views/user/record/list',
      ),
      'filterList' => 
      array (
        0 => 'active',
        1 => 'activePortal',
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMyTeam',
      ),
      'selectDefaultFilters' => 
      array (
        'filter' => 'active',
      ),
    ),
    'Account' => 
    array (
      'controller' => 'controllers/record',
      'aclPortal' => 'crm:acl-portal/account',
      'views' => 
      array (
        'detail' => 'crm:views/account/detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'crm:views/record/panels/activities',
            'aclScope' => 'Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'crm:views/record/panels/history',
            'aclScope' => 'Activities',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'crm:views/record/panels/tasks',
            'aclScope' => 'Task',
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'contacts' => 
        array (
          'layout' => 'listForAccount',
        ),
        'opportunities' => 
        array (
          'layout' => 'listForAccount',
        ),
        'campaignLogRecords' => 
        array (
          'rowActionsView' => 'views/record/row-actions/empty',
          'select' => false,
          'create' => false,
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'recentlyCreated',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'additionalLayouts' => 
      array (
        'detailConvert' => 
        array (
          'type' => 'detail',
        ),
      ),
    ),
    'Calendar' => 
    array (
      'colors' => 
      array (
        'Meeting' => '#558BBD',
        'Call' => '#CF605D',
        'Task' => '#76BA4E',
      ),
      'scopeList' => 
      array (
        0 => 'Meeting',
        1 => 'Call',
        2 => 'Task',
      ),
      'allDayScopeList' => 
      array (
        0 => 'Task',
      ),
      'modeList' => 
      array (
        0 => 'month',
        1 => 'agendaWeek',
        2 => 'agendaDay',
        3 => 'timeline',
      ),
      'canceledStatusList' => 
      array (
        0 => 'Not Held',
        1 => 'Canceled',
      ),
      'completedStatusList' => 
      array (
        0 => 'Held',
        1 => 'Completed',
      ),
      'additionalColorList' => 
      array (
        0 => '#AB78AD',
        1 => '#CC9B45',
      ),
    ),
    'Call' => 
    array (
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/call',
      'views' => 
      array (
        'detail' => 'crm:views/call/detail',
      ),
      'recordViews' => 
      array (
        'list' => 'crm:views/call/record/list',
        'detail' => 'crm:views/call/record/detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
        'detailSmall' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
        'editSmall' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'planned',
        ),
        1 => 
        array (
          'name' => 'held',
          'style' => 'success',
        ),
        2 => 
        array (
          'name' => 'todays',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'Campaign' => 
    array (
      'controller' => 'controllers/record',
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Target Lists',
              'link' => '#TargetList',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'TargetList',
            ),
          ),
          'dropdown' => 
          array (
            0 => 
            array (
              'label' => 'Mass Emails',
              'link' => '#MassEmail',
              'acl' => 'read',
              'aclScope' => 'MassEmail',
            ),
            1 => 
            array (
              'label' => 'Email Templates',
              'link' => '#EmailTemplate',
              'acl' => 'read',
              'aclScope' => 'EmailTemplate',
            ),
          ),
        ),
      ),
      'recordViews' => 
      array (
        'detail' => 'crm:views/campaign/record/detail',
      ),
      'views' => 
      array (
        'detail' => 'crm:views/campaign/detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'statistics',
            'label' => 'Statistics',
            'view' => 'crm:views/campaign/record/panels/statistics',
            'hidden' => false,
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'campaignLogRecords' => 
        array (
          'view' => 'crm:views/campaign/record/panels/campaign-log-records',
          'layout' => 'listForCampaign',
          'rowActionsView' => 'views/record/row-actions/remove-only',
          'select' => false,
          'create' => false,
        ),
      ),
      'filterList' => 
      array (
        0 => 'active',
      ),
      'formDependency' => 
      array (
        'type' => 
        array (
          'map' => 
          array (
            'Email' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'targetLists',
                  1 => 'excludingTargetLists',
                ),
              ),
            ),
            'Newsletter' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'targetLists',
                  1 => 'excludingTargetLists',
                ),
              ),
            ),
            'Mail' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'fields' => 
                array (
                  0 => 'targetLists',
                  1 => 'excludingTargetLists',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'fields' => 
              array (
                0 => 'targetLists',
                1 => 'excludingTargetLists',
              ),
            ),
          ),
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'CampaignLogRecord' => 
    array (
      'acl' => 'crm:acl/campaign-tracking-url',
    ),
    'CampaignTrackingUrl' => 
    array (
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/campaign-tracking-url',
      'recordViews' => 
      array (
        'edit' => 'crm:views/campaign-tracking-url/record/edit',
        'editQuick' => 'crm:views/campaign-tracking-url/record/edit-small',
      ),
      'defaultSidePanel' => 
      array (
        'edit' => false,
        'editSmall' => false,
      ),
    ),
    'Case' => 
    array (
      'controller' => 'controllers/record',
      'recordViews' => 
      array (
        'detail' => 'crm:views/case/record/detail',
      ),
      'bottomPanels' => 
      array (
        'detail' => 
        array (
        ),
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'crm:views/case/record/panels/activities',
            'aclScope' => 'Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'crm:views/record/panels/history',
            'aclScope' => 'Activities',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'crm:views/record/panels/tasks',
            'aclScope' => 'Task',
          ),
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'open',
        ),
        1 => 
        array (
          'name' => 'closed',
          'style' => 'success',
        ),
      ),
      'relationshipPanels' => 
      array (
        'articles' => 
        array (
          'create' => false,
          'recordListView' => 'crm:views/knowledge-base-article/record/list-for-case',
          'rowActionsView' => 'crm:views/knowledge-base-article/record/row-actions/for-case',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'selectDefaultFilters' => 
      array (
        'filter' => 'open',
      ),
      'allowInternalNotes' => true,
    ),
    'Contact' => 
    array (
      'controller' => 'controllers/record',
      'aclPortal' => 'crm:acl-portal/contact',
      'views' => 
      array (
        'detail' => 'crm:views/contact/detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'crm:views/record/panels/activities',
            'aclScope' => 'Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'crm:views/record/panels/history',
            'aclScope' => 'Activities',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'crm:views/record/panels/tasks',
            'aclScope' => 'Task',
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'campaignLogRecords' => 
        array (
          'rowActionsView' => 'views/record/row-actions/empty',
          'select' => false,
          'create' => false,
        ),
        'opportunities' => 
        array (
          'layout' => 'listForAccount',
        ),
        'targetLists' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'additionalLayouts' => 
      array (
        'detailConvert' => 
        array (
          'type' => 'detail',
        ),
        'listForAccount' => 
        array (
          'type' => 'listSmall',
        ),
      ),
      'filterList' => 
      array (
        0 => 'portalUsers',
      ),
    ),
    'Document' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'list' => 'crm:views/document/list',
      ),
      'modalViews' => 
      array (
        'select' => 'crm:views/document/modals/select-records',
      ),
      'filterList' => 
      array (
        0 => 'active',
        1 => 'draft',
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'selectDefaultFilters' => 
      array (
        'filter' => 'active',
      ),
    ),
    'DocumentFolder' => 
    array (
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => 
      array (
        'listTree' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'List View',
              'link' => '#DocumentFolder/list',
              'acl' => 'read',
              'style' => 'default',
            ),
            1 => 
            array (
              'label' => 'Documents',
              'link' => '#Document',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'Document',
            ),
          ),
        ),
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Tree View',
              'link' => '#DocumentFolder',
              'acl' => 'read',
              'style' => 'default',
            ),
            1 => 
            array (
              'label' => 'Documents',
              'link' => '#Document',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'Document',
            ),
          ),
        ),
      ),
    ),
    'EmailQueueItem' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'list' => 'crm:views/email-queue-item/list',
      ),
      'recordViews' => 
      array (
        'list' => 'crm:views/email-queue-item/record/list',
      ),
    ),
    'KnowledgeBaseArticle' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'list' => 'crm:views/knowledge-base-article/list',
      ),
      'recordViews' => 
      array (
        'editQuick' => 'crm:views/knowledge-base-article/record/edit-quick',
        'detailQuick' => 'crm:views/knowledge-base-article/record/detail-quick',
        'detail' => 'crm:views/knowledge-base-article/record/detail',
        'list' => 'crm:views/knowledge-base-article/record/list',
      ),
      'modalViews' => 
      array (
        'select' => 'crm:views/knowledge-base-article/modals/select-records',
      ),
      'filterList' => 
      array (
        0 => 'published',
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'relationshipPanels' => 
      array (
        'cases' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-view-and-unlink',
        ),
      ),
    ),
    'KnowledgeBaseCategory' => 
    array (
      'controller' => 'controllers/record-tree',
      'collection' => 'collections/tree',
      'menu' => 
      array (
        'listTree' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'List View',
              'link' => '#KnowledgeBaseCategory/list',
              'acl' => 'read',
              'style' => 'default',
            ),
            1 => 
            array (
              'label' => 'Articles',
              'link' => '#KnowledgeBaseArticle',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'KnowledgeBaseArticle',
            ),
          ),
        ),
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Tree View',
              'link' => '#KnowledgeBaseCategory',
              'acl' => 'read',
              'style' => 'default',
            ),
            1 => 
            array (
              'label' => 'Articles',
              'link' => '#KnowledgeBaseArticle',
              'acl' => 'read',
              'style' => 'default',
              'aclScope' => 'KnowledgeBaseArticle',
            ),
          ),
        ),
      ),
    ),
    'Lead' => 
    array (
      'controller' => 'crm:controllers/lead',
      'views' => 
      array (
        'detail' => 'Crm:Lead.Detail',
      ),
      'recordViews' => 
      array (
        'detail' => 'Crm:Lead.Record.Detail',
      ),
      'formDependency' => 
      array (
        'status' => 
        array (
          'map' => 
          array (
            'Converted' => 
            array (
              0 => 
              array (
                'action' => 'show',
                'panels' => 
                array (
                  0 => 'convertedTo',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'hide',
              'panels' => 
              array (
                0 => 'convertedTo',
              ),
            ),
          ),
        ),
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
          ),
          1 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'crm:views/record/panels/activities',
            'aclScope' => 'Activities',
          ),
          2 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'crm:views/record/panels/history',
            'aclScope' => 'Activities',
          ),
          3 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'crm:views/record/panels/tasks',
            'aclScope' => 'Task',
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
          ),
        ),
        'detailSmall' => 
        array (
          0 => 
          array (
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
          ),
        ),
        'editSmall' => 
        array (
          0 => 
          array (
            'name' => 'convertedTo',
            'label' => 'Converted To',
            'view' => 'crm:views/lead/record/panels/converted-to',
            'notRefreshable' => true,
            'hidden' => true,
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'campaignLogRecords' => 
        array (
          'rowActionsView' => 'Record.RowActions.Empty',
          'select' => false,
          'create' => false,
        ),
        'targetLists' => 
        array (
          'create' => false,
          'rowActionsView' => 'views/record/row-actions/relationship-unlink-only',
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'actual',
        ),
        1 => 
        array (
          'name' => 'converted',
          'style' => 'success',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'MassEmail' => 
    array (
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/mass-email',
      'recordViews' => 
      array (
        'detail' => 'crm:views/mass-email/record/detail',
        'edit' => 'crm:views/mass-email/record/edit',
        'editQuick' => 'crm:views/mass-email/record/edit-small',
      ),
      'views' => 
      array (
        'detail' => 'crm:views/mass-email/detail',
      ),
      'defaultSidePanel' => 
      array (
        'edit' => false,
        'editSmall' => false,
      ),
      'formDependency' => 
      array (
        'status' => 
        array (
          'map' => 
          array (
            'Complete' => 
            array (
              0 => 
              array (
                'action' => 'setReadOnly',
                'fields' => 
                array (
                  0 => 'status',
                ),
              ),
            ),
            'In Process' => 
            array (
              0 => 
              array (
                'action' => 'setReadOnly',
                'fields' => 
                array (
                  0 => 'status',
                ),
              ),
            ),
            'Failed' => 
            array (
              0 => 
              array (
                'action' => 'setReadOnly',
                'fields' => 
                array (
                  0 => 'status',
                ),
              ),
            ),
          ),
          'default' => 
          array (
            0 => 
            array (
              'action' => 'setNotReadOnly',
              'fields' => 
              array (
                0 => 'status',
              ),
            ),
          ),
        ),
      ),
    ),
    'Meeting' => 
    array (
      'controller' => 'controllers/record',
      'acl' => 'crm:acl/meeting',
      'views' => 
      array (
        'detail' => 'crm:views/meeting/detail',
      ),
      'recordViews' => 
      array (
        'list' => 'crm:views/meeting/record/list',
        'detail' => 'crm:views/meeting/record/detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'options' => 
            array (
              'fieldList' => 
              array (
                0 => 'users',
                1 => 'contacts',
                2 => 'leads',
              ),
            ),
            'sticked' => true,
          ),
        ),
        'detailSmall' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
        'editSmall' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'crm:views/meeting/record/panels/attendees',
            'sticked' => true,
          ),
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'planned',
        ),
        1 => 
        array (
          'name' => 'held',
          'style' => 'success',
        ),
        2 => 
        array (
          'name' => 'todays',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'Opportunity' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'detail' => 'Crm:Opportunity.Detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'crm:views/record/panels/activities',
            'aclScope' => 'Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'crm:views/record/panels/history',
            'aclScope' => 'Activities',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'crm:views/record/panels/tasks',
            'aclScope' => 'Task',
          ),
        ),
      ),
      'filterList' => 
      array (
        0 => 
        array (
          'name' => 'open',
        ),
        1 => 
        array (
          'name' => 'won',
          'style' => 'success',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'additionalLayouts' => 
      array (
        'detailConvert' => 
        array (
          'type' => 'detail',
        ),
        'listForAccount' => 
        array (
          'type' => 'listSmall',
        ),
      ),
    ),
    'Target' => 
    array (
      'controller' => 'controllers/record',
      'views' => 
      array (
        'detail' => 'Crm:Target.Detail',
      ),
      'menu' => 
      array (
        'detail' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Convert to Lead',
              'action' => 'convertToLead',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
    'TargetList' => 
    array (
      'controller' => 'controllers/record',
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'optedOut',
            'label' => 'Opted Out',
            'view' => 'crm:views/target-list/record/panels/opted-out',
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'contacts' => 
        array (
          'actionList' => 
          array (
            0 => 
            array (
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => 
              array (
                'link' => 'contacts',
              ),
            ),
          ),
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
        ),
        'leads' => 
        array (
          'actionList' => 
          array (
            0 => 
            array (
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => 
              array (
                'link' => 'leads',
              ),
            ),
          ),
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
        ),
        'accounts' => 
        array (
          'actionList' => 
          array (
            0 => 
            array (
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => 
              array (
                'link' => 'accounts',
              ),
            ),
          ),
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
        ),
        'users' => 
        array (
          'create' => false,
          'actionList' => 
          array (
            0 => 
            array (
              'label' => 'Unlink All',
              'action' => 'unlinkAllRelated',
              'acl' => 'edit',
              'data' => 
              array (
                'link' => 'users',
              ),
            ),
          ),
          'rowActionsView' => 'crm:views/target-list/record/row-actions/default',
          'view' => 'crm:views/target-list/record/panels/relationship',
        ),
      ),
    ),
    'Task' => 
    array (
      'controller' => 'controllers/record',
      'recordViews' => 
      array (
        'list' => 'crm:views/task/record/list',
        'detail' => 'crm:views/task/record/detail',
      ),
      'views' => 
      array (
        'list' => 'crm:views/task/list',
        'detail' => 'crm:views/task/detail',
      ),
      'dynamicLogic' => 
      array (
        'fields' => 
        array (
          'dateCompleted' => 
          array (
            'visible' => 
            array (
              'conditionGroup' => 
              array (
                0 => 
                array (
                  'type' => 'equals',
                  'attribute' => 'status',
                  'value' => 'Completed',
                ),
              ),
            ),
          ),
        ),
      ),
      'filterList' => 
      array (
        0 => 'actual',
        1 => 
        array (
          'name' => 'completed',
          'style' => 'success',
        ),
        2 => 
        array (
          'name' => 'todays',
        ),
        3 => 
        array (
          'name' => 'overdue',
          'style' => 'danger',
        ),
      ),
      'boolFilterList' => 
      array (
        0 => 'onlyMy',
      ),
    ),
  ),
  'dashlets' => 
  array (
    'Emails' => 
    array (
      'view' => 'views/dashlets/emails',
      'aclScope' => 'Email',
      'entityType' => 'Email',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
              7 => 50,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'dateSent',
          'asc' => false,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'subject',
                  'link' => true,
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateSent',
                  'view' => 'views/fields/datetime-short',
                ),
                1 => 
                array (
                  'name' => 'personStringData',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'inbox',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Stream' => 
    array (
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
              7 => 50,
            ),
          ),
        ),
        'defaults' => 
        array (
          'displayRecords' => 10,
          'autorefreshInterval' => 0.5,
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Activities' => 
    array (
      'view' => 'crm:views/dashlets/activities',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
              7 => 50,
            ),
          ),
        ),
        'defaults' => 
        array (
          'displayRecords' => 5,
          'autorefreshInterval' => 0.5,
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Calendar' => 
    array (
      'view' => 'crm:views/dashlets/calendar',
      'aclScope' => 'Calendar',
      'options' => 
      array (
        'view' => 'crm:views/dashlets/options/calendar',
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'enabledScopeList' => 
          array (
            'type' => 'multiEnum',
            'options' => 
            array (
              0 => 'Meeting',
              1 => 'Call',
              2 => 'Task',
            ),
            'translation' => 'Global.scopeNamesPlural',
            'required' => true,
          ),
          'mode' => 
          array (
            'type' => 'enum',
            'options' => 
            array (
              0 => 'basicWeek',
              1 => 'agendaWeek',
              2 => 'timeline',
              3 => 'month',
              4 => 'basicDay',
              5 => 'agendaDay',
            ),
          ),
          'users' => 
          array (
            'type' => 'linkMultiple',
            'entity' => 'User',
            'view' => 'views/fields/assigned-users',
            'sortable' => true,
          ),
        ),
        'defaults' => 
        array (
          'autorefreshInterval' => 0.5,
          'mode' => 'basicWeek',
          'enabledScopeList' => 
          array (
            0 => 'Meeting',
            1 => 'Call',
            2 => 'Task',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'mode',
                ),
                1 => 
                array (
                  'name' => 'enabledScopeList',
                ),
              ),
              2 => 
              array (
                0 => 
                array (
                  'name' => 'users',
                ),
                1 => false,
              ),
            ),
          ),
        ),
      ),
    ),
    'Calls' => 
    array (
      'view' => 'crm:views/dashlets/calls',
      'aclScope' => 'Call',
      'entityType' => 'Call',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'dateStart',
          'asc' => true,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateStart',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'planned',
            'advanced' => 
            array (
              1 => 
              array (
                'type' => 'or',
                'value' => 
                array (
                  1 => 
                  array (
                    'type' => 'today',
                    'field' => 'dateStart',
                    'dateTime' => true,
                  ),
                  2 => 
                  array (
                    'type' => 'future',
                    'field' => 'dateEnd',
                    'dateTime' => true,
                  ),
                ),
              ),
            ),
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Cases' => 
    array (
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Case',
      'entityType' => 'Case',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'createdAt',
          'asc' => false,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'number',
                ),
                1 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
                2 => 
                array (
                  'name' => 'type',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'status',
                ),
                1 => 
                array (
                  'name' => 'priority',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'open',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Leads' => 
    array (
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Lead',
      'entityType' => 'Lead',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'createdAt',
          'asc' => false,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
                1 => 
                array (
                  'name' => 'addressCity',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'status',
                ),
                1 => 
                array (
                  'name' => 'source',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'actual',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Meetings' => 
    array (
      'view' => 'crm:views/dashlets/meetings',
      'aclScope' => 'Meeting',
      'entityType' => 'Meeting',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'dateStart',
          'asc' => true,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateStart',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'planned',
            'advanced' => 
            array (
              1 => 
              array (
                'type' => 'or',
                'value' => 
                array (
                  1 => 
                  array (
                    'type' => 'today',
                    'field' => 'dateStart',
                    'dateTime' => true,
                  ),
                  2 => 
                  array (
                    'type' => 'future',
                    'field' => 'dateEnd',
                    'dateTime' => true,
                  ),
                ),
              ),
            ),
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Opportunities' => 
    array (
      'view' => 'views/dashlets/abstract/record-list',
      'aclScope' => 'Opportunity',
      'entityType' => 'Opportunity',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'closeDate',
          'asc' => true,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'stage',
                ),
                1 => 
                array (
                  'name' => 'amount',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'open',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'OpportunitiesByLeadSource' => 
    array (
      'view' => 'crm:views/dashlets/opportunities-by-lead-source',
      'aclScope' => 'Opportunity',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'dateFrom' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
          'dateTo' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateFrom',
                ),
                1 => 
                array (
                  'name' => 'dateTo',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'OpportunitiesByStage' => 
    array (
      'view' => 'crm:views/dashlets/opportunities-by-stage',
      'aclScope' => 'Opportunity',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'dateFrom' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
          'dateTo' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateFrom',
                ),
                1 => 
                array (
                  'name' => 'dateTo',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'SalesByMonth' => 
    array (
      'view' => 'crm:views/dashlets/sales-by-month',
      'aclScope' => 'Opportunity',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'dateFrom' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
          'dateTo' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateFrom',
                ),
                1 => 
                array (
                  'name' => 'dateTo',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'SalesPipeline' => 
    array (
      'view' => 'crm:views/dashlets/sales-pipeline',
      'aclScope' => 'Opportunity',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'dateFrom' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
          'dateTo' => 
          array (
            'type' => 'date',
            'required' => true,
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'dateFrom',
                ),
                1 => 
                array (
                  'name' => 'dateTo',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
    'Tasks' => 
    array (
      'view' => 'crm:views/dashlets/tasks',
      'aclScope' => 'Task',
      'entityType' => 'Task',
      'options' => 
      array (
        'fields' => 
        array (
          'title' => 
          array (
            'type' => 'varchar',
            'required' => true,
          ),
          'autorefreshInterval' => 
          array (
            'type' => 'enumFloat',
            'options' => 
            array (
              0 => 0,
              1 => 0.5,
              2 => 1,
              3 => 2,
              4 => 5,
              5 => 10,
            ),
          ),
          'displayRecords' => 
          array (
            'type' => 'enumInt',
            'options' => 
            array (
              0 => 3,
              1 => 4,
              2 => 5,
              3 => 10,
              4 => 15,
              5 => 20,
              6 => 30,
            ),
          ),
        ),
        'defaults' => 
        array (
          'sortBy' => 'dateEnd',
          'asc' => true,
          'displayRecords' => 5,
          'expandedLayout' => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'name',
                  'link' => true,
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'status',
                ),
                1 => 
                array (
                  'name' => 'dateEnd',
                ),
              ),
            ),
          ),
          'searchData' => 
          array (
            'bool' => 
            array (
              'onlyMy' => true,
            ),
            'primary' => 'actualNotDeferred',
          ),
        ),
        'layout' => 
        array (
          0 => 
          array (
            'rows' => 
            array (
              0 => 
              array (
                0 => 
                array (
                  'name' => 'title',
                ),
              ),
              1 => 
              array (
                0 => 
                array (
                  'name' => 'displayRecords',
                ),
                1 => 
                array (
                  'name' => 'autorefreshInterval',
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'entityDefs' => 
  array (
    'Attachment' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'type' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'size' => 
        array (
          'type' => 'int',
          'min' => 0,
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'related' => 
        array (
          'type' => 'linkParent',
          'noLoad' => true,
        ),
        'sourceId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 36,
          'readOnly' => true,
          'disabled' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'contents' => 
        array (
          'type' => 'text',
          'notStorable' => true,
        ),
        'role' => 
        array (
          'type' => 'varchar',
          'maxLength' => 36,
        ),
        'global' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'attachments',
        ),
        'related' => 
        array (
          'type' => 'belongsToParent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'parent' => 
        array (
          'columns' => 
          array (
            0 => 'parentType',
            1 => 'parentId',
          ),
        ),
      ),
      'sources' => 
      array (
        'Document' => 
        array (
          'insertModalView' => '',
        ),
      ),
    ),
    'AuthToken' => 
    array (
      'fields' => 
      array (
        'token' => 
        array (
          'type' => 'varchar',
          'maxLength' => '36',
          'index' => true,
        ),
        'hash' => 
        array (
          'type' => 'varchar',
          'maxLength' => 150,
          'index' => true,
        ),
        'userId' => 
        array (
          'type' => 'varchar',
          'maxLength' => '36',
        ),
        'user' => 
        array (
          'type' => 'link',
        ),
        'portal' => 
        array (
          'type' => 'link',
        ),
        'ipAddress' => 
        array (
          'type' => 'varchar',
          'maxLength' => '36',
        ),
        'lastAccess' => 
        array (
          'type' => 'datetime',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'user' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'portal' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Portal',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'lastAccess',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'token' => 
        array (
          'columns' => 
          array (
            0 => 'token',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'Currency' => 
    array (
      'fields' => 
      array (
        'rate' => 
        array (
          'type' => 'float',
        ),
      ),
    ),
    'Email' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'subject' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'notStorable' => true,
          'view' => 'views/email/fields/subject',
          'disabled' => true,
          'trim' => true,
        ),
        'fromName' => 
        array (
          'type' => 'varchar',
        ),
        'fromString' => 
        array (
          'type' => 'varchar',
        ),
        'replyToString' => 
        array (
          'type' => 'varchar',
        ),
        'from' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'required' => true,
          'view' => 'views/email/fields/from-address-varchar',
        ),
        'to' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'required' => true,
          'view' => 'views/email/fields/email-address-varchar',
        ),
        'cc' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'view' => 'views/email/fields/email-address-varchar',
        ),
        'bcc' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'view' => 'views/email/fields/email-address-varchar',
        ),
        'replyTo' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'view' => 'views/email/fields/email-address-varchar',
        ),
        'personStringData' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'isRead' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'default' => true,
          'readOnly' => true,
        ),
        'isNotRead' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
        ),
        'isReplied' => 
        array (
          'type' => 'bool',
          'readOnly' => true,
        ),
        'isNotReplied' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'layoutListDisabled' => true,
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
        ),
        'isImportant' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
        ),
        'inTrash' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
        ),
        'folderId' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'default' => false,
        ),
        'isUsers' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'default' => false,
        ),
        'folder' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'readOnly' => true,
        ),
        'nameHash' => 
        array (
          'type' => 'text',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'typeHash' => 
        array (
          'type' => 'text',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'idHash' => 
        array (
          'type' => 'text',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'messageId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 255,
          'readOnly' => true,
        ),
        'messageIdInternal' => 
        array (
          'type' => 'varchar',
          'maxLength' => 300,
          'readOnly' => true,
        ),
        'emailAddress' => 
        array (
          'type' => 'base',
          'notStorable' => true,
          'view' => 'views/email/fields/email-address',
        ),
        'fromEmailAddress' => 
        array (
          'type' => 'link',
          'view' => 'views/email/fields/from-email-address',
        ),
        'toEmailAddresses' => 
        array (
          'type' => 'linkMultiple',
        ),
        'ccEmailAddresses' => 
        array (
          'type' => 'linkMultiple',
        ),
        'bodyPlain' => 
        array (
          'type' => 'text',
          'readOnly' => true,
          'seeMoreDisabled' => true,
        ),
        'body' => 
        array (
          'type' => 'wysiwyg',
          'seeMoreDisabled' => true,
        ),
        'isHtml' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Draft',
            1 => 'Sending',
            2 => 'Sent',
            3 => 'Archived',
            4 => 'Failed',
          ),
          'readOnly' => true,
          'default' => 'Archived',
        ),
        'attachments' => 
        array (
          'type' => 'attachmentMultiple',
          'sourceList' => 
          array (
            0 => 'Document',
          ),
        ),
        'hasAttachment' => 
        array (
          'type' => 'bool',
          'readOnly' => true,
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
          'entityList' => 
          array (
            0 => 'Account',
            1 => 'Lead',
            2 => 'Opportunity',
            3 => 'Case',
          ),
        ),
        'dateSent' => 
        array (
          'type' => 'datetime',
        ),
        'deliveryDate' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'sentBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'noLoad' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => false,
          'view' => 'views/fields/assigned-user',
        ),
        'replied' => 
        array (
          'type' => 'link',
          'noJoin' => true,
          'readOnly' => true,
        ),
        'replies' => 
        array (
          'type' => 'linkMultiple',
          'readOnly' => true,
        ),
        'isSystem' => 
        array (
          'type' => 'bool',
          'default' => false,
          'readOnly' => true,
        ),
        'isJustSent' => 
        array (
          'type' => 'bool',
          'default' => false,
          'disabled' => true,
          'notStorable' => true,
        ),
        'isBeingImported' => 
        array (
          'type' => 'bool',
          'disabled' => true,
          'notStorable' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'noLoad' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
          'columns' => 
          array (
            'inTrash' => 'inTrash',
            'folderId' => 'folderId',
          ),
        ),
        'assignedUsers' => 
        array (
          'type' => 'linkMultiple',
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'readOnly' => true,
        ),
        'inboundEmails' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'noLoad' => true,
        ),
        'emailAccounts' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'noLoad' => true,
        ),
        'account' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
        ),
        'assignedUsers' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'relationName' => 'entityUser',
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'emails',
          'additionalColumns' => 
          array (
            'isRead' => 
            array (
              'type' => 'bool',
              'default' => false,
            ),
            'isImportant' => 
            array (
              'type' => 'bool',
              'default' => false,
            ),
            'inTrash' => 
            array (
              'type' => 'bool',
              'default' => false,
            ),
            'folderId' => 
            array (
              'type' => 'varchar',
              'default' => NULL,
              'maxLength' => 24,
            ),
          ),
        ),
        'sentBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'attachments' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'foreign' => 'parent',
          'relationName' => 'attachments',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entityList' => 
          array (
          ),
          'foreign' => 'emails',
        ),
        'replied' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Email',
          'foreign' => 'replies',
        ),
        'replies' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'replied',
        ),
        'fromEmailAddress' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailAddress',
        ),
        'toEmailAddresses' => 
        array (
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => 
          array (
            'addressType' => 'to',
          ),
          'additionalColumns' => 
          array (
            'addressType' => 
            array (
              'type' => 'varchar',
              'len' => '4',
            ),
          ),
        ),
        'ccEmailAddresses' => 
        array (
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => 
          array (
            'addressType' => 'cc',
          ),
          'additionalColumns' => 
          array (
            'addressType' => 
            array (
              'type' => 'varchar',
              'len' => '4',
            ),
          ),
        ),
        'bccEmailAddresses' => 
        array (
          'type' => 'hasMany',
          'entity' => 'EmailAddress',
          'relationName' => 'emailEmailAddress',
          'conditions' => 
          array (
            'addressType' => 'bcc',
          ),
          'additionalColumns' => 
          array (
            'addressType' => 
            array (
              'type' => 'varchar',
              'len' => '4',
            ),
          ),
        ),
        'inboundEmails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'InboundEmail',
          'foreign' => 'emails',
        ),
        'emailAccounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'EmailAccount',
          'foreign' => 'emails',
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'dateSent',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'dateSentAssignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'dateSent',
            1 => 'assignedUserId',
          ),
        ),
        'dateSent' => 
        array (
          'columns' => 
          array (
            0 => 'dateSent',
            1 => 'deleted',
          ),
        ),
        'dateSentStatus' => 
        array (
          'columns' => 
          array (
            0 => 'dateSent',
            1 => 'status',
            2 => 'deleted',
          ),
        ),
      ),
    ),
    'EmailAccount' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'emailAddress' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'trim' => true,
          'view' => 'views/email-account/fields/email-address',
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Active',
            1 => 'Inactive',
          ),
        ),
        'host' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'port' => 
        array (
          'type' => 'varchar',
          'default' => '143',
          'required' => true,
        ),
        'ssl' => 
        array (
          'type' => 'bool',
        ),
        'username' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'password' => 
        array (
          'type' => 'password',
        ),
        'monitoredFolders' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'default' => 'INBOX',
          'view' => 'views/email-account/fields/folders',
          'tooltip' => true,
        ),
        'sentFolder' => 
        array (
          'type' => 'varchar',
          'view' => 'views/email-account/fields/folder',
        ),
        'storeSentEmails' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'keepFetchedEmailsUnread' => 
        array (
          'type' => 'bool',
        ),
        'fetchSince' => 
        array (
          'type' => 'date',
          'required' => true,
        ),
        'fetchData' => 
        array (
          'type' => 'jsonObject',
          'readOnly' => true,
        ),
        'emailFolder' => 
        array (
          'type' => 'link',
          'view' => 'views/email-account/fields/email-folder',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'useSmtp' => 
        array (
          'type' => 'bool',
        ),
        'smtpHost' => 
        array (
          'type' => 'varchar',
          'trim' => true,
        ),
        'smtpPort' => 
        array (
          'type' => 'int',
          'min' => 0,
          'max' => 9999,
          'default' => 25,
        ),
        'smtpAuth' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'smtpSecurity' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'SSL',
            2 => 'TLS',
          ),
        ),
        'smtpUsername' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'smtpPassword' => 
        array (
          'type' => 'password',
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'filters' => 
        array (
          'type' => 'hasChildren',
          'foreign' => 'parent',
          'entity' => 'EmailFilter',
        ),
        'emails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'emailAccounts',
        ),
        'emailFolder' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailFolder',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'EmailAddress' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'lower' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'index' => true,
        ),
        'invalid' => 
        array (
          'type' => 'bool',
        ),
        'optOut' => 
        array (
          'type' => 'bool',
        ),
      ),
      'links' => 
      array (
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'EmailFilter' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'tooltip' => true,
          'trim' => true,
        ),
        'from' => 
        array (
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true,
          'trim' => true,
        ),
        'to' => 
        array (
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true,
          'trim' => true,
        ),
        'subject' => 
        array (
          'type' => 'varchar',
          'maxLength' => 255,
          'tooltip' => true,
        ),
        'bodyContains' => 
        array (
          'type' => 'array',
          'tooltip' => true,
        ),
        'isGlobal' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'action' => 
        array (
          'type' => 'enum',
          'default' => 'Skip',
          'options' => 
          array (
            0 => 'Skip',
            1 => 'Move to Folder',
          ),
          'view' => 'views/email-filter/fields/action',
        ),
        'emailFolder' => 
        array (
          'type' => 'link',
          'view' => 'views/email-filter/fields/email-folder',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entityList' => 
          array (
            0 => 'User',
            1 => 'EmailAccount',
            2 => 'InboundEmail',
          ),
        ),
        'emailFolder' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailFolder',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'EmailFolder' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 64,
          'trim' => true,
        ),
        'order' => 
        array (
          'type' => 'int',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'skipNotifications' => 
        array (
          'type' => 'bool',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'order',
        'asc' => true,
      ),
    ),
    'EmailTemplate' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'subject' => 
        array (
          'type' => 'varchar',
        ),
        'body' => 
        array (
          'type' => 'text',
          'view' => 'views/fields/wysiwyg',
        ),
        'isHtml' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'oneOff' => 
        array (
          'type' => 'bool',
          'default' => false,
          'tooltip' => true,
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/attachment-multiple',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
      ),
      'links' => 
      array (
        'attachments' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'foreign' => 'parent',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'Extension' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'version' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 50,
        ),
        'fileList' => 
        array (
          'type' => 'jsonArray',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'isInstalled' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'ExternalAccount' => 
    array (
      'fields' => 
      array (
        'id' => 
        array (
          'maxLength' => 64,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'enabled' => 
        array (
          'type' => 'bool',
        ),
      ),
    ),
    'Import' => 
    array (
      'fields' => 
      array (
        'entityType' => 
        array (
          'type' => 'enum',
          'translation' => 'Global.scopeNames',
          'required' => true,
        ),
        'file' => 
        array (
          'type' => 'file',
          'required' => true,
        ),
        'importedCount' => 
        array (
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
        ),
        'duplicateCount' => 
        array (
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
        ),
        'updatedCount' => 
        array (
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'InboundEmail' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'emailAddress' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
          'view' => 'views/inbound-email/fields/email-address',
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Active',
            1 => 'Inactive',
          ),
        ),
        'host' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'port' => 
        array (
          'type' => 'varchar',
          'default' => '143',
          'required' => true,
        ),
        'ssl' => 
        array (
          'type' => 'bool',
        ),
        'username' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'password' => 
        array (
          'type' => 'password',
        ),
        'monitoredFolders' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'default' => 'INBOX',
          'view' => 'views/inbound-email/fields/folders',
        ),
        'fetchSince' => 
        array (
          'type' => 'date',
          'required' => true,
        ),
        'fetchData' => 
        array (
          'type' => 'jsonObject',
          'readOnly' => true,
        ),
        'assignToUser' => 
        array (
          'type' => 'link',
          'tooltip' => true,
        ),
        'team' => 
        array (
          'type' => 'link',
          'tooltip' => true,
        ),
        'addAllTeamUsers' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'createCase' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'caseDistribution' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Direct-Assignment',
            2 => 'Round-Robin',
            3 => 'Least-Busy',
          ),
          'default' => 'Direct-Assignment',
          'tooltip' => true,
        ),
        'targetUserPosition' => 
        array (
          'type' => 'enum',
          'view' => 'views/inbound-email/fields/target-user-position',
          'tooltip' => true,
        ),
        'reply' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'replyEmailTemplate' => 
        array (
          'type' => 'link',
        ),
        'replyFromAddress' => 
        array (
          'type' => 'varchar',
        ),
        'replyToAddress' => 
        array (
          'type' => 'varchar',
          'tooltip' => true,
          'required' => true,
        ),
        'replyFromName' => 
        array (
          'type' => 'varchar',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignToUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'team' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Team',
        ),
        'replyEmailTemplate' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailTemplate',
        ),
        'filters' => 
        array (
          'type' => 'hasChildren',
          'foreign' => 'parent',
          'entity' => 'EmailFilter',
        ),
        'emails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'inboundEmails',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'Integration' => 
    array (
      'fields' => 
      array (
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'enabled' => 
        array (
          'type' => 'bool',
        ),
      ),
    ),
    'Job' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/admin/job/fields/name',
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Pending',
            1 => 'Running',
            2 => 'Success',
            3 => 'Failed',
          ),
          'default' => 'Pending',
        ),
        'executeTime' => 
        array (
          'type' => 'datetime',
          'required' => true,
        ),
        'serviceName' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
        ),
        'method' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 100,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'scheduledJob' => 
        array (
          'type' => 'link',
        ),
        'attempts' => 
        array (
          'type' => 'int',
        ),
        'targetId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 48,
        ),
        'targetType' => 
        array (
          'type' => 'varchar',
          'maxLength' => 64,
        ),
        'failedAttempts' => 
        array (
          'type' => 'int',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'scheduledJob' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'ScheduledJob',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'executeTime' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'executeTime',
          ),
        ),
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'NextNumber' => 
    array (
      'fields' => 
      array (
        'entityType' => 
        array (
          'type' => 'varchar',
          'index' => true,
        ),
        'fieldName' => 
        array (
          'type' => 'varchar',
        ),
        'value' => 
        array (
          'type' => 'int',
          'default' => 1,
        ),
      ),
    ),
    'Note' => 
    array (
      'fields' => 
      array (
        'post' => 
        array (
          'type' => 'text',
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
          'readOnly' => true,
        ),
        'type' => 
        array (
          'type' => 'varchar',
          'readOnly' => true,
        ),
        'targetType' => 
        array (
          'type' => 'varchar',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
          'readOnly' => true,
        ),
        'related' => 
        array (
          'type' => 'linkParent',
          'readOnly' => true,
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/stream/fields/attachment-multiple',
        ),
        'number' => 
        array (
          'type' => 'autoincrement',
          'index' => true,
          'readOnly' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'noLoad' => true,
        ),
        'portals' => 
        array (
          'type' => 'linkMultiple',
          'noLoad' => true,
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'noLoad' => true,
        ),
        'isGlobal' => 
        array (
          'type' => 'bool',
        ),
        'createdByGender' => 
        array (
          'type' => 'foreign',
          'link' => 'createdBy',
          'field' => 'gender',
        ),
        'notifiedUserIdList' => 
        array (
          'type' => 'jsonArray',
          'notStorable' => true,
        ),
        'isInternal' => 
        array (
          'type' => 'bool',
        ),
        'isToSelf' => 
        array (
          'type' => 'bool',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'attachments' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'relationName' => 'attachments',
          'foreign' => 'parent',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'notes',
        ),
        'superParent' => 
        array (
          'type' => 'belongsToParent',
        ),
        'related' => 
        array (
          'type' => 'belongsToParent',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'notes',
        ),
        'portals' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'notes',
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'notes',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'number',
        'asc' => false,
      ),
      'streamRelated' => 
      array (
        'Account' => 
        array (
          0 => 'meetings',
          1 => 'calls',
          2 => 'tasks',
        ),
        'Contact' => 
        array (
          0 => 'meetings',
          1 => 'calls',
          2 => 'tasks',
        ),
        'Lead' => 
        array (
          0 => 'meetings',
          1 => 'calls',
          2 => 'tasks',
        ),
        'Opportunity' => 
        array (
          0 => 'meetings',
          1 => 'calls',
          2 => 'tasks',
        ),
        'Case' => 
        array (
          0 => 'meetings',
          1 => 'calls',
          2 => 'tasks',
        ),
      ),
      'statusStyles' => 
      array (
        'Lead' => 
        array (
          'New' => 'primary',
          'Assigned' => 'primary',
          'In Process' => 'primary',
          'Converted' => 'success',
          'Recycled' => 'danger',
          'Dead' => 'danger',
        ),
        'Case' => 
        array (
          'New' => 'primary',
          'Assigned' => 'primary',
          'Pending' => 'default',
          'Closed' => 'success',
          'Rejected' => 'danger',
          'Duplicate' => 'danger',
        ),
        'Opportunity' => 
        array (
          'Prospecting' => 'primary',
          'Qualification' => 'primary',
          'Needs Analysis' => 'primary',
          'Value Proposition' => 'primary',
          'Id. Decision Makers' => 'primary',
          'Perception Analysis' => 'primary',
          'Proposal/Price Quote' => 'primary',
          'Negotiation/Review' => 'primary',
          'Closed Won' => 'success',
          'Closed Lost' => 'danger',
        ),
        'Task' => 
        array (
          'Completed' => 'success',
          'Started' => 'primary',
          'Canceled' => 'danger',
        ),
        'Meeting' => 
        array (
          'Held' => 'success',
        ),
        'Call' => 
        array (
          'Held' => 'success',
        ),
      ),
      'statusFields' => 
      array (
        'Lead' => 'status',
        'Case' => 'status',
        'Opportunity' => 'stage',
        'Task' => 'status',
        'Meeting' => 'status',
        'Call' => 'status',
        'Campaign' => 'status',
      ),
      'indexes' => 
      array (
        'createdAt' => 
        array (
          'type' => 'index',
          'columns' => 
          array (
            0 => 'createdAt',
          ),
        ),
        'parent' => 
        array (
          'type' => 'index',
          'columns' => 
          array (
            0 => 'parentId',
            1 => 'parentType',
          ),
        ),
        'parentAndSuperParent' => 
        array (
          'type' => 'index',
          'columns' => 
          array (
            0 => 'parentId',
            1 => 'parentType',
            2 => 'superParentId',
            3 => 'superParentType',
          ),
        ),
      ),
    ),
    'Notification' => 
    array (
      'fields' => 
      array (
        'number' => 
        array (
          'type' => 'autoincrement',
          'index' => true,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'noteData' => 
        array (
          'type' => 'jsonObject',
          'notStorable' => true,
        ),
        'type' => 
        array (
          'type' => 'varchar',
        ),
        'read' => 
        array (
          'type' => 'bool',
        ),
        'emailIsProcessed' => 
        array (
          'type' => 'bool',
        ),
        'user' => 
        array (
          'type' => 'link',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'message' => 
        array (
          'type' => 'text',
        ),
        'related' => 
        array (
          'type' => 'linkParent',
          'readOnly' => true,
        ),
        'relatedParent' => 
        array (
          'type' => 'linkParent',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'user' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'related' => 
        array (
          'type' => 'belongsToParent',
        ),
        'relatedParent' => 
        array (
          'type' => 'belongsToParent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'number',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'createdAt' => 
        array (
          'type' => 'index',
          'columns' => 
          array (
            0 => 'createdAt',
          ),
        ),
        'user' => 
        array (
          'type' => 'index',
          'columns' => 
          array (
            0 => 'userId',
            1 => 'createdAt',
          ),
        ),
      ),
    ),
    'PasswordChangeRequest' => 
    array (
      'fields' => 
      array (
        'requestId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 24,
          'index' => true,
        ),
        'user' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'index' => true,
        ),
        'url' => 
        array (
          'type' => 'url',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'user' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
    ),
    'PhoneNumber' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 36,
          'index' => true,
        ),
        'type' => 
        array (
          'type' => 'enum',
        ),
      ),
      'links' => 
      array (
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'Portal' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'trim' => true,
        ),
        'logo' => 
        array (
          'type' => 'image',
        ),
        'url' => 
        array (
          'type' => 'url',
          'notStorable' => true,
          'readOnly' => true,
        ),
        'customId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 36,
          'view' => 'views/portal/fields/custom-id',
          'trim' => true,
          'index' => true,
        ),
        'isActive' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'isDefault' => 
        array (
          'type' => 'bool',
          'default' => false,
          'notStorable' => true,
        ),
        'portalRoles' => 
        array (
          'type' => 'linkMultiple',
        ),
        'tabList' => 
        array (
          'type' => 'array',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/portal/fields/tab-list',
        ),
        'quickCreateList' => 
        array (
          'type' => 'array',
          'translation' => 'Global.scopeNames',
          'view' => 'views/portal/fields/quick-create-list',
        ),
        'companyLogo' => 
        array (
          'type' => 'image',
        ),
        'theme' => 
        array (
          'type' => 'enum',
          'view' => 'views/preferences/fields/theme',
          'translation' => 'Global.themes',
          'default' => '',
        ),
        'language' => 
        array (
          'type' => 'enum',
          'view' => 'views/preferences/fields/language',
          'default' => '',
        ),
        'timeZone' => 
        array (
          'type' => 'enum',
          'detault' => '',
          'view' => 'views/preferences/fields/time-zone',
        ),
        'dateFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'MM/DD/YYYY',
            1 => 'YYYY-MM-DD',
            2 => 'DD.MM.YYYY',
            3 => 'DD/MM/YYYY',
          ),
          'default' => '',
          'view' => 'views/preferences/fields/date-format',
        ),
        'timeFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'HH:mm',
            1 => 'hh:mma',
            2 => 'hh:mmA',
            3 => 'hh:mm A',
            4 => 'hh:mm a',
          ),
          'default' => '',
          'view' => 'views/preferences/fields/time-format',
        ),
        'weekStart' => 
        array (
          'type' => 'enumInt',
          'options' => 
          array (
            0 => 0,
            1 => 1,
          ),
          'default' => -1,
          'view' => 'views/preferences/fields/week-start',
        ),
        'defaultCurrency' => 
        array (
          'type' => 'enum',
          'default' => '',
          'view' => 'views/preferences/fields/default-currency',
        ),
        'dashboardLayout' => 
        array (
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout',
        ),
        'dashletsOptions' => 
        array (
          'type' => 'jsonObject',
          'disabled' => true,
        ),
        'customUrl' => 
        array (
          'type' => 'url',
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'portals',
        ),
        'portalRoles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'PortalRole',
          'foreign' => 'portals',
        ),
        'notes' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'portals',
        ),
        'articles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'KnowledgeBaseArticle',
          'foreign' => 'portals',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'PortalRole' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'maxLength' => 150,
          'required' => true,
          'type' => 'varchar',
          'trim' => true,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'fieldData' => 
        array (
          'type' => 'jsonObject',
        ),
      ),
      'links' => 
      array (
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'portalRoles',
        ),
        'portals' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'portalRoles',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'Preferences' => 
    array (
      'fields' => 
      array (
        'timeZone' => 
        array (
          'type' => 'enum',
          'detault' => '',
          'view' => 'views/preferences/fields/time-zone',
        ),
        'dateFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'MM/DD/YYYY',
            1 => 'YYYY-MM-DD',
            2 => 'DD.MM.YYYY',
            3 => 'DD/MM/YYYY',
          ),
          'default' => '',
          'view' => 'views/preferences/fields/date-format',
        ),
        'timeFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'HH:mm',
            1 => 'hh:mma',
            2 => 'hh:mmA',
            3 => 'hh:mm A',
            4 => 'hh:mm a',
          ),
          'default' => '',
          'view' => 'views/preferences/fields/time-format',
        ),
        'weekStart' => 
        array (
          'type' => 'enumInt',
          'options' => 
          array (
            0 => 0,
            1 => 1,
          ),
          'default' => -1,
          'view' => 'views/preferences/fields/week-start',
        ),
        'defaultCurrency' => 
        array (
          'type' => 'enum',
          'default' => '',
          'view' => 'views/preferences/fields/default-currency',
        ),
        'thousandSeparator' => 
        array (
          'type' => 'varchar',
          'default' => ',',
          'maxLength' => 1,
          'view' => 'views/settings/fields/thousand-separator',
        ),
        'decimalMark' => 
        array (
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
          'maxLength' => 1,
        ),
        'dashboardLayout' => 
        array (
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout',
        ),
        'dashletsOptions' => 
        array (
          'type' => 'jsonObject',
        ),
        'sharedCalendarUserList' => 
        array (
          'type' => 'jsonArray',
        ),
        'presetFilters' => 
        array (
          'type' => 'jsonObject',
        ),
        'smtpEmailAddress' => 
        array (
          'type' => 'varchar',
          'readOnly' => true,
          'notStorable' => true,
          'view' => 'views/preferences/fields/smtp-email-address',
          'trim' => true,
        ),
        'smtpServer' => 
        array (
          'type' => 'varchar',
          'trim' => true,
        ),
        'smtpPort' => 
        array (
          'type' => 'int',
          'min' => 0,
          'max' => 9999,
          'default' => 25,
        ),
        'smtpAuth' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'smtpSecurity' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'SSL',
            2 => 'TLS',
          ),
        ),
        'smtpUsername' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'smtpPassword' => 
        array (
          'type' => 'password',
        ),
        'language' => 
        array (
          'type' => 'enum',
          'default' => '',
          'view' => 'views/preferences/fields/language',
        ),
        'exportDelimiter' => 
        array (
          'type' => 'varchar',
          'default' => ',',
          'required' => true,
          'maxLength' => 1,
        ),
        'receiveAssignmentEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'receiveMentionEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'receiveStreamEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'autoFollowEntityTypeList' => 
        array (
          'type' => 'multiEnum',
          'view' => 'views/preferences/fields/auto-follow-entity-type-list',
          'translation' => 'Global.scopeNamesPlural',
          'notStorable' => true,
          'tooltip' => true,
        ),
        'signature' => 
        array (
          'type' => 'text',
          'view' => 'views/fields/wysiwyg',
        ),
        'defaultReminders' => 
        array (
          'type' => 'jsonArray',
          'view' => 'crm:views/meeting/fields/reminders',
        ),
        'theme' => 
        array (
          'type' => 'enum',
          'view' => 'views/preferences/fields/theme',
          'translation' => 'Global.themes',
        ),
        'useCustomTabList' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'tabList' => 
        array (
          'type' => 'array',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/preferences/fields/tab-list',
        ),
        'emailReplyToAllByDefault' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'emailReplyForceHtml' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'isPortalUser' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
        ),
        'doNotFillAssignedUserIfNotRequired' => 
        array (
          'type' => 'bool',
        ),
      ),
    ),
    'Role' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'maxLength' => 150,
          'required' => true,
          'type' => 'varchar',
          'trim' => true,
        ),
        'assignmentPermission' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no',
          ),
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
        ),
        'userPermission' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'not-set',
            1 => 'all',
            2 => 'team',
            3 => 'no',
          ),
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
        ),
        'portalPermission' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'not-set',
            1 => 'yes',
            2 => 'no',
          ),
          'default' => 'not-set',
          'tooltip' => true,
          'translation' => 'Role.options.levelList',
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'fieldData' => 
        array (
          'type' => 'jsonObject',
        ),
      ),
      'links' => 
      array (
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'roles',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'roles',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'ScheduledJob' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'job' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/scheduled-job/fields/job',
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Active',
            1 => 'Inactive',
          ),
        ),
        'scheduling' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'view' => 'views/scheduled-job/fields/scheduling',
          'tooltip' => true,
        ),
        'lastRun' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'log' => 
        array (
          'type' => 'hasMany',
          'entity' => 'ScheduledJobLogRecord',
          'foreign' => 'scheduledJob',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
      'jobSchedulingMap' => 
      array (
        'CheckInboundEmails' => '*/4 * * * *',
        'CheckEmailAccounts' => '*/5 * * * *',
        'SendEmailReminders' => '/2 * * * *',
        'Cleanup' => '1 1 * * 0',
        'AuthTokenControl' => '*/6 * * * *',
        'SendEmailNotifications' => '/2 * * * *',
        'ProcessMassEmail' => '15 * * * *',
        'ControlKnowledgeBaseArticleStatus' => '10 1 * * *',
      ),
    ),
    'ScheduledJobLogRecord' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'readOnly' => true,
        ),
        'status' => 
        array (
          'type' => 'varchar',
          'readOnly' => true,
        ),
        'executionTime' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'scheduledJob' => 
        array (
          'type' => 'link',
        ),
        'target' => 
        array (
          'type' => 'linkParent',
        ),
      ),
      'links' => 
      array (
        'scheduledJob' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'ScheduledJob',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'executionTime',
        'asc' => false,
      ),
    ),
    'Settings' => 
    array (
      'fields' => 
      array (
        'useCache' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'recordsPerPage' => 
        array (
          'type' => 'int',
          'minValue' => 1,
          'maxValue' => 1000,
          'default' => 20,
          'required' => true,
          'tooltip' => true,
        ),
        'recordsPerPageSmall' => 
        array (
          'type' => 'int',
          'minValue' => 1,
          'maxValue' => 100,
          'default' => 10,
          'required' => true,
          'tooltip' => true,
        ),
        'timeZone' => 
        array (
          'type' => 'enum',
          'detault' => 'UTC',
          'options' => 
          array (
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
          ),
        ),
        'dateFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'MM/DD/YYYY',
            1 => 'YYYY-MM-DD',
            2 => 'DD.MM.YYYY',
            3 => 'DD/MM/YYYY',
          ),
          'default' => 'MM/DD/YYYY',
        ),
        'timeFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'HH:mm',
            1 => 'hh:mma',
            2 => 'hh:mmA',
            3 => 'hh:mm A',
            4 => 'hh:mm a',
          ),
          'default' => 'HH:mm',
        ),
        'weekStart' => 
        array (
          'type' => 'enumInt',
          'options' => 
          array (
            0 => 0,
            1 => 1,
          ),
          'default' => 0,
        ),
        'thousandSeparator' => 
        array (
          'type' => 'varchar',
          'default' => ',',
          'maxLength' => 1,
          'view' => 'views/settings/fields/thousand-separator',
        ),
        'decimalMark' => 
        array (
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
          'maxLength' => 1,
        ),
        'currencyList' => 
        array (
          'type' => 'multiEnum',
          'default' => 
          array (
            0 => 'USD',
            1 => 'EUR',
          ),
          'options' => 
          array (
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
          ),
          'required' => true,
        ),
        'defaultCurrency' => 
        array (
          'type' => 'enum',
          'default' => 'USD',
          'required' => true,
          'view' => 'views/settings/fields/default-currency',
        ),
        'baseCurrency' => 
        array (
          'type' => 'enum',
          'default' => 'USD',
          'required' => true,
          'view' => 'views/settings/fields/default-currency',
        ),
        'currencyRates' => 
        array (
          'type' => 'base',
          'view' => 'views/settings/fields/currency-rates',
        ),
        'outboundEmailIsShared' => 
        array (
          'type' => 'bool',
          'default' => false,
          'tooltip' => true,
        ),
        'outboundEmailFromName' => 
        array (
          'type' => 'varchar',
          'default' => 'EspoCRM',
          'required' => true,
        ),
        'outboundEmailFromAddress' => 
        array (
          'type' => 'varchar',
          'default' => 'crm@example.com',
          'required' => true,
          'trim' => true,
        ),
        'smtpServer' => 
        array (
          'type' => 'varchar',
        ),
        'smtpPort' => 
        array (
          'type' => 'int',
          'required' => true,
          'min' => 0,
          'max' => 9999,
          'default' => 25,
        ),
        'smtpAuth' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'smtpSecurity' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'SSL',
            2 => 'TLS',
          ),
        ),
        'smtpUsername' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'smtpPassword' => 
        array (
          'type' => 'password',
        ),
        'tabList' => 
        array (
          'type' => 'array',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/tab-list',
        ),
        'quickCreateList' => 
        array (
          'type' => 'array',
          'translation' => 'Global.scopeNames',
          'view' => 'views/settings/fields/quick-create-list',
        ),
        'language' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'en_US',
          ),
          'default' => 'en_US',
          'view' => 'views/settings/fields/language',
        ),
        'globalSearchEntityList' => 
        array (
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNames',
          'view' => 'views/settings/fields/global-search-entity-list',
        ),
        'exportDelimiter' => 
        array (
          'type' => 'varchar',
          'default' => ',',
          'required' => true,
          'maxLength' => 1,
        ),
        'companyLogo' => 
        array (
          'type' => 'image',
        ),
        'authenticationMethod' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Espo',
            1 => 'LDAP',
          ),
          'default' => 'Espo',
        ),
        'ldapHost' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'ldapPort' => 
        array (
          'type' => 'varchar',
          'default' => 389,
        ),
        'ldapSecurity' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'SSL',
            2 => 'TLS',
          ),
        ),
        'ldapAuth' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'ldapUsername' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapPassword' => 
        array (
          'type' => 'password',
          'tooltip' => true,
        ),
        'ldapBindRequiresDn' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'ldapUserLoginFilter' => 
        array (
          'type' => 'varchar',
          'tooltip' => true,
        ),
        'ldapBaseDn' => 
        array (
          'type' => 'varchar',
          'tooltip' => true,
        ),
        'ldapAccountCanonicalForm' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Dn',
            1 => 'Username',
            2 => 'Backslash',
            3 => 'Principal',
          ),
          'tooltip' => true,
        ),
        'ldapAccountDomainName' => 
        array (
          'type' => 'varchar',
          'tooltip' => true,
        ),
        'ldapAccountDomainNameShort' => 
        array (
          'type' => 'varchar',
          'tooltip' => true,
        ),
        'ldapAccountFilterFormat' => 
        array (
          'type' => 'varchar',
        ),
        'ldapTryUsernameSplit' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'ldapOptReferrals' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'ldapCreateEspoUser' => 
        array (
          'type' => 'bool',
          'default' => true,
          'tooltip' => true,
        ),
        'ldapUserNameAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserObjectClass' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserFirstNameAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserLastNameAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserTitleAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserEmailAddressAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserPhoneNumberAttribute' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'tooltip' => true,
        ),
        'ldapUserDefaultTeam' => 
        array (
          'type' => 'link',
          'tooltip' => true,
        ),
        'ldapUserTeams' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'exportDisabled' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'assignmentEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'assignmentEmailNotificationsEntityList' => 
        array (
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/assignment-email-notifications-entity-list',
        ),
        'assignmentNotificationsEntityList' => 
        array (
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/assignment-notifications-entity-list',
        ),
        'postEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'updateEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'mentionEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'streamEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'portalStreamEmailNotifications' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'streamEmailNotificationsEntityList' => 
        array (
          'type' => 'multiEnum',
          'translation' => 'Global.scopeNamesPlural',
          'view' => 'views/settings/fields/stream-email-notifications-entity-list',
        ),
        'b2cMode' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'avatarsDisabled' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'followCreatedEntities' => 
        array (
          'type' => 'bool',
          'default' => false,
          'tooltip' => true,
        ),
        'adminPanelIframeUrl' => 
        array (
          'type' => 'varchar',
        ),
        'displayListViewRecordCount' => 
        array (
          'type' => 'bool',
        ),
        'userThemesDisabled' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'theme' => 
        array (
          'type' => 'enum',
          'view' => 'views/settings/fields/theme',
          'translation' => 'Global.themes',
        ),
        'emailMessageMaxSize' => 
        array (
          'type' => 'float',
          'min' => 0,
          'tooltip' => true,
        ),
        'inboundEmailMaxPortionSize' => 
        array (
          'type' => 'int',
        ),
        'personalEmailMaxPortionSize' => 
        array (
          'type' => 'int',
        ),
        'maxEmailAccountCount' => 
        array (
          'type' => 'int',
        ),
        'massEmailMaxPerHourCount' => 
        array (
          'type' => 'int',
          'min' => 0,
        ),
        'authTokenLifetime' => 
        array (
          'type' => 'float',
          'min' => 0,
          'default' => 0,
          'tooltip' => true,
        ),
        'authTokenMaxIdleTime' => 
        array (
          'type' => 'float',
          'min' => 0,
          'default' => 0,
          'tooltip' => true,
        ),
        'dashboardLayout' => 
        array (
          'type' => 'jsonArray',
          'view' => 'views/settings/fields/dashboard-layout',
        ),
        'dashletsOptions' => 
        array (
          'type' => 'jsonObject',
          'disabled' => true,
        ),
        'siteUrl' => 
        array (
          'type' => 'varchar',
        ),
        'applicationName' => 
        array (
          'type' => 'varchar',
        ),
        'readableDateFormatDisabled' => 
        array (
          'type' => 'bool',
        ),
        'addressFormat' => 
        array (
          'type' => 'enumInt',
          'options' => 
          array (
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
          ),
        ),
        'addressPreview' => 
        array (
          'type' => 'address',
          'notStorable' => true,
          'readOnly' => true,
          'view' => 'views/settings/fields/address-preview',
        ),
        'notificationSoundsDisabled' => 
        array (
          'type' => 'bool',
        ),
        'calendarEntityList' => 
        array (
          'type' => 'multiEnum',
          'view' => 'views/settings/fields/calendar-entity-list',
        ),
        'googleMapsApiKey' => 
        array (
          'type' => 'varchar',
        ),
        'addressPreviewStreet' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'addressPreviewCity' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
        ),
        'addressPreviewState' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
        ),
        'addressPreviewCountry' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
        ),
        'addressPreviewPostalCode' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
        ),
        'addressPreviewMap' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'map',
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'links' => 
      array (
        'ldapUserDefaultTeam' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Team',
        ),
        'ldapUserTeams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'users',
          'additionalColumns' => 
          array (
            'role' => 
            array (
              'type' => 'varchar',
              'len' => 100,
            ),
          ),
          'layoutRelationshipsDisabled' => true,
        ),
      ),
    ),
    'Team' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'trim' => true,
        ),
        'roles' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'positionList' => 
        array (
          'type' => 'array',
          'tooltip' => true,
        ),
        'userRole' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'teams',
        ),
        'roles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Role',
          'foreign' => 'teams',
        ),
        'notes' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'teams',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'Template' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'body' => 
        array (
          'type' => 'text',
          'view' => 'Fields.Wysiwyg',
        ),
        'header' => 
        array (
          'type' => 'text',
          'view' => 'Fields.Wysiwyg',
        ),
        'footer' => 
        array (
          'type' => 'text',
          'view' => 'Fields.Wysiwyg',
          'tooltip' => true,
        ),
        'entityType' => 
        array (
          'type' => 'enum',
          'required' => true,
          'translation' => 'Global.scopeNames',
          'view' => 'Fields.EntityType',
        ),
        'leftMargin' => 
        array (
          'type' => 'float',
          'default' => 10,
        ),
        'rightMargin' => 
        array (
          'type' => 'float',
          'default' => 10,
        ),
        'topMargin' => 
        array (
          'type' => 'float',
          'default' => 10,
        ),
        'bottomMargin' => 
        array (
          'type' => 'float',
          'default' => 25,
        ),
        'printFooter' => 
        array (
          'type' => 'bool',
        ),
        'footerPosition' => 
        array (
          'type' => 'float',
          'default' => 15,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'UniqueId' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'index' => true,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'User' => 
    array (
      'fields' => 
      array (
        'isAdmin' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
        ),
        'userName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 50,
          'required' => true,
          'view' => 'views/user/fields/user-name',
          'tooltip' => true,
        ),
        'name' => 
        array (
          'type' => 'personName',
          'view' => 'views/user/fields/name',
        ),
        'password' => 
        array (
          'type' => 'password',
          'maxLength' => 150,
          'internal' => true,
          'disabled' => true,
        ),
        'passwordConfirm' => 
        array (
          'type' => 'password',
          'maxLength' => 150,
          'internal' => true,
          'disabled' => true,
          'notStorable' => true,
        ),
        'salutationName' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'default' => '',
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'default' => '',
        ),
        'isActive' => 
        array (
          'type' => 'bool',
          'tooltip' => true,
          'default' => true,
        ),
        'isPortalUser' => 
        array (
          'type' => 'bool',
        ),
        'isSuperAdmin' => 
        array (
          'type' => 'bool',
          'default' => false,
          'disabled' => true,
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'trim' => true,
        ),
        'position' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'notStorable' => true,
          'where' => 
          array (
            'LIKE' => 
            array (
              'leftJoins' => 
              array (
                0 => 
                array (
                  0 => 'teams',
                  1 => 'teamsPosition',
                ),
              ),
              'sql' => 'teamsPositionMiddle.role LIKE {value}',
              'distinct' => true,
            ),
            '=' => 
            array (
              'leftJoins' => 
              array (
                0 => 
                array (
                  0 => 'teams',
                  1 => 'teamsPosition',
                ),
              ),
              'sql' => 'teamsPositionMiddle.role = {value}',
              'distinct' => true,
            ),
            '<>' => 
            array (
              'leftJoins' => 
              array (
                0 => 
                array (
                  0 => 'teams',
                  1 => 'teamsPosition',
                ),
              ),
              'sql' => 'teamsPositionMiddle.role <> {value}',
              'distinct' => true,
            ),
            'IS NULL' => 
            array (
              'leftJoins' => 
              array (
                0 => 
                array (
                  0 => 'teams',
                  1 => 'teamsPosition',
                ),
              ),
              'sql' => 'teamsPositionMiddle.role IS NULL',
              'distinct' => true,
            ),
            'IS NOT NULL' => 
            array (
              'leftJoins' => 
              array (
                0 => 
                array (
                  0 => 'teams',
                  1 => 'teamsPosition',
                ),
              ),
              'sql' => 'teamsPositionMiddle.role IS NOT NULL',
              'distinct' => true,
            ),
          ),
          'trim' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
          'required' => false,
        ),
        'phoneNumber' => 
        array (
          'type' => 'phone',
          'typeList' => 
          array (
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other',
          ),
          'defaultType' => 'Mobile',
        ),
        'token' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'defaultTeam' => 
        array (
          'type' => 'link',
          'tooltip' => true,
        ),
        'acceptanceStatus' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'teamRole' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
          'columns' => 
          array (
            'role' => 'userRole',
          ),
          'view' => 'views/user/fields/teams',
          'default' => 'javascript: return {teamsIds: []}',
        ),
        'roles' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'portals' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'portalRoles' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'contact' => 
        array (
          'type' => 'link',
          'view' => 'views/user/fields/contact',
        ),
        'accounts' => 
        array (
          'type' => 'linkMultiple',
        ),
        'account' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'readOnly' => true,
        ),
        'portal' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'avatar' => 
        array (
          'type' => 'image',
          'view' => 'views/user/fields/avatar',
          'previewSize' => 'small',
        ),
        'sendAccessInfo' => 
        array (
          'type' => 'bool',
          'notStorable' => true,
          'disabled' => true,
        ),
        'gender' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Male',
            2 => 'Female',
            3 => 'Neutral',
          ),
          'default' => '',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'defaultTeam' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Team',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'foreign' => 'users',
          'additionalColumns' => 
          array (
            'role' => 
            array (
              'type' => 'varchar',
              'len' => 100,
            ),
          ),
          'layoutRelationshipsDisabled' => true,
        ),
        'roles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Role',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true,
        ),
        'portals' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true,
        ),
        'portalRoles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'PortalRole',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true,
        ),
        'preferences' => 
        array (
          'type' => 'hasOne',
          'entity' => 'Preferences',
        ),
        'meetings' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'users',
        ),
        'calls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'users',
        ),
        'emails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'users',
        ),
        'notes' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Note',
          'foreign' => 'users',
          'layoutRelationshipsDisabled' => true,
        ),
        'contact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'portalUser',
        ),
        'accounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'portalUsers',
          'relationName' => 'AccountPortalUser',
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'users',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'userName',
        'asc' => true,
        'textFilterFields' => 
        array (
          0 => 'name',
          1 => 'userName',
        ),
      ),
    ),
    'Account' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'website' => 
        array (
          'type' => 'url',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phoneNumber' => 
        array (
          'type' => 'phone',
          'typeList' => 
          array (
            0 => 'Office',
            1 => 'Mobile',
            2 => 'Fax',
            3 => 'Other',
          ),
          'defaultType' => 'Office',
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Customer',
            2 => 'Investor',
            3 => 'Partner',
            4 => 'Reseller',
          ),
          'default' => '',
        ),
        'industry' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Advertising',
            2 => 'Agriculture',
            3 => 'Apparel & Accessories',
            4 => 'Automotive',
            5 => 'Banking',
            6 => 'Biotechnology',
            7 => 'Building Materials & Equipment',
            8 => 'Chemical',
            9 => 'Computer',
            10 => 'Education',
            11 => 'Electronics',
            12 => 'Energy',
            13 => 'Entertainment & Leisure',
            14 => 'Finance',
            15 => 'Food & Beverage',
            16 => 'Grocery',
            17 => 'Healthcare',
            18 => 'Insurance',
            19 => 'Legal',
            20 => 'Manufacturing',
            21 => 'Publishing',
            22 => 'Real Estate',
            23 => 'Service',
            24 => 'Sports',
            25 => 'Software',
            26 => 'Technology',
            27 => 'Telecommunications',
            28 => 'Television',
            29 => 'Transportation',
            30 => 'Venture Capital',
          ),
          'default' => '',
          'isSorted' => true,
        ),
        'sicCode' => 
        array (
          'type' => 'varchar',
          'maxLength' => 40,
          'trim' => true,
        ),
        'contactRole' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'billingAddress' => 
        array (
          'type' => 'address',
        ),
        'billingAddressStreet' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'billingAddressCity' => 
        array (
          'type' => 'varchar',
        ),
        'billingAddressState' => 
        array (
          'type' => 'varchar',
        ),
        'billingAddressCountry' => 
        array (
          'type' => 'varchar',
        ),
        'billingAddressPostalCode' => 
        array (
          'type' => 'varchar',
        ),
        'shippingAddress' => 
        array (
          'type' => 'address',
          'view' => 'crm:views/account/fields/shipping-address',
        ),
        'shippingAddressStreet' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'shippingAddressCity' => 
        array (
          'type' => 'varchar',
        ),
        'shippingAddressState' => 
        array (
          'type' => 'varchar',
        ),
        'shippingAddressCountry' => 
        array (
          'type' => 'varchar',
        ),
        'shippingAddressPostalCode' => 
        array (
          'type' => 'varchar',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'campaign' => 
        array (
          'type' => 'link',
          'layoutListDisabled' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'targetLists' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'importDisabled' => true,
          'noLoad' => true,
        ),
        'targetList' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'entity' => 'TargetList',
        ),
        'billingAddressMap' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
        'shippingAddressMap' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'accounts',
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'account',
        ),
        'cases' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'account',
        ),
        'documents' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'accounts',
        ),
        'meetingsPrimary' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true,
        ),
        'emailsPrimary' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Email',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true,
        ),
        'callsPrimary' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasksPrimary' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Task',
          'foreign' => 'account',
          'layoutRelationshipsDisabled' => true,
        ),
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'accounts',
          'noJoin' => true,
        ),
        'campaignLogRecords' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent',
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'accounts',
        ),
        'portalUsers' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'accounts',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
        'textFilterFields' => 
        array (
          0 => 'name',
          1 => 'emailAddress',
        ),
      ),
      'indexes' => 
      array (
        'name' => 
        array (
          'columns' => 
          array (
            0 => 'name',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'Call' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Planned',
            1 => 'Held',
            2 => 'Not Held',
          ),
          'default' => 'Planned',
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Held' => 'success',
          ),
          'audited' => true,
        ),
        'dateStart' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
          'audited' => true,
        ),
        'dateEnd' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'after' => 'dateStart',
        ),
        'duration' => 
        array (
          'type' => 'duration',
          'start' => 'dateStart',
          'end' => 'dateEnd',
          'options' => 
          array (
            0 => 300,
            1 => 600,
            2 => 900,
            3 => 1800,
            4 => 2700,
            5 => 3600,
            6 => 7200,
          ),
          'default' => 300,
          'notStorable' => true,
        ),
        'reminders' => 
        array (
          'type' => 'jsonArray',
          'notStorable' => true,
          'view' => 'crm:views/meeting/fields/reminders',
        ),
        'direction' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Outbound',
            1 => 'Inbound',
          ),
          'default' => 'Outbound',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
          'entityList' => 
          array (
            0 => 'Account',
            1 => 'Lead',
            2 => 'Opportunity',
            3 => 'Case',
          ),
        ),
        'account' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'acceptanceStatus' => 
        array (
          'type' => 'enum',
          'notStorable' => true,
          'disabled' => true,
          'options' => 
          array (
            0 => 'None',
            1 => 'Accepted',
            2 => 'Tentative',
            3 => 'Declined',
          ),
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/users',
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/contacts',
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/attendees',
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
      ),
      'links' => 
      array (
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'calls',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'calls',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'calls',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'calls',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'dateStart',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'dateStartStatus' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'status',
          ),
        ),
        'dateStart' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'deleted',
          ),
        ),
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'assignedUserStatus' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'status',
          ),
        ),
      ),
    ),
    'Campaign' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Planning',
            1 => 'Active',
            2 => 'Inactive',
            3 => 'Complete',
          ),
          'default' => 'Planning',
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Email',
            1 => 'Newsletter',
            2 => 'Web',
            3 => 'Television',
            4 => 'Radio',
            5 => 'Mail',
          ),
          'default' => 'Email',
        ),
        'startDate' => 
        array (
          'type' => 'date',
        ),
        'endDate' => 
        array (
          'type' => 'date',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'targetLists' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'excludingTargetLists' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'sentCount' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'openedCount' => 
        array (
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'clickedCount' => 
        array (
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'optedOutCount' => 
        array (
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'bouncedCount' => 
        array (
          'view' => 'crm:views/campaign/fields/int-with-percentage',
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'hardBouncedCount' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'softBouncedCount' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'leadCreatedCount' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'openedPercentage' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'clickedPercentage' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'optedOutPercentage' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'bouncedPercentage' => 
        array (
          'type' => 'int',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'revenue' => 
        array (
          'type' => 'currency',
          'notStorable' => true,
          'readOnly' => true,
          'disabled' => true,
        ),
        'budget' => 
        array (
          'type' => 'currency',
        ),
        'revenueCurrency' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'varchar',
          'disabled' => true,
        ),
        'revenueConverted' => 
        array (
          'notStorable' => true,
          'readOnly' => true,
          'type' => 'currencyConverted',
        ),
        'budgetCurrency' => 
        array (
          'type' => 'varchar',
          'disabled' => true,
        ),
        'budgetConverted' => 
        array (
          'type' => 'currencyConverted',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'campaigns',
        ),
        'excludingTargetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'campaignsExcluding',
          'relationName' => 'campaignTargetListExcluding',
        ),
        'accounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'campaign',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'campaign',
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'campaign',
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'campaign',
        ),
        'campaignLogRecords' => 
        array (
          'type' => 'hasMany',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'campaign',
        ),
        'trackingUrls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'CampaignTrackingUrl',
          'foreign' => 'campaign',
        ),
        'massEmails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'campaign',
          'layoutRelationshipsDisabled' => true,
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'createdAt' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'CampaignLogRecord' => 
    array (
      'fields' => 
      array (
        'action' => 
        array (
          'type' => 'enum',
          'required' => true,
          'maxLength' => 50,
          'options' => 
          array (
            0 => 'Sent',
            1 => 'Opened',
            2 => 'Opted Out',
            3 => 'Bounced',
            4 => 'Clicked',
            5 => 'Lead Created',
          ),
        ),
        'actionDate' => 
        array (
          'type' => 'datetime',
          'required' => true,
        ),
        'data' => 
        array (
          'type' => 'jsonObject',
          'view' => 'crm:views/campaign-log-record/fields/data',
        ),
        'stringData' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'stringAdditionalData' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'application' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'maxLength' => 36,
          'default' => 'Espo',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'campaign' => 
        array (
          'type' => 'link',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'object' => 
        array (
          'type' => 'linkParent',
        ),
        'queueItem' => 
        array (
          'type' => 'link',
        ),
        'isTest' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'campaignLogRecords',
        ),
        'queueItem' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailQueueItem',
          'noJoin' => true,
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entityList' => 
          array (
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'User',
          ),
        ),
        'object' => 
        array (
          'type' => 'belongsToParent',
          'entityList' => 
          array (
            0 => 'Email',
          ),
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'actionDate',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'actionDate' => 
        array (
          'columns' => 
          array (
            0 => 'actionDate',
            1 => 'deleted',
          ),
        ),
        'action' => 
        array (
          'columns' => 
          array (
            0 => 'action',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'CampaignTrackingUrl' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'url' => 
        array (
          'type' => 'url',
          'required' => true,
        ),
        'urlToUse' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'readOnly' => true,
        ),
        'campaign' => 
        array (
          'type' => 'link',
          'required' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'trackingUrls',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'Case' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'number' => 
        array (
          'type' => 'autoincrement',
          'index' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'New',
            1 => 'Assigned',
            2 => 'Pending',
            3 => 'Closed',
            4 => 'Rejected',
            5 => 'Duplicate',
          ),
          'default' => 'New',
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Closed' => 'success',
            'Duplicate' => 'danger',
            'Rejected' => 'danger',
          ),
          'audited' => true,
        ),
        'priority' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Low',
            1 => 'Normal',
            2 => 'High',
            3 => 'Urgent',
          ),
          'default' => 'Normal',
          'audited' => true,
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Question',
            2 => 'Incident',
            3 => 'Problem',
          ),
          'audited' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'account' => 
        array (
          'type' => 'link',
        ),
        'lead' => 
        array (
          'type' => 'link',
        ),
        'contact' => 
        array (
          'type' => 'link',
          'view' => 'crm:views/case/fields/contact',
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'crm:views/case/fields/contacts',
        ),
        'inboundEmail' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'inboundEmail' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'InboundEmail',
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'cases',
        ),
        'lead' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Lead',
          'foreign' => 'cases',
        ),
        'contact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'casesPrimary',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'cases',
          'layoutRelationshipsDisabled' => true,
        ),
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'articles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'KnowledgeBaseArticle',
          'foreign' => 'cases',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'number',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'assignedUserStatus' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'status',
          ),
        ),
      ),
    ),
    'Contact' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'personName',
        ),
        'salutationName' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'default' => '',
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'default' => '',
        ),
        'accountId' => 
        array (
          'where' => 
          array (
            '=' => 'contact.id IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id = {value})',
            '<>' => 'contact.id IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id <> {value})',
            'IN' => 'contact.id IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id IN {value})',
            'NOT IN' => 'contact.id NOT IN (SELECT contact_id FROM account_contact WHERE deleted = 0 AND account_id IN {value})',
            'IS NULL' => 'contact.account_id IS NULL',
            'IS NOT NULL' => 'contact.account_id IS NOT NULL',
          ),
          'disabled' => true,
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 50,
          'notStorable' => true,
          'select' => 'accountContact.role',
          'orderBy' => 'accountContact.role {direction}',
          'where' => 
          array (
            'LIKE' => 
            array (
              'leftJoins' => 
              array (
                0 => 'accounts',
              ),
              'sql' => 'accountsMiddle.role LIKE {value}',
              'distinct' => true,
            ),
            '=' => 
            array (
              'leftJoins' => 
              array (
                0 => 'accounts',
              ),
              'sql' => 'accountsMiddle.role = {value}',
              'distinct' => true,
            ),
            '<>' => 
            array (
              'leftJoins' => 
              array (
                0 => 'accounts',
              ),
              'sql' => 'accountsMiddle.role <> {value}',
              'distinct' => true,
            ),
            'IS NULL' => 
            array (
              'leftJoins' => 
              array (
                0 => 'accounts',
              ),
              'sql' => 'accountsMiddle.role IS NULL',
              'distinct' => true,
            ),
            'IS NOT NULL' => 
            array (
              'leftJoins' => 
              array (
                0 => 'accounts',
              ),
              'sql' => 'accountsMiddle.role IS NOT NULL',
              'distinct' => true,
            ),
          ),
          'trim' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phoneNumber' => 
        array (
          'type' => 'phone',
          'typeList' => 
          array (
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other',
          ),
          'defaultType' => 'Mobile',
        ),
        'doNotCall' => 
        array (
          'type' => 'bool',
        ),
        'address' => 
        array (
          'type' => 'address',
        ),
        'addressStreet' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'addressCity' => 
        array (
          'type' => 'varchar',
        ),
        'addressState' => 
        array (
          'type' => 'varchar',
        ),
        'addressCountry' => 
        array (
          'type' => 'varchar',
        ),
        'addressPostalCode' => 
        array (
          'type' => 'varchar',
        ),
        'account' => 
        array (
          'type' => 'link',
        ),
        'accounts' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'crm:views/contact/fields/accounts',
          'columns' => 
          array (
            'role' => 'contactRole',
          ),
        ),
        'accountRole' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'accountType' => 
        array (
          'type' => 'foreign',
          'link' => 'account',
          'field' => 'type',
          'readOnly' => true,
          'view' => 'views/fields/foreign-enum',
        ),
        'opportunityRole' => 
        array (
          'type' => 'enum',
          'notStorable' => true,
          'disabled' => true,
          'options' => 
          array (
            0 => '',
            1 => 'Decision Maker',
            2 => 'Evaluator',
            3 => 'Influencer',
          ),
        ),
        'acceptanceStatus' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'campaign' => 
        array (
          'type' => 'link',
          'layoutListDisabled' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'targetLists' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'importDisabled' => true,
          'noLoad' => true,
        ),
        'targetList' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'entity' => 'TargetList',
        ),
        'portalUser' => 
        array (
          'type' => 'link',
          'layoutMassUpdateDisabled' => true,
          'layoutListDisabled' => true,
          'readOnly' => true,
        ),
        'addressMap' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
        'accounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'contacts',
          'additionalColumns' => 
          array (
            'role' => 
            array (
              'type' => 'varchar',
              'len' => 50,
            ),
          ),
          'layoutRelationshipsDisabled' => true,
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'contacts',
        ),
        'casesPrimary' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'contact',
          'layoutRelationshipsDisabled' => true,
        ),
        'cases' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'contacts',
        ),
        'meetings' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'contacts',
          'layoutRelationshipsDisabled' => true,
        ),
        'calls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'contacts',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'contacts',
          'noJoin' => true,
        ),
        'campaignLogRecords' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent',
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'contacts',
        ),
        'portalUser' => 
        array (
          'type' => 'hasOne',
          'entity' => 'User',
          'foreign' => 'contact',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
        'textFilterFields' => 
        array (
          0 => 'name',
          1 => 'emailAddress',
        ),
      ),
      'indexes' => 
      array (
        'firstName' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'deleted',
          ),
        ),
        'name' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'lastName',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'Document' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'view' => 'crm:views/document/fields/name',
          'trim' => true,
        ),
        'file' => 
        array (
          'type' => 'file',
          'required' => true,
          'view' => 'crm:views/document/fields/file',
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Active',
            1 => 'Draft',
            2 => 'Expired',
            3 => 'Canceled',
          ),
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Canceled' => 'danger',
            'Expired' => 'danger',
          ),
        ),
        'source' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Espo',
          ),
          'default' => 'Espo',
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Contract',
            2 => 'NDA',
            3 => 'EULA',
            4 => 'License Agreement',
          ),
        ),
        'publishDate' => 
        array (
          'type' => 'date',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getToday();',
        ),
        'expirationDate' => 
        array (
          'type' => 'date',
          'after' => 'publishDate',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'accounts' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'importDisabled' => true,
          'noLoad' => true,
        ),
        'folder' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/link-category-tree',
        ),
      ),
      'links' => 
      array (
        'accounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'documents',
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'documents',
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'documents',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'folder' => 
        array (
          'type' => 'belongsTo',
          'foreign' => 'documents',
          'entity' => 'DocumentFolder',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'DocumentFolder' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'parent' => 
        array (
          'type' => 'link',
        ),
        'childList' => 
        array (
          'type' => 'jsonArray',
          'notStorable' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'parent' => 
        array (
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'DocumentFolder',
        ),
        'children' => 
        array (
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'DocumentFolder',
        ),
        'documents' => 
        array (
          'type' => 'hasMany',
          'foreign' => 'folder',
          'entity' => 'Document',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'parent',
        'asc' => true,
      ),
      'additionalTables' => 
      array (
        'DocumentFolderPath' => 
        array (
          'fields' => 
          array (
            'id' => 
            array (
              'type' => 'id',
              'dbType' => 'int',
              'len' => '11',
              'autoincrement' => true,
              'unique' => true,
            ),
            'ascendorId' => 
            array (
              'type' => 'varchar',
              'len' => '100',
              'index' => true,
            ),
            'descendorId' => 
            array (
              'type' => 'varchar',
              'len' => '24',
              'index' => true,
            ),
          ),
        ),
      ),
    ),
    'EmailQueueItem' => 
    array (
      'fields' => 
      array (
        'massEmail' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Pending',
            1 => 'Sent',
            2 => 'Failed',
          ),
          'readOnly' => true,
        ),
        'attemptCount' => 
        array (
          'type' => 'int',
          'readOnly' => true,
          'default' => 0,
        ),
        'target' => 
        array (
          'type' => 'linkParent',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'sentAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'emailAddress' => 
        array (
          'type' => 'varchar',
          'readOnly' => true,
        ),
        'isTest' => 
        array (
          'type' => 'bool',
        ),
      ),
      'links' => 
      array (
        'massEmail' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'MassEmail',
          'foreign' => 'queueItems',
        ),
        'target' => 
        array (
          'type' => 'belongsToParent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'KnowledgeBaseArticle' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Draft',
            1 => 'In Review',
            2 => 'Published',
            3 => 'Archived',
          ),
          'view' => 'crm:views/knowledge-base-article/fields/status',
          'default' => 'Draft',
        ),
        'language' => 
        array (
          'type' => 'enum',
          'view' => 'crm:views/knowledge-base-article/fields/language',
          'default' => '',
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Article',
          ),
        ),
        'portals' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'publishDate' => 
        array (
          'type' => 'date',
        ),
        'expirationDate' => 
        array (
          'type' => 'date',
          'after' => 'publishDate',
        ),
        'order' => 
        array (
          'type' => 'int',
          'disableFormatting' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'categories' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/link-multiple-category-tree',
        ),
        'attachments' => 
        array (
          'type' => 'attachmentMultiple',
        ),
        'body' => 
        array (
          'type' => 'wysiwyg',
        ),
      ),
      'links' => 
      array (
        'cases' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'articles',
        ),
        'portals' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Portal',
          'foreign' => 'articles',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'categories' => 
        array (
          'type' => 'hasMany',
          'foreign' => 'articles',
          'entity' => 'KnowledgeBaseCategory',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'order',
        'asc' => true,
      ),
    ),
    'KnowledgeBaseCategory' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'order' => 
        array (
          'type' => 'int',
          'required' => true,
          'disableFormatting' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'parent' => 
        array (
          'type' => 'link',
        ),
        'childList' => 
        array (
          'type' => 'jsonArray',
          'notStorable' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'parent' => 
        array (
          'type' => 'belongsTo',
          'foreign' => 'children',
          'entity' => 'KnowledgeBaseCategory',
        ),
        'children' => 
        array (
          'type' => 'hasMany',
          'foreign' => 'parent',
          'entity' => 'KnowledgeBaseCategory',
        ),
        'articles' => 
        array (
          'type' => 'hasMany',
          'foreign' => 'categories',
          'entity' => 'KnowledgeBaseArticle',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'parent',
        'asc' => true,
      ),
      'additionalTables' => 
      array (
        'KnowledgeBaseCategoryPath' => 
        array (
          'fields' => 
          array (
            'id' => 
            array (
              'type' => 'id',
              'dbType' => 'int',
              'len' => '11',
              'autoincrement' => true,
              'unique' => true,
            ),
            'ascendorId' => 
            array (
              'type' => 'varchar',
              'len' => '100',
              'index' => true,
            ),
            'descendorId' => 
            array (
              'type' => 'varchar',
              'len' => '24',
              'index' => true,
            ),
          ),
        ),
      ),
    ),
    'Lead' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'personName',
        ),
        'salutationName' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'default' => '',
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'default' => '',
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'New',
            1 => 'Assigned',
            2 => 'In Process',
            3 => 'Converted',
            4 => 'Recycled',
            5 => 'Dead',
          ),
          'default' => 'New',
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Converted' => 'success',
            'Recycled' => 'danger',
            'Dead' => 'danger',
          ),
          'audited' => true,
        ),
        'source' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Call',
            2 => 'Email',
            3 => 'Existing Customer',
            4 => 'Partner',
            5 => 'Public Relations',
            6 => 'Web Site',
            7 => 'Campaign',
            8 => 'Other',
          ),
          'default' => '',
        ),
        'opportunityAmount' => 
        array (
          'type' => 'currency',
          'audited' => true,
        ),
        'opportunityAmountConverted' => 
        array (
          'type' => 'currencyConverted',
          'readOnly' => true,
        ),
        'website' => 
        array (
          'type' => 'url',
        ),
        'address' => 
        array (
          'type' => 'address',
        ),
        'addressStreet' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'addressCity' => 
        array (
          'type' => 'varchar',
        ),
        'addressState' => 
        array (
          'type' => 'varchar',
        ),
        'addressCountry' => 
        array (
          'type' => 'varchar',
        ),
        'addressPostalCode' => 
        array (
          'type' => 'varchar',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phoneNumber' => 
        array (
          'type' => 'phone',
          'typeList' => 
          array (
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other',
          ),
          'defaultType' => 'Mobile',
        ),
        'doNotCall' => 
        array (
          'type' => 'bool',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'accountName' => 
        array (
          'type' => 'varchar',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'acceptanceStatus' => 
        array (
          'type' => 'varchar',
          'notStorable' => true,
          'disabled' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'campaign' => 
        array (
          'type' => 'link',
          'layoutListDisabled' => true,
        ),
        'createdAccount' => 
        array (
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
        ),
        'createdContact' => 
        array (
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
        ),
        'createdOpportunity' => 
        array (
          'type' => 'link',
          'layoutDetailDisabled' => true,
          'layoutMassUpdateDisabled' => true,
        ),
        'targetLists' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'importDisabled' => true,
          'noLoad' => true,
        ),
        'targetList' => 
        array (
          'type' => 'link',
          'notStorable' => true,
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'layoutMassUpdateDisabled' => true,
          'layoutFiltersDisabled' => true,
          'entity' => 'TargetList',
        ),
        'opportunityAmountCurrency' => 
        array (
          'type' => 'varchar',
          'disabled' => true,
        ),
        'addressMap' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'leads',
        ),
        'meetings' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'leads',
          'layoutRelationshipsDisabled' => true,
        ),
        'calls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'leads',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'cases' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'lead',
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'createdAccount' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
          'noJoin' => true,
        ),
        'createdContact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'noJoin' => true,
        ),
        'createdOpportunity' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Opportunity',
          'noJoin' => true,
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'leads',
          'noJoin' => true,
        ),
        'campaignLogRecords' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'CampaignLogRecord',
          'foreign' => 'parent',
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'leads',
        ),
        'documents' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'leads',
        ),
      ),
      'convertEntityList' => 
      array (
        0 => 'Account',
        1 => 'Contact',
        2 => 'Opportunity',
      ),
      'convertFields' => 
      array (
        'Contact' => 
        array (
        ),
        'Account' => 
        array (
          'name' => 'accountName',
          'billingAddressStreet' => 'addressStreet',
          'billingAddressCity' => 'addressCity',
          'billingAddressState' => 'addressState',
          'billingAddressPostalCode' => 'addressPostalCode',
          'billingAddressCountry' => 'addressCountry',
        ),
        'Opportunity' => 
        array (
          'amount' => 'opportunityAmount',
          'leadSource' => 'source',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
        'textFilterFields' => 
        array (
          0 => 'name',
          1 => 'accountName',
          2 => 'emailAddress',
        ),
      ),
      'indexes' => 
      array (
        'firstName' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'deleted',
          ),
        ),
        'name' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'lastName',
          ),
        ),
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
        'createdAt' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'deleted',
          ),
        ),
        'createdAtStatus' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'status',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'assignedUserStatus' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'status',
          ),
        ),
      ),
    ),
    'MassEmail' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Draft',
            1 => 'Pending',
          ),
          'default' => 'Pending',
        ),
        'storeSentEmails' => 
        array (
          'type' => 'bool',
          'default' => false,
        ),
        'optOutEntirely' => 
        array (
          'type' => 'bool',
          'default' => false,
          'tooltip' => true,
        ),
        'fromAddress' => 
        array (
          'type' => 'varchar',
          'trim' => true,
          'view' => 'crm:views/mass-email/fields/from-address',
        ),
        'fromName' => 
        array (
          'type' => 'varchar',
        ),
        'replyToAddress' => 
        array (
          'type' => 'varchar',
          'trim' => true,
        ),
        'replyToName' => 
        array (
          'type' => 'varchar',
        ),
        'startAt' => 
        array (
          'type' => 'datetime',
          'required' => true,
        ),
        'emailTemplate' => 
        array (
          'type' => 'link',
          'required' => true,
          'view' => 'crm:views/mass-email/fields/email-template',
        ),
        'campaign' => 
        array (
          'type' => 'link',
        ),
        'targetLists' => 
        array (
          'type' => 'linkMultiple',
          'required' => true,
          'tooltip' => true,
        ),
        'excludingTargetLists' => 
        array (
          'type' => 'linkMultiple',
          'tooltip' => true,
        ),
        'inboundEmail' => 
        array (
          'type' => 'link',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'emailTemplate' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'EmailTemplate',
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'massEmails',
        ),
        'targetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'massEmails',
        ),
        'excludingTargetLists' => 
        array (
          'type' => 'hasMany',
          'entity' => 'TargetList',
          'foreign' => 'massEmailsExcluding',
          'relationName' => 'massEmailTargetListExcluding',
        ),
        'inboundEmail' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'InboundEmail',
        ),
        'queueItems' => 
        array (
          'type' => 'hasMany',
          'entity' => 'EmailQueueItem',
          'foreign' => 'massEmail',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'Meeting' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Planned',
            1 => 'Held',
            2 => 'Not Held',
          ),
          'default' => 'Planned',
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Held' => 'success',
          ),
          'audited' => true,
        ),
        'dateStart' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
          'audited' => true,
        ),
        'dateEnd' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'after' => 'dateStart',
        ),
        'duration' => 
        array (
          'type' => 'duration',
          'start' => 'dateStart',
          'end' => 'dateEnd',
          'options' => 
          array (
            0 => 900,
            1 => 1800,
            2 => 3600,
            3 => 7200,
            4 => 10800,
            5 => 86400,
          ),
          'default' => 3600,
          'notStorable' => true,
        ),
        'reminders' => 
        array (
          'type' => 'jsonArray',
          'notStorable' => true,
          'view' => 'crm:views/meeting/fields/reminders',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
          'entityList' => 
          array (
            0 => 'Account',
            1 => 'Lead',
            2 => 'Opportunity',
            3 => 'Case',
          ),
        ),
        'account' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'acceptanceStatus' => 
        array (
          'type' => 'enum',
          'notStorable' => true,
          'disabled' => true,
          'options' => 
          array (
            0 => 'None',
            1 => 'Accepted',
            2 => 'Tentative',
            3 => 'Declined',
          ),
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'crm:views/meeting/fields/users',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'view' => 'crm:views/meeting/fields/contacts',
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'crm:views/meeting/fields/attendees',
          'layoutDetailDisabled' => true,
          'layoutListDisabled' => true,
          'columns' => 
          array (
            'status' => 'acceptanceStatus',
          ),
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
      ),
      'links' => 
      array (
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'meetings',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'meetings',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'meetings',
          'additionalColumns' => 
          array (
            'status' => 
            array (
              'type' => 'varchar',
              'len' => '36',
              'default' => 'None',
            ),
          ),
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'meetings',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'dateStart',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'dateStartStatus' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'status',
          ),
        ),
        'dateStart' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'deleted',
          ),
        ),
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'assignedUserStatus' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'status',
          ),
        ),
      ),
    ),
    'Opportunity' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'amount' => 
        array (
          'type' => 'currency',
          'required' => true,
          'audited' => true,
        ),
        'amountConverted' => 
        array (
          'type' => 'currencyConverted',
          'readOnly' => true,
        ),
        'amountWeightedConverted' => 
        array (
          'type' => 'float',
          'readOnly' => true,
          'notStorable' => true,
          'select' => 'opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100',
          'where' => 
          array (
            '=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) = {value}',
            '<' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) < {value}',
            '>' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) > {value}',
            '<=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) <= {value}',
            '>=' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) >= {value}',
            '<>' => '(opportunity.amount * amount_currency_alias.rate * opportunity.probability / 100) <> {value}',
          ),
          'orderBy' => 'amountWeightedConverted {direction}',
          'view' => 'views/fields/currency-converted',
        ),
        'account' => 
        array (
          'type' => 'link',
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'crm:views/opportunity/fields/contacts',
          'columns' => 
          array (
            'role' => 'opportunityRole',
          ),
        ),
        'stage' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
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
          ),
          'view' => 'crm:views/opportunity/fields/stage',
          'default' => 'Prospecting',
          'audited' => true,
        ),
        'probability' => 
        array (
          'type' => 'int',
          'required' => true,
          'min' => 0,
          'max' => 100,
        ),
        'leadSource' => 
        array (
          'type' => 'enum',
          'view' => 'crm:views/opportunity/fields/lead-source',
          'customizationOptionsDisabled' => true,
          'default' => '',
        ),
        'closeDate' => 
        array (
          'type' => 'date',
          'required' => true,
          'audited' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'campaign' => 
        array (
          'type' => 'link',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => false,
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'amountCurrency' => 
        array (
          'type' => 'varchar',
          'disabled' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'opportunities',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'opportunities',
          'additionalColumns' => 
          array (
            'role' => 
            array (
              'type' => 'varchar',
              'len' => 50,
            ),
          ),
        ),
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
          'layoutRelationshipsDisabled' => true,
        ),
        'documents' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Document',
          'foreign' => 'opportunities',
        ),
        'campaign' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Campaign',
          'foreign' => 'opportunities',
          'noJoin' => true,
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'probabilityMap' => 
      array (
        'Prospecting' => 10,
        'Qualification' => 10,
        'Needs Analysis' => 20,
        'Value Proposition' => 50,
        'Id. Decision Makers' => 60,
        'Perception Analysis' => 70,
        'Proposal/Price Quote' => 75,
        'Negotiation/Review' => 90,
        'Closed Won' => 100,
        'Closed Lost' => 0,
      ),
      'indexes' => 
      array (
        'stage' => 
        array (
          'columns' => 
          array (
            0 => 'stage',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'createdAt' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'deleted',
          ),
        ),
        'createdAtStage' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'stage',
          ),
        ),
        'assignedUserStage' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'stage',
          ),
        ),
      ),
    ),
    'Reminder' => 
    array (
      'fields' => 
      array (
        'remindAt' => 
        array (
          'type' => 'datetime',
          'index' => true,
        ),
        'startAt' => 
        array (
          'type' => 'datetime',
          'index' => true,
        ),
        'type' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Popup',
            1 => 'Email',
          ),
          'maxLength' => 36,
          'index' => true,
          'default' => 'Popup',
        ),
        'seconds' => 
        array (
          'type' => 'enumInt',
          'options' => 
          array (
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
          ),
          'default' => 0,
        ),
        'entityType' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'entityId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 50,
        ),
        'userId' => 
        array (
          'type' => 'varchar',
          'maxLength' => 50,
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'remindAt',
        'asc' => false,
      ),
    ),
    'Target' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'personName',
        ),
        'salutationName' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
            5 => 'Drs.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'default' => '',
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
          'default' => '',
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'accountName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'website' => 
        array (
          'type' => 'url',
        ),
        'address' => 
        array (
          'type' => 'address',
        ),
        'addressStreet' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'addressCity' => 
        array (
          'type' => 'varchar',
        ),
        'addressState' => 
        array (
          'type' => 'varchar',
        ),
        'addressCountry' => 
        array (
          'type' => 'varchar',
        ),
        'addressPostalCode' => 
        array (
          'type' => 'varchar',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phoneNumber' => 
        array (
          'type' => 'phone',
          'typeList' => 
          array (
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other',
          ),
          'defaultType' => 'Mobile',
        ),
        'doNotCall' => 
        array (
          'type' => 'bool',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'addressMap' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'firstName' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'deleted',
          ),
        ),
        'name' => 
        array (
          'columns' => 
          array (
            0 => 'firstName',
            1 => 'lastName',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'TargetList' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'entryCount' => 
        array (
          'type' => 'int',
          'readOnly' => true,
          'notStorable' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'campaigns' => 
        array (
          'type' => 'link',
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'campaigns' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Campaign',
          'foreign' => 'targetLists',
        ),
        'massEmails' => 
        array (
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'targetLists',
        ),
        'campaignsExcluding' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Campaign',
          'foreign' => 'excludingTargetLists',
        ),
        'massEmailsExcluding' => 
        array (
          'type' => 'hasMany',
          'entity' => 'MassEmail',
          'foreign' => 'excludingTargetLists',
        ),
        'accounts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Account',
          'foreign' => 'targetLists',
          'additionalColumns' => 
          array (
            'optedOut' => 
            array (
              'type' => 'bool',
            ),
          ),
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'targetLists',
          'additionalColumns' => 
          array (
            'optedOut' => 
            array (
              'type' => 'bool',
            ),
          ),
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'targetLists',
          'additionalColumns' => 
          array (
            'optedOut' => 
            array (
              'type' => 'bool',
            ),
          ),
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'targetLists',
          'additionalColumns' => 
          array (
            'optedOut' => 
            array (
              'type' => 'bool',
            ),
          ),
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'createdAt' => 
        array (
          'columns' => 
          array (
            0 => 'createdAt',
            1 => 'deleted',
          ),
        ),
      ),
    ),
    'Task' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'trim' => true,
        ),
        'status' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Not Started',
            1 => 'Started',
            2 => 'Completed',
            3 => 'Canceled',
            4 => 'Deferred',
          ),
          'view' => 'views/fields/enum-styled',
          'style' => 
          array (
            'Completed' => 'success',
          ),
          'default' => 'Not Started',
          'audited' => true,
        ),
        'priority' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Low',
            1 => 'Normal',
            2 => 'High',
            3 => 'Urgent',
          ),
          'default' => 'Normal',
          'audited' => true,
        ),
        'dateStart' => 
        array (
          'type' => 'datetimeOptional',
          'before' => 'dateEnd',
        ),
        'dateEnd' => 
        array (
          'type' => 'datetimeOptional',
          'after' => 'dateStart',
          'view' => 'crm:views/task/fields/date-end',
          'audited' => true,
        ),
        'dateStartDate' => 
        array (
          'type' => 'date',
          'disabled' => true,
        ),
        'dateEndDate' => 
        array (
          'type' => 'date',
          'disabled' => true,
        ),
        'dateCompleted' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'isOverdue' => 
        array (
          'type' => 'bool',
          'readOnly' => true,
          'notStorable' => true,
          'view' => 'crm:views/task/fields/is-overdue',
          'disabled' => true,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
          'entityList' => 
          array (
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'Case',
          ),
        ),
        'account' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'createdAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'modifiedAt' => 
        array (
          'type' => 'datetime',
          'readOnly' => true,
        ),
        'createdBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
          'view' => 'views/fields/user',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'view' => 'views/fields/assigned-user',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'views/fields/teams',
        ),
        'attachments' => 
        array (
          'type' => 'attachmentMultiple',
          'sourceList' => 
          array (
            0 => 'Document',
          ),
          'layoutListDisabled' => true,
        ),
      ),
      'links' => 
      array (
        'createdBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'modifiedBy' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'assignedUser' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
          'relationName' => 'entityTeam',
          'layoutRelationshipsDisabled' => true,
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'tasks',
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
      'indexes' => 
      array (
        'dateStartStatus' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'status',
          ),
        ),
        'dateEndStatus' => 
        array (
          'columns' => 
          array (
            0 => 'dateEnd',
            1 => 'status',
          ),
        ),
        'dateStart' => 
        array (
          'columns' => 
          array (
            0 => 'dateStart',
            1 => 'deleted',
          ),
        ),
        'status' => 
        array (
          'columns' => 
          array (
            0 => 'status',
            1 => 'deleted',
          ),
        ),
        'assignedUser' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'deleted',
          ),
        ),
        'assignedUserStatus' => 
        array (
          'columns' => 
          array (
            0 => 'assignedUserId',
            1 => 'status',
          ),
        ),
      ),
    ),
  ),
  'fields' => 
  array (
    'address' => 
    array (
      'actualFields' => 
      array (
        0 => 'street',
        1 => 'city',
        2 => 'state',
        3 => 'country',
        4 => 'postalCode',
      ),
      'fields' => 
      array (
        'street' => 
        array (
          'type' => 'text',
          'maxLength' => 255,
          'dbType' => 'varchar',
        ),
        'city' => 
        array (
          'type' => 'varchar',
        ),
        'state' => 
        array (
          'type' => 'varchar',
        ),
        'country' => 
        array (
          'type' => 'varchar',
        ),
        'postalCode' => 
        array (
          'type' => 'varchar',
        ),
        'map' => 
        array (
          'type' => 'map',
          'notStorable' => true,
          'readOnly' => true,
          'layoutListDisabled' => true,
          'provider' => 'Google',
          'height' => 300,
        ),
      ),
      'notMergeable' => true,
      'notCreatable' => false,
      'filter' => true,
      'fieldDefs' => 
      array (
        'skipOrmDefs' => true,
      ),
    ),
    'array' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options',
        ),
        2 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true,
        ),
        3 => 
        array (
          'name' => 'noEmptyString',
          'type' => 'bool',
          'default' => false,
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'notCreatable' => false,
      'fieldDefs' => 
      array (
        'type' => 'jsonArray',
      ),
      'translatedOptions' => true,
    ),
    'arrayInt' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'options',
          'type' => 'arrayInt',
        ),
        2 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true,
        ),
        3 => 
        array (
          'name' => 'noEmptyString',
          'type' => 'bool',
          'default' => false,
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'type' => 'jsonArray',
      ),
    ),
    'attachmentMultiple' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'sourceList',
          'type' => 'multiEnum',
          'view' => 'views/admin/field-manager/fields/source-list',
        ),
      ),
      'actualFields' => 
      array (
        0 => 'ids',
      ),
      'notActualFields' => 
      array (
        0 => 'names',
      ),
      'linkDefs' => 
      array (
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreign' => 'parent',
        'layoutRelationshipsDisabled' => true,
        'relationName' => 'attachments',
      ),
      'filter' => false,
      'fieldDefs' => 
      array (
        'layoutListDisabled' => true,
      ),
    ),
    'autoincrement' => 
    array (
      'params' => 
      array (
      ),
      'notCreatable' => false,
      'filter' => true,
      'fieldDefs' => 
      array (
        'type' => 'int',
        'autoincrement' => true,
        'unique' => true,
      ),
    ),
    'base' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
        ),
      ),
      'filter' => false,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'bool' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'default',
          'type' => 'bool',
        ),
        1 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
    ),
    'currency' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'min',
          'type' => 'float',
        ),
        2 => 
        array (
          'name' => 'max',
          'type' => 'float',
        ),
        3 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'actualFields' => 
      array (
        0 => 'currency',
        1 => '',
      ),
      'fields' => 
      array (
        'currency' => 
        array (
          'type' => 'varchar',
          'disabled' => true,
        ),
        'converted' => 
        array (
          'type' => 'currencyConverted',
          'readOnly' => true,
        ),
      ),
      'filter' => true,
    ),
    'currencyConverted' => 
    array (
      'params' => 
      array (
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'skipOrmDefs' => true,
      ),
    ),
    'date' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/default',
          'trim' => true,
        ),
        2 => 
        array (
          'name' => 'after',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'before',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'notNull' => false,
      ),
    ),
    'datetime' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/default',
          'trim' => true,
        ),
        2 => 
        array (
          'name' => 'after',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'before',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'notNull' => false,
      ),
    ),
    'datetimeOptional' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/date/default',
          'trim' => true,
        ),
        2 => 
        array (
          'name' => 'after',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'before',
          'type' => 'varchar',
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'actualFields' => 
      array (
        0 => '',
        1 => 'date',
      ),
      'fields' => 
      array (
        'date' => 
        array (
          'type' => 'date',
          'disabled' => true,
        ),
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'type' => 'datetime',
        'notNull' => false,
      ),
      'view' => 'Fields.DatetimeOptional',
    ),
    'duration' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'default',
          'type' => 'int',
        ),
        1 => 
        array (
          'name' => 'options',
          'type' => 'arrayInt',
        ),
      ),
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'type' => 'int',
      ),
    ),
    'email' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
      ),
      'notActualFields' => 
      array (
        0 => 'data',
      ),
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'enum' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options',
        ),
        2 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'isSorted',
          'type' => 'bool',
        ),
        4 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true,
        ),
        5 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'type' => 'varchar',
      ),
      'translatedOptions' => true,
    ),
    'enumFloat' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'options',
          'type' => 'array',
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'float',
        ),
        2 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'type' => 'float',
      ),
    ),
    'enumInt' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'options',
          'type' => 'array',
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'int',
        ),
        2 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'type' => 'int',
      ),
    ),
    'file' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
      ),
      'actualFields' => 
      array (
        0 => 'id',
      ),
      'notActualFields' => 
      array (
        0 => 'name',
      ),
      'filter' => false,
      'linkDefs' => 
      array (
        'type' => 'belongsTo',
        'entity' => 'Attachment',
      ),
      'fieldDefs' => 
      array (
        'skipOrmDefs' => true,
      ),
    ),
    'float' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'float',
        ),
        2 => 
        array (
          'name' => 'min',
          'type' => 'float',
        ),
        3 => 
        array (
          'name' => 'max',
          'type' => 'float',
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'notNull' => false,
      ),
    ),
    'foreign' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'link',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/foreign/link',
          'required' => true,
        ),
        1 => 
        array (
          'name' => 'field',
          'type' => 'varchar',
          'view' => 'views/admin/field-manager/fields/foreign/field',
          'required' => true,
        ),
      ),
      'filter' => true,
      'notCreatable' => false,
      'fieldDefs' => 
      array (
        'readOnly' => true,
      ),
    ),
    'image' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'previewSize',
          'type' => 'enum',
          'default' => 'small',
          'options' => 
          array (
            0 => 'x-small',
            1 => 'small',
            2 => 'medium',
            3 => 'large',
          ),
        ),
        2 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'actualFields' => 
      array (
        0 => 'id',
      ),
      'notActualFields' => 
      array (
        0 => 'name',
      ),
      'filter' => false,
      'linkDefs' => 
      array (
        'type' => 'belongsTo',
        'entity' => 'Attachment',
      ),
      'fieldDefs' => 
      array (
        'skipOrmDefs' => true,
      ),
    ),
    'int' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'int',
        ),
        2 => 
        array (
          'name' => 'min',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'max',
          'type' => 'int',
        ),
        4 => 
        array (
          'name' => 'disableFormatting',
          'type' => 'bool',
        ),
        5 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
    ),
    'link' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'actualFields' => 
      array (
        0 => 'id',
      ),
      'notActualFields' => 
      array (
        0 => 'name',
      ),
      'filter' => true,
      'notCreatable' => true,
    ),
    'linkMultiple' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'sortable',
          'type' => 'bool',
          'default' => false,
          'hidden' => true,
        ),
      ),
      'actualFields' => 
      array (
        0 => 'ids',
      ),
      'notActualFields' => 
      array (
        0 => 'names',
      ),
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => 
      array (
        'layoutListDisabled' => true,
      ),
    ),
    'linkParent' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'entityList',
          'type' => 'multiEnum',
          'view' => 'Admin.FieldManager.Fields.EntityList',
        ),
        2 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'actualFields' => 
      array (
        0 => 'id',
        1 => 'type',
      ),
      'notActualFields' => 
      array (
        0 => 'name',
      ),
      'filter' => true,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'map' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'provider',
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Google',
          ),
          'default' => 'Google',
        ),
        1 => 
        array (
          'name' => 'height',
          'type' => 'int',
          'default' => 300,
        ),
      ),
      'filter' => false,
      'notCreatable' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'multiEnum' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'options',
          'type' => 'array',
          'view' => 'views/admin/field-manager/fields/options',
        ),
        2 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
          'hidden' => true,
        ),
        3 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'notCreatable' => false,
      'fieldDefs' => 
      array (
        'type' => 'jsonArray',
      ),
      'translatedOptions' => true,
    ),
    'number' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'prefix',
          'type' => 'varchar',
          'maxLength' => 16,
        ),
        1 => 
        array (
          'name' => 'nextNumber',
          'type' => 'int',
          'min' => 0,
          'required' => true,
          'default' => 1,
        ),
        2 => 
        array (
          'name' => 'padLength',
          'type' => 'int',
          'default' => 5,
          'required' => true,
          'min' => 1,
          'max' => 20,
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'type' => 'varchar',
        'len' => 36,
        'notNull' => false,
        'unique' => false,
      ),
      'hookClassName' => '\\Espo\\Core\\Utils\\FieldManager\\Hooks\\NumberType',
    ),
    'password' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
      ),
      'notCreatable' => true,
      'filter' => false,
    ),
    'personName' => 
    array (
      'actualFields' => 
      array (
        0 => 'salutation',
        1 => 'first',
        2 => 'last',
      ),
      'fields' => 
      array (
        'salutation' => 
        array (
          'type' => 'enum',
        ),
        'first' => 
        array (
          'type' => 'varchar',
        ),
        'last' => 
        array (
          'type' => 'varchar',
        ),
      ),
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'phone' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'typeList',
          'type' => 'array',
          'default' => 
          array (
            0 => 'Mobile',
            1 => 'Office',
            2 => 'Home',
            3 => 'Fax',
            4 => 'Other',
          ),
          'view' => 'views/admin/field-manager/fields/options',
        ),
        2 => 
        array (
          'name' => 'defaultType',
          'type' => 'varchar',
          'default' => 'Mobile',
        ),
      ),
      'notActualFields' => 
      array (
        0 => 'data',
      ),
      'notCreatable' => true,
      'filter' => true,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
      'translatedOptions' => true,
    ),
    'rangeCurrency' => 
    array (
      'actualFields' => 
      array (
        0 => 'from',
        1 => 'to',
      ),
      'fields' => 
      array (
        'from' => 
        array (
          'type' => 'currency',
        ),
        'to' => 
        array (
          'type' => 'currency',
        ),
      ),
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'rangeFloat' => 
    array (
      'actualFields' => 
      array (
        0 => 'from',
        1 => 'to',
      ),
      'fields' => 
      array (
        'from' => 
        array (
          'type' => 'float',
        ),
        'to' => 
        array (
          'type' => 'float',
        ),
      ),
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'rangeInt' => 
    array (
      'actualFields' => 
      array (
        0 => 'from',
        1 => 'to',
      ),
      'fields' => 
      array (
        'from' => 
        array (
          'type' => 'int',
        ),
        'to' => 
        array (
          'type' => 'int',
        ),
      ),
      'naming' => 'prefix',
      'notMergeable' => true,
      'notCreatable' => true,
      'filter' => false,
      'fieldDefs' => 
      array (
        'notStorable' => true,
      ),
    ),
    'text' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'text',
        ),
        2 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'seeMoreDisabled',
          'type' => 'bool',
        ),
        4 => 
        array (
          'name' => 'rows',
          'type' => 'int',
          'default' => 4,
          'min' => 1,
        ),
        5 => 
        array (
          'name' => 'lengthOfCut',
          'type' => 'int',
          'default' => 400,
          'min' => 1,
        ),
      ),
      'filter' => true,
    ),
    'url' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'type' => 'varchar',
      ),
    ),
    'varchar' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'trim',
          'type' => 'bool',
          'default' => true,
        ),
        4 => 
        array (
          'name' => 'audited',
          'type' => 'bool',
        ),
      ),
      'filter' => true,
    ),
    'wysiwyg' => 
    array (
      'params' => 
      array (
        0 => 
        array (
          'name' => 'required',
          'type' => 'bool',
          'default' => false,
        ),
        1 => 
        array (
          'name' => 'default',
          'type' => 'text',
        ),
        2 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
        ),
        3 => 
        array (
          'name' => 'seeMoreDisabled',
          'type' => 'bool',
        ),
        4 => 
        array (
          'name' => 'height',
          'type' => 'int',
        ),
        5 => 
        array (
          'name' => 'minHeight',
          'type' => 'int',
        ),
      ),
      'filter' => true,
      'fieldDefs' => 
      array (
        'type' => 'text',
      ),
    ),
  ),
  'integrations' => 
  array (
    'GoogleMaps' => 
    array (
      'fields' => 
      array (
        'apiKey' => 
        array (
          'type' => 'varchar',
          'maxLength' => 255,
          'required' => true,
        ),
      ),
      'allowUserAccounts' => false,
      'view' => 'views/admin/integrations/google-maps',
      'authMethod' => 'GoogleMaps',
    ),
  ),
  'scopes' => 
  array (
    'Attachment' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'AuthToken' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Currency' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Dashboard' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Email' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactOwnNo',
      'notifications' => true,
      'object' => true,
      'customizable' => true,
      'activity' => true,
    ),
    'EmailAccount' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
    ),
    'EmailAccountScope' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean',
    ),
    'EmailAddress' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'EmailFilter' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false,
    ),
    'EmailTemplate' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'customizable' => false,
    ),
    'Extension' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'ExternalAccount' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean',
      'aclPortal' => false,
      'customizable' => false,
    ),
    'Import' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'InboundEmail' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
    ),
    'Integration' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Job' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Note' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Notification' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'PasswordChangeRequest' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'PhoneNumber' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Portal' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Preferences' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Role' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'ScheduledJob' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'ScheduledJobLogRecord' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Stream' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => true,
      'acl' => false,
      'customizable' => false,
    ),
    'Team' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'Template' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => 'recordAllTeamNo',
      'customizable' => true,
      'disabled' => true,
    ),
    'UniqueId' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'User' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => false,
      'customizable' => true,
      'object' => true,
    ),
    'Account' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
    ),
    'Activities' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => 'boolean',
      'aclPortal' => 'boolean',
      'module' => 'Crm',
      'customizable' => false,
    ),
    'Calendar' => 
    array (
      'entity' => false,
      'tab' => true,
      'acl' => 'boolean',
      'module' => 'Crm',
    ),
    'Call' => 
    array (
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
    ),
    'Campaign' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => false,
      'importable' => false,
      'object' => true,
    ),
    'CampaignLogRecord' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'stream' => false,
      'importable' => false,
    ),
    'CampaignTrackingUrl' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'stream' => false,
      'importable' => false,
    ),
    'Case' => 
    array (
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
    ),
    'Contact' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountContactNo',
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
      'importable' => true,
      'notifications' => true,
      'object' => true,
    ),
    'Document' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'aclPortal' => 'recordAllAccountOwnNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => false,
      'notifications' => true,
      'object' => true,
    ),
    'DocumentFolder' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => 'recordAllTeamNo',
      'aclPortal' => 'recordAllNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => false,
      'type' => 'CategoryTree',
      'stream' => false,
      'notifications' => false,
    ),
    'EmailQueueItem' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false,
      'module' => 'Crm',
    ),
    'KnowledgeBaseArticle' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => 'recordAllTeamNo',
      'aclPortal' => 'recordAllNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => true,
      'notifications' => false,
      'object' => true,
    ),
    'KnowledgeBaseCategory' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => 'recordAllTeamNo',
      'aclPortal' => 'recordAllNo',
      'module' => 'Crm',
      'customizable' => true,
      'importable' => false,
      'type' => 'CategoryTree',
      'stream' => false,
      'notifications' => false,
    ),
    'Lead' => 
    array (
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
    ),
    'MassEmail' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'notifications' => false,
      'object' => false,
      'customizable' => false,
      'module' => 'Crm',
    ),
    'Meeting' => 
    array (
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
    ),
    'Opportunity' => 
    array (
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
    ),
    'Reminder' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false,
    ),
    'Target' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
      'importable' => false,
      'notifications' => false,
      'object' => true,
    ),
    'TargetList' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => false,
      'stream' => false,
      'importable' => false,
      'notifications' => true,
      'object' => true,
    ),
    'Task' => 
    array (
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
      'object' => true,
    ),
  ),
  'themes' => 
  array (
    'Espo' => 
    array (
      'stylesheet' => 'client/css/espo.css',
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
      'navbarHeight' => 44,
    ),
    'EspoVertical' => 
    array (
      'stylesheet' => 'client/css/espo-vertical.css',
      'navbarIsVertical' => true,
      'navbarStaticItemsHeight' => 65,
      'recordTopButtonsStickTop' => 61,
      'recordTopButtonsBlockHeight' => 21,
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
    ),
    'Sakura' => 
    array (
      'stylesheet' => 'client/css/sakura.css',
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
      'navbarHeight' => 44,
    ),
    'SakuraVertical' => 
    array (
      'stylesheet' => 'client/css/sakura-vertical.css',
      'navbarIsVertical' => true,
      'navbarStaticItemsHeight' => 65,
      'recordTopButtonsStickTop' => 61,
      'recordTopButtonsBlockHeight' => 21,
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
    ),
    'Violet' => 
    array (
      'stylesheet' => 'client/css/violet.css',
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
      'navbarHeight' => 44,
    ),
    'VioletVertical' => 
    array (
      'stylesheet' => 'client/css/violet-vertical.css',
      'navbarIsVertical' => true,
      'navbarStaticItemsHeight' => 65,
      'recordTopButtonsStickTop' => 61,
      'recordTopButtonsBlockHeight' => 21,
      'dashboardCellHeight' => 155,
      'dashboardCellMargin' => 19,
    ),
  ),
);
?>