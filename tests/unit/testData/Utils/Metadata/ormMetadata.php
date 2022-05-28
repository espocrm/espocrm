<?php

return  [
    'Attachment' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'type' =>
               [
                   'type' => 'varchar',
                   'len' => 36,
               ],
              'size' =>
               [
                   'type' => 'int',
                   'len' => 11,
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Currency' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'rate' =>
               [
                   'type' => 'float',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
          ],
         'relations' =>
          [
          ],
     ],
    'Email' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'subject' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'len' => 255,
               ],
              'fromName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'from' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'len' => 255,
               ],
              'to' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'len' => 255,
               ],
              'cc' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'len' => 255,
               ],
              'bcc' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'len' => 255,
               ],
              'bodyPlain' =>
               [
                   'type' => 'text',
               ],
              'body' =>
               [
                   'type' => 'text',
               ],
              'isHtml' =>
               [
                   'type' => 'bool',
                   'default' => true,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'dateSent' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'bccEmailAddressesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'bccEmailAddressesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'ccEmailAddressesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'ccEmailAddressesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'toEmailAddressesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'toEmailAddressesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'fromEmailAddressName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'fromEmailAddress',
                   'foreign' => 'name',
               ],
              'fromEmailAddressId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'attachmentsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'attachmentsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'bccEmailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'emailId',
                        1 => 'emailAddressId',
                    ],
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
              'ccEmailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'emailId',
                        1 => 'emailAddressId',
                    ],
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
              'toEmailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'emailId',
                        1 => 'emailAddressId',
                    ],
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
              'fromEmailAddress' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'EmailAddress',
                   'key' => 'fromEmailAddressId',
                   'foreignKey' => 'id',
               ],
              'attachments' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Attachment',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Email',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'EmailAddress' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'lower' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'invalid' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'optOut' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
          ],
         'relations' =>
          [
          ],
     ],
    'EmailTemplate' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'subject' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'body' =>
               [
                   'type' => 'text',
               ],
              'isHtml' =>
               [
                   'type' => 'bool',
                   'default' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'attachmentsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'attachmentsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'EmailTemplate',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'attachments' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Attachment',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
          ],
     ],
    'Job' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'default' => 'Pending',
                   'len' => 255,
               ],
              'executeTime' =>
               [
                   'type' => 'datetime',
               ],
              'serviceName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'method' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'data' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'scheduledJobName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'scheduledJob',
                   'foreign' => 'name',
               ],
              'scheduledJobId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'scheduledJob' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'ScheduledJob',
                   'key' => 'scheduledJobId',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Note' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
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
                   'len' => 255,
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'attachmentsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'attachmentsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'attachments' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Attachment',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Notification' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'data' =>
               [
                   'type' => 'text',
               ],
              'type' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'read' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'userName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'user',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'userId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'user' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'userId',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'OutboundEmail' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'server' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'port' =>
               [
                   'type' => 'int',
                   'default' => '25',
                   'len' => 11,
               ],
              'auth' =>
               [
                   'type' => 'bool',
                   'default' => true,
               ],
              'security' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'username' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'password' =>
               [
                   'type' => 'password',
               ],
              'fromName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'fromAddress' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'userName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'user',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'userId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'user' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'userId',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Preferences' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'timeZone' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'dateFormat' =>
               [
                   'type' => 'varchar',
                   'default' => 'MM/DD/YYYY',
                   'len' => 255,
               ],
              'timeFormat' =>
               [
                   'type' => 'varchar',
                   'default' => 'HH:mm',
                   'len' => 255,
               ],
              'weekStart' =>
               [
                   'type' => 'int',
                   'default' => '0',
                   'len' => 11,
               ],
              'thousandSeparator' =>
               [
                   'type' => 'varchar',
                   'default' => ',',
                   'len' => 255,
               ],
              'decimalMark' =>
               [
                   'type' => 'varchar',
                   'default' => '.',
                   'len' => 255,
               ],
              'defaultCurrency' =>
               [
                   'type' => 'varchar',
                   'default' => 'USD',
                   'len' => 255,
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
                   'len' => 255,
               ],
              'smtpPort' =>
               [
                   'type' => 'int',
                   'default' => '25',
                   'len' => 11,
               ],
              'smtpAuth' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'smtpSecurity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'smtpUsername' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'smtpPassword' =>
               [
                   'type' => 'password',
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
          ],
         'relations' =>
          [
          ],
     ],
    'Role' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 150,
               ],
              'data' =>
               [
                   'type' => 'text',
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'roleTeam',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'roleId',
                        1 => 'teamId',
                    ],
               ],
              'users' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'User',
                   'relationName' => 'roleUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'roleId',
                        1 => 'userId',
                    ],
               ],
          ],
     ],
    'ScheduledJob' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'job' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'scheduling' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'lastRun' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'logIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'logNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'log' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'ScheduledJobLogRecord',
                   'foreignKey' => 'scheduledJobId',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'ScheduledJobLogRecord' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'executionTime' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'scheduledJobName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'scheduledJob',
                   'foreign' => 'name',
               ],
              'scheduledJobId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'scheduledJob' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'ScheduledJob',
                   'key' => 'scheduledJobId',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Settings' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'useCache' =>
               [
                   'type' => 'bool',
                   'default' => true,
               ],
              'recordsPerPage' =>
               [
                   'type' => 'int',
                   'default' => '20',
                   'len' => 11,
               ],
              'recordsPerPageSmall' =>
               [
                   'type' => 'int',
                   'default' => '10',
                   'len' => 11,
               ],
              'timeZone' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'dateFormat' =>
               [
                   'type' => 'varchar',
                   'default' => 'MM/DD/YYYY',
                   'len' => 255,
               ],
              'timeFormat' =>
               [
                   'type' => 'varchar',
                   'default' => 'HH:mm',
                   'len' => 255,
               ],
              'weekStart' =>
               [
                   'type' => 'int',
                   'default' => '0',
                   'len' => 11,
               ],
              'thousandSeparator' =>
               [
                   'type' => 'varchar',
                   'default' => ',',
                   'len' => 255,
               ],
              'decimalMark' =>
               [
                   'type' => 'varchar',
                   'default' => '.',
                   'len' => 255,
               ],
              'currencyList' =>
               [
                   'type' => 'json_array',
                   'default' => '["USD","EUR"]',
               ],
              'defaultCurrency' =>
               [
                   'type' => 'varchar',
                   'default' => 'USD',
                   'len' => 255,
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
                   'len' => 255,
               ],
              'outboundEmailFromAddress' =>
               [
                   'type' => 'varchar',
                   'default' => 'crm@example.com',
                   'len' => 255,
               ],
              'smtpServer' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'smtpPort' =>
               [
                   'type' => 'int',
                   'default' => '25',
                   'len' => 11,
               ],
              'smtpAuth' =>
               [
                   'type' => 'bool',
                   'default' => true,
               ],
              'smtpSecurity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'smtpUsername' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'smtpPassword' =>
               [
                   'type' => 'password',
               ],
              'tabList' =>
               [
                   'type' => 'json_array',
                   'default' => '["Account","Contact","Lead","Opportunity","Calendar","Meeting","Call","Task","Case","Prospect"]',
               ],
              'quickCreateList' =>
               [
                   'type' => 'json_array',
                   'default' => '["Account","Contact","Lead","Opportunity","Meeting","Call","Task","Case","Prospect"]',
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
          ],
         'relations' =>
          [
          ],
     ],
    'Team' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'rolesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'rolesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'roles' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Role',
                   'relationName' => 'roleTeam',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'teamId',
                        1 => 'roleId',
                    ],
               ],
              'users' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'User',
                   'relationName' => 'teamUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'teamId',
                        1 => 'userId',
                    ],
               ],
          ],
     ],
    'User' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'select' => 'TRIM(CONCAT(user.first_name, \' \', user.last_name))',
                   'where' =>
                    [
                        'LIKE' => '(user.first_name LIKE \'{text}\' OR user.last_name LIKE \'{text}\' OR CONCAT(user.first_name, \' \', user.last_name) LIKE \'{text}\')',
                    ],
                   'orderBy' => 'user.first_name {direction}, user.last_name {direction}',
               ],
              'isAdmin' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'userName' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
                   'unique' => true,
               ],
              'salutationName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'firstName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'lastName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'password' =>
               [
                   'type' => 'password',
               ],
              'title' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'emailAddress' =>
               [
                   'type' => 'email',
                   'notStorable' => true,
                   'select' => 'email_address.name',
                   'where' =>
                    [
                        'LIKE' => 'email_address.name LIKE \'{text}\'',
                        '=' => 'email_address.name = \'{text}\'',
                    ],
                   'orderBy' => 'email_address.name {direction}',
               ],
              'phone' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'rolesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'rolesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'defaultTeamName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'defaultTeam',
                   'foreign' => 'name',
               ],
              'defaultTeamId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'calls' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Call',
                   'relationName' => 'callUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'userId',
                        1 => 'callId',
                    ],
               ],
              'meetings' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Meeting',
                   'relationName' => 'meetingUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'userId',
                        1 => 'meetingId',
                    ],
               ],
              'roles' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Role',
                   'relationName' => 'roleUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'userId',
                        1 => 'roleId',
                    ],
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'teamUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'userId',
                        1 => 'teamId',
                    ],
               ],
              'defaultTeam' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Team',
                   'key' => 'defaultTeamId',
                   'foreignKey' => 'id',
               ],
              'emailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'relationName' => 'entityEmailAddress',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'email_address_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'User',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                        'primary' =>
                         [
                             'type' => 'bool',
                             'default' => false,
                         ],
                    ],
               ],
          ],
     ],
    'Account' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'website' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'emailAddress' =>
               [
                   'type' => 'email',
                   'notStorable' => true,
                   'select' => 'email_address.name',
                   'where' =>
                    [
                        'LIKE' => 'email_address.name LIKE \'{text}\'',
                        '=' => 'email_address.name = \'{text}\'',
                    ],
                   'orderBy' => 'email_address.name {direction}',
               ],
              'phone' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'fax' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'type' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'industry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'sicCode' =>
               [
                   'type' => 'varchar',
                   'len' => 40,
               ],
              'billingAddressStreet' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'billingAddressCity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'billingAddressState' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'billingAddressCountry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'billingAddressPostalCode' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'shippingAddressStreet' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'shippingAddressCity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'shippingAddressState' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'shippingAddressCountry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'shippingAddressPostalCode' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'emailsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'emailsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'casesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'casesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'isFollowed' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'emails' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Email',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'tasks' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Task',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'calls' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Call',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'meetings' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Meeting',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'cases' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'Case',
                   'foreignKey' => 'accountId',
               ],
              'opportunities' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'Opportunity',
                   'foreignKey' => 'accountId',
               ],
              'contacts' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'Contact',
                   'foreignKey' => 'accountId',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Account',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
              'emailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'relationName' => 'entityEmailAddress',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'email_address_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Account',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                        'primary' =>
                         [
                             'type' => 'bool',
                             'default' => false,
                         ],
                    ],
               ],
          ],
     ],
    'Call' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'default' => 'Planned',
                   'len' => 255,
               ],
              'dateStart' =>
               [
                   'type' => 'datetime',
               ],
              'dateEnd' =>
               [
                   'type' => 'datetime',
               ],
              'duration' =>
               [
                   'type' => 'int',
                   'default' => '300',
                   'len' => 11,
               ],
              'direction' =>
               [
                   'type' => 'varchar',
                   'default' => 'Outbound',
                   'len' => 255,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'leadsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'leadsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'leads' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Lead',
                   'relationName' => 'callLead',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'callId',
                        1 => 'leadId',
                    ],
               ],
              'contacts' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Contact',
                   'relationName' => 'callContact',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'callId',
                        1 => 'contactId',
                    ],
               ],
              'users' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'User',
                   'relationName' => 'callUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'callId',
                        1 => 'userId',
                    ],
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Call',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Case' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'number' =>
               [
                   'type' => 'int',
                   'autoincrement' => true,
                   'unique' => true,
                   'len' => 11,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'default' => 'New',
                   'len' => 255,
               ],
              'priority' =>
               [
                   'type' => 'varchar',
                   'default' => 'Normal',
                   'len' => 255,
               ],
              'type' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'emailsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'emailsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'contact',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'contactId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'accountName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'account',
                   'foreign' => 'name',
               ],
              'accountId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'isFollowed' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'emails' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Email',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'tasks' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Task',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'calls' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Call',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'meetings' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Meeting',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'contact' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Contact',
                   'key' => 'contactId',
                   'foreignKey' => 'id',
               ],
              'account' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Account',
                   'key' => 'accountId',
                   'foreignKey' => 'id',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Case',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Contact' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'select' => 'TRIM(CONCAT(contact.first_name, \' \', contact.last_name))',
                   'where' =>
                    [
                        'LIKE' => '(contact.first_name LIKE \'{text}\' OR contact.last_name LIKE \'{text}\' OR CONCAT(contact.first_name, \' \', contact.last_name) LIKE \'{text}\')',
                    ],
                   'orderBy' => 'contact.first_name {direction}, contact.last_name {direction}',
               ],
              'salutationName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'firstName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'lastName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'title' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'emailAddress' =>
               [
                   'type' => 'email',
                   'notStorable' => true,
                   'select' => 'email_address.name',
                   'where' =>
                    [
                        'LIKE' => 'email_address.name LIKE \'{text}\'',
                        '=' => 'email_address.name = \'{text}\'',
                    ],
                   'orderBy' => 'email_address.name {direction}',
               ],
              'phone' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'fax' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'doNotCall' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'phoneOffice' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'addressStreet' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressState' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCountry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressPostalCode' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'accountType' =>
               [
                   'type' => 'foreign',
                   'relation' => 'account',
                   'foreign' => 'type',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'tasksIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'casesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'casesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'accountName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'account',
                   'foreign' => 'name',
               ],
              'accountId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'isFollowed' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'tasks' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Task',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'calls' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Call',
                   'relationName' => 'callContact',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'contactId',
                        1 => 'callId',
                    ],
               ],
              'meetings' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Meeting',
                   'relationName' => 'contactMeeting',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'contactId',
                        1 => 'meetingId',
                    ],
               ],
              'cases' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'Case',
                   'foreignKey' => 'contactId',
               ],
              'opportunities' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Opportunity',
                   'relationName' => 'contactOpportunity',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'contactId',
                        1 => 'opportunityId',
                    ],
               ],
              'account' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Account',
                   'key' => 'accountId',
                   'foreignKey' => 'id',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Contact',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
              'emailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'relationName' => 'entityEmailAddress',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'email_address_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Contact',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                        'primary' =>
                         [
                             'type' => 'bool',
                             'default' => false,
                         ],
                    ],
               ],
          ],
     ],
    'InboundEmail' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'host' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'port' =>
               [
                   'type' => 'varchar',
                   'default' => '143',
                   'len' => 255,
               ],
              'username' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'password' =>
               [
                   'type' => 'password',
               ],
              'monitoredFolders' =>
               [
                   'type' => 'varchar',
                   'default' => 'INBOX',
                   'len' => 255,
               ],
              'trashFolder' =>
               [
                   'type' => 'varchar',
                   'default' => 'INBOX.Trash',
                   'len' => 255,
               ],
              'createCase' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'caseDistribution' =>
               [
                   'type' => 'varchar',
                   'default' => 'Direct-Assignment',
                   'len' => 255,
               ],
              'reply' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'replyFromAddress' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'replyFromName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'replyEmailTemplateName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'replyEmailTemplate',
                   'foreign' => 'name',
               ],
              'replyEmailTemplateId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'teamName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'team',
                   'foreign' => 'name',
               ],
              'teamId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'assignToUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignToUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignToUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
          ],
         'relations' =>
          [
              'replyEmailTemplate' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'EmailTemplate',
                   'key' => 'replyEmailTemplateId',
                   'foreignKey' => 'id',
               ],
              'team' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Team',
                   'key' => 'teamId',
                   'foreignKey' => 'id',
               ],
              'assignToUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignToUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Lead' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'select' => 'TRIM(CONCAT(lead.first_name, \' \', lead.last_name))',
                   'where' =>
                    [
                        'LIKE' => '(lead.first_name LIKE \'{text}\' OR lead.last_name LIKE \'{text}\' OR CONCAT(lead.first_name, \' \', lead.last_name) LIKE \'{text}\')',
                    ],
                   'orderBy' => 'lead.first_name {direction}, lead.last_name {direction}',
               ],
              'salutationName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'firstName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'lastName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'title' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'default' => 'New',
                   'len' => 255,
               ],
              'source' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'opportunityAmountCurrency' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'opportunityAmount' =>
               [
                   'type' => 'float',
                   'notnull' => false,
               ],
              'website' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressStreet' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressState' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCountry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressPostalCode' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'emailAddress' =>
               [
                   'type' => 'email',
                   'notStorable' => true,
                   'select' => 'email_address.name',
                   'where' =>
                    [
                        'LIKE' => 'email_address.name LIKE \'{text}\'',
                        '=' => 'email_address.name = \'{text}\'',
                    ],
                   'orderBy' => 'email_address.name {direction}',
               ],
              'phone' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'fax' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'phoneOffice' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'doNotCall' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'accountName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'createdOpportunityName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdOpportunity',
                   'foreign' => 'name',
               ],
              'createdOpportunityId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdContactName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdContact',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdContactId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdAccountName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdAccount',
                   'foreign' => 'name',
               ],
              'createdAccountId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'tasksIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'opportunitiesNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'isFollowed' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'createdOpportunity' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Opportunity',
                   'key' => 'createdOpportunityId',
                   'foreignKey' => 'id',
               ],
              'createdContact' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Contact',
                   'key' => 'createdContactId',
                   'foreignKey' => 'id',
               ],
              'createdAccount' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Account',
                   'key' => 'createdAccountId',
                   'foreignKey' => 'id',
               ],
              'tasks' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Task',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'calls' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Call',
                   'relationName' => 'callLead',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'leadId',
                        1 => 'callId',
                    ],
               ],
              'meetings' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Meeting',
                   'relationName' => 'leadMeeting',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'leadId',
                        1 => 'meetingId',
                    ],
               ],
              'opportunities' =>
               [
                   'type' => 'hasMany',
                   'entity' => 'Opportunity',
                   'foreignKey' => 'id',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Lead',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
              'emailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'relationName' => 'entityEmailAddress',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'email_address_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Lead',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                        'primary' =>
                         [
                             'type' => 'bool',
                             'default' => false,
                         ],
                    ],
               ],
          ],
     ],
    'Meeting' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'default' => 'Planned',
                   'len' => 255,
               ],
              'dateStart' =>
               [
                   'type' => 'datetime',
               ],
              'dateEnd' =>
               [
                   'type' => 'datetime',
               ],
              'duration' =>
               [
                   'type' => 'int',
                   'default' => '3600',
                   'len' => 11,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'leadsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'leadsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'usersNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'leads' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Lead',
                   'relationName' => 'leadMeeting',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'meetingId',
                        1 => 'leadId',
                    ],
               ],
              'contacts' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Contact',
                   'relationName' => 'contactMeeting',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'meetingId',
                        1 => 'contactId',
                    ],
               ],
              'users' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'User',
                   'relationName' => 'meetingUser',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'meetingId',
                        1 => 'userId',
                    ],
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Meeting',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Opportunity' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'amountCurrency' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'amount' =>
               [
                   'type' => 'float',
                   'notnull' => false,
               ],
              'stage' =>
               [
                   'type' => 'varchar',
                   'default' => 'Prospecting',
                   'len' => 255,
               ],
              'probability' =>
               [
                   'type' => 'int',
                   'len' => 11,
               ],
              'leadSource' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'closeDate' =>
               [
                   'type' => 'date',
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'emailsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'emailsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'tasksNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'callsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'meetingsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'contactsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'accountName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'account',
                   'foreign' => 'name',
               ],
              'accountId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'isFollowed' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'emails' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Email',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'tasks' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Task',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'calls' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Call',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'meetings' =>
               [
                   'type' => 'hasChildren',
                   'entity' => 'Meeting',
                   'foreignKey' => 'parentId',
                   'foreignType' => 'parentType',
               ],
              'contacts' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Contact',
                   'relationName' => 'contactOpportunity',
                   'key' => 'id',
                   'foreignKey' => 'id',
                   'midKeys' =>
                    [
                        0 => 'opportunityId',
                        1 => 'contactId',
                    ],
               ],
              'account' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'Account',
                   'key' => 'accountId',
                   'foreignKey' => 'id',
               ],
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Opportunity',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
    'Prospect' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
                   'select' => 'TRIM(CONCAT(prospect.first_name, \' \', prospect.last_name))',
                   'where' =>
                    [
                        'LIKE' => '(prospect.first_name LIKE \'{text}\' OR prospect.last_name LIKE \'{text}\' OR CONCAT(prospect.first_name, \' \', prospect.last_name) LIKE \'{text}\')',
                    ],
                   'orderBy' => 'prospect.first_name {direction}, prospect.last_name {direction}',
               ],
              'salutationName' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'firstName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'lastName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'title' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'accountName' =>
               [
                   'type' => 'varchar',
                   'len' => 100,
               ],
              'website' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressStreet' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCity' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressState' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressCountry' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'addressPostalCode' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'emailAddress' =>
               [
                   'type' => 'email',
                   'notStorable' => true,
                   'select' => 'email_address.name',
                   'where' =>
                    [
                        'LIKE' => 'email_address.name LIKE \'{text}\'',
                        '=' => 'email_address.name = \'{text}\'',
                    ],
                   'orderBy' => 'email_address.name {direction}',
               ],
              'phone' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'fax' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'phoneOffice' =>
               [
                   'type' => 'varchar',
                   'len' => 50,
               ],
              'doNotCall' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Prospect',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
              'emailAddresses' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'EmailAddress',
                   'relationName' => 'entityEmailAddress',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'email_address_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Prospect',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                        'primary' =>
                         [
                             'type' => 'bool',
                             'default' => false,
                         ],
                    ],
               ],
          ],
     ],
    'Task' =>
     [
         'fields' =>
          [
              'id' =>
               [
                   'type' => 'id',
                   'dbType' => 'varchar',
                   'len' => '24',
               ],
              'name' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'status' =>
               [
                   'type' => 'varchar',
                   'len' => 255,
               ],
              'priority' =>
               [
                   'type' => 'varchar',
                   'default' => 'Normal',
                   'len' => 255,
               ],
              'dateStart' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'dateEnd' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'isOverdue' =>
               [
                   'type' => 'base',
                   'notStorable' => true,
               ],
              'description' =>
               [
                   'type' => 'text',
               ],
              'parent' =>
               [
                   'type' => 'linkParent',
                   'notStorable' => true,
               ],
              'createdAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'modifiedAt' =>
               [
                   'type' => 'datetime',
                   'notnull' => false,
               ],
              'deleted' =>
               [
                   'type' => 'bool',
                   'default' => false,
               ],
              'assignedUserName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'assignedUser',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'assignedUserId' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'modifiedByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'modifiedBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'modifiedById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'createdByName' =>
               [
                   'type' => 'foreign',
                   'relation' => 'createdBy',
                   'foreign' =>
                    [
                        0 => 'firstName',
                        1 => ' ',
                        2 => 'lastName',
                    ],
               ],
              'createdById' =>
               [
                   'type' => 'foreignId',
                   'index' => true,
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentId' =>
               [
                   'type' => 'foreignId',
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => '24',
                   'notnull' => false,
               ],
              'parentType' =>
               [
                   'type' => 'foreignType',
                   'notnull' => false,
                   'index' => 'parent',
                   'dbType' => 'varchar',
                   'len' => 255,
               ],
              'parentName' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsIds' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
              'teamsNames' =>
               [
                   'type' => 'varchar',
                   'notStorable' => true,
               ],
          ],
         'relations' =>
          [
              'teams' =>
               [
                   'type' => 'manyMany',
                   'entity' => 'Team',
                   'relationName' => 'entityTeam',
                   'midKeys' =>
                    [
                        0 => 'entity_id',
                        1 => 'team_id',
                    ],
                   'conditions' =>
                    [
                        'entityType' => 'Task',
                    ],
                   'additionalColumns' =>
                    [
                        'entityType' =>
                         [
                             'type' => 'varchar',
                             'len' => 100,
                         ],
                    ],
               ],
              'assignedUser' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'assignedUserId',
                   'foreignKey' => 'id',
               ],
              'modifiedBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'modifiedById',
                   'foreignKey' => 'id',
               ],
              'createdBy' =>
               [
                   'type' => 'belongsTo',
                   'entity' => 'User',
                   'key' => 'createdById',
                   'foreignKey' => 'id',
               ],
          ],
     ],
];

?>