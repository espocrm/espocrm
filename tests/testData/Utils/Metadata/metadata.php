<?php

return array (
  'app' => 
  array (
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
            'description' => 'System settings of application.',
          ),
          1 => 
          array (
            'url' => '#ScheduledJob',
            'label' => 'Scheduled Jobs',
            'description' => 'Jobs which are executed by cron.',
          ),
          2 => 
          array (
            'url' => '#Admin/clearCache',
            'label' => 'Clear Cache',
            'description' => 'Clear all backend cache.',
          ),
          3 => 
          array (
            'url' => '#Admin/rebuild',
            'label' => 'Rebuild',
            'description' => 'Rebuild backend and clear cache.',
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
            'description' => 'Users management.',
          ),
          1 => 
          array (
            'url' => '#Team',
            'label' => 'Teams',
            'description' => 'Teams management.',
          ),
          2 => 
          array (
            'url' => '#Role',
            'label' => 'Roles',
            'description' => 'Roles management.',
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
            'url' => '#Admin/outboundEmail',
            'label' => 'Outbound Emails',
            'description' => 'SMTP settings for outgoing emails.',
          ),
          1 => 
          array (
            'url' => '#InboundEmail',
            'label' => 'Inbound Emails',
            'description' => 'Group IMAP email accouts. Email import and Email-to-Case.',
          ),
          2 => 
          array (
            'url' => '#EmailTemplate',
            'label' => 'Email Templates',
            'description' => 'Templates for outbound emails.',
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
            'url' => '#Admin/import',
            'label' => 'Import',
            'description' => 'Import data from CSV file.',
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
            'description' => 'Customize layouts (list, detail, edit, search, mass update).',
          ),
          1 => 
          array (
            'url' => '#Admin/fields',
            'label' => 'Field Manager',
            'description' => 'Create new fields or customize existing ones.',
          ),
          2 => 
          array (
            'url' => '#Admin/userInterface',
            'label' => 'User Interface',
            'description' => 'Configure UI.',
          ),
        ),
      ),
    ),
    'defaultDashboardLayout' => 
    array (
      0 => 
      array (
        0 => 
        array (
          'name' => 'Stream',
          'id' => 'd4',
        ),
        1 => 
        array (
          'name' => 'Calendar',
          'id' => 'd1',
        ),
      ),
      1 => 
      array (
        0 => 
        array (
          'name' => 'Tasks',
          'id' => 'd3',
        ),
      ),
    ),
  ),
  'customTest' => 
  array (
    'CustomTest' => 
    array (
      'name' => 'CustomTestModuleName',
      'var1' => 
      array (
        'subvar1' => 'NEWsubval1',
        'subvar2' => 'subval2',
        'subvar55' => 'subval55',
      ),
      'module' => 'Test',
    ),
  ),
  'dashlets' => 
  array (
    'Stream' => 
    array (
      'module' => false,
    ),
    'Calendar' => 
    array (
      'module' => 'Crm',
    ),
    'Cases' => 
    array (
      'module' => 'Crm',
    ),
    'Leads' => 
    array (
      'module' => 'Crm',
    ),
    'Opportunities' => 
    array (
      'module' => 'Crm',
    ),
    'OpportunitiesByLeadSource' => 
    array (
      'module' => 'Crm',
    ),
    'OpportunitiesByStage' => 
    array (
      'module' => 'Crm',
    ),
    'SalesByMonth' => 
    array (
      'module' => 'Crm',
    ),
    'SalesPipeline' => 
    array (
      'module' => 'Crm',
    ),
    'Tasks' => 
    array (
      'module' => 'Crm',
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
        ),
        'type' => 
        array (
          'type' => 'varchar',
          'maxLength' => 36,
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
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'attachments',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
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
        ),
        'subject' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'db' => false,
        ),
        'fromName' => 
        array (
          'type' => 'varchar',
        ),
        'from' => 
        array (
          'type' => 'varchar',
          'db' => false,
          'required' => true,
        ),
        'to' => 
        array (
          'type' => 'varchar',
          'db' => false,
          'required' => true,
        ),
        'cc' => 
        array (
          'type' => 'varchar',
          'db' => false,
        ),
        'bcc' => 
        array (
          'type' => 'varchar',
          'db' => false,
        ),
        'bodyPlain' => 
        array (
          'type' => 'text',
          'readOnly' => true,
        ),
        'body' => 
        array (
          'type' => 'text',
          'view' => 'Fields.Wysiwyg',
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
          ),
          'readOnly' => true,
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'Fields.AttachmentMultiple',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'dateSent' => 
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'attachments' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Attachment',
          'foreign' => 'parent',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entities' => 
          array (
            0 => 'Account',
            1 => 'Opportunity',
            2 => 'Case',
          ),
          'foreign' => 'emails',
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
          'relationName' => 'EmailEmailAddress',
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
          'relationName' => 'EmailEmailAddress',
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
          'relationName' => 'EmailEmailAddress',
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
      ),
      'collection' => 
      array (
        'sortBy' => 'dateSent',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
        ),
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
    'EmailTemplate' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'subject' => 
        array (
          'type' => 'varchar',
        ),
        'body' => 
        array (
          'type' => 'text',
          'view' => 'Fields.Wysiwyg',
        ),
        'isHtml' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
          'view' => 'Fields.AttachmentMultiple',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
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
          'relationName' => 'EntityTeam',
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
        'sortBy' => 'name',
        'asc' => true,
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
          'len' => 100,
        ),
        'method' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'len' => 100,
        ),
        'data' => 
        array (
          'type' => 'text',
        ),
        'scheduledJob' => 
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
    ),
    'Note' => 
    array (
      'fields' => 
      array (
        'message' => 
        array (
          'type' => 'text',
        ),
        'data' => 
        array (
          'type' => 'text',
        ),
        'type' => 
        array (
          'type' => 'varchar',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'attachments' => 
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
          'foreign' => 'parent',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'foreign' => 'notes',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'Notification' => 
    array (
      'fields' => 
      array (
        'data' => 
        array (
          'type' => 'text',
        ),
        'type' => 
        array (
          'type' => 'varchar',
        ),
        'read' => 
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
      ),
      'links' => 
      array (
        'user' => 
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
    'OutboundEmail' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'server' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'port' => 
        array (
          'type' => 'int',
          'required' => true,
          'min' => 0,
          'max' => 9999,
          'default' => 25,
        ),
        'auth' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'security' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'SSL',
            2 => 'TLS',
          ),
        ),
        'username' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'password' => 
        array (
          'type' => 'password',
        ),
        'fromName' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'fromAddress' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'user' => 
        array (
          'type' => 'link',
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
    'Preferences' => 
    array (
      'fields' => 
      array (
        'timeZone' => 
        array (
          'type' => 'enum',
          'detault' => 'UTC',
        ),
        'dateFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'MM/DD/YYYY',
            1 => 'YYYY-MM-DD',
            2 => 'DD.MM.YYYY',
          ),
          'default' => 'MM/DD/YYYY',
        ),
        'timeFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'HH:mm',
            1 => 'hh:mm A',
            2 => 'hh:mm a',
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
        ),
        'decimalMark' => 
        array (
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
        ),
        'defaultCurrency' => 
        array (
          'type' => 'enum',
          'default' => 'USD',
        ),
        'dashboardLayout' => 
        array (
          'type' => 'text',
        ),
        'dashletOptions' => 
        array (
          'type' => 'text',
        ),
        'smtpServer' => 
        array (
          'type' => 'varchar',
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
        ),
        'smtpPassword' => 
        array (
          'type' => 'password',
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
        ),
        'data' => 
        array (
          'type' => 'text',
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
        ),
        'recordsPerPageSmall' => 
        array (
          'type' => 'int',
          'minValue' => 1,
          'maxValue' => 100,
          'default' => 10,
          'required' => true,
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
          ),
          'default' => 'MM/DD/YYYY',
        ),
        'timeFormat' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'HH:mm',
            1 => 'hh:mm A',
            2 => 'hh:mm a',
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
        ),
        'decimalMark' => 
        array (
          'type' => 'varchar',
          'default' => '.',
          'required' => true,
        ),
        'currencyList' => 
        array (
          'type' => 'array',
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
        ),
        'outboundEmailIsShared' => 
        array (
          'type' => 'bool',
          'default' => false,
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
        ),
        'smtpServer' => 
        array (
          'type' => 'varchar',
          'required' => true,
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
          'default' => 
          array (
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
          ),
          'translation' => 'App.scopeNamesPlural',
        ),
        'quickCreateList' => 
        array (
          'type' => 'array',
          'default' => 
          array (
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'Meeting',
            5 => 'Call',
            6 => 'Task',
            7 => 'Case',
            8 => 'Prospect',
          ),
          'translation' => 'App.scopeNames',
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
        ),
        'roles' => 
        array (
          'type' => 'linkMultiple',
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
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
      ),
    ),
    'User' => 
    array (
      'fields' => 
      array (
        'isAdmin' => 
        array (
          'type' => 'bool',
        ),
        'userName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 50,
          'required' => true,
          'unique' => true,
        ),
        'name' => 
        array (
          'type' => 'personName',
        ),
        'password' => 
        array (
          'type' => 'password',
        ),
        'salutationName' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Dr.',
            4 => 'Drs.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
          'required' => false,
        ),
        'phone' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'defaultTeam' => 
        array (
          'type' => 'link',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
        ),
        'roles' => 
        array (
          'type' => 'linkMultiple',
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
        ),
        'roles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Role',
          'foreign' => 'users',
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
      ),
      'collection' => 
      array (
        'sortBy' => 'userName',
        'asc' => true,
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
        ),
        'website' => 
        array (
          'type' => 'url',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phone' => 
        array (
          'type' => 'phone',
        ),
        'fax' => 
        array (
          'type' => 'phone',
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
        ),
        'industry' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => '',
            1 => 'Apparel',
            2 => 'Banking',
            3 => 'Education',
            4 => 'Electronics',
            5 => 'Finance',
            6 => 'Insurance',
            7 => 'IT',
          ),
        ),
        'sicCode' => 
        array (
          'type' => 'varchar',
          'maxLength' => 40,
        ),
        'billingAddress' => 
        array (
          'type' => 'address',
        ),
        'billingAddressStreet' => 
        array (
          'type' => 'varchar',
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
        ),
        'shippingAddressStreet' => 
        array (
          'type' => 'varchar',
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'account',
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
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
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
          'view' => 'Fields.EnumStyled',
          'style' => 
          array (
            'Held' => 'success',
            'Not Held' => 'danger',
          ),
        ),
        'dateStart' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
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
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'calls',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'calls',
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'calls',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entities' => 
          array (
            0 => 'Account',
            1 => 'Opportunity',
            2 => 'Case',
          ),
          'foreign' => 'calls',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'dateStart',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
        ),
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
        ),
        'number' => 
        array (
          'type' => 'autoincrement',
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
          'view' => 'Fields.EnumStyled',
          'style' => 
          array (
            'Closed' => 'success',
            'Duplicate' => 'danger',
            'Rejected' => 'danger',
          ),
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
        'contact' => 
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
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
          'foreign' => 'cases',
        ),
        'contact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
          'foreign' => 'cases',
        ),
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'number',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
          1 => 'open',
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
            3 => 'Dr.',
            4 => 'Drs.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
        ),
        'title' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'emailAddress' => 
        array (
          'type' => 'email',
        ),
        'phone' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'fax' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'doNotCall' => 
        array (
          'type' => 'bool',
        ),
        'phoneOffice' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'address' => 
        array (
          'type' => 'address',
        ),
        'addressStreet' => 
        array (
          'type' => 'varchar',
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
        'accountType' => 
        array (
          'type' => 'foreign',
          'link' => 'account',
          'field' => 'type',
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'account' => 
        array (
          'type' => 'belongsTo',
          'jointTable' => true,
          'entity' => 'Account',
        ),
        'opportunities' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Opportunity',
          'foreign' => 'contacts',
        ),
        'cases' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Case',
          'foreign' => 'contact',
        ),
        'meetings' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Meeting',
          'foreign' => 'contacts',
        ),
        'calls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'contacts',
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
        ),
        'activities' => 
        array (
          'type' => 'joint',
          'links' => 
          array (
            0 => 'meetings',
            1 => 'calls',
            2 => 'tasks',
          ),
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
        ),
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
        ),
        'port' => 
        array (
          'type' => 'varchar',
          'default' => '143',
          'required' => true,
        ),
        'username' => 
        array (
          'type' => 'varchar',
          'required' => true,
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
        ),
        'trashFolder' => 
        array (
          'type' => 'varchar',
          'required' => true,
          'default' => 'INBOX.Trash',
        ),
        'assignToUser' => 
        array (
          'type' => 'link',
        ),
        'team' => 
        array (
          'type' => 'link',
        ),
        'createCase' => 
        array (
          'type' => 'bool',
        ),
        'caseDistribution' => 
        array (
          'type' => 'enum',
          'options' => 
          array (
            0 => 'Direct-Assignment',
            1 => 'Round-Robin',
            2 => 'Least-Busy',
          ),
          'default' => 'Direct-Assignment',
        ),
        'reply' => 
        array (
          'type' => 'bool',
        ),
        'replyEmailTemplate' => 
        array (
          'type' => 'link',
        ),
        'replyFromAddress' => 
        array (
          'type' => 'varchar',
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
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
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
            3 => 'Dr.',
            4 => 'Drs.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
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
          'view' => 'Fields.EnumStyled',
          'style' => 
          array (
            'Converted' => 'success',
            'Recycled' => 'danger',
            'Dead' => 'danger',
          ),
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
        ),
        'opportunityAmount' => 
        array (
          'type' => 'currency',
          'audited' => true,
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
          'type' => 'varchar',
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
        'phone' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'fax' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'phoneOffice' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
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
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'accountName' => 
        array (
          'type' => 'varchar',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
        ),
        'createdAccount' => 
        array (
          'type' => 'link',
          'disabled' => true,
          'readOnly' => true,
        ),
        'createdContact' => 
        array (
          'type' => 'link',
          'disabled' => true,
          'readOnly' => true,
        ),
        'createdOpportunity' => 
        array (
          'type' => 'link',
          'disabled' => true,
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
          'relationName' => 'EntityTeam',
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
        ),
        'calls' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Call',
          'foreign' => 'leads',
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
        ),
        'createdAccount' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
        'createdContact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
        ),
        'createdOpportunity' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Opportunity',
        ),
      ),
      'convertFields' => 
      array (
        'Contact' => 
        array (
          'name' => 'name',
          'title' => 'title',
          'emailAddress' => 'emailAddress',
          'phone' => 'phone',
          'address' => 'address',
          'assignedUser' => 'assignedUser',
          'teams' => 'teams',
        ),
        'Account' => 
        array (
          'name' => 'accountName',
          'website' => 'website',
          'emailAddress' => 'emailAddress',
          'phone' => 'phoneOffice',
          'assignedUser' => 'assignedUser',
          'teams' => 'teams',
        ),
        'Opportunity' => 
        array (
          'amount' => 'opportunityAmount',
          'leadSource' => 'source',
          'assignedUser' => 'assignedUser',
          'teams' => 'teams',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
        ),
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
          'view' => 'Fields.EnumStyled',
          'style' => 
          array (
            'Held' => 'success',
            'Not Held' => 'danger',
          ),
        ),
        'dateStart' => 
        array (
          'type' => 'datetime',
          'required' => true,
          'default' => 'javascript: return this.dateTime.getNow(15);',
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
            0 => 0,
            1 => 900,
            2 => 1800,
            3 => 3600,
            4 => 7200,
            5 => 10800,
            6 => 86400,
          ),
          'default' => 3600,
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'users' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
          'foreign' => 'meetings',
        ),
        'contacts' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Contact',
          'foreign' => 'meetings',
        ),
        'leads' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Lead',
          'foreign' => 'meetings',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entities' => 
          array (
            0 => 'Account',
            1 => 'Opportunity',
            2 => 'Case',
          ),
          'foreign' => 'meetings',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'dateStart',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
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
        ),
        'amount' => 
        array (
          'type' => 'currency',
          'required' => true,
          'audited' => true,
        ),
        'account' => 
        array (
          'type' => 'link',
          'required' => true,
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
          'view' => 'Crm:Opportunity.Fields.Stage',
          'default' => 'Prospecting',
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
          'options' => 
          array (
            0 => 'Other',
            1 => 'Call',
            2 => 'Email',
            3 => 'Existing Customer',
            4 => 'Partner',
            5 => 'Public Relations',
            6 => 'Web Site',
            7 => 'Campaign',
          ),
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
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
        ),
        'meetings' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Meeting',
          'foreign' => 'parent',
        ),
        'calls' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Call',
          'foreign' => 'parent',
        ),
        'tasks' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Task',
          'foreign' => 'parent',
        ),
        'emails' => 
        array (
          'type' => 'hasChildren',
          'entity' => 'Email',
          'foreign' => 'parent',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
          1 => 'open',
        ),
      ),
    ),
    'Prospect' => 
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
            3 => 'Dr.',
            4 => 'Drs.',
          ),
        ),
        'firstName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
        ),
        'lastName' => 
        array (
          'type' => 'varchar',
          'maxLength' => 100,
          'required' => true,
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
          'type' => 'varchar',
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
        'phone' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'fax' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
        ),
        'phoneOffice' => 
        array (
          'type' => 'phone',
          'maxLength' => 50,
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
        ),
        'modifiedBy' => 
        array (
          'type' => 'link',
          'readOnly' => true,
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
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
          ),
          'view' => 'Fields.EnumStyled',
          'style' => 
          array (
            'Completed' => 'success',
            'Canceled' => 'danger',
          ),
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
        ),
        'dateStart' => 
        array (
          'type' => 'datetime',
          'before' => 'dateEnd',
        ),
        'dateEnd' => 
        array (
          'type' => 'datetime',
          'after' => 'dateStart',
        ),
        'isOverdue' => 
        array (
          'type' => 'base',
          'db' => false,
          'view' => 'Crm:Task.Fields.IsOverdue',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
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
          'required' => true,
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
          'relationName' => 'EntityTeam',
        ),
        'parent' => 
        array (
          'type' => 'belongsToParent',
          'entities' => 
          array (
            0 => 'Account',
            1 => 'Contact',
            2 => 'Lead',
            3 => 'Opportunity',
            4 => 'Case',
          ),
          'foreign' => 'tasks',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
        'boolFilters' => 
        array (
          0 => 'onlyMy',
          1 => 'active',
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
          'type' => 'varchar',
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
      ),
      'mergable' => false,
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
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
        ),
        2 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
        ),
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
      ),
      'database' => 
      array (
        'type' => 'json_array',
      ),
    ),
    'autoincrement' => 
    array (
      'params' => 
      array (
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'type' => 'int',
        'autoincrement' => true,
        'unique' => true,
      ),
    ),
    'base' => 
    array (
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
      ),
      'database' => 
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'type' => 'float',
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'notnull' => false,
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
      ),
      'database' => 
      array (
        'notnull' => false,
      ),
    ),
    'duration' => 
    array (
      'database' => 
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
        1 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
        ),
      ),
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
      ),
      'database' => 
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
        ),
        2 => 
        array (
          'name' => 'default',
          'type' => 'varchar',
        ),
        3 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
        ),
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'type' => 'varchar',
      ),
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
          'name' => 'translation',
          'type' => 'varchar',
        ),
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
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
          'name' => 'translation',
          'type' => 'varchar',
        ),
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'type' => 'int',
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'notnull' => false,
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
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
      ),
      'actualFields' => 
      array (
        0 => 'id',
      ),
      'notActualFields' => 
      array (
        0 => 'name',
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'skip' => true,
      ),
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
      ),
      'actualFields' => 
      array (
        0 => 'ids',
      ),
      'notActualFields' => 
      array (
        0 => 'names',
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
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
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
      ),
      'database' => 
      array (
        'notStorable' => true,
      ),
    ),
    'multienum' => 
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
        ),
        2 => 
        array (
          'name' => 'translation',
          'type' => 'varchar',
        ),
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
      ),
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
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
      ),
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
      'mergable' => false,
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
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
          'name' => 'default',
          'type' => 'varchar',
        ),
        2 => 
        array (
          'name' => 'maxLength',
          'type' => 'int',
          'defalut' => 50,
        ),
      ),
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
      ),
      'database' => 
      array (
        'type' => 'varchar',
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
      ),
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
      ),
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
      ),
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
      ),
      'database' => 
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
      ),
      'search' => 
      array (
        'basic' => true,
        'advanced' => true,
      ),
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
    'Currency' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'CustomTest' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => false,
      'acl' => false,
      'customizable' => true,
    ),
    'Email' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => true,
    ),
    'EmailAddress' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'customizable' => false,
    ),
    'EmailTemplate' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => true,
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
    'OutboundEmail' => 
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
      'tab' => false,
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
    'User' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => false,
      'acl' => false,
      'customizable' => true,
    ),
    'Account' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
    ),
    'Activities' => 
    array (
      'entity' => false,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
      'customizable' => false,
    ),
    'Calendar' => 
    array (
      'entity' => false,
      'tab' => true,
      'acl' => false,
      'module' => 'Crm',
    ),
    'Call' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
    ),
    'Case' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
    ),
    'Contact' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
    ),
    'InboundEmail' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => false,
      'module' => 'Crm',
    ),
    'Lead' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
    ),
    'Meeting' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
    ),
    'Opportunity' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
      'stream' => true,
    ),
    'Prospect' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
    ),
    'Task' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
    ),
  ),
  'viewDefs' => 
  array (
    'Note' => 
    array (
      'recordViews' => 
      array (
        'edit' => 'Note.Record.Edit',
        'editQuick' => 'Note.Record.Edit',
      ),
    ),
    'OutboundEmail' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'OutboundEmail.Record.Detail',
        'edit' => 'OutboundEmail.Record.Edit',
        'editQuick' => 'OutboundEmail.Record.Edit',
      ),
    ),
    'Preferences' => 
    array (
      'recordViews' => 
      array (
        'edit' => 'Preferences.Record.Edit',
      ),
      'ciews' => 
      array (
        'edit' => 'Preferences.Edit',
      ),
    ),
    'Role' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'Role.Record.Detail',
        'edit' => 'Role.Record.Edit',
        'editQuick' => 'Role.Record.Edit',
      ),
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
        ),
        'teams' => 
        array (
          'create' => false,
        ),
      ),
    ),
    'ScheduledJob' => 
    array (
      'relationshipPanels' => 
      array (
        'log' => 
        array (
          'readOnly' => true,
        ),
      ),
    ),
    'Team' => 
    array (
      'relationshipPanels' => 
      array (
        'users' => 
        array (
          'create' => false,
        ),
      ),
      'recordViews' => 
      array (
        'detail' => 'Team.Detail',
        'edit' => 'Team.Edit',
      ),
    ),
    'User' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'User.Record.Detail',
        'edit' => 'User.Record.Edit',
        'editQuick' => 'User.Record.Edit',
      ),
    ),
    'Account' => 
    array (
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'Crm:Record.Panels.Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'Crm:Record.Panels.History',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'Crm:Record.Panels.Tasks',
          ),
        ),
      ),
      'relationshipPanels' => 
      array (
        'contacts' => 
        array (
          'actions' => 
          array (
          ),
          'layout' => 'listSmall',
        ),
      ),
    ),
    'Call' => 
    array (
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'Record.Panels.Side',
            'options' => 
            array (
              'fields' => 
              array (
                0 => 'users',
                1 => 'contacts',
                2 => 'leads',
              ),
              'mode' => 'detail',
            ),
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'Record.Panels.Side',
            'options' => 
            array (
              'fields' => 
              array (
                0 => 'users',
                1 => 'contacts',
                2 => 'leads',
              ),
              'mode' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Case' => 
    array (
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
            'view' => 'Crm:Record.Panels.Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'Crm:Record.Panels.History',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'Crm:Record.Panels.Tasks',
          ),
        ),
      ),
    ),
    'Contact' => 
    array (
      'views' => 
      array (
        'detail' => 'Crm:Contact.Detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'Crm:Record.Panels.Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'Crm:Record.Panels.History',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'Crm:Record.Panels.Tasks',
          ),
        ),
      ),
    ),
    'InboundEmail' => 
    array (
      'recordViews' => 
      array (
        'detail' => 'Crm:InboundEmail.Record.Detail',
        'edit' => 'Crm:InboundEmail.Record.Edit',
      ),
    ),
    'Lead' => 
    array (
      'views' => 
      array (
        'detail' => 'Crm:Lead.Detail',
      ),
      'recordViews' => 
      array (
        'detail' => 'Crm:Lead.Record.Detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'activities',
            'label' => 'Activities',
            'view' => 'Crm:Record.Panels.Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'Crm:Record.Panels.History',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'Crm:Record.Panels.Tasks',
          ),
        ),
      ),
    ),
    'Meeting' => 
    array (
      'views' => 
      array (
        'detail' => 'Crm:Meeting.Detail',
      ),
      'sidePanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'Record.Panels.Side',
            'options' => 
            array (
              'fields' => 
              array (
                0 => 'users',
                1 => 'contacts',
                2 => 'leads',
              ),
              'mode' => 'detail',
            ),
          ),
        ),
        'edit' => 
        array (
          0 => 
          array (
            'name' => 'attendees',
            'label' => 'Attendees',
            'view' => 'Record.Panels.Side',
            'options' => 
            array (
              'fields' => 
              array (
                0 => 'users',
                1 => 'contacts',
                2 => 'leads',
              ),
              'mode' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Opportunity' => 
    array (
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
            'view' => 'Crm:Record.Panels.Activities',
          ),
          1 => 
          array (
            'name' => 'history',
            'label' => 'History',
            'view' => 'Crm:Record.Panels.History',
          ),
          2 => 
          array (
            'name' => 'tasks',
            'label' => 'Tasks',
            'view' => 'Crm:Record.Panels.Tasks',
          ),
        ),
      ),
    ),
    'Prospect' => 
    array (
      'views' => 
      array (
        'detail' => 'Crm:Prospect.Detail',
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
    ),
  ),
);

?>