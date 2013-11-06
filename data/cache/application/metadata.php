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
            'url' => '#admin/settings',
            'label' => 'Settings',
            'description' => 'System settings of application.',
          ),
          1 => 
          array (
            'url' => '#admin/clear-cache',
            'label' => 'Clear Cache',
            'description' => 'Clear all server side cache.',
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
            'url' => '#user',
            'label' => 'Users',
            'description' => 'Users management.',
          ),
          1 => 
          array (
            'url' => '#team',
            'label' => 'Teams',
            'description' => 'Teams management.',
          ),
          2 => 
          array (
            'url' => '#role',
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
            'url' => '#admin/outbound-email',
            'label' => 'Outbound Emails',
            'description' => 'SMTP settings for outgoing emails.',
          ),
          1 => 
          array (
            'url' => '#inbound-email',
            'label' => 'Inbound Emails',
            'description' => 'Group IMAP email accouts. Email import and Email-to-Case.',
          ),
          2 => 
          array (
            'url' => '#email-template',
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
            'url' => '#admin/import',
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
            'url' => '#admin/layouts',
            'label' => 'Layout Manager',
            'description' => 'Customize layouts (list, detail, edit, search, mass update).',
          ),
          1 => 
          array (
            'url' => '#admin/fields',
            'label' => 'Field Manager',
            'description' => 'Create new fields or customize existing ones.',
          ),
          2 => 
          array (
            'url' => '#admin/user-interface',
            'label' => 'User Interface',
            'description' => 'Configure UI.',
          ),
        ),
      ),
    ),
  ),
  'defs' => 
  array (
    'Bug' => 
    array (
      'type' => 'entity',
      'manyToOne' => 
      array (
        'reporter' => 
        array (
          'inversedBy' => 'reportedBugs',
          'targetEntity' => 'Espo\\Entities\\User',
        ),
        'engineer' => 
        array (
          'inversedBy' => 'assignedBugs',
          'targetEntity' => 'Espo\\Entities\\User',
        ),
      ),
      'fields' => 
      array (
        'status' => 
        array (
          'type' => 'string',
        ),
        'description' => 
        array (
          'type' => 'text',
        ),
        'created' => 
        array (
          'type' => 'datetime',
        ),
      ),
      'repositoryClass' => 'BugRepository',
      'table' => 'bugs',
      'id' => 
      array (
        'id' => 
        array (
          'type' => 'string',
          'generator' => 
          array (
            'strategy' => 'UUID',
          ),
        ),
      ),
    ),
    'User' => 
    array (
      'table' => 'users',
      'type' => 'entity',
      'id' => 
      array (
        'id' => 
        array (
          'type' => 'string',
          'generator' => 
          array (
            'strategy' => 'UUID',
          ),
        ),
      ),
      'fields' => 
      array (
        'username' => 
        array (
          'type' => 'string(30)',
        ),
        'password' => 
        array (
          'type' => 'string(255)',
        ),
        'isAdmin' => 
        array (
          'type' => 'boolean',
          'options' => 
          array (
            'default' => 0,
          ),
        ),
      ),
    ),
    'Contact' => 
    array (
      'module' => 'Test',
      'var1' => 
      array (
        'subvar1' => 'NEWsubval1',
        'subvar55' => 'subval55',
      ),
    ),
    'Product' => 
    array (
      'table' => 'products',
      'type' => 'entity',
      'id' => 
      array (
        'id' => 
        array (
          'type' => 'string',
          'generator' => 
          array (
            'strategy' => 'UUID',
          ),
        ),
      ),
      'fields' => 
      array (
        'name' => 
        array (
          'type' => 'string',
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
        'extension' => 
        array (
          'type' => 'varchar',
          'maxLength' => 10,
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
          'foreign' => 'attachments',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
      ),
    ),
    'Comment' => 
    array (
      'fields' => 
      array (
        'message' => 
        array (
          'type' => 'text',
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
          'foreign' => 'attachments',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'createdAt',
        'asc' => false,
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
          'required' => true,
        ),
        'body' => 
        array (
          'type' => 'text',
        ),
        'isHtml' => 
        array (
          'type' => 'bool',
          'default' => true,
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
        ),
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
          'type' => 'hasMany',
          'entity' => 'Attachment',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
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
    'Preferences' => 
    array (
      'fields' => 
      array (
        'timeZone' => 
        array (
          'type' => 'enum',
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
    'Role' => 
    array (
      'fields' => 
      array (
        'name' => 
        array (
          'maxLength' => 150,
          'required' => true,
        ),
      ),
      'links' => 
      array (
        'users' => 
        array (
          'type' => 'hasMany',
          'entity' => 'User',
        ),
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
        'asc' => true,
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
        ),
        'roles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Role',
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
        ),
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
        'email' => 
        array (
          'type' => 'email',
          'required' => true,
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
        ),
        'roles' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Role',
        ),
        'preferences' => 
        array (
          'type' => 'hasOne',
          'entity' => 'Preferences',
        ),
      ),
      'collection' => 
      array (
        'sortBy' => 'name',
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
        'email' => 
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
          'readOnly' => true,
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
          'readOnly' => true,
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
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
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
          1 => 'planned',
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
        'email' => 
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
    'Email' => 
    array (
      'fields' => 
      array (
        'subject' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'from' => 
        array (
          'type' => 'varchar',
          'db' => false,
        ),
        'to' => 
        array (
          'type' => 'varchar',
          'db' => false,
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
        'body' => 
        array (
          'type' => 'text',
        ),
        'status' => 
        array (
          'type' => 'varchar',
        ),
        'attachments' => 
        array (
          'type' => 'linkMultiple',
        ),
        'parent' => 
        array (
          'type' => 'linkParent',
        ),
        'dateSent' => 
        array (
          'type' => 'datetime',
          'default' => 'javascript: return this.dateTime.getNow(15);',
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
          'foreign' => 'attachments',
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
        'serverAddress' => 
        array (
          'type' => 'varchar',
          'required' => true,
        ),
        'serverPort' => 
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
        ),
        'removeMessagesFromServer' => 
        array (
          'type' => 'bool',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
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
            0 => 'Round-Robin',
            1 => 'Least-Busy',
          ),
        ),
        'caseEmailTemplate' => 
        array (
          'type' => 'link',
        ),
        'replyFromAddress' => 
        array (
          'type' => 'varchar',
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
        'teams' => 
        array (
          'type' => 'hasMany',
          'entity' => 'Team',
        ),
        'caseEmailTemplate' => 
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
            0 => '',
            1 => 'New',
            2 => 'Assigned',
            3 => 'In Process',
            4 => 'Converted',
            5 => 'Recycled',
            6 => 'Dead',
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
          'required' => true,
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
        'email' => 
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
          'required' => true,
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
        ),
        'teams' => 
        array (
          'type' => 'linkMultiple',
        ),
        'account' => 
        array (
          'type' => 'link',
          'disabled' => true,
          'readOnly' => true,
        ),
        'contact' => 
        array (
          'type' => 'link',
          'disabled' => true,
          'readOnly' => true,
        ),
        'opportunity' => 
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
        'account' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Account',
        ),
        'contact' => 
        array (
          'type' => 'belongsTo',
          'entity' => 'Contact',
        ),
        'opportunity' => 
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
          'email' => 'email',
          'phone' => 'phone',
          'address' => 'address',
          'assignedUser' => 'assignedUser',
        ),
        'Account' => 
        array (
          'name' => 'accountName',
          'website' => 'website',
          'email' => 'email',
          'phone' => 'phoneOffice',
          'assignedUser' => 'assignedUser',
        ),
        'Opportunity' => 
        array (
          'amount' => 'opportunityAmount',
          'leadSource' => 'source',
          'assignedUser' => 'assignedUser',
        ),
      ),
      'convertLinks' => 
      array (
        'Account' => 
        array (
          'Contact' => 'contacts',
          'Opportunity' => 'oppotunities',
        ),
        'Contact' => 
        array (
          'Opportunity' => 'oppotunities',
        ),
        'Lead' => 
        array (
          'Account' => 'account',
          'Contact' => 'contact',
          'Opportunity' => 'oppotunity',
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
          'readOnly' => true,
        ),
        'contacts' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
          'readOnly' => true,
        ),
        'leads' => 
        array (
          'type' => 'linkMultiple',
          'disabled' => true,
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
        'assignedUser' => 
        array (
          'type' => 'link',
          'required' => true,
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
          1 => 'planned',
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
        'account' => 
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
        'email' => 
        array (
          'type' => 'email',
          'required' => true,
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
          'required' => true,
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
        ),
        'dateEnd' => 
        array (
          'type' => 'datetime',
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
          'default' => 'javascript: return {assignedUserId: this.getUser().id, assignedUserName: this.getUser().get("name")};',
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
    ),
    'base' => 
    array (
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
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
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => true,
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
      ),
      'search' => 
      array (
        'basic' => false,
        'advanced' => false,
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
          'type' => 'varchar',
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
          'type' => 'varchar',
        ),
        'first' => 
        array (
          'type' => 'enum',
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
    'Comment' => 
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
    'Role' => 
    array (
      'entity' => true,
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
    ),
    'Contact' => 
    array (
      'entity' => true,
      'layouts' => true,
      'tab' => true,
      'acl' => true,
      'module' => 'Crm',
      'customizable' => true,
    ),
    'Email' => 
    array (
      'entity' => true,
      'layouts' => false,
      'tab' => false,
      'acl' => true,
      'module' => 'Crm',
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
    'EmailTemplate' => 
    array (
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Email Template',
              'link' => '#email-template/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Role' => 
    array (
      'recordViewMaps' => 
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
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Role',
              'link' => '#role/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
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
    ),
    'User' => 
    array (
      'recordViewMaps' => 
      array (
        'detail' => 'User.Record.Detail',
        'edit' => 'User.Record.Edit',
        'editQuick' => 'User.Record.Edit',
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create User',
              'link' => '#user/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Account' => 
    array (
      'bottomPanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'stream',
            'label' => 'Stream',
            'view' => 'Record.Panels.Stream',
          ),
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
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Account',
              'link' => '#account/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
        'detail' => 
        array (
          'dropdown' => 
          array (
            0 => 
            array (
              'label' => 'Add Contact',
              'action' => 'createRelated',
              'data' => 
              array (
                'link' => 'contacts',
              ),
              'acl' => 'edit',
              'aclScope' => 'Contact',
            ),
          ),
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
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Call',
              'link' => '#call/create',
              'style' => 'primary',
              'acl' => 'edit',
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
          0 => 
          array (
            'name' => 'stream',
            'label' => 'Stream',
            'view' => 'Record.Panels.Stream',
          ),
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
        ),
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Case',
              'link' => '#case/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Contact' => 
    array (
      'bottomPanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'stream',
            'label' => 'Stream',
            'view' => 'Record.Panels.Stream',
          ),
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
        ),
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Contact',
              'link' => '#contact/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'InboundEmail' => 
    array (
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Inbound Email',
              'link' => '#inbound-email/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Lead' => 
    array (
      'viewMaps' => 
      array (
        'detail' => 'Crm:Lead.Detail',
      ),
      'recordViewMaps' => 
      array (
        'detail' => 'Crm:Lead.Record.Detail',
      ),
      'bottomPanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'stream',
            'label' => 'Stream',
            'view' => 'Record.Panels.Stream',
          ),
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
        ),
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Lead',
              'link' => '#lead/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Meeting' => 
    array (
      'viewMaps' => 
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
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Meeting',
              'link' => '#meeting/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Opportunity' => 
    array (
      'bottomPanels' => 
      array (
        'detail' => 
        array (
          0 => 
          array (
            'name' => 'stream',
            'label' => 'Stream',
            'view' => 'Record.Panels.Stream',
          ),
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
        ),
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Opportunity',
              'link' => '#opportunity/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
    'Prospect' => 
    array (
      'viewMaps' => 
      array (
        'detail' => 'Crm:Prospect.Detail',
      ),
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Prospect',
              'link' => '#prospect/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
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
    'Task' => 
    array (
      'menu' => 
      array (
        'list' => 
        array (
          'buttons' => 
          array (
            0 => 
            array (
              'label' => 'Create Task',
              'link' => '#task/create',
              'style' => 'primary',
              'acl' => 'edit',
            ),
          ),
        ),
      ),
    ),
  ),
  'dashlets' => 
  array (
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
);

?>