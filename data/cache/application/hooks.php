<?php
return [
  'Account' => [
    'afterRelate' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Account\\Contacts',
        'order' => 9
      ]
    ],
    'afterUnrelate' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Account\\Contacts',
        'order' => 9
      ]
    ],
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Account\\TargetList',
        'order' => 9
      ]
    ]
  ],
  'Call' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Call\\CalendarWebSocket',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Call\\CalendarWebSocket',
        'order' => 9
      ]
    ],
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Call\\ParentLink',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Call\\Uid',
        'order' => 9
      ],
      2 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Call\\Users',
        'order' => 12
      ]
    ]
  ],
  'Case' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\CaseObj\\Contacts',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\CaseObj\\IsInternal',
        'order' => 9
      ]
    ]
  ],
  'Common' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Common\\FieldProcessing',
        'order' => -11
      ],
      1 => [
        'className' => 'Espo\\Hooks\\Common\\ForeignFields',
        'order' => 8
      ],
      2 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Common\\CalendarWebSocket',
        'order' => 9
      ],
      3 => [
        'className' => 'Espo\\Hooks\\Common\\AssignmentEmailNotification',
        'order' => 9
      ],
      4 => [
        'className' => 'Espo\\Hooks\\Common\\Stream',
        'order' => 9
      ],
      5 => [
        'className' => 'Espo\\Hooks\\Common\\Notifications',
        'order' => 10
      ],
      6 => [
        'className' => 'Espo\\Hooks\\Common\\StreamNotesAcl',
        'order' => 10
      ],
      7 => [
        'className' => 'Espo\\Hooks\\Common\\WebSocketSubmit',
        'order' => 20
      ],
      8 => [
        'className' => 'Espo\\Hooks\\Common\\CallDeferredActions',
        'order' => 101
      ],
      9 => [
        'className' => 'Espo\\Hooks\\Common\\Webhook',
        'order' => 101
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Common\\CalendarWebSocket',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Hooks\\Common\\Stream',
        'order' => 9
      ],
      2 => [
        'className' => 'Espo\\Hooks\\Common\\Notifications',
        'order' => 10
      ],
      3 => [
        'className' => 'Espo\\Hooks\\Common\\Webhook',
        'order' => 101
      ]
    ],
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Common\\CurrencyConverted',
        'order' => 1
      ],
      1 => [
        'className' => 'Espo\\Hooks\\Common\\Collaborators',
        'order' => 7
      ],
      2 => [
        'className' => 'Espo\\Hooks\\Common\\DeleteId',
        'order' => 9
      ],
      3 => [
        'className' => 'Espo\\Hooks\\Common\\NextNumber',
        'order' => 9
      ],
      4 => [
        'className' => 'Espo\\Hooks\\Common\\Stream',
        'order' => 9
      ],
      5 => [
        'className' => 'Espo\\Hooks\\Common\\VersionNumber',
        'order' => 9
      ],
      6 => [
        'className' => 'Espo\\Hooks\\Common\\Formula',
        'order' => 11
      ],
      7 => [
        'className' => 'Espo\\Hooks\\Common\\CurrencyDefault',
        'order' => 200
      ]
    ],
    'beforeRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\Common\\DeleteId',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Hooks\\Common\\Notifications',
        'order' => 10
      ]
    ],
    'afterRelate' => [
      0 => [
        'className' => 'Espo\\Hooks\\Common\\Stream',
        'order' => 9
      ]
    ],
    'afterUnrelate' => [
      0 => [
        'className' => 'Espo\\Hooks\\Common\\Stream',
        'order' => 9
      ]
    ]
  ],
  'Contact' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Contact\\Accounts',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Contact\\TargetList',
        'order' => 9
      ]
    ],
    'afterRelate' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Contact\\Opportunities',
        'order' => 9
      ]
    ],
    'afterUnrelate' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Contact\\Opportunities',
        'order' => 9
      ]
    ]
  ],
  'KnowledgeBaseArticle' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\KnowledgeBaseArticle\\SetBodyPlain',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\KnowledgeBaseArticle\\SetOrder',
        'order' => 9
      ]
    ]
  ],
  'Lead' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Lead\\ConvertedAt',
        'order' => 9
      ]
    ],
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Lead\\TargetList',
        'order' => 9
      ]
    ]
  ],
  'MassEmail' => [
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\MassEmail\\DeleteQueue',
        'order' => 9
      ]
    ]
  ],
  'Meeting' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\CalendarWebSocket',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\CalendarWebSocket',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\EmailCreatedEvent',
        'order' => 9
      ]
    ],
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\ParentLink',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\Uid',
        'order' => 9
      ],
      2 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Meeting\\Users',
        'order' => 12
      ]
    ]
  ],
  'Opportunity' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Opportunity\\AmountWeightedConverted',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Opportunity\\Contacts',
        'order' => 9
      ]
    ],
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Opportunity\\Probability',
        'order' => 7
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Opportunity\\LastStage',
        'order' => 8
      ]
    ]
  ],
  'Task' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Task\\CalendarWebSocket',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Task\\CalendarWebSocket',
        'order' => 9
      ]
    ],
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Task\\DateCompleted',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Modules\\Crm\\Hooks\\Task\\ParentLink',
        'order' => 9
      ]
    ]
  ],
  'AddressCountry' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\AddressCountry\\ClearCache',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\AddressCountry\\ClearCache',
        'order' => 9
      ]
    ]
  ],
  'Attachment' => [
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\Attachment\\RemoveFile',
        'order' => 9
      ]
    ]
  ],
  'CurrencyRecordRate' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\CurrencyRecordRate\\SetFields',
        'order' => 9
      ]
    ]
  ],
  'Email' => [
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\Email\\NoteRemove',
        'order' => 9
      ]
    ]
  ],
  'EmailFilter' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\EmailFilter\\CacheClearing',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\EmailFilter\\CacheClearing',
        'order' => 9
      ]
    ]
  ],
  'GroupEmailFolder' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\GroupEmailFolder\\Order',
        'order' => 9
      ]
    ]
  ],
  'Integration' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Integration\\GoogleMaps',
        'order' => 9
      ]
    ]
  ],
  'LayoutSet' => [
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\LayoutSet\\Removal',
        'order' => 9
      ]
    ]
  ],
  'LeadCapture' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\LeadCapture\\ClearCache',
        'order' => 9
      ]
    ]
  ],
  'Note' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Note\\Mentions',
        'order' => 9
      ]
    ],
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Note\\StreamUpdatedAt',
        'order' => 9
      ],
      1 => [
        'className' => 'Espo\\Hooks\\Note\\Notifications',
        'order' => 14
      ],
      2 => [
        'className' => 'Espo\\Hooks\\Note\\WebSocketSubmit',
        'order' => 20
      ]
    ]
  ],
  'Notification' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Notification\\WebSocketSubmit',
        'order' => 20
      ]
    ]
  ],
  'Portal' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Portal\\WriteConfig',
        'order' => 9
      ]
    ]
  ],
  'Sms' => [
    'beforeSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Sms\\Numbers',
        'order' => 9
      ]
    ]
  ],
  'Template' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\Template\\WebSocketSubmit',
        'order' => 9
      ]
    ]
  ],
  'User' => [
    'afterSave' => [
      0 => [
        'className' => 'Espo\\Hooks\\User\\ApiKey',
        'order' => 9
      ]
    ],
    'afterRemove' => [
      0 => [
        'className' => 'Espo\\Hooks\\User\\ApiKey',
        'order' => 9
      ]
    ]
  ]
];
