<?php

return [
    'attributes' => [
        'id' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'id',
        ],
        'name' =>
        [
        'type' => 'varchar',
        'len' => 255,
        ],
        'deleted' =>
        [
        'type' => 'bool',
        'default' => false,
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
        'fromString' =>
        [
        'type' => 'varchar',
        'len' => 255,
        ],
        'replyToString' =>
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
        'replyTo' =>
        [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ],
        'personStringData' =>
        [
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ],
        'isRead' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => true,
        ],
        'isNotRead' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'isReplied' =>
        [
        'type' => 'bool',
        'default' => false,
        ],
        'isNotReplied' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'isImportant' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'inTrash' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'folderId' =>
        [
        'dbType' => 'varchar',
        'len' => 255,
        'type' => 'foreignId',
        'notStorable' => true,
        'default' => '',
        'index' => 'folder',
        'notNull' => false,
        ],
        'isUsers' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'nameHash' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'typeHash' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'idHash' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'messageId' =>
        [
        'type' => 'varchar',
        'len' => 255,
        ],
        'messageIdInternal' =>
        [
        'type' => 'varchar',
        'len' => 300,
        'index' => true,
        ],
        'emailAddress' =>
        [
        'type' => 'base',
        'notStorable' => true,
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
        'default' => 'Archived',
        'len' => 255,
        ],
        'hasAttachment' =>
        [
        'type' => 'bool',
        'default' => false,
        ],
        'dateSent' =>
        [
        'type' => 'datetime',
        'notNull' => false,
        ],
        'deliveryDate' =>
        [
        'type' => 'datetime',
        'notNull' => false,
        ],
        'createdAt' =>
        [
        'type' => 'datetime',
        'notNull' => false,
        ],
        'modifiedAt' =>
        [
        'type' => 'datetime',
        'notNull' => false,
        ],
        'isSystem' =>
        [
        'type' => 'bool',
        'default' => false,
        ],
        'isJustSent' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'isBeingImported' =>
        [
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ],
        'folderName' =>
        [
        'type' => 'varchar',
        'notStorable' => true,
        ],
        'fromEmailAddressId' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'fromEmailAddressName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'fromEmailAddress',
        'foreign' => 'name',
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
        'parentId' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'parent',
        'notNull' => false,
        ],
        'parentType' =>
        [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'dbType' => 'varchar',
        ],
        'parentName' =>
        [
        'type' => 'varchar',
        'notStorable' => true,
        ],
        'createdById' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'createdByName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'createdBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
        ],
        'sentById' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'sentByName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'sentBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
        ],
        'modifiedById' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'modifiedByName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'modifiedBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
        ],
        'assignedUserId' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'assignedUserName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'assignedUser',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
        ],
        'repliedId' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'repliedName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'replied',
        'foreign' => 'name',
        ],
        'repliesIds' =>
        [
        'type' => 'varchar',
        'notStorable' => true,
        ],
        'repliesNames' =>
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
        'usersIds' =>
        [
        'type' => 'jsonArray',
        'notStorable' => true,
        ],
        'usersNames' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'usersColumns' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'assignedUsersIds' =>
        [
        'type' => 'jsonArray',
        'notStorable' => true,
        ],
        'assignedUsersNames' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'inboundEmailsIds' =>
        [
        'type' => 'jsonArray',
        'notStorable' => true,
        ],
        'inboundEmailsNames' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'emailAccountsIds' =>
        [
        'type' => 'jsonArray',
        'notStorable' => true,
        ],
        'emailAccountsNames' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
        'accountId' =>
        [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ],
        'accountName' =>
        [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'account',
        'foreign' => 'name',
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
        'attachmentsTypes' =>
        [
        'type' => 'jsonObject',
        'notStorable' => true,
        ],
    ],
    'relations' => [
        'account' =>
        [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        ],
        'emailAccounts' =>
        [
        'type' => 'manyMany',
        'entity' => 'EmailAccount',
        'relationName' => 'emailEmailAccount',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'emailAccountId',
        ],
        ],
        'inboundEmails' =>
        [
        'type' => 'manyMany',
        'entity' => 'InboundEmail',
        'relationName' => 'emailInboundEmail',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'inboundEmailId',
        ],
        ],
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
        'relationName' => 'emailEmailAddress',
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
        'relationName' => 'emailEmailAddress',
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
        'relationName' => 'emailEmailAddress',
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
        'replies' =>
        [
        'type' => 'hasMany',
        'entity' => 'Email',
        'foreignKey' => 'repliedId',
        ],
        'replied' =>
        [
        'type' => 'belongsTo',
        'entity' => 'Email',
        'key' => 'repliedId',
        'foreignKey' => 'id',
        ],
        'parent' =>
        [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        ],
        'sentBy' =>
        [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'sentById',
        'foreignKey' => 'id',
        ],
        'users' =>
        [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'emailUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' =>
        [
          0 => 'emailId',
          1 => 'userId',
        ],
        'additionalColumns' =>
        [
          'isRead' =>
          [
            'type' => 'bool',
            'default' => false,
          ],
          'isImportant' =>
          [
            'type' => 'bool',
            'default' => false,
          ],
          'inTrash' =>
          [
            'type' => 'bool',
            'default' => false,
          ],
          'folderId' =>
          [
            'type' => 'varchar',
            'default' => NULL,
            'maxLength' => 24,
          ],
        ],
        ],
        'assignedUsers' =>
        [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'entityUser',
        'midKeys' =>
        [
          0 => 'entityId',
          1 => 'userId',
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
        'attachments' =>
        [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'relationName' => 'attachments',
        ],
    ]
];
