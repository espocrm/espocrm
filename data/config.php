<?php
return [
  'useCache' => false,
  'jobMaxPortion' => 15,
  'jobRunInParallel' => false,
  'jobPoolConcurrencyNumber' => 8,
  'daemonMaxProcessNumber' => 5,
  'daemonInterval' => 10,
  'daemonProcessTimeout' => 36000,
  'recordsPerPage' => 50,
  'recordsPerPageSmall' => 30,
  'recordsPerPageSelect' => 35,
  'recordsPerPageKanban' => 100,
  'applicationName' => 'Freedom',
  'version' => '7.5.5',
  'timeZone' => 'Europe/Kiev',
  'dateFormat' => 'DD.MM.YYYY',
  'timeFormat' => 'HH:mm',
  'weekStart' => 1,
  'thousandSeparator' => ',',
  'decimalMark' => '.',
  'exportDelimiter' => ',',
  'currencyList' => [
    0 => 'UAH'
  ],
  'defaultCurrency' => 'UAH',
  'baseCurrency' => 'UAH',
  'currencyRates' => [],
  'currencyNoJoinMode' => false,
  'outboundEmailIsShared' => true,
  'outboundEmailFromName' => 'Freedom DS',
  'outboundEmailFromAddress' => 'billjoynf@gmail.com',
  'smtpServer' => 'smtp.gmail.com',
  'smtpPort' => 587,
  'smtpAuth' => true,
  'smtpSecurity' => 'TLS',
  'smtpUsername' => 'billjoynf@gmail.com',
  'language' => 'uk_UA',
  'authenticationMethod' => 'Espo',
  'globalSearchEntityList' => [
    0 => 'Contact',
    1 => 'Abonement'
  ],
  'tabList' => [
    0 => (object) [
      'type' => 'group',
      'text' => 'Події',
      'iconClass' => 'fas fa-calendar-check',
      'color' => NULL,
      'id' => '822152',
      'itemList' => [
        0 => 'Calendar',
        1 => 'Training',
        2 => 'Indiv',
        3 => 'Rent',
        4 => 'Event'
      ]
    ],
    1 => 'AttendanceSheet',
    2 => 'Contact',
    3 => 'Abonement',
    4 => 'Goods',
    5 => 'Ticket',
    6 => 'RentPlan',
    7 => (object) [
      'type' => 'divider',
      'text' => 'Менеджмент',
      'id' => '117676'
    ],
    8 => (object) [
      'type' => 'group',
      'text' => 'Адмін',
      'iconClass' => 'fas fa-user-edit',
      'color' => NULL,
      'id' => '680055',
      'itemList' => [
        0 => 'AdminMark',
        1 => 'Mark'
      ]
    ],
    9 => (object) [
      'type' => 'group',
      'text' => 'Облік',
      'iconClass' => 'fas fa-file-invoice-dollar',
      'color' => NULL,
      'id' => '906957',
      'itemList' => [
        0 => 'Expenses',
        1 => 'Fine'
      ]
    ],
    10 => (object) [
      'type' => 'group',
      'text' => 'Аналітика',
      'iconClass' => 'fas fa-chart-bar',
      'color' => NULL,
      'id' => '845134',
      'itemList' => [
        0 => 'CalculationBudget'
      ]
    ],
    11 => (object) [
      'type' => 'group',
      'text' => 'Послуги',
      'iconClass' => 'fas fa-hand-holding-usd',
      'color' => NULL,
      'id' => '89473',
      'itemList' => [
        0 => 'ProductItem',
        1 => 'Abonplan',
        2 => 'Abonservice',
        3 => 'Pricebook',
        4 => 'Discount'
      ]
    ],
    12 => (object) [
      'type' => 'group',
      'text' => 'Групи',
      'iconClass' => 'fas fa-users-cog',
      'color' => NULL,
      'id' => '535103',
      'itemList' => [
        0 => 'Group',
        1 => 'Direction'
      ]
    ],
    13 => (object) [
      'type' => 'group',
      'text' => 'Розклад',
      'iconClass' => 'fas fa-calendar-minus',
      'color' => NULL,
      'id' => '492602',
      'itemList' => [
        0 => 'TrainingTemplate',
        1 => 'WeekTemplate'
      ]
    ],
    14 => (object) [
      'type' => 'group',
      'text' => 'Студія',
      'iconClass' => 'fas fa-door-open',
      'color' => NULL,
      'id' => '4935',
      'itemList' => [
        0 => 'User',
        1 => 'Hall',
        2 => 'Branch'
      ]
    ],
    15 => '_delimiter_',
    16 => (object) [
      'type' => 'group',
      'text' => 'Налаштування',
      'iconClass' => 'fas fa-cog',
      'color' => NULL,
      'id' => '3560',
      'itemList' => [
        0 => 'CustomSettings',
        1 => 'TrainerCategory',
        2 => 'EventType',
        3 => 'TicketType'
      ]
    ],
    17 => (object) [
      'type' => 'group',
      'text' => 'Додатково',
      'iconClass' => 'fas fa-chess-knight',
      'color' => NULL,
      'id' => '288933',
      'itemList' => [
        0 => 'Lead',
        1 => 'Opportunity',
        2 => 'Meeting'
      ]
    ],
    18 => 'Stream'
  ],
  'quickCreateList' => [
    0 => 'Contact',
    1 => 'Abonement',
    2 => 'Training',
    3 => 'Indiv'
  ],
  'exportDisabled' => false,
  'adminNotifications' => true,
  'adminNotificationsNewVersion' => false,
  'adminNotificationsCronIsNotConfigured' => true,
  'adminNotificationsNewExtensionVersion' => false,
  'assignmentEmailNotifications' => false,
  'assignmentEmailNotificationsEntityList' => [
    0 => 'Lead',
    1 => 'Opportunity',
    2 => 'Task',
    3 => 'Case'
  ],
  'assignmentNotificationsEntityList' => [
    0 => 'Call',
    1 => 'Email'
  ],
  'portalStreamEmailNotifications' => true,
  'streamEmailNotificationsEntityList' => [
    0 => 'Case'
  ],
  'streamEmailNotificationsTypeList' => [
    0 => 'Post',
    1 => 'Status',
    2 => 'EmailReceived'
  ],
  'emailNotificationsDelay' => 30,
  'emailMessageMaxSize' => 10,
  'emailRecipientAddressMaxCount' => 100,
  'notificationsCheckInterval' => 10,
  'popupNotificationsCheckInterval' => 15,
  'maxEmailAccountCount' => 2,
  'followCreatedEntities' => true,
  'b2cMode' => false,
  'theme' => 'Violet',
  'themeParams' => (object) [
    'navbar' => 'side'
  ],
  'massEmailMaxPerHourCount' => 100,
  'massEmailVerp' => false,
  'personalEmailMaxPortionSize' => 50,
  'inboundEmailMaxPortionSize' => 50,
  'emailAddressLookupEntityTypeList' => [
    0 => 'User',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Account'
  ],
  'emailAddressEntityLookupDefaultOrder' => [
    0 => 'User',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Account'
  ],
  'phoneNumberEntityLookupDefaultOrder' => [
    0 => 'User',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Account'
  ],
  'authTokenLifetime' => 0,
  'authTokenMaxIdleTime' => 48,
  'userNameRegularExpression' => '[^a-z0-9\\-@_\\.\\s]',
  'addressFormat' => 1,
  'displayListViewRecordCount' => true,
  'dashboardLayout' => [
    0 => (object) [
      'name' => 'My Espo',
      'layout' => [
        0 => (object) [
          'id' => 'default-stream',
          'name' => 'Stream',
          'x' => 0,
          'y' => 0,
          'width' => 2,
          'height' => 4
        ],
        1 => (object) [
          'id' => 'default-activities',
          'name' => 'Activities',
          'x' => 2,
          'y' => 2,
          'width' => 2,
          'height' => 4
        ]
      ]
    ]
  ],
  'calendarEntityList' => [
    0 => 'Training',
    1 => 'Indiv',
    2 => 'Rent',
    3 => 'AdminMark',
    4 => 'Event',
    5 => 'Meeting'
  ],
  'activitiesEntityList' => [
    0 => 'Training',
    1 => 'Indiv',
    2 => 'Rent',
    3 => 'AdminMark',
    4 => 'Event',
    5 => 'Meeting'
  ],
  'historyEntityList' => [
    0 => 'Training',
    1 => 'Indiv',
    2 => 'Rent',
    3 => 'AdminMark',
    4 => 'Event',
    5 => 'Meeting'
  ],
  'busyRangesEntityList' => [
    0 => 'Training',
    1 => 'Indiv',
    2 => 'Rent',
    3 => 'AdminMark',
    4 => 'Event',
    5 => 'Meeting'
  ],
  'emailAutoReplySuppressPeriod' => '2 hours',
  'emailAutoReplyLimit' => 5,
  'cleanupJobPeriod' => '1 month',
  'cleanupActionHistoryPeriod' => '15 days',
  'cleanupAuthTokenPeriod' => '1 month',
  'cleanupSubscribers' => true,
  'currencyFormat' => 1,
  'currencyDecimalPlaces' => 2,
  'aclAllowDeleteCreated' => false,
  'aclAllowDeleteCreatedThresholdPeriod' => '24 hours',
  'attachmentAvailableStorageList' => NULL,
  'attachmentUploadMaxSize' => 256,
  'attachmentUploadChunkSize' => 4,
  'inlineAttachmentUploadMaxSize' => 20,
  'textFilterUseContainsForVarchar' => false,
  'tabColorsDisabled' => true,
  'massPrintPdfMaxCount' => 50,
  'emailKeepParentTeamsEntityList' => [
    0 => 'Case'
  ],
  'streamEmailWithContentEntityTypeList' => [
    0 => 'Case'
  ],
  'recordListMaxSizeLimit' => 200,
  'noteDeleteThresholdPeriod' => '1 month',
  'noteEditThresholdPeriod' => '7 days',
  'emailForceUseExternalClient' => false,
  'useWebSocket' => false,
  'auth2FAMethodList' => [
    0 => 'Totp'
  ],
  'auth2FAInPortal' => false,
  'personNameFormat' => 'firstLast',
  'newNotificationCountInTitle' => false,
  'pdfEngine' => 'Dompdf',
  'smsProvider' => NULL,
  'defaultFileStorage' => 'EspoUploadDir',
  'ldapUserNameAttribute' => 'sAMAccountName',
  'ldapUserFirstNameAttribute' => 'givenName',
  'ldapUserLastNameAttribute' => 'sn',
  'ldapUserTitleAttribute' => 'title',
  'ldapUserEmailAddressAttribute' => 'mail',
  'ldapUserPhoneNumberAttribute' => 'telephoneNumber',
  'ldapUserObjectClass' => 'person',
  'ldapPortalUserLdapAuth' => false,
  'passwordGenerateLength' => 10,
  'massActionIdleCountThreshold' => 100,
  'exportIdleCountThreshold' => 1000,
  'oidcJwtSignatureAlgorithmList' => [
    0 => 'RS256'
  ],
  'oidcUsernameClaim' => 'sub',
  'oidcFallback' => true,
  'oidcScopes' => [
    0 => 'profile',
    1 => 'email',
    2 => 'phone'
  ],
  'cacheTimestamp' => 1709835192,
  'microtime' => 1709835192.533476,
  'siteUrl' => 'https://dsfreedom.art',
  'fullTextSearchMinLength' => 4,
  'appTimestamp' => 1695834342,
  'userThemesDisabled' => false,
  'avatarsDisabled' => false,
  'scopeColorsDisabled' => false,
  'tabIconsDisabled' => false,
  'dashletsOptions' => (object) [],
  'maintenanceMode' => false,
  'cronDisabled' => false,
  'emailAddressIsOptedOutByDefault' => false,
  'cleanupDeletedRecords' => false,
  'fiscalYearShift' => 0,
  'addressCountryList' => [],
  'addressCityList' => [],
  'addressStateList' => [],
  'workingTimeCalendarName' => NULL,
  'workingTimeCalendarId' => NULL,
  'companyLogoId' => '65e7240e590737b5d',
  'companyLogoName' => 'logo.png',
  'latestVersion' => '8.0.2',
  'outboundEmailBccAddress' => NULL,
  'massEmailDisableMandatoryOptOutLink' => false,
  'massEmailOpenTracking' => false,
  'streamEmailNotifications' => false,
  'mentionEmailNotifications' => false,
  'massEmailMaxPerBatchCount' => NULL,
  'mapProvider' => 'Google',
  'listViewSettingsDisabled' => false,
  'phoneNumberNumericSearch' => true,
  'phoneNumberInternational' => true,
  'phoneNumberPreferredCountryList' => [
    0 => 'ua'
  ]
];
