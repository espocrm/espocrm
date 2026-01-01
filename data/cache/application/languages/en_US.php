<?php
return [
  'ActionHistoryRecord' => [
    'fields' => [
      'user' => 'User',
      'action' => 'Action',
      'createdAt' => 'Date',
      'userType' => 'User Type',
      'target' => 'Target',
      'targetType' => 'Target Type',
      'authToken' => 'Auth Token',
      'ipAddress' => 'IP Address',
      'authLogRecord' => 'Auth Log Record'
    ],
    'links' => [
      'authToken' => 'Auth Token',
      'authLogRecord' => 'Auth Log Record',
      'user' => 'User',
      'target' => 'Target'
    ],
    'presetFilters' => [
      'onlyMy' => 'Only My'
    ],
    'options' => [
      'action' => [
        'read' => 'Read',
        'update' => 'Update',
        'delete' => 'Delete',
        'create' => 'Create'
      ]
    ]
  ],
  'AddressCountry' => [
    'labels' => [
      'Create AddressCountry' => 'Create Address Country',
      'Populate' => 'Populate'
    ],
    'fields' => [
      'code' => 'Code',
      'isPreferred' => 'Is Preferred'
    ],
    'tooltips' => [
      'code' => 'ISO 3166-1 alpha-2 code.',
      'isPreferred' => 'Preferred countries appear first in the picklist.'
    ],
    'messages' => [
      'confirmPopulateDefaults' => 'All existing countries will be deleted, the default country list will be created. It won\'t be possible to revert the operation.

Are you sure?'
    ],
    'strings' => [
      'populateDefaults' => 'Populate with default country list'
    ]
  ],
  'Admin' => [
    'labels' => [
      'Enabled' => 'Enabled',
      'Disabled' => 'Disabled',
      'System' => 'System',
      'Users' => 'Users',
      'Email' => 'Email',
      'Messaging' => 'Messaging',
      'Data' => 'Data',
      'Misc' => 'Misc',
      'Setup' => 'Setup',
      'Customization' => 'Customization',
      'Available Fields' => 'Available Fields',
      'Layout' => 'Layout',
      'Entity Manager' => 'Entity Manager',
      'Add Panel' => 'Add Panel',
      'Add Field' => 'Add Field',
      'Settings' => 'Settings',
      'Scheduled Jobs' => 'Scheduled Jobs',
      'Upgrade' => 'Upgrade',
      'Clear Cache' => 'Clear Cache',
      'Rebuild' => 'Rebuild',
      'Teams' => 'Teams',
      'Roles' => 'Roles',
      'Portal' => 'Portal',
      'Portals' => 'Portals',
      'Portal Roles' => 'Portal Roles',
      'Portal Users' => 'Portal Users',
      'API Users' => 'API Users',
      'Outbound Emails' => 'Outbound Emails',
      'Group Email Accounts' => 'Group Email Accounts',
      'Personal Email Accounts' => 'Personal Email Accounts',
      'Inbound Emails' => 'Inbound Emails',
      'Email Templates' => 'Email Templates',
      'Import' => 'Import',
      'Layout Manager' => 'Layout Manager',
      'User Interface' => 'User Interface',
      'Auth Tokens' => 'Auth Tokens',
      'Auth Log' => 'Auth Log',
      'App Log' => 'App Log',
      'App Secrets' => 'App Secrets',
      'Authentication' => 'Authentication',
      'Currency' => 'Currency',
      'Integrations' => 'Integrations',
      'Extensions' => 'Extensions',
      'Webhooks' => 'Webhooks',
      'Dashboard Templates' => 'Dashboard Templates',
      'Upload' => 'Upload',
      'Installing...' => 'Installing...',
      'Upgrading...' => 'Upgrading...',
      'Upgraded successfully' => 'Upgraded successfully',
      'Installed successfully' => 'Installed successfully',
      'Ready for upgrade' => 'Ready for upgrade',
      'Run Upgrade' => 'Run Upgrade',
      'Install' => 'Install',
      'Ready for installation' => 'Ready for installation',
      'Uninstalling...' => 'Uninstalling...',
      'Uninstalled' => 'Uninstalled',
      'Create Entity' => 'Create Entity',
      'Edit Entity' => 'Edit Entity',
      'Create Link' => 'Create Link',
      'Edit Link' => 'Edit Link',
      'Notifications' => 'Notifications',
      'Jobs' => 'Jobs',
      'Job Settings' => 'Job Settings',
      'Reset to Default' => 'Reset to Default',
      'Email Filters' => 'Email Filters',
      'Action History' => 'Action History',
      'Label Manager' => 'Label Manager',
      'Template Manager' => 'Template Manager',
      'Lead Capture' => 'Lead Capture',
      'Attachments' => 'Attachments',
      'System Requirements' => 'System Requirements',
      'PDF Templates' => 'PDF Templates',
      'PHP Settings' => 'PHP Settings',
      'Database Settings' => 'Database Settings',
      'Permissions' => 'Permissions',
      'Email Addresses' => 'Email Addresses',
      'Phone Numbers' => 'Phone Numbers',
      'Layout Sets' => 'Layout Sets',
      'Working Time Calendars' => 'Working Time Calendars',
      'Group Email Folders' => 'Group Email Folders',
      'Authentication Providers' => 'Authentication Providers',
      'Address Countries' => 'Address Countries',
      'OAuth Providers' => 'OAuth Providers',
      'Success' => 'Success',
      'Fail' => 'Fail',
      'Configuration Instructions' => 'Configuration Instructions',
      'Formula Sandbox' => 'Formula Sandbox',
      'is recommended' => 'is recommended',
      'extension is missing' => 'extension is missing'
    ],
    'layouts' => [
      'list' => 'List',
      'detail' => 'Detail',
      'listSmall' => 'List (Small)',
      'detailSmall' => 'Detail (Small)',
      'detailPortal' => 'Detail (Portal)',
      'detailSmallPortal' => 'Detail (Small, Portal)',
      'listSmallPortal' => 'List (Small, Portal)',
      'listPortal' => 'List (Portal)',
      'relationshipsPortal' => 'Relationship Panels (Portal)',
      'filters' => 'Search Filters',
      'massUpdate' => 'Mass Update',
      'relationships' => 'Relationship Panels',
      'defaultSidePanel' => 'Side Panel Fields',
      'bottomPanelsDetail' => 'Bottom Panels',
      'bottomPanelsEdit' => 'Bottom Panels (Edit)',
      'bottomPanelsDetailSmall' => 'Bottom Panels (Detail Small)',
      'bottomPanelsEditSmall' => 'Bottom Panels (Edit Small)',
      'sidePanelsDetail' => 'Side Panels (Detail)',
      'sidePanelsEdit' => 'Side Panels (Edit)',
      'sidePanelsDetailSmall' => 'Side Panels (Detail Small)',
      'sidePanelsEditSmall' => 'Side Panels (Edit Small)',
      'kanban' => 'Kanban',
      'detailConvert' => 'Convert Lead',
      'listForAccount' => 'List (for Account)',
      'listForContact' => 'List (for Contact)'
    ],
    'fieldTypes' => [
      'base' => 'Base',
      'address' => 'Address',
      'array' => 'Array',
      'foreign' => 'Foreign',
      'duration' => 'Duration',
      'password' => 'Password',
      'personName' => 'Person Name',
      'autoincrement' => 'Auto-increment',
      'bool' => 'Boolean',
      'decimal' => 'Decimal',
      'currency' => 'Currency',
      'currencyConverted' => 'Currency (Converted)',
      'date' => 'Date',
      'datetime' => 'Date-Time',
      'datetimeOptional' => 'Date/Date-Time',
      'email' => 'Email',
      'enum' => 'Enum',
      'enumInt' => 'Enum Integer',
      'enumFloat' => 'Enum Float',
      'float' => 'Float',
      'int' => 'Integer',
      'link' => 'Link',
      'linkMultiple' => 'Link Multiple',
      'linkParent' => 'Link Parent',
      'linkOne' => 'Link One',
      'phone' => 'Phone',
      'text' => 'Text',
      'url' => 'Url',
      'urlMultiple' => 'Url Multiple',
      'varchar' => 'Varchar',
      'file' => 'File',
      'image' => 'Image',
      'multiEnum' => 'Multi-Enum',
      'attachmentMultiple' => 'Attachment Multiple',
      'rangeInt' => 'Range Integer',
      'rangeFloat' => 'Range Float',
      'rangeCurrency' => 'Range Currency',
      'wysiwyg' => 'Wysiwyg',
      'map' => 'Map',
      'number' => 'Number (auto-increment)',
      'colorpicker' => 'Color Picker',
      'checklist' => 'Checklist',
      'barcode' => 'Barcode',
      'jsonArray' => 'Json Array',
      'jsonObject' => 'Json Object'
    ],
    'fields' => [
      'type' => 'Type',
      'name' => 'Name',
      'label' => 'Label',
      'tooltipText' => 'Tooltip Text',
      'required' => 'Required',
      'default' => 'Default',
      'maxLength' => 'Max Length',
      'options' => 'Options',
      'optionsReference' => 'Options Reference',
      'after' => 'After (field)',
      'before' => 'Before (field)',
      'link' => 'Link',
      'field' => 'Field',
      'min' => 'Min',
      'max' => 'Max',
      'translation' => 'Translation',
      'previewSize' => 'Preview Size',
      'listPreviewSize' => 'Preview Size in List View',
      'noEmptyString' => 'Empty string value is not allowed',
      'defaultType' => 'Default Type',
      'seeMoreDisabled' => 'Disable Text Cut',
      'cutHeight' => 'Cut Height (px)',
      'entityList' => 'Entity List',
      'isSorted' => 'Is Sorted (alphabetically)',
      'audited' => 'Audited',
      'trim' => 'Trim',
      'height' => 'Height (px)',
      'minHeight' => 'Min Height (px)',
      'provider' => 'Provider',
      'typeList' => 'Type List',
      'rows' => 'Max number of rows',
      'lengthOfCut' => 'Length of cut',
      'sourceList' => 'Source List',
      'prefix' => 'Prefix',
      'nextNumber' => 'Next Number',
      'padLength' => 'Pad Length',
      'disableFormatting' => 'Disable Formatting',
      'dynamicLogicVisible' => 'Conditions making field visible',
      'dynamicLogicReadOnly' => 'Conditions making field read-only',
      'dynamicLogicRequired' => 'Conditions making field required',
      'dynamicLogicOptions' => 'Conditional options',
      'dynamicLogicInvalid' => 'Conditions making field invalid',
      'dynamicLogicReadOnlySaved' => 'Saved state conditions making field read-only',
      'probabilityMap' => 'Stage Probabilities (%)',
      'notActualOptions' => 'Not Actual Options',
      'activeOptions' => 'Active Options',
      'readOnly' => 'Read-only',
      'readOnlyAfterCreate' => 'Read-only After Create',
      'preview' => 'Preview',
      'attachmentField' => 'Attachment Field',
      'maxFileSize' => 'Max File Size (Mb)',
      'isPersonalData' => 'Is Personal Data',
      'useIframe' => 'Use Iframe',
      'useNumericFormat' => 'Use Numeric Format',
      'strip' => 'Strip',
      'minuteStep' => 'Minutes Step',
      'inlineEditDisabled' => 'Disable Inline Edit',
      'allowCustomOptions' => 'Allow Custom Options',
      'displayAsLabel' => 'Display as Label',
      'displayAsList' => 'Display as List',
      'labelType' => 'Label Type',
      'maxCount' => 'Max Item Count',
      'accept' => 'Accept',
      'viewMap' => 'View Map Button',
      'codeType' => 'Code Type',
      'lastChar' => 'Last Character',
      'onlyDefaultCurrency' => 'Only default currency',
      'decimal' => 'Decimal',
      'displayRawText' => 'Display raw text (no markdown)',
      'conversionDisabled' => 'Disable Conversion',
      'decimalPlaces' => 'Decimal Places',
      'pattern' => 'Pattern',
      'globalRestrictions' => 'Global Restrictions',
      'copyToClipboard' => 'Copy to clipboard button',
      'createButton' => 'Create Button',
      'autocompleteOnEmpty' => 'Autocomplete on empty input',
      'relateOnImport' => 'Relate on Import',
      'aclScope' => 'ACL Scope',
      'onlyAdmin' => 'Only for Admin',
      'notStorable' => 'Not Storable',
      'itemsEditable' => 'Items Editable'
    ],
    'strings' => [
      'rebuildRequired' => 'Rebuild is required'
    ],
    'messages' => [
      'cacheIsDisabled' => 'Cache is disabled, the application will run slow. Enable cache in the [settings](#Admin/settings).',
      'formulaFunctions' => 'More functions can be found in [documentation]({documentationUrl}).',
      'rebuildRequired' => 'You need to run rebuild from CLI.',
      'upgradeVersion' => 'EspoCRM will be upgraded to version **{version}**. Please be patient as this may take a while.',
      'upgradeDone' => 'EspoCRM has been upgraded to version **{version}**.',
      'upgradeBackup' => 'We recommend making a backup of your EspoCRM files and data before upgrading.',
      'thousandSeparatorEqualsDecimalMark' => 'The thousands separator character can not be the same as the decimal point character.',
      'userHasNoEmailAddress' => 'User has no email address.',
      'selectEntityType' => 'Select entity type in the left menu.',
      'selectUpgradePackage' => 'Select upgrade package',
      'downloadUpgradePackage' => 'Download upgrade package(s) [here]({url}).',
      'selectLayout' => 'Select needed layout in the left menu and edit it.',
      'selectExtensionPackage' => 'Select extension package',
      'extensionInstalled' => 'Extension {name} {version} has been installed.',
      'installExtension' => 'Extension {name} {version} is ready for an installation.',
      'cronIsDisabled' => 'Cron is disabled, the application is not fully functional. Enable cron in the [settings](#Admin/settings).',
      'cronIsNotConfigured' => 'Scheduled jobs are not running.  Hence inbound emails, notifications and reminders are not working. Please follow the [instructions](https://www.espocrm.com/documentation/administration/server-configuration/#user-content-setup-a-crontab) to setup cron job.',
      'newVersionIsAvailable' => 'New EspoCRM version {latestVersion} is available. Please follow the [instructions](https://www.espocrm.com/documentation/administration/upgrading/) to upgrade your instance.',
      'newExtensionVersionIsAvailable' => 'New {extensionName} version {latestVersion} is available.',
      'uninstallConfirmation' => 'Are you sure you want to uninstall the extension?',
      'upgradeInfo' => 'Check the [documentation]({url}) about how to upgrade your EspoCRM instance.',
      'upgradeRecommendation' => 'This way of upgrading is not recommended. It\'s better to upgrade from CLI.'
    ],
    'descriptions' => [
      'settings' => 'System settings of application.',
      'scheduledJob' => 'Jobs which are executed by cron.',
      'jobs' => 'Jobs execute tasks in the background.',
      'upgrade' => 'Upgrade EspoCRM.',
      'clearCache' => 'Clear all backend cache.',
      'rebuild' => 'Rebuild backend and clear cache.',
      'users' => 'Users management.',
      'teams' => 'Teams management.',
      'roles' => 'Roles management.',
      'portals' => 'Portals management.',
      'portalRoles' => 'Roles for portal.',
      'portalUsers' => 'Users of portal.',
      'outboundEmails' => 'SMTP settings for outgoing emails.',
      'groupEmailAccounts' => 'Group IMAP email accounts. Email import and Email-to-Case.',
      'personalEmailAccounts' => 'Users email accounts.',
      'emailTemplates' => 'Templates for outbound emails.',
      'import' => 'Import data from CSV file.',
      'layoutManager' => 'Customize layouts (list, detail, edit, search, mass update).',
      'entityManager' => 'Create and edit custom entities. Manage fields and relationships.',
      'userInterface' => 'Configure UI.',
      'authTokens' => 'Active auth sessions. IP address and last access date.',
      'authentication' => 'Authentication settings.',
      'currency' => 'Currency settings and rates.',
      'extensions' => 'Install or uninstall extensions.',
      'integrations' => 'Integration with third-party services.',
      'notifications' => 'In-app and email notification settings.',
      'inboundEmails' => 'Settings for incoming emails.',
      'emailFilters' => 'Email messages that match the specified filter won\'t be imported.',
      'groupEmailFolders' => 'Email folders shared for teams.',
      'actionHistory' => 'Log of user actions.',
      'labelManager' => 'Customize application labels.',
      'templateManager' => 'Customize message templates.',
      'authLog' => 'Login history.',
      'appLog' => 'Application log.',
      'appSecrets' => 'Store sensitive information like API keys, passwords, and other secrets.',
      'leadCapture' => 'Lead capture endpoints and web forms.',
      'attachments' => 'All file attachments stored in the system.',
      'systemRequirements' => 'System Requirements for EspoCRM.',
      'apiUsers' => 'Separate users for integration purposes.',
      'webhooks' => 'Manage webhooks.',
      'authenticationProviders' => 'Additional authentication providers for portals.',
      'emailAddresses' => 'All email addresses stored in the system.',
      'phoneNumbers' => 'All phone numbers stored in the system.',
      'dashboardTemplates' => 'Deploy dashboards to users.',
      'layoutSets' => 'Collections of layouts that can be assigned to teams & portals.',
      'workingTimeCalendars' => 'Working schedule.',
      'jobsSettings' => 'Job processing settings. Jobs execute tasks in the background.',
      'sms' => 'SMS settings.',
      'pdfTemplates' => 'Templates for printing to PDF.',
      'formulaSandbox' => 'Write and test formula scripts.',
      'addressCountries' => 'Countries available for address fields.',
      'oAuthProviders' => 'OAuth providers for integrations.'
    ],
    'keywords' => [
      'settings' => 'system',
      'userInterface' => 'ui,theme,tabs,logo,dashboard',
      'authentication' => 'password,security,ldap',
      'scheduledJob' => 'cron,jobs',
      'integrations' => 'google,maps,google maps',
      'authLog' => 'log,history',
      'authTokens' => 'history,access,log',
      'entityManager' => 'fields,relations,relationships',
      'templateManager' => 'notifications',
      'jobs' => 'cron',
      'labelManager' => 'language,translation',
      'appSecrets' => 'key,keys,password',
      'leadCapture' => 'web forms'
    ],
    'options' => [
      'previewSize' => [
        '' => 'Default',
        'x-small' => 'X-Small',
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large'
      ],
      'labelType' => [
        'state' => 'State',
        'regular' => 'Regular'
      ]
    ],
    'logicalOperators' => [
      'and' => 'AND',
      'or' => 'OR',
      'not' => 'NOT'
    ],
    'systemRequirements' => [
      'requiredPhpVersion' => 'PHP Version',
      'requiredMysqlVersion' => 'MySQL Version',
      'requiredMariadbVersion' => 'MariaDB version',
      'requiredPostgresqlVersion' => 'PostgreSQL version',
      'host' => 'Host Name',
      'dbname' => 'Database Name',
      'user' => 'User Name',
      'writable' => 'Writable',
      'readable' => 'Readable'
    ],
    'templates' => [
      'twoFactorCode' => '2FA Code',
      'accessInfo' => 'Access Info',
      'accessInfoPortal' => 'Access Info for Portals',
      'assignment' => 'Assignment',
      'mention' => 'Mention',
      'noteEmailReceived' => 'Note about Received Email',
      'notePost' => 'Note about Post',
      'notePostNoParent' => 'Note about Post (no Parent)',
      'noteStatus' => 'Note about Status Update',
      'passwordChangeLink' => 'Password Change Link',
      'invitation' => 'Invitation',
      'cancellation' => 'Cancellation',
      'reminder' => 'Reminder'
    ],
    'tooltips' => [
      'tabUrl' => 'Can start with `#` to navigate to an application page.',
      'tabUrlAclScope' => 'The tab will be available for users who have access to the specified scope.'
    ]
  ],
  'ApiUser' => [
    'labels' => [
      'Create ApiUser' => 'Create API User'
    ]
  ],
  'AppLogRecord' => [
    'fields' => [
      'message' => 'Message',
      'code' => 'Code',
      'level' => 'Level',
      'exceptionClass' => 'Exception Class',
      'file' => 'File',
      'line' => 'Line',
      'requestMethod' => 'Request Method',
      'requestResourcePath' => 'Request Resource Path'
    ],
    'presetFilters' => [
      'errors' => 'Errors'
    ]
  ],
  'AppSecret' => [
    'labels' => [
      'Create AppSecret' => 'Create Secret'
    ],
    'fields' => [
      'value' => 'Value'
    ],
    'tooltips' => [
      'name' => 'Allowed characters:
* `[a-z]`
* `[A-Z]`
* `[0-9]`
* `_`'
    ]
  ],
  'Attachment' => [
    'fields' => [
      'role' => 'Role',
      'related' => 'Related',
      'file' => 'File',
      'type' => 'Type',
      'field' => 'Field',
      'sourceId' => 'Source ID',
      'storage' => 'Storage',
      'size' => 'Size (bytes)',
      'isBeingUploaded' => 'Is Being Uploaded'
    ],
    'options' => [
      'role' => [
        'Attachment' => 'Attachment',
        'Inline Attachment' => 'Inline Attachment',
        'Import File' => 'Import File',
        'Export File' => 'Export File',
        'Mail Merge' => 'Mail Merge',
        'Mass Pdf' => 'Mass Pdf'
      ]
    ],
    'insertFromSourceLabels' => [
      'Document' => 'Insert Document'
    ],
    'presetFilters' => [
      'orphan' => 'Orphan'
    ]
  ],
  'AuthLogRecord' => [
    'fields' => [
      'username' => 'Username',
      'ipAddress' => 'IP Address',
      'requestTime' => 'Request Time',
      'createdAt' => 'Requested At',
      'isDenied' => 'Is Denied',
      'denialReason' => 'Denial Reason',
      'portal' => 'Portal',
      'user' => 'User',
      'authToken' => 'Auth Token Created',
      'requestUrl' => 'Request URL',
      'requestMethod' => 'Request Method',
      'authTokenIsActive' => 'Auth Token is Active',
      'authenticationMethod' => 'Authentication Method'
    ],
    'links' => [
      'authToken' => 'Auth Token Created',
      'user' => 'User',
      'portal' => 'Portal',
      'actionHistoryRecords' => 'Action History'
    ],
    'presetFilters' => [
      'denied' => 'Denied',
      'accepted' => 'Accepted'
    ],
    'options' => [
      'denialReason' => [
        'CREDENTIALS' => 'Invalid credentials',
        'WRONG_CODE' => 'Wrong code',
        'INACTIVE_USER' => 'Inactive user',
        'IS_PORTAL_USER' => 'Portal user',
        'IS_NOT_PORTAL_USER' => 'Not a portal user',
        'USER_IS_NOT_IN_PORTAL' => 'User is not related to the portal',
        'IS_SYSTEM_USER' => 'Is system user',
        'FORBIDDEN' => 'Forbidden'
      ]
    ]
  ],
  'AuthToken' => [
    'fields' => [
      'user' => 'User',
      'ipAddress' => 'IP Address',
      'lastAccess' => 'Last Access Date',
      'createdAt' => 'Login Date',
      'isActive' => 'Is Active',
      'portal' => 'Portal'
    ],
    'links' => [
      'actionHistoryRecords' => 'Action History'
    ],
    'presetFilters' => [
      'active' => 'Active',
      'inactive' => 'Inactive'
    ],
    'labels' => [
      'Set Inactive' => 'Set Inactive'
    ],
    'massActions' => [
      'setInactive' => 'Set Inactive'
    ]
  ],
  'AuthenticationProvider' => [
    'fields' => [
      'method' => 'Method'
    ],
    'labels' => [
      'Create AuthenticationProvider' => 'Create Provider'
    ]
  ],
  'Currency' => [
    'names' => [
      'AED' => 'United Arab Emirates Dirham',
      'AFN' => 'Afghan Afghani',
      'ALL' => 'Albanian Lek',
      'AMD' => 'Armenian Dram',
      'ANG' => 'Netherlands Antillean Guilder',
      'AOA' => 'Angolan Kwanza',
      'ARS' => 'Argentine Peso',
      'AUD' => 'Australian Dollar',
      'AWG' => 'Aruban Florin',
      'AZN' => 'Azerbaijani Manat',
      'BAM' => 'Bosnia-Herzegovina Convertible Mark',
      'BBD' => 'Barbadian Dollar',
      'BDT' => 'Bangladeshi Taka',
      'BGN' => 'Bulgarian Lev',
      'BHD' => 'Bahraini Dinar',
      'BIF' => 'Burundian Franc',
      'BMD' => 'Bermudan Dollar',
      'BND' => 'Brunei Dollar',
      'BOB' => 'Bolivian Boliviano',
      'BOV' => 'Bolivian Mvdol',
      'BRL' => 'Brazilian Real',
      'BSD' => 'Bahamian Dollar',
      'BTN' => 'Bhutanese Ngultrum',
      'BWP' => 'Botswanan Pula',
      'BYN' => 'Belarusian Ruble',
      'BZD' => 'Belize Dollar',
      'CAD' => 'Canadian Dollar',
      'CDF' => 'Congolese Franc',
      'CHE' => 'WIR Euro',
      'CHF' => 'Swiss Franc',
      'CHW' => 'WIR Franc',
      'CLF' => 'Chilean Unit of Account (UF)',
      'CLP' => 'Chilean Peso',
      'CNH' => 'Chinese Yuan (offshore)',
      'CNY' => 'Chinese Yuan',
      'COP' => 'Colombian Peso',
      'COU' => 'Colombian Real Value Unit',
      'CRC' => 'Costa Rican Colón',
      'CUC' => 'Cuban Convertible Peso',
      'CUP' => 'Cuban Peso',
      'CVE' => 'Cape Verdean Escudo',
      'CZK' => 'Czech Koruna',
      'DJF' => 'Djiboutian Franc',
      'DKK' => 'Danish Krone',
      'DOP' => 'Dominican Peso',
      'DZD' => 'Algerian Dinar',
      'EGP' => 'Egyptian Pound',
      'ERN' => 'Eritrean Nakfa',
      'ETB' => 'Ethiopian Birr',
      'EUR' => 'Euro',
      'FJD' => 'Fijian Dollar',
      'FKP' => 'Falkland Islands Pound',
      'GBP' => 'British Pound',
      'GEL' => 'Georgian Lari',
      'GHS' => 'Ghanaian Cedi',
      'GIP' => 'Gibraltar Pound',
      'GMD' => 'Gambian Dalasi',
      'GNF' => 'Guinean Franc',
      'GTQ' => 'Guatemalan Quetzal',
      'GYD' => 'Guyanaese Dollar',
      'HKD' => 'Hong Kong Dollar',
      'HNL' => 'Honduran Lempira',
      'HRK' => 'Croatian Kuna',
      'HTG' => 'Haitian Gourde',
      'HUF' => 'Hungarian Forint',
      'IDR' => 'Indonesian Rupiah',
      'ILS' => 'Israeli New Shekel',
      'INR' => 'Indian Rupee',
      'IQD' => 'Iraqi Dinar',
      'IRR' => 'Iranian Rial',
      'ISK' => 'Icelandic Króna',
      'JMD' => 'Jamaican Dollar',
      'JOD' => 'Jordanian Dinar',
      'JPY' => 'Japanese Yen',
      'KES' => 'Kenyan Shilling',
      'KGS' => 'Kyrgystani Som',
      'KHR' => 'Cambodian Riel',
      'KMF' => 'Comorian Franc',
      'KPW' => 'North Korean Won',
      'KRW' => 'South Korean Won',
      'KWD' => 'Kuwaiti Dinar',
      'KYD' => 'Cayman Islands Dollar',
      'KZT' => 'Kazakhstani Tenge',
      'LAK' => 'Laotian Kip',
      'LBP' => 'Lebanese Pound',
      'LKR' => 'Sri Lankan Rupee',
      'LRD' => 'Liberian Dollar',
      'LSL' => 'Lesotho Loti',
      'LYD' => 'Libyan Dinar',
      'MAD' => 'Moroccan Dirham',
      'MDL' => 'Moldovan Leu',
      'MGA' => 'Malagasy Ariary',
      'MKD' => 'Macedonian Denar',
      'MMK' => 'Myanmar Kyat',
      'MNT' => 'Mongolian Tugrik',
      'MOP' => 'Macanese Pataca',
      'MRO' => 'Mauritanian Ouguiya',
      'MUR' => 'Mauritian Rupee',
      'MWK' => 'Malawian Kwacha',
      'MXN' => 'Mexican Peso',
      'MXV' => 'Mexican Investment Unit',
      'MYR' => 'Malaysian Ringgit',
      'MZN' => 'Mozambican Metical',
      'NAD' => 'Namibian Dollar',
      'NGN' => 'Nigerian Naira',
      'NIO' => 'Nicaraguan Córdoba',
      'NOK' => 'Norwegian Krone',
      'NPR' => 'Nepalese Rupee',
      'NZD' => 'New Zealand Dollar',
      'OMR' => 'Omani Rial',
      'PAB' => 'Panamanian Balboa',
      'PEN' => 'Peruvian Sol',
      'PGK' => 'Papua New Guinean Kina',
      'PHP' => 'Philippine Piso',
      'PKR' => 'Pakistani Rupee',
      'PLN' => 'Polish Zloty',
      'PYG' => 'Paraguayan Guarani',
      'QAR' => 'Qatari Rial',
      'RON' => 'Romanian Leu',
      'RSD' => 'Serbian Dinar',
      'RUB' => 'Russian Ruble',
      'RWF' => 'Rwandan Franc',
      'SAR' => 'Saudi Riyal',
      'SBD' => 'Solomon Islands Dollar',
      'SCR' => 'Seychellois Rupee',
      'SDG' => 'Sudanese Pound',
      'SEK' => 'Swedish Krona',
      'SGD' => 'Singapore Dollar',
      'SHP' => 'St. Helena Pound',
      'SLL' => 'Sierra Leonean Leone',
      'SOS' => 'Somali Shilling',
      'SRD' => 'Surinamese Dollar',
      'SSP' => 'South Sudanese Pound',
      'STN' => 'São Tomé & Príncipe Dobra (2018)',
      'SYP' => 'Syrian Pound',
      'SZL' => 'Swazi Lilangeni',
      'SVC' => 'Salvadoran Colón',
      'THB' => 'Thai Baht',
      'TJS' => 'Tajikistani Somoni',
      'TND' => 'Tunisian Dinar',
      'TOP' => 'Tongan Paʻanga',
      'TRY' => 'Turkish Lira',
      'TTD' => 'Trinidad & Tobago Dollar',
      'TWD' => 'New Taiwan Dollar',
      'TZS' => 'Tanzanian Shilling',
      'UAH' => 'Ukrainian Hryvnia',
      'UGX' => 'Ugandan Shilling',
      'USD' => 'US Dollar',
      'USN' => 'US Dollar (Next day)',
      'UYI' => 'Uruguayan Peso (Indexed Units)',
      'UYU' => 'Uruguayan Peso',
      'UZS' => 'Uzbekistani Som',
      'VEF' => 'Venezuelan Bolívar',
      'VND' => 'Vietnamese Dong',
      'VUV' => 'Vanuatu Vatu',
      'WST' => 'Samoan Tala',
      'XAF' => 'Central African CFA Franc',
      'XCD' => 'East Caribbean Dollar',
      'XOF' => 'West African CFA Franc',
      'XPF' => 'CFP Franc',
      'YER' => 'Yemeni Rial',
      'ZAR' => 'South African Rand',
      'ZMW' => 'Zambian Kwacha',
      'ZWL' => 'Zimbabwe Dollar'
    ],
    'messages' => [
      'rateOnDateAlreadyExists' => 'A rate on this date already exists.'
    ]
  ],
  'CurrencyRecord' => [
    'fields' => [
      'code' => 'Code',
      'status' => 'Status',
      'label' => 'Label',
      'symbol' => 'Symbol',
      'isBase' => 'Is Base',
      'rate' => 'Rate',
      'rateDate' => 'Rate Date'
    ],
    'links' => [
      'rates' => 'Rates'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ]
    ]
  ],
  'CurrencyRecordRate' => [
    'labels' => [
      'Create CurrencyRecordRate' => 'Create Exchange Rate'
    ],
    'fields' => [
      'record' => 'Currency',
      'baseCode' => 'Base',
      'date' => 'Date',
      'rate' => 'Rate',
      'number' => 'Number'
    ],
    'links' => [
      'record' => 'Currency'
    ]
  ],
  'DashboardTemplate' => [
    'fields' => [
      'layout' => 'Layout',
      'append' => 'Append (don\'t remove user\'s tabs)'
    ],
    'links' => [],
    'labels' => [
      'Create DashboardTemplate' => 'Create Template',
      'Deploy to Users' => 'Deploy to Users',
      'Deploy to Team' => 'Deploy to Team'
    ]
  ],
  'DashletOptions' => [
    'fields' => [
      'title' => 'Title',
      'dateFrom' => 'Date From',
      'dateTo' => 'Date To',
      'autorefreshInterval' => 'Auto-refresh Interval',
      'displayRecords' => 'Display Records',
      'isDoubleHeight' => 'Height 2x',
      'mode' => 'Mode',
      'enabledScopeList' => 'What to display',
      'users' => 'Users',
      'entityType' => 'Entity Type',
      'primaryFilter' => 'Primary Filter',
      'boolFilterList' => 'Additional Filters',
      'sortBy' => 'Order By',
      'sortDirection' => 'Order Direction',
      'expandedLayout' => 'Layout',
      'skipOwn' => 'Don\'t show own records',
      'url' => 'URL',
      'dateFilter' => 'Date Filter',
      'text' => 'Text',
      'folder' => 'Folder',
      'includeShared' => 'Include Shared',
      'team' => 'Team',
      'futureDays' => 'Next X Days',
      'useLastStage' => 'Group by last reached stage'
    ],
    'options' => [
      'mode' => [
        'agendaWeek' => 'Week (agenda)',
        'basicWeek' => 'Week',
        'month' => 'Month',
        'basicDay' => 'Day',
        'agendaDay' => 'Day (agenda)',
        'timeline' => 'Timeline'
      ],
      'sortDirection' => [
        'asc' => 'Ascending',
        'desc' => 'Descending'
      ]
    ],
    'messages' => [
      'selectEntityType' => 'Select Entity Type in dashlet options.'
    ],
    'tooltips' => [
      'skipOwn' => 'Actions made by your user account won\'t be displayed.'
    ],
    'otherFields' => [
      'soft' => 'Soft Color',
      'small' => 'Small Font'
    ]
  ],
  'DynamicLogic' => [
    'labels' => [
      'Field' => 'Field'
    ],
    'options' => [
      'operators' => [
        'equals' => 'Equals',
        'notEquals' => 'Not Equals',
        'greaterThan' => 'Greater Than',
        'lessThan' => 'Less Than',
        'greaterThanOrEquals' => 'Greater Than Or Equals',
        'lessThanOrEquals' => 'Less Than Or Equals',
        'in' => 'In',
        'notIn' => 'Not In',
        'inPast' => 'In Past',
        'inFuture' => 'Is Future',
        'isToday' => 'Is Today',
        'isTrue' => 'Is True',
        'isFalse' => 'Is False',
        'isEmpty' => 'Is Empty',
        'isNotEmpty' => 'Is Not Empty',
        'contains' => 'Contains',
        'notContains' => 'Not Contains',
        'has' => 'Contains',
        'notHas' => 'Not Contains',
        'startsWith' => 'Starts With',
        'endsWith' => 'Ends With',
        'matches' => 'Matches (reg exp)'
      ]
    ]
  ],
  'Email' => [
    'fields' => [
      'name' => 'Name (Subject)',
      'parent' => 'Parent',
      'status' => 'Status',
      'dateSent' => 'Date Sent',
      'from' => 'From',
      'to' => 'To',
      'cc' => 'CC',
      'bcc' => 'BCC',
      'replyTo' => 'Reply To',
      'replyToString' => 'Reply To (String)',
      'personStringData' => 'Person String Data',
      'isHtml' => 'HTML',
      'body' => 'Body',
      'bodyPlain' => 'Body (Plain)',
      'subject' => 'Subject',
      'attachments' => 'Attachments',
      'selectTemplate' => 'Select Template',
      'fromEmailAddress' => 'From Address (link)',
      'emailAddress' => 'Email Address',
      'deliveryDate' => 'Delivery Date',
      'account' => 'Account',
      'users' => 'Users',
      'replied' => 'Replied',
      'replies' => 'Replies',
      'isRead' => 'Is Read',
      'isNotRead' => 'Is Not Read',
      'isImportant' => 'Is Important',
      'isReplied' => 'Is Replied',
      'isNotReplied' => 'Is Not Replied',
      'isUsers' => 'Is User\'s',
      'isUsersSent' => 'Is User\'s Sent',
      'inTrash' => 'In Trash',
      'inArchive' => 'In Archive',
      'folder' => 'Folder',
      'inboundEmails' => 'Group Accounts',
      'emailAccounts' => 'Personal Accounts',
      'hasAttachment' => 'Has Attachment',
      'assignedUsers' => 'Assigned Users',
      'sentBy' => 'Sent By',
      'toEmailAddresses' => 'To Email Addresses',
      'ccEmailAddresses' => 'CC Email Addresses',
      'bccEmailAddresses' => 'BCC Email Addresses',
      'replyToEmailAddresses' => 'Reply-To Email Addresses',
      'messageId' => 'Message Id',
      'messageIdInternal' => 'Message Id (Internal)',
      'folderId' => 'Folder Id',
      'folderString' => 'Folder',
      'fromName' => 'From Name',
      'fromString' => 'From String',
      'fromAddress' => 'From Address',
      'replyToName' => 'Reply-To Name',
      'replyToAddress' => 'Reply-To Address',
      'isSystem' => 'Is System',
      'icsContents' => 'ICS Contents',
      'icsEventData' => 'ICS Event Data',
      'icsEventUid' => 'ICS Event UID',
      'createdEvent' => 'Created Event',
      'event' => 'Event',
      'icsEventDateStart' => 'ICS Event Date Start',
      'groupFolder' => 'Group Folder',
      'groupStatusFolder' => 'Group Status Folder',
      'sendAt' => 'Send At',
      'isAutoReply' => 'Is Auto-Reply',
      'tasks' => 'Tasks'
    ],
    'links' => [
      'replied' => 'Replied',
      'replies' => 'Replies',
      'inboundEmails' => 'Group Accounts',
      'emailAccounts' => 'Personal Accounts',
      'assignedUsers' => 'Assigned Users',
      'sentBy' => 'Sent By',
      'attachments' => 'Attachments',
      'fromEmailAddress' => 'From Email Address',
      'toEmailAddresses' => 'To Email Addresses',
      'ccEmailAddresses' => 'CC Email Addresses',
      'bccEmailAddresses' => 'BCC Email Addresses',
      'replyToEmailAddresses' => 'Reply-To Email Addresses',
      'createdEvent' => 'Created Event',
      'groupFolder' => 'Group Folder'
    ],
    'options' => [
      'status' => [
        'Draft' => 'Draft',
        'Sending' => 'Sending',
        'Sent' => 'Sent',
        'Archived' => 'Imported',
        'Received' => 'Received',
        'Failed' => 'Failed'
      ],
      'groupStatusFolder' => [
        'Archive' => 'Archive',
        'Trash' => 'Trash'
      ]
    ],
    'labels' => [
      'Create Email' => 'Archive Email',
      'Archive Email' => 'Archive Email',
      'Import EML' => 'Import EML',
      'Compose' => 'Compose',
      'Reply' => 'Reply',
      'Reply to All' => 'Reply to All',
      'Forward' => 'Forward',
      'Insert Field' => 'Insert Field',
      'Original message' => 'Original message',
      'Forwarded message' => 'Forwarded message',
      'Email Accounts' => 'Personal Email Accounts',
      'Inbound Emails' => 'Group Email Accounts',
      'Email Templates' => 'Email Templates',
      'Send Test Email' => 'Send Test Email',
      'Send' => 'Send',
      'Email Address' => 'Email Address',
      'Mark Read' => 'Mark Read',
      'Sending...' => 'Sending...',
      'Save Draft' => 'Save Draft',
      'Mark all as read' => 'Mark all as read',
      'Show Plain Text' => 'Show Plain Text',
      'Mark as Important' => 'Mark as Important',
      'Unmark Importance' => 'Unmark Importance',
      'Move to Trash' => 'Move to Trash',
      'Retrieve from Trash' => 'Retrieve from Trash',
      'Move to Folder' => 'Move to Folder',
      'Moved to Archive' => 'Moved to Archive',
      'No Records Moved' => 'No Records Moved',
      'Filters' => 'Filters',
      'Folders' => 'Folders',
      'Group Folders' => 'Group Folders',
      'No Subject' => 'No Subject',
      'View Users' => 'View Users',
      'Event' => 'Event',
      'View Attachments' => 'View Attachments',
      'Moved to Trash' => 'Moved to Trash',
      'Retrieved from Trash' => 'Retrieved from Trash',
      'Schedule Send' => 'Schedule Send',
      'Create Lead' => 'Create Lead',
      'Create Contact' => 'Create Contact',
      'Add to Contact' => 'Add to Contact',
      'Add to Lead' => 'Add to Lead',
      'Create Task' => 'Create Task',
      'Create Case' => 'Create Case'
    ],
    'strings' => [
      'sendingFailed' => 'Email sending failed',
      'group' => 'Group'
    ],
    'messages' => [
      'confirmSend' => 'Send the email?',
      'couldNotSentScheduledEmail' => 'Could not send scheduled [email]({link})',
      'notEditAccess' => 'No edit access to email.',
      'groupFolderNoAccess' => 'No access to group folder.',
      'groupMoveOutNoEditAccess' => 'Cannot move out from group folder. No edit access to email.',
      'groupMoveToNoEditAccess' => 'Cannot move to group folder. No edit access to email.',
      'groupMoveToTrashNoEditAccess' => 'Cannot move email from group folder to trash. No edit access to email.',
      'groupMoveToArchiveNoEditAccess' => 'Cannot move from group folder to Archive. No edit access to email.',
      'alreadyImported' => 'The [email]({link}) already exists in the system.',
      'invalidCredentials' => 'Invalid credentials.',
      'unknownError' => 'Unknown error.',
      'recipientAddressRejected' => 'Recipient address rejected.',
      'noSmtpSetup' => 'SMTP is not configured: {link}',
      'testEmailSent' => 'Test email has been sent',
      'emailSent' => 'Email has been sent',
      'savedAsDraft' => 'Saved as draft',
      'sendConfirm' => 'Send the email?',
      'removeSelectedRecordsConfirmation' => 'Are you sure you want to remove selected emails?

They will be removed for other users too.',
      'removeRecordConfirmation' => 'Are you sure you want to remove the email?

It will be removed for other users too.',
      'confirmInsertTemplate' => 'The email body will be lost. Are you sure you want to insert the template?'
    ],
    'presetFilters' => [
      'sent' => 'Sent',
      'archived' => 'Imported',
      'inbox' => 'Inbox',
      'drafts' => 'Drafts',
      'trash' => 'Trash',
      'archive' => 'Archive',
      'important' => 'Important'
    ],
    'actions' => [
      'moveToArchive' => 'Archive'
    ],
    'massActions' => [
      'markAsRead' => 'Mark as Read',
      'markAsNotRead' => 'Mark as Not Read',
      'markAsImportant' => 'Mark as Important',
      'markAsNotImportant' => 'Unmark Importance',
      'moveToTrash' => 'Move to Trash',
      'moveToFolder' => 'Move to Folder',
      'moveToArchive' => 'Archive',
      'retrieveFromTrash' => 'Retrieve from Trash'
    ],
    'otherFields' => [
      'file' => 'File'
    ]
  ],
  'EmailAccount' => [
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'host' => 'Host',
      'username' => 'Username',
      'password' => 'Password',
      'port' => 'Port',
      'monitoredFolders' => 'Monitored Folders',
      'security' => 'Security',
      'fetchSince' => 'Fetch Since',
      'emailAddress' => 'Email Address',
      'sentFolder' => 'Sent Folder',
      'storeSentEmails' => 'Store Sent Emails',
      'keepFetchedEmailsUnread' => 'Keep Fetched Emails Unread',
      'emailFolder' => 'Put in Folder',
      'connectedAt' => 'Connected At',
      'useImap' => 'Fetch Emails',
      'useSmtp' => 'Use SMTP',
      'smtpHost' => 'SMTP Host',
      'smtpPort' => 'SMTP Port',
      'smtpAuth' => 'SMTP Auth',
      'smtpSecurity' => 'SMTP Security',
      'smtpAuthMechanism' => 'SMTP Auth Mechanism',
      'smtpUsername' => 'SMTP Username',
      'smtpPassword' => 'SMTP Password',
      'folderMap' => 'Folder Mapping'
    ],
    'links' => [
      'filters' => 'Filters',
      'emails' => 'Emails'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ],
      'smtpAuthMechanism' => [
        'plain' => 'PLAIN',
        'login' => 'LOGIN',
        'crammd5' => 'CRAM-MD5'
      ],
      'smtpSecurity' => [
        'SSL' => 'SSL/TLS',
        'TLS' => 'STARTTLS'
      ],
      'security' => [
        'SSL' => 'SSL/TLS',
        'TLS' => 'STARTTLS'
      ]
    ],
    'labels' => [
      'Create EmailAccount' => 'Create Email Account',
      'IMAP' => 'IMAP',
      'Main' => 'Main',
      'Test Connection' => 'Test Connection',
      'Send Test Email' => 'Send Test Email',
      'SMTP' => 'SMTP'
    ],
    'presetFilters' => [
      'active' => 'Active'
    ],
    'messages' => [
      'noFolder' => 'Mapped folder does not exist.',
      'couldNotConnectToImap' => 'Could not connect to IMAP server',
      'connectionIsOk' => 'Connection is Ok',
      'imapNotConnected' => 'Could not connect to [IMAP account](#EmailAccount/view/{id}).'
    ],
    'tooltips' => [
      'useSmtp' => 'The ability to send emails.',
      'emailAddress' => 'The user record (assigned user) should have the same email address to be able to use this email account for sending.',
      'monitoredFolders' => 'Select IMAP folders to import. Optionally, map them to Espo folders.

You can add the \'Sent\' folder to sync emails sent from an external email client.',
      'storeSentEmails' => 'Sent emails will be stored on the IMAP server. Email Address field should match the address emails will be sent from.'
    ]
  ],
  'EmailAddress' => [
    'labels' => [
      'Primary' => 'Primary',
      'Opted Out' => 'Opted Out',
      'Invalid' => 'Invalid'
    ],
    'fields' => [
      'optOut' => 'Opted Out',
      'invalid' => 'Invalid',
      'lower' => 'Lower-case Name'
    ],
    'presetFilters' => [
      'orphan' => 'Orphan'
    ]
  ],
  'EmailFilter' => [
    'fields' => [
      'from' => 'From',
      'to' => 'To',
      'subject' => 'Subject',
      'bodyContains' => 'Body Contains',
      'bodyContainsAll' => 'Body Contains All',
      'action' => 'Action',
      'isGlobal' => 'Is Global',
      'emailFolder' => 'Folder',
      'groupEmailFolder' => 'Group Email Folder',
      'markAsRead' => 'Mark as Read',
      'skipNotification' => 'Skip Notification'
    ],
    'links' => [
      'emailFolder' => 'Folder',
      'groupEmailFolder' => 'Group Email Folder'
    ],
    'labels' => [
      'Create EmailFilter' => 'Create Email Filter',
      'Emails' => 'Emails'
    ],
    'options' => [
      'action' => [
        'None' => 'None',
        'Skip' => 'Ignore',
        'Move to Folder' => 'Put in Folder',
        'Move to Group Folder' => 'Put in Group Folder'
      ]
    ],
    'tooltips' => [
      'name' => 'Give the filter a descriptive name.',
      'subject' => 'Use a wildcard (`*`): 

 * `text*` – starts with text
 * `*text*` – contains text
 * `*text` – ends with text',
      'bodyContains' => 'Body of the email contains any of the specified words or phrases.',
      'bodyContainsAll' => 'An email body contains all specified words or phrases.',
      'from' => 'Emails sent from the specified address will be filtered. Leave empty if not needed. You can use a wildcard (`*`).',
      'to' => 'Emails sent to the specified address will be filtered. Leave empty if not needed. You can use a wildcard (`*`).',
      'isGlobal' => 'Applies this filter to all emails incoming to system.'
    ]
  ],
  'EmailFolder' => [
    'fields' => [
      'skipNotifications' => 'Skip Notifications'
    ],
    'labels' => [
      'Create EmailFolder' => 'Create Folder',
      'Manage Folders' => 'Manage Folders',
      'Emails' => 'Emails'
    ]
  ],
  'EmailTemplate' => [
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'isHtml' => 'HTML',
      'body' => 'Body',
      'subject' => 'Subject',
      'attachments' => 'Attachments',
      'oneOff' => 'One-off',
      'category' => 'Category',
      'insertField' => 'Placeholders'
    ],
    'links' => [],
    'labels' => [
      'Create EmailTemplate' => 'Create Email Template',
      'Info' => 'Info',
      'Available placeholders' => 'Available placeholders'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ]
    ],
    'messages' => [
      'infoText' => 'Available placeholders:

{optOutUrl} &#8211; URL for an unsubscribe link;

{optOutLink} &#8211; an unsubscribe link.'
    ],
    'tooltips' => [
      'oneOff' => 'Check if you are going to use this template only once. E.g. for Mass Email.'
    ],
    'presetFilters' => [
      'actual' => 'Active'
    ],
    'placeholderTexts' => [
      'today' => 'Today\'s date',
      'now' => 'Current date & time',
      'currentYear' => 'Current Year',
      'optOutUrl' => 'URL for an unsubscribe link',
      'optOutLink' => 'an unsubscribe link'
    ]
  ],
  'EmailTemplateCategory' => [
    'labels' => [
      'Create EmailTemplateCategory' => 'Create Category',
      'Manage Categories' => 'Manage Categories',
      'EmailTemplates' => 'Email Templates'
    ],
    'fields' => [
      'order' => 'Order',
      'childList' => 'Child List'
    ],
    'links' => [
      'emailTemplates' => 'Email Templates'
    ]
  ],
  'EntityManager' => [
    'labels' => [
      'Fields' => 'Fields',
      'Relationships' => 'Relationships',
      'Layouts' => 'Layouts',
      'Schedule' => 'Schedule',
      'Log' => 'Log',
      'Formula' => 'Formula',
      'Parameters' => 'Parameters'
    ],
    'fields' => [
      'name' => 'Name',
      'type' => 'Type',
      'labelSingular' => 'Label Singular',
      'labelPlural' => 'Label Plural',
      'stream' => 'Stream',
      'label' => 'Label',
      'linkType' => 'Link Type',
      'entity' => 'Entity',
      'entityForeign' => 'Foreign Entity',
      'linkForeign' => 'Foreign Link',
      'link' => 'Link',
      'labelForeign' => 'Foreign Label',
      'sortBy' => 'Default Order Field',
      'sortDirection' => 'Default Order Direction',
      'relationName' => 'Middle Table Name',
      'linkMultipleField' => 'Link Multiple Field',
      'linkMultipleFieldForeign' => 'Foreign Link Multiple Field',
      'disabled' => 'Disabled',
      'textFilterFields' => 'Text Filter Fields',
      'audited' => 'Audited',
      'auditedForeign' => 'Foreign Audited',
      'statusField' => 'Status Field',
      'beforeSaveCustomScript' => 'Before Save Custom Script',
      'beforeSaveApiScript' => 'API Before Save Script',
      'color' => 'Color',
      'kanbanViewMode' => 'Kanban View',
      'kanbanStatusIgnoreList' => 'Ignored groups in Kanban view',
      'iconClass' => 'Icon',
      'countDisabled' => 'Disable record count',
      'fullTextSearch' => 'Full-Text Search',
      'parentEntityTypeList' => 'Parent Entity Types',
      'foreignLinkEntityTypeList' => 'Foreign Links',
      'optimisticConcurrencyControl' => 'Optimistic concurrency control',
      'preserveAuditLog' => 'Preserve Audit Log',
      'updateDuplicateCheck' => 'Duplicate check on update',
      'duplicateCheckFieldList' => 'Duplicate check fields',
      'stars' => 'Stars',
      'layout' => 'Layout',
      'selectFilter' => 'Select Filter',
      'author' => 'Author',
      'module' => 'Module',
      'version' => 'Version',
      'primaryFilters' => 'Primary Filters',
      'assignedUsers' => 'Multiple Assigned Users',
      'collaborators' => 'Collaborators',
      'aclContactLink' => 'ACL Contact Link',
      'aclAccountLink' => 'ACL Account Link',
      'activityStatusList' => 'Activity Statuses',
      'historyStatusList' => 'History Statuses',
      'completedStatusList' => 'Completed Statuses',
      'canceledStatusList' => 'Canceled Statuses'
    ],
    'options' => [
      'type' => [
        '' => 'None',
        'Base' => 'Base',
        'Person' => 'Person',
        'CategoryTree' => 'Category Tree',
        'Event' => 'Event',
        'BasePlus' => 'Base Plus',
        'Company' => 'Company'
      ],
      'linkType' => [
        'manyToMany' => 'Many-to-Many',
        'oneToMany' => 'One-to-Many',
        'manyToOne' => 'Many-to-One',
        'oneToOneRight' => 'One-to-One Right',
        'oneToOneLeft' => 'One-to-One Left',
        'parentToChildren' => 'Parent-to-Children',
        'childrenToParent' => 'Children-to-Parent'
      ],
      'sortDirection' => [
        'asc' => 'Ascending',
        'desc' => 'Descending'
      ],
      'module' => [
        'Custom' => 'Custom'
      ]
    ],
    'messages' => [
      'urlHashCopiedToClipboard' => 'A URL fragment for the *{name}* filter is copied to the clipboard. You can add it to the navbar.',
      'confirmRemoveLink' => 'Are you sure you want to remove the *{link}* relationship?',
      'nameIsAlreadyUsed' => 'Name \'{name}\' is already used.',
      'nameIsNotAllowed' => 'Name \'{name}\' is not allowed.',
      'nameIsTooLong' => 'Name is too long.',
      'confirmRemove' => 'Are you sure you want to remove the entity type from the system?',
      'entityCreated' => 'Entity has been created',
      'linkAlreadyExists' => 'Link name conflict.',
      'linkConflict' => 'Name conflict: link or field with the same name already exists.',
      'beforeSaveCustomScript' => 'A script called every time before an entity is saved. Use for setting calculated fields.',
      'beforeSaveApiScript' => 'A script called on create and update API requests before an entity is saved. Use for custom validation and duplicate checking.'
    ],
    'tooltips' => [
      'aclContactLink' => 'The link with Contact to use when applying access control for portal users.',
      'aclAccountLink' => 'The link with Account to use when applying access control for portal users.',
      'collaborators' => 'The ability to share records with specific users.',
      'assignedUsers' => 'The ability to assign multiple users to a record.

Note that after enabling the parameter, existing assigned users won\'t be transferred to the new *Assigned Users* field.',
      'duplicateCheckFieldList' => 'Which fields to check when performing checking for duplicates.',
      'updateDuplicateCheck' => 'Perform checking for duplicates when updating a record.',
      'optimisticConcurrencyControl' => 'Prevents writing conflicts.',
      'preserveAuditLog' => 'Disables cleanup of the audit log. This parameter is applicable only if Stream is disabled. As if Stream is enabled, audit log records are not being deleted.',
      'stars' => 'The ability to star records. Stars can be used by users to bookmark records.',
      'statusField' => 'Updates of this field are logged in stream.',
      'textFilterFields' => 'Fields used by text search.',
      'stream' => 'Whether the entity has the Stream.',
      'disabled' => 'Check if you don\'t need this entity in your system.',
      'linkAudited' => 'Creating related record and linking with existing record will be logged in Stream.',
      'linkMultipleField' => 'Link Multiple field provides a handy way to edit relations. Don\'t use it if you can have a large number of related records.',
      'linkSelectFilter' => 'A primary filter to apply by default when selecting a record.',
      'entityType' => 'Base Plus - has Activities, History and Tasks panels.

Event - available in Calendar and Activities panel.',
      'countDisabled' => 'Total number won\'t be displayed on the list view. Can decrease loading time when the DB table is big.',
      'fullTextSearch' => 'Running rebuild is required.',
      'linkParamReadOnly' => 'A read-only link cannot be edited via the *link* and *unlink* API requests. It won\'t be possible to relate and unrelate records via the relationship panel. It still possible to edit read-only links via link and link-multiple fields.',
      'activityStatusList' => 'Status values determining that an activity record should be displayed in the Activity panel and considered as actual.',
      'historyStatusList' => 'Status values determining that an activity record should be displayed in the History panel.',
      'completedStatusList' => 'Status values determining that an activity is completed.',
      'canceledStatusList' => 'Status values determining that an activity is canceled and won\'t be taken into account in free/busy ranges.'
    ]
  ],
  'Export' => [
    'fields' => [
      'exportAllFields' => 'Export all fields',
      'fieldList' => 'Field List',
      'format' => 'Format',
      'status' => 'Status',
      'xlsxLite' => 'Lite',
      'xlsxRecordLinks' => 'Record Links',
      'xlsxTitle' => 'Title'
    ],
    'options' => [
      'format' => [
        'csv' => 'CSV',
        'xlsx' => 'XLSX (Excel)'
      ],
      'status' => [
        'Pending' => 'Pending',
        'Running' => 'Running',
        'Success' => 'Success',
        'Failed' => 'Failed'
      ]
    ],
    'tooltips' => [
      'xlsxLite' => 'Consumes much less memory. Recommended if a big number of records is exported.',
      'xlsxTitle' => 'Print a title and current date in the header.'
    ],
    'messages' => [
      'exportProcessed' => 'Export has been processed. Download the [file]({url}).',
      'infoText' => 'The export is being processed in idle by cron. It can take some time to finish. Closing this modal dialog won\'t affect the execution process.'
    ]
  ],
  'Extension' => [
    'fields' => [
      'name' => 'Name',
      'version' => 'Version',
      'description' => 'Description',
      'isInstalled' => 'Installed',
      'checkVersionUrl' => 'An URL for checking new versions'
    ],
    'labels' => [
      'Uninstall' => 'Uninstall',
      'Install' => 'Install'
    ],
    'messages' => [
      'uninstalled' => 'Extension {name} has been uninstalled',
      'fileExceedsMaxUploadSize' => 'The file size exceeds the max upload size {maxSize}. Consider increasing `post_max_size` or install the extension via CLI.'
    ]
  ],
  'ExternalAccount' => [
    'labels' => [
      'Connect' => 'Connect',
      'Disconnect' => 'Disconnect',
      'Disconnected' => 'Disconnected',
      'Connected' => 'Connected'
    ],
    'help' => [],
    'messages' => [
      'externalAccountNoConnectDisabled' => 'External account for integration \'{integration}\' has been disabled due not being able to connect.'
    ]
  ],
  'FieldManager' => [
    'labels' => [
      'Dynamic Logic' => 'Dynamic Logic',
      'Name' => 'Name',
      'Label' => 'Label',
      'Type' => 'Type',
      'View Details' => 'View Details'
    ],
    'options' => [
      'dateTimeDefault' => [
        '' => 'None',
        'javascript: return this.dateTime.getNow(1);' => 'Now',
        'javascript: return this.dateTime.getNow(5);' => 'Now (5m)',
        'javascript: return this.dateTime.getNow(15);' => 'Now (15m)',
        'javascript: return this.dateTime.getNow(30);' => 'Now (30m)',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'hours\', 15);' => '+1 hour',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'hours\', 15);' => '+2 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'hours\', 15);' => '+3 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'hours\', 15);' => '+4 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'hours\', 15);' => '+5 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'hours\', 15);' => '+6 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(7, \'hours\', 15);' => '+7 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(8, \'hours\', 15);' => '+8 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(9, \'hours\', 15);' => '+9 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(10, \'hours\', 15);' => '+10 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(11, \'hours\', 15);' => '+11 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(12, \'hours\', 15);' => '+12 hours',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'days\', 15);' => '+1 day',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(2, \'days\', 15);' => '+2 days',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(3, \'days\', 15);' => '+3 days',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(4, \'days\', 15);' => '+4 days',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(5, \'days\', 15);' => '+5 days',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(6, \'days\', 15);' => '+6 days',
        'javascript: return this.dateTime.getDateTimeShiftedFromNow(1, \'week\', 15);' => '+1 week'
      ],
      'dateDefault' => [
        '' => 'None',
        'javascript: return this.dateTime.getToday();' => 'Today',
        'javascript: return this.dateTime.getDateShiftedFromToday(1, \'days\');' => '+1 day',
        'javascript: return this.dateTime.getDateShiftedFromToday(2, \'days\');' => '+2 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(3, \'days\');' => '+3 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(4, \'days\');' => '+4 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(5, \'days\');' => '+5 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(6, \'days\');' => '+6 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(7, \'days\');' => '+7 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(8, \'days\');' => '+8 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(9, \'days\');' => '+9 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(10, \'days\');' => '+10 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(30, \'days\');' => '+30 days',
        'javascript: return this.dateTime.getDateShiftedFromToday(1, \'weeks\');' => '+1 week',
        'javascript: return this.dateTime.getDateShiftedFromToday(2, \'weeks\');' => '+2 weeks',
        'javascript: return this.dateTime.getDateShiftedFromToday(3, \'weeks\');' => '+3 weeks',
        'javascript: return this.dateTime.getDateShiftedFromToday(1, \'months\');' => '+1 month',
        'javascript: return this.dateTime.getDateShiftedFromToday(2, \'months\');' => '+2 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(3, \'months\');' => '+3 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(4, \'months\');' => '+4 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(5, \'months\');' => '+5 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(6, \'months\');' => '+6 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(7, \'months\');' => '+7 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(8, \'months\');' => '+8 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(9, \'months\');' => '+9 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(10, \'months\');' => '+10 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(11, \'months\');' => '+11 months',
        'javascript: return this.dateTime.getDateShiftedFromToday(1, \'year\');' => '+1 year'
      ],
      'barcodeType' => [
        'EAN13' => 'EAN-13',
        'EAN8' => 'EAN-8',
        'EAN5' => 'EAN-5',
        'EAN2' => 'EAN-2',
        'UPC' => 'UPC (A)',
        'UPCE' => 'UPC (E)',
        'pharmacode' => 'Pharmacode',
        'QRcode' => 'QR code'
      ],
      'globalRestrictions' => [
        'forbidden' => 'Forbidden',
        'internal' => 'Internal',
        'onlyAdmin' => 'Admin-only',
        'readOnly' => 'Read-only',
        'nonAdminReadOnly' => 'Non-admin read-only'
      ]
    ],
    'tooltips' => [
      'optionsReference' => 'Re-use options from another field.',
      'currencyDecimal' => 'Use the Decimal DB type. In the app, values will be represented as strings. Check this parameter if precision is required.',
      'cutHeight' => 'A text higher then a specified value will be cut with a \'show more\' button displayed.',
      'urlStrip' => 'Strip a protocol and a trailing slash.',
      'audited' => 'Updates will be logged in stream.',
      'required' => 'Field will be mandatory. Can\'t be left empty.',
      'default' => 'Value will be set by default upon creating.',
      'min' => 'Min acceptable value.',
      'max' => 'Max acceptable value.',
      'seeMoreDisabled' => 'If not checked, then long texts will be shortened.',
      'lengthOfCut' => 'How long text can be before it will be cut.',
      'maxLength' => 'Max acceptable length of text.',
      'before' => 'The date value should be before the date value of the specified field.',
      'after' => 'The date value should be after the date value of the specified field.',
      'readOnly' => 'Field value can\'t be specified by user. But can be calculated by formula.',
      'readOnlyAfterCreate' => 'The field value can be specified when creating a new record. After that, the field becomes read-only. It can still be calculated by formula.',
      'preview' => 'Show the preview button. Applicable if Markdown is enabled.',
      'fileAccept' => 'Which file types to accept. It\'s possible to add custom items.',
      'barcodeLastChar' => 'For EAN-13 type.',
      'maxFileSize' => 'If empty or 0 then no limit.',
      'conversionDisabled' => 'The currency conversion action won\'t be applied to this field.',
      'pattern' => 'A regular expression to check a field value against. Define an expression or select a predefined one.',
      'options' => 'A list of possible values and their labels.',
      'itemsEditable' => 'Items can be edited. Applicable only if no options are specified.',
      'optionsArray' => 'A list of possible values and their labels. If empty, the field will allow entering custom values.',
      'maxCount' => 'Maximum number of items allowed to be selected.',
      'displayAsList' => 'Each item in a new line.',
      'optionsVarchar' => 'A list of autocomplete values.',
      'linkReadOnly' => 'Field value can\'t be specified by user. But can be calculated by formula.

It will also disable the ability to create a related record from relationship panels.',
      'relateOnImport' => 'When importing with this field, it will automatically relate a record with a matching foreign record. Use this functionality only if the foreign field is considered as unique.'
    ],
    'fieldParts' => [
      'address' => [
        'street' => 'Street',
        'city' => 'City',
        'state' => 'State',
        'country' => 'Country',
        'postalCode' => 'Postal Code',
        'map' => 'Map'
      ],
      'personName' => [
        'salutation' => 'Salutation',
        'first' => 'First',
        'middle' => 'Middle',
        'last' => 'Last'
      ],
      'currency' => [
        'converted' => '(Converted)',
        'currency' => '(Currency)'
      ],
      'datetimeOptional' => [
        'date' => 'Date'
      ]
    ],
    'fieldInfo' => [
      'varchar' => 'A single-line text.',
      'decimal' => 'A decimal number with fixed-point precision.',
      'enum' => 'Selectbox, only one value can be selected.',
      'text' => 'A multiline text with markdown support.',
      'date' => 'Date w/o time.',
      'datetime' => 'Date and time',
      'currency' => 'A currency value. A float number with a currency code.',
      'int' => 'A whole number.',
      'float' => 'A number with a decimal part.',
      'bool' => 'A checkbox. Two possible values: true and false.',
      'multiEnum' => 'A list of values, multiple values can be selected. The list is ordered.',
      'checklist' => 'A list of checkboxes.',
      'array' => 'A list of values, similar to Multi-Enum field.',
      'address' => 'An address with street, city, state, postal code and country.',
      'url' => 'For storing links.',
      'urlMultiple' => 'Multiple links.',
      'wysiwyg' => 'A text with HTML support.',
      'file' => 'For file uploading.',
      'image' => 'For image uploading.',
      'attachmentMultiple' => 'Allows to upload multiple files.',
      'number' => 'An auto-incrementing number of string type with a possible prefix and specific length.',
      'autoincrement' => 'A generated read-only auto-incrementing integer number.',
      'barcode' => 'A barcode. Can be printed to PDF.',
      'email' => 'A set of email addresses with their parameters: Opted-out, Invalid, Primary.',
      'phone' => 'A set of phone numbers with their parameters: Type, Opted-out, Invalid, Primary.',
      'foreign' => 'A field of a related record. Read-only.',
      'link' => 'A record related through Belongs-To (many-to-one or one-to-one) relationship.',
      'linkParent' => 'A record related through Belongs-To-Parent relationship. Can be of different entity types.',
      'linkMultiple' => 'A set of records related through Has-Many (many-to-many or one-to-many) relationship. Not all relationships have their link-multiple fields. Only those do, where Link-Multiple parameter(s) is enabled.'
    ],
    'messages' => [
      'fieldCreatedAddToLayouts' => 'Field has been created. Now, you can add it to [layouts]({link})',
      'confirmRemove' => 'Are you sure you want to remove the *{field}* field?

Field removal does not remove data from the database. Data from the database will be removed if you run hard rebuild.',
      'fieldNameIsNotAllowed' => 'Field name \'{field}\' is not allowed.',
      'fieldAlreadyExists' => 'Field \'{field}\' already exists in \'{entityType}\'.',
      'linkWithSameNameAlreadyExists' => 'Link with the name \'{field}\' already exists in \'{entityType}\'.',
      'namingFieldLinkConflict' => 'Name \'{field}\' conflicts with link.'
    ],
    'otherFields' => [
      'attributes' => 'Attributes'
    ]
  ],
  'Formula' => [
    'labels' => [
      'Check Syntax' => 'Check Syntax',
      'Run' => 'Run'
    ],
    'fields' => [
      'target' => 'Target',
      'targetType' => 'Target Type',
      'script' => 'Script',
      'output' => 'Output',
      'error' => 'Error'
    ],
    'messages' => [
      'runSuccess' => 'Executed successfully.',
      'runError' => 'Error.',
      'checkSyntaxSuccess' => 'Syntax is correct.',
      'checkSyntaxError' => 'Syntax error.',
      'emptyScript' => 'Script is empty.'
    ],
    'tooltips' => [
      'output' => 'Print values with the function `output\\printLine`.'
    ]
  ],
  'Global' => [
    'scopeNames' => [
      'Note' => 'Note',
      'Email' => 'Email',
      'User' => 'User',
      'Team' => 'Team',
      'Role' => 'Role',
      'EmailTemplate' => 'Email Template',
      'EmailTemplateCategory' => 'Email Template Categories',
      'EmailAccount' => 'Personal Email Account',
      'EmailAccountScope' => 'Personal Email Account',
      'OutboundEmail' => 'Outbound Email',
      'ScheduledJob' => 'Scheduled Job',
      'ExternalAccount' => 'External Account',
      'Extension' => 'Extension',
      'Dashboard' => 'Dashboard',
      'InboundEmail' => 'Group Email Account',
      'Stream' => 'Stream',
      'Import' => 'Import',
      'ImportError' => 'Import Error',
      'Template' => 'PDF Template',
      'Job' => 'Job',
      'EmailFilter' => 'Email Filter',
      'Portal' => 'Portal',
      'PortalRole' => 'Portal Role',
      'Attachment' => 'Attachment',
      'EmailFolder' => 'Email Folder',
      'GroupEmailFolder' => 'Group Email Folder',
      'PortalUser' => 'Portal User',
      'ApiUser' => 'API User',
      'ScheduledJobLogRecord' => 'Scheduled Job Log Record',
      'PasswordChangeRequest' => 'Password Change Request',
      'ActionHistoryRecord' => 'Action History Record',
      'AuthToken' => 'Auth Token',
      'UniqueId' => 'Unique ID',
      'LastViewed' => 'Last Viewed',
      'Settings' => 'Settings',
      'FieldManager' => 'Field Manager',
      'Integration' => 'Integration',
      'LayoutManager' => 'Layout Manager',
      'EntityManager' => 'Entity Manager',
      'Export' => 'Export',
      'DynamicLogic' => 'Dynamic Logic',
      'DashletOptions' => 'Dashlet Options',
      'Admin' => 'Admin',
      'Global' => 'Global',
      'Preferences' => 'Preferences',
      'EmailAddress' => 'Email Address',
      'PhoneNumber' => 'Phone Number',
      'AppLogRecord' => 'App Log Record',
      'AuthLogRecord' => 'Auth Log Record',
      'AuthFailLogRecord' => 'Auth Fail Log Record',
      'LeadCapture' => 'Lead Capture Entry Point',
      'LeadCaptureLogRecord' => 'Lead Capture Log Record',
      'ArrayValue' => 'Array Value',
      'DashboardTemplate' => 'Dashboard Template',
      'Currency' => 'Currency',
      'LayoutSet' => 'Layout Set',
      'Webhook' => 'Webhook',
      'WebhookQueueItem' => 'Webhook Queue Item',
      'WebhookEventQueueItem' => 'Webhook Event Queue Item',
      'Mass Action' => 'Mass Action',
      'WorkingTimeCalendar' => 'Working Time Calendar',
      'WorkingTimeRange' => 'Working Time Exception',
      'AuthenticationProvider' => 'Authentication Provider',
      'GlobalStream' => 'Global Stream',
      'AddressCountry' => 'Address Country',
      'AppSecret' => 'App Secret',
      'OAuthProvider' => 'OAuth Provider',
      'OAuthAccount' => 'OAuth Account',
      'OpenApi' => 'OpenAPI',
      'CurrencyRecord' => 'Currency Record',
      'CurrencyRecordRate' => 'Currency Rate',
      'Account' => 'Account',
      'Contact' => 'Contact',
      'Lead' => 'Lead',
      'Target' => 'Target',
      'Opportunity' => 'Opportunity',
      'Meeting' => 'Meeting',
      'Calendar' => 'Calendar',
      'Call' => 'Call',
      'Task' => 'Task',
      'Case' => 'Case',
      'Document' => 'Document',
      'DocumentFolder' => 'Document Folder',
      'Campaign' => 'Campaign',
      'TargetList' => 'Target List',
      'MassEmail' => 'Mass Email',
      'EmailQueueItem' => 'Email Queue Item',
      'CampaignTrackingUrl' => 'Tracking URL',
      'Activities' => 'Activities',
      'KnowledgeBaseArticle' => 'Knowledge Base Article',
      'KnowledgeBaseCategory' => 'Knowledge Base Category',
      'CampaignLogRecord' => 'Campaign Log Record',
      'TargetListCategory' => 'Target List Category'
    ],
    'scopeNamesPlural' => [
      'Note' => 'Notes',
      'Email' => 'Emails',
      'User' => 'Users',
      'Team' => 'Teams',
      'Role' => 'Roles',
      'EmailTemplate' => 'Email Templates',
      'EmailTemplateCategory' => 'Email Template Categories',
      'EmailAccount' => 'Personal Email Accounts',
      'EmailAccountScope' => 'Personal Email Accounts',
      'OutboundEmail' => 'Outbound Emails',
      'ScheduledJob' => 'Scheduled Jobs',
      'ExternalAccount' => 'External Accounts',
      'Extension' => 'Extensions',
      'Dashboard' => 'Dashboard',
      'InboundEmail' => 'Group Email Accounts',
      'EmailAddress' => 'Email Addresses',
      'PhoneNumber' => 'Phone Numbers',
      'Stream' => 'Stream',
      'Import' => 'Import',
      'ImportError' => 'Import Errors',
      'Template' => 'PDF Templates',
      'Job' => 'Jobs',
      'EmailFilter' => 'Email Filters',
      'Portal' => 'Portals',
      'PortalRole' => 'Portal Roles',
      'Attachment' => 'Attachments',
      'EmailFolder' => 'Email Folders',
      'GroupEmailFolder' => 'Group Email Folders',
      'PortalUser' => 'Portal Users',
      'ApiUser' => 'API Users',
      'ScheduledJobLogRecord' => 'Scheduled Job Log Records',
      'PasswordChangeRequest' => 'Password Change Requests',
      'ActionHistoryRecord' => 'Action History',
      'AuthToken' => 'Auth Tokens',
      'UniqueId' => 'Unique IDs',
      'LastViewed' => 'Last Viewed',
      'AppLogRecord' => 'App Log',
      'AuthLogRecord' => 'Auth Log',
      'AuthFailLogRecord' => 'Auth Fail Log',
      'LeadCapture' => 'Lead Capture',
      'LeadCaptureLogRecord' => 'Lead Capture Log',
      'ArrayValue' => 'Array Values',
      'DashboardTemplate' => 'Dashboard Templates',
      'Currency' => 'Currency',
      'LayoutSet' => 'Layout Sets',
      'Webhook' => 'Webhooks',
      'WebhookQueueItem' => 'Webhook Queue Items',
      'WebhookEventQueueItem' => 'Webhook Event Queue Items',
      'WorkingTimeCalendar' => 'Working Time Calendars',
      'WorkingTimeRange' => 'Working Time Exceptions',
      'AuthenticationProvider' => 'Authentication Providers',
      'GlobalStream' => 'Global Stream',
      'AddressCountry' => 'Address Countries',
      'AppSecret' => 'App Secrets',
      'OAuthProvider' => 'OAuth Providers',
      'OAuthAccount' => 'OAuth Accounts',
      'OpenApi' => 'OpenAPI',
      'CurrencyRecord' => 'Currencies',
      'CurrencyRecordRate' => 'Currency Rates',
      'Account' => 'Accounts',
      'Contact' => 'Contacts',
      'Lead' => 'Leads',
      'Target' => 'Targets',
      'Opportunity' => 'Opportunities',
      'Meeting' => 'Meetings',
      'Calendar' => 'Calendar',
      'Call' => 'Calls',
      'Task' => 'Tasks',
      'Case' => 'Cases',
      'Document' => 'Documents',
      'DocumentFolder' => 'Document Folders',
      'Campaign' => 'Campaigns',
      'TargetList' => 'Target Lists',
      'MassEmail' => 'Mass Emails',
      'EmailQueueItem' => 'Email Queue Items',
      'CampaignTrackingUrl' => 'Tracking URLs',
      'Activities' => 'Activities',
      'KnowledgeBaseArticle' => 'Knowledge Base',
      'KnowledgeBaseCategory' => 'Knowledge Base Categories',
      'CampaignLogRecord' => 'Campaign Log Records',
      'TargetListCategory' => 'Target List Categories'
    ],
    'labels' => [
      'Previous Page' => 'Previous Page',
      'Next Page' => 'Next Page',
      'First Page' => 'First Page',
      'Last Page' => 'Last Page',
      'Page' => 'Page',
      'Sort' => 'Sort',
      'Column Resize' => 'Column Resize',
      'Misc' => 'Misc',
      'General' => 'General',
      'Merge' => 'Merge',
      'None' => 'None',
      'Home' => 'Home',
      'by' => 'by',
      'Proceed' => 'Proceed',
      'Saved' => 'Saved',
      'Error' => 'Error',
      'Select' => 'Select',
      'Not valid' => 'Not valid',
      'Please wait...' => 'Please wait...',
      'Please wait' => 'Please wait',
      'Attached' => 'Attached',
      'Loading...' => 'Loading...',
      'Uploading...' => 'Uploading...',
      'Sending...' => 'Sending...',
      'Send' => 'Send',
      'Merged' => 'Merged',
      'Removed' => 'Removed',
      'Posted' => 'Posted',
      'Linked' => 'Linked',
      'Unlinked' => 'Unlinked',
      'Done' => 'Done',
      'Access denied' => 'Access denied',
      'Not found' => 'Not found',
      'Access' => 'Access',
      'Timeout' => 'Timeout',
      'No internet' => 'No internet',
      'Network error' => 'Network error',
      'Are you sure?' => 'Are you sure?',
      'Record has been removed' => 'Record has been removed',
      'Wrong username/password' => 'Wrong username/password',
      'Post cannot be empty' => 'Post cannot be empty',
      'Username can not be empty!' => 'Username can not be empty!',
      'Cache is not enabled' => 'Cache is not enabled',
      'Cache has been cleared' => 'Cache has been cleared',
      'Rebuild has been done' => 'Rebuild has been done',
      'Return to Application' => 'Return to Application',
      'Modified' => 'Modified',
      'Created' => 'Created',
      'Create' => 'Create',
      'create' => 'create',
      'Scheduled' => 'Scheduled',
      'Overview' => 'Overview',
      'Details' => 'Details',
      'Add Field' => 'Add Field',
      'Add Dashlet' => 'Add Dashlet',
      'Filter' => 'Filter',
      'Edit Dashboard' => 'Edit Dashboard',
      'Add' => 'Add',
      'Add Item' => 'Add Item',
      'Reset' => 'Reset',
      'Menu' => 'Menu',
      'More' => 'More',
      'Search' => 'Search',
      'Only My' => 'Only My',
      'Open' => 'Open',
      'Admin' => 'Admin',
      'About' => 'About',
      'Refresh' => 'Refresh',
      'Remove' => 'Remove',
      'Restore' => 'Restore',
      'Options' => 'Options',
      'Username' => 'Username',
      'Password' => 'Password',
      'Login' => 'Login',
      'Log Out' => 'Log Out',
      'Log in' => 'Log in',
      'Log in as' => 'Log in as',
      'Sign in' => 'Sign in',
      'Preferences' => 'Preferences',
      'State' => 'State',
      'Street' => 'Street',
      'Country' => 'Country',
      'City' => 'City',
      'PostalCode' => 'Postal Code',
      'Star' => 'Star',
      'Unstar' => 'Unstar',
      'Starred' => 'Starred',
      'Followed' => 'Followed',
      'Follow' => 'Follow',
      'Followers' => 'Followers',
      'Clear Local Cache' => 'Clear Local Cache',
      'Actions' => 'Actions',
      'Delete' => 'Delete',
      'Update' => 'Update',
      'Save' => 'Save',
      'Edit' => 'Edit',
      'View' => 'View',
      'Cancel' => 'Cancel',
      'Apply' => 'Apply',
      'Unlink' => 'Unlink',
      'Mass Update' => 'Mass Update',
      'Export' => 'Export',
      'No Data' => 'No Data',
      'No Access' => 'No Access',
      'All' => 'All',
      'Active' => 'Active',
      'Inactive' => 'Inactive',
      'Write your comment here' => 'Write your comment here',
      'Post' => 'Post',
      'Stream' => 'Stream',
      'Show more' => 'Show more',
      'Dashlet Options' => 'Dashlet Options',
      'Full Form' => 'Full Form',
      'Insert' => 'Insert',
      'Person' => 'Person',
      'First Name' => 'First Name',
      'Last Name' => 'Last Name',
      'Middle Name' => 'Middle Name',
      'Original' => 'Original',
      'You' => 'You',
      'you' => 'you',
      'change' => 'change',
      'Change' => 'Change',
      'Primary' => 'Primary',
      'Save Filter' => 'Save Filter',
      'Remove Filter' => 'Remove Filter',
      'Ready' => 'Ready',
      'Administration' => 'Administration',
      'Run Import' => 'Run Import',
      'Duplicate' => 'Duplicate',
      'Notifications' => 'Notifications',
      'Mark all read' => 'Mark all read',
      'See more' => 'See more',
      'Today' => 'Today',
      'Tomorrow' => 'Tomorrow',
      'Yesterday' => 'Yesterday',
      'Now' => 'Now',
      'Submit' => 'Submit',
      'Close' => 'Close',
      'Yes' => 'Yes',
      'No' => 'No',
      'Select All Results' => 'Select All Results',
      'Value' => 'Value',
      'Edit Item' => 'Edit Item',
      'Current version' => 'Current version',
      'List View' => 'List View',
      'Tree View' => 'Tree View',
      'Unlink All' => 'Unlink All',
      'Total' => 'Total',
      'Print' => 'Print',
      'Print to PDF' => 'Print to PDF',
      'Default' => 'Default',
      'Number' => 'Number',
      'From' => 'From',
      'To' => 'To',
      'Create Post' => 'Create Post',
      'Previous Entry' => 'Previous Entry',
      'Next Entry' => 'Next Entry',
      'View List' => 'View List',
      'Attach File' => 'Attach File',
      'Skip' => 'Skip',
      'Attribute' => 'Attribute',
      'Function' => 'Function',
      'Self-Assign' => 'Self-Assign',
      'Self-Assigned' => 'Self-Assigned',
      'Expand' => 'Expand',
      'Collapse' => 'Collapse',
      'Expanded' => 'Expanded',
      'Collapsed' => 'Collapsed',
      'Top Level' => 'Top Level',
      'New notifications' => 'New notifications',
      'Manage Categories' => 'Manage Categories',
      'Manage Folders' => 'Manage Folders',
      'Convert to' => 'Convert to',
      'View Personal Data' => 'View Personal Data',
      'Personal Data' => 'Personal Data',
      'Erase' => 'Erase',
      'View Followers' => 'View Followers',
      'Convert Currency' => 'Convert Currency',
      'View on Map' => 'View on Map',
      'Preview' => 'Preview',
      'Move Over' => 'Move Over',
      'Up' => 'Up',
      'Save & Continue Editing' => 'Save & Continue Editing',
      'Save & New' => 'Save & New',
      'Field' => 'Field',
      'Fields' => 'Fields',
      'Resolution' => 'Resolution',
      'Resolve Conflict' => 'Resolve Conflict',
      'Download' => 'Download',
      'Global Search' => 'Global Search',
      'Navigation Panel' => 'Show Navigation Panel',
      'Copy to Clipboard' => 'Copy to Clipboard',
      'Copied to clipboard' => 'Copied to clipboard',
      'Audit Log' => 'Audit Log',
      'View Audit Log' => 'View Audit Log',
      'View User Access' => 'View User Access',
      'Reacted' => 'Reacted',
      'Reaction Removed' => 'Reaction Removed',
      'Reactions' => 'Reactions',
      'Schedule' => 'Schedule',
      'Log' => 'Log',
      'Scheduler' => 'Scheduler',
      'Create InboundEmail' => 'Create Inbound Email',
      'Activities' => 'Activities',
      'History' => 'History',
      'Attendees' => 'Attendees',
      'Schedule Meeting' => 'Schedule Meeting',
      'Schedule Call' => 'Schedule Call',
      'Compose Email' => 'Compose Email',
      'Log Meeting' => 'Log Meeting',
      'Log Call' => 'Log Call',
      'Archive Email' => 'Archive Email',
      'Create Task' => 'Create Task',
      'Tasks' => 'Tasks'
    ],
    'messages' => [
      'pleaseWait' => 'Please wait...',
      'loading' => 'Loading...',
      'saving' => 'Saving...',
      'confirmLeaveOutMessage' => 'Are you sure you want to leave the form?',
      'notModified' => 'You have not modified the record',
      'duplicate' => 'The record you are creating might already exist',
      'dropToAttach' => 'Drop to attach',
      'pageNumberIsOutOfBound' => 'Page number is out of bound',
      'fieldUrlExceedsMaxLength' => 'Encoded URL exceeds max length of {maxLength}',
      'fieldNotMatchingPattern' => '{field} does not match the pattern `{pattern}`',
      'fieldNotMatchingPattern$noBadCharacters' => '{field} contains not allowed characters',
      'fieldNotMatchingPattern$noAsciiSpecialCharacters' => '{field} should not contain ASCII special characters',
      'fieldNotMatchingPattern$latinLetters' => '{field} can contain only latin letters',
      'fieldNotMatchingPattern$latinLettersDigits' => '{field} can contain only latin letters and digits',
      'fieldNotMatchingPattern$latinLettersDigitsWhitespace' => '{field} can contain only latin letters, digits and whitespace',
      'fieldNotMatchingPattern$latinLettersWhitespace' => '{field} can contain only latin letters and whitespace',
      'fieldNotMatchingPattern$digits' => '{field} can contain only digits',
      'fieldNotMatchingPattern$uriOptionalProtocol' => '{field} must be a valid URL',
      'fieldNotMatchingPattern$phoneNumberLoose' => '{field} contains characters not allowed in a phone number',
      'fieldInvalid' => '{field} is invalid',
      'fieldIsRequired' => '{field} is required',
      'fieldPhoneInvalid' => '{field} is invalid',
      'fieldPhoneInvalidCode' => 'Invalid country code',
      'fieldPhoneTooShort' => '{field} is too short',
      'fieldPhoneTooLong' => '{field} is too long',
      'fieldPhoneInvalidCharacters' => 'Only digits, latin letters and characters `-+_@:#().` are allowed',
      'fieldPhoneExtensionTooLong' => 'Extension should not be longer than {maxLength}',
      'fieldShouldBeEmail' => '{field} should be a valid email',
      'fieldShouldBeFloat' => '{field} should be a valid float',
      'fieldShouldBeInt' => '{field} should be a valid integer',
      'fieldShouldBeNumber' => '{field} should be a valid number',
      'fieldShouldBeDate' => '{field} should be a valid date',
      'fieldShouldBeDatetime' => '{field} should be a valid date/time',
      'fieldShouldAfter' => '{field} should be after {otherField}',
      'fieldShouldBefore' => '{field} should be before {otherField}',
      'fieldShouldBeBetween' => '{field} should be between {min} and {max}',
      'fieldShouldBeLess' => '{field} shouldn\'t be greater than {value}',
      'fieldShouldBeGreater' => '{field} shouldn\'t be less than {value}',
      'fieldBadPasswordConfirm' => '{field} not confirmed properly',
      'fieldMaxFileSizeError' => 'File should not exceed {max} Mb',
      'fieldValueDuplicate' => 'Duplicate value',
      'fieldIsUploading' => 'Uploading in progress',
      'fieldExceedsMaxCount' => 'Count exceeds max allowed {maxCount}',
      'barcodeInvalid' => '{field} is not valid {type}',
      'arrayItemMaxLength' => 'Item shouldn\'t be longer than {max} characters',
      'arrayInputNotEmpty' => 'Item is entered but not added',
      'resetPreferencesDone' => 'Preferences has been reset to defaults',
      'confirmation' => 'Are you sure?',
      'unlinkAllConfirmation' => 'Are you sure you want to unlink all related records?',
      'resetPreferencesConfirmation' => 'Are you sure you want to reset preferences to defaults?',
      'removeRecordConfirmation' => 'Are you sure you want to remove the record?',
      'unlinkRecordConfirmation' => 'Are you sure you want to unlink the related record?',
      'removeSelectedRecordsConfirmation' => 'Are you sure you want to remove selected records?',
      'unlinkSelectedRecordsConfirmation' => 'Are you sure you want to unlink selected records?',
      'massUpdateResult' => '{count} records have been updated',
      'massUpdateResultSingle' => '{count} record has been updated',
      'recalculateFormulaConfirmation' => 'Are you sure you want to recalculate formula for selected records?',
      'noRecordsUpdated' => 'No records were updated',
      'massRemoveResult' => '{count} records have been removed',
      'massRemoveResultSingle' => '{count} record has been removed',
      'noRecordsRemoved' => 'No records were removed',
      'changesLossConfirmation' => 'Unsaved changes will be lost. Are you sure?',
      'clickToRefresh' => 'Click to refresh',
      'writeYourCommentHere' => 'Write your comment here',
      'writeMessageToUser' => 'Write a message to {user}',
      'writeMessageToSelf' => 'Write a message on your stream',
      'typeAndPressEnter' => 'Type & press enter',
      'checkForNewNotifications' => 'Check for new notifications',
      'checkForNewNotes' => 'Check for stream updates',
      'internalPost' => 'Post will be seen only by internal users',
      'internalPostTitle' => 'Post is seen only by internal users',
      'done' => 'Done',
      'notUpdated' => 'Not updated',
      'confirmMassUpdate' => 'Are you sure you want to mass-update selected records?',
      'confirmMassFollow' => 'Are you sure you want to follow selected records?',
      'confirmMassUnfollow' => 'Are you sure you want to unfollow selected records?',
      'massFollowResult' => '{count} records now are followed',
      'massUnfollowResult' => '{count} records now are not followed',
      'massFollowResultSingle' => '{count} record now is followed',
      'massUnfollowResultSingle' => '{count} record now is not followed',
      'massFollowZeroResult' => 'Nothing got followed',
      'massUnfollowZeroResult' => 'Nothing got unfollowed',
      'erasePersonalDataConfirmation' => 'Checked fields will be erased permanently. Are you sure?',
      'maintenanceModeError' => 'The application currently is in maintenance mode.',
      'maintenanceMode' => 'The application currently is in maintenance mode. Only admin users have access.

Maintenance mode can be disabled at Administration → Settings.',
      'resolveSaveConflict' => 'The record has been modified. You need to resolve the conflict before you can save the record.',
      'massPrintPdfMaxCountError' => 'Can\'t print more that {maxCount} records.',
      'massActionProcessed' => 'Mass action has been processed.',
      'validationFailure' => 'Backend validation failure.

Field: `{field}`
Validation: `{type}`',
      'extensionLicenseInvalid' => 'Invalid \'{name}\' extension license.',
      'extensionLicenseExpired' => 'The \'{name}\' extension license subscription has expired.',
      'extensionLicenseSoftExpired' => 'The \'{name}\' extension license subscription has expired.',
      'confirmAppRefresh' => 'The application has been updated. It is recommended to refresh the page to ensure the proper functioning.',
      'loggedOutLeaveOut' => 'Logged out. The session is inactive. You may lose unsaved form data after page refresh. You may need to make a copy.',
      'noAccessToRecord' => 'Operation requires `{action}` access to record.',
      'noAccessToForeignRecord' => 'Operation requires `{action}` access to foreign record.',
      'noLinkAccess' => 'Can\'t relate with {foreignEntityType} record through the link \'{link}\'. No access.',
      'cannotUnrelateRequiredLink' => 'Can\'t unrelate required link.',
      'cannotRelateNonExisting' => 'Can\'t relate with non-existing {foreignEntityType} record.',
      'cannotRelateForbidden' => 'Can\'t relate with forbidden {foreignEntityType} record. `{action}` access required.',
      'cannotRelateForbiddenLink' => 'No access to link \'{link}\'.',
      'cannotLinkAlreadyLinked' => 'Cannot link an already linked record.',
      'error404' => 'The url you requested can\'t be handled.',
      'error403' => 'You don\'t have access to this area.',
      'emptyMassUpdate' => 'No fields available for Mass Update.',
      'attemptIntervalFailure' => 'The operation is not allowed during a specific time interval. Wait for some time before the next attempt.',
      'confirmRestoreFromAudit' => 'The previous values will be set in a form. Then you can save the record to restore the previous values.',
      'starsLimitExceeded' => 'The number of stars exceeded the limit.',
      'select2OrMoreRecords' => 'Select 2 or more records',
      'selectNotMoreThanNumberRecords' => 'Select not more than {number} records',
      'selectAtLeastOneRecord' => 'Select at least one record',
      'duplicateConflict' => 'A record already exists.',
      'cannotRemoveCategoryWithChildCategory' => 'Cannot remove a category that has a child category.',
      'cannotRemoveNotEmptyCategory' => 'Cannot remove a non-empty category.',
      'sameRecordIsAlreadyBeingEdited' => 'The record is already being edited.'
    ],
    'boolFilters' => [
      'onlyMy' => 'Only My',
      'onlyMyTeam' => 'My Team',
      'followed' => 'Followed',
      'shared' => 'Shared'
    ],
    'presetFilters' => [
      'followed' => 'Followed',
      'all' => 'All',
      'starred' => 'Starred',
      'active' => 'Active'
    ],
    'massActions' => [
      'delete' => 'Delete',
      'remove' => 'Remove',
      'merge' => 'Merge',
      'update' => 'Update',
      'massUpdate' => 'Mass Update',
      'unlink' => 'Unlink',
      'export' => 'Export',
      'follow' => 'Follow',
      'unfollow' => 'Unfollow',
      'convertCurrency' => 'Convert Currency',
      'recalculateFormula' => 'Recalculate Formula',
      'printPdf' => 'Print to PDF'
    ],
    'fields' => [
      'name' => 'Name',
      'firstName' => 'First Name',
      'lastName' => 'Last Name',
      'middleName' => 'Middle Name',
      'salutationName' => 'Salutation',
      'assignedUser' => 'Assigned User',
      'assignedUsers' => 'Assigned Users',
      'collaborators' => 'Collaborators',
      'emailAddress' => 'Email',
      'emailAddressData' => 'Email Address Data',
      'emailAddressIsOptedOut' => 'Email Address is Opted-Out',
      'emailAddressIsInvalid' => 'Email Address is Invalid',
      'assignedUserName' => 'Assigned User Name',
      'teams' => 'Teams',
      'users' => 'Users',
      'createdAt' => 'Created At',
      'modifiedAt' => 'Modified At',
      'createdBy' => 'Created By',
      'modifiedBy' => 'Modified By',
      'streamUpdatedAt' => 'Stream Updated At',
      'description' => 'Description',
      'address' => 'Address',
      'phoneNumber' => 'Phone',
      'phoneNumberMobile' => 'Phone (Mobile)',
      'phoneNumberHome' => 'Phone (Home)',
      'phoneNumberFax' => 'Phone (Fax)',
      'phoneNumberOffice' => 'Phone (Office)',
      'phoneNumberOther' => 'Phone (Other)',
      'phoneNumberData' => 'Phone Number Data',
      'phoneNumberIsOptedOut' => 'Phone Number is Opted-Out',
      'phoneNumberIsInvalid' => 'Phone Number is Invalid',
      'order' => 'Order',
      'parent' => 'Parent',
      'children' => 'Children',
      'id' => 'ID',
      'ids' => 'IDs',
      'type' => 'Type',
      'names' => 'Names',
      'types' => 'Types',
      'targetListIsOptedOut' => 'Is Opted Out (Target List)',
      'childList' => 'Child List',
      'billingAddressCity' => 'City',
      'billingAddressCountry' => 'Country',
      'billingAddressPostalCode' => 'Postal Code',
      'billingAddressState' => 'State',
      'billingAddressStreet' => 'Street',
      'billingAddressMap' => 'Map',
      'addressCity' => 'City',
      'addressStreet' => 'Street',
      'addressCountry' => 'Country',
      'addressState' => 'State',
      'addressPostalCode' => 'Postal Code',
      'addressMap' => 'Map',
      'shippingAddressCity' => 'City (Shipping)',
      'shippingAddressStreet' => 'Street (Shipping)',
      'shippingAddressCountry' => 'Country (Shipping)',
      'shippingAddressState' => 'State (Shipping)',
      'shippingAddressPostalCode' => 'Postal Code (Shipping)',
      'shippingAddressMap' => 'Map (Shipping)'
    ],
    'links' => [
      'assignedUser' => 'Assigned User',
      'assignedUsers' => 'Assigned Users',
      'collaborators' => 'Collaborators',
      'createdBy' => 'Created By',
      'modifiedBy' => 'Modified By',
      'team' => 'Team',
      'roles' => 'Roles',
      'teams' => 'Teams',
      'users' => 'Users',
      'parent' => 'Parent',
      'children' => 'Children',
      'contacts' => 'Contacts',
      'opportunities' => 'Opportunities',
      'leads' => 'Leads',
      'meetings' => 'Meetings',
      'calls' => 'Calls',
      'tasks' => 'Tasks',
      'emails' => 'Emails',
      'accounts' => 'Accounts',
      'cases' => 'Cases',
      'documents' => 'Documents',
      'account' => 'Account',
      'opportunity' => 'Opportunity',
      'contact' => 'Contact'
    ],
    'dashlets' => [
      'Stream' => 'Stream',
      'Emails' => 'My Inbox',
      'Iframe' => 'Iframe',
      'Records' => 'Record List',
      'Memo' => 'Memo',
      'Leads' => 'My Leads',
      'Opportunities' => 'My Opportunities',
      'Tasks' => 'My Tasks',
      'Cases' => 'My Cases',
      'Calendar' => 'Calendar',
      'Calls' => 'My Calls',
      'Meetings' => 'My Meetings',
      'OpportunitiesByStage' => 'Opportunities by Stage',
      'OpportunitiesByLeadSource' => 'Opportunities by Lead Source',
      'SalesByMonth' => 'Sales by Month',
      'SalesPipeline' => 'Sales Pipeline',
      'Activities' => 'My Activities'
    ],
    'notificationMessages' => [
      'assign' => '{entityType} {entity} has been assigned to you',
      'emailReceived' => 'Email received from {from}',
      'entityRemoved' => '{user} removed {entityType} {entity}',
      'emailInbox' => '{user} added email {entity} to your inbox',
      'userPostReaction' => '{user} reacted to your {post}',
      'addedToCollaborators' => '{user} added you as a collaborator to {entityType} {entity}',
      'userPostInParentReaction' => '{user} reacted to your {post} in {entityType} {entity}',
      'eventAttendee' => '{user} added you to {entityType} {entity}'
    ],
    'streamMessages' => [
      'post' => '{user} posted on {entityType} {entity}',
      'attach' => '{user} attached on {entityType} {entity}',
      'status' => '{user} updated {field} of {entityType} {entity}',
      'update' => '{user} updated {entityType} {entity}',
      'postTargetTeam' => '{user} posted to team {target}',
      'postTargetTeams' => '{user} posted to teams {target}',
      'postTargetPortal' => '{user} posted to portal {target}',
      'postTargetPortals' => '{user} posted to portals {target}',
      'postTarget' => '{user} posted to {target}',
      'postTargetYou' => '{user} posted to you',
      'postTargetYouAndOthers' => '{user} posted to {target} and you',
      'postTargetAll' => '{user} posted to all',
      'postTargetSelf' => '{user} self-posted',
      'postTargetSelfAndOthers' => '{user} posted to {target} and themself',
      'mentionInPost' => '{user} mentioned {mentioned} in {entityType} {entity}',
      'mentionYouInPost' => '{user} mentioned you in {entityType} {entity}',
      'mentionInPostTarget' => '{user} mentioned {mentioned} in post',
      'mentionYouInPostTarget' => '{user} mentioned you in post to {target}',
      'mentionYouInPostTargetAll' => '{user} mentioned you in post to all',
      'mentionYouInPostTargetNoTarget' => '{user} mentioned you in post',
      'create' => '{user} created {entityType} {entity}',
      'createThis' => '{user} created this {entityType}',
      'createAssignedThis' => '{user} created this {entityType} assigned to {assignee}',
      'createAssigned' => '{user} created {entityType} {entity} assigned to {assignee}',
      'createAssignedYou' => '{user} created {entityType} {entity} assigned to you',
      'createAssignedThisSelf' => '{user} created this {entityType} self-assigned',
      'createAssignedSelf' => '{user} created {entityType} {entity} self-assigned',
      'assign' => '{user} assigned {entityType} {entity} to {assignee}',
      'assignThis' => '{user} assigned this {entityType} to {assignee}',
      'assignYou' => '{user} assigned {entityType} {entity} to you',
      'assignThisVoid' => '{user} unassigned this {entityType}',
      'assignVoid' => '{user} unassigned {entityType} {entity}',
      'assignThisSelf' => '{user} self-assigned this {entityType}',
      'assignSelf' => '{user} self-assigned {entityType} {entity}',
      'assignMultiAdd' => '{user} assigned {entity} to {assignee}',
      'assignMultiRemove' => '{user} unassigned {entity} from {removedAssignee}',
      'assignMultiAddRemove' => '{user} assigned {entity} to {assignee} and unassigned from {removedAssignee}',
      'assignMultiAddThis' => '{user} assigned this {entityType} to {assignee}',
      'assignMultiRemoveThis' => '{user} unassigned this {entityType} from {removedAssignee}',
      'assignMultiAddRemoveThis' => '{user} assigned this {entityType} to {assignee} and unassigned from {removedAssignee}',
      'postThis' => '{user} posted',
      'attachThis' => '{user} attached',
      'statusThis' => '{user} updated {field}',
      'updateThis' => '{user} updated this {entityType}',
      'createRelatedThis' => '{user} created {relatedEntityType} {relatedEntity} related to this {entityType}',
      'createRelated' => '{user} created {relatedEntityType} {relatedEntity} related to {entityType} {entity}',
      'relate' => '{user} linked {relatedEntityType} {relatedEntity} with {entityType} {entity}',
      'relateThis' => '{user} linked {relatedEntityType} {relatedEntity} with this {entityType}',
      'unrelate' => '{user} unlinked {relatedEntityType} {relatedEntity} from {entityType} {entity}',
      'unrelateThis' => '{user} unlinked {relatedEntityType} {relatedEntity} from this {entityType}',
      'emailReceivedFromThis' => 'Email received from {from}',
      'emailReceivedInitialFromThis' => 'Email received from {from}, this {entityType} created',
      'emailReceivedThis' => 'Email received',
      'emailReceivedInitialThis' => 'Email received, this {entityType} created',
      'emailReceivedFrom' => 'Email received from {from}, related to {entityType} {entity}',
      'emailReceivedFromInitial' => 'Email received from {from}, {entityType} {entity} created',
      'emailReceived' => 'Email received related to {entityType} {entity}',
      'emailReceivedInitial' => 'Email received: {entityType} {entity} created',
      'emailReceivedInitialFrom' => 'Email received from {from}, {entityType} {entity} created',
      'emailSent' => '{by} sent email related to {entityType} {entity}',
      'emailSentThis' => '{by} sent email',
      'eventConfirmationAccepted' => '{invitee} accepted participation in {entityType} {entity}',
      'eventConfirmationDeclined' => '{invitee} declined participation in {entityType} {entity}',
      'eventConfirmationTentative' => '{invitee} is tentative about participation in {entityType} {entity}',
      'eventConfirmationAcceptedThis' => '{invitee} accepted participation',
      'eventConfirmationDeclinedThis' => '{invitee} declined participation',
      'eventConfirmationTentativeThis' => '{invitee} is tentative about participation'
    ],
    'streamMessagesMale' => [
      'postTargetSelfAndOthers' => '{user} posted to {target} and himself'
    ],
    'streamMessagesFemale' => [
      'postTargetSelfAndOthers' => '{user} posted to {target} and herself'
    ],
    'lists' => [
      'monthNames' => [
        0 => 'January',
        1 => 'February',
        2 => 'March',
        3 => 'April',
        4 => 'May',
        5 => 'June',
        6 => 'July',
        7 => 'August',
        8 => 'September',
        9 => 'October',
        10 => 'November',
        11 => 'December'
      ],
      'monthNamesShort' => [
        0 => 'Jan',
        1 => 'Feb',
        2 => 'Mar',
        3 => 'Apr',
        4 => 'May',
        5 => 'Jun',
        6 => 'Jul',
        7 => 'Aug',
        8 => 'Sep',
        9 => 'Oct',
        10 => 'Nov',
        11 => 'Dec'
      ],
      'dayNames' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday'
      ],
      'dayNamesShort' => [
        0 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat'
      ],
      'dayNamesMin' => [
        0 => 'Su',
        1 => 'Mo',
        2 => 'Tu',
        3 => 'We',
        4 => 'Th',
        5 => 'Fr',
        6 => 'Sa'
      ]
    ],
    'durationUnits' => [
      'd' => 'd',
      'h' => 'h',
      'm' => 'm',
      's' => 's'
    ],
    'options' => [
      'salutationName' => [
        'Mr.' => 'Mr.',
        'Mrs.' => 'Mrs.',
        'Ms.' => 'Ms.',
        'Dr.' => 'Dr.'
      ],
      'language' => [
        'ar_AR' => 'Arabic',
        'af_ZA' => 'Afrikaans',
        'az_AZ' => 'Azerbaijani',
        'be_BY' => 'Belarusian',
        'bg_BG' => 'Bulgarian',
        'bn_IN' => 'Bengali',
        'bs_BA' => 'Bosnian',
        'ca_ES' => 'Catalan',
        'cs_CZ' => 'Czech',
        'cy_GB' => 'Welsh',
        'da_DK' => 'Danish',
        'de_DE' => 'German',
        'el_GR' => 'Greek',
        'en_GB' => 'English (UK)',
        'es_MX' => 'Spanish (Mexico)',
        'en_US' => 'English (US)',
        'es_ES' => 'Spanish (Spain)',
        'et_EE' => 'Estonian',
        'eu_ES' => 'Basque',
        'fa_IR' => 'Persian',
        'fi_FI' => 'Finnish',
        'fo_FO' => 'Faroese',
        'fr_CA' => 'French (Canada)',
        'fr_FR' => 'French (France)',
        'ga_IE' => 'Irish',
        'gl_ES' => 'Galician',
        'gn_PY' => 'Guarani',
        'he_IL' => 'Hebrew',
        'hi_IN' => 'Hindi',
        'hr_HR' => 'Croatian',
        'hu_HU' => 'Hungarian',
        'hy_AM' => 'Armenian',
        'id_ID' => 'Indonesian',
        'is_IS' => 'Icelandic',
        'it_IT' => 'Italian',
        'ja_JP' => 'Japanese',
        'ka_GE' => 'Georgian',
        'km_KH' => 'Khmer',
        'ko_KR' => 'Korean',
        'ku_TR' => 'Kurdish',
        'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian',
        'mk_MK' => 'Macedonian',
        'ml_IN' => 'Malayalam',
        'ms_MY' => 'Malay',
        'nb_NO' => 'Norwegian Bokmål',
        'nn_NO' => 'Norwegian Nynorsk',
        'ne_NP' => 'Nepali',
        'nl_NL' => 'Dutch',
        'pa_IN' => 'Punjabi',
        'pl_PL' => 'Polish',
        'ps_AF' => 'Pashto',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'ro_RO' => 'Romanian',
        'ru_RU' => 'Russian',
        'sk_SK' => 'Slovak',
        'sl_SI' => 'Slovene',
        'sq_AL' => 'Albanian',
        'sr_RS' => 'Serbian',
        'sv_SE' => 'Swedish',
        'sw_KE' => 'Swahili',
        'ta_IN' => 'Tamil',
        'te_IN' => 'Telugu',
        'th_TH' => 'Thai',
        'tl_PH' => 'Tagalog',
        'tr_TR' => 'Turkish',
        'uk_UA' => 'Ukrainian',
        'ur_PK' => 'Urdu',
        'vi_VN' => 'Vietnamese',
        'zh_CN' => 'Simplified Chinese (China)',
        'zh_HK' => 'Traditional Chinese (Hong Kong)',
        'zh_TW' => 'Traditional Chinese (Taiwan)'
      ],
      'dateSearchRanges' => [
        'on' => 'On',
        'notOn' => 'Not On',
        'after' => 'After',
        'before' => 'Before',
        'between' => 'Between',
        'today' => 'Today',
        'past' => 'Past',
        'future' => 'Future',
        'currentMonth' => 'Current Month',
        'lastMonth' => 'Last Month',
        'nextMonth' => 'Next Month',
        'currentQuarter' => 'Current Quarter',
        'lastQuarter' => 'Last Quarter',
        'currentYear' => 'Current Year',
        'lastYear' => 'Last Year',
        'lastSevenDays' => 'Last 7 Days',
        'lastXDays' => 'Last X Days',
        'nextXDays' => 'Next X Days',
        'ever' => 'Ever',
        'isEmpty' => 'Is Empty',
        'olderThanXDays' => 'Older Than X Days',
        'afterXDays' => 'After X Days',
        'currentFiscalYear' => 'Current Fiscal Year',
        'lastFiscalYear' => 'Last Fiscal Year',
        'currentFiscalQuarter' => 'Current Fiscal Quarter',
        'lastFiscalQuarter' => 'Last Fiscal Quarter'
      ],
      'searchRanges' => [
        'is' => 'Is',
        'isEmpty' => 'Is Empty',
        'isNotEmpty' => 'Is Not Empty',
        'isOneOf' => 'Any Of',
        'isFromTeams' => 'Is From Team',
        'isNot' => 'Is Not',
        'isNotOneOf' => 'None Of',
        'anyOf' => 'Any Of',
        'allOf' => 'All Of',
        'noneOf' => 'None Of',
        'any' => 'Any'
      ],
      'varcharSearchRanges' => [
        'equals' => 'Equals',
        'like' => 'Is Like (%)',
        'notLike' => 'Is Not Like (%)',
        'startsWith' => 'Starts With',
        'endsWith' => 'Ends With',
        'contains' => 'Contains',
        'notContains' => 'Not Contains',
        'isEmpty' => 'Is Empty',
        'isNotEmpty' => 'Is Not Empty',
        'notEquals' => 'Not Equals',
        'anyOf' => 'Any Of',
        'noneOf' => 'None Of'
      ],
      'intSearchRanges' => [
        'equals' => 'Equals',
        'notEquals' => 'Not Equals',
        'greaterThan' => 'Greater Than',
        'lessThan' => 'Less Than',
        'greaterThanOrEquals' => 'Greater Than or Equals',
        'lessThanOrEquals' => 'Less Than or Equals',
        'between' => 'Between',
        'isEmpty' => 'Is Empty',
        'isNotEmpty' => 'Is Not Empty'
      ],
      'autorefreshInterval' => [
        0 => 'None',
        '0.5' => '30 seconds',
        1 => '1 minute',
        2 => '2 minutes',
        5 => '5 minutes',
        10 => '10 minutes'
      ],
      'phoneNumber' => [
        'Mobile' => 'Mobile',
        'Office' => 'Office',
        'Fax' => 'Fax',
        'Home' => 'Home',
        'Other' => 'Other'
      ],
      'saveConflictResolution' => [
        'current' => 'Current',
        'actual' => 'Actual',
        'original' => 'Original'
      ],
      'reminderTypes' => [
        'Popup' => 'Popup',
        'Email' => 'Email'
      ]
    ],
    'sets' => [
      'summernote' => [
        'NOTICE' => 'You can find translation here: https://github.com/HackerWins/summernote/tree/master/lang',
        'font' => [
          'bold' => 'Bold',
          'italic' => 'Italic',
          'underline' => 'Underline',
          'strike' => 'Strike',
          'clear' => 'Remove Font Style',
          'height' => 'Line Height',
          'name' => 'Font Family',
          'size' => 'Font Size'
        ],
        'image' => [
          'image' => 'Picture',
          'insert' => 'Insert Image',
          'resizeFull' => 'Resize Full',
          'resizeHalf' => 'Resize Half',
          'resizeQuarter' => 'Resize Quarter',
          'floatLeft' => 'Float Left',
          'floatRight' => 'Float Right',
          'floatNone' => 'Float None',
          'dragImageHere' => 'Drag an image here',
          'selectFromFiles' => 'Select from files',
          'url' => 'Image URL',
          'remove' => 'Remove Image'
        ],
        'link' => [
          'link' => 'Link',
          'insert' => 'Insert Link',
          'unlink' => 'Unlink',
          'edit' => 'Edit',
          'textToDisplay' => 'Text to display',
          'url' => 'To what URL should this link go?',
          'openInNewWindow' => 'Open in new window'
        ],
        'video' => [
          'video' => 'Video',
          'videoLink' => 'Video Link',
          'insert' => 'Insert Video',
          'url' => 'Video URL?',
          'providers' => '(YouTube, Vimeo, Vine, Instagram, or DailyMotion)'
        ],
        'table' => [
          'table' => 'Table'
        ],
        'hr' => [
          'insert' => 'Insert Horizontal Rule'
        ],
        'style' => [
          'style' => 'Style',
          'normal' => 'Normal',
          'blockquote' => 'Quote',
          'pre' => 'Code',
          'h1' => 'Header 1',
          'h2' => 'Header 2',
          'h3' => 'Header 3',
          'h4' => 'Header 4',
          'h5' => 'Header 5',
          'h6' => 'Header 6'
        ],
        'lists' => [
          'unordered' => 'Unordered list',
          'ordered' => 'Ordered list'
        ],
        'options' => [
          'help' => 'Help',
          'fullscreen' => 'Full Screen',
          'codeview' => 'Code View'
        ],
        'paragraph' => [
          'paragraph' => 'Paragraph',
          'outdent' => 'Outdent',
          'indent' => 'Indent',
          'left' => 'Align left',
          'center' => 'Align center',
          'right' => 'Align right',
          'justify' => 'Justify full'
        ],
        'color' => [
          'recent' => 'Recent Color',
          'more' => 'More Color',
          'background' => 'Back Color',
          'foreground' => 'Font Color',
          'transparent' => 'Transparent',
          'setTransparent' => 'Set transparent',
          'reset' => 'Reset',
          'resetToDefault' => 'Reset to default'
        ],
        'shortcut' => [
          'shortcuts' => 'Keyboard shortcuts',
          'close' => 'Close',
          'textFormatting' => 'Text formatting',
          'action' => 'Action',
          'paragraphFormatting' => 'Paragraph formatting',
          'documentStyle' => 'Document Style'
        ],
        'history' => [
          'undo' => 'Undo',
          'redo' => 'Redo'
        ]
      ]
    ],
    'listViewModes' => [
      'list' => 'List',
      'kanban' => 'Kanban'
    ],
    'themes' => [
      'Dark' => 'Dark',
      'Light' => 'Light',
      'Espo' => 'Espo',
      'EspoRtl' => 'RTL',
      'Sakura' => 'Sakura',
      'Violet' => 'Violet',
      'Hazyblue' => 'Hazyblue',
      'Glass' => 'Glass'
    ],
    'themeNavbars' => [
      'side' => 'Side Navbar',
      'top' => 'Top Navbar'
    ],
    'fieldValidations' => [
      'required' => 'Required',
      'maxCount' => 'Max Count',
      'maxLength' => 'Max Length',
      'pattern' => 'Pattern Matching',
      'emailAddress' => 'Valid Email Address',
      'phoneNumber' => 'Valid Phone Number',
      'array' => 'Array',
      'arrayOfString' => 'Array of Strings',
      'valid' => 'Validity',
      'noEmptyString' => 'No Empty String',
      'max' => 'Max Value',
      'min' => 'Min Value'
    ],
    'fieldValidationExplanations' => [
      'valid' => 'Invalid value.',
      'maxLength' => 'Value length exceeds maximum value.',
      'phone_valid' => 'Phone number is not valid. May be caused by a wrong or empty country code.',
      'url_valid' => 'Invalid URL value.',
      'currency_valid' => 'Invalid amount value.',
      'currency_validCurrency' => 'The currency code value is invalid or not allowed.',
      'varchar_pattern' => 'Likely, the value contains not allowed characters.',
      'email_emailAddress' => 'Invalid email address value.',
      'phone_phoneNumber' => 'Invalid phone number value.',
      'datetimeOptional_valid' => 'Invalid date-time value.',
      'datetime_valid' => 'Invalid date-time value.',
      'date_valid' => 'Invalid date value.',
      'enum_valid' => 'Invalid enum value. The value must be one of defined enum options. An empty value is allowed only if the field has an empty option.',
      'int_valid' => 'Invalid integer number value.',
      'float_valid' => 'Invalid number value.',
      'multiEnum_valid' => 'Invalid multi-enum value. Values must be one of defined field options.'
    ],
    'navbarTabs' => [
      'Business' => 'Business',
      'Marketing' => 'Marketing',
      'Support' => 'Support',
      'CRM' => 'CRM',
      'Activities' => 'Activities'
    ],
    'wysiwygLabels' => [
      'cell' => 'Cell',
      'align' => 'Align',
      'width' => 'Width',
      'height' => 'Height',
      'borderWidth' => 'Border Width',
      'borderColor' => 'Border Color',
      'cellPadding' => 'Cell Padding',
      'backgroundColor' => 'Background Color',
      'verticalAlign' => 'Vertical Align'
    ],
    'wysiwygOptions' => [
      'align' => [
        'left' => 'Left',
        'center' => 'Center',
        'right' => 'Right'
      ],
      'verticalAlign' => [
        'top' => 'Top',
        'middle' => 'Middle',
        'bottom' => 'Bottom'
      ]
    ],
    'detailViewModes' => [
      'detail' => 'Detail'
    ],
    'strings' => [
      'yesterdayShort' => 'Yest'
    ],
    'reactions' => [
      'Smile' => 'Smile',
      'Surprise' => 'Surprise',
      'Laugh' => 'Laugh',
      'Meh' => 'Meh',
      'Sad' => 'Sad',
      'Love' => 'Love',
      'Like' => 'Like',
      'Dislike' => 'Dislike'
    ],
    'recordActions' => [
      'create' => 'Create',
      'read' => 'Read',
      'edit' => 'Edit',
      'delete' => 'Delete',
      'stream' => 'Stream'
    ],
    'tabs' => [
      'Stream' => 'Stream'
    ]
  ],
  'GroupEmailFolder' => [
    'links' => [
      'emails' => 'Emails'
    ],
    'labels' => [
      'Create GroupEmailFolder' => 'Create Folder'
    ]
  ],
  'Import' => [
    'labels' => [
      'New import with same params' => 'New import with same params',
      'Revert Import' => 'Revert Import',
      'Return to Import' => 'Return to Import',
      'Run Import' => 'Run Import',
      'Back' => 'Back',
      'Field Mapping' => 'Field Mapping',
      'Default Values' => 'Default Values',
      'Add Field' => 'Add Field',
      'Created' => 'Created',
      'Updated' => 'Updated',
      'Result' => 'Result',
      'Show records' => 'Show records',
      'Remove Duplicates' => 'Remove Duplicates',
      'importedCount' => 'Imported (count)',
      'duplicateCount' => 'Duplicates (count)',
      'updatedCount' => 'Updated (count)',
      'Create Only' => 'Create Only',
      'Create and Update' => 'Create & Update',
      'Update Only' => 'Update Only',
      'Update by' => 'Update by',
      'Set as Not Duplicate' => 'Set as Not Duplicate',
      'File (CSV)' => 'File (CSV)',
      'First Row Value' => 'First Row Value',
      'Skip' => 'Skip',
      'Header Row Value' => 'Header Row Value',
      'Field' => 'Field',
      'What to Import?' => 'What to Import?',
      'Entity Type' => 'Entity Type',
      'What to do?' => 'What to do?',
      'Parameters' => 'Parameters',
      'Header Row' => 'Header Row',
      'Person Name Format' => 'Person Name Format',
      'John Smith' => 'John Smith',
      'Smith John' => 'Smith John',
      'Smith, John' => 'Smith, John',
      'Field Delimiter' => 'Field Delimiter',
      'Date Format' => 'Date Format',
      'Decimal Mark' => 'Decimal Mark',
      'Text Qualifier' => 'Text Qualifier',
      'Time Format' => 'Time Format',
      'Currency' => 'Currency',
      'Preview' => 'Preview',
      'Next' => 'Next',
      'Step 1' => 'Step 1',
      'Step 2' => 'Step 2',
      'Double Quote' => 'Double Quote',
      'Single Quote' => 'Single Quote',
      'Imported' => 'Imported',
      'Duplicates' => 'Duplicates',
      'Skip searching for duplicates' => 'Skip searching for duplicates',
      'Timezone' => 'Timezone',
      'Remove Import Log' => 'Remove Import Log',
      'New Import' => 'New Import',
      'Import Results' => 'Import Results',
      'Run Manually' => 'Run Manually',
      'Silent Mode' => 'Silent Mode',
      'Export' => 'Export'
    ],
    'messages' => [
      'importRunning' => 'Import running...',
      'noErrors' => 'No errors',
      'utf8' => 'Should be UTF-8 encoded',
      'duplicatesRemoved' => 'Duplicates removed',
      'inIdle' => 'Execute in idle (for big data; via cron)',
      'revert' => 'This will remove all imported records permanently.',
      'removeDuplicates' => 'This will permanently remove all imported records that were recognized as duplicates.',
      'confirmRevert' => 'This will remove all imported records permanently. Are you sure?',
      'confirmRemoveDuplicates' => 'This will permanently remove all imported records that were recognized as duplicates. Are you sure?',
      'confirmRemoveImportLog' => 'This will remove the import log. All imported records will be kept. You won\'t be able to revert import results. Are you sure?',
      'removeImportLog' => 'This will remove the import log. All imported records will be kept. Use it if you are sure that import is fine.'
    ],
    'params' => [
      'phoneNumberCountry' => 'Telephone country code'
    ],
    'fields' => [
      'file' => 'File',
      'entityType' => 'Entity Type',
      'imported' => 'Imported Records',
      'duplicates' => 'Duplicate Records',
      'updated' => 'Updated Records',
      'status' => 'Status'
    ],
    'links' => [
      'errors' => 'Errors'
    ],
    'options' => [
      'status' => [
        'Failed' => 'Failed',
        'Standby' => 'Standby',
        'Pending' => 'Pending',
        'In Process' => 'In Process',
        'Complete' => 'Complete'
      ],
      'personNameFormat' => [
        'f l' => 'First Last',
        'l f' => 'Last First',
        'f m l' => 'First Middle Last',
        'l f m' => 'Last First Middle',
        'l, f' => 'Last, First'
      ]
    ],
    'strings' => [
      'commandToRun' => 'Command to run (from CLI)',
      'saveAsDefault' => 'Save as default'
    ],
    'tooltips' => [
      'manualMode' => 'If checked, you will need to run import manually from CLI. Command will be shown after setting up the import.',
      'silentMode' => 'A majority of after-save scripts will be skipped, stream notes won\'t be created. Import will run faster.'
    ]
  ],
  'ImportError' => [
    'fields' => [
      'type' => 'Type',
      'validationFailures' => 'Validation Failures',
      'import' => 'Import',
      'rowIndex' => 'Row Index',
      'exportRowIndex' => 'Export Row Index',
      'lineNumber' => 'Line Number',
      'exportLineNumber' => 'Export Line Number',
      'row' => 'Row',
      'entityType' => 'Entity Type'
    ],
    'options' => [
      'type' => [
        'Validation' => 'Validation',
        'Access' => 'Access',
        'Not-Found' => 'Not-Found'
      ]
    ],
    'tooltips' => [
      'lineNumber' => 'A line number in the original CSV.',
      'exportLineNumber' => 'A line number in the export CSV.'
    ]
  ],
  'InboundEmail' => [
    'fields' => [
      'name' => 'Name',
      'emailAddress' => 'Email Address',
      'team' => 'Target Team',
      'status' => 'Status',
      'assignToUser' => 'Assign to User',
      'host' => 'Host',
      'username' => 'Username',
      'password' => 'Password',
      'port' => 'Port',
      'monitoredFolders' => 'Monitored Folders',
      'trashFolder' => 'Trash Folder',
      'security' => 'Security',
      'createCase' => 'Create Case',
      'reply' => 'Auto-Reply',
      'caseDistribution' => 'Case Distribution',
      'replyEmailTemplate' => 'Reply Email Template',
      'replyFromAddress' => 'Reply From Address',
      'replyToAddress' => 'Reply To Address',
      'replyFromName' => 'Reply From Name',
      'targetUserPosition' => 'Target User Position',
      'fetchSince' => 'Fetch Since',
      'addAllTeamUsers' => 'For all team users',
      'teams' => 'Teams',
      'sentFolder' => 'Sent Folder',
      'storeSentEmails' => 'Store Sent Emails',
      'keepFetchedEmailsUnread' => 'Keep Fetched Emails Unread',
      'connectedAt' => 'Connected At',
      'excludeFromReply' => 'Exclude from Reply',
      'useImap' => 'Fetch Emails',
      'useSmtp' => 'Use SMTP',
      'smtpHost' => 'SMTP Host',
      'smtpPort' => 'SMTP Port',
      'smtpAuth' => 'SMTP Auth',
      'smtpSecurity' => 'SMTP Security',
      'smtpAuthMechanism' => 'SMTP Auth Mechanism',
      'smtpUsername' => 'SMTP Username',
      'smtpPassword' => 'SMTP Password',
      'fromName' => 'From Name',
      'smtpIsShared' => 'SMTP Is Shared',
      'smtpIsForMassEmail' => 'SMTP Is for Mass Email',
      'groupEmailFolder' => 'Group Email Folder',
      'isSystem' => 'Is System'
    ],
    'tooltips' => [
      'isSystem' => 'Is the system email account.',
      'useSmtp' => 'The ability to send emails.',
      'reply' => 'Notify email senders that their emails has been received.

 Only one email will be sent to a particular recipient during some period of time to prevent looping.',
      'createCase' => 'Automatically create cases from incoming emails.',
      'replyToAddress' => 'To route responses to this email account, specify the email address of this account.',
      'caseDistribution' => 'How cases will be assigned to. Assigned directly to the user or among the team.',
      'assignToUser' => 'User cases will be assigned to.',
      'team' => 'Team cases will be assigned to.',
      'teams' => 'Teams emails will be assigned to.',
      'targetUserPosition' => 'Users with specified position will be distributed with cases.',
      'addAllTeamUsers' => 'Emails will be appearing in Inbox of all users of specified teams.',
      'monitoredFolders' => 'Multiple folders should be separated by comma.',
      'smtpIsShared' => 'If checked, then users will be able to send emails using this SMTP. Availability is controlled by Roles through the Group Email Account permission.',
      'smtpIsForMassEmail' => 'If checked, then SMTP will be available for Mass Email.',
      'storeSentEmails' => 'Sent emails will be stored on the IMAP server.',
      'groupEmailFolder' => 'Put incoming emails in a group folder.',
      'excludeFromReply' => 'When replying on emails sent to this account\'s email address, its email address won\'t be added to CC.

Note that by enabling this parameter, the email address of this account will be exposed to users who have access to send Emails.'
    ],
    'links' => [
      'filters' => 'Filters',
      'emails' => 'Emails',
      'assignToUser' => 'Assign to User',
      'groupEmailFolder' => 'Group Email Folder'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ],
      'caseDistribution' => [
        '' => 'None',
        'Direct-Assignment' => 'Direct-Assignment',
        'Round-Robin' => 'Round-Robin',
        'Least-Busy' => 'Least-Busy'
      ],
      'smtpAuthMechanism' => [
        'plain' => 'PLAIN',
        'login' => 'LOGIN',
        'crammd5' => 'CRAM-MD5'
      ],
      'smtpSecurity' => [
        'SSL' => 'SSL/TLS',
        'TLS' => 'STARTTLS'
      ],
      'security' => [
        'SSL' => 'SSL/TLS',
        'TLS' => 'STARTTLS'
      ]
    ],
    'labels' => [
      'Create InboundEmail' => 'Create Email Account',
      'IMAP' => 'IMAP',
      'Actions' => 'Actions',
      'Main' => 'Main'
    ],
    'messages' => [
      'couldNotConnectToImap' => 'Could not connect to IMAP server',
      'imapNotConnected' => 'Could not connect to group [IMAP account](#InboundEmail/view/{id}).'
    ]
  ],
  'Integration' => [
    'fields' => [
      'enabled' => 'Enabled',
      'clientId' => 'Client ID',
      'clientSecret' => 'Client Secret',
      'redirectUri' => 'Redirect URI',
      'apiKey' => 'API Key',
      'siteKey' => 'Site Key',
      'secretKey' => 'Secret Key',
      'scoreThreshold' => 'Score Threshold',
      'mapId' => 'Map ID'
    ],
    'titles' => [
      'GoogleMaps' => 'Google Maps',
      'GoogleReCaptcha' => 'Google reCAPTCHA'
    ],
    'messages' => [
      'selectIntegration' => 'Select an integration from menu.',
      'noIntegrations' => 'No Integrations is available.'
    ],
    'help' => [
      'GoogleReCaptcha' => 'Obtain the Site Key and Secret Key from [Google](https://www.google.com/recaptcha/).',
      'Google' => '**Obtain OAuth 2.0 credentials from the Google Developers Console.**

Visit [Google Developers Console](https://console.developers.google.com/project) to obtain OAuth 2.0 credentials such as a Client ID and Client Secret that are known to both Google and EspoCRM application.',
      'GoogleMaps' => 'Obtain API key [here](https://developers.google.com/maps/documentation/javascript/get-api-key).'
    ]
  ],
  'Job' => [
    'fields' => [
      'status' => 'Status',
      'executeTime' => 'Execute At',
      'executedAt' => 'Executed At',
      'startedAt' => 'Started At',
      'attempts' => 'Attempts Left',
      'failedAttempts' => 'Failed Attempts',
      'serviceName' => 'Service',
      'method' => 'Method (deprecated)',
      'methodName' => 'Method',
      'scheduledJob' => 'Scheduled Job',
      'scheduledJobJob' => 'Scheduled Job Name',
      'data' => 'Data',
      'targetType' => 'Target Type',
      'targetId' => 'Target ID',
      'number' => 'Number',
      'queue' => 'Queue',
      'group' => 'Group',
      'className' => 'Class Name',
      'targetGroup' => 'Target Group',
      'job' => 'Job'
    ],
    'options' => [
      'status' => [
        'Pending' => 'Pending',
        'Success' => 'Success',
        'Running' => 'Running',
        'Failed' => 'Failed'
      ]
    ]
  ],
  'LayoutManager' => [
    'fields' => [
      'width' => 'Width',
      'link' => 'Link',
      'notSortable' => 'Not Sortable',
      'align' => 'Align',
      'panelName' => 'Panel Name',
      'style' => 'Style',
      'sticked' => 'Stick to top',
      'isMuted' => 'Muted color',
      'isLarge' => 'Large font size',
      'hidden' => 'Hidden',
      'noLabel' => 'No Label',
      'dynamicLogicVisible' => 'Conditions making panel visible',
      'dynamicLogicStyled' => 'Conditions making style applied',
      'tabLabel' => 'Tab Label',
      'tabBreak' => 'Tab-Break',
      'noteText' => 'Note Text',
      'noteStyle' => 'Note Style'
    ],
    'options' => [
      'align' => [
        'left' => 'Left',
        'right' => 'Right'
      ],
      'style' => [
        'default' => 'Default',
        'success' => 'Success',
        'danger' => 'Danger',
        'info' => 'Info',
        'warning' => 'Warning',
        'primary' => 'Primary'
      ]
    ],
    'labels' => [
      'New panel' => 'New panel',
      'Layout' => 'Layout'
    ],
    'messages' => [
      'alreadyExists' => 'Layout `{name}` already exists.',
      'createInfo' => 'Custom list layouts can be used by relationship panels.',
      'cantBeEmpty' => 'Layout can\'t be empty.',
      'fieldsIncompatible' => 'Fields can\'t be on the layout together: {fields}.'
    ],
    'tooltips' => [
      'noteText' => 'A text to be displayed in the panel. Markdown is supported.',
      'tabBreak' => 'A separate tab for the panel and all following panels until the next tab-break.',
      'noLabel' => 'Don\'t display a column label in the header.',
      'notSortable' => 'Disables the ability to sort by the column.',
      'width' => 'A column width. It\'s recommended to have one column without specified width, usually it should be the *Name* field.',
      'sticked' => 'The panel will be sticked to the panel above. No gap between panels.',
      'hiddenPanel' => 'Need to click \'show more\' to see the panel.',
      'panelStyle' => 'A color of the panel.',
      'dynamicLogicVisible' => 'If set, the panel will be hidden unless the condition is met.',
      'dynamicLogicStyled' => 'A color will be applied if a specific condition is met . The color is defined by the *Style* parameter.',
      'link' => 'If checked, then a field value will be displayed as a link pointing to the detail view of the record. Usually it is used for *Name* fields.'
    ]
  ],
  'LayoutSet' => [
    'fields' => [
      'layoutList' => 'Layouts'
    ],
    'labels' => [
      'Create LayoutSet' => 'Create Layout Set',
      'Edit Layouts' => 'Edit Layouts'
    ],
    'tooltips' => []
  ],
  'LeadCapture' => [
    'fields' => [
      'name' => 'Name',
      'campaign' => 'Campaign',
      'isActive' => 'Is Active',
      'subscribeToTargetList' => 'Subscribe to Target List',
      'subscribeContactToTargetList' => 'Subscribe Contact if exists',
      'targetList' => 'Target List',
      'fieldList' => 'Payload Fields',
      'optInConfirmation' => 'Double Opt-In',
      'optInConfirmationEmailTemplate' => 'Opt-in confirmation email template',
      'optInConfirmationLifetime' => 'Opt-in confirmation lifetime (hours)',
      'optInConfirmationSuccessMessage' => 'Text to show after opt-in confirmation',
      'leadSource' => 'Lead Source',
      'apiKey' => 'API Key',
      'targetTeam' => 'Target Team',
      'exampleRequestMethod' => 'Method',
      'exampleRequestUrl' => 'URL',
      'exampleRequestPayload' => 'Payload',
      'exampleRequestHeaders' => 'Headers',
      'createLeadBeforeOptInConfirmation' => 'Create Lead before confirmation',
      'skipOptInConfirmationIfSubscribed' => 'Skip confirmation if lead is already in target list',
      'smtpAccount' => 'SMTP Account',
      'inboundEmail' => 'Group Email Account',
      'duplicateCheck' => 'Duplicate Check',
      'phoneNumberCountry' => 'Telephone country code',
      'fieldParams' => 'Field Params',
      'formId' => 'Form ID',
      'formEnabled' => 'Web Form',
      'formUrl' => 'Form URL',
      'formTitle' => 'Form Title',
      'formTheme' => 'Form Theme',
      'formSuccessText' => 'Text to display after form submission',
      'formText' => 'Text to display on form',
      'formSuccessRedirectUrl' => 'URL to redirect to after form submission',
      'formLanguage' => 'Language used on form',
      'formFrameAncestors' => 'Allowed hosts for form embedding',
      'formCaptcha' => 'Use Captcha'
    ],
    'links' => [
      'targetList' => 'Target List',
      'campaign' => 'Campaign',
      'optInConfirmationEmailTemplate' => 'Opt-in confirmation email template',
      'targetTeam' => 'Target Team',
      'inboundEmail' => 'Group Email Account',
      'logRecords' => 'Log'
    ],
    'labels' => [
      'Create LeadCapture' => 'Create Entry Point',
      'Generate New API Key' => 'Generate New API Key',
      'Request' => 'Request',
      'Confirm Opt-In' => 'Confirm Opt-In',
      'Generate New Form ID' => 'Generate New Form ID',
      'Web Form' => 'Web Form'
    ],
    'messages' => [
      'generateApiKey' => 'Create new API Key',
      'optInConfirmationExpired' => 'Opt-in confirmation link is expired.',
      'optInIsConfirmed' => 'Opt-in is confirmed.'
    ],
    'tooltips' => [
      'formCaptcha' => 'To be able to use Captcha, you need to configure it under **Administration** > **Integrations**.',
      'optInConfirmationSuccessMessage' => 'Markdown is supported.'
    ]
  ],
  'LeadCaptureLogRecord' => [
    'fields' => [
      'number' => 'Number',
      'data' => 'Data',
      'target' => 'Target',
      'leadCapture' => 'Lead Capture',
      'createdAt' => 'Entered At',
      'isCreated' => 'Is Lead Created'
    ],
    'links' => [
      'leadCapture' => 'Lead Capture',
      'target' => 'Target'
    ]
  ],
  'MassAction' => [
    'fields' => [
      'status' => 'Status',
      'processedCount' => 'Processed Count'
    ],
    'options' => [
      'status' => [
        'Pending' => 'Pending',
        'Running' => 'Running',
        'Success' => 'Success',
        'Failed' => 'Failed'
      ]
    ],
    'messages' => [
      'infoText' => 'The mass action is being processed in idle by cron. It can take some time to finish. Closing this modal dialog won\'t affect the execution process.'
    ]
  ],
  'Note' => [
    'fields' => [
      'post' => 'Post',
      'attachments' => 'Attachments',
      'targetType' => 'Target',
      'teams' => 'Teams',
      'users' => 'Users',
      'portals' => 'Portals',
      'type' => 'Type',
      'isGlobal' => 'Is Global',
      'isInternal' => 'Is Internal (for internal users)',
      'isPinned' => 'Is Pinned',
      'related' => 'Related',
      'createdByGender' => 'Created By Gender',
      'data' => 'Data',
      'number' => 'Number'
    ],
    'filters' => [
      'all' => 'All',
      'posts' => 'Posts',
      'updates' => 'Updates',
      'activity' => 'Activity'
    ],
    'options' => [
      'targetType' => [
        'self' => 'Self',
        'users' => 'Users',
        'teams' => 'Teams',
        'all' => 'All Internal Users',
        'portals' => 'Portal Users'
      ],
      'type' => [
        'Post' => 'Post',
        'Create' => 'Create',
        'CreateRelated' => 'Create Related',
        'Update' => 'Update',
        'Status' => 'Status',
        'Assign' => 'Assign',
        'Relate' => 'Relate',
        'Unrelate' => 'Unrelate',
        'EmailReceived' => 'Email Received',
        'EmailSent' => 'Email Sent'
      ]
    ],
    'labels' => [
      'View Posts' => 'View Posts',
      'View Attachments' => 'View Attachments',
      'View Activity' => 'View Activity',
      'Pin' => 'Pin',
      'Unpin' => 'Unpin',
      'Pinned' => 'Pinned',
      'Quote Reply' => 'Quote Reply'
    ],
    'messages' => [
      'writeMessage' => 'Write your message here',
      'pinnedMaxCountExceeded' => 'Cannot pin more notes. Max allowed number is {count}.'
    ],
    'links' => [
      'portals' => 'Portals',
      'attachments' => 'Attachments',
      'superParent' => 'Super Parent',
      'related' => 'Related'
    ],
    'otherFields' => [
      'to' => 'To'
    ]
  ],
  'OAuthAccount' => [
    'labels' => [
      'Create OAuthAccount' => 'Create OAuth Account',
      'Connection' => 'Connection'
    ],
    'fields' => [
      'provider' => 'Provider',
      'hasAccessToken' => 'Has Access Token',
      'user' => 'User',
      'providerIsActive' => 'Provider is Active',
      'data' => 'Data'
    ],
    'links' => [
      'provider' => 'Provider'
    ]
  ],
  'OAuthProvider' => [
    'labels' => [
      'Create OAuthProvider' => 'Create OAuth Provider'
    ],
    'fields' => [
      'isActive' => 'Is Active',
      'clientId' => 'Client ID',
      'clientSecret' => 'Client Secret',
      'authorizationEndpoint' => 'Authorization Endpoint',
      'tokenEndpoint' => 'Token Endpoint',
      'authorizationRedirectUri' => 'Authorization Redirect URI',
      'scopes' => 'Scopes',
      'scopeSeparator' => 'Scope Separator',
      'hasAccessToken' => 'Has Access Token',
      'authorizationPrompt' => 'Authorization Prompt',
      'authorizationParams' => 'Authorization Params'
    ],
    'links' => [
      'accounts' => 'Accounts'
    ],
    'tooltips' => [
      'authorizationParams' => 'Additional query parameters to be sent to the authorization endpoint. Specified in JSON format.'
    ]
  ],
  'PhoneNumber' => [
    'fields' => [
      'type' => 'Type',
      'optOut' => 'Opted Out',
      'invalid' => 'Invalid',
      'numeric' => 'Numeric Value'
    ],
    'presetFilters' => [
      'orphan' => 'Orphan'
    ]
  ],
  'Portal' => [
    'fields' => [
      'name' => 'Name',
      'logo' => 'Logo',
      'url' => 'URL',
      'portalRoles' => 'Roles',
      'isActive' => 'Is Active',
      'isDefault' => 'Is Default',
      'tabList' => 'Tab List',
      'applicationName' => 'Application Name',
      'quickCreateList' => 'Quick Create List',
      'companyLogo' => 'Logo',
      'theme' => 'Theme',
      'language' => 'Language',
      'dashboardLayout' => 'Dashboard Layout',
      'dateFormat' => 'Date Format',
      'timeFormat' => 'Time Format',
      'timeZone' => 'Time Zone',
      'weekStart' => 'First Day of Week',
      'defaultCurrency' => 'Default Currency',
      'layoutSet' => 'Layout Set',
      'authenticationProvider' => 'Authentication Provider',
      'customUrl' => 'Custom URL',
      'customId' => 'Custom ID',
      'authTokenLifetime' => 'Auth Token Lifetime (hours)',
      'authTokenMaxIdleTime' => 'Auth Token Max Idle Time (hours)'
    ],
    'links' => [
      'users' => 'Users',
      'portalRoles' => 'Roles',
      'layoutSet' => 'Layout Set',
      'authenticationProvider' => 'Authentication Provider',
      'notes' => 'Notes',
      'articles' => 'Knowledge Base Articles'
    ],
    'tooltips' => [
      'layoutSet' => 'Provides the ability to have layouts that differ from the standard ones.',
      'portalRoles' => 'Specified Portal Roles will be applied to all users of this portal.'
    ],
    'labels' => [
      'Create Portal' => 'Create Portal',
      'User Interface' => 'User Interface',
      'General' => 'General',
      'Settings' => 'Settings'
    ]
  ],
  'PortalRole' => [
    'fields' => [
      'exportPermission' => 'Export Permission',
      'massUpdatePermission' => 'Mass Update Permission',
      'data' => 'Data',
      'fieldData' => 'Field Data'
    ],
    'links' => [
      'users' => 'Users'
    ],
    'labels' => [
      'Access' => 'Access',
      'Create PortalRole' => 'Create Portal Role',
      'Scope Level' => 'Scope Level',
      'Field Level' => 'Field Level'
    ]
  ],
  'PortalUser' => [
    'labels' => [
      'Create PortalUser' => 'Create Portal User'
    ]
  ],
  'Preferences' => [
    'fields' => [
      'dateFormat' => 'Date Format',
      'timeFormat' => 'Time Format',
      'timeZone' => 'Time Zone',
      'weekStart' => 'First Day of Week',
      'thousandSeparator' => 'Thousand Separator',
      'decimalMark' => 'Decimal Mark',
      'defaultCurrency' => 'Default Currency',
      'currencyList' => 'Currency List',
      'language' => 'Language',
      'exportDelimiter' => 'Export Delimiter',
      'receiveAssignmentEmailNotifications' => 'Email notifications upon assignment',
      'receiveMentionEmailNotifications' => 'Email notifications about mentions in posts',
      'receiveStreamEmailNotifications' => 'Email notifications about posts and status updates',
      'assignmentNotificationsIgnoreEntityTypeList' => 'In-app assignment notifications',
      'assignmentEmailNotificationsIgnoreEntityTypeList' => 'Email assignment notifications',
      'reactionNotifications' => 'In-app notifications about reactions',
      'reactionNotificationsNotFollowed' => 'Notifications about reactions for non-followed records',
      'autoFollowEntityTypeList' => 'Global Auto-Follow',
      'signature' => 'Email Signature',
      'dashboardTabList' => 'Tab List',
      'defaultReminders' => 'Default Reminders',
      'defaultRemindersTask' => 'Default Reminders for Tasks',
      'theme' => 'Theme',
      'pageContentWidth' => 'Content Width',
      'useCustomTabList' => 'Custom Tab List',
      'addCustomTabs' => 'Add Custom Tabs',
      'tabList' => 'Tab List',
      'emailReplyToAllByDefault' => 'Email Reply to all by default',
      'dashboardLayout' => 'Dashboard Layout',
      'dashboardLocked' => 'Lock Dashboard',
      'emailReplyForceHtml' => 'Email Reply in HTML',
      'doNotFillAssignedUserIfNotRequired' => 'Do not pre-fill assigned user on record creation',
      'followEntityOnStreamPost' => 'Auto-follow record after posting in Stream',
      'followCreatedEntities' => 'Auto-follow created records',
      'followCreatedEntityTypeList' => 'Auto-follow created records of specific entity types',
      'followAsCollaborator' => 'Auto-follow when added as a collaborator',
      'emailUseExternalClient' => 'Use an external email client',
      'textSearchStoringDisabled' => 'Disable text filter storing',
      'calendarSlotDuration' => 'Calendar Slot Duration',
      'calendarScrollHour' => 'Calendar Scroll to Hour'
    ],
    'links' => [],
    'options' => [
      'weekStart' => [
        0 => 'Sunday',
        1 => 'Monday'
      ],
      'pageContentWidth' => [
        '' => 'Normal',
        'Wide' => 'Wide'
      ]
    ],
    'labels' => [
      'Notifications' => 'Notifications',
      'User Interface' => 'User Interface',
      'Misc' => 'Misc',
      'Locale' => 'Locale',
      'Reset Dashboard to Default' => 'Reset Dashboard to Default'
    ],
    'tooltips' => [
      'addCustomTabs' => 'If checked, custom tabs will be appended to default tabs. Otherwise, custom tabs will be used instead of default tabs.',
      'autoFollowEntityTypeList' => 'Automatically follow ALL new records (created by any user) of the selected entity types. To be able to see information in the stream and receive notifications about all records in the system.',
      'doNotFillAssignedUserIfNotRequired' => 'When creating a new record, the assigned user won\'t be pre-filled with the own user. If the assigned user field is required, then this parameter has no effect.',
      'followCreatedEntities' => 'Created records will be automatically followed, regardless of who they assigned to.',
      'followCreatedEntityTypeList' => 'Records of the selected entity types will be automatically followed, regardless of who they assigned to. Use this parameter to follow only specific entity types.'
    ],
    'tabFields' => [
      'label' => 'Label',
      'iconClass' => 'Icon',
      'color' => 'Color'
    ]
  ],
  'Role' => [
    'fields' => [
      'name' => 'Name',
      'roles' => 'Roles',
      'assignmentPermission' => 'Assignment Permission',
      'userPermission' => 'User Permission',
      'messagePermission' => 'Message Permission',
      'portalPermission' => 'Portal Permission',
      'groupEmailAccountPermission' => 'Group Email Account Permission',
      'exportPermission' => 'Export Permission',
      'massUpdatePermission' => 'Mass Update Permission',
      'followerManagementPermission' => 'Follower Management Permission',
      'dataPrivacyPermission' => 'Data Privacy Permission',
      'auditPermission' => 'Audit Permission',
      'mentionPermission' => 'Mention Permission',
      'userCalendarPermission' => 'User Calendar Permission',
      'data' => 'Data',
      'fieldData' => 'Field Data'
    ],
    'links' => [
      'users' => 'Users',
      'teams' => 'Teams'
    ],
    'tooltips' => [
      'messagePermission' => 'Allows to send messages to other users.

* all – can send to all
* team – can send only to teammates
* no – cannot send',
      'assignmentPermission' => 'Allows to assign records to other users.

* all – no restriction
* team – can assign only to teammates
* no – can assign only to self',
      'userPermission' => 'Allows to view stream of other users. Allows users to view the access levels other users have for specific records.',
      'userCalendarPermission' => 'Allows to view calendars of other users.',
      'portalPermission' => 'Access to portal information, the ability to post messages to portal users.',
      'groupEmailAccountPermission' => 'Access to group email accounts, the ability to send emails from group SMTP.',
      'exportPermission' => 'Allows to export records.',
      'massUpdatePermission' => 'The ability to perform mass update of records.',
      'followerManagementPermission' => 'Allows to manage followers of specific records.',
      'dataPrivacyPermission' => 'Allows to view and erase personal data.',
      'auditPermission' => 'Allows to view the audit log.',
      'mentionPermission' => 'Allows to mention other users in the Stream.

* all – can mention all
* team – can mention only teammates
* no – cannot mention'
    ],
    'labels' => [
      'Access' => 'Access',
      'Create Role' => 'Create Role',
      'Scope Level' => 'Scope Level',
      'Field Level' => 'Field Level',
      'Baseline' => 'Baseline'
    ],
    'options' => [
      'accessList' => [
        'not-set' => 'not-set',
        'enabled' => 'enabled',
        'disabled' => 'disabled'
      ],
      'levelList' => [
        'all' => 'all',
        'team' => 'team',
        'account' => 'account',
        'contact' => 'contact',
        'own' => 'own',
        'no' => 'no',
        'yes' => 'yes',
        'not-set' => 'not-set'
      ]
    ],
    'actions' => [
      'read' => 'Read',
      'edit' => 'Edit',
      'delete' => 'Delete',
      'stream' => 'Stream',
      'create' => 'Create'
    ],
    'messages' => [
      'changesAfterClearCache' => 'All changes in an access control will be applied after cache is cleared.'
    ]
  ],
  'ScheduledJob' => [
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'job' => 'Job',
      'scheduling' => 'Scheduling'
    ],
    'links' => [
      'log' => 'Log'
    ],
    'labels' => [
      'As often as possible' => 'As often as possible',
      'Create ScheduledJob' => 'Create Scheduled Job'
    ],
    'options' => [
      'job' => [
        'Cleanup' => 'Clean-up',
        'CheckInboundEmails' => 'Check Group Email Accounts',
        'CheckEmailAccounts' => 'Check Personal Email Accounts',
        'SendEmailReminders' => 'Send Email Reminders',
        'AuthTokenControl' => 'Auth Token Control',
        'SendEmailNotifications' => 'Send Email Notifications',
        'CheckNewVersion' => 'Check for New Version',
        'ProcessWebhookQueue' => 'Process Webhook Queue',
        'SendScheduledEmails' => 'Send Scheduled Emails',
        'ProcessMassEmail' => 'Send Mass Emails',
        'ControlKnowledgeBaseArticleStatus' => 'Control Knowledge Base Article Status'
      ],
      'cronSetup' => [
        'linux' => 'Note: Add this line to the crontab file to run Espo Scheduled Jobs:',
        'mac' => 'Note: Add this line to the crontab file to run Espo Scheduled Jobs:',
        'windows' => 'Note: Create a batch file with the following commands to run Espo Scheduled Jobs using Windows Scheduled Tasks:',
        'default' => 'Note: Add this command to Cron Job (Scheduled Task):'
      ],
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ]
    ],
    'tooltips' => [
      'scheduling' => 'Crontab notation. Defines frequency of job runs.

`*/5 * * * *` - every 5 minutes

`0 */2 * * *` - every 2 hours

`30 1 * * *` - at 01:30 once a day

`0 0 1 * *` - on the first day of the month'
    ]
  ],
  'ScheduledJobLogRecord' => [
    'fields' => [
      'status' => 'Status',
      'executionTime' => 'Execution Time',
      'target' => 'Target'
    ]
  ],
  'Settings' => [
    'fields' => [
      'useCache' => 'Use Cache',
      'dateFormat' => 'Date Format',
      'timeFormat' => 'Time Format',
      'timeZone' => 'Time Zone',
      'weekStart' => 'First Day of Week',
      'thousandSeparator' => 'Thousand Separator',
      'decimalMark' => 'Decimal Mark',
      'defaultCurrency' => 'Default Currency',
      'baseCurrency' => 'Base Currency',
      'currencyRates' => 'Rate Values',
      'currencyList' => 'Currency List',
      'language' => 'Language',
      'companyLogo' => 'Company Logo',
      'smsProvider' => 'SMS Provider',
      'outboundSmsFromNumber' => 'SMS From Number',
      'emailAddress' => 'Email',
      'outboundEmailFromName' => 'From Name',
      'outboundEmailFromAddress' => 'System Email Address',
      'outboundEmailIsShared' => 'Is Shared',
      'emailAddressLookupEntityTypeList' => 'Email address look-up scopes',
      'emailAddressSelectEntityTypeList' => 'Email address select scopes',
      'recordsPerPage' => 'Records Per Page',
      'recordsPerPageSmall' => 'Records Per Page (Small)',
      'recordsPerPageSelect' => 'Records Per Page (Select)',
      'recordsPerPageKanban' => 'Records Per Page (Kanban)',
      'tabList' => 'Tab List',
      'quickCreateList' => 'Quick Create List',
      'exportDelimiter' => 'Export Delimiter',
      'globalSearchEntityList' => 'Global Search Entity List',
      'authenticationMethod' => 'Authentication Method',
      'ldapHost' => 'Host',
      'ldapPort' => 'Port',
      'ldapAuth' => 'Auth',
      'ldapUsername' => 'Full User DN',
      'ldapPassword' => 'Password',
      'ldapBindRequiresDn' => 'Bind Requires DN',
      'ldapBaseDn' => 'Base DN',
      'ldapAccountCanonicalForm' => 'Account Canonical Form',
      'ldapAccountDomainName' => 'Account Domain Name',
      'ldapTryUsernameSplit' => 'Try Username Split',
      'ldapPortalUserLdapAuth' => 'Use LDAP Authentication for Portal Users',
      'ldapCreateEspoUser' => 'Create User in EspoCRM',
      'ldapSecurity' => 'Security',
      'ldapUserLoginFilter' => 'User Login Filter',
      'ldapAccountDomainNameShort' => 'Account Domain Name Short',
      'ldapOptReferrals' => 'Opt Referrals',
      'ldapUserNameAttribute' => 'Username Attribute',
      'ldapUserObjectClass' => 'User ObjectClass',
      'ldapUserTitleAttribute' => 'User Title Attribute',
      'ldapUserFirstNameAttribute' => 'User First Name Attribute',
      'ldapUserLastNameAttribute' => 'User Last Name Attribute',
      'ldapUserEmailAddressAttribute' => 'User Email Address Attribute',
      'ldapUserTeams' => 'User Teams',
      'ldapUserDefaultTeam' => 'User Default Team',
      'ldapUserPhoneNumberAttribute' => 'User Phone Number Attribute',
      'ldapPortalUserPortals' => 'Default Portals for a Portal User',
      'ldapPortalUserRoles' => 'Default Roles for a Portal User',
      'exportDisabled' => 'Disable Export (only admin is allowed)',
      'assignmentNotificationsEntityList' => 'Entities to notify about upon assignment',
      'assignmentEmailNotifications' => 'Notifications upon assignment',
      'assignmentEmailNotificationsEntityList' => 'Assignment email notifications scopes',
      'streamEmailNotifications' => 'Notifications about updates in Stream for internal users',
      'portalStreamEmailNotifications' => 'Notifications about updates in Stream for portal users',
      'streamEmailNotificationsEntityList' => 'Stream email notifications scopes',
      'streamEmailNotificationsTypeList' => 'What to notify about',
      'streamEmailWithContentEntityTypeList' => 'Entities with email body in stream notes',
      'emailNotificationsDelay' => 'Delay of email notifications (in seconds)',
      'b2cMode' => 'B2C Mode',
      'avatarsDisabled' => 'Disable Avatars',
      'followCreatedEntities' => 'Follow created records',
      'displayListViewRecordCount' => 'Display Total Count (on List View)',
      'theme' => 'Theme',
      'userThemesDisabled' => 'Disable User Themes',
      'attachmentUploadMaxSize' => 'Upload Max Size (Mb)',
      'attachmentUploadChunkSize' => 'Upload Chunk Size (Mb)',
      'emailMessageMaxSize' => 'Email Max Size (Mb)',
      'massEmailMaxPerHourCount' => 'Max number of emails sent per hour',
      'massEmailMaxPerBatchCount' => 'Max number of emails sent per batch',
      'personalEmailMaxPortionSize' => 'Max email portion size for personal account fetching',
      'inboundEmailMaxPortionSize' => 'Max email portion size for group account fetching',
      'maxEmailAccountCount' => 'Max number of personal email accounts per user',
      'emailScheduledBatchCount' => 'Max number of scheduled emails sent per batch',
      'authTokenLifetime' => 'Auth Token Lifetime (hours)',
      'authTokenMaxIdleTime' => 'Auth Token Max Idle Time (hours)',
      'dashboardLayout' => 'Dashboard Layout (default)',
      'siteUrl' => 'Site URL',
      'addressPreview' => 'Address Preview',
      'addressFormat' => 'Address Format',
      'personNameFormat' => 'Person Name Format',
      'notificationSoundsDisabled' => 'Disable Notification Sounds',
      'newNotificationCountInTitle' => 'Display new notification number in page title',
      'applicationName' => 'Application Name',
      'calendarEntityList' => 'Calendar Entity List',
      'busyRangesEntityList' => 'Free/Busy Entity List',
      'mentionEmailNotifications' => 'Send email notifications about mentions in posts',
      'massEmailDisableMandatoryOptOutLink' => 'Disable mandatory opt-out link',
      'massEmailOpenTracking' => 'Email Open Tracking',
      'massEmailVerp' => 'Use VERP',
      'activitiesEntityList' => 'Activities Entity List',
      'historyEntityList' => 'History Entity List',
      'currencyFormat' => 'Currency Format',
      'currencyDecimalPlaces' => 'Currency Decimal Places',
      'aclAllowDeleteCreated' => 'Allow to remove created records',
      'adminNotifications' => 'System notifications in administration panel',
      'adminNotificationsNewVersion' => 'Show notification when new EspoCRM version is available',
      'adminNotificationsNewExtensionVersion' => 'Show notification when new versions of extensions are available',
      'textFilterUseContainsForVarchar' => 'Use \'contains\' operator when filtering varchar fields',
      'phoneNumberNumericSearch' => 'Numeric phone number search',
      'phoneNumberInternational' => 'International phone numbers',
      'phoneNumberExtensions' => 'Phone number extensions',
      'phoneNumberPreferredCountryList' => 'Preferred telephone country codes',
      'authTokenPreventConcurrent' => 'Only one auth token per user',
      'scopeColorsDisabled' => 'Disable scope colors',
      'tabColorsDisabled' => 'Disable tab colors',
      'tabIconsDisabled' => 'Disable tab icons',
      'emailAddressIsOptedOutByDefault' => 'Mark new email addresses as opted-out',
      'outboundEmailBccAddress' => 'BCC address for external clients',
      'cleanupDeletedRecords' => 'Clean up deleted records',
      'addressCityList' => 'Address City Autocomplete List',
      'addressStateList' => 'Address State Autocomplete List',
      'fiscalYearShift' => 'Fiscal Year Start',
      'jobRunInParallel' => 'Jobs Run in Parallel',
      'jobMaxPortion' => 'Jobs Max Portion',
      'jobPoolConcurrencyNumber' => 'Jobs Pool Concurrency Number',
      'jobForceUtc' => 'Force UTC Time Zone',
      'daemonInterval' => 'Daemon Interval',
      'daemonMaxProcessNumber' => 'Daemon Max Process Number',
      'daemonProcessTimeout' => 'Daemon Process Timeout',
      'cronDisabled' => 'Disable Cron',
      'maintenanceMode' => 'Maintenance Mode',
      'useWebSocket' => 'Use WebSocket',
      'passwordRecoveryDisabled' => 'Disable password recovery',
      'passwordRecoveryForAdminDisabled' => 'Disable password recovery for admin users',
      'passwordRecoveryForInternalUsersDisabled' => 'Disable password recovery for internal users',
      'passwordRecoveryNoExposure' => 'Prevent email address exposure on password recovery form',
      'passwordGenerateLength' => 'Length of generated passwords',
      'passwordStrengthLength' => 'Minimum password length',
      'passwordStrengthLetterCount' => 'Number of letters required in password',
      'passwordStrengthNumberCount' => 'Number of digits required in password',
      'passwordStrengthBothCases' => 'Password must contain letters of both upper and lower case',
      'passwordStrengthSpecialCharacterCount' => 'Number of special character required in password',
      'auth2FA' => 'Enable 2-Factor Authentication',
      'auth2FAForced' => 'Force regular users to set up 2FA',
      'auth2FAMethodList' => 'Available 2FA methods',
      'auth2FAInPortal' => 'Allow 2FA in portals',
      'workingTimeCalendar' => 'Working Time Calendar',
      'oidcClientId' => 'OIDC Client ID',
      'oidcClientSecret' => 'OIDC Client Secret',
      'oidcAuthorizationRedirectUri' => 'OIDC Authorization Redirect URI',
      'oidcAuthorizationEndpoint' => 'OIDC Authorization Endpoint',
      'oidcTokenEndpoint' => 'OIDC Token Endpoint',
      'oidcUserInfoEndpoint' => 'OIDC UserInfo Endpoint',
      'oidcJwksEndpoint' => 'OIDC JSON Web Key Set Endpoint',
      'oidcJwtSignatureAlgorithmList' => 'OIDC JWT Allowed Signature Algorithms',
      'oidcScopes' => 'OIDC Scopes',
      'oidcGroupClaim' => 'OIDC Group Claim',
      'oidcCreateUser' => 'OIDC Create User',
      'oidcUsernameClaim' => 'OIDC Username Claim',
      'oidcTeams' => 'OIDC Teams',
      'oidcSync' => 'OIDC Sync',
      'oidcSyncTeams' => 'OIDC Sync Teams',
      'oidcFallback' => 'OIDC Fallback Login',
      'oidcAllowRegularUserFallback' => 'OIDC Allow fallback login for regular users',
      'oidcAllowAdminUser' => 'OIDC Allow OIDC login for admin users',
      'oidcLogoutUrl' => 'OIDC Logout URL',
      'oidcAuthorizationPrompt' => 'OIDC Authorization Prompt',
      'pdfEngine' => 'PDF Engine',
      'quickSearchFullTextAppendWildcard' => 'Append wildcard in quick search',
      'authIpAddressCheck' => 'Restrict access by IP address',
      'authIpAddressWhitelist' => 'IP Address Whitelist',
      'authIpAddressCheckExcludedUsers' => 'Users excluded from check',
      'availableReactions' => 'Available Reactions',
      'baselineRole' => 'Baseline Role'
    ],
    'options' => [
      'authenticationMethod' => [
        'Oidc' => 'OIDC'
      ],
      'currencyFormat' => [
        1 => '10 USD',
        2 => '$10',
        3 => '10 $'
      ],
      'personNameFormat' => [
        'firstLast' => 'First Last',
        'lastFirst' => 'Last First',
        'firstMiddleLast' => 'First Middle Last',
        'lastFirstMiddle' => 'Last First Middle'
      ],
      'streamEmailNotificationsTypeList' => [
        'Post' => 'Posts',
        'Status' => 'Status updates',
        'EmailReceived' => 'Received emails'
      ],
      'auth2FAMethodList' => [
        'Totp' => 'TOTP',
        'Email' => 'Email',
        'Sms' => 'SMS'
      ],
      'smtpSecurity' => [
        'SSL' => 'SSL/TLS',
        'TLS' => 'STARTTLS'
      ]
    ],
    'tooltips' => [
      'authIpAddressCheckExcludedUsers' => 'Users that will be able to log in regardless whether their IP address is in the whitelist.',
      'authIpAddressWhitelist' => 'A list of IP addresses or ranges in CIDR notation.

Portals are not affected by restriction.',
      'workingTimeCalendar' => 'A working time calendar that will be applied to all users by default.',
      'displayListViewRecordCount' => 'The total number of records will be shown on the list view.',
      'currencyList' => 'Currencies available in the system.',
      'activitiesEntityList' => 'Record types included in the Activities panel.',
      'historyEntityList' => 'Record types included in the History panel.',
      'calendarEntityList' => 'Record types included in the Calendar.',
      'addressStateList' => 'State suggestions for address fields.',
      'addressCityList' => 'City suggestions for address fields.',
      'addressCountryList' => 'Country suggestions for address fields.',
      'exportDisabled' => 'Users won\'t be able to export records. Only admin will be allowed.',
      'globalSearchEntityList' => 'Defines which record types are searchable with Global Search.',
      'siteUrl' => 'A URL of this EspoCRM instance. You need to change it if you move to another domain.',
      'useCache' => 'Not recommended to disable, unless for development purpose.',
      'useWebSocket' => 'WebSocket enables two-way interactive communication between a server and a browser. Requires setting up the WebSocket daemon on your server. Check the documentation for more info.',
      'passwordRecoveryForInternalUsersDisabled' => 'Only portal users will be able to recover passwords.',
      'passwordRecoveryNoExposure' => 'It won\'t be possible to determine whether a specific email address exists in the system. Recommended for security.',
      'emailAddressLookupEntityTypeList' => 'Record types included in email address autocomplete.',
      'emailAddressSelectEntityTypeList' => 'Record types available when selecting recipients for a composed email.',
      'emailNotificationsDelay' => 'A message can be edited within the specified timeframe before the notification is sent.',
      'outboundEmailFromAddress' => 'System emails will be sent from this email address. A [group email account](#InboundEmail) with the same email address must be set up and properly configured to send emails.',
      'busyRangesEntityList' => 'Specifies which record types are used to determine busy time ranges in the scheduler and timeline.',
      'massEmailVerp' => 'Variable envelope return path. For better handling of bounced messages. Make sure that your SMTP provider supports it.',
      'recordsPerPage' => 'Number of records initially displayed in list views.',
      'recordsPerPageSmall' => 'Number of records initially displayed in relationship panels.',
      'recordsPerPageSelect' => 'Number of records initially displayed when selecting records.',
      'recordsPerPageKanban' => 'Number of records initially displayed in kanban columns.',
      'outboundEmailIsShared' => 'Allow users to send emails from this address.',
      'followCreatedEntities' => 'Users will automatically follow records they created.',
      'emailMessageMaxSize' => 'All inbound emails exceeding the specified size will be fetched without the body and attachments.',
      'authTokenLifetime' => 'Defines how long access tokens can be valid.

Examples:
- 0 – no expiration
- 48 – expires 2 days after login',
      'authTokenMaxIdleTime' => 'Defines the maximum period of inactivity after which an access token expires. Value is in hours.

Examples:
- 0 – no expiration
- 48 – expires after 2 days of inactivity',
      'userThemesDisabled' => 'If checked, then users won\'t be able to choose another theme.',
      'ldapUsername' => 'The full system user DN which allows to search other users. E.g. "CN=LDAP System User,OU=users,OU=espocrm, DC=test,DC=lan".',
      'ldapPassword' => 'The password to access to LDAP server.',
      'ldapAuth' => 'Access credentials for the LDAP server.',
      'ldapUserNameAttribute' => 'The attribute to identify the user. 
E.g. "userPrincipalName" or "sAMAccountName" for Active Directory, "uid" for OpenLDAP.',
      'ldapUserObjectClass' => 'ObjectClass attribute for searching users. E.g. "person" for AD, "inetOrgPerson" for OpenLDAP.',
      'ldapAccountCanonicalForm' => 'The type of your account canonical form. There are 4 options:

- \'Dn\' - the form in the format \'CN=tester,OU=espocrm,DC=test, DC=lan\'.

- \'Username\' - the form \'tester\'.

- \'Backslash\' - the form \'COMPANY\\tester\'.

- \'Principal\' - the form \'tester@company.com\'.',
      'ldapBindRequiresDn' => 'The option to format the username in the DN form.',
      'ldapBaseDn' => 'The default base DN used for searching users. E.g. "OU=users,OU=espocrm,DC=test, DC=lan".',
      'ldapTryUsernameSplit' => 'The option to split a username with the domain.',
      'ldapOptReferrals' => 'if referrals should be followed to the LDAP client.',
      'ldapPortalUserLdapAuth' => 'Allow portal users to use LDAP authentication instead of Espo authentication.',
      'ldapCreateEspoUser' => 'This option allows EspoCRM to create a user from the LDAP.',
      'ldapUserFirstNameAttribute' => 'LDAP attribute which is used to determine the user first name. E.g. "givenname".',
      'ldapUserLastNameAttribute' => 'LDAP attribute which is used to determine the user last name. E.g. "sn".',
      'ldapUserTitleAttribute' => 'LDAP attribute which is used to determine the user title. E.g. "title".',
      'ldapUserEmailAddressAttribute' => 'LDAP attribute which is used to determine the user email address. E.g. "mail".',
      'ldapUserPhoneNumberAttribute' => 'LDAP attribute which is used to determine the user phone number. E.g. "telephoneNumber".',
      'ldapUserLoginFilter' => 'The filter which allows to restrict users who able to use EspoCRM. E.g. "memberOf=CN=espoGroup, OU=groups,OU=espocrm, DC=test,DC=lan".',
      'ldapAccountDomainName' => 'The domain which is used for authorization to LDAP server.',
      'ldapAccountDomainNameShort' => 'The short domain which is used for authorization to LDAP server.',
      'ldapUserTeams' => 'Teams for created user. For more, see user profile.',
      'ldapUserDefaultTeam' => 'Default team for created user. For more, see user profile.',
      'ldapPortalUserPortals' => 'Default Portals for created Portal User',
      'ldapPortalUserRoles' => 'Default Roles for created Portal User',
      'b2cMode' => 'By default EspoCRM is adapted for B2B. You can switch it to B2C.',
      'currencyDecimalPlaces' => 'Number of decimal places. If empty, then all nonempty decimal places will be displayed.',
      'aclStrictMode' => 'Enabled: Access to scopes will be forbidden if it\'s not specified in roles.

Disabled: Access to scopes will be allowed if it\'s not specified in roles.',
      'aclAllowDeleteCreated' => 'If enabled, users are allowed to delete their newly created records, regardless of delete permissions, for a limited time.',
      'textFilterUseContainsForVarchar' => 'If enabled, the text search uses the *contains* operator. Otherwise, it will use *starts with*. If disabled, you can still use the wildcard (`*`) in the search query.',
      'streamEmailNotificationsEntityList' => 'Email notifications about stream updates of followed records. Users will receive email notifications only for specified entity types.',
      'authTokenPreventConcurrent' => 'Users won\'t be able to be logged in on multiple devices simultaneously.',
      'emailAddressIsOptedOutByDefault' => 'When creating a new record, its email address will be marked as opted out.',
      'cleanupDeletedRecords' => 'Soft-removed records will be permanently deleted from the database after a retention period.',
      'jobRunInParallel' => 'Jobs will run in parallel processes. It\'s highly recommended to enable.',
      'jobPoolConcurrencyNumber' => 'Max number of processes run simultaneously.',
      'jobMaxPortion' => 'Maximum number of jobs processed per cycle.',
      'jobForceUtc' => 'Use the UTC time zone for scheduled jobs. Otherwise, the time zone set in settings will be used.',
      'daemonInterval' => 'Interval between cycles in seconds.',
      'daemonMaxProcessNumber' => 'Maximum number of cycles that can run simultaneously. If exceeded, the next cycle will wait until the number drops before starting.',
      'daemonProcessTimeout' => 'Maximum execution time (in seconds) allocated for a cycle process.',
      'cronDisabled' => 'Cron will not run.',
      'maintenanceMode' => 'Only administrators will have access to the system.',
      'oidcGroupClaim' => 'A claim to use for team mapping.',
      'oidcFallback' => 'Allow login by username/password.',
      'oidcCreateUser' => 'Create a new user in Espo when no matching user found.',
      'oidcSync' => 'Sync user data (on every login).',
      'oidcSyncTeams' => 'Sync user teams (on every login).',
      'oidcUsernameClaim' => 'A claim to use for a username (for user matching and creation).',
      'oidcTeams' => 'Espo teams mapped against groups/teams/roles of the identity provider. Teams with an empty mapping value will be always assigned to a user (when creating or syncing).',
      'oidcLogoutUrl' => 'A URL the browser will redirect to after logging out from Espo. Intended for clearing the session information in the browser and doing logging out on the provider side. Usually the URL contains a redirect-URL parameter, to return back to Espo.

Available placeholders:
* `{siteUrl}`
* `{clientId}`',
      'quickSearchFullTextAppendWildcard' => 'Append a wildcard to the autocomplete search query when Full-Text search is enabled. Reduces search performance.',
      'baselineRole' => 'The default role applied to all users. Any additional roles assigned to a user grant permissions on top of this baseline.

**Important**. Changing this role affects all users – review the change carefully to avoid granting unintended permissions.'
    ],
    'labels' => [
      'Group Tab' => 'Group Tab',
      'Divider' => 'Divider',
      'System' => 'System',
      'Locale' => 'Locale',
      'Search' => 'Search',
      'Misc' => 'Misc',
      'SMTP' => 'SMTP',
      'General' => 'General',
      'Phone Numbers' => 'Phone Numbers',
      'Navbar' => 'Navbar',
      'Dashboard' => 'Dashboard',
      'Configuration' => 'Configuration',
      'In-app Notifications' => 'In-app Notifications',
      'Email Notifications' => 'Email Notifications',
      'Currency Settings' => 'Currency Settings',
      'Currency Rates' => 'Currency Rates',
      'Mass Email' => 'Mass Email',
      'Scheduled Send' => 'Scheduled Send',
      'Test Connection' => 'Test Connection',
      'Connecting' => 'Connecting...',
      'Activities' => 'Activities',
      'Admin Notifications' => 'Admin Notifications',
      'Passwords' => 'Passwords',
      '2-Factor Authentication' => '2-Factor Authentication',
      'Attachments' => 'Attachments',
      'IdP Group' => 'IdP Group',
      'Access' => 'Access',
      'Strength' => 'Strength',
      'Recovery' => 'Recovery'
    ],
    'messages' => [
      'ldapTestConnection' => 'The connection successfully established.',
      'confirmBaselineRoleChange' => 'Are you sure you want to change the baseline role? An unconsidered change may grant unintended permissions to all users.'
    ]
  ],
  'Stream' => [
    'messages' => [
      'infoMention' => 'Type **@username** to mention user in the post.',
      'infoSyntax' => 'Available markdown syntax',
      'couldNotAddFollowerUserHasNoAccessToStream' => 'Could not add the user \'{userName}\' to the followers. The user does not have \'stream\' access to the record.'
    ],
    'syntaxItems' => [
      'code' => 'code',
      'multilineCode' => 'multiline code',
      'strongText' => 'strong text',
      'emphasizedText' => 'emphasized text',
      'deletedText' => 'deleted text',
      'blockquote' => 'blockquote',
      'link' => 'link text'
    ]
  ],
  'Team' => [
    'fields' => [
      'name' => 'Name',
      'roles' => 'Roles',
      'layoutSet' => 'Layout Set',
      'workingTimeCalendar' => 'Working Time Calendar',
      'positionList' => 'Position List',
      'userRole' => 'User Role'
    ],
    'links' => [
      'users' => 'Users',
      'notes' => 'Notes',
      'roles' => 'Roles',
      'layoutSet' => 'Layout Set',
      'workingTimeCalendar' => 'Working Time Calendar',
      'inboundEmails' => 'Group Email Accounts',
      'groupEmailFolders' => 'Group Email Folders'
    ],
    'tooltips' => [
      'workingTimeCalendar' => 'A calendar will be applied to users who have this team set as a Default Team.',
      'layoutSet' => 'Provides the ability to have layouts that differ from the standard ones. Layout Set will be applied to users who have this team set as Default Team.',
      'roles' => 'Access roles. Users of this team obtain access control level from the specified roles.',
      'positionList' => 'Positions available in this team. For example, Salesperson, Manager.'
    ],
    'labels' => [
      'Create Team' => 'Create Team'
    ]
  ],
  'Template' => [
    'fields' => [
      'name' => 'Name',
      'body' => 'Body',
      'entityType' => 'Entity Type',
      'header' => 'Header',
      'footer' => 'Footer',
      'leftMargin' => 'Left Margin',
      'topMargin' => 'Top Margin',
      'rightMargin' => 'Right Margin',
      'bottomMargin' => 'Bottom Margin',
      'printFooter' => 'Print Footer',
      'printHeader' => 'Print Header',
      'footerPosition' => 'Footer Position',
      'headerPosition' => 'Header Position',
      'variables' => 'Available Placeholders',
      'pageOrientation' => 'Page Orientation',
      'pageFormat' => 'Paper Format',
      'pageWidth' => 'Page Width (mm)',
      'pageHeight' => 'Page Height (mm)',
      'fontFace' => 'Font',
      'title' => 'Title',
      'style' => 'Style',
      'status' => 'Status',
      'filename' => 'Filename'
    ],
    'links' => [],
    'labels' => [
      'Create Template' => 'Create Template'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Inactive' => 'Inactive'
      ],
      'pageOrientation' => [
        'Portrait' => 'Portrait',
        'Landscape' => 'Landscape'
      ],
      'pageFormat' => [
        'Custom' => 'Custom'
      ],
      'placeholders' => [
        'pagebreak' => 'Page break',
        'today' => 'Today (date)',
        'now' => 'Now (date-time)'
      ],
      'fontFace' => []
    ],
    'tooltips' => [
      'filename' => 'An optional filename. Entity attributes can be used as placeholders.

Additional placeholder:
- `{{today}}`

Example:
`Document_{{today}}_{{name}}`',
      'footer' => 'Use {pageNumber} to print page number.',
      'variables' => 'Copy-paste needed placeholder to Header, Body or Footer.'
    ]
  ],
  'User' => [
    'fields' => [
      'name' => 'Name',
      'userName' => 'User Name',
      'title' => 'Title',
      'type' => 'Type',
      'isAdmin' => 'Is Admin',
      'defaultTeam' => 'Default Team',
      'emailAddress' => 'Email',
      'phoneNumber' => 'Phone',
      'roles' => 'Roles',
      'portals' => 'Portals',
      'portalRoles' => 'Portal Roles',
      'teamRole' => 'Position',
      'password' => 'Password',
      'currentPassword' => 'Current Password',
      'passwordConfirm' => 'Confirm Password',
      'newPassword' => 'New Password',
      'newPasswordConfirm' => 'Confirm New Password',
      'yourPassword' => 'Your current password',
      'avatar' => 'Avatar',
      'avatarColor' => 'Avatar Color',
      'isActive' => 'Is Active',
      'isPortalUser' => 'Is Portal User',
      'contact' => 'Contact',
      'accounts' => 'Accounts',
      'account' => 'Account (Primary)',
      'sendAccessInfo' => 'Send Email with Access Info to User',
      'portal' => 'Portal',
      'gender' => 'Gender',
      'position' => 'Position in Team',
      'ipAddress' => 'IP Address',
      'passwordPreview' => 'Password Preview',
      'isSuperAdmin' => 'Is Super Admin',
      'lastAccess' => 'Last Access',
      'apiKey' => 'API Key',
      'secretKey' => 'Secret Key',
      'dashboardTemplate' => 'Dashboard Template',
      'workingTimeCalendar' => 'Working Time Calendar',
      'auth2FA' => '2FA',
      'authMethod' => 'Authentication Method',
      'auth2FAEnable' => 'Enable 2-Factor Authentication',
      'auth2FAMethod' => '2FA Method',
      'auth2FATotpSecret' => '2FA TOTP Secret',
      'layoutSet' => 'Layout Set',
      'acceptanceStatus' => 'Acceptance Status',
      'acceptanceStatusMeetings' => 'Acceptance Status (Meetings)',
      'acceptanceStatusCalls' => 'Acceptance Status (Calls)'
    ],
    'links' => [
      'defaultTeam' => 'Default Team',
      'teams' => 'Teams',
      'roles' => 'Roles',
      'notes' => 'Notes',
      'portals' => 'Portals',
      'portalRoles' => 'Portal Roles',
      'contact' => 'Contact',
      'accounts' => 'Accounts',
      'account' => 'Account (Primary)',
      'tasks' => 'Tasks',
      'userData' => 'User Data',
      'dashboardTemplate' => 'Dashboard Template',
      'workingTimeCalendar' => 'Working Time Calendar',
      'workingTimeRanges' => 'Working Time Exceptions',
      'layoutSet' => 'Layout Set',
      'targetLists' => 'Target Lists'
    ],
    'labels' => [
      'Create User' => 'Create User',
      'Generate' => 'Generate',
      'Access' => 'Access',
      'Preferences' => 'Preferences',
      'Change Password' => 'Change Password',
      'Teams and Access Control' => 'Teams and Access Control',
      'Forgot Password?' => 'Forgot Password?',
      'Password Change Request' => 'Password Change Request',
      'Email Address' => 'Email Address',
      'External Accounts' => 'External Accounts',
      'Email Accounts' => 'Email Accounts',
      'Portal' => 'Portal',
      'Create Portal User' => 'Create Portal User',
      'Proceed w/o Contact' => 'Proceed w/o Contact',
      'Generate New API Key' => 'Generate New API Key',
      'Generate New Password' => 'Generate New Password',
      'Send Password Change Link' => 'Send Password Change Link',
      'Back to login form' => 'Back to login form',
      'Requirements' => 'Requirements',
      'Security' => 'Security',
      'Reset 2FA' => 'Reset 2FA',
      'Code' => 'Code',
      'Secret' => 'Secret',
      'Send Code' => 'Send Code',
      'Login Link' => 'Login Link'
    ],
    'tooltips' => [
      'defaultTeam' => 'All records created by this user will be related to this team by default.',
      'userName' => 'Allowed characters: `a-z`, `0-9`, `-_@.`.',
      'isAdmin' => 'Admin user can access everything.',
      'isActive' => 'If unchecked, then the user won\'t be able to log in.',
      'teams' => 'Teams which this user belongs to. Access control level is inherited from team\'s roles.',
      'roles' => 'Additional access roles. Use it if user doesn\'t belong to any team or you need to extend access control level exclusively for this user.',
      'portalRoles' => 'Additional portal roles. Use it to extend access control level exclusively for this user.',
      'portals' => 'Portals which this user has access to.',
      'layoutSet' => 'Layouts from the specified layout set will be applied for the user. The default layouts will be overridden.'
    ],
    'messages' => [
      '2faMethodNotConfigured' => 'The 2FA method is not fully configured in the system.',
      'loginAs' => 'Open the login link in an incognito window to preserve your current session. Use your admin credentials to log in.',
      'sendPasswordChangeLinkConfirmation' => 'An email with a unique link will be sent to the user allowing them to change their password. The link will expire after a specific amount of time.',
      'passwordRecoverySentIfMatched' => 'Assuming the entered data matched any user account.',
      'passwordStrengthLength' => 'Must be at least {length} characters long.',
      'passwordStrengthLetterCount' => 'Must contain at least {count} letter(s).',
      'passwordStrengthNumberCount' => 'Must contain at least {count} digit(s).',
      'passwordStrengthSpecialCharacterCount' => 'Must contain at least {count} special character(s).',
      'passwordStrengthBothCases' => 'Must contain letters of both upper and lower case.',
      'passwordWillBeSent' => 'Password will be sent to user\'s email address.',
      'passwordChanged' => 'Password has been changed',
      'userCantBeEmpty' => 'Username can not be empty',
      'wrongUsernamePassword' => 'Wrong username/password',
      'failedToLogIn' => 'Failed to log in',
      'emailAddressCantBeEmpty' => 'Email Address can not be empty',
      'userNameEmailAddressNotFound' => 'Username/Email Address not found',
      'forbidden' => 'Forbidden, please try later',
      'uniqueLinkHasBeenSent' => 'The unique URL has been sent to the specified email address.',
      'passwordChangedByRequest' => 'Password has been changed.',
      'setupSmtpBefore' => 'You need to setup [SMTP settings]({url}) to make the system be able to send password in email.',
      'userNameExists' => 'User Name already exists',
      'loginError' => 'Error occurred',
      'wrongCode' => 'Wrong code',
      'codeIsRequired' => 'Code is required',
      'yourAuthenticationCode' => 'Your authentication code: {code}.',
      'choose2FaSmsPhoneNumber' => 'Select a phone number that will be used for 2FA.',
      'choose2FaEmailAddress' => 'Select an email address that will be used for 2FA. It\'s highly recommended to use a non-primary email address.',
      'enterCodeSentInEmail' => 'Enter the code sent to your email address.',
      'enterCodeSentBySms' => 'Enter the code sent by SMS to your phone number.',
      'enterTotpCode' => 'Enter a code from your authenticator app.',
      'verifyTotpCode' => 'Scan the QR-code with your mobile authenticator app. If you have a trouble with scanning, you can enter the secret manually. After that you will see a 6-digit code in your application. Enter this code in the field below.',
      'generateAndSendNewPassword' => 'A new password will be generated and sent to the user\'s email address.',
      'security2FaResetConfirmation' => 'Are you sure you want to reset the current 2FA settings?',
      'auth2FARequiredHeader' => '2 factor authentication required',
      'auth2FARequired' => 'You need to set up 2 factor authentication. Use an authenticator application on your mobile phone (e.g. Google Authenticator).',
      'ldapUserInEspoNotFound' => 'User is not found in EspoCRM. Contact your administrator to create the user.',
      'passwordChangeRequestNotFound' => 'The password change request is not found. It might be expired. Try to initiate a new password recovery from the [login page]({url}).',
      'defaultTeamIsNotUsers' => 'Default Team should be one of user\'s Teams'
    ],
    'options' => [
      'gender' => [
        '' => 'Not Set',
        'Male' => 'Male',
        'Female' => 'Female',
        'Neutral' => 'Neutral'
      ],
      'type' => [
        'regular' => 'Regular',
        'admin' => 'Admin',
        'portal' => 'Portal',
        'system' => 'System',
        'super-admin' => 'Super-Admin',
        'api' => 'API'
      ],
      'authMethod' => [
        'ApiKey' => 'API Key',
        'Hmac' => 'HMAC'
      ]
    ],
    'boolFilters' => [
      'onlyMyTeam' => 'Only My Team',
      'onlyMe' => 'OnlyMe'
    ],
    'presetFilters' => [
      'active' => 'Active',
      'activePortal' => 'Portal Active',
      'activeApi' => 'API Active'
    ],
    'actions' => [
      'changePosition' => 'Change Position'
    ]
  ],
  'Webhook' => [
    'labels' => [
      'Create Webhook' => 'Create Webhook'
    ],
    'fields' => [
      'event' => 'Event',
      'url' => 'URL',
      'isActive' => 'Is Active',
      'user' => 'API User',
      'entityType' => 'Entity Type',
      'field' => 'Field',
      'secretKey' => 'Secret Key',
      'skipOwn' => 'Skip Own'
    ],
    'links' => [
      'user' => 'User',
      'queueItems' => 'Queue Items'
    ],
    'tooltips' => [
      'skipOwn' => 'Do not send if the event was initiated by the user owning the webhook.'
    ]
  ],
  'WebhookEventQueueItem' => [
    'fields' => [
      'event' => 'Event',
      'target' => 'Target',
      'data' => 'Data',
      'isProcessed' => 'Is Processed',
      'user' => 'User'
    ],
    'links' => [
      'target' => 'Target',
      'user' => 'User'
    ]
  ],
  'WebhookQueueItem' => [
    'fields' => [
      'event' => 'Event',
      'webhook' => 'Webhook',
      'target' => 'Target',
      'data' => 'Data',
      'status' => 'Status',
      'processedAt' => 'Processed At',
      'attempts' => 'Attempts',
      'processAt' => 'Process At'
    ],
    'links' => [
      'webhook' => 'Webhook'
    ],
    'options' => [
      'status' => [
        'Pending' => 'Pending',
        'Success' => 'Success',
        'Failed' => 'Failed'
      ]
    ]
  ],
  'WorkingTimeCalendar' => [
    'labels' => [
      'Create WorkingTimeCalendar' => 'Create Calendar'
    ],
    'fields' => [
      'timeZone' => 'Time Zone',
      'timeRanges' => 'Workday Schedule',
      'weekday0' => 'Sun',
      'weekday1' => 'Mon',
      'weekday2' => 'Tue',
      'weekday3' => 'Wed',
      'weekday4' => 'Thu',
      'weekday5' => 'Fri',
      'weekday6' => 'Sat',
      'weekday0TimeRanges' => 'Sun Schedule',
      'weekday1TimeRanges' => 'Mon Schedule',
      'weekday2TimeRanges' => 'Tue Schedule',
      'weekday3TimeRanges' => 'Wed Schedule',
      'weekday4TimeRanges' => 'Thu Schedule',
      'weekday5TimeRanges' => 'Fri Schedule',
      'weekday6TimeRanges' => 'Sat Schedule'
    ],
    'links' => [
      'ranges' => 'Exceptions'
    ]
  ],
  'WorkingTimeRange' => [
    'labels' => [
      'Create WorkingTimeRange' => 'Create Exception',
      'Calendars' => 'Calendars'
    ],
    'fields' => [
      'timeRanges' => 'Schedule',
      'dateStart' => 'Date Start',
      'dateEnd' => 'Date End',
      'type' => 'Type',
      'calendars' => 'Calendars',
      'users' => 'Users'
    ],
    'links' => [
      'calendars' => 'Calendars',
      'users' => 'Users'
    ],
    'options' => [
      'type' => [
        'Non-working' => 'Non-working',
        'Working' => 'Working'
      ]
    ],
    'presetFilters' => [
      'actual' => 'Actual'
    ],
    'tooltips' => [
      'calendars' => 'Calendars to apply the exception to. The exception will be applied to all users of selected calendars.

Leave the field empty if you need to apply the exception only for specific users.',
      'users' => 'Specific users to apply the exception to.'
    ]
  ],
  'Account' => [
    'fields' => [
      'name' => 'Name',
      'emailAddress' => 'Email',
      'website' => 'Website',
      'phoneNumber' => 'Phone',
      'billingAddress' => 'Billing Address',
      'shippingAddress' => 'Shipping Address',
      'description' => 'Description',
      'sicCode' => 'Sic Code',
      'industry' => 'Industry',
      'type' => 'Type',
      'contactRole' => 'Title',
      'contactIsInactive' => 'Inactive',
      'campaign' => 'Campaign',
      'targetLists' => 'Target Lists',
      'targetList' => 'Target List',
      'originalLead' => 'Original Lead'
    ],
    'links' => [
      'contacts' => 'Contacts',
      'contactsPrimary' => 'Contacts (primary)',
      'opportunities' => 'Opportunities',
      'cases' => 'Cases',
      'documents' => 'Documents',
      'meetingsPrimary' => 'Meetings (expanded)',
      'callsPrimary' => 'Calls (expanded)',
      'tasksPrimary' => 'Tasks (expanded)',
      'emailsPrimary' => 'Emails (expanded)',
      'targetLists' => 'Target Lists',
      'campaignLogRecords' => 'Campaign Log',
      'campaign' => 'Campaign',
      'portalUsers' => 'Portal Users',
      'originalLead' => 'Original Lead'
    ],
    'options' => [
      'type' => [
        'Customer' => 'Customer',
        'Investor' => 'Investor',
        'Partner' => 'Partner',
        'Reseller' => 'Reseller'
      ],
      'industry' => [
        'Aerospace' => 'Aerospace',
        'Agriculture' => 'Agriculture',
        'Advertising' => 'Advertising',
        'Apparel & Accessories' => 'Apparel & Accessories',
        'Architecture' => 'Architecture',
        'Automotive' => 'Automotive',
        'Banking' => 'Banking',
        'Biotechnology' => 'Biotechnology',
        'Building Materials & Equipment' => 'Building Materials & Equipment',
        'Chemical' => 'Chemical',
        'Construction' => 'Construction',
        'Computer' => 'Computer',
        'Defense' => 'Defense',
        'Creative' => 'Creative',
        'Culture' => 'Culture',
        'Consulting' => 'Consulting',
        'Education' => 'Education',
        'Electronics' => 'Electronics',
        'Electric Power' => 'Electric Power',
        'Energy' => 'Energy',
        'Entertainment & Leisure' => 'Entertainment & Leisure',
        'Finance' => 'Finance',
        'Food & Beverage' => 'Food & Beverage',
        'Grocery' => 'Grocery',
        'Hospitality' => 'Hospitality',
        'Healthcare' => 'Healthcare',
        'Insurance' => 'Insurance',
        'Legal' => 'Legal',
        'Manufacturing' => 'Manufacturing',
        'Mass Media' => 'Mass Media',
        'Mining' => 'Mining',
        'Music' => 'Music',
        'Marketing' => 'Marketing',
        'Publishing' => 'Publishing',
        'Petroleum' => 'Petroleum',
        'Real Estate' => 'Real Estate',
        'Retail' => 'Retail',
        'Shipping' => 'Shipping',
        'Service' => 'Service',
        'Support' => 'Support',
        'Sports' => 'Sports',
        'Software' => 'Software',
        'Technology' => 'Technology',
        'Telecommunications' => 'Telecommunications',
        'Television' => 'Television',
        'Testing, Inspection & Certification' => 'Testing, Inspection & Certification',
        'Transportation' => 'Transportation',
        'Travel' => 'Travel',
        'Venture Capital' => 'Venture Capital',
        'Wholesale' => 'Wholesale',
        'Water' => 'Water'
      ]
    ],
    'labels' => [
      'Create Account' => 'Create Account',
      'Copy Billing' => 'Copy Billing',
      'Set Primary' => 'Set Primary'
    ],
    'presetFilters' => [
      'customers' => 'Customers',
      'partners' => 'Partners',
      'recentlyCreated' => 'Recently Created'
    ],
    'tabs' => [
      'Account' => 'Account',
      'Support' => 'Support'
    ]
  ],
  'Calendar' => [
    'modes' => [
      'month' => 'Month',
      'week' => 'Week',
      'day' => 'Day',
      'agendaWeek' => 'Week',
      'agendaDay' => 'Day',
      'timeline' => 'Timeline'
    ],
    'labels' => [
      'Today' => 'Today',
      'Create' => 'Create',
      'Shared' => 'Shared',
      'Add User' => 'Add User',
      'current' => 'current',
      'time' => 'time',
      'User List' => 'User List',
      'View Calendar' => 'View Calendar',
      'Create Shared View' => 'Create Shared View',
      'Edit Shared View' => 'Edit Shared View',
      'Shared Mode Options' => 'Shared Mode Options'
    ]
  ],
  'Call' => [
    'fields' => [
      'name' => 'Name',
      'parent' => 'Parent',
      'status' => 'Status',
      'dateStart' => 'Date Start',
      'dateEnd' => 'Date End',
      'direction' => 'Direction',
      'duration' => 'Duration',
      'description' => 'Description',
      'users' => 'Users',
      'contacts' => 'Contacts',
      'leads' => 'Leads',
      'reminders' => 'Reminders',
      'account' => 'Account',
      'acceptanceStatus' => 'Acceptance Status',
      'uid' => 'UID'
    ],
    'links' => [],
    'options' => [
      'status' => [
        'Planned' => 'Planned',
        'Held' => 'Held',
        'Not Held' => 'Not Held'
      ],
      'direction' => [
        'Outbound' => 'Outbound',
        'Inbound' => 'Inbound'
      ],
      'acceptanceStatus' => [
        'None' => 'None',
        'Accepted' => 'Accepted',
        'Declined' => 'Declined',
        'Tentative' => 'Tentative'
      ]
    ],
    'massActions' => [
      'setHeld' => 'Set Held',
      'setNotHeld' => 'Set Not Held'
    ],
    'labels' => [
      'Create Call' => 'Create Call',
      'Set Held' => 'Set Held',
      'Set Not Held' => 'Set Not Held',
      'Send Invitations' => 'Send Invitations'
    ],
    'presetFilters' => [
      'planned' => 'Planned',
      'held' => 'Held',
      'todays' => 'Today\'s'
    ]
  ],
  'Campaign' => [
    'fields' => [
      'name' => 'Name',
      'description' => 'Description',
      'status' => 'Status',
      'type' => 'Type',
      'startDate' => 'Start Date',
      'endDate' => 'End Date',
      'targetLists' => 'Target Lists',
      'excludingTargetLists' => 'Excluding Target Lists',
      'sentCount' => 'Sent',
      'openedCount' => 'Opened',
      'clickedCount' => 'Clicked',
      'optedOutCount' => 'Opted Out',
      'bouncedCount' => 'Bounced',
      'optedInCount' => 'Opted In',
      'hardBouncedCount' => 'Hard Bounced',
      'softBouncedCount' => 'Soft Bounced',
      'leadCreatedCount' => 'Leads Created',
      'revenue' => 'Revenue',
      'revenueConverted' => 'Revenue (converted)',
      'budget' => 'Budget',
      'budgetConverted' => 'Budget (converted)',
      'budgetCurrency' => 'Budget Currency',
      'contactsTemplate' => 'Contacts Template',
      'leadsTemplate' => 'Leads Template',
      'accountsTemplate' => 'Accounts Template',
      'usersTemplate' => 'Users Template',
      'mailMergeOnlyWithAddress' => 'Skip records w/o filled address'
    ],
    'links' => [
      'targetLists' => 'Target Lists',
      'excludingTargetLists' => 'Excluding Target Lists',
      'accounts' => 'Accounts',
      'contacts' => 'Contacts',
      'leads' => 'Leads',
      'opportunities' => 'Opportunities',
      'campaignLogRecords' => 'Log',
      'massEmails' => 'Mass Emails',
      'trackingUrls' => 'Tracking URLs',
      'contactsTemplate' => 'Contacts Template',
      'leadsTemplate' => 'Leads Template',
      'accountsTemplate' => 'Accounts Template',
      'usersTemplate' => 'Users Template'
    ],
    'options' => [
      'type' => [
        'Email' => 'Email',
        'Informational Email' => 'Informational Email',
        'Web' => 'Web',
        'Television' => 'Television',
        'Radio' => 'Radio',
        'Newsletter' => 'Newsletter',
        'Mail' => 'Mail'
      ],
      'status' => [
        'Planning' => 'Planning',
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Complete' => 'Complete'
      ]
    ],
    'labels' => [
      'Create Campaign' => 'Create Campaign',
      'Target Lists' => 'Target Lists',
      'Statistics' => 'Statistics',
      'hard' => 'hard',
      'soft' => 'soft',
      'Unsubscribe' => 'Unsubscribe',
      'Mass Emails' => 'Mass Emails',
      'Email Templates' => 'Email Templates',
      'Unsubscribe again' => 'Unsubscribe again',
      'Subscribe again' => 'Subscribe again',
      'Create Target List' => 'Create Target List',
      'Mail Merge' => 'Mail Merge',
      'Generate Mail Merge PDF' => 'Generate Mail Merge PDF'
    ],
    'presetFilters' => [
      'active' => 'Active'
    ],
    'messages' => [
      'cannotChangeType' => 'Cannot change type.',
      'unsubscribed' => 'You have been unsubscribed from our mailing list.',
      'subscribedAgain' => 'You are subscribed again.'
    ],
    'tooltips' => [
      'targetLists' => 'Targets that should receive messages.',
      'excludingTargetLists' => 'Targets that should not receive messages.'
    ]
  ],
  'CampaignLogRecord' => [
    'fields' => [
      'action' => 'Action',
      'actionDate' => 'Date',
      'data' => 'Data',
      'campaign' => 'Campaign',
      'parent' => 'Target',
      'object' => 'Object',
      'application' => 'Application',
      'queueItem' => 'Queue Item',
      'stringData' => 'String Data',
      'stringAdditionalData' => 'String Additional Data',
      'isTest' => 'Is Test'
    ],
    'links' => [
      'queueItem' => 'Queue Item',
      'parent' => 'Parent',
      'object' => 'Object',
      'campaign' => 'Campaign'
    ],
    'options' => [
      'action' => [
        'Sent' => 'Sent',
        'Opened' => 'Opened',
        'Opted Out' => 'Opted Out',
        'Bounced' => 'Bounced',
        'Clicked' => 'Clicked',
        'Lead Created' => 'Lead Created',
        'Opted In' => 'Opted In'
      ]
    ],
    'labels' => [
      'All' => 'All'
    ],
    'presetFilters' => [
      'sent' => 'Sent',
      'opened' => 'Opened',
      'optedOut' => 'Opted Out',
      'optedIn' => 'Opted In',
      'bounced' => 'Bounced',
      'clicked' => 'Clicked',
      'leadCreated' => 'Lead Created'
    ]
  ],
  'CampaignTrackingUrl' => [
    'fields' => [
      'url' => 'URL',
      'action' => 'Action',
      'urlToUse' => 'Code to insert instead of URL',
      'message' => 'Message',
      'campaign' => 'Campaign'
    ],
    'links' => [
      'campaign' => 'Campaign'
    ],
    'labels' => [
      'Create CampaignTrackingUrl' => 'Create Tracking URL'
    ],
    'options' => [
      'action' => [
        'Redirect' => 'Redirect',
        'Show Message' => 'Show Message'
      ]
    ],
    'tooltips' => [
      'url' => 'The recipient will be redirected to this location after they follow the link.',
      'message' => 'The message will be shown to the recipient after they follow the link. Markdown is supported.'
    ]
  ],
  'Case' => [
    'fields' => [
      'name' => 'Name',
      'number' => 'Number',
      'status' => 'Status',
      'account' => 'Account',
      'contact' => 'Contact',
      'contacts' => 'Contacts',
      'priority' => 'Priority',
      'type' => 'Type',
      'description' => 'Description',
      'inboundEmail' => 'Group Email Account',
      'lead' => 'Lead',
      'attachments' => 'Attachments',
      'originalEmail' => 'Original Email',
      'isInternal' => 'Hidden from Portal'
    ],
    'links' => [
      'inboundEmail' => 'Group Email Account',
      'account' => 'Account',
      'contact' => 'Contact (Primary)',
      'Contacts' => 'Contacts',
      'meetings' => 'Meetings',
      'calls' => 'Calls',
      'tasks' => 'Tasks',
      'emails' => 'Emails',
      'articles' => 'Knowledge Base Articles',
      'lead' => 'Lead',
      'attachments' => 'Attachments'
    ],
    'options' => [
      'status' => [
        'New' => 'New',
        'Assigned' => 'Assigned',
        'Pending' => 'Pending',
        'Closed' => 'Closed',
        'Rejected' => 'Rejected',
        'Duplicate' => 'Duplicate'
      ],
      'priority' => [
        'Low' => 'Low',
        'Normal' => 'Normal',
        'High' => 'High',
        'Urgent' => 'Urgent'
      ],
      'type' => [
        'Question' => 'Question',
        'Incident' => 'Incident',
        'Problem' => 'Problem'
      ]
    ],
    'labels' => [
      'Create Case' => 'Create Case',
      'Close' => 'Close',
      'Reject' => 'Reject',
      'Closed' => 'Closed',
      'Rejected' => 'Rejected'
    ],
    'presetFilters' => [
      'open' => 'Open',
      'closed' => 'Closed'
    ]
  ],
  'Contact' => [
    'fields' => [
      'name' => 'Name',
      'emailAddress' => 'Email',
      'title' => 'Account Title',
      'account' => 'Account',
      'accounts' => 'Accounts',
      'phoneNumber' => 'Phone',
      'accountType' => 'Account Type',
      'doNotCall' => 'Do Not Call',
      'address' => 'Address',
      'opportunityRole' => 'Opportunity Role',
      'accountRole' => 'Title',
      'description' => 'Description',
      'campaign' => 'Campaign',
      'targetLists' => 'Target Lists',
      'targetList' => 'Target List',
      'portalUser' => 'Portal User',
      'hasPortalUser' => 'Has Portal User',
      'originalLead' => 'Original Lead',
      'acceptanceStatus' => 'Acceptance Status',
      'accountIsInactive' => 'Account Inactive',
      'acceptanceStatusMeetings' => 'Acceptance Status (Meetings)',
      'acceptanceStatusCalls' => 'Acceptance Status (Calls)',
      'originalEmail' => 'Original Email'
    ],
    'links' => [
      'opportunities' => 'Opportunities',
      'cases' => 'Cases',
      'targetLists' => 'Target Lists',
      'campaignLogRecords' => 'Campaign Log',
      'campaign' => 'Campaign',
      'account' => 'Account (Primary)',
      'accounts' => 'Accounts',
      'casesPrimary' => 'Cases (Primary)',
      'tasksPrimary' => 'Tasks (expanded)',
      'opportunitiesPrimary' => 'Opportunities (Primary)',
      'portalUser' => 'Portal User',
      'originalLead' => 'Original Lead',
      'documents' => 'Documents'
    ],
    'labels' => [
      'Create Contact' => 'Create Contact'
    ],
    'options' => [
      'opportunityRole' => [
        '' => '',
        'Decision Maker' => 'Decision Maker',
        'Evaluator' => 'Evaluator',
        'Influencer' => 'Influencer'
      ]
    ],
    'presetFilters' => [
      'portalUsers' => 'Portal Users',
      'notPortalUsers' => 'Not Portal Users',
      'accountActive' => 'Active'
    ]
  ],
  'Document' => [
    'labels' => [
      'Create Document' => 'Create Document',
      'Details' => 'Details'
    ],
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'file' => 'File',
      'type' => 'Type',
      'publishDate' => 'Publish Date',
      'expirationDate' => 'Expiration Date',
      'description' => 'Description',
      'accounts' => 'Accounts',
      'folder' => 'Folder'
    ],
    'links' => [
      'accounts' => 'Accounts',
      'opportunities' => 'Opportunities',
      'folder' => 'Folder',
      'leads' => 'Leads',
      'contacts' => 'Contacts'
    ],
    'options' => [
      'status' => [
        'Active' => 'Active',
        'Draft' => 'Draft',
        'Expired' => 'Expired',
        'Canceled' => 'Canceled'
      ],
      'type' => [
        '' => 'None',
        'Contract' => 'Contract',
        'NDA' => 'NDA',
        'EULA' => 'EULA',
        'License Agreement' => 'License Agreement'
      ]
    ],
    'presetFilters' => [
      'active' => 'Active',
      'draft' => 'Draft'
    ]
  ],
  'DocumentFolder' => [
    'labels' => [
      'Create DocumentFolder' => 'Create Document Folder',
      'Manage Categories' => 'Manage Folders',
      'Documents' => 'Documents'
    ],
    'links' => [
      'documents' => 'Documents'
    ]
  ],
  'EmailQueueItem' => [
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'target' => 'Target',
      'sentAt' => 'Date Sent',
      'attemptCount' => 'Attempts',
      'emailAddress' => 'Email Address',
      'massEmail' => 'Mass Email',
      'isTest' => 'Is Test'
    ],
    'links' => [
      'target' => 'Target',
      'massEmail' => 'Mass Email'
    ],
    'options' => [
      'status' => [
        'Pending' => 'Pending',
        'Sent' => 'Sent',
        'Failed' => 'Failed',
        'Sending' => 'Sending'
      ]
    ],
    'presetFilters' => [
      'pending' => 'Pending',
      'sent' => 'Sent',
      'failed' => 'Failed'
    ]
  ],
  'KnowledgeBaseArticle' => [
    'labels' => [
      'Create KnowledgeBaseArticle' => 'Create Article',
      'Any' => 'Any',
      'Send in Email' => 'Send in Email',
      'Move Up' => 'Move Up',
      'Move Down' => 'Move Down',
      'Move to Top' => 'Move to Top',
      'Move to Bottom' => 'Move to Bottom'
    ],
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'type' => 'Type',
      'attachments' => 'Attachments',
      'publishDate' => 'Publish Date',
      'expirationDate' => 'Expiration Date',
      'description' => 'Description',
      'body' => 'Body',
      'categories' => 'Categories',
      'language' => 'Language',
      'portals' => 'Portals',
      'bodyPlain' => 'Body Plain'
    ],
    'links' => [
      'cases' => 'Cases',
      'opportunities' => 'Opportunities',
      'categories' => 'Categories',
      'portals' => 'Portals'
    ],
    'options' => [
      'status' => [
        'In Review' => 'In Review',
        'Draft' => 'Draft',
        'Archived' => 'Archived',
        'Published' => 'Published'
      ],
      'type' => [
        'Article' => 'Article'
      ]
    ],
    'tooltips' => [
      'portals' => 'Article will be available only in specified portals.'
    ],
    'presetFilters' => [
      'published' => 'Published'
    ]
  ],
  'KnowledgeBaseCategory' => [
    'labels' => [
      'Create KnowledgeBaseCategory' => 'Create Category',
      'Manage Categories' => 'Manage Categories',
      'Articles' => 'Articles'
    ],
    'links' => [
      'articles' => 'Articles'
    ]
  ],
  'Lead' => [
    'labels' => [
      'Converted To' => 'Converted To',
      'Create Lead' => 'Create Lead',
      'Convert' => 'Convert',
      'convert' => 'convert'
    ],
    'fields' => [
      'name' => 'Name',
      'emailAddress' => 'Email',
      'title' => 'Title',
      'website' => 'Website',
      'phoneNumber' => 'Phone',
      'accountName' => 'Account Name',
      'doNotCall' => 'Do Not Call',
      'address' => 'Address',
      'status' => 'Status',
      'source' => 'Source',
      'opportunityAmount' => 'Opportunity Amount',
      'opportunityAmountConverted' => 'Opportunity Amount (converted)',
      'description' => 'Description',
      'createdAccount' => 'Account',
      'createdContact' => 'Contact',
      'createdOpportunity' => 'Opportunity',
      'convertedAt' => 'Converted At',
      'campaign' => 'Campaign',
      'targetLists' => 'Target Lists',
      'targetList' => 'Target List',
      'industry' => 'Industry',
      'acceptanceStatus' => 'Acceptance Status',
      'opportunityAmountCurrency' => 'Opportunity Amount Currency',
      'acceptanceStatusMeetings' => 'Acceptance Status (Meetings)',
      'acceptanceStatusCalls' => 'Acceptance Status (Calls)',
      'originalEmail' => 'Original Email'
    ],
    'links' => [
      'targetLists' => 'Target Lists',
      'campaignLogRecords' => 'Campaign Log',
      'campaign' => 'Campaign',
      'createdAccount' => 'Account',
      'createdContact' => 'Contact',
      'createdOpportunity' => 'Opportunity',
      'cases' => 'Cases',
      'documents' => 'Documents'
    ],
    'options' => [
      'status' => [
        'New' => 'New',
        'Assigned' => 'Assigned',
        'In Process' => 'In Process',
        'Converted' => 'Converted',
        'Recycled' => 'Recycled',
        'Dead' => 'Dead'
      ],
      'source' => [
        'Call' => 'Call',
        'Email' => 'Email',
        'Existing Customer' => 'Existing Customer',
        'Partner' => 'Partner',
        'Public Relations' => 'Public Relations',
        'Web Site' => 'Web Site',
        'Campaign' => 'Campaign',
        'Other' => 'Other'
      ]
    ],
    'presetFilters' => [
      'active' => 'Active',
      'actual' => 'Open',
      'converted' => 'Converted'
    ]
  ],
  'MassEmail' => [
    'fields' => [
      'name' => 'Name',
      'status' => 'Status',
      'storeSentEmails' => 'Store Sent Emails',
      'startAt' => 'Date Start',
      'fromAddress' => 'From Address',
      'fromName' => 'From Name',
      'replyToAddress' => 'Reply-to Address',
      'replyToName' => 'Reply-to Name',
      'campaign' => 'Campaign',
      'emailTemplate' => 'Email Template',
      'inboundEmail' => 'Email Account',
      'targetLists' => 'Target Lists',
      'excludingTargetLists' => 'Excluding Target Lists',
      'optOutEntirely' => 'Opt-Out Entirely',
      'smtpAccount' => 'SMTP Account'
    ],
    'links' => [
      'targetLists' => 'Target Lists',
      'excludingTargetLists' => 'Excluding Target Lists',
      'queueItems' => 'Queue Items',
      'campaign' => 'Campaign',
      'emailTemplate' => 'Email Template',
      'inboundEmail' => 'Email Account'
    ],
    'options' => [
      'status' => [
        'Draft' => 'Draft',
        'Pending' => 'Pending',
        'In Process' => 'In Process',
        'Complete' => 'Complete',
        'Canceled' => 'Canceled',
        'Failed' => 'Failed'
      ]
    ],
    'labels' => [
      'Create MassEmail' => 'Create Mass Email',
      'Send Test' => 'Send Test',
      'System SMTP' => 'System SMTP',
      'system' => 'system',
      'group' => 'group'
    ],
    'messages' => [
      'selectAtLeastOneTarget' => 'Select at least one target.',
      'testSent' => 'Test email(s) supposed to be sent'
    ],
    'tooltips' => [
      'optOutEntirely' => 'Email addresses of recipients that unsubscribed will be marked as opted out and they will not receive any mass emails anymore.',
      'targetLists' => 'Targets that should receive messages.',
      'excludingTargetLists' => 'Targets that should not receive messages.',
      'storeSentEmails' => 'Emails will be stored in CRM.'
    ],
    'presetFilters' => [
      'actual' => 'Active',
      'complete' => 'Complete'
    ]
  ],
  'Meeting' => [
    'fields' => [
      'name' => 'Name',
      'parent' => 'Parent',
      'status' => 'Status',
      'dateStart' => 'Date Start',
      'dateEnd' => 'Date End',
      'duration' => 'Duration',
      'description' => 'Description',
      'users' => 'Users',
      'contacts' => 'Contacts',
      'leads' => 'Leads',
      'reminders' => 'Reminders',
      'account' => 'Account',
      'acceptanceStatus' => 'Acceptance Status',
      'dateStartDate' => 'Date Start (all day)',
      'dateEndDate' => 'Date End (all day)',
      'isAllDay' => 'Is All-Day',
      'sourceEmail' => 'Source Email',
      'uid' => 'UID',
      'joinUrl' => 'Join URL'
    ],
    'links' => [],
    'options' => [
      'status' => [
        'Planned' => 'Planned',
        'Held' => 'Held',
        'Not Held' => 'Not Held'
      ],
      'acceptanceStatus' => [
        'None' => 'None',
        'Accepted' => 'Accepted',
        'Declined' => 'Declined',
        'Tentative' => 'Tentative'
      ]
    ],
    'massActions' => [
      'setHeld' => 'Set Held',
      'setNotHeld' => 'Set Not Held'
    ],
    'labels' => [
      'Create Meeting' => 'Create Meeting',
      'Set Held' => 'Set Held',
      'Set Not Held' => 'Set Not Held',
      'Send Invitations' => 'Send Invitations',
      'Send Cancellation' => 'Send Cancellation',
      'on time' => 'on time',
      'before' => 'before',
      'All-Day' => 'All-Day',
      'Acceptance' => 'Acceptance'
    ],
    'presetFilters' => [
      'planned' => 'Planned',
      'held' => 'Held',
      'todays' => 'Today\'s'
    ],
    'messages' => [
      'sendInvitationsToSelectedAttendees' => 'Invitation emails will be sent to the selected attendees.',
      'sendCancellationsToSelectedAttendees' => 'Cancellation emails will be sent to the selected attendees.',
      'selectAcceptanceStatus' => 'Set your acceptance status.',
      'nothingHasBeenSent' => 'Nothing were sent'
    ]
  ],
  'Opportunity' => [
    'fields' => [
      'name' => 'Name',
      'account' => 'Account',
      'stage' => 'Stage',
      'amount' => 'Amount',
      'probability' => 'Probability, %',
      'leadSource' => 'Lead Source',
      'doNotCall' => 'Do Not Call',
      'closeDate' => 'Close Date',
      'contacts' => 'Contacts',
      'contact' => 'Contact (Primary)',
      'description' => 'Description',
      'amountConverted' => 'Amount (converted)',
      'amountWeightedConverted' => 'Amount Weighted',
      'campaign' => 'Campaign',
      'originalLead' => 'Original Lead',
      'amountCurrency' => 'Amount Currency',
      'contactRole' => 'Contact Role',
      'lastStage' => 'Last Stage'
    ],
    'links' => [
      'contacts' => 'Contacts',
      'contact' => 'Contact (Primary)',
      'documents' => 'Documents',
      'campaign' => 'Campaign',
      'originalLead' => 'Original Lead'
    ],
    'options' => [
      'stage' => [
        'Prospecting' => 'Prospecting',
        'Qualification' => 'Qualification',
        'Proposal' => 'Proposal',
        'Negotiation' => 'Negotiation',
        'Needs Analysis' => 'Needs Analysis',
        'Value Proposition' => 'Value Proposition',
        'Id. Decision Makers' => 'Id. Decision Makers',
        'Perception Analysis' => 'Perception Analysis',
        'Proposal/Price Quote' => 'Proposal/Price Quote',
        'Negotiation/Review' => 'Negotiation/Review',
        'Closed Won' => 'Closed Won',
        'Closed Lost' => 'Closed Lost'
      ]
    ],
    'labels' => [
      'Create Opportunity' => 'Create Opportunity'
    ],
    'presetFilters' => [
      'open' => 'Open',
      'won' => 'Won',
      'lost' => 'Lost'
    ]
  ],
  'TargetList' => [
    'fields' => [
      'name' => 'Name',
      'description' => 'Description',
      'entryCount' => 'Entry Count',
      'optedOutCount' => 'Opted Out Count',
      'campaigns' => 'Campaigns',
      'endDate' => 'End Date',
      'targetLists' => 'Target Lists',
      'includingActionList' => 'Including',
      'excludingActionList' => 'Excluding',
      'targetStatus' => 'Target Status',
      'isOptedOut' => 'Is Opted Out',
      'sourceCampaign' => 'Source Campaign',
      'category' => 'Category'
    ],
    'links' => [
      'accounts' => 'Accounts',
      'contacts' => 'Contacts',
      'leads' => 'Leads',
      'campaigns' => 'Campaigns',
      'massEmails' => 'Mass Emails',
      'category' => 'Category'
    ],
    'options' => [
      'type' => [
        'Email' => 'Email',
        'Web' => 'Web',
        'Television' => 'Television',
        'Radio' => 'Radio',
        'Newsletter' => 'Newsletter'
      ],
      'targetStatus' => [
        'Opted Out' => 'Opted Out',
        'Listed' => 'Listed'
      ]
    ],
    'labels' => [
      'Create TargetList' => 'Create Target List',
      'Opted Out' => 'Opted Out',
      'Cancel Opt-Out' => 'Cancel Opt-Out',
      'Opt-Out' => 'Opt-Out'
    ]
  ],
  'TargetListCategory' => [
    'labels' => [
      'Create TargetListCategory' => 'Create Category'
    ],
    'links' => [
      'targetLists' => 'Target Lists'
    ]
  ],
  'Task' => [
    'fields' => [
      'name' => 'Name',
      'parent' => 'Parent',
      'status' => 'Status',
      'dateStart' => 'Date Start',
      'dateEnd' => 'Date Due',
      'dateStartDate' => 'Date Start (all day)',
      'dateEndDate' => 'Date End (all day)',
      'priority' => 'Priority',
      'description' => 'Description',
      'isOverdue' => 'Is Overdue',
      'account' => 'Account',
      'dateCompleted' => 'Date Completed',
      'attachments' => 'Attachments',
      'reminders' => 'Reminders',
      'contact' => 'Contact',
      'originalEmail' => 'Original Email'
    ],
    'links' => [
      'attachments' => 'Attachments',
      'account' => 'Account',
      'contact' => 'Contact',
      'email' => 'Email'
    ],
    'options' => [
      'status' => [
        'Not Started' => 'Not Started',
        'Started' => 'Started',
        'Completed' => 'Completed',
        'Canceled' => 'Canceled',
        'Deferred' => 'Deferred'
      ],
      'priority' => [
        'Low' => 'Low',
        'Normal' => 'Normal',
        'High' => 'High',
        'Urgent' => 'Urgent'
      ]
    ],
    'labels' => [
      'Create Task' => 'Create Task',
      'Complete' => 'Complete',
      'overdue' => 'overdue'
    ],
    'presetFilters' => [
      'actual' => 'Open',
      'completed' => 'Completed',
      'deferred' => 'Deferred',
      'todays' => 'Today\'s',
      'overdue' => 'Overdue'
    ],
    'nameOptions' => [
      'replyToEmail' => 'Reply to email'
    ]
  ]
];
