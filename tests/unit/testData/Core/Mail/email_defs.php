<?php

return array(
    'fields' => array(
        'id' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'id',
        ),
        'name' => 
        array (
        'type' => 'varchar',
        'len' => 255,
        ),
        'deleted' => 
        array (
        'type' => 'bool',
        'default' => false,
        ),
        'subject' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'fromName' => 
        array (
        'type' => 'varchar',
        'len' => 255,
        ),
        'fromString' => 
        array (
        'type' => 'varchar',
        'len' => 255,
        ),
        'replyToString' => 
        array (
        'type' => 'varchar',
        'len' => 255,
        ),
        'from' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'to' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'cc' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'bcc' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'replyTo' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'personStringData' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        'len' => 255,
        ),
        'isRead' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => true,
        ),
        'isNotRead' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ),
        'isReplied' => 
        array (
        'type' => 'bool',
        'default' => false,
        ),
        'isNotReplied' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
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
        'dbType' => 'varchar',
        'len' => 255,
        'type' => 'foreignId',
        'notStorable' => true,
        'default' => '',
        'index' => 'folder',
        'notNull' => false,
        ),
        'isUsers' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ),
        'nameHash' => 
        array (
        'type' => 'text',
        'notStorable' => true,
        ),
        'typeHash' => 
        array (
        'type' => 'text',
        'notStorable' => true,
        ),
        'idHash' => 
        array (
        'type' => 'text',
        'notStorable' => true,
        ),
        'messageId' => 
        array (
        'type' => 'varchar',
        'len' => 255,
        ),
        'messageIdInternal' => 
        array (
        'type' => 'varchar',
        'len' => 300,
        'index' => true,
        ),
        'emailAddress' => 
        array (
        'type' => 'base',
        'notStorable' => true,
        ),
        'bodyPlain' => 
        array (
        'type' => 'text',
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
        'status' => 
        array (
        'type' => 'varchar',
        'default' => 'Archived',
        'len' => 255,
        ),
        'hasAttachment' => 
        array (
        'type' => 'bool',
        'default' => false,
        ),
        'dateSent' => 
        array (
        'type' => 'datetime',
        'notNull' => false,
        ),
        'deliveryDate' => 
        array (
        'type' => 'datetime',
        'notNull' => false,
        ),
        'createdAt' => 
        array (
        'type' => 'datetime',
        'notNull' => false,
        ),
        'modifiedAt' => 
        array (
        'type' => 'datetime',
        'notNull' => false,
        ),
        'isSystem' => 
        array (
        'type' => 'bool',
        'default' => false,
        ),
        'isJustSent' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ),
        'isBeingImported' => 
        array (
        'type' => 'bool',
        'notStorable' => true,
        'default' => false,
        ),
        'folderName' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'fromEmailAddressId' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'fromEmailAddressName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'fromEmailAddress',
        'foreign' => 'name',
        ),
        'toEmailAddressesIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'toEmailAddressesNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'ccEmailAddressesIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'ccEmailAddressesNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'attachmentsIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'attachmentsNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'parentId' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'parent',
        'notNull' => false,
        ),
        'parentType' => 
        array (
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'dbType' => 'varchar',
        ),
        'parentName' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'createdById' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'createdByName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'createdBy',
        'foreign' => 
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
        ),
        'sentById' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'sentByName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'sentBy',
        'foreign' => 
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
        ),
        'modifiedById' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'modifiedByName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'modifiedBy',
        'foreign' => 
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
        ),
        'assignedUserId' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'assignedUserName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'assignedUser',
        'foreign' => 
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
        ),
        'repliedId' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'repliedName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'replied',
        'foreign' => 'name',
        ),
        'repliesIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'repliesNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'teamsIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'teamsNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'usersIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'usersNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'usersColumns' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'assignedUsersIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'assignedUsersNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'inboundEmailsIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'inboundEmailsNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'emailAccountsIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'emailAccountsNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'accountId' => 
        array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
        ),
        'accountName' => 
        array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'account',
        'foreign' => 'name',
        ),
        'bccEmailAddressesIds' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'bccEmailAddressesNames' => 
        array (
        'type' => 'varchar',
        'notStorable' => true,
        ),
        'attachmentsTypes' => 
        array (
        'type' => 'jsonObject',
        'notStorable' => true,
        ),
    ),
    'relations' => array(
        'account' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        ),
        'emailAccounts' => 
        array (
        'type' => 'manyMany',
        'entity' => 'EmailAccount',
        'relationName' => 'emailEmailAccount',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'emailAccountId',
        ),
        ),
        'inboundEmails' => 
        array (
        'type' => 'manyMany',
        'entity' => 'InboundEmail',
        'relationName' => 'emailInboundEmail',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'inboundEmailId',
        ),
        ),
        'bccEmailAddresses' => 
        array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
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
        'ccEmailAddresses' => 
        array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
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
        'toEmailAddresses' => 
        array (
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'emailAddressId',
        ),
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
        'fromEmailAddress' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'EmailAddress',
        'key' => 'fromEmailAddressId',
        'foreignKey' => 'id',
        ),
        'replies' => 
        array (
        'type' => 'hasMany',
        'entity' => 'Email',
        'foreignKey' => 'repliedId',
        ),
        'replied' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'Email',
        'key' => 'repliedId',
        'foreignKey' => 'id',
        ),
        'parent' => 
        array (
        'type' => 'belongsToParent',
        'key' => 'parentId',
        ),
        'sentBy' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'sentById',
        'foreignKey' => 'id',
        ),
        'users' => 
        array (
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'emailUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => 
        array (
          0 => 'emailId',
          1 => 'userId',
        ),
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
        'assignedUsers' => 
        array (
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'entityUser',
        'midKeys' => 
        array (
          0 => 'entityId',
          1 => 'userId',
        ),
        'conditions' => 
        array (
          'entityType' => 'Email',
        ),
        'additionalColumns' => 
        array (
          'entityType' => 
          array (
            'type' => 'varchar',
            'len' => 100,
          ),
        ),
        ),
        'teams' => 
        array (
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => 
        array (
          0 => 'entity_id',
          1 => 'team_id',
        ),
        'conditions' => 
        array (
          'entityType' => 'Email',
        ),
        'additionalColumns' => 
        array (
          'entityType' => 
          array (
            'type' => 'varchar',
            'len' => 100,
          ),
        ),
        ),
        'assignedUser' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        ),
        'modifiedBy' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        ),
        'createdBy' => 
        array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        ),
        'attachments' => 
        array (
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'relationName' => 'attachments',
        ),
    )
);