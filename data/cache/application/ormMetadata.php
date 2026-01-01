<?php
return [
  'ActionHistoryRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'targetType' => [
        'type' => 'foreignType',
        'fieldType' => 'linkParent',
        'len' => 100,
        'notNull' => false,
        'index' => 'target',
        'attributeRole' => 'type',
        'dbType' => 'string'
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'action' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userType' => [
        'type' => 'foreign',
        'notStorable' => true,
        'relation' => 'user',
        'foreign' => 'type',
        'fieldType' => 'foreign',
        'foreignType' => 'varchar'
      ],
      'ipAddress' => [
        'type' => 'varchar',
        'len' => 39,
        'fieldType' => 'varchar'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'authTokenId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'authTokenName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'authToken',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'authLogRecordId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'authLogRecordName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'authLogRecord',
        'foreign' => 'id',
        'foreignType' => 'id'
      ]
    ],
    'relations' => [
      'authLogRecord' => [
        'type' => 'belongsTo',
        'entity' => 'AuthLogRecord',
        'key' => 'authLogRecordId',
        'foreignKey' => 'id',
        'foreign' => 'actionHistoryRecords'
      ],
      'authToken' => [
        'type' => 'belongsTo',
        'entity' => 'AuthToken',
        'key' => 'authTokenId',
        'foreignKey' => 'id',
        'foreign' => 'actionHistoryRecords'
      ],
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetType',
          1 => 'targetId'
        ],
        'key' => 'IDX_TARGET'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'authTokenId' => [
        'type' => 'index',
        'columns' => [
          0 => 'authTokenId'
        ],
        'key' => 'IDX_AUTH_TOKEN_ID'
      ],
      'authLogRecordId' => [
        'type' => 'index',
        'columns' => [
          0 => 'authLogRecordId'
        ],
        'key' => 'IDX_AUTH_LOG_RECORD_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'AddressCountry' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'code' => [
        'type' => 'varchar',
        'len' => 2,
        'fieldType' => 'varchar'
      ],
      'isPreferred' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'preferredName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'name' => [
        'unique' => true,
        'columns' => [
          0 => 'name'
        ],
        'key' => 'UNIQ_NAME'
      ]
    ],
    'collection' => [
      'orderBy' => 'preferredName',
      'order' => 'ASC'
    ]
  ],
  'AppLogRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'message' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'level' => [
        'type' => 'varchar',
        'len' => 9,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'code' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'exceptionClass' => [
        'type' => 'varchar',
        'len' => 512,
        'fieldType' => 'varchar'
      ],
      'file' => [
        'type' => 'varchar',
        'len' => 512,
        'fieldType' => 'varchar'
      ],
      'line' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'requestMethod' => [
        'type' => 'varchar',
        'len' => 7,
        'fieldType' => 'varchar'
      ],
      'requestResourcePath' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'requestUrl' => [
        'type' => 'varchar',
        'len' => 512,
        'fieldType' => 'varchar'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'level' => [
        'type' => 'index',
        'columns' => [
          0 => 'level'
        ],
        'key' => 'IDX_LEVEL'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'AppSecret' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'value' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'deleteId' => [
        'type' => 'varchar',
        'len' => 17,
        'notNull' => true,
        'default' => '0',
        'fieldType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'nameDeleteId' => [
        'type' => 'unique',
        'columns' => [
          0 => 'name',
          1 => 'deleteId'
        ],
        'key' => 'UNIQ_NAME_DELETE_ID'
      ],
      'name' => [
        'type' => 'index',
        'columns' => [
          0 => 'name'
        ],
        'key' => 'IDX_NAME'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'ArrayValue' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'value' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'attribute' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'entityTypeValue' => [
        'columns' => [
          0 => 'entityType',
          1 => 'value'
        ],
        'key' => 'IDX_ENTITY_TYPE_VALUE'
      ],
      'entityValue' => [
        'columns' => [
          0 => 'entityType',
          1 => 'entityId',
          2 => 'value'
        ],
        'key' => 'IDX_ENTITY_VALUE'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ]
    ]
  ],
  'Attachment' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'size' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'fieldType' => 'int',
        'len' => 11
      ],
      'field' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'isBeingUploaded' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'contents' => [
        'type' => 'text',
        'notStorable' => true,
        'fieldType' => 'text'
      ],
      'role' => [
        'type' => 'varchar',
        'len' => 36,
        'fieldType' => 'varchar'
      ],
      'storage' => [
        'type' => 'varchar',
        'len' => 24,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'storageFilePath' => [
        'type' => 'varchar',
        'len' => 260,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'global' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'relatedId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'related',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'relatedType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'related',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'relatedName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'related',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'sourceId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'source',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'sourceName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'related' => [
        'type' => 'belongsToParent',
        'key' => 'relatedId',
        'foreign' => NULL
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'attachments'
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'parent' => [
        'columns' => [
          0 => 'parentType',
          1 => 'parentId'
        ],
        'key' => 'IDX_PARENT'
      ],
      'related' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedId',
          1 => 'relatedType'
        ],
        'key' => 'IDX_RELATED'
      ],
      'source' => [
        'type' => 'index',
        'columns' => [
          0 => 'sourceId'
        ],
        'key' => 'IDX_SOURCE'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'AuthLogRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'username' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'ipAddress' => [
        'type' => 'varchar',
        'len' => 45,
        'fieldType' => 'varchar'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isDenied' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'denialReason' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'requestTime' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'requestUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'requestMethod' => [
        'type' => 'varchar',
        'len' => 15,
        'fieldType' => 'varchar'
      ],
      'authTokenIsActive' => [
        'type' => 'foreign',
        'relation' => 'authToken',
        'foreign' => 'isActive',
        'fieldType' => 'foreign',
        'foreignType' => 'bool'
      ],
      'authenticationMethod' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'portalName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'portal',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'authTokenId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'authTokenName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'authToken',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'actionHistoryRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'actionHistoryRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'actionHistoryRecords' => [
        'type' => 'hasMany',
        'entity' => 'ActionHistoryRecord',
        'foreignKey' => 'authLogRecordId',
        'foreign' => 'authLogRecord'
      ],
      'authToken' => [
        'type' => 'belongsTo',
        'entity' => 'AuthToken',
        'key' => 'authTokenId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'portal' => [
        'type' => 'belongsTo',
        'entity' => 'Portal',
        'key' => 'portalId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'ipAddress' => [
        'columns' => [
          0 => 'ipAddress'
        ],
        'key' => 'IDX_IP_ADDRESS'
      ],
      'ipAddressRequestTime' => [
        'columns' => [
          0 => 'ipAddress',
          1 => 'requestTime'
        ],
        'key' => 'IDX_IP_ADDRESS_REQUEST_TIME'
      ],
      'requestTime' => [
        'columns' => [
          0 => 'requestTime'
        ],
        'key' => 'IDX_REQUEST_TIME'
      ],
      'portalId' => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'key' => 'IDX_PORTAL_ID'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'authTokenId' => [
        'type' => 'index',
        'columns' => [
          0 => 'authTokenId'
        ],
        'key' => 'IDX_AUTH_TOKEN_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'requestTime',
      'order' => 'DESC'
    ]
  ],
  'AuthToken' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'token' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'hash' => [
        'type' => 'varchar',
        'len' => 150,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'secret' => [
        'type' => 'varchar',
        'len' => 36,
        'fieldType' => 'varchar'
      ],
      'ipAddress' => [
        'type' => 'varchar',
        'len' => 45,
        'fieldType' => 'varchar'
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'lastAccess' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'portalName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'portal',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'actionHistoryRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'actionHistoryRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'actionHistoryRecords' => [
        'type' => 'hasMany',
        'entity' => 'ActionHistoryRecord',
        'foreignKey' => 'authTokenId',
        'foreign' => 'authToken'
      ],
      'portal' => [
        'type' => 'belongsTo',
        'entity' => 'Portal',
        'key' => 'portalId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'token' => [
        'columns' => [
          0 => 'token',
          1 => 'deleted'
        ],
        'key' => 'IDX_TOKEN'
      ],
      'hash' => [
        'type' => 'index',
        'columns' => [
          0 => 'hash'
        ],
        'key' => 'IDX_HASH'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'portalId' => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'key' => 'IDX_PORTAL_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'lastAccess',
      'order' => 'DESC'
    ]
  ],
  'AuthenticationProvider' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'method' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcAuthorizationRedirectUri' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcClientId' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcClientSecret' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'oidcAuthorizationEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcUserInfoEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcTokenEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcJwksEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcJwtSignatureAlgorithmList' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'RS256'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'oidcScopes' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'profile',
          1 => 'email',
          2 => 'phone'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'oidcCreateUser' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcUsernameClaim' => [
        'type' => 'varchar',
        'default' => 'sub',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcSync' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcLogoutUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcAuthorizationPrompt' => [
        'type' => 'varchar',
        'len' => 14,
        'fieldType' => 'varchar'
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'Autofollow' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'integer',
        'autoincrement' => true,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'user',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'entityType' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityType'
        ],
        'key' => 'IDX_ENTITY_TYPE'
      ],
      'user' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER'
      ]
    ]
  ],
  'Currency' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'string',
        'len' => 3,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'rate' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'CurrencyRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'code' => [
        'type' => 'varchar',
        'len' => 3,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 8,
        'default' => 'Active',
        'fieldType' => 'varchar'
      ],
      'label' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'symbol' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'rateDate' => [
        'type' => 'date',
        'notNull' => false,
        'notStorable' => true,
        'fieldType' => 'date'
      ],
      'isBase' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'deleteId' => [
        'type' => 'varchar',
        'len' => 17,
        'notNull' => true,
        'default' => '0',
        'fieldType' => 'varchar'
      ],
      'rate' => [
        'type' => 'varchar',
        'dbType' => 'decimal',
        'precision' => 13,
        'scale' => 4
      ],
      'ratesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'ratesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'rates' => [
        'type' => 'hasMany',
        'entity' => 'CurrencyRecordRate',
        'foreignKey' => 'recordId',
        'foreign' => 'record',
        'orderBy' => 'date',
        'order' => 'DESC'
      ]
    ],
    'indexes' => [
      'codeDeleteId' => [
        'type' => 'unique',
        'columns' => [
          0 => 'code',
          1 => 'deleteId'
        ],
        'key' => 'UNIQ_CODE_DELETE_ID'
      ],
      'code' => [
        'type' => 'index',
        'columns' => [
          0 => 'code'
        ],
        'key' => 'IDX_CODE'
      ]
    ],
    'collection' => [
      'orderBy' => 'code',
      'order' => 'ASC'
    ]
  ],
  'CurrencyRecordRate' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'baseCode' => [
        'type' => 'varchar',
        'len' => 3,
        'fieldType' => 'varchar'
      ],
      'date' => [
        'type' => 'date',
        'fieldType' => 'date'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'deleteId' => [
        'type' => 'varchar',
        'len' => 17,
        'notNull' => true,
        'default' => '0',
        'fieldType' => 'varchar'
      ],
      'recordId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'recordName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'record',
        'foreign' => 'code',
        'foreignType' => 'varchar'
      ],
      'rate' => [
        'type' => 'varchar',
        'dbType' => 'decimal',
        'precision' => 15,
        'scale' => 8
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'record' => [
        'type' => 'belongsTo',
        'entity' => 'CurrencyRecord',
        'key' => 'recordId',
        'foreignKey' => 'id',
        'foreign' => NULL
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
        ],
        'key' => 'UNIQ_RECORD_ID_BASE_CODE_DATE'
      ],
      'recordId' => [
        'type' => 'index',
        'columns' => [
          0 => 'recordId'
        ],
        'key' => 'IDX_RECORD_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'date',
      'order' => 'DESC'
    ]
  ],
  'DashboardTemplate' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'layout' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'dashletsOptions' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Email' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'subject' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromString' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToString' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'addressNameMap' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'from' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'to' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'cc' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'bcc' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyTo' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'personStringData' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'isRead' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'isNotRead' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isReplied' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isNotReplied' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isImportant' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'inTrash' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'inArchive' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'folderId' => [
        'len' => 255,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notStorable' => true,
        'default' => NULL,
        'fieldType' => 'link',
        'index' => 'folder',
        'attributeRole' => 'id',
        'notNull' => false
      ],
      'isUsers' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'isUsersSent' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'nameHash' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'typeHash' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'idHash' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'messageId' => [
        'type' => 'varchar',
        'len' => 255,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'messageIdInternal' => [
        'type' => 'varchar',
        'len' => 300,
        'fieldType' => 'varchar'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'bodyPlain' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'body' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'isHtml' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Archived',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'hasAttachment' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'dateSent' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'deliveryDate' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'sendAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isAutoReply' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isSystem' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'isJustSent' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'isBeingImported' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'skipNotificationMap' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'icsContents' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'icsEventData' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'icsEventUid' => [
        'type' => 'varchar',
        'len' => 255,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'icsEventDateStart' => [
        'type' => 'datetime',
        'notNull' => false,
        'notStorable' => true,
        'fieldType' => 'datetime'
      ],
      'createEvent' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'groupStatusFolder' => [
        'type' => 'varchar',
        'len' => 7,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'icsEventDateStartDate' => [
        'type' => 'date',
        'notNull' => false,
        'notStorable' => true,
        'fieldType' => 'date'
      ],
      'folderName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'folderStringId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'folderString',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'folderStringName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'fromEmailAddressId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'fromEmailAddressName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'fromEmailAddress',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'toEmailAddressesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'toEmailAddresses',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'toEmailAddressesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'ccEmailAddressesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'ccEmailAddresses',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'ccEmailAddressesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'bccEmailAddressesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'bccEmailAddresses',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'bccEmailAddressesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'replyToEmailAddressesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'replyToEmailAddresses',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'replyToEmailAddressesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'sentById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'sentByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'sentBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'repliedId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'repliedName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'replied',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'repliesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'replies',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'dateSent',
        'isLinkStub' => false
      ],
      'repliesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'repliesColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'status'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'users',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'inTrash' => 'inTrash',
          'folderId' => 'folderId',
          'inArchive' => 'inArchive',
          'isRead' => 'isRead'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'assignedUsersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'assignedUsers',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'assignedUsersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'inboundEmailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'inboundEmails',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'inboundEmailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'emailAccountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'emailAccounts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'emailAccountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdEventId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'createdEvent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'createdEventType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'createdEvent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'createdEventName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'createdEvent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'groupFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'groupFolderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'groupFolder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'tasks',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'tasksColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'status'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'tasks' => [
        'type' => 'hasMany',
        'entity' => 'Task',
        'foreignKey' => 'emailId',
        'foreign' => 'email'
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'groupFolder' => [
        'type' => 'belongsTo',
        'entity' => 'GroupEmailFolder',
        'key' => 'groupFolderId',
        'foreignKey' => 'id',
        'foreign' => 'emails'
      ],
      'createdEvent' => [
        'type' => 'belongsToParent',
        'key' => 'createdEventId',
        'foreign' => NULL
      ],
      'emailAccounts' => [
        'type' => 'manyMany',
        'entity' => 'EmailAccount',
        'relationName' => 'emailEmailAccount',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'emailAccountId'
        ],
        'foreign' => 'emails',
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAccountId' => [
            'columns' => [
              0 => 'emailAccountId'
            ],
            'key' => 'IDX_EMAIL_ACCOUNT_ID'
          ],
          'emailId_emailAccountId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'emailAccountId'
            ],
            'key' => 'UNIQ_EMAIL_ID_EMAIL_ACCOUNT_ID'
          ]
        ]
      ],
      'inboundEmails' => [
        'type' => 'manyMany',
        'entity' => 'InboundEmail',
        'relationName' => 'emailInboundEmail',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'inboundEmailId'
        ],
        'foreign' => 'emails',
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'inboundEmailId' => [
            'columns' => [
              0 => 'inboundEmailId'
            ],
            'key' => 'IDX_INBOUND_EMAIL_ID'
          ],
          'emailId_inboundEmailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'inboundEmailId'
            ],
            'key' => 'UNIQ_EMAIL_ID_INBOUND_EMAIL_ID'
          ]
        ]
      ],
      'replyToEmailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'emailAddressId'
        ],
        'relationName' => 'emailEmailAddress',
        'conditions' => [
          'addressType' => 'rto'
        ],
        'additionalColumns' => [
          'addressType' => [
            'type' => 'varchar',
            'len' => '4'
          ]
        ],
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'emailId_emailAddressId_addressType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'emailAddressId',
              2 => 'addressType'
            ],
            'key' => 'UNIQ_EMAIL_ID_EMAIL_ADDRESS_ID_ADDRESS_TYPE'
          ]
        ]
      ],
      'bccEmailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'emailAddressId'
        ],
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
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'emailId_emailAddressId_addressType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'emailAddressId',
              2 => 'addressType'
            ],
            'key' => 'UNIQ_EMAIL_ID_EMAIL_ADDRESS_ID_ADDRESS_TYPE'
          ]
        ]
      ],
      'ccEmailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'emailAddressId'
        ],
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
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'emailId_emailAddressId_addressType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'emailAddressId',
              2 => 'addressType'
            ],
            'key' => 'UNIQ_EMAIL_ID_EMAIL_ADDRESS_ID_ADDRESS_TYPE'
          ]
        ]
      ],
      'toEmailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'emailAddressId'
        ],
        'relationName' => 'emailEmailAddress',
        'conditions' => [
          'addressType' => 'to'
        ],
        'additionalColumns' => [
          'addressType' => [
            'type' => 'varchar',
            'len' => '4'
          ]
        ],
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'emailId_emailAddressId_addressType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'emailAddressId',
              2 => 'addressType'
            ],
            'key' => 'UNIQ_EMAIL_ID_EMAIL_ADDRESS_ID_ADDRESS_TYPE'
          ]
        ]
      ],
      'fromEmailAddress' => [
        'type' => 'belongsTo',
        'entity' => 'EmailAddress',
        'key' => 'fromEmailAddressId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'replies' => [
        'type' => 'hasMany',
        'entity' => 'Email',
        'foreignKey' => 'repliedId',
        'foreign' => 'replied'
      ],
      'replied' => [
        'type' => 'belongsTo',
        'entity' => 'Email',
        'key' => 'repliedId',
        'foreignKey' => 'id',
        'foreign' => 'replies'
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'emails'
      ],
      'sentBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'sentById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'emailUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailId',
          1 => 'userId'
        ],
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
        ],
        'indexes' => [
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'emailId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_EMAIL_ID_USER_ID'
          ]
        ]
      ],
      'assignedUsers' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'entityUser',
        'midKeys' => [
          0 => 'entityId',
          1 => 'userId'
        ],
        'conditions' => [
          'entityType' => 'Email'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'entityId_userId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'userId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_USER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Email'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'indexes' => [
      'createdById' => [
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'dateSent' => [
        'columns' => [
          0 => 'dateSent',
          1 => 'deleted'
        ],
        'key' => 'IDX_DATE_SENT'
      ],
      'dateSentStatus' => [
        'columns' => [
          0 => 'dateSent',
          1 => 'status',
          2 => 'deleted'
        ],
        'key' => 'IDX_DATE_SENT_STATUS'
      ],
      'system_fullTextSearch' => [
        'columns' => [
          0 => 'name',
          1 => 'bodyPlain',
          2 => 'body'
        ],
        'flags' => [
          0 => 'fulltext'
        ],
        'key' => 'IDX_SYSTEM_FULL_TEXT_SEARCH'
      ],
      'messageId' => [
        'type' => 'index',
        'columns' => [
          0 => 'messageId'
        ],
        'key' => 'IDX_MESSAGE_ID'
      ],
      'icsEventUid' => [
        'type' => 'index',
        'columns' => [
          0 => 'icsEventUid'
        ],
        'key' => 'IDX_ICS_EVENT_UID'
      ],
      'groupStatusFolder' => [
        'type' => 'index',
        'columns' => [
          0 => 'groupStatusFolder'
        ],
        'key' => 'IDX_GROUP_STATUS_FOLDER'
      ],
      'fromEmailAddressId' => [
        'type' => 'index',
        'columns' => [
          0 => 'fromEmailAddressId'
        ],
        'key' => 'IDX_FROM_EMAIL_ADDRESS_ID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'sentById' => [
        'type' => 'index',
        'columns' => [
          0 => 'sentById'
        ],
        'key' => 'IDX_SENT_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'repliedId' => [
        'type' => 'index',
        'columns' => [
          0 => 'repliedId'
        ],
        'key' => 'IDX_REPLIED_ID'
      ],
      'createdEvent' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdEventId',
          1 => 'createdEventType'
        ],
        'key' => 'IDX_CREATED_EVENT'
      ],
      'groupFolderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'groupFolderId'
        ],
        'key' => 'IDX_GROUP_FOLDER_ID'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ]
    ],
    'fullTextSearchColumnList' => [
      0 => 'name',
      1 => 'bodyPlain',
      2 => 'body'
    ],
    'collection' => [
      'orderBy' => 'dateSent',
      'order' => 'DESC'
    ]
  ],
  'EmailAccount' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Active',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'host' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'port' => [
        'type' => 'int',
        'default' => 993,
        'fieldType' => 'int',
        'len' => 11
      ],
      'security' => [
        'type' => 'varchar',
        'default' => 'SSL',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'username' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'password' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'monitoredFolders' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'INBOX'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'sentFolder' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'folderMap' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'storeSentEmails' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'keepFetchedEmailsUnread' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'fetchSince' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'fetchData' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'connectedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'useImap' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'useSmtp' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'smtpHost' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPort' => [
        'type' => 'int',
        'default' => 587,
        'fieldType' => 'int',
        'len' => 11
      ],
      'smtpAuth' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'smtpSecurity' => [
        'type' => 'varchar',
        'default' => 'TLS',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpUsername' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPassword' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'smtpAuthMechanism' => [
        'type' => 'varchar',
        'default' => 'login',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'imapHandler' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpHandler' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'emailFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'emailFolderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'emailFolder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'filtersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'filtersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailFolder' => [
        'type' => 'belongsTo',
        'entity' => 'EmailFolder',
        'key' => 'emailFolderId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'emails' => [
        'type' => 'manyMany',
        'entity' => 'Email',
        'relationName' => 'emailEmailAccount',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'emailAccountId',
          1 => 'emailId'
        ],
        'foreign' => 'emailAccounts',
        'indexes' => [
          'emailAccountId' => [
            'columns' => [
              0 => 'emailAccountId'
            ],
            'key' => 'IDX_EMAIL_ACCOUNT_ID'
          ],
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'emailAccountId_emailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'emailAccountId',
              1 => 'emailId'
            ],
            'key' => 'UNIQ_EMAIL_ACCOUNT_ID_EMAIL_ID'
          ]
        ]
      ],
      'filters' => [
        'type' => 'hasChildren',
        'entity' => 'EmailFilter',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'emailFolderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'emailFolderId'
        ],
        'key' => 'IDX_EMAIL_FOLDER_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'EmailAddress' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'lower' => [
        'type' => 'varchar',
        'index' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'invalid' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'optOut' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'primary' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ]
    ],
    'relations' => [],
    'indexes' => [
      'lower' => [
        'type' => 'index',
        'columns' => [
          0 => 'lower'
        ],
        'key' => 'IDX_LOWER'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'EmailFilter' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'from' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'to' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'subject' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'bodyContains' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'bodyContainsAll' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'isGlobal' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'action' => [
        'type' => 'varchar',
        'default' => 'Skip',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'markAsRead' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'skipNotification' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'emailFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'emailFolderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'emailFolder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'groupEmailFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'groupEmailFolderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'groupEmailFolder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'groupEmailFolder' => [
        'type' => 'belongsTo',
        'entity' => 'GroupEmailFolder',
        'key' => 'groupEmailFolderId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'emailFolder' => [
        'type' => 'belongsTo',
        'entity' => 'EmailFolder',
        'key' => 'emailFolderId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'emailFolderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'emailFolderId'
        ],
        'key' => 'IDX_EMAIL_FOLDER_ID'
      ],
      'groupEmailFolderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'groupEmailFolderId'
        ],
        'key' => 'IDX_GROUP_EMAIL_FOLDER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'EmailFolder' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 64,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'skipNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'order',
      'order' => 'ASC'
    ]
  ],
  'EmailTemplate' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'subject' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'body' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'isHtml' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 8,
        'default' => 'Active',
        'fieldType' => 'varchar'
      ],
      'oneOff' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'categoryId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'categoryName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'category',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'isStarred' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'readOnly' => true,
        'default' => false
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'EmailTemplate'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'category' => [
        'type' => 'belongsTo',
        'entity' => 'EmailTemplateCategory',
        'key' => 'categoryId',
        'foreignKey' => 'id',
        'foreign' => 'emailTemplates'
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'indexes' => [
      'categoryId' => [
        'type' => 'index',
        'columns' => [
          0 => 'categoryId'
        ],
        'key' => 'IDX_CATEGORY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'EmailTemplateCategory' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'childList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'parentName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'parent',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'emailTemplatesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailTemplatesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailTemplates' => [
        'type' => 'hasMany',
        'entity' => 'EmailTemplate',
        'foreignKey' => 'categoryId',
        'foreign' => 'category'
      ],
      'children' => [
        'type' => 'hasMany',
        'entity' => 'EmailTemplateCategory',
        'foreignKey' => 'parentId',
        'foreign' => 'parent'
      ],
      'parent' => [
        'type' => 'belongsTo',
        'entity' => 'EmailTemplateCategory',
        'key' => 'parentId',
        'foreignKey' => 'id',
        'foreign' => 'children'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'EmailTemplateCategory'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'parentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId'
        ],
        'key' => 'IDX_PARENT_ID'
      ]
    ],
    'collection' => [
      'order' => 'ASC'
    ]
  ],
  'Export' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Pending',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'params' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'notifyOnFinish' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'attachmentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'attachment',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'attachmentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'attachment' => [
        'type' => 'index',
        'columns' => [
          0 => 'attachmentId'
        ],
        'key' => 'IDX_ATTACHMENT'
      ]
    ]
  ],
  'Extension' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'version' => [
        'type' => 'varchar',
        'len' => 50,
        'fieldType' => 'varchar'
      ],
      'fileList' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'licenseStatus' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'licenseStatusMessage' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'isInstalled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'checkVersionUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'licenseStatus' => [
        'type' => 'index',
        'columns' => [
          0 => 'licenseStatus'
        ],
        'key' => 'IDX_LICENSE_STATUS'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'ExternalAccount' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'string',
        'len' => 64,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'enabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isLocked' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'GroupEmailFolder' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 64,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emails' => [
        'type' => 'hasMany',
        'entity' => 'Email',
        'foreignKey' => 'groupFolderId',
        'foreign' => 'groupFolder'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'groupEmailFolderTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'groupEmailFolderId',
          1 => 'teamId'
        ],
        'foreign' => 'groupEmailFolders',
        'indexes' => [
          'groupEmailFolderId' => [
            'columns' => [
              0 => 'groupEmailFolderId'
            ],
            'key' => 'IDX_GROUP_EMAIL_FOLDER_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'groupEmailFolderId_teamId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'groupEmailFolderId',
              1 => 'teamId'
            ],
            'key' => 'UNIQ_GROUP_EMAIL_FOLDER_ID_TEAM_ID'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'order',
      'order' => 'ASC'
    ]
  ],
  'Import' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'status' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'importedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'duplicateCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'updatedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'lastIndex' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'params' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'attributeList' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'fileId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'fileName' => [
        'type' => 'foreign',
        'relation' => 'file',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'errorsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'errorsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'file' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'fileId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'errors' => [
        'type' => 'hasMany',
        'entity' => 'ImportError',
        'foreignKey' => 'importId',
        'foreign' => 'import'
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'ImportEml' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'fileId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'fileName' => [
        'type' => 'foreign',
        'relation' => 'file',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'file' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'fileId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => []
  ],
  'ImportEntity' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'isImported' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isUpdated' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isDuplicate' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'importId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'import',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'importName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'entityImport' => [
        'columns' => [
          0 => 'importId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY_IMPORT'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ],
      'import' => [
        'type' => 'index',
        'columns' => [
          0 => 'importId'
        ],
        'key' => 'IDX_IMPORT'
      ]
    ]
  ],
  'ImportError' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityType' => [
        'type' => 'foreign',
        'relation' => 'import',
        'foreign' => 'entityType',
        'fieldType' => 'foreign',
        'foreignType' => 'varchar'
      ],
      'rowIndex' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'exportRowIndex' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'lineNumber' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'exportLineNumber' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'validationFailures' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'row' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'importId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'importName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'import',
        'foreign' => 'id',
        'foreignType' => 'id'
      ]
    ],
    'relations' => [
      'import' => [
        'type' => 'belongsTo',
        'entity' => 'Import',
        'key' => 'importId',
        'foreignKey' => 'id',
        'foreign' => 'errors'
      ]
    ],
    'indexes' => [
      'rowIndex' => [
        'columns' => [
          0 => 'rowIndex'
        ],
        'key' => 'IDX_ROW_INDEX'
      ],
      'importRowIndex' => [
        'columns' => [
          0 => 'importId',
          1 => 'rowIndex'
        ],
        'key' => 'IDX_IMPORT_ROW_INDEX'
      ],
      'importId' => [
        'type' => 'index',
        'columns' => [
          0 => 'importId'
        ],
        'key' => 'IDX_IMPORT_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'rowIndex',
      'order' => 'ASC'
    ]
  ],
  'InboundEmail' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Active',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'host' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'port' => [
        'type' => 'int',
        'default' => 993,
        'fieldType' => 'int',
        'len' => 11
      ],
      'security' => [
        'type' => 'varchar',
        'default' => 'SSL',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'username' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'password' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'monitoredFolders' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'INBOX'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'fetchSince' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'fetchData' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'addAllTeamUsers' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'isSystem' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'sentFolder' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'storeSentEmails' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'keepFetchedEmailsUnread' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'connectedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'excludeFromReply' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'useImap' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'useSmtp' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'smtpIsShared' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'smtpIsForMassEmail' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'smtpHost' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPort' => [
        'type' => 'int',
        'default' => 587,
        'fieldType' => 'int',
        'len' => 11
      ],
      'smtpAuth' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'smtpSecurity' => [
        'type' => 'varchar',
        'default' => 'TLS',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpUsername' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPassword' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'smtpAuthMechanism' => [
        'type' => 'varchar',
        'default' => 'login',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createCase' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'caseDistribution' => [
        'type' => 'varchar',
        'default' => 'Direct-Assignment',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'targetUserPosition' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'reply' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'replyFromAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyFromName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'imapHandler' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpHandler' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'assignToUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignToUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignToUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'teamName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'team',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'replyEmailTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'replyEmailTemplateName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'replyEmailTemplate',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'groupEmailFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'groupEmailFolderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'groupEmailFolder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'filtersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'filtersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'groupEmailFolder' => [
        'type' => 'belongsTo',
        'entity' => 'GroupEmailFolder',
        'key' => 'groupEmailFolderId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'emails' => [
        'type' => 'manyMany',
        'entity' => 'Email',
        'relationName' => 'emailInboundEmail',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'inboundEmailId',
          1 => 'emailId'
        ],
        'foreign' => 'inboundEmails',
        'indexes' => [
          'inboundEmailId' => [
            'columns' => [
              0 => 'inboundEmailId'
            ],
            'key' => 'IDX_INBOUND_EMAIL_ID'
          ],
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'inboundEmailId_emailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'inboundEmailId',
              1 => 'emailId'
            ],
            'key' => 'UNIQ_INBOUND_EMAIL_ID_EMAIL_ID'
          ]
        ]
      ],
      'filters' => [
        'type' => 'hasChildren',
        'entity' => 'EmailFilter',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'replyEmailTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'EmailTemplate',
        'key' => 'replyEmailTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'team' => [
        'type' => 'belongsTo',
        'entity' => 'Team',
        'key' => 'teamId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'assignToUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignToUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'inboundEmailTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'inboundEmailId',
          1 => 'teamId'
        ],
        'foreign' => 'inboundEmails',
        'indexes' => [
          'inboundEmailId' => [
            'columns' => [
              0 => 'inboundEmailId'
            ],
            'key' => 'IDX_INBOUND_EMAIL_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'inboundEmailId_teamId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'inboundEmailId',
              1 => 'teamId'
            ],
            'key' => 'UNIQ_INBOUND_EMAIL_ID_TEAM_ID'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'assignToUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignToUserId'
        ],
        'key' => 'IDX_ASSIGN_TO_USER_ID'
      ],
      'teamId' => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'key' => 'IDX_TEAM_ID'
      ],
      'replyEmailTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'replyEmailTemplateId'
        ],
        'key' => 'IDX_REPLY_EMAIL_TEMPLATE_ID'
      ],
      'groupEmailFolderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'groupEmailFolderId'
        ],
        'key' => 'IDX_GROUP_EMAIL_FOLDER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Integration' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'string',
        'len' => 24,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'enabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'Job' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 16,
        'default' => 'Pending',
        'fieldType' => 'varchar'
      ],
      'executeTime' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'className' => [
        'type' => 'varchar',
        'len' => 255,
        'fieldType' => 'varchar'
      ],
      'serviceName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'methodName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'job' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'scheduledJobJob' => [
        'type' => 'foreign',
        'relation' => 'scheduledJob',
        'foreign' => 'job',
        'fieldType' => 'foreign',
        'foreignType' => 'varchar'
      ],
      'queue' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'group' => [
        'type' => 'varchar',
        'len' => 128,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'targetGroup' => [
        'type' => 'varchar',
        'len' => 128,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'startedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'executedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'pid' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'attempts' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'targetId' => [
        'type' => 'varchar',
        'len' => 48,
        'fieldType' => 'varchar'
      ],
      'targetType' => [
        'type' => 'varchar',
        'len' => 64,
        'fieldType' => 'varchar'
      ],
      'failedAttempts' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'scheduledJobId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'scheduledJobName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'scheduledJob',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'scheduledJob' => [
        'type' => 'belongsTo',
        'entity' => 'ScheduledJob',
        'key' => 'scheduledJobId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'executeTime' => [
        'columns' => [
          0 => 'status',
          1 => 'executeTime'
        ],
        'key' => 'IDX_EXECUTE_TIME'
      ],
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'statusScheduledJobId' => [
        'columns' => [
          0 => 'status',
          1 => 'scheduledJobId'
        ],
        'key' => 'IDX_STATUS_SCHEDULED_JOB_ID'
      ],
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'scheduledJobId' => [
        'type' => 'index',
        'columns' => [
          0 => 'scheduledJobId'
        ],
        'key' => 'IDX_SCHEDULED_JOB_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'KanbanOrder' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'order' => [
        'type' => 'int',
        'dbType' => 'smallint',
        'fieldType' => 'int',
        'len' => 11
      ],
      'group' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'user',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [
      'entity' => [
        'type' => 'belongsToParent',
        'key' => 'entityId',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'entityUserId' => [
        'columns' => [
          0 => 'entityType',
          1 => 'entityId',
          2 => 'userId'
        ],
        'key' => 'IDX_ENTITY_USER_ID'
      ],
      'entityType' => [
        'columns' => [
          0 => 'entityType'
        ],
        'key' => 'IDX_ENTITY_TYPE'
      ],
      'entityTypeUserId' => [
        'columns' => [
          0 => 'entityType',
          1 => 'userId'
        ],
        'key' => 'IDX_ENTITY_TYPE_USER_ID'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ],
      'user' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER'
      ]
    ]
  ],
  'LayoutRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'data' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'layoutSetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'layoutSetName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'layoutSet',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'layoutSet' => [
        'type' => 'belongsTo',
        'entity' => 'LayoutSet',
        'key' => 'layoutSetId',
        'foreignKey' => 'id',
        'foreign' => 'layoutRecords'
      ]
    ],
    'indexes' => [
      'nameLayoutSetId' => [
        'columns' => [
          0 => 'name',
          1 => 'layoutSetId'
        ],
        'key' => 'IDX_NAME_LAYOUT_SET_ID'
      ],
      'layoutSetId' => [
        'type' => 'index',
        'columns' => [
          0 => 'layoutSetId'
        ],
        'key' => 'IDX_LAYOUT_SET_ID'
      ]
    ]
  ],
  'LayoutSet' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'layoutList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'portalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'portalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'layoutRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'layoutRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'portals' => [
        'type' => 'hasMany',
        'entity' => 'Portal',
        'foreignKey' => 'layoutSetId',
        'foreign' => 'layoutSet'
      ],
      'teams' => [
        'type' => 'hasMany',
        'entity' => 'Team',
        'foreignKey' => 'layoutSetId',
        'foreign' => 'layoutSet'
      ],
      'layoutRecords' => [
        'type' => 'hasMany',
        'entity' => 'LayoutRecord',
        'foreignKey' => 'layoutSetId',
        'foreign' => 'layoutSet'
      ]
    ],
    'indexes' => [],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'LeadCapture' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'subscribeToTargetList' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'subscribeContactToTargetList' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'fieldList' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'firstName',
          1 => 'lastName',
          2 => 'emailAddress'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'fieldParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'duplicateCheck' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'optInConfirmation' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'optInConfirmationLifetime' => [
        'type' => 'int',
        'default' => 48,
        'fieldType' => 'int',
        'len' => 11
      ],
      'optInConfirmationSuccessMessage' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createLeadBeforeOptInConfirmation' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'skipOptInConfirmationIfSubscribed' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'leadSource' => [
        'type' => 'varchar',
        'default' => 'Web Site',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'apiKey' => [
        'type' => 'varchar',
        'len' => 36,
        'fieldType' => 'varchar'
      ],
      'formId' => [
        'type' => 'varchar',
        'len' => 17,
        'fieldType' => 'varchar'
      ],
      'formEnabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'formTitle' => [
        'type' => 'varchar',
        'len' => 80,
        'fieldType' => 'varchar'
      ],
      'formTheme' => [
        'type' => 'varchar',
        'len' => 64,
        'fieldType' => 'varchar'
      ],
      'formText' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'formSuccessText' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'formSuccessRedirectUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'formLanguage' => [
        'type' => 'varchar',
        'len' => 5,
        'fieldType' => 'varchar'
      ],
      'formFrameAncestors' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'formCaptcha' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'exampleRequestUrl' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'exampleRequestMethod' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'exampleRequestPayload' => [
        'type' => 'text',
        'notStorable' => true,
        'fieldType' => 'text'
      ],
      'exampleRequestHeaders' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'formUrl' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpAccount' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'phoneNumberCountry' => [
        'type' => 'varchar',
        'len' => 2,
        'fieldType' => 'varchar'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'targetListName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'targetList',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'optInConfirmationEmailTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'optInConfirmationEmailTemplateName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'optInConfirmationEmailTemplate',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetTeamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'targetTeamName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'targetTeam',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'inboundEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'inboundEmailName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'inboundEmail',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'logRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'logRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'logRecords' => [
        'type' => 'hasMany',
        'entity' => 'LeadCaptureLogRecord',
        'foreignKey' => 'leadCaptureId',
        'foreign' => 'leadCapture'
      ],
      'optInConfirmationEmailTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'EmailTemplate',
        'key' => 'optInConfirmationEmailTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'inboundEmail' => [
        'type' => 'belongsTo',
        'entity' => 'InboundEmail',
        'key' => 'inboundEmailId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'targetTeam' => [
        'type' => 'belongsTo',
        'entity' => 'Team',
        'key' => 'targetTeamId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'targetList' => [
        'type' => 'belongsTo',
        'entity' => 'TargetList',
        'key' => 'targetListId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'targetListId' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      'optInConfirmationEmailTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'optInConfirmationEmailTemplateId'
        ],
        'key' => 'IDX_OPT_IN_CONFIRMATION_EMAIL_TEMPLATE_ID'
      ],
      'targetTeamId' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetTeamId'
        ],
        'key' => 'IDX_TARGET_TEAM_ID'
      ],
      'inboundEmailId' => [
        'type' => 'index',
        'columns' => [
          0 => 'inboundEmailId'
        ],
        'key' => 'IDX_INBOUND_EMAIL_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'LeadCaptureLogRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'isCreated' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'leadCaptureId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'leadCaptureName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'leadCapture',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ],
      'leadCapture' => [
        'type' => 'belongsTo',
        'entity' => 'LeadCapture',
        'key' => 'leadCaptureId',
        'foreignKey' => 'id',
        'foreign' => 'logRecords'
      ]
    ],
    'indexes' => [
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'leadCaptureId' => [
        'type' => 'index',
        'columns' => [
          0 => 'leadCaptureId'
        ],
        'key' => 'IDX_LEAD_CAPTURE_ID'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'MassAction' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'action' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Pending',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'params' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'processedCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'notifyOnFinish' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ]
  ],
  'NextNumber' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'fieldName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'value' => [
        'type' => 'int',
        'default' => 1,
        'fieldType' => 'int',
        'len' => 11
      ]
    ],
    'relations' => [],
    'indexes' => [
      'entityTypeFieldName' => [
        'columns' => [
          0 => 'entityType',
          1 => 'fieldName'
        ],
        'key' => 'IDX_ENTITY_TYPE_FIELD_NAME'
      ],
      'entityType' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityType'
        ],
        'key' => 'IDX_ENTITY_TYPE'
      ]
    ]
  ],
  'Note' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'post' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 24,
        'default' => 'Post',
        'fieldType' => 'varchar'
      ],
      'targetType' => [
        'type' => 'varchar',
        'len' => 7,
        'fieldType' => 'varchar'
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'isGlobal' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdByGender' => [
        'type' => 'foreign',
        'relation' => 'createdBy',
        'foreign' => 'gender',
        'fieldType' => 'foreign',
        'foreignType' => 'varchar'
      ],
      'notifiedUserIdList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'isInternal' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'isPinned' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'reactionCounts' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'myReactions' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'relatedId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'related',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'relatedType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'related',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'relatedName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'related',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'portals',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'users',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'superParentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'superParent',
        'notNull' => false
      ],
      'superParentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'superParent',
        'len' => 100,
        'dbType' => 'string'
      ],
      'superParentName' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'noteUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'noteId',
          1 => 'userId'
        ],
        'foreign' => 'notes',
        'indexes' => [
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'noteId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'noteId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_NOTE_ID_USER_ID'
          ]
        ]
      ],
      'portals' => [
        'type' => 'manyMany',
        'entity' => 'Portal',
        'relationName' => 'notePortal',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'noteId',
          1 => 'portalId'
        ],
        'foreign' => 'notes',
        'indexes' => [
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'noteId_portalId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'noteId',
              1 => 'portalId'
            ],
            'key' => 'UNIQ_NOTE_ID_PORTAL_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'noteTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'noteId',
          1 => 'teamId'
        ],
        'foreign' => 'notes',
        'indexes' => [
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'noteId_teamId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'noteId',
              1 => 'teamId'
            ],
            'key' => 'UNIQ_NOTE_ID_TEAM_ID'
          ]
        ]
      ],
      'related' => [
        'type' => 'belongsToParent',
        'key' => 'relatedId',
        'foreign' => NULL
      ],
      'superParent' => [
        'type' => 'belongsToParent',
        'key' => 'superParentId',
        'foreign' => NULL
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'notes'
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdAt'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdByNumber' => [
        'columns' => [
          0 => 'createdById',
          1 => 'number'
        ],
        'key' => 'IDX_CREATED_BY_NUMBER'
      ],
      'type' => [
        'type' => 'index',
        'columns' => [
          0 => 'type'
        ],
        'key' => 'IDX_TYPE'
      ],
      'targetType' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetType'
        ],
        'key' => 'IDX_TARGET_TYPE'
      ],
      'parentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId'
        ],
        'key' => 'IDX_PARENT_ID'
      ],
      'parentType' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentType'
        ],
        'key' => 'IDX_PARENT_TYPE'
      ],
      'relatedId' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedId'
        ],
        'key' => 'IDX_RELATED_ID'
      ],
      'relatedType' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedType'
        ],
        'key' => 'IDX_RELATED_TYPE'
      ],
      'superParentType' => [
        'type' => 'index',
        'columns' => [
          0 => 'superParentType'
        ],
        'key' => 'IDX_SUPER_PARENT_TYPE'
      ],
      'superParentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'superParentId'
        ],
        'key' => 'IDX_SUPER_PARENT_ID'
      ],
      'system_fullTextSearch' => [
        'columns' => [
          0 => 'post'
        ],
        'flags' => [
          0 => 'fulltext'
        ],
        'key' => 'IDX_SYSTEM_FULL_TEXT_SEARCH'
      ],
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'related' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedId',
          1 => 'relatedType'
        ],
        'key' => 'IDX_RELATED'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'superParent' => [
        'type' => 'index',
        'columns' => [
          0 => 'superParentId',
          1 => 'superParentType'
        ],
        'key' => 'IDX_SUPER_PARENT'
      ]
    ],
    'fullTextSearchColumnList' => [
      0 => 'post'
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'Notification' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'noteData' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'read' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'emailIsProcessed' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'message' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'actionId' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'groupedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'relatedId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'related',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'relatedType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'related',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'relatedName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'related',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'relatedParentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'relatedParent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'relatedParentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'relatedParent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'relatedParentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'relatedParent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name'
      ]
    ],
    'relations' => [
      'relatedParent' => [
        'type' => 'belongsToParent',
        'key' => 'relatedParentId',
        'foreign' => NULL
      ],
      'related' => [
        'type' => 'belongsToParent',
        'key' => 'relatedId',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdAt'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'user' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId',
          1 => 'number'
        ],
        'key' => 'IDX_USER'
      ],
      'userIdReadRelatedParentType' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId',
          1 => 'deleted',
          2 => 'read',
          3 => 'relatedParentType'
        ],
        'key' => 'IDX_USER_ID_READ_RELATED_PARENT_TYPE'
      ],
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'actionId' => [
        'type' => 'index',
        'columns' => [
          0 => 'actionId'
        ],
        'key' => 'IDX_ACTION_ID'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'related' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedId',
          1 => 'relatedType'
        ],
        'key' => 'IDX_RELATED'
      ],
      'relatedParent' => [
        'type' => 'index',
        'columns' => [
          0 => 'relatedParentId',
          1 => 'relatedParentType'
        ],
        'key' => 'IDX_RELATED_PARENT'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'OAuthAccount' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'hasAccessToken' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'select' => [
          'select' => 'IS_NOT_NULL:(accessToken)'
        ],
        'fieldType' => 'bool',
        'default' => false
      ],
      'providerIsActive' => [
        'type' => 'foreign',
        'relation' => 'provider',
        'foreign' => 'isActive',
        'fieldType' => 'foreign',
        'foreignType' => 'bool'
      ],
      'data' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'accessToken' => [
        'type' => 'password',
        'dbType' => 'text',
        'fieldType' => 'password'
      ],
      'refreshToken' => [
        'type' => 'password',
        'dbType' => 'text',
        'fieldType' => 'password'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'expiresAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'providerId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'providerName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'provider',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'provider' => [
        'type' => 'belongsTo',
        'entity' => 'OAuthProvider',
        'key' => 'providerId',
        'foreignKey' => 'id',
        'foreign' => 'accounts'
      ]
    ],
    'indexes' => [
      'providerId' => [
        'type' => 'index',
        'columns' => [
          0 => 'providerId'
        ],
        'key' => 'IDX_PROVIDER_ID'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ]
  ],
  'OAuthProvider' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'clientId' => [
        'type' => 'varchar',
        'len' => 150,
        'fieldType' => 'varchar'
      ],
      'clientSecret' => [
        'type' => 'password',
        'dbType' => 'text',
        'len' => 512,
        'fieldType' => 'password'
      ],
      'authorizationEndpoint' => [
        'type' => 'varchar',
        'dbType' => 'text',
        'len' => 512,
        'fieldType' => 'varchar'
      ],
      'tokenEndpoint' => [
        'type' => 'varchar',
        'dbType' => 'text',
        'len' => 512,
        'fieldType' => 'varchar'
      ],
      'authorizationRedirectUri' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'authorizationPrompt' => [
        'type' => 'varchar',
        'len' => 14,
        'default' => 'none',
        'fieldType' => 'varchar'
      ],
      'scopes' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'authorizationParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'scopeSeparator' => [
        'type' => 'varchar',
        'len' => 1,
        'fieldType' => 'varchar'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'accounts' => [
        'type' => 'hasMany',
        'entity' => 'OAuthAccount',
        'foreignKey' => 'providerId',
        'foreign' => 'provider'
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ]
  ],
  'PasswordChangeRequest' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'requestId' => [
        'type' => 'varchar',
        'len' => 64,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'url' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'requestId' => [
        'type' => 'index',
        'columns' => [
          0 => 'requestId'
        ],
        'key' => 'IDX_REQUEST_ID'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ]
    ]
  ],
  'PhoneNumber' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'numeric' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'invalid' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'optOut' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'primary' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ]
    ],
    'relations' => [],
    'indexes' => [
      'name' => [
        'type' => 'index',
        'columns' => [
          0 => 'name'
        ],
        'key' => 'IDX_NAME'
      ],
      'numeric' => [
        'type' => 'index',
        'columns' => [
          0 => 'numeric'
        ],
        'key' => 'IDX_NUMERIC'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Portal' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'url' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'customId' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'isDefault' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'tabList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'quickCreateList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'applicationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'theme' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'themeParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'language' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'timeZone' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'timeFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'weekStart' => [
        'type' => 'int',
        'default' => -1,
        'fieldType' => 'int',
        'len' => 11
      ],
      'defaultCurrency' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dashboardLayout' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'dashletsOptions' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'customUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'authTokenLifetime' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'authTokenMaxIdleTime' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'logoId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'logoName' => [
        'type' => 'foreign',
        'relation' => 'logo',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'portalRolesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'portalRoles',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalRolesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'companyLogoId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'companyLogoName' => [
        'type' => 'foreign',
        'relation' => 'companyLogo',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'layoutSetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'layoutSetName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'layoutSet',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'authenticationProviderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'authenticationProviderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'authenticationProvider',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'articlesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'articlesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'logo' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'logoId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'companyLogo' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'companyLogoId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'articles' => [
        'type' => 'manyMany',
        'entity' => 'KnowledgeBaseArticle',
        'relationName' => 'knowledgeBaseArticlePortal',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalId',
          1 => 'knowledgeBaseArticleId'
        ],
        'foreign' => 'portals',
        'indexes' => [
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'portalId_knowledgeBaseArticleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalId',
              1 => 'knowledgeBaseArticleId'
            ],
            'key' => 'UNIQ_PORTAL_ID_KNOWLEDGE_BASE_ARTICLE_ID'
          ]
        ]
      ],
      'authenticationProvider' => [
        'type' => 'belongsTo',
        'entity' => 'AuthenticationProvider',
        'key' => 'authenticationProviderId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'layoutSet' => [
        'type' => 'belongsTo',
        'entity' => 'LayoutSet',
        'key' => 'layoutSetId',
        'foreignKey' => 'id',
        'foreign' => 'portals'
      ],
      'notes' => [
        'type' => 'manyMany',
        'entity' => 'Note',
        'relationName' => 'notePortal',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalId',
          1 => 'noteId'
        ],
        'foreign' => 'portals',
        'indexes' => [
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'portalId_noteId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalId',
              1 => 'noteId'
            ],
            'key' => 'UNIQ_PORTAL_ID_NOTE_ID'
          ]
        ]
      ],
      'portalRoles' => [
        'type' => 'manyMany',
        'entity' => 'PortalRole',
        'relationName' => 'portalPortalRole',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalId',
          1 => 'portalRoleId'
        ],
        'foreign' => 'portals',
        'indexes' => [
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'portalRoleId' => [
            'columns' => [
              0 => 'portalRoleId'
            ],
            'key' => 'IDX_PORTAL_ROLE_ID'
          ],
          'portalId_portalRoleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalId',
              1 => 'portalRoleId'
            ],
            'key' => 'UNIQ_PORTAL_ID_PORTAL_ROLE_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'portalUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalId',
          1 => 'userId'
        ],
        'foreign' => 'portals',
        'indexes' => [
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'portalId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_PORTAL_ID_USER_ID'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'customId' => [
        'type' => 'index',
        'columns' => [
          0 => 'customId'
        ],
        'key' => 'IDX_CUSTOM_ID'
      ],
      'layoutSetId' => [
        'type' => 'index',
        'columns' => [
          0 => 'layoutSetId'
        ],
        'key' => 'IDX_LAYOUT_SET_ID'
      ],
      'authenticationProviderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'authenticationProviderId'
        ],
        'key' => 'IDX_AUTHENTICATION_PROVIDER_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'PortalRole' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 150,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'fieldData' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'exportPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'massUpdatePermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'portalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'portalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'portals' => [
        'type' => 'manyMany',
        'entity' => 'Portal',
        'relationName' => 'portalPortalRole',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalRoleId',
          1 => 'portalId'
        ],
        'foreign' => 'portalRoles',
        'indexes' => [
          'portalRoleId' => [
            'columns' => [
              0 => 'portalRoleId'
            ],
            'key' => 'IDX_PORTAL_ROLE_ID'
          ],
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'portalRoleId_portalId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalRoleId',
              1 => 'portalId'
            ],
            'key' => 'UNIQ_PORTAL_ROLE_ID_PORTAL_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'portalRoleUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'portalRoleId',
          1 => 'userId'
        ],
        'foreign' => 'portalRoles',
        'indexes' => [
          'portalRoleId' => [
            'columns' => [
              0 => 'portalRoleId'
            ],
            'key' => 'IDX_PORTAL_ROLE_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'portalRoleId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'portalRoleId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_PORTAL_ROLE_ID_USER_ID'
          ]
        ]
      ]
    ],
    'indexes' => [],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Preferences' => [
    'modifierClassName' => 'Espo\\Core\\Utils\\Database\\Schema\\EntityDefsModifiers\\JsonData',
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'timeZone' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'timeFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'weekStart' => [
        'type' => 'int',
        'default' => -1,
        'fieldType' => 'int',
        'len' => 11
      ],
      'defaultCurrency' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'thousandSeparator' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => ',',
        'fieldType' => 'varchar'
      ],
      'decimalMark' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => '.',
        'fieldType' => 'varchar'
      ],
      'dashboardLayout' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'dashletsOptions' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'dashboardLocked' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'importParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'sharedCalendarUserList' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'calendarViewDataList' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'presetFilters' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'language' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'exportDelimiter' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => ',',
        'fieldType' => 'varchar'
      ],
      'receiveAssignmentEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'receiveMentionEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'receiveStreamEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'assignmentNotificationsIgnoreEntityTypeList' => [
        'type' => 'jsonArray',
        'default' => [],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'assignmentEmailNotificationsIgnoreEntityTypeList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'reactionNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'reactionNotificationsNotFollowed' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'autoFollowEntityTypeList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'signature' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'defaultReminders' => [
        'type' => 'jsonArray',
        'default' => [],
        'fieldType' => 'jsonArray'
      ],
      'defaultRemindersTask' => [
        'type' => 'jsonArray',
        'default' => [],
        'fieldType' => 'jsonArray'
      ],
      'theme' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'themeParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'pageContentWidth' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'useCustomTabList' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'addCustomTabs' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'tabList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'emailReplyToAllByDefault' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'emailReplyForceHtml' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'isPortalUser' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'doNotFillAssignedUserIfNotRequired' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'followEntityOnStreamPost' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'followCreatedEntities' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'followCreatedEntityTypeList' => [
        'type' => 'jsonArray',
        'default' => [],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'followAsCollaborator' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'emailUseExternalClient' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'scopeColorsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'tabColorsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'textSearchStoringDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'calendarSlotDuration' => [
        'type' => 'int',
        'default' => NULL,
        'fieldType' => 'int',
        'len' => 11
      ],
      'calendarScrollHour' => [
        'type' => 'int',
        'default' => NULL,
        'fieldType' => 'int',
        'len' => 11
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'Role' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 150,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'info' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'assignmentPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'userPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'messagePermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'portalPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'groupEmailAccountPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'exportPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'massUpdatePermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dataPrivacyPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'followerManagementPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'auditPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'mentionPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'userCalendarPermission' => [
        'type' => 'varchar',
        'default' => 'not-set',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'fieldData' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'roleTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'roleId',
          1 => 'teamId'
        ],
        'foreign' => 'roles',
        'indexes' => [
          'roleId' => [
            'columns' => [
              0 => 'roleId'
            ],
            'key' => 'IDX_ROLE_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'roleId_teamId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'roleId',
              1 => 'teamId'
            ],
            'key' => 'UNIQ_ROLE_ID_TEAM_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'roleUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'roleId',
          1 => 'userId'
        ],
        'foreign' => 'roles',
        'indexes' => [
          'roleId' => [
            'columns' => [
              0 => 'roleId'
            ],
            'key' => 'IDX_ROLE_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'roleId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'roleId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_ROLE_ID_USER_ID'
          ]
        ]
      ]
    ],
    'indexes' => [],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'ScheduledJob' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'job' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Active',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'scheduling' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'lastRun' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isInternal' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'logIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'logNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'log' => [
        'type' => 'hasMany',
        'entity' => 'ScheduledJobLogRecord',
        'foreignKey' => 'scheduledJobId',
        'foreign' => 'scheduledJob'
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'ScheduledJobLogRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'executionTime' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'scheduledJobId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'scheduledJobName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'scheduledJob',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'scheduledJob' => [
        'type' => 'belongsTo',
        'entity' => 'ScheduledJob',
        'key' => 'scheduledJobId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'scheduledJobIdExecutionTime' => [
        'type' => 'index',
        'columns' => [
          0 => 'scheduledJobId',
          1 => 'executionTime'
        ],
        'key' => 'IDX_SCHEDULED_JOB_ID_EXECUTION_TIME'
      ],
      'scheduledJobId' => [
        'type' => 'index',
        'columns' => [
          0 => 'scheduledJobId'
        ],
        'key' => 'IDX_SCHEDULED_JOB_ID'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ]
    ],
    'collection' => [
      'orderBy' => 'executionTime',
      'order' => 'DESC'
    ]
  ],
  'Settings' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'useCache' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'recordsPerPage' => [
        'type' => 'int',
        'default' => 20,
        'fieldType' => 'int',
        'len' => 11
      ],
      'recordsPerPageSmall' => [
        'type' => 'int',
        'default' => 5,
        'fieldType' => 'int',
        'len' => 11
      ],
      'recordsPerPageSelect' => [
        'type' => 'int',
        'default' => 10,
        'fieldType' => 'int',
        'len' => 11
      ],
      'recordsPerPageKanban' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'timeZone' => [
        'type' => 'varchar',
        'default' => 'UTC',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateFormat' => [
        'type' => 'varchar',
        'default' => 'DD.MM.YYYY',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'timeFormat' => [
        'type' => 'varchar',
        'default' => 'HH:mm',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'weekStart' => [
        'type' => 'int',
        'default' => 0,
        'fieldType' => 'int',
        'len' => 11
      ],
      'fiscalYearShift' => [
        'type' => 'int',
        'default' => 0,
        'fieldType' => 'int',
        'len' => 11
      ],
      'thousandSeparator' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => ',',
        'fieldType' => 'varchar'
      ],
      'decimalMark' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => '.',
        'fieldType' => 'varchar'
      ],
      'currencyList' => [
        'type' => 'jsonArray',
        'default' => [
          0 => 'USD',
          1 => 'EUR'
        ],
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'defaultCurrency' => [
        'type' => 'varchar',
        'default' => 'USD',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'baseCurrency' => [
        'type' => 'varchar',
        'default' => 'USD',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'currencyRates' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'outboundEmailIsShared' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'outboundEmailFromName' => [
        'type' => 'varchar',
        'default' => 'EspoCRM',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'outboundEmailFromAddress' => [
        'type' => 'varchar',
        'default' => 'crm@example.com',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'emailAddressLookupEntityTypeList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'emailAddressSelectEntityTypeList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'smtpServer' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPort' => [
        'type' => 'int',
        'default' => 587,
        'fieldType' => 'int',
        'len' => 11
      ],
      'smtpAuth' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'smtpSecurity' => [
        'type' => 'varchar',
        'default' => 'TLS',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpUsername' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smtpPassword' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'tabList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'quickCreateList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'language' => [
        'type' => 'varchar',
        'default' => 'en_US',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'globalSearchEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'exportDelimiter' => [
        'type' => 'varchar',
        'len' => 1,
        'default' => ',',
        'fieldType' => 'varchar'
      ],
      'authenticationMethod' => [
        'type' => 'varchar',
        'default' => 'Espo',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'auth2FA' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'auth2FAMethodList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'auth2FAForced' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'auth2FAInPortal' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'passwordRecoveryDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'passwordRecoveryForAdminDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'passwordRecoveryForInternalUsersDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'passwordRecoveryNoExposure' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'passwordGenerateLength' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'passwordStrengthLength' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'passwordStrengthLetterCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'passwordStrengthNumberCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'passwordStrengthSpecialCharacterCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'passwordStrengthBothCases' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'ldapHost' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapPort' => [
        'type' => 'varchar',
        'default' => 389,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapSecurity' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapAuth' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'ldapUsername' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapPassword' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'ldapBindRequiresDn' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'ldapUserLoginFilter' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapBaseDn' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapAccountCanonicalForm' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapAccountDomainName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapAccountDomainNameShort' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapAccountFilterFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapTryUsernameSplit' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'ldapOptReferrals' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'ldapPortalUserLdapAuth' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'ldapCreateEspoUser' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'ldapUserNameAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserObjectClass' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserFirstNameAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserLastNameAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserTitleAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserEmailAddressAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ldapUserPhoneNumberAttribute' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'exportDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'emailNotificationsDelay' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'assignmentEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'assignmentEmailNotificationsEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'assignmentNotificationsEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'postEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'updateEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'mentionEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'streamEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'portalStreamEmailNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'streamEmailNotificationsEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'streamEmailNotificationsTypeList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'streamEmailWithContentEntityTypeList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'newNotificationCountInTitle' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'b2cMode' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'avatarsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'followCreatedEntities' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'adminPanelIframeUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'displayListViewRecordCount' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'userThemesDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'theme' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'themeParams' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'attachmentUploadMaxSize' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'attachmentUploadChunkSize' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'emailMessageMaxSize' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'inboundEmailMaxPortionSize' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'personalEmailMaxPortionSize' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'maxEmailAccountCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'massEmailMaxPerHourCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'massEmailMaxPerBatchCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'massEmailVerp' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'emailScheduledBatchCount' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'authTokenLifetime' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 0,
        'fieldType' => 'float'
      ],
      'authTokenMaxIdleTime' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 0,
        'fieldType' => 'float'
      ],
      'authTokenPreventConcurrent' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'dashboardLayout' => [
        'type' => 'jsonArray',
        'fieldType' => 'jsonArray'
      ],
      'dashletsOptions' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'siteUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'applicationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'readableDateFormatDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'addressFormat' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'personNameFormat' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'currencyFormat' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'currencyDecimalPlaces' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'notificationSoundsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'calendarEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'activitiesEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'historyEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'busyRangesEntityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'googleMapsApiKey' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'massEmailDisableMandatoryOptOutLink' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'massEmailOpenTracking' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'aclAllowDeleteCreated' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'lastViewedCount' => [
        'type' => 'int',
        'default' => 20,
        'fieldType' => 'int',
        'len' => 11
      ],
      'adminNotifications' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'adminNotificationsNewVersion' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'adminNotificationsNewExtensionVersion' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'textFilterUseContainsForVarchar' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'phoneNumberNumericSearch' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'phoneNumberInternational' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'phoneNumberExtensions' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'phoneNumberPreferredCountryList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'scopeColorsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'tabColorsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'tabIconsDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'emailAddressIsOptedOutByDefault' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'outboundEmailBccAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'cleanupDeletedRecords' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'addressCityList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'addressStateList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'jobRunInParallel' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'jobMaxPortion' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'jobPoolConcurrencyNumber' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'jobForceUtc' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'daemonInterval' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'daemonMaxProcessNumber' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'daemonProcessTimeout' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'cronDisabled' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'maintenanceMode' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'useWebSocket' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'awsS3Storage' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'outboundSmsFromNumber' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'smsProvider' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcClientId' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcClientSecret' => [
        'type' => 'password',
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'oidcAuthorizationEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcUserInfoEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcTokenEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcJwksEndpoint' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcJwtSignatureAlgorithmList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'oidcScopes' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'oidcGroupClaim' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcCreateUser' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcUsernameClaim' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcSync' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcSyncTeams' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcFallback' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcAllowRegularUserFallback' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcAllowAdminUser' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'oidcLogoutUrl' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'oidcAuthorizationPrompt' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'pdfEngine' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'quickSearchFullTextAppendWildcard' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'authIpAddressCheck' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'authIpAddressWhitelist' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'availableReactions' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'addressPreviewStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'notStorable' => true,
        'fieldType' => 'text'
      ],
      'addressPreviewCity' => [
        'type' => 'varchar',
        'len' => 100,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'addressPreviewState' => [
        'type' => 'varchar',
        'len' => 100,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'addressPreviewCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'addressPreviewPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'addressPreviewMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'companyLogoId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'companyLogoName' => [
        'type' => 'foreign',
        'relation' => 'companyLogo',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'ldapUserDefaultTeamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'ldapUserDefaultTeam',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'ldapUserDefaultTeamName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'ldapUserTeamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'ldapUserTeams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'ldapUserTeamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'ldapPortalUserPortalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'ldapPortalUserPortals',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'ldapPortalUserPortalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'ldapPortalUserRolesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'ldapPortalUserRoles',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'ldapPortalUserRolesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'workingTimeCalendarId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'workingTimeCalendar',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'workingTimeCalendarName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'oidcTeamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'oidcTeams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'oidcTeamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'authIpAddressCheckExcludedUsersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'authIpAddressCheckExcludedUsers',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'authIpAddressCheckExcludedUsersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'baselineRoleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'baselineRole',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'baselineRoleName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [
      'companyLogo' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'companyLogoId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'ldapUserDefaultTeam' => [
        'type' => 'index',
        'columns' => [
          0 => 'ldapUserDefaultTeamId'
        ],
        'key' => 'IDX_LDAP_USER_DEFAULT_TEAM'
      ],
      'workingTimeCalendar' => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeCalendarId'
        ],
        'key' => 'IDX_WORKING_TIME_CALENDAR'
      ],
      'baselineRole' => [
        'type' => 'index',
        'columns' => [
          0 => 'baselineRoleId'
        ],
        'key' => 'IDX_BASELINE_ROLE'
      ]
    ]
  ],
  'Sms' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'from' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'to' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'body' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Archived',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateSent' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'fromPhoneNumberId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'fromPhoneNumberName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'fromPhoneNumber',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'toPhoneNumbersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'toPhoneNumbers',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'toPhoneNumbersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'repliedId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'repliedName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'replied',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'repliesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'replies',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'dateSent',
        'isLinkStub' => false
      ],
      'repliesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ]
    ],
    'relations' => [
      'toPhoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'smsId',
          1 => 'phoneNumberId'
        ],
        'relationName' => 'smsPhoneNumber',
        'conditions' => [
          'addressType' => 'to'
        ],
        'additionalColumns' => [
          'addressType' => [
            'type' => 'varchar',
            'len' => '4'
          ]
        ],
        'indexes' => [
          'smsId' => [
            'columns' => [
              0 => 'smsId'
            ],
            'key' => 'IDX_SMS_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'smsId_phoneNumberId_addressType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'smsId',
              1 => 'phoneNumberId',
              2 => 'addressType'
            ],
            'key' => 'UNIQ_SMS_ID_PHONE_NUMBER_ID_ADDRESS_TYPE'
          ]
        ]
      ],
      'fromPhoneNumber' => [
        'type' => 'belongsTo',
        'entity' => 'PhoneNumber',
        'key' => 'fromPhoneNumberId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'replies' => [
        'type' => 'hasMany',
        'entity' => 'Sms',
        'foreignKey' => 'repliedId',
        'foreign' => 'replied'
      ],
      'replied' => [
        'type' => 'belongsTo',
        'entity' => 'Sms',
        'key' => 'repliedId',
        'foreignKey' => 'id',
        'foreign' => 'replies'
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'emails'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Sms'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'dateSent' => [
        'columns' => [
          0 => 'dateSent',
          1 => 'deleted'
        ],
        'key' => 'IDX_DATE_SENT'
      ],
      'dateSentStatus' => [
        'columns' => [
          0 => 'dateSent',
          1 => 'status',
          2 => 'deleted'
        ],
        'key' => 'IDX_DATE_SENT_STATUS'
      ],
      'fromPhoneNumberId' => [
        'type' => 'index',
        'columns' => [
          0 => 'fromPhoneNumberId'
        ],
        'key' => 'IDX_FROM_PHONE_NUMBER_ID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'repliedId' => [
        'type' => 'index',
        'columns' => [
          0 => 'repliedId'
        ],
        'key' => 'IDX_REPLIED_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'dateSent',
      'order' => 'DESC'
    ]
  ],
  'StarSubscription' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'user',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'userEntity' => [
        'unique' => true,
        'columns' => [
          0 => 'userId',
          1 => 'entityId',
          2 => 'entityType'
        ],
        'key' => 'UNIQ_USER_ENTITY'
      ],
      'userEntityType' => [
        'columns' => [
          0 => 'userId',
          1 => 'entityType'
        ],
        'key' => 'IDX_USER_ENTITY_TYPE'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ],
      'user' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER'
      ]
    ]
  ],
  'StreamSubscription' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'user',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ]
    ],
    'relations' => [],
    'indexes' => [
      'userEntity' => [
        'columns' => [
          0 => 'userId',
          1 => 'entityId',
          2 => 'entityType'
        ],
        'key' => 'IDX_USER_ENTITY'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ],
      'user' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER'
      ]
    ]
  ],
  'SystemData' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'string',
        'len' => 1,
        'fieldType' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'lastPasswordRecoveryDate' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ]
    ],
    'relations' => [],
    'indexes' => []
  ],
  'Team' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'positionList' => [
        'type' => 'jsonArray',
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'userRole' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'rolesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'roles',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'rolesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'layoutSetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'layoutSetName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'layoutSet',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'workingTimeCalendarId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'workingTimeCalendarName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'workingTimeCalendar',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'groupEmailFoldersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'groupEmailFoldersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'inboundEmailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'inboundEmailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'groupEmailFolders' => [
        'type' => 'manyMany',
        'entity' => 'GroupEmailFolder',
        'relationName' => 'groupEmailFolderTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'teamId',
          1 => 'groupEmailFolderId'
        ],
        'foreign' => 'teams',
        'indexes' => [
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'groupEmailFolderId' => [
            'columns' => [
              0 => 'groupEmailFolderId'
            ],
            'key' => 'IDX_GROUP_EMAIL_FOLDER_ID'
          ],
          'teamId_groupEmailFolderId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'teamId',
              1 => 'groupEmailFolderId'
            ],
            'key' => 'UNIQ_TEAM_ID_GROUP_EMAIL_FOLDER_ID'
          ]
        ]
      ],
      'workingTimeCalendar' => [
        'type' => 'belongsTo',
        'entity' => 'WorkingTimeCalendar',
        'key' => 'workingTimeCalendarId',
        'foreignKey' => 'id',
        'foreign' => 'teams'
      ],
      'layoutSet' => [
        'type' => 'belongsTo',
        'entity' => 'LayoutSet',
        'key' => 'layoutSetId',
        'foreignKey' => 'id',
        'foreign' => 'teams'
      ],
      'inboundEmails' => [
        'type' => 'manyMany',
        'entity' => 'InboundEmail',
        'relationName' => 'inboundEmailTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'teamId',
          1 => 'inboundEmailId'
        ],
        'foreign' => 'teams',
        'indexes' => [
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'inboundEmailId' => [
            'columns' => [
              0 => 'inboundEmailId'
            ],
            'key' => 'IDX_INBOUND_EMAIL_ID'
          ],
          'teamId_inboundEmailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'teamId',
              1 => 'inboundEmailId'
            ],
            'key' => 'UNIQ_TEAM_ID_INBOUND_EMAIL_ID'
          ]
        ]
      ],
      'notes' => [
        'type' => 'manyMany',
        'entity' => 'Note',
        'relationName' => 'noteTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'teamId',
          1 => 'noteId'
        ],
        'foreign' => 'teams',
        'indexes' => [
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'teamId_noteId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'teamId',
              1 => 'noteId'
            ],
            'key' => 'UNIQ_TEAM_ID_NOTE_ID'
          ]
        ]
      ],
      'roles' => [
        'type' => 'manyMany',
        'entity' => 'Role',
        'relationName' => 'roleTeam',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'teamId',
          1 => 'roleId'
        ],
        'foreign' => 'teams',
        'indexes' => [
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'roleId' => [
            'columns' => [
              0 => 'roleId'
            ],
            'key' => 'IDX_ROLE_ID'
          ],
          'teamId_roleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'teamId',
              1 => 'roleId'
            ],
            'key' => 'UNIQ_TEAM_ID_ROLE_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'teamUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'teamId',
          1 => 'userId'
        ],
        'foreign' => 'teams',
        'columnAttributeMap' => [
          'role' => 'userRole'
        ],
        'additionalColumns' => [
          'role' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'teamId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'teamId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_TEAM_ID_USER_ID'
          ]
        ]
      ]
    ],
    'indexes' => [
      'layoutSetId' => [
        'type' => 'index',
        'columns' => [
          0 => 'layoutSetId'
        ],
        'key' => 'IDX_LAYOUT_SET_ID'
      ],
      'workingTimeCalendarId' => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeCalendarId'
        ],
        'key' => 'IDX_WORKING_TIME_CALENDAR_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Template' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'body' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'header' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'footer' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'entityType' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 8,
        'default' => 'Active',
        'fieldType' => 'varchar'
      ],
      'leftMargin' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 10,
        'fieldType' => 'float'
      ],
      'rightMargin' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 10,
        'fieldType' => 'float'
      ],
      'topMargin' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 10,
        'fieldType' => 'float'
      ],
      'bottomMargin' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 20,
        'fieldType' => 'float'
      ],
      'printFooter' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'printHeader' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'footerPosition' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 10,
        'fieldType' => 'float'
      ],
      'headerPosition' => [
        'type' => 'float',
        'notNull' => false,
        'default' => 0,
        'fieldType' => 'float'
      ],
      'style' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'variables' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'pageOrientation' => [
        'type' => 'varchar',
        'default' => 'Portrait',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'pageFormat' => [
        'type' => 'varchar',
        'default' => 'A4',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'pageWidth' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'pageHeight' => [
        'type' => 'float',
        'notNull' => false,
        'fieldType' => 'float'
      ],
      'fontFace' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'title' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'filename' => [
        'type' => 'varchar',
        'len' => 150,
        'fieldType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Template'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'TwoFactorCode' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'code' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'method' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'attemptsLeft' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'columns' => [
          0 => 'createdAt'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'userIdMethod' => [
        'columns' => [
          0 => 'userId',
          1 => 'method'
        ],
        'key' => 'IDX_USER_ID_METHOD'
      ],
      'userIdMethodIsActive' => [
        'columns' => [
          0 => 'userId',
          1 => 'method',
          2 => 'isActive'
        ],
        'key' => 'IDX_USER_ID_METHOD_IS_ACTIVE'
      ],
      'userIdMethodCreatedAt' => [
        'columns' => [
          0 => 'userId',
          1 => 'method',
          2 => 'createdAt'
        ],
        'key' => 'IDX_USER_ID_METHOD_CREATED_AT'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ]
    ]
  ],
  'UniqueId' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'index' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'terminateAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'name' => [
        'type' => 'index',
        'columns' => [
          0 => 'name'
        ],
        'key' => 'IDX_NAME'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'User' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'dependeeAttributeList' => [
          0 => 'userName'
        ],
        'fieldType' => 'personName',
        'notStorable' => true,
        'select' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:(firstName, \'\'), \' \', IFNULL:(lastName, \'\'))), \'\')'
        ],
        'selectForeign' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:({alias}.firstName, \'\'), \' \', IFNULL:({alias}.lastName, \'\'))), \'\')'
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'OR' => [
                'firstName*' => '{value}',
                'lastName*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)*' => '{value}'
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'AND' => [
                'firstName!*' => '{value}',
                'lastName!*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)!*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)!*' => '{value}'
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'OR' => [
                'firstName' => '{value}',
                'lastName' => '{value}',
                'CONCAT:(firstName, \' \', lastName)' => '{value}',
                'CONCAT:(lastName, \' \', firstName)' => '{value}'
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'firstName',
              1 => '{direction}'
            ],
            1 => [
              0 => 'lastName',
              1 => '{direction}'
            ]
          ]
        ]
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userName' => [
        'type' => 'varchar',
        'len' => 50,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 24,
        'index' => true,
        'default' => 'regular',
        'fieldType' => 'varchar'
      ],
      'password' => [
        'type' => 'password',
        'len' => 150,
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'passwordConfirm' => [
        'type' => 'password',
        'len' => 150,
        'notStorable' => true,
        'fieldType' => 'password',
        'dbType' => 'string'
      ],
      'authMethod' => [
        'type' => 'varchar',
        'len' => 24,
        'fieldType' => 'varchar'
      ],
      'apiKey' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'secretKey' => [
        'type' => 'varchar',
        'len' => 100,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'salutationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'firstName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'lastName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'title' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'position' => [
        'type' => 'varchar',
        'len' => 100,
        'notExportable' => true,
        'notStorable' => true,
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
        'fieldType' => 'varchar'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'email',
        'select' => [
          'select' => 'emailAddresses.name',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressUser{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressUser{alias}ForeignMiddle',
              2 => [
                'emailAddressUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressUser{alias}ForeignMiddle.primary' => true,
                'emailAddressUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressUser{alias}Foreign',
              2 => [
                'emailAddressUser{alias}Foreign.id:' => 'emailAddressUser{alias}ForeignMiddle.emailAddressId',
                'emailAddressUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'EQUAL:(emailAddressesMultiple.lower, LOWER:({value})):' => NULL
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'EQUAL:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'emailAddressesMultiple.lower=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.lower',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.lower'
          ]
        ]
      ],
      'phoneNumber' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'phone',
        'select' => [
          'select' => 'phoneNumbers.name',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberUser{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberUser{alias}ForeignMiddle',
              2 => [
                'phoneNumberUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberUser{alias}ForeignMiddle.primary' => true,
                'phoneNumberUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberUser{alias}Foreign',
              2 => [
                'phoneNumberUser{alias}Foreign.id:' => 'phoneNumberUser{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => '{value}'
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.name!=' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.name',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.name'
          ]
        ]
      ],
      'token' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'authTokenId' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'authLogRecordId' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'ipAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusMeetings' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'meetings',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusCalls' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'calls',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'teamRole' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'avatarColor' => [
        'type' => 'varchar',
        'len' => 7,
        'fieldType' => 'varchar'
      ],
      'sendAccessInfo' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'gender' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'auth2FA' => [
        'type' => 'foreign',
        'relation' => 'userData',
        'foreign' => 'auth2FA',
        'fieldType' => 'foreign',
        'foreignType' => 'bool'
      ],
      'lastAccess' => [
        'type' => 'datetime',
        'notNull' => false,
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'datetime'
      ],
      'emailAddressList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'userEmailAddressList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'excludeFromReplyEmailAddressList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'recordAccessLevels' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'targetListIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'middleName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'emailAddressIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.optOut',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressUser{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressUser{alias}ForeignMiddle',
              2 => [
                'emailAddressUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressUser{alias}ForeignMiddle.primary' => true,
                'emailAddressUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressUser{alias}Foreign',
              2 => [
                'emailAddressUser{alias}Foreign.id:' => 'emailAddressUser{alias}ForeignMiddle.emailAddressId',
                'emailAddressUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.optOut=' => true
              ],
              1 => [
                'emailAddresses.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.optOut=' => false
                ],
                1 => [
                  'emailAddresses.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.optOut'
          ]
        ],
        'default' => false
      ],
      'emailAddressIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.invalid',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressUser{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressUser{alias}ForeignMiddle',
              2 => [
                'emailAddressUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressUser{alias}ForeignMiddle.primary' => true,
                'emailAddressUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressUser{alias}Foreign',
              2 => [
                'emailAddressUser{alias}Foreign.id:' => 'emailAddressUser{alias}ForeignMiddle.emailAddressId',
                'emailAddressUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.invalid=' => true
              ],
              1 => [
                'emailAddresses.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.invalid=' => false
                ],
                1 => [
                  'emailAddresses.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.invalid'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.optOut',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberUser{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberUser{alias}ForeignMiddle',
              2 => [
                'phoneNumberUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberUser{alias}ForeignMiddle.primary' => true,
                'phoneNumberUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberUser{alias}Foreign',
              2 => [
                'phoneNumberUser{alias}Foreign.id:' => 'phoneNumberUser{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.optOut=' => true
              ],
              1 => [
                'phoneNumbers.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.optOut=' => false
                ],
                1 => [
                  'phoneNumbers.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.optOut'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.invalid',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberUser{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberUser{alias}ForeignMiddle',
              2 => [
                'phoneNumberUser{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberUser{alias}ForeignMiddle.primary' => true,
                'phoneNumberUser{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberUser{alias}Foreign',
              2 => [
                'phoneNumberUser{alias}Foreign.id:' => 'phoneNumberUser{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberUser{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.invalid=' => true
              ],
              1 => [
                'phoneNumbers.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.invalid=' => false
                ],
                1 => [
                  'phoneNumbers.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.invalid'
          ]
        ],
        'default' => false
      ],
      'deleteId' => [
        'type' => 'varchar',
        'len' => 17,
        'notNull' => true,
        'default' => '0',
        'fieldType' => 'varchar'
      ],
      'emailAddressData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isEmailAddressData' => true,
        'field' => 'emailAddress'
      ],
      'phoneNumberData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isPhoneNumberData' => true,
        'field' => 'phoneNumber'
      ],
      'phoneNumberNumeric' => [
        'type' => 'varchar',
        'notStorable' => true,
        'notExportable' => true,
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.numeric=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'User'
                ]
              ]
            ]
          ]
        ]
      ],
      'defaultTeamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'defaultTeamName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'defaultTeam',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'role' => 'userRole'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'rolesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'roles',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'rolesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'portals',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalRolesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'portalRoles',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalRolesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'contactName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'contact',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'accounts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'account',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'portal',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'portalName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'avatarId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'default' => NULL,
        'notNull' => false
      ],
      'avatarName' => [
        'type' => 'foreign',
        'relation' => 'avatar',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'dashboardTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'dashboardTemplateName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'dashboardTemplate',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'workingTimeCalendarId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'workingTimeCalendarName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'workingTimeCalendar',
        'foreign' => 'name'
      ],
      'layoutSetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'layoutSetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'layoutSet',
        'foreign' => 'name'
      ],
      'userDataId' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'id',
        'fieldType' => 'linkOne',
        'relation' => 'userData',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'userDataName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkOne',
        'relation' => 'userData',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'notesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'workingTimeRangesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'workingTimeRangesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'relationName' => 'entityEmailAddress',
        'midKeys' => [
          0 => 'entityId',
          1 => 'emailAddressId'
        ],
        'conditions' => [
          'entityType' => 'User'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'entityId_emailAddressId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'emailAddressId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'phoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'relationName' => 'entityPhoneNumber',
        'midKeys' => [
          0 => 'entityId',
          1 => 'phoneNumberId'
        ],
        'conditions' => [
          'entityType' => 'User'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'entityId_phoneNumberId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'phoneNumberId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'avatar' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'avatarId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'targetListUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'targetListId'
        ],
        'foreign' => 'users',
        'columnAttributeMap' => [
          'optedOut' => 'targetListIsOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'userId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_USER_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'tasks' => [
        'type' => 'hasMany',
        'entity' => 'Task',
        'foreignKey' => 'assignedUserId',
        'foreign' => 'assignedUser'
      ],
      'accounts' => [
        'type' => 'manyMany',
        'entity' => 'Account',
        'relationName' => 'accountPortalUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'accountId'
        ],
        'foreign' => 'portalUsers',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'userId_accountId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'accountId'
            ],
            'key' => 'UNIQ_USER_ID_ACCOUNT_ID'
          ]
        ]
      ],
      'contact' => [
        'type' => 'belongsTo',
        'entity' => 'Contact',
        'key' => 'contactId',
        'foreignKey' => 'id',
        'foreign' => 'portalUser'
      ],
      'notes' => [
        'type' => 'manyMany',
        'entity' => 'Note',
        'relationName' => 'noteUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'noteId'
        ],
        'foreign' => 'users',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'noteId' => [
            'columns' => [
              0 => 'noteId'
            ],
            'key' => 'IDX_NOTE_ID'
          ],
          'userId_noteId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'noteId'
            ],
            'key' => 'UNIQ_USER_ID_NOTE_ID'
          ]
        ]
      ],
      'emails' => [
        'type' => 'manyMany',
        'entity' => 'Email',
        'relationName' => 'emailUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'emailId'
        ],
        'foreign' => 'users',
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
        ],
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'emailId' => [
            'columns' => [
              0 => 'emailId'
            ],
            'key' => 'IDX_EMAIL_ID'
          ],
          'userId_emailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'emailId'
            ],
            'key' => 'UNIQ_USER_ID_EMAIL_ID'
          ]
        ]
      ],
      'calls' => [
        'type' => 'manyMany',
        'entity' => 'Call',
        'relationName' => 'callUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'callId'
        ],
        'foreign' => 'users',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'userId_callId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'callId'
            ],
            'key' => 'UNIQ_USER_ID_CALL_ID'
          ]
        ]
      ],
      'meetings' => [
        'type' => 'manyMany',
        'entity' => 'Meeting',
        'relationName' => 'meetingUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'meetingId'
        ],
        'foreign' => 'users',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'userId_meetingId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'meetingId'
            ],
            'key' => 'UNIQ_USER_ID_MEETING_ID'
          ]
        ]
      ],
      'userData' => [
        'type' => 'hasOne',
        'entity' => 'UserData',
        'foreignKey' => 'userId',
        'foreign' => 'user'
      ],
      'layoutSet' => [
        'type' => 'belongsTo',
        'entity' => 'LayoutSet',
        'key' => 'layoutSetId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'workingTimeRanges' => [
        'type' => 'manyMany',
        'entity' => 'WorkingTimeRange',
        'relationName' => 'userWorkingTimeRange',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'workingTimeRangeId'
        ],
        'foreign' => 'users',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'workingTimeRangeId' => [
            'columns' => [
              0 => 'workingTimeRangeId'
            ],
            'key' => 'IDX_WORKING_TIME_RANGE_ID'
          ],
          'userId_workingTimeRangeId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'workingTimeRangeId'
            ],
            'key' => 'UNIQ_USER_ID_WORKING_TIME_RANGE_ID'
          ]
        ]
      ],
      'workingTimeCalendar' => [
        'type' => 'belongsTo',
        'entity' => 'WorkingTimeCalendar',
        'key' => 'workingTimeCalendarId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'dashboardTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'DashboardTemplate',
        'key' => 'dashboardTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'portalRoles' => [
        'type' => 'manyMany',
        'entity' => 'PortalRole',
        'relationName' => 'portalRoleUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'portalRoleId'
        ],
        'foreign' => 'users',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'portalRoleId' => [
            'columns' => [
              0 => 'portalRoleId'
            ],
            'key' => 'IDX_PORTAL_ROLE_ID'
          ],
          'userId_portalRoleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'portalRoleId'
            ],
            'key' => 'UNIQ_USER_ID_PORTAL_ROLE_ID'
          ]
        ]
      ],
      'portals' => [
        'type' => 'manyMany',
        'entity' => 'Portal',
        'relationName' => 'portalUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'portalId'
        ],
        'foreign' => 'users',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'userId_portalId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'portalId'
            ],
            'key' => 'UNIQ_USER_ID_PORTAL_ID'
          ]
        ]
      ],
      'roles' => [
        'type' => 'manyMany',
        'entity' => 'Role',
        'relationName' => 'roleUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'roleId'
        ],
        'foreign' => 'users',
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'roleId' => [
            'columns' => [
              0 => 'roleId'
            ],
            'key' => 'IDX_ROLE_ID'
          ],
          'userId_roleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'roleId'
            ],
            'key' => 'UNIQ_USER_ID_ROLE_ID'
          ]
        ]
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'teamUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'userId',
          1 => 'teamId'
        ],
        'foreign' => 'users',
        'columnAttributeMap' => [
          'role' => 'teamRole'
        ],
        'additionalColumns' => [
          'role' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'userId_teamId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'userId',
              1 => 'teamId'
            ],
            'key' => 'UNIQ_USER_ID_TEAM_ID'
          ]
        ]
      ],
      'defaultTeam' => [
        'type' => 'belongsTo',
        'entity' => 'Team',
        'key' => 'defaultTeamId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'userNameDeleteId' => [
        'type' => 'unique',
        'columns' => [
          0 => 'userName',
          1 => 'deleteId'
        ],
        'key' => 'UNIQ_USER_NAME_DELETE_ID'
      ],
      'userName' => [
        'type' => 'index',
        'columns' => [
          0 => 'userName'
        ],
        'key' => 'IDX_USER_NAME'
      ],
      'type' => [
        'type' => 'index',
        'columns' => [
          0 => 'type'
        ],
        'key' => 'IDX_TYPE'
      ],
      'defaultTeamId' => [
        'type' => 'index',
        'columns' => [
          0 => 'defaultTeamId'
        ],
        'key' => 'IDX_DEFAULT_TEAM_ID'
      ],
      'contactId' => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'key' => 'IDX_CONTACT_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'dashboardTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'dashboardTemplateId'
        ],
        'key' => 'IDX_DASHBOARD_TEMPLATE_ID'
      ],
      'workingTimeCalendarId' => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeCalendarId'
        ],
        'key' => 'IDX_WORKING_TIME_CALENDAR_ID'
      ],
      'layoutSetId' => [
        'type' => 'index',
        'columns' => [
          0 => 'layoutSetId'
        ],
        'key' => 'IDX_LAYOUT_SET_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'userName',
      'order' => 'ASC'
    ]
  ],
  'UserData' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'auth2FA' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'auth2FAMethod' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'auth2FATotpSecret' => [
        'type' => 'varchar',
        'len' => 32,
        'fieldType' => 'varchar'
      ],
      'auth2FAEmailAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ]
    ]
  ],
  'UserReaction' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 10,
        'fieldType' => 'varchar'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
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
        ],
        'key' => 'UNIQ_PARENT_USER_TYPE'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ]
    ]
  ],
  'Webhook' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'event' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'url' => [
        'type' => 'varchar',
        'len' => 512,
        'fieldType' => 'varchar'
      ],
      'isActive' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'entityType' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'field' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'secretKey' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'skipOwn' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'queueItemsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'queueItemsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'queueItems' => [
        'type' => 'hasMany',
        'entity' => 'WebhookQueueItem',
        'foreignKey' => 'webhookId',
        'foreign' => 'webhook'
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'event' => [
        'columns' => [
          0 => 'event'
        ],
        'key' => 'IDX_EVENT'
      ],
      'entityTypeType' => [
        'columns' => [
          0 => 'entityType',
          1 => 'type'
        ],
        'key' => 'IDX_ENTITY_TYPE_TYPE'
      ],
      'entityTypeField' => [
        'columns' => [
          0 => 'entityType',
          1 => 'field'
        ],
        'key' => 'IDX_ENTITY_TYPE_FIELD'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'WebhookEventQueueItem' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'event' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isProcessed' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'WebhookQueueItem' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'autoincrement' => true,
        'unique' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'event' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 7,
        'default' => 'Pending',
        'fieldType' => 'varchar'
      ],
      'processedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'attempts' => [
        'type' => 'int',
        'default' => 0,
        'fieldType' => 'int',
        'len' => 11
      ],
      'processAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'webhookId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'webhookName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'webhook',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'webhook' => [
        'type' => 'belongsTo',
        'entity' => 'Webhook',
        'key' => 'webhookId',
        'foreignKey' => 'id',
        'foreign' => 'queueItems'
      ],
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'webhookId' => [
        'type' => 'index',
        'columns' => [
          0 => 'webhookId'
        ],
        'key' => 'IDX_WEBHOOK_ID'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ]
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'WorkingTimeCalendar' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'timeZone' => [
        'type' => 'varchar',
        'default' => NULL,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'timeRanges' => [
        'type' => 'jsonArray',
        'default' => [
          0 => [
            0 => '9:00',
            1 => '17:00'
          ]
        ],
        'fieldType' => 'jsonArray'
      ],
      'weekday0' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'weekday1' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'weekday2' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'weekday3' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'weekday4' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'weekday5' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'weekday6' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'weekday0TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday1TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday2TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday3TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday4TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday5TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'weekday6TimeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'rangesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'rangesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'hasMany',
        'entity' => 'Team',
        'foreignKey' => 'workingTimeCalendarId',
        'foreign' => 'workingTimeCalendar'
      ],
      'ranges' => [
        'type' => 'manyMany',
        'entity' => 'WorkingTimeRange',
        'relationName' => 'workingTimeCalendarWorkingTimeRange',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'workingTimeCalendarId',
          1 => 'workingTimeRangeId'
        ],
        'foreign' => 'calendars',
        'indexes' => [
          'workingTimeCalendarId' => [
            'columns' => [
              0 => 'workingTimeCalendarId'
            ],
            'key' => 'IDX_WORKING_TIME_CALENDAR_ID'
          ],
          'workingTimeRangeId' => [
            'columns' => [
              0 => 'workingTimeRangeId'
            ],
            'key' => 'IDX_WORKING_TIME_RANGE_ID'
          ],
          'workingTimeCalendarId_workingTimeRangeId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'workingTimeCalendarId',
              1 => 'workingTimeRangeId'
            ],
            'key' => 'UNIQ_WORKING_TIME_CALENDAR_ID_WORKING_TIME_RANGE_ID'
          ]
        ]
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'WorkingTimeRange' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'timeRanges' => [
        'type' => 'jsonArray',
        'default' => NULL,
        'fieldType' => 'jsonArray'
      ],
      'dateStart' => [
        'type' => 'date',
        'fieldType' => 'date'
      ],
      'dateEnd' => [
        'type' => 'date',
        'fieldType' => 'date'
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 11,
        'index' => true,
        'default' => 'Non-working',
        'fieldType' => 'varchar'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'calendarsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'calendars',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'calendarsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'users',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'userWorkingTimeRange',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'workingTimeRangeId',
          1 => 'userId'
        ],
        'foreign' => 'workingTimeRanges',
        'indexes' => [
          'workingTimeRangeId' => [
            'columns' => [
              0 => 'workingTimeRangeId'
            ],
            'key' => 'IDX_WORKING_TIME_RANGE_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'workingTimeRangeId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'workingTimeRangeId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_WORKING_TIME_RANGE_ID_USER_ID'
          ]
        ]
      ],
      'calendars' => [
        'type' => 'manyMany',
        'entity' => 'WorkingTimeCalendar',
        'relationName' => 'workingTimeCalendarWorkingTimeRange',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'workingTimeRangeId',
          1 => 'workingTimeCalendarId'
        ],
        'foreign' => 'ranges',
        'indexes' => [
          'workingTimeRangeId' => [
            'columns' => [
              0 => 'workingTimeRangeId'
            ],
            'key' => 'IDX_WORKING_TIME_RANGE_ID'
          ],
          'workingTimeCalendarId' => [
            'columns' => [
              0 => 'workingTimeCalendarId'
            ],
            'key' => 'IDX_WORKING_TIME_CALENDAR_ID'
          ],
          'workingTimeRangeId_workingTimeCalendarId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'workingTimeRangeId',
              1 => 'workingTimeCalendarId'
            ],
            'key' => 'UNIQ_WORKING_TIME_RANGE_ID_WORKING_TIME_CALENDAR_ID'
          ]
        ]
      ]
    ],
    'indexes' => [
      'typeRange' => [
        'columns' => [
          0 => 'type',
          1 => 'dateStart',
          2 => 'dateEnd'
        ],
        'key' => 'IDX_TYPE_RANGE'
      ],
      'type' => [
        'columns' => [
          0 => 'type'
        ],
        'key' => 'IDX_TYPE'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'dateStart',
      'order' => 'DESC'
    ]
  ],
  'Account' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'len' => 249,
        'fieldType' => 'varchar'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'website' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'email',
        'select' => [
          'select' => 'emailAddresses.name',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressAccount{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressAccount{alias}ForeignMiddle',
              2 => [
                'emailAddressAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressAccount{alias}ForeignMiddle.primary' => true,
                'emailAddressAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressAccount{alias}Foreign',
              2 => [
                'emailAddressAccount{alias}Foreign.id:' => 'emailAddressAccount{alias}ForeignMiddle.emailAddressId',
                'emailAddressAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'EQUAL:(emailAddressesMultiple.lower, LOWER:({value})):' => NULL
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'EQUAL:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'emailAddressesMultiple.lower=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.lower',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.lower'
          ]
        ]
      ],
      'phoneNumber' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'phone',
        'select' => [
          'select' => 'phoneNumbers.name',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberAccount{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberAccount{alias}ForeignMiddle',
              2 => [
                'phoneNumberAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberAccount{alias}ForeignMiddle.primary' => true,
                'phoneNumberAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberAccount{alias}Foreign',
              2 => [
                'phoneNumberAccount{alias}Foreign.id:' => 'phoneNumberAccount{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => '{value}'
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.name!=' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.name',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.name'
          ]
        ]
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'industry' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'sicCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'contactRole' => [
        'type' => 'varchar',
        'len' => 100,
        'notStorable' => true,
        'fieldType' => 'varchar'
      ],
      'contactIsInactive' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'billingAddressStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'fieldType' => 'text'
      ],
      'billingAddressCity' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'billingAddressState' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'billingAddressCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'billingAddressPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'shippingAddressStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'fieldType' => 'text'
      ],
      'shippingAddressCity' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'shippingAddressState' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'shippingAddressCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'shippingAddressPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'targetListIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'emailAddressIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.optOut',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressAccount{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressAccount{alias}ForeignMiddle',
              2 => [
                'emailAddressAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressAccount{alias}ForeignMiddle.primary' => true,
                'emailAddressAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressAccount{alias}Foreign',
              2 => [
                'emailAddressAccount{alias}Foreign.id:' => 'emailAddressAccount{alias}ForeignMiddle.emailAddressId',
                'emailAddressAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.optOut=' => true
              ],
              1 => [
                'emailAddresses.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.optOut=' => false
                ],
                1 => [
                  'emailAddresses.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.optOut'
          ]
        ],
        'default' => false
      ],
      'emailAddressIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.invalid',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressAccount{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressAccount{alias}ForeignMiddle',
              2 => [
                'emailAddressAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressAccount{alias}ForeignMiddle.primary' => true,
                'emailAddressAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressAccount{alias}Foreign',
              2 => [
                'emailAddressAccount{alias}Foreign.id:' => 'emailAddressAccount{alias}ForeignMiddle.emailAddressId',
                'emailAddressAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.invalid=' => true
              ],
              1 => [
                'emailAddresses.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.invalid=' => false
                ],
                1 => [
                  'emailAddresses.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.invalid'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.optOut',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberAccount{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberAccount{alias}ForeignMiddle',
              2 => [
                'phoneNumberAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberAccount{alias}ForeignMiddle.primary' => true,
                'phoneNumberAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberAccount{alias}Foreign',
              2 => [
                'phoneNumberAccount{alias}Foreign.id:' => 'phoneNumberAccount{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.optOut=' => true
              ],
              1 => [
                'phoneNumbers.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.optOut=' => false
                ],
                1 => [
                  'phoneNumbers.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.optOut'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.invalid',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberAccount{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberAccount{alias}ForeignMiddle',
              2 => [
                'phoneNumberAccount{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberAccount{alias}ForeignMiddle.primary' => true,
                'phoneNumberAccount{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberAccount{alias}Foreign',
              2 => [
                'phoneNumberAccount{alias}Foreign.id:' => 'phoneNumberAccount{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberAccount{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.invalid=' => true
              ],
              1 => [
                'phoneNumbers.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.invalid=' => false
                ],
                1 => [
                  'phoneNumbers.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.invalid'
          ]
        ],
        'default' => false
      ],
      'billingAddressMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'shippingAddressMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'emailAddressData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isEmailAddressData' => true,
        'field' => 'emailAddress'
      ],
      'phoneNumberData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isPhoneNumberData' => true,
        'field' => 'phoneNumber'
      ],
      'phoneNumberNumeric' => [
        'type' => 'varchar',
        'notStorable' => true,
        'notExportable' => true,
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.numeric=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Account'
                ]
              ]
            ]
          ]
        ]
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'targetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'targetList',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'targetListName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'originalLeadId' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'id',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'originalLeadName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'isStarred' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'readOnly' => true,
        'default' => false
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'portalUsersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'portalUsersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'relationName' => 'entityEmailAddress',
        'midKeys' => [
          0 => 'entityId',
          1 => 'emailAddressId'
        ],
        'conditions' => [
          'entityType' => 'Account'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'entityId_emailAddressId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'emailAddressId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'phoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'relationName' => 'entityPhoneNumber',
        'midKeys' => [
          0 => 'entityId',
          1 => 'phoneNumberId'
        ],
        'conditions' => [
          'entityType' => 'Account'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'entityId_phoneNumberId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'phoneNumberId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'originalLead' => [
        'type' => 'hasOne',
        'entity' => 'Lead',
        'foreignKey' => 'createdAccountId',
        'foreign' => 'createdAccount'
      ],
      'portalUsers' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'AccountPortalUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'accountId',
          1 => 'userId'
        ],
        'foreign' => 'accounts',
        'indexes' => [
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'accountId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'accountId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_ACCOUNT_ID_USER_ID'
          ]
        ]
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'accountTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'accountId',
          1 => 'targetListId'
        ],
        'foreign' => 'accounts',
        'columnAttributeMap' => [
          'optedOut' => 'targetListIsOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'accountId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'accountId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_ACCOUNT_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'campaignLogRecords' => [
        'type' => 'hasChildren',
        'entity' => 'CampaignLogRecord',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'accounts'
      ],
      'emails' => [
        'type' => 'hasChildren',
        'entity' => 'Email',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'tasks' => [
        'type' => 'hasChildren',
        'entity' => 'Task',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'calls' => [
        'type' => 'hasChildren',
        'entity' => 'Call',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'meetings' => [
        'type' => 'hasChildren',
        'entity' => 'Meeting',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'tasksPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Task',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'callsPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Call',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'emailsPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Email',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'meetingsPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Meeting',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'documents' => [
        'type' => 'manyMany',
        'entity' => 'Document',
        'relationName' => 'accountDocument',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'accountId',
          1 => 'documentId'
        ],
        'foreign' => 'accounts',
        'indexes' => [
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'accountId_documentId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'accountId',
              1 => 'documentId'
            ],
            'key' => 'UNIQ_ACCOUNT_ID_DOCUMENT_ID'
          ]
        ]
      ],
      'cases' => [
        'type' => 'hasMany',
        'entity' => 'Case',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'opportunities' => [
        'type' => 'hasMany',
        'entity' => 'Opportunity',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'contactsPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Contact',
        'foreignKey' => 'accountId',
        'foreign' => 'account'
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'accountContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'accountId',
          1 => 'contactId'
        ],
        'foreign' => 'accounts',
        'columnAttributeMap' => [
          'role' => 'contactRole',
          'isInactive' => 'contactIsInactive'
        ],
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
        'indexes' => [
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'accountId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'accountId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_ACCOUNT_ID_CONTACT_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Account'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdAtId' => [
        'unique' => true,
        'columns' => [
          0 => 'createdAt',
          1 => 'id'
        ],
        'key' => 'UNIQ_CREATED_AT_ID'
      ],
      'name' => [
        'columns' => [
          0 => 'name',
          1 => 'deleted'
        ],
        'key' => 'IDX_NAME'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'Call' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Planned',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateStart' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'dateEnd' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'duration' => [
        'type' => 'int',
        'notStorable' => true,
        'default' => 300,
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
        ],
        'fieldType' => 'int',
        'len' => 11
      ],
      'reminders' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'direction' => [
        'type' => 'varchar',
        'default' => 'Outbound',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'uid' => [
        'type' => 'varchar',
        'len' => 255,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'acceptanceStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
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
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'phoneNumbersMap' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'fieldType' => 'jsonObject'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'users',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'contacts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'contactsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'leadsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'leads',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'leadsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'leadsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ]
    ],
    'relations' => [
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'calls'
      ],
      'leads' => [
        'type' => 'manyMany',
        'entity' => 'Lead',
        'relationName' => 'callLead',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'callId',
          1 => 'leadId'
        ],
        'foreign' => 'calls',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'callId_leadId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'callId',
              1 => 'leadId'
            ],
            'key' => 'UNIQ_CALL_ID_LEAD_ID'
          ]
        ]
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'callContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'callId',
          1 => 'contactId'
        ],
        'foreign' => 'calls',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'callId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'callId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_CALL_ID_CONTACT_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'callUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'callId',
          1 => 'userId'
        ],
        'foreign' => 'calls',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'callId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'callId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_CALL_ID_USER_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Call'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'dateStartStatus' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'status'
        ],
        'key' => 'IDX_DATE_START_STATUS'
      ],
      'dateStart' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'deleted'
        ],
        'key' => 'IDX_DATE_START'
      ],
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'assignedUserStatus' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'status'
        ],
        'key' => 'IDX_ASSIGNED_USER_STATUS'
      ],
      'uid' => [
        'type' => 'index',
        'columns' => [
          0 => 'uid'
        ],
        'key' => 'IDX_UID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'dateStart',
      'order' => 'DESC'
    ]
  ],
  'Campaign' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Planning',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 64,
        'default' => 'Email',
        'fieldType' => 'varchar'
      ],
      'startDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'endDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'sentCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'openedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'clickedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'optedInCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'optedOutCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'bouncedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'hardBouncedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'softBouncedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'leadCreatedCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'openedPercentage' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'clickedPercentage' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'optedOutPercentage' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'bouncedPercentage' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'revenue' => [
        'type' => 'float',
        'notStorable' => true,
        'fieldType' => 'currency',
        'attributeRole' => 'value'
      ],
      'budget' => [
        'type' => 'float',
        'fieldType' => 'currency',
        'attributeRole' => 'value',
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(budget, budgetCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'budgetCurrencyRecordRate',
              2 => [
                'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'budgetCurrencyRecordRate.rate'
          ]
        ]
      ],
      'mailMergeOnlyWithAddress' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => true,
        'fieldType' => 'bool'
      ],
      'revenueCurrency' => [
        'type' => 'varchar',
        'len' => 3,
        'notStorable' => true,
        'fieldType' => 'currency',
        'attributeRole' => 'currency'
      ],
      'budgetCurrency' => [
        'type' => 'varchar',
        'len' => 3,
        'fieldType' => 'currency',
        'attributeRole' => 'currency'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'targetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'excludingTargetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'excludingTargetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'excludingTargetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'budgetConverted' => [
        'type' => 'float',
        'select' => [
          'select' => 'MUL:(budget, budgetCurrencyRecordRate.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'budgetCurrencyRecordRate',
              2 => [
                'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'MUL:({alias}.budget, budgetCurrencyRecordRateCampaign{alias}Foreign.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'budgetCurrencyRecordRateCampaign{alias}Foreign',
              2 => [
                'budgetCurrencyRecordRateCampaign{alias}Foreign.id:' => '{alias}.budgetCurrency'
              ]
            ]
          ]
        ],
        'where' => [
          '=' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          '>' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)>' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          '<' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)<' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          '>=' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)>=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          '<=' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)<=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'MUL:(budget, budgetCurrencyRecordRate.rate)!=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'budgetCurrencyRecordRate',
                2 => [
                  'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'whereClause' => [
              'budget=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'budget!=' => NULL
            ]
          ]
        ],
        'notStorable' => true,
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(budget, budgetCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'budgetCurrencyRecordRate',
              2 => [
                'budgetCurrencyRecordRate.id:' => 'budgetCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'budgetCurrencyRecordRate.rate'
          ]
        ],
        'attributeRole' => 'valueConverted',
        'fieldType' => 'currency'
      ],
      'contactsTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'contactsTemplateName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'contactsTemplate',
        'foreign' => 'name'
      ],
      'leadsTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'leadsTemplateName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'leadsTemplate',
        'foreign' => 'name'
      ],
      'accountsTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountsTemplateName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'accountsTemplate',
        'foreign' => 'name'
      ],
      'usersTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'usersTemplateName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'usersTemplate',
        'foreign' => 'name'
      ],
      'massEmailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'massEmailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'trackingUrlsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'trackingUrlsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'usersTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'Template',
        'key' => 'usersTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'accountsTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'Template',
        'key' => 'accountsTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'leadsTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'Template',
        'key' => 'leadsTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'contactsTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'Template',
        'key' => 'contactsTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'massEmails' => [
        'type' => 'hasMany',
        'entity' => 'MassEmail',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'trackingUrls' => [
        'type' => 'hasMany',
        'entity' => 'CampaignTrackingUrl',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'campaignLogRecords' => [
        'type' => 'hasMany',
        'entity' => 'CampaignLogRecord',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'opportunities' => [
        'type' => 'hasMany',
        'entity' => 'Opportunity',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'leads' => [
        'type' => 'hasMany',
        'entity' => 'Lead',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'contacts' => [
        'type' => 'hasMany',
        'entity' => 'Contact',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'accounts' => [
        'type' => 'hasMany',
        'entity' => 'Account',
        'foreignKey' => 'campaignId',
        'foreign' => 'campaign'
      ],
      'excludingTargetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'campaignTargetListExcluding',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'campaignId',
          1 => 'targetListId'
        ],
        'foreign' => 'campaignsExcluding',
        'indexes' => [
          'campaignId' => [
            'columns' => [
              0 => 'campaignId'
            ],
            'key' => 'IDX_CAMPAIGN_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'campaignId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'campaignId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_CAMPAIGN_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'campaignTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'campaignId',
          1 => 'targetListId'
        ],
        'foreign' => 'campaigns',
        'indexes' => [
          'campaignId' => [
            'columns' => [
              0 => 'campaignId'
            ],
            'key' => 'IDX_CAMPAIGN_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'campaignId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'campaignId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_CAMPAIGN_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Campaign'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'contactsTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'contactsTemplateId'
        ],
        'key' => 'IDX_CONTACTS_TEMPLATE_ID'
      ],
      'leadsTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'leadsTemplateId'
        ],
        'key' => 'IDX_LEADS_TEMPLATE_ID'
      ],
      'accountsTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountsTemplateId'
        ],
        'key' => 'IDX_ACCOUNTS_TEMPLATE_ID'
      ],
      'usersTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'usersTemplateId'
        ],
        'key' => 'IDX_USERS_TEMPLATE_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'CampaignLogRecord' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'action' => [
        'type' => 'varchar',
        'len' => 50,
        'fieldType' => 'varchar'
      ],
      'actionDate' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'data' => [
        'type' => 'jsonObject',
        'fieldType' => 'jsonObject'
      ],
      'stringData' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'stringAdditionalData' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'application' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'Espo',
        'fieldType' => 'varchar'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isTest' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'objectId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'object',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'objectType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'object',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'objectName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'object',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'queueItemId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'queueItemName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'queueItem',
        'foreign' => 'name'
      ]
    ],
    'relations' => [
      'object' => [
        'type' => 'belongsToParent',
        'key' => 'objectId',
        'foreign' => NULL
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => NULL
      ],
      'queueItem' => [
        'type' => 'belongsTo',
        'entity' => 'EmailQueueItem',
        'key' => 'queueItemId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'noJoin' => true
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'campaignLogRecords'
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'actionDate' => [
        'columns' => [
          0 => 'actionDate',
          1 => 'deleted'
        ],
        'key' => 'IDX_ACTION_DATE'
      ],
      'action' => [
        'columns' => [
          0 => 'action',
          1 => 'deleted'
        ],
        'key' => 'IDX_ACTION'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'object' => [
        'type' => 'index',
        'columns' => [
          0 => 'objectId',
          1 => 'objectType'
        ],
        'key' => 'IDX_OBJECT'
      ],
      'queueItemId' => [
        'type' => 'index',
        'columns' => [
          0 => 'queueItemId'
        ],
        'key' => 'IDX_QUEUE_ITEM_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'CampaignTrackingUrl' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'url' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'urlToUse' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'action' => [
        'type' => 'varchar',
        'len' => 12,
        'default' => 'Redirect',
        'fieldType' => 'varchar'
      ],
      'message' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ]
    ],
    'relations' => [
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'trackingUrls'
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'Case' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'number' => [
        'type' => 'int',
        'autoincrement' => true,
        'unique' => true,
        'index' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'New',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'priority' => [
        'type' => 'varchar',
        'default' => 'Normal',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'isInternal' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'leadId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'leadName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'lead',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'contactName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'contact',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'contacts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'inboundEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'inboundEmailName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'inboundEmail',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'originalEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'originalEmail',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'originalEmailName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'articlesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'articlesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'articles' => [
        'type' => 'manyMany',
        'entity' => 'KnowledgeBaseArticle',
        'relationName' => 'caseKnowledgeBaseArticle',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'caseId',
          1 => 'knowledgeBaseArticleId'
        ],
        'foreign' => 'cases',
        'indexes' => [
          'caseId' => [
            'columns' => [
              0 => 'caseId'
            ],
            'key' => 'IDX_CASE_ID'
          ],
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'caseId_knowledgeBaseArticleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'caseId',
              1 => 'knowledgeBaseArticleId'
            ],
            'key' => 'UNIQ_CASE_ID_KNOWLEDGE_BASE_ARTICLE_ID'
          ]
        ]
      ],
      'emails' => [
        'type' => 'hasChildren',
        'entity' => 'Email',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'tasks' => [
        'type' => 'hasChildren',
        'entity' => 'Task',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'calls' => [
        'type' => 'hasChildren',
        'entity' => 'Call',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'meetings' => [
        'type' => 'hasChildren',
        'entity' => 'Meeting',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'caseContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'caseId',
          1 => 'contactId'
        ],
        'foreign' => 'cases',
        'indexes' => [
          'caseId' => [
            'columns' => [
              0 => 'caseId'
            ],
            'key' => 'IDX_CASE_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'caseId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'caseId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_CASE_ID_CONTACT_ID'
          ]
        ]
      ],
      'contact' => [
        'type' => 'belongsTo',
        'entity' => 'Contact',
        'key' => 'contactId',
        'foreignKey' => 'id',
        'foreign' => 'casesPrimary',
        'deferredLoad' => true
      ],
      'lead' => [
        'type' => 'belongsTo',
        'entity' => 'Lead',
        'key' => 'leadId',
        'foreignKey' => 'id',
        'foreign' => 'cases',
        'deferredLoad' => true
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => 'cases',
        'deferredLoad' => true
      ],
      'inboundEmail' => [
        'type' => 'belongsTo',
        'entity' => 'InboundEmail',
        'key' => 'inboundEmailId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Case'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'indexes' => [
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'assignedUserStatus' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'status'
        ],
        'key' => 'IDX_ASSIGNED_USER_STATUS'
      ],
      'system_fullTextSearch' => [
        'columns' => [
          0 => 'name',
          1 => 'description'
        ],
        'flags' => [
          0 => 'fulltext'
        ],
        'key' => 'IDX_SYSTEM_FULL_TEXT_SEARCH'
      ],
      'number' => [
        'type' => 'unique',
        'columns' => [
          0 => 'number'
        ],
        'key' => 'UNIQ_NUMBER'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'leadId' => [
        'type' => 'index',
        'columns' => [
          0 => 'leadId'
        ],
        'key' => 'IDX_LEAD_ID'
      ],
      'contactId' => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'key' => 'IDX_CONTACT_ID'
      ],
      'inboundEmailId' => [
        'type' => 'index',
        'columns' => [
          0 => 'inboundEmailId'
        ],
        'key' => 'IDX_INBOUND_EMAIL_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'fullTextSearchColumnList' => [
      0 => 'name',
      1 => 'description'
    ],
    'collection' => [
      'orderBy' => 'number',
      'order' => 'DESC'
    ]
  ],
  'Contact' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'personName',
        'notStorable' => true,
        'select' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:(firstName, \'\'), \' \', IFNULL:(lastName, \'\'))), \'\')'
        ],
        'selectForeign' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:({alias}.firstName, \'\'), \' \', IFNULL:({alias}.lastName, \'\'))), \'\')'
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'OR' => [
                'firstName*' => '{value}',
                'lastName*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)*' => '{value}'
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'AND' => [
                'firstName!*' => '{value}',
                'lastName!*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)!*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)!*' => '{value}'
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'OR' => [
                'firstName' => '{value}',
                'lastName' => '{value}',
                'CONCAT:(firstName, \' \', lastName)' => '{value}',
                'CONCAT:(lastName, \' \', firstName)' => '{value}'
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'firstName',
              1 => '{direction}'
            ],
            1 => [
              0 => 'lastName',
              1 => '{direction}'
            ]
          ]
        ]
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'salutationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'firstName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'lastName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'accountAnyId' => [
        'type' => 'varchar',
        'notStorable' => true,
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
        ],
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'title' => [
        'type' => 'varchar',
        'len' => 100,
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
        'fieldType' => 'varchar'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'email',
        'select' => [
          'select' => 'emailAddresses.name',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressContact{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressContact{alias}ForeignMiddle',
              2 => [
                'emailAddressContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressContact{alias}ForeignMiddle.primary' => true,
                'emailAddressContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressContact{alias}Foreign',
              2 => [
                'emailAddressContact{alias}Foreign.id:' => 'emailAddressContact{alias}ForeignMiddle.emailAddressId',
                'emailAddressContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'EQUAL:(emailAddressesMultiple.lower, LOWER:({value})):' => NULL
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'EQUAL:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'emailAddressesMultiple.lower=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.lower',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.lower'
          ]
        ]
      ],
      'phoneNumber' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'phone',
        'select' => [
          'select' => 'phoneNumbers.name',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberContact{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberContact{alias}ForeignMiddle',
              2 => [
                'phoneNumberContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberContact{alias}ForeignMiddle.primary' => true,
                'phoneNumberContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberContact{alias}Foreign',
              2 => [
                'phoneNumberContact{alias}Foreign.id:' => 'phoneNumberContact{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => '{value}'
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.name!=' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.name',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.name'
          ]
        ]
      ],
      'doNotCall' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'addressStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'fieldType' => 'text'
      ],
      'addressCity' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressState' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'accountRole' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'accountIsInactive' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
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
        'fieldType' => 'bool',
        'default' => false
      ],
      'accountType' => [
        'type' => 'foreign',
        'relation' => 'account',
        'foreign' => 'type',
        'fieldType' => 'foreign',
        'foreignType' => 'varchar'
      ],
      'acceptanceStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusMeetings' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'meetings',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusCalls' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'calls',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'hasPortalUser' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'select' => [
          'select' => 'IS_NOT_NULL:(portalUser.id)',
          'leftJoins' => [
            0 => [
              0 => 'portalUser',
              1 => 'portalUser'
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
        'fieldType' => 'bool',
        'default' => false
      ],
      'targetListIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'middleName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'emailAddressIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.optOut',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressContact{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressContact{alias}ForeignMiddle',
              2 => [
                'emailAddressContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressContact{alias}ForeignMiddle.primary' => true,
                'emailAddressContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressContact{alias}Foreign',
              2 => [
                'emailAddressContact{alias}Foreign.id:' => 'emailAddressContact{alias}ForeignMiddle.emailAddressId',
                'emailAddressContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.optOut=' => true
              ],
              1 => [
                'emailAddresses.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.optOut=' => false
                ],
                1 => [
                  'emailAddresses.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.optOut'
          ]
        ],
        'default' => false
      ],
      'emailAddressIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.invalid',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressContact{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressContact{alias}ForeignMiddle',
              2 => [
                'emailAddressContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressContact{alias}ForeignMiddle.primary' => true,
                'emailAddressContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressContact{alias}Foreign',
              2 => [
                'emailAddressContact{alias}Foreign.id:' => 'emailAddressContact{alias}ForeignMiddle.emailAddressId',
                'emailAddressContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.invalid=' => true
              ],
              1 => [
                'emailAddresses.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.invalid=' => false
                ],
                1 => [
                  'emailAddresses.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.invalid'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.optOut',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberContact{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberContact{alias}ForeignMiddle',
              2 => [
                'phoneNumberContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberContact{alias}ForeignMiddle.primary' => true,
                'phoneNumberContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberContact{alias}Foreign',
              2 => [
                'phoneNumberContact{alias}Foreign.id:' => 'phoneNumberContact{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.optOut=' => true
              ],
              1 => [
                'phoneNumbers.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.optOut=' => false
                ],
                1 => [
                  'phoneNumbers.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.optOut'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.invalid',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberContact{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberContact{alias}ForeignMiddle',
              2 => [
                'phoneNumberContact{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberContact{alias}ForeignMiddle.primary' => true,
                'phoneNumberContact{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberContact{alias}Foreign',
              2 => [
                'phoneNumberContact{alias}Foreign.id:' => 'phoneNumberContact{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberContact{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.invalid=' => true
              ],
              1 => [
                'phoneNumbers.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.invalid=' => false
                ],
                1 => [
                  'phoneNumbers.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.invalid'
          ]
        ],
        'default' => false
      ],
      'addressMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'emailAddressData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isEmailAddressData' => true,
        'field' => 'emailAddress'
      ],
      'phoneNumberData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isPhoneNumberData' => true,
        'field' => 'phoneNumber'
      ],
      'phoneNumberNumeric' => [
        'type' => 'varchar',
        'notStorable' => true,
        'notExportable' => true,
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.numeric=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Contact'
                ]
              ]
            ]
          ]
        ]
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'accounts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'accountsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'role' => 'contactRole',
          'isInactive' => 'contactIsInactive'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'opportunityRole' => [
        'type' => 'varchar',
        'notStorable' => true,
        'where' => [
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
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
                'from' => 'ContactOpportunity',
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
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
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
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'ContactOpportunity',
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
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
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
                'from' => 'ContactOpportunity',
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
                      0 => 'opportunities',
                      1 => 'm',
                      2 => NULL,
                      3 => [
                        'onlyMiddle' => true
                      ]
                    ]
                  ],
                  'whereClause' => [
                    'm.role!=' => NULL,
                    'sq.id:' => 'contact.id'
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
                    0 => 'opportunities',
                    1 => 'm',
                    2 => NULL,
                    3 => [
                      'onlyMiddle' => true
                    ]
                  ]
                ],
                'whereClause' => [
                  'm.role!=' => NULL,
                  'sq.id:' => 'contact.id'
                ]
              ]
            ]
          ]
        ]
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'targetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'targetList',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'targetListName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'portalUserId' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'id',
        'fieldType' => 'linkOne',
        'relation' => 'portalUser',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'portalUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkOne',
        'relation' => 'portalUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'originalLeadId' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'id',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'originalLeadName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'originalEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'originalEmail',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'originalEmailName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'isStarred' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'readOnly' => true,
        'default' => false
      ],
      'tasksPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesPrimaryIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesPrimaryNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'relationName' => 'entityEmailAddress',
        'midKeys' => [
          0 => 'entityId',
          1 => 'emailAddressId'
        ],
        'conditions' => [
          'entityType' => 'Contact'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'entityId_emailAddressId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'emailAddressId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'phoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'relationName' => 'entityPhoneNumber',
        'midKeys' => [
          0 => 'entityId',
          1 => 'phoneNumberId'
        ],
        'conditions' => [
          'entityType' => 'Contact'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'entityId_phoneNumberId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'phoneNumberId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'tasksPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Task',
        'foreignKey' => 'contactId',
        'foreign' => 'contact'
      ],
      'documents' => [
        'type' => 'manyMany',
        'entity' => 'Document',
        'relationName' => 'contactDocument',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'documentId'
        ],
        'foreign' => 'contacts',
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'contactId_documentId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'documentId'
            ],
            'key' => 'UNIQ_CONTACT_ID_DOCUMENT_ID'
          ]
        ]
      ],
      'originalLead' => [
        'type' => 'hasOne',
        'entity' => 'Lead',
        'foreignKey' => 'createdContactId',
        'foreign' => 'createdContact'
      ],
      'portalUser' => [
        'type' => 'hasOne',
        'entity' => 'User',
        'foreignKey' => 'contactId',
        'foreign' => 'contact'
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'contactTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'targetListId'
        ],
        'foreign' => 'contacts',
        'columnAttributeMap' => [
          'optedOut' => 'targetListIsOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'contactId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_CONTACT_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'campaignLogRecords' => [
        'type' => 'hasChildren',
        'entity' => 'CampaignLogRecord',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'contacts'
      ],
      'emails' => [
        'type' => 'hasChildren',
        'entity' => 'Email',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'tasks' => [
        'type' => 'hasChildren',
        'entity' => 'Task',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'calls' => [
        'type' => 'manyMany',
        'entity' => 'Call',
        'relationName' => 'callContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'callId'
        ],
        'foreign' => 'contacts',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'contactId_callId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'callId'
            ],
            'key' => 'UNIQ_CONTACT_ID_CALL_ID'
          ]
        ]
      ],
      'meetings' => [
        'type' => 'manyMany',
        'entity' => 'Meeting',
        'relationName' => 'contactMeeting',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'meetingId'
        ],
        'foreign' => 'contacts',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'contactId_meetingId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'meetingId'
            ],
            'key' => 'UNIQ_CONTACT_ID_MEETING_ID'
          ]
        ]
      ],
      'cases' => [
        'type' => 'manyMany',
        'entity' => 'Case',
        'relationName' => 'caseContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'caseId'
        ],
        'foreign' => 'contacts',
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'caseId' => [
            'columns' => [
              0 => 'caseId'
            ],
            'key' => 'IDX_CASE_ID'
          ],
          'contactId_caseId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'caseId'
            ],
            'key' => 'UNIQ_CONTACT_ID_CASE_ID'
          ]
        ]
      ],
      'casesPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Case',
        'foreignKey' => 'contactId',
        'foreign' => 'contact'
      ],
      'opportunitiesPrimary' => [
        'type' => 'hasMany',
        'entity' => 'Opportunity',
        'foreignKey' => 'contactId',
        'foreign' => 'contact'
      ],
      'opportunities' => [
        'type' => 'manyMany',
        'entity' => 'Opportunity',
        'relationName' => 'contactOpportunity',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'opportunityId'
        ],
        'foreign' => 'contacts',
        'columnAttributeMap' => [
          'role' => 'opportunityRole'
        ],
        'additionalColumns' => [
          'role' => [
            'type' => 'varchar',
            'len' => 50
          ]
        ],
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'opportunityId' => [
            'columns' => [
              0 => 'opportunityId'
            ],
            'key' => 'IDX_OPPORTUNITY_ID'
          ],
          'contactId_opportunityId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'opportunityId'
            ],
            'key' => 'UNIQ_CONTACT_ID_OPPORTUNITY_ID'
          ]
        ]
      ],
      'accounts' => [
        'type' => 'manyMany',
        'entity' => 'Account',
        'relationName' => 'accountContact',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'contactId',
          1 => 'accountId'
        ],
        'foreign' => 'contacts',
        'columnAttributeMap' => [
          'role' => 'accountRole',
          'isInactive' => 'accountIsInactive'
        ],
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
        'indexes' => [
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'contactId_accountId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'contactId',
              1 => 'accountId'
            ],
            'key' => 'UNIQ_CONTACT_ID_ACCOUNT_ID'
          ]
        ]
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => NULL,
        'deferredLoad' => true
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Contact'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdAtId' => [
        'unique' => true,
        'columns' => [
          0 => 'createdAt',
          1 => 'id'
        ],
        'key' => 'UNIQ_CREATED_AT_ID'
      ],
      'firstName' => [
        'columns' => [
          0 => 'firstName',
          1 => 'deleted'
        ],
        'key' => 'IDX_FIRST_NAME'
      ],
      'name' => [
        'columns' => [
          0 => 'firstName',
          1 => 'lastName'
        ],
        'key' => 'IDX_NAME'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'Document' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Active',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'type' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'publishDate' => [
        'type' => 'date',
        'fieldType' => 'date'
      ],
      'expirationDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'fileId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => false,
        'notNull' => false
      ],
      'fileName' => [
        'type' => 'foreign',
        'relation' => 'file',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'accounts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'folderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'folderName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'folder',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'opportunitiesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'file' => [
        'type' => 'belongsTo',
        'entity' => 'Attachment',
        'key' => 'fileId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'folder' => [
        'type' => 'belongsTo',
        'entity' => 'DocumentFolder',
        'key' => 'folderId',
        'foreignKey' => 'id',
        'foreign' => 'documents'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Document'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'contactDocument',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'documentId',
          1 => 'contactId'
        ],
        'foreign' => 'documents',
        'indexes' => [
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'documentId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'documentId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_DOCUMENT_ID_CONTACT_ID'
          ]
        ]
      ],
      'leads' => [
        'type' => 'manyMany',
        'entity' => 'Lead',
        'relationName' => 'documentLead',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'documentId',
          1 => 'leadId'
        ],
        'foreign' => 'documents',
        'indexes' => [
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'documentId_leadId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'documentId',
              1 => 'leadId'
            ],
            'key' => 'UNIQ_DOCUMENT_ID_LEAD_ID'
          ]
        ]
      ],
      'opportunities' => [
        'type' => 'manyMany',
        'entity' => 'Opportunity',
        'relationName' => 'documentOpportunity',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'documentId',
          1 => 'opportunityId'
        ],
        'foreign' => 'documents',
        'indexes' => [
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'opportunityId' => [
            'columns' => [
              0 => 'opportunityId'
            ],
            'key' => 'IDX_OPPORTUNITY_ID'
          ],
          'documentId_opportunityId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'documentId',
              1 => 'opportunityId'
            ],
            'key' => 'UNIQ_DOCUMENT_ID_OPPORTUNITY_ID'
          ]
        ]
      ],
      'accounts' => [
        'type' => 'manyMany',
        'entity' => 'Account',
        'relationName' => 'accountDocument',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'documentId',
          1 => 'accountId'
        ],
        'foreign' => 'documents',
        'indexes' => [
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'documentId_accountId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'documentId',
              1 => 'accountId'
            ],
            'key' => 'UNIQ_DOCUMENT_ID_ACCOUNT_ID'
          ]
        ]
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'folderId' => [
        'type' => 'index',
        'columns' => [
          0 => 'folderId'
        ],
        'key' => 'IDX_FOLDER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'DocumentFolder' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'childList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'parentName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'parent',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'documentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'documents' => [
        'type' => 'hasMany',
        'entity' => 'Document',
        'foreignKey' => 'folderId',
        'foreign' => 'folder'
      ],
      'children' => [
        'type' => 'hasMany',
        'entity' => 'DocumentFolder',
        'foreignKey' => 'parentId',
        'foreign' => 'parent'
      ],
      'parent' => [
        'type' => 'belongsTo',
        'entity' => 'DocumentFolder',
        'key' => 'parentId',
        'foreignKey' => 'id',
        'foreign' => 'children'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'DocumentFolder'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'parentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId'
        ],
        'key' => 'IDX_PARENT_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'name',
      'order' => 'ASC'
    ]
  ],
  'EmailQueueItem' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'attemptCount' => [
        'type' => 'int',
        'default' => 0,
        'fieldType' => 'int',
        'len' => 11
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'sentAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'index' => true,
        'fieldType' => 'datetime'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'isTest' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'massEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'massEmailName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'massEmail',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'target',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'targetType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'target',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'targetName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'target',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'target' => [
        'type' => 'belongsToParent',
        'key' => 'targetId',
        'foreign' => NULL
      ],
      'massEmail' => [
        'type' => 'belongsTo',
        'entity' => 'MassEmail',
        'key' => 'massEmailId',
        'foreignKey' => 'id',
        'foreign' => 'queueItems'
      ]
    ],
    'indexes' => [
      'sentAt' => [
        'type' => 'index',
        'columns' => [
          0 => 'sentAt'
        ],
        'key' => 'IDX_SENT_AT'
      ],
      'massEmailId' => [
        'type' => 'index',
        'columns' => [
          0 => 'massEmailId'
        ],
        'key' => 'IDX_MASS_EMAIL_ID'
      ],
      'target' => [
        'type' => 'index',
        'columns' => [
          0 => 'targetId',
          1 => 'targetType'
        ],
        'key' => 'IDX_TARGET'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'KnowledgeBaseArticle' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Draft',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'language' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'type' => [
        'type' => 'varchar',
        'default' => 'Article',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'publishDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'expirationDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'body' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'bodyPlain' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'portalsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'portals',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'portalsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'categoriesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'categories',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'categoriesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'casesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'categories' => [
        'type' => 'manyMany',
        'entity' => 'KnowledgeBaseCategory',
        'relationName' => 'knowledgeBaseArticleKnowledgeBaseCategory',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'knowledgeBaseArticleId',
          1 => 'knowledgeBaseCategoryId'
        ],
        'foreign' => 'articles',
        'indexes' => [
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'knowledgeBaseCategoryId' => [
            'columns' => [
              0 => 'knowledgeBaseCategoryId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_CATEGORY_ID'
          ],
          'knowledgeBaseArticleId_knowledgeBaseCategoryId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'knowledgeBaseArticleId',
              1 => 'knowledgeBaseCategoryId'
            ],
            'key' => 'UNIQ_KNOWLEDGE_BASE_ARTICLE_ID_KNOWLEDGE_BASE_CATEGORY_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'KnowledgeBaseArticle'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'portals' => [
        'type' => 'manyMany',
        'entity' => 'Portal',
        'relationName' => 'knowledgeBaseArticlePortal',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'knowledgeBaseArticleId',
          1 => 'portalId'
        ],
        'foreign' => 'articles',
        'indexes' => [
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'portalId' => [
            'columns' => [
              0 => 'portalId'
            ],
            'key' => 'IDX_PORTAL_ID'
          ],
          'knowledgeBaseArticleId_portalId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'knowledgeBaseArticleId',
              1 => 'portalId'
            ],
            'key' => 'UNIQ_KNOWLEDGE_BASE_ARTICLE_ID_PORTAL_ID'
          ]
        ]
      ],
      'cases' => [
        'type' => 'manyMany',
        'entity' => 'Case',
        'relationName' => 'caseKnowledgeBaseArticle',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'knowledgeBaseArticleId',
          1 => 'caseId'
        ],
        'foreign' => 'articles',
        'indexes' => [
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'caseId' => [
            'columns' => [
              0 => 'caseId'
            ],
            'key' => 'IDX_CASE_ID'
          ],
          'knowledgeBaseArticleId_caseId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'knowledgeBaseArticleId',
              1 => 'caseId'
            ],
            'key' => 'UNIQ_KNOWLEDGE_BASE_ARTICLE_ID_CASE_ID'
          ]
        ]
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'fullTextSearchColumnList' => [
      0 => 'name',
      1 => 'bodyPlain'
    ],
    'indexes' => [
      'system_fullTextSearch' => [
        'columns' => [
          0 => 'name',
          1 => 'bodyPlain'
        ],
        'flags' => [
          0 => 'fulltext'
        ],
        'key' => 'IDX_SYSTEM_FULL_TEXT_SEARCH'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'order',
      'order' => 'ASC'
    ]
  ],
  'KnowledgeBaseCategory' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'childList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'parentName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'parent',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'articlesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'articlesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'articles' => [
        'type' => 'manyMany',
        'entity' => 'KnowledgeBaseArticle',
        'relationName' => 'knowledgeBaseArticleKnowledgeBaseCategory',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'knowledgeBaseCategoryId',
          1 => 'knowledgeBaseArticleId'
        ],
        'foreign' => 'categories',
        'indexes' => [
          'knowledgeBaseCategoryId' => [
            'columns' => [
              0 => 'knowledgeBaseCategoryId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_CATEGORY_ID'
          ],
          'knowledgeBaseArticleId' => [
            'columns' => [
              0 => 'knowledgeBaseArticleId'
            ],
            'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
          ],
          'knowledgeBaseCategoryId_knowledgeBaseArticleId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'knowledgeBaseCategoryId',
              1 => 'knowledgeBaseArticleId'
            ],
            'key' => 'UNIQ_KNOWLEDGE_BASE_CATEGORY_ID_KNOWLEDGE_BASE_ARTICLE_ID'
          ]
        ]
      ],
      'children' => [
        'type' => 'hasMany',
        'entity' => 'KnowledgeBaseCategory',
        'foreignKey' => 'parentId',
        'foreign' => 'parent'
      ],
      'parent' => [
        'type' => 'belongsTo',
        'entity' => 'KnowledgeBaseCategory',
        'key' => 'parentId',
        'foreignKey' => 'id',
        'foreign' => 'children'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'KnowledgeBaseCategory'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'parentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId'
        ],
        'key' => 'IDX_PARENT_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'parentId',
      'order' => 'ASC'
    ]
  ],
  'Lead' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'dependeeAttributeList' => [
          0 => 'emailAddress',
          1 => 'phoneNumber',
          2 => 'accountName'
        ],
        'fieldType' => 'personName',
        'notStorable' => true,
        'select' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:(firstName, \'\'), \' \', IFNULL:(lastName, \'\'))), \'\')'
        ],
        'selectForeign' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:({alias}.firstName, \'\'), \' \', IFNULL:({alias}.lastName, \'\'))), \'\')'
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'OR' => [
                'firstName*' => '{value}',
                'lastName*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)*' => '{value}'
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'AND' => [
                'firstName!*' => '{value}',
                'lastName!*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)!*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)!*' => '{value}'
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'OR' => [
                'firstName' => '{value}',
                'lastName' => '{value}',
                'CONCAT:(firstName, \' \', lastName)' => '{value}',
                'CONCAT:(lastName, \' \', firstName)' => '{value}'
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'firstName',
              1 => '{direction}'
            ],
            1 => [
              0 => 'lastName',
              1 => '{direction}'
            ]
          ]
        ]
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'salutationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'firstName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'lastName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'title' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'New',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'source' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'industry' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'opportunityAmount' => [
        'type' => 'float',
        'fieldType' => 'currency',
        'attributeRole' => 'value',
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'opportunityAmountCurrencyRecordRate',
              2 => [
                'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'opportunityAmountCurrencyRecordRate.rate'
          ]
        ]
      ],
      'website' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'addressStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'fieldType' => 'text'
      ],
      'addressCity' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressState' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'email',
        'select' => [
          'select' => 'emailAddresses.name',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressLead{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressLead{alias}ForeignMiddle',
              2 => [
                'emailAddressLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressLead{alias}ForeignMiddle.primary' => true,
                'emailAddressLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressLead{alias}Foreign',
              2 => [
                'emailAddressLead{alias}Foreign.id:' => 'emailAddressLead{alias}ForeignMiddle.emailAddressId',
                'emailAddressLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'EQUAL:(emailAddressesMultiple.lower, LOWER:({value})):' => NULL
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'EQUAL:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'emailAddressesMultiple.lower=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.lower',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.lower'
          ]
        ]
      ],
      'phoneNumber' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'phone',
        'select' => [
          'select' => 'phoneNumbers.name',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberLead{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberLead{alias}ForeignMiddle',
              2 => [
                'phoneNumberLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberLead{alias}ForeignMiddle.primary' => true,
                'phoneNumberLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberLead{alias}Foreign',
              2 => [
                'phoneNumberLead{alias}Foreign.id:' => 'phoneNumberLead{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => '{value}'
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.name!=' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.name',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.name'
          ]
        ]
      ],
      'doNotCall' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'convertedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'accountName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusMeetings' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'meetings',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'acceptanceStatusCalls' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'relation' => 'calls',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'targetListIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'middleName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'opportunityAmountCurrency' => [
        'type' => 'varchar',
        'len' => 3,
        'fieldType' => 'currency',
        'attributeRole' => 'currency'
      ],
      'addressMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'emailAddressIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.optOut',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressLead{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressLead{alias}ForeignMiddle',
              2 => [
                'emailAddressLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressLead{alias}ForeignMiddle.primary' => true,
                'emailAddressLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressLead{alias}Foreign',
              2 => [
                'emailAddressLead{alias}Foreign.id:' => 'emailAddressLead{alias}ForeignMiddle.emailAddressId',
                'emailAddressLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.optOut=' => true
              ],
              1 => [
                'emailAddresses.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.optOut=' => false
                ],
                1 => [
                  'emailAddresses.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.optOut'
          ]
        ],
        'default' => false
      ],
      'emailAddressIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.invalid',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressLead{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressLead{alias}ForeignMiddle',
              2 => [
                'emailAddressLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressLead{alias}ForeignMiddle.primary' => true,
                'emailAddressLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressLead{alias}Foreign',
              2 => [
                'emailAddressLead{alias}Foreign.id:' => 'emailAddressLead{alias}ForeignMiddle.emailAddressId',
                'emailAddressLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.invalid=' => true
              ],
              1 => [
                'emailAddresses.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.invalid=' => false
                ],
                1 => [
                  'emailAddresses.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.invalid'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.optOut',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberLead{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberLead{alias}ForeignMiddle',
              2 => [
                'phoneNumberLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberLead{alias}ForeignMiddle.primary' => true,
                'phoneNumberLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberLead{alias}Foreign',
              2 => [
                'phoneNumberLead{alias}Foreign.id:' => 'phoneNumberLead{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.optOut=' => true
              ],
              1 => [
                'phoneNumbers.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.optOut=' => false
                ],
                1 => [
                  'phoneNumbers.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.optOut'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.invalid',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberLead{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberLead{alias}ForeignMiddle',
              2 => [
                'phoneNumberLead{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberLead{alias}ForeignMiddle.primary' => true,
                'phoneNumberLead{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberLead{alias}Foreign',
              2 => [
                'phoneNumberLead{alias}Foreign.id:' => 'phoneNumberLead{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberLead{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.invalid=' => true
              ],
              1 => [
                'phoneNumbers.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.invalid=' => false
                ],
                1 => [
                  'phoneNumbers.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.invalid'
          ]
        ],
        'default' => false
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'opportunityAmountConverted' => [
        'type' => 'float',
        'select' => [
          'select' => 'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'opportunityAmountCurrencyRecordRate',
              2 => [
                'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'MUL:({alias}.opportunityAmount, opportunityAmountCurrencyRecordRateLead{alias}Foreign.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'opportunityAmountCurrencyRecordRateLead{alias}Foreign',
              2 => [
                'opportunityAmountCurrencyRecordRateLead{alias}Foreign.id:' => '{alias}.opportunityAmountCurrency'
              ]
            ]
          ]
        ],
        'where' => [
          '=' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          '>' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)>' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          '<' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)<' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          '>=' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)>=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          '<=' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)<=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)!=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'opportunityAmountCurrencyRecordRate',
                2 => [
                  'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'whereClause' => [
              'opportunityAmount=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'opportunityAmount!=' => NULL
            ]
          ]
        ],
        'notStorable' => true,
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(opportunityAmount, opportunityAmountCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'opportunityAmountCurrencyRecordRate',
              2 => [
                'opportunityAmountCurrencyRecordRate.id:' => 'opportunityAmountCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'opportunityAmountCurrencyRecordRate.rate'
          ]
        ],
        'attributeRole' => 'valueConverted',
        'fieldType' => 'currency'
      ],
      'emailAddressData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isEmailAddressData' => true,
        'field' => 'emailAddress'
      ],
      'phoneNumberData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isPhoneNumberData' => true,
        'field' => 'phoneNumber'
      ],
      'phoneNumberNumeric' => [
        'type' => 'varchar',
        'notStorable' => true,
        'notExportable' => true,
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.numeric=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Lead'
                ]
              ]
            ]
          ]
        ]
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdAccountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdAccountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdAccount',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdContactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdContactName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdContact',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdOpportunityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdOpportunityName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdOpportunity',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'targetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'targetList',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'targetListName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'originalEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'originalEmail',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'originalEmailName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'documentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignLogRecordsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'casesNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'emailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'relationName' => 'entityEmailAddress',
        'midKeys' => [
          0 => 'entityId',
          1 => 'emailAddressId'
        ],
        'conditions' => [
          'entityType' => 'Lead'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'entityId_emailAddressId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'emailAddressId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'phoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'relationName' => 'entityPhoneNumber',
        'midKeys' => [
          0 => 'entityId',
          1 => 'phoneNumberId'
        ],
        'conditions' => [
          'entityType' => 'Lead'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'entityId_phoneNumberId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'phoneNumberId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'documents' => [
        'type' => 'manyMany',
        'entity' => 'Document',
        'relationName' => 'documentLead',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'leadId',
          1 => 'documentId'
        ],
        'foreign' => 'leads',
        'indexes' => [
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'leadId_documentId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'leadId',
              1 => 'documentId'
            ],
            'key' => 'UNIQ_LEAD_ID_DOCUMENT_ID'
          ]
        ]
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'leadTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'leadId',
          1 => 'targetListId'
        ],
        'foreign' => 'leads',
        'columnAttributeMap' => [
          'optedOut' => 'targetListIsOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'leadId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'leadId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_LEAD_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'campaignLogRecords' => [
        'type' => 'hasChildren',
        'entity' => 'CampaignLogRecord',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'leads'
      ],
      'createdOpportunity' => [
        'type' => 'belongsTo',
        'entity' => 'Opportunity',
        'key' => 'createdOpportunityId',
        'foreignKey' => 'id',
        'foreign' => 'originalLead'
      ],
      'createdContact' => [
        'type' => 'belongsTo',
        'entity' => 'Contact',
        'key' => 'createdContactId',
        'foreignKey' => 'id',
        'foreign' => 'originalLead'
      ],
      'createdAccount' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'createdAccountId',
        'foreignKey' => 'id',
        'foreign' => 'originalLead'
      ],
      'emails' => [
        'type' => 'hasChildren',
        'entity' => 'Email',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'cases' => [
        'type' => 'hasMany',
        'entity' => 'Case',
        'foreignKey' => 'leadId',
        'foreign' => 'lead'
      ],
      'tasks' => [
        'type' => 'hasChildren',
        'entity' => 'Task',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'calls' => [
        'type' => 'manyMany',
        'entity' => 'Call',
        'relationName' => 'callLead',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'leadId',
          1 => 'callId'
        ],
        'foreign' => 'leads',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'callId' => [
            'columns' => [
              0 => 'callId'
            ],
            'key' => 'IDX_CALL_ID'
          ],
          'leadId_callId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'leadId',
              1 => 'callId'
            ],
            'key' => 'UNIQ_LEAD_ID_CALL_ID'
          ]
        ]
      ],
      'meetings' => [
        'type' => 'manyMany',
        'entity' => 'Meeting',
        'relationName' => 'leadMeeting',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'leadId',
          1 => 'meetingId'
        ],
        'foreign' => 'leads',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'leadId_meetingId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'leadId',
              1 => 'meetingId'
            ],
            'key' => 'UNIQ_LEAD_ID_MEETING_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Lead'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'firstName' => [
        'columns' => [
          0 => 'firstName',
          1 => 'deleted'
        ],
        'key' => 'IDX_FIRST_NAME'
      ],
      'name' => [
        'columns' => [
          0 => 'firstName',
          1 => 'lastName'
        ],
        'key' => 'IDX_NAME'
      ],
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdAtStatus' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'status'
        ],
        'key' => 'IDX_CREATED_AT_STATUS'
      ],
      'createdAtId' => [
        'unique' => true,
        'columns' => [
          0 => 'createdAt',
          1 => 'id'
        ],
        'key' => 'UNIQ_CREATED_AT_ID'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'assignedUserStatus' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'status'
        ],
        'key' => 'IDX_ASSIGNED_USER_STATUS'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'createdAccountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdAccountId'
        ],
        'key' => 'IDX_CREATED_ACCOUNT_ID'
      ],
      'createdContactId' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdContactId'
        ],
        'key' => 'IDX_CREATED_CONTACT_ID'
      ],
      'createdOpportunityId' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdOpportunityId'
        ],
        'key' => 'IDX_CREATED_OPPORTUNITY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'MassEmail' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Pending',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'storeSentEmails' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'optOutEntirely' => [
        'type' => 'bool',
        'notNull' => true,
        'default' => false,
        'fieldType' => 'bool'
      ],
      'fromAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'fromName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToAddress' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'replyToName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'startAt' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'smtpAccount' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'base'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'emailTemplateId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'emailTemplateName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'emailTemplate',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'targetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'excludingTargetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'excludingTargetLists',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'excludingTargetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'inboundEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'inboundEmailName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'inboundEmail',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'queueItemsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'queueItemsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'queueItems' => [
        'type' => 'hasMany',
        'entity' => 'EmailQueueItem',
        'foreignKey' => 'massEmailId',
        'foreign' => 'massEmail'
      ],
      'inboundEmail' => [
        'type' => 'belongsTo',
        'entity' => 'InboundEmail',
        'key' => 'inboundEmailId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'excludingTargetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'massEmailTargetListExcluding',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'massEmailId',
          1 => 'targetListId'
        ],
        'foreign' => 'massEmailsExcluding',
        'indexes' => [
          'massEmailId' => [
            'columns' => [
              0 => 'massEmailId'
            ],
            'key' => 'IDX_MASS_EMAIL_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'massEmailId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'massEmailId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_MASS_EMAIL_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'targetLists' => [
        'type' => 'manyMany',
        'entity' => 'TargetList',
        'relationName' => 'massEmailTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'massEmailId',
          1 => 'targetListId'
        ],
        'foreign' => 'massEmails',
        'indexes' => [
          'massEmailId' => [
            'columns' => [
              0 => 'massEmailId'
            ],
            'key' => 'IDX_MASS_EMAIL_ID'
          ],
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'massEmailId_targetListId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'massEmailId',
              1 => 'targetListId'
            ],
            'key' => 'UNIQ_MASS_EMAIL_ID_TARGET_LIST_ID'
          ]
        ]
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'massEmails'
      ],
      'emailTemplate' => [
        'type' => 'belongsTo',
        'entity' => 'EmailTemplate',
        'key' => 'emailTemplateId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'emailTemplateId' => [
        'type' => 'index',
        'columns' => [
          0 => 'emailTemplateId'
        ],
        'key' => 'IDX_EMAIL_TEMPLATE_ID'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'inboundEmailId' => [
        'type' => 'index',
        'columns' => [
          0 => 'inboundEmailId'
        ],
        'key' => 'IDX_INBOUND_EMAIL_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'Meeting' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Planned',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateStart' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'dateEnd' => [
        'type' => 'datetime',
        'fieldType' => 'datetime'
      ],
      'isAllDay' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'duration' => [
        'type' => 'int',
        'notStorable' => true,
        'default' => 3600,
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
        ],
        'fieldType' => 'int',
        'len' => 11
      ],
      'reminders' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'uid' => [
        'type' => 'varchar',
        'len' => 255,
        'index' => true,
        'fieldType' => 'varchar'
      ],
      'joinUrl' => [
        'type' => 'varchar',
        'dbType' => 'text',
        'len' => 320,
        'default' => NULL,
        'fieldType' => 'varchar'
      ],
      'acceptanceStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
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
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'dateStartDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'dateEndDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'users',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'usersColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'contacts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'contactsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'leadsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'leads',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'leadsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'leadsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'status' => 'acceptanceStatus'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'sourceEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'sourceEmail',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'sourceEmailName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ]
    ],
    'relations' => [
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'meetings'
      ],
      'leads' => [
        'type' => 'manyMany',
        'entity' => 'Lead',
        'relationName' => 'leadMeeting',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'meetingId',
          1 => 'leadId'
        ],
        'foreign' => 'meetings',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'meetingId_leadId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'meetingId',
              1 => 'leadId'
            ],
            'key' => 'UNIQ_MEETING_ID_LEAD_ID'
          ]
        ]
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'contactMeeting',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'meetingId',
          1 => 'contactId'
        ],
        'foreign' => 'meetings',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'meetingId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'meetingId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_MEETING_ID_CONTACT_ID'
          ]
        ]
      ],
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'meetingUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'meetingId',
          1 => 'userId'
        ],
        'foreign' => 'meetings',
        'columnAttributeMap' => [
          'status' => 'acceptanceStatus'
        ],
        'additionalColumns' => [
          'status' => [
            'type' => 'varchar',
            'len' => '36',
            'default' => 'None'
          ]
        ],
        'indexes' => [
          'meetingId' => [
            'columns' => [
              0 => 'meetingId'
            ],
            'key' => 'IDX_MEETING_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'meetingId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'meetingId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_MEETING_ID_USER_ID'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Meeting'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'dateStartStatus' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'status'
        ],
        'key' => 'IDX_DATE_START_STATUS'
      ],
      'dateStart' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'deleted'
        ],
        'key' => 'IDX_DATE_START'
      ],
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'assignedUserStatus' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'status'
        ],
        'key' => 'IDX_ASSIGNED_USER_STATUS'
      ],
      'uid' => [
        'type' => 'index',
        'columns' => [
          0 => 'uid'
        ],
        'key' => 'IDX_UID'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'dateStart',
      'order' => 'DESC'
    ]
  ],
  'Opportunity' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'amount' => [
        'type' => 'float',
        'fieldType' => 'currency',
        'attributeRole' => 'value',
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(amount, amountCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'amountCurrencyRecordRate',
              2 => [
                'amountCurrencyRecordRate.id:' => 'amountCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'amountCurrencyRecordRate.rate'
          ]
        ]
      ],
      'amountWeightedConverted' => [
        'type' => 'float',
        'notNull' => false,
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
        'fieldType' => 'float'
      ],
      'stage' => [
        'type' => 'varchar',
        'default' => 'Prospecting',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'lastStage' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'probability' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'leadSource' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'closeDate' => [
        'type' => 'date',
        'fieldType' => 'date'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'amountCurrency' => [
        'type' => 'varchar',
        'len' => 3,
        'fieldType' => 'currency',
        'attributeRole' => 'currency'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'amountConverted' => [
        'type' => 'float',
        'select' => [
          'select' => 'MUL:(amount, amountCurrencyRecordRate.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'amountCurrencyRecordRate',
              2 => [
                'amountCurrencyRecordRate.id:' => 'amountCurrency'
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'MUL:({alias}.amount, amountCurrencyRecordRateOpportunity{alias}Foreign.rate)',
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'amountCurrencyRecordRateOpportunity{alias}Foreign',
              2 => [
                'amountCurrencyRecordRateOpportunity{alias}Foreign.id:' => '{alias}.amountCurrency'
              ]
            ]
          ]
        ],
        'where' => [
          '=' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          '>' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)>' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          '<' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)<' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          '>=' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)>=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          '<=' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)<=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'MUL:(amount, amountCurrencyRecordRate.rate)!=' => '{value}'
            ],
            'leftJoins' => [
              0 => [
                0 => 'Currency',
                1 => 'amountCurrencyRecordRate',
                2 => [
                  'amountCurrencyRecordRate.id:' => 'amountCurrency'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'whereClause' => [
              'amount=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'amount!=' => NULL
            ]
          ]
        ],
        'notStorable' => true,
        'order' => [
          'order' => [
            0 => [
              0 => 'MUL:(amount, amountCurrencyRecordRate.rate)',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'Currency',
              1 => 'amountCurrencyRecordRate',
              2 => [
                'amountCurrencyRecordRate.id:' => 'amountCurrency'
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'amountCurrencyRecordRate.rate'
          ]
        ],
        'attributeRole' => 'valueConverted',
        'fieldType' => 'currency'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'contacts',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple',
        'orderBy' => 'name',
        'isLinkStub' => false
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple',
        'isLinkStub' => false
      ],
      'contactsColumns' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'columns' => [
          'role' => 'opportunityRole'
        ],
        'attributeRole' => 'columnsMap'
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'contactName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'contact',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'campaignName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'campaign',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'originalLeadId' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'id',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'id',
        'foreignType' => 'id'
      ],
      'originalLeadName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkOne',
        'relation' => 'originalLead',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactRole' => [
        'type' => 'varchar',
        'notStorable' => true,
        'where' => [
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
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
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'role' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'role' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'role' => '{value}'
                ]
              ]
            ]
          ],
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
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
                'from' => 'ContactOpportunity',
                'select' => [
                  0 => 'opportunityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'role*' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'whereClause' => [
              'NOT' => [
                'EXISTS' => [
                  'from' => 'Opportunity',
                  'fromAlias' => 'sq',
                  'select' => [
                    0 => 'id'
                  ],
                  'leftJoins' => [
                    0 => [
                      0 => 'contacts',
                      1 => 'm',
                      2 => NULL,
                      3 => [
                        'onlyMiddle' => true
                      ]
                    ]
                  ],
                  'whereClause' => [
                    'm.role!=' => NULL,
                    'sq.id:' => 'opportunity.id'
                  ]
                ]
              ]
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'EXISTS' => [
                'from' => 'Opportunity',
                'fromAlias' => 'sq',
                'select' => [
                  0 => 'id'
                ],
                'leftJoins' => [
                  0 => [
                    0 => 'contacts',
                    1 => 'm',
                    2 => NULL,
                    3 => [
                      'onlyMiddle' => true
                    ]
                  ]
                ],
                'whereClause' => [
                  'm.role!=' => NULL,
                  'sq.id:' => 'opportunity.id'
                ]
              ]
            ]
          ]
        ]
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'documentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'documentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'emailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'tasksNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'callsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'meetingsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'originalLead' => [
        'type' => 'hasOne',
        'entity' => 'Lead',
        'foreignKey' => 'createdOpportunityId',
        'foreign' => 'createdOpportunity'
      ],
      'campaign' => [
        'type' => 'belongsTo',
        'entity' => 'Campaign',
        'key' => 'campaignId',
        'foreignKey' => 'id',
        'foreign' => 'opportunities'
      ],
      'documents' => [
        'type' => 'manyMany',
        'entity' => 'Document',
        'relationName' => 'documentOpportunity',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'opportunityId',
          1 => 'documentId'
        ],
        'foreign' => 'opportunities',
        'indexes' => [
          'opportunityId' => [
            'columns' => [
              0 => 'opportunityId'
            ],
            'key' => 'IDX_OPPORTUNITY_ID'
          ],
          'documentId' => [
            'columns' => [
              0 => 'documentId'
            ],
            'key' => 'IDX_DOCUMENT_ID'
          ],
          'opportunityId_documentId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'opportunityId',
              1 => 'documentId'
            ],
            'key' => 'UNIQ_OPPORTUNITY_ID_DOCUMENT_ID'
          ]
        ]
      ],
      'emails' => [
        'type' => 'hasChildren',
        'entity' => 'Email',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'tasks' => [
        'type' => 'hasChildren',
        'entity' => 'Task',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'calls' => [
        'type' => 'hasChildren',
        'entity' => 'Call',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'meetings' => [
        'type' => 'hasChildren',
        'entity' => 'Meeting',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent'
      ],
      'contact' => [
        'type' => 'belongsTo',
        'entity' => 'Contact',
        'key' => 'contactId',
        'foreignKey' => 'id',
        'foreign' => 'opportunitiesPrimary'
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'contactOpportunity',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'opportunityId',
          1 => 'contactId'
        ],
        'foreign' => 'opportunities',
        'columnAttributeMap' => [
          'role' => 'contactRole'
        ],
        'additionalColumns' => [
          'role' => [
            'type' => 'varchar',
            'len' => 50
          ]
        ],
        'indexes' => [
          'opportunityId' => [
            'columns' => [
              0 => 'opportunityId'
            ],
            'key' => 'IDX_OPPORTUNITY_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'opportunityId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'opportunityId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_OPPORTUNITY_ID_CONTACT_ID'
          ]
        ]
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => 'opportunities'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Opportunity'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'stage' => [
        'columns' => [
          0 => 'stage',
          1 => 'deleted'
        ],
        'key' => 'IDX_STAGE'
      ],
      'lastStage' => [
        'columns' => [
          0 => 'lastStage'
        ],
        'key' => 'IDX_LAST_STAGE'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'createdAtStage' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'stage'
        ],
        'key' => 'IDX_CREATED_AT_STAGE'
      ],
      'createdAtId' => [
        'unique' => true,
        'columns' => [
          0 => 'createdAt',
          1 => 'id'
        ],
        'key' => 'UNIQ_CREATED_AT_ID'
      ],
      'assignedUserStage' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'stage'
        ],
        'key' => 'IDX_ASSIGNED_USER_STAGE'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'contactId' => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'key' => 'IDX_CONTACT_ID'
      ],
      'campaignId' => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'Reminder' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'notStorable' => true
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'remindAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'index' => true,
        'fieldType' => 'datetime'
      ],
      'startAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'index' => true,
        'fieldType' => 'datetime'
      ],
      'type' => [
        'type' => 'varchar',
        'len' => 36,
        'index' => true,
        'default' => 'Popup',
        'fieldType' => 'varchar'
      ],
      'seconds' => [
        'type' => 'int',
        'default' => 0,
        'fieldType' => 'int',
        'len' => 11
      ],
      'isSubmitted' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'userName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'user',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'entity',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'entity',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'entityName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'entity',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ]
    ],
    'relations' => [
      'entity' => [
        'type' => 'belongsToParent',
        'key' => 'entityId',
        'foreign' => NULL
      ],
      'user' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'userId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'remindAt' => [
        'type' => 'index',
        'columns' => [
          0 => 'remindAt'
        ],
        'key' => 'IDX_REMIND_AT'
      ],
      'startAt' => [
        'type' => 'index',
        'columns' => [
          0 => 'startAt'
        ],
        'key' => 'IDX_START_AT'
      ],
      'type' => [
        'type' => 'index',
        'columns' => [
          0 => 'type'
        ],
        'key' => 'IDX_TYPE'
      ],
      'userId' => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'key' => 'IDX_USER_ID'
      ],
      'entity' => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId',
          1 => 'entityType'
        ],
        'key' => 'IDX_ENTITY'
      ]
    ],
    'collection' => [
      'orderBy' => 'remindAt',
      'order' => 'DESC'
    ]
  ],
  'Target' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'personName',
        'notStorable' => true,
        'select' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:(firstName, \'\'), \' \', IFNULL:(lastName, \'\'))), \'\')'
        ],
        'selectForeign' => [
          'select' => 'NULLIF:(TRIM:(CONCAT:(IFNULL:({alias}.firstName, \'\'), \' \', IFNULL:({alias}.lastName, \'\'))), \'\')'
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'OR' => [
                'firstName*' => '{value}',
                'lastName*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)*' => '{value}'
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'AND' => [
                'firstName!*' => '{value}',
                'lastName!*' => '{value}',
                'CONCAT:(firstName, \' \', lastName)!*' => '{value}',
                'CONCAT:(lastName, \' \', firstName)!*' => '{value}'
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'OR' => [
                'firstName' => '{value}',
                'lastName' => '{value}',
                'CONCAT:(firstName, \' \', lastName)' => '{value}',
                'CONCAT:(lastName, \' \', firstName)' => '{value}'
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'firstName',
              1 => '{direction}'
            ],
            1 => [
              0 => 'lastName',
              1 => '{direction}'
            ]
          ]
        ]
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'salutationName' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'firstName' => [
        'type' => 'varchar',
        'len' => 100,
        'default' => '',
        'fieldType' => 'varchar'
      ],
      'lastName' => [
        'type' => 'varchar',
        'len' => 100,
        'default' => '',
        'fieldType' => 'varchar'
      ],
      'title' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'accountName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'website' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'addressStreet' => [
        'type' => 'text',
        'dbType' => 'varchar',
        'len' => 255,
        'fieldType' => 'text'
      ],
      'addressCity' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressState' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressCountry' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressPostalCode' => [
        'type' => 'varchar',
        'len' => 40,
        'fieldType' => 'varchar'
      ],
      'emailAddress' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'email',
        'select' => [
          'select' => 'emailAddresses.name',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressTarget{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressTarget{alias}ForeignMiddle',
              2 => [
                'emailAddressTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressTarget{alias}ForeignMiddle.primary' => true,
                'emailAddressTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressTarget{alias}Foreign',
              2 => [
                'emailAddressTarget{alias}Foreign.id:' => 'emailAddressTarget{alias}ForeignMiddle.emailAddressId',
                'emailAddressTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'LIKE:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'EQUAL:(emailAddressesMultiple.lower, LOWER:({value})):' => NULL
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'EQUAL:(emailAddress.lower, LOWER:({value})):' => NULL
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'emailAddress',
                    1 => 'emailAddress',
                    2 => [
                      'emailAddress.id:' => 'emailAddressId',
                      'emailAddress.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'emailAddress.lower' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddressesMultiple'
              ]
            ],
            'whereClause' => [
              'emailAddressesMultiple.lower=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityEmailAddress',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.lower',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.lower'
          ]
        ]
      ],
      'phoneNumber' => [
        'type' => 'varchar',
        'notStorable' => true,
        'fieldType' => 'phone',
        'select' => [
          'select' => 'phoneNumbers.name',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberTarget{alias}Foreign.name',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberTarget{alias}ForeignMiddle',
              2 => [
                'phoneNumberTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberTarget{alias}ForeignMiddle.primary' => true,
                'phoneNumberTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberTarget{alias}Foreign',
              2 => [
                'phoneNumberTarget{alias}Foreign.id:' => 'phoneNumberTarget{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.name*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => '{value}'
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.name' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.name!=' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.name=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target'
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.name',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.name'
          ]
        ]
      ],
      'doNotCall' => [
        'type' => 'bool',
        'notNull' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'middleName' => [
        'type' => 'varchar',
        'len' => 100,
        'fieldType' => 'varchar'
      ],
      'addressMap' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'map'
      ],
      'emailAddressIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.optOut',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressTarget{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressTarget{alias}ForeignMiddle',
              2 => [
                'emailAddressTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressTarget{alias}ForeignMiddle.primary' => true,
                'emailAddressTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressTarget{alias}Foreign',
              2 => [
                'emailAddressTarget{alias}Foreign.id:' => 'emailAddressTarget{alias}ForeignMiddle.emailAddressId',
                'emailAddressTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.optOut=' => true
              ],
              1 => [
                'emailAddresses.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.optOut=' => false
                ],
                1 => [
                  'emailAddresses.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.optOut'
          ]
        ],
        'default' => false
      ],
      'emailAddressIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'emailAddresses.invalid',
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'emailAddressTarget{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityEmailAddress',
              1 => 'emailAddressTarget{alias}ForeignMiddle',
              2 => [
                'emailAddressTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'emailAddressTarget{alias}ForeignMiddle.primary' => true,
                'emailAddressTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'EmailAddress',
              1 => 'emailAddressTarget{alias}Foreign',
              2 => [
                'emailAddressTarget{alias}Foreign.id:' => 'emailAddressTarget{alias}ForeignMiddle.emailAddressId',
                'emailAddressTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'emailAddresses.invalid=' => true
              ],
              1 => [
                'emailAddresses.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'emailAddresses.invalid=' => false
                ],
                1 => [
                  'emailAddresses.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'emailAddresses',
                1 => 'emailAddresses',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'emailAddresses.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'emailAddresses',
              1 => 'emailAddresses',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'emailAddresses.invalid'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.optOut',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberTarget{alias}Foreign.optOut',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberTarget{alias}ForeignMiddle',
              2 => [
                'phoneNumberTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberTarget{alias}ForeignMiddle.primary' => true,
                'phoneNumberTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberTarget{alias}Foreign',
              2 => [
                'phoneNumberTarget{alias}Foreign.id:' => 'phoneNumberTarget{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.optOut=' => true
              ],
              1 => [
                'phoneNumbers.optOut!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.optOut=' => false
                ],
                1 => [
                  'phoneNumbers.optOut=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.optOut',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.optOut'
          ]
        ],
        'default' => false
      ],
      'phoneNumberIsInvalid' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'select' => [
          'select' => 'phoneNumbers.invalid',
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ]
        ],
        'selectForeign' => [
          'select' => 'phoneNumberTarget{alias}Foreign.invalid',
          'leftJoins' => [
            0 => [
              0 => 'EntityPhoneNumber',
              1 => 'phoneNumberTarget{alias}ForeignMiddle',
              2 => [
                'phoneNumberTarget{alias}ForeignMiddle.entityId:' => '{alias}.id',
                'phoneNumberTarget{alias}ForeignMiddle.primary' => true,
                'phoneNumberTarget{alias}ForeignMiddle.deleted' => false
              ]
            ],
            1 => [
              0 => 'PhoneNumber',
              1 => 'phoneNumberTarget{alias}Foreign',
              2 => [
                'phoneNumberTarget{alias}Foreign.id:' => 'phoneNumberTarget{alias}ForeignMiddle.phoneNumberId',
                'phoneNumberTarget{alias}Foreign.deleted' => false
              ]
            ]
          ]
        ],
        'where' => [
          '= TRUE' => [
            'whereClause' => [
              0 => [
                'phoneNumbers.invalid=' => true
              ],
              1 => [
                'phoneNumbers.invalid!=' => NULL
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ],
          '= FALSE' => [
            'whereClause' => [
              'OR' => [
                0 => [
                  'phoneNumbers.invalid=' => false
                ],
                1 => [
                  'phoneNumbers.invalid=' => NULL
                ]
              ]
            ],
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbers',
                2 => [
                  'primary' => true
                ]
              ]
            ]
          ]
        ],
        'order' => [
          'order' => [
            0 => [
              0 => 'phoneNumbers.invalid',
              1 => '{direction}'
            ]
          ],
          'leftJoins' => [
            0 => [
              0 => 'phoneNumbers',
              1 => 'phoneNumbers',
              2 => [
                'primary' => true
              ]
            ]
          ],
          'additionalSelect' => [
            0 => 'phoneNumbers.invalid'
          ]
        ],
        'default' => false
      ],
      'emailAddressData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isEmailAddressData' => true,
        'field' => 'emailAddress'
      ],
      'phoneNumberData' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true,
        'isPhoneNumberData' => true,
        'field' => 'phoneNumber'
      ],
      'phoneNumberNumeric' => [
        'type' => 'varchar',
        'notStorable' => true,
        'notExportable' => true,
        'where' => [
          'LIKE' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          'NOT LIKE' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric*' => '{value}'
                ]
              ]
            ]
          ],
          '=' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          '<>' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IN' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'NOT IN' => [
            'whereClause' => [
              'id!=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'joins' => [
                  0 => [
                    0 => 'phoneNumber',
                    1 => 'phoneNumber',
                    2 => [
                      'phoneNumber.id:' => 'phoneNumberId',
                      'phoneNumber.deleted' => false
                    ]
                  ]
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target',
                  'phoneNumber.numeric' => '{value}'
                ]
              ]
            ]
          ],
          'IS NULL' => [
            'leftJoins' => [
              0 => [
                0 => 'phoneNumbers',
                1 => 'phoneNumbersMultiple'
              ]
            ],
            'whereClause' => [
              'phoneNumbersMultiple.numeric=' => NULL
            ]
          ],
          'IS NOT NULL' => [
            'whereClause' => [
              'id=s' => [
                'from' => 'EntityPhoneNumber',
                'select' => [
                  0 => 'entityId'
                ],
                'whereClause' => [
                  'deleted' => false,
                  'entityType' => 'Target'
                ]
              ]
            ]
          ]
        ]
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ]
    ],
    'relations' => [
      'emailAddresses' => [
        'type' => 'manyMany',
        'entity' => 'EmailAddress',
        'relationName' => 'entityEmailAddress',
        'midKeys' => [
          0 => 'entityId',
          1 => 'emailAddressId'
        ],
        'conditions' => [
          'entityType' => 'Target'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'emailAddressId' => [
            'columns' => [
              0 => 'emailAddressId'
            ],
            'key' => 'IDX_EMAIL_ADDRESS_ID'
          ],
          'entityId_emailAddressId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'emailAddressId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'phoneNumbers' => [
        'type' => 'manyMany',
        'entity' => 'PhoneNumber',
        'relationName' => 'entityPhoneNumber',
        'midKeys' => [
          0 => 'entityId',
          1 => 'phoneNumberId'
        ],
        'conditions' => [
          'entityType' => 'Target'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ],
          'primary' => [
            'type' => 'bool',
            'default' => false
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'phoneNumberId' => [
            'columns' => [
              0 => 'phoneNumberId'
            ],
            'key' => 'IDX_PHONE_NUMBER_ID'
          ],
          'entityId_phoneNumberId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'phoneNumberId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Target'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'firstName' => [
        'columns' => [
          0 => 'firstName',
          1 => 'deleted'
        ],
        'key' => 'IDX_FIRST_NAME'
      ],
      'name' => [
        'columns' => [
          0 => 'firstName',
          1 => 'lastName'
        ],
        'key' => 'IDX_NAME'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'TargetList' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entryCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'optedOutCount' => [
        'type' => 'int',
        'notStorable' => true,
        'fieldType' => 'int',
        'len' => 11
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'includingActionList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'excludingActionList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'storeArrayValues' => true,
        'fieldType' => 'jsonArray'
      ],
      'targetStatus' => [
        'type' => 'varchar',
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'isOptedOut' => [
        'type' => 'bool',
        'notNull' => true,
        'notExportable' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'categoryId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'categoryName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'category',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'sourceCampaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'sourceCampaign',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'sourceCampaignName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'usersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'usersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'leadsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'contactsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'accountsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'accountsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'massEmailsExcludingIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'massEmailsExcludingNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignsExcludingIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignsExcludingNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'massEmailsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'massEmailsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'campaignsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'users' => [
        'type' => 'manyMany',
        'entity' => 'User',
        'relationName' => 'targetListUser',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'userId'
        ],
        'foreign' => 'targetLists',
        'columnAttributeMap' => [
          'optedOut' => 'isOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'userId' => [
            'columns' => [
              0 => 'userId'
            ],
            'key' => 'IDX_USER_ID'
          ],
          'targetListId_userId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'userId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_USER_ID'
          ]
        ]
      ],
      'leads' => [
        'type' => 'manyMany',
        'entity' => 'Lead',
        'relationName' => 'leadTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'leadId'
        ],
        'foreign' => 'targetLists',
        'columnAttributeMap' => [
          'optedOut' => 'isOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'leadId' => [
            'columns' => [
              0 => 'leadId'
            ],
            'key' => 'IDX_LEAD_ID'
          ],
          'targetListId_leadId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'leadId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_LEAD_ID'
          ]
        ]
      ],
      'contacts' => [
        'type' => 'manyMany',
        'entity' => 'Contact',
        'relationName' => 'contactTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'contactId'
        ],
        'foreign' => 'targetLists',
        'columnAttributeMap' => [
          'optedOut' => 'isOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'contactId' => [
            'columns' => [
              0 => 'contactId'
            ],
            'key' => 'IDX_CONTACT_ID'
          ],
          'targetListId_contactId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'contactId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_CONTACT_ID'
          ]
        ]
      ],
      'accounts' => [
        'type' => 'manyMany',
        'entity' => 'Account',
        'relationName' => 'accountTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'accountId'
        ],
        'foreign' => 'targetLists',
        'columnAttributeMap' => [
          'optedOut' => 'isOptedOut'
        ],
        'additionalColumns' => [
          'optedOut' => [
            'type' => 'bool'
          ]
        ],
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'accountId' => [
            'columns' => [
              0 => 'accountId'
            ],
            'key' => 'IDX_ACCOUNT_ID'
          ],
          'targetListId_accountId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'accountId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_ACCOUNT_ID'
          ]
        ]
      ],
      'massEmailsExcluding' => [
        'type' => 'manyMany',
        'entity' => 'MassEmail',
        'relationName' => 'massEmailTargetListExcluding',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'massEmailId'
        ],
        'foreign' => 'excludingTargetLists',
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'massEmailId' => [
            'columns' => [
              0 => 'massEmailId'
            ],
            'key' => 'IDX_MASS_EMAIL_ID'
          ],
          'targetListId_massEmailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'massEmailId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_MASS_EMAIL_ID'
          ]
        ]
      ],
      'campaignsExcluding' => [
        'type' => 'manyMany',
        'entity' => 'Campaign',
        'relationName' => 'campaignTargetListExcluding',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'campaignId'
        ],
        'foreign' => 'excludingTargetLists',
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'campaignId' => [
            'columns' => [
              0 => 'campaignId'
            ],
            'key' => 'IDX_CAMPAIGN_ID'
          ],
          'targetListId_campaignId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'campaignId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_CAMPAIGN_ID'
          ]
        ]
      ],
      'massEmails' => [
        'type' => 'manyMany',
        'entity' => 'MassEmail',
        'relationName' => 'massEmailTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'massEmailId'
        ],
        'foreign' => 'targetLists',
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'massEmailId' => [
            'columns' => [
              0 => 'massEmailId'
            ],
            'key' => 'IDX_MASS_EMAIL_ID'
          ],
          'targetListId_massEmailId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'massEmailId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_MASS_EMAIL_ID'
          ]
        ]
      ],
      'campaigns' => [
        'type' => 'manyMany',
        'entity' => 'Campaign',
        'relationName' => 'campaignTargetList',
        'key' => 'id',
        'foreignKey' => 'id',
        'midKeys' => [
          0 => 'targetListId',
          1 => 'campaignId'
        ],
        'foreign' => 'targetLists',
        'indexes' => [
          'targetListId' => [
            'columns' => [
              0 => 'targetListId'
            ],
            'key' => 'IDX_TARGET_LIST_ID'
          ],
          'campaignId' => [
            'columns' => [
              0 => 'campaignId'
            ],
            'key' => 'IDX_CAMPAIGN_ID'
          ],
          'targetListId_campaignId' => [
            'type' => 'unique',
            'columns' => [
              0 => 'targetListId',
              1 => 'campaignId'
            ],
            'key' => 'UNIQ_TARGET_LIST_ID_CAMPAIGN_ID'
          ]
        ]
      ],
      'category' => [
        'type' => 'belongsTo',
        'entity' => 'TargetListCategory',
        'key' => 'categoryId',
        'foreignKey' => 'id',
        'foreign' => 'category'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'TargetList'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdAt' => [
        'columns' => [
          0 => 'createdAt',
          1 => 'deleted'
        ],
        'key' => 'IDX_CREATED_AT'
      ],
      'categoryId' => [
        'type' => 'index',
        'columns' => [
          0 => 'categoryId'
        ],
        'key' => 'IDX_CATEGORY_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'TargetListCategory' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'order' => [
        'type' => 'int',
        'fieldType' => 'int',
        'len' => 11
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'childList' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'parentName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'parent',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'targetListsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'targetListsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkStub' => true
      ],
      'childrenNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkStub' => true
      ]
    ],
    'relations' => [
      'targetLists' => [
        'type' => 'hasMany',
        'entity' => 'TargetList',
        'foreignKey' => 'categoryId',
        'foreign' => 'category'
      ],
      'children' => [
        'type' => 'hasMany',
        'entity' => 'TargetListCategory',
        'foreignKey' => 'parentId',
        'foreign' => 'parent'
      ],
      'parent' => [
        'type' => 'belongsTo',
        'entity' => 'TargetListCategory',
        'key' => 'parentId',
        'foreignKey' => 'id',
        'foreign' => 'children'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'TargetListCategory'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ]
    ],
    'indexes' => [
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'parentId' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId'
        ],
        'key' => 'IDX_PARENT_ID'
      ]
    ],
    'collection' => [
      'order' => 'ASC'
    ]
  ],
  'Task' => [
    'attributes' => [
      'id' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'id'
      ],
      'name' => [
        'type' => 'varchar',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'status' => [
        'type' => 'varchar',
        'default' => 'Not Started',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'priority' => [
        'type' => 'varchar',
        'default' => 'Normal',
        'fieldType' => 'varchar',
        'len' => 255
      ],
      'dateStart' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'dateEnd' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'dateStartDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'dateEndDate' => [
        'type' => 'date',
        'notNull' => false,
        'fieldType' => 'date'
      ],
      'dateCompleted' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'isOverdue' => [
        'type' => 'bool',
        'notNull' => true,
        'notStorable' => true,
        'fieldType' => 'bool',
        'default' => false
      ],
      'reminders' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'fieldType' => 'jsonArray'
      ],
      'description' => [
        'type' => 'text',
        'fieldType' => 'text'
      ],
      'createdAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'modifiedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'streamUpdatedAt' => [
        'type' => 'datetime',
        'notNull' => false,
        'fieldType' => 'datetime'
      ],
      'parentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'parent',
        'attributeRole' => 'id',
        'fieldType' => 'linkParent',
        'notNull' => false
      ],
      'parentType' => [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'attributeRole' => 'type',
        'fieldType' => 'linkParent',
        'dbType' => 'string'
      ],
      'parentName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'relation' => 'parent',
        'isParentName' => true,
        'attributeRole' => 'name',
        'fieldType' => 'linkParent'
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'accountName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'account',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'contactName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'contact',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'originalEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => 'originalEmail',
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notStorable' => true,
        'notNull' => false
      ],
      'originalEmailName' => [
        'type' => 'varchar',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link'
      ],
      'createdById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'createdByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'createdBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'modifiedById' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'modifiedByName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'modifiedBy',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'assignedUserId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'attributeRole' => 'id',
        'fieldType' => 'link',
        'notNull' => false
      ],
      'assignedUserName' => [
        'type' => 'foreign',
        'notStorable' => true,
        'attributeRole' => 'name',
        'fieldType' => 'link',
        'relation' => 'assignedUser',
        'foreign' => 'name',
        'foreignType' => 'varchar'
      ],
      'teamsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'isLinkMultipleIdList' => true,
        'relation' => 'teams',
        'isUnordered' => true,
        'attributeRole' => 'idList',
        'fieldType' => 'linkMultiple'
      ],
      'teamsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'attributeRole' => 'nameMap',
        'fieldType' => 'linkMultiple'
      ],
      'attachmentsIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'orderBy' => [
          0 => [
            0 => 'createdAt',
            1 => 'ASC'
          ],
          1 => [
            0 => 'name',
            1 => 'ASC'
          ]
        ],
        'isLinkMultipleIdList' => true,
        'relation' => 'attachments',
        'isLinkStub' => false
      ],
      'attachmentsNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'isLinkMultipleNameMap' => true,
        'isLinkStub' => false
      ],
      'isFollowed' => [
        'type' => 'bool',
        'notStorable' => true,
        'notExportable' => true,
        'default' => false
      ],
      'followersIds' => [
        'type' => 'jsonArray',
        'notStorable' => true,
        'notExportable' => true
      ],
      'followersNames' => [
        'type' => 'jsonObject',
        'notStorable' => true,
        'notExportable' => true
      ],
      'versionNumber' => [
        'type' => 'int',
        'dbType' => 'bigint',
        'notExportable' => true
      ],
      'emailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'attachmentsTypes' => [
        'type' => 'jsonObject',
        'notStorable' => true
      ]
    ],
    'relations' => [
      'email' => [
        'type' => 'belongsTo',
        'entity' => 'Email',
        'key' => 'emailId',
        'foreignKey' => 'id',
        'foreign' => 'tasks'
      ],
      'contact' => [
        'type' => 'belongsTo',
        'entity' => 'Contact',
        'key' => 'contactId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'account' => [
        'type' => 'belongsTo',
        'entity' => 'Account',
        'key' => 'accountId',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'parent' => [
        'type' => 'belongsToParent',
        'key' => 'parentId',
        'foreign' => 'tasks'
      ],
      'teams' => [
        'type' => 'manyMany',
        'entity' => 'Team',
        'relationName' => 'entityTeam',
        'midKeys' => [
          0 => 'entityId',
          1 => 'teamId'
        ],
        'conditions' => [
          'entityType' => 'Task'
        ],
        'additionalColumns' => [
          'entityType' => [
            'type' => 'varchar',
            'len' => 100
          ]
        ],
        'indexes' => [
          'entityId' => [
            'columns' => [
              0 => 'entityId'
            ],
            'key' => 'IDX_ENTITY_ID'
          ],
          'teamId' => [
            'columns' => [
              0 => 'teamId'
            ],
            'key' => 'IDX_TEAM_ID'
          ],
          'entityId_teamId_entityType' => [
            'type' => 'unique',
            'columns' => [
              0 => 'entityId',
              1 => 'teamId',
              2 => 'entityType'
            ],
            'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
          ]
        ]
      ],
      'assignedUser' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'assignedUserId',
        'foreignKey' => 'id',
        'foreign' => 'tasks'
      ],
      'modifiedBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'modifiedById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'createdBy' => [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
        'foreign' => NULL
      ],
      'attachments' => [
        'type' => 'hasChildren',
        'entity' => 'Attachment',
        'foreignKey' => 'parentId',
        'foreignType' => 'parentType',
        'foreign' => 'parent',
        'conditions' => [
          'OR' => [
            0 => [
              'field' => NULL
            ],
            1 => [
              'field' => 'attachments'
            ]
          ]
        ],
        'relationName' => 'attachments'
      ]
    ],
    'indexes' => [
      'dateStartStatus' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'status'
        ],
        'key' => 'IDX_DATE_START_STATUS'
      ],
      'dateEndStatus' => [
        'columns' => [
          0 => 'dateEnd',
          1 => 'status'
        ],
        'key' => 'IDX_DATE_END_STATUS'
      ],
      'dateStart' => [
        'columns' => [
          0 => 'dateStart',
          1 => 'deleted'
        ],
        'key' => 'IDX_DATE_START'
      ],
      'status' => [
        'columns' => [
          0 => 'status',
          1 => 'deleted'
        ],
        'key' => 'IDX_STATUS'
      ],
      'assignedUser' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'deleted'
        ],
        'key' => 'IDX_ASSIGNED_USER'
      ],
      'assignedUserStatus' => [
        'columns' => [
          0 => 'assignedUserId',
          1 => 'status'
        ],
        'key' => 'IDX_ASSIGNED_USER_STATUS'
      ],
      'parent' => [
        'type' => 'index',
        'columns' => [
          0 => 'parentId',
          1 => 'parentType'
        ],
        'key' => 'IDX_PARENT'
      ],
      'accountId' => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      'contactId' => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'key' => 'IDX_CONTACT_ID'
      ],
      'createdById' => [
        'type' => 'index',
        'columns' => [
          0 => 'createdById'
        ],
        'key' => 'IDX_CREATED_BY_ID'
      ],
      'modifiedById' => [
        'type' => 'index',
        'columns' => [
          0 => 'modifiedById'
        ],
        'key' => 'IDX_MODIFIED_BY_ID'
      ],
      'assignedUserId' => [
        'type' => 'index',
        'columns' => [
          0 => 'assignedUserId'
        ],
        'key' => 'IDX_ASSIGNED_USER_ID'
      ],
      'emailId' => [
        'type' => 'index',
        'columns' => [
          0 => 'emailId'
        ],
        'key' => 'IDX_EMAIL_ID'
      ]
    ],
    'collection' => [
      'orderBy' => 'createdAt',
      'order' => 'DESC'
    ]
  ],
  'EmailEmailAccount' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'emailAccountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailAccountId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ACCOUNT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'emailAccountId',
          1 => 'emailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_EMAIL_ACCOUNT_ID_EMAIL_ID'
      ]
    ]
  ],
  'EmailInboundEmail' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'inboundEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'inboundEmailId'
        ],
        'flags' => [],
        'key' => 'IDX_INBOUND_EMAIL_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'inboundEmailId',
          1 => 'emailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_INBOUND_EMAIL_ID_EMAIL_ID'
      ]
    ]
  ],
  'EmailEmailAddress' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'emailAddressId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'addressType' => [
        'type' => 'varchar',
        'len' => 4
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailAddressId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ADDRESS_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'emailId',
          1 => 'emailAddressId',
          2 => 'addressType'
        ],
        'flags' => [],
        'key' => 'UNIQ_EMAIL_ID_EMAIL_ADDRESS_ID_ADDRESS_TYPE'
      ]
    ]
  ],
  'EmailUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'emailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
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
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'emailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_EMAIL_ID'
      ]
    ]
  ],
  'EntityUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId'
        ],
        'flags' => [],
        'key' => 'IDX_ENTITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'entityId',
          1 => 'userId',
          2 => 'entityType'
        ],
        'flags' => [],
        'key' => 'UNIQ_ENTITY_ID_USER_ID_ENTITY_TYPE'
      ]
    ]
  ],
  'EntityTeam' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId'
        ],
        'flags' => [],
        'key' => 'IDX_ENTITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'entityId',
          1 => 'teamId',
          2 => 'entityType'
        ],
        'flags' => [],
        'key' => 'UNIQ_ENTITY_ID_TEAM_ID_ENTITY_TYPE'
      ]
    ]
  ],
  'GroupEmailFolderTeam' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'groupEmailFolderId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'groupEmailFolderId'
        ],
        'flags' => [],
        'key' => 'IDX_GROUP_EMAIL_FOLDER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'teamId',
          1 => 'groupEmailFolderId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TEAM_ID_GROUP_EMAIL_FOLDER_ID'
      ]
    ]
  ],
  'InboundEmailTeam' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'inboundEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'inboundEmailId'
        ],
        'flags' => [],
        'key' => 'IDX_INBOUND_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'teamId',
          1 => 'inboundEmailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TEAM_ID_INBOUND_EMAIL_ID'
      ]
    ]
  ],
  'NoteUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'noteId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'noteId'
        ],
        'flags' => [],
        'key' => 'IDX_NOTE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'noteId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_NOTE_ID'
      ]
    ]
  ],
  'NotePortal' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'noteId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'noteId'
        ],
        'flags' => [],
        'key' => 'IDX_NOTE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'portalId',
          1 => 'noteId'
        ],
        'flags' => [],
        'key' => 'UNIQ_PORTAL_ID_NOTE_ID'
      ]
    ]
  ],
  'NoteTeam' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'noteId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'noteId'
        ],
        'flags' => [],
        'key' => 'IDX_NOTE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'teamId',
          1 => 'noteId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TEAM_ID_NOTE_ID'
      ]
    ]
  ],
  'KnowledgeBaseArticlePortal' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'knowledgeBaseArticleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'knowledgeBaseArticleId'
        ],
        'flags' => [],
        'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'knowledgeBaseArticleId',
          1 => 'portalId'
        ],
        'flags' => [],
        'key' => 'UNIQ_KNOWLEDGE_BASE_ARTICLE_ID_PORTAL_ID'
      ]
    ]
  ],
  'PortalPortalRole' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'portalRoleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalRoleId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ROLE_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'portalRoleId',
          1 => 'portalId'
        ],
        'flags' => [],
        'key' => 'UNIQ_PORTAL_ROLE_ID_PORTAL_ID'
      ]
    ]
  ],
  'PortalUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'portalId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'portalId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_PORTAL_ID'
      ]
    ]
  ],
  'PortalRoleUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'portalRoleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'portalRoleId'
        ],
        'flags' => [],
        'key' => 'IDX_PORTAL_ROLE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'portalRoleId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_PORTAL_ROLE_ID'
      ]
    ]
  ],
  'RoleTeam' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'roleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'roleId'
        ],
        'flags' => [],
        'key' => 'IDX_ROLE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'teamId',
          1 => 'roleId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TEAM_ID_ROLE_ID'
      ]
    ]
  ],
  'RoleUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'roleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'roleId'
        ],
        'flags' => [],
        'key' => 'IDX_ROLE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'roleId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_ROLE_ID'
      ]
    ]
  ],
  'SmsPhoneNumber' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'smsId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'phoneNumberId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'addressType' => [
        'type' => 'varchar',
        'len' => 4
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'smsId'
        ],
        'flags' => [],
        'key' => 'IDX_SMS_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'phoneNumberId'
        ],
        'flags' => [],
        'key' => 'IDX_PHONE_NUMBER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'smsId',
          1 => 'phoneNumberId',
          2 => 'addressType'
        ],
        'flags' => [],
        'key' => 'UNIQ_SMS_ID_PHONE_NUMBER_ID_ADDRESS_TYPE'
      ]
    ]
  ],
  'TeamUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'teamId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'role' => [
        'type' => 'varchar',
        'len' => 100
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'teamId'
        ],
        'flags' => [],
        'key' => 'IDX_TEAM_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'userId',
          1 => 'teamId'
        ],
        'flags' => [],
        'key' => 'UNIQ_USER_ID_TEAM_ID'
      ]
    ]
  ],
  'EntityEmailAddress' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'emailAddressId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100
      ],
      'primary' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId'
        ],
        'flags' => [],
        'key' => 'IDX_ENTITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'emailAddressId'
        ],
        'flags' => [],
        'key' => 'IDX_EMAIL_ADDRESS_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'entityId',
          1 => 'emailAddressId',
          2 => 'entityType'
        ],
        'flags' => [],
        'key' => 'UNIQ_ENTITY_ID_EMAIL_ADDRESS_ID_ENTITY_TYPE'
      ]
    ]
  ],
  'EntityPhoneNumber' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'entityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'phoneNumberId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'entityType' => [
        'type' => 'varchar',
        'len' => 100
      ],
      'primary' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'entityId'
        ],
        'flags' => [],
        'key' => 'IDX_ENTITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'phoneNumberId'
        ],
        'flags' => [],
        'key' => 'IDX_PHONE_NUMBER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'entityId',
          1 => 'phoneNumberId',
          2 => 'entityType'
        ],
        'flags' => [],
        'key' => 'UNIQ_ENTITY_ID_PHONE_NUMBER_ID_ENTITY_TYPE'
      ]
    ]
  ],
  'TargetListUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'optedOut' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'userId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_USER_ID'
      ]
    ]
  ],
  'AccountPortalUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'flags' => [],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'accountId',
          1 => 'userId'
        ],
        'flags' => [],
        'key' => 'UNIQ_ACCOUNT_ID_USER_ID'
      ]
    ]
  ],
  'CallUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'callId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'callId'
        ],
        'flags' => [],
        'key' => 'IDX_CALL_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'callId',
          1 => 'userId'
        ],
        'flags' => [],
        'key' => 'UNIQ_CALL_ID_USER_ID'
      ]
    ]
  ],
  'MeetingUser' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'meetingId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'meetingId'
        ],
        'flags' => [],
        'key' => 'IDX_MEETING_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'meetingId',
          1 => 'userId'
        ],
        'flags' => [],
        'key' => 'UNIQ_MEETING_ID_USER_ID'
      ]
    ]
  ],
  'UserWorkingTimeRange' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'userId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'workingTimeRangeId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeRangeId'
        ],
        'flags' => [],
        'key' => 'IDX_WORKING_TIME_RANGE_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'userId'
        ],
        'flags' => [],
        'key' => 'IDX_USER_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'workingTimeRangeId',
          1 => 'userId'
        ],
        'flags' => [],
        'key' => 'UNIQ_WORKING_TIME_RANGE_ID_USER_ID'
      ]
    ]
  ],
  'WorkingTimeCalendarWorkingTimeRange' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'workingTimeCalendarId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'workingTimeRangeId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeRangeId'
        ],
        'flags' => [],
        'key' => 'IDX_WORKING_TIME_RANGE_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'workingTimeCalendarId'
        ],
        'flags' => [],
        'key' => 'IDX_WORKING_TIME_CALENDAR_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'workingTimeRangeId',
          1 => 'workingTimeCalendarId'
        ],
        'flags' => [],
        'key' => 'UNIQ_WORKING_TIME_RANGE_ID_WORKING_TIME_CALENDAR_ID'
      ]
    ]
  ],
  'AccountTargetList' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'optedOut' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'flags' => [],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'accountId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_ACCOUNT_ID'
      ]
    ]
  ],
  'AccountDocument' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'documentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'documentId'
        ],
        'flags' => [],
        'key' => 'IDX_DOCUMENT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'flags' => [],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'documentId',
          1 => 'accountId'
        ],
        'flags' => [],
        'key' => 'UNIQ_DOCUMENT_ID_ACCOUNT_ID'
      ]
    ]
  ],
  'AccountContact' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'accountId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'role' => [
        'type' => 'varchar',
        'len' => 100
      ],
      'isInactive' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'accountId'
        ],
        'flags' => [],
        'key' => 'IDX_ACCOUNT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'contactId',
          1 => 'accountId'
        ],
        'flags' => [],
        'key' => 'UNIQ_CONTACT_ID_ACCOUNT_ID'
      ]
    ]
  ],
  'CallLead' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'callId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'leadId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'leadId'
        ],
        'flags' => [],
        'key' => 'IDX_LEAD_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'callId'
        ],
        'flags' => [],
        'key' => 'IDX_CALL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'leadId',
          1 => 'callId'
        ],
        'flags' => [],
        'key' => 'UNIQ_LEAD_ID_CALL_ID'
      ]
    ]
  ],
  'CallContact' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'callId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'callId'
        ],
        'flags' => [],
        'key' => 'IDX_CALL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'contactId',
          1 => 'callId'
        ],
        'flags' => [],
        'key' => 'UNIQ_CONTACT_ID_CALL_ID'
      ]
    ]
  ],
  'CampaignTargetListExcluding' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'flags' => [],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'campaignId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_CAMPAIGN_ID'
      ]
    ]
  ],
  'CampaignTargetList' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'campaignId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'campaignId'
        ],
        'flags' => [],
        'key' => 'IDX_CAMPAIGN_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'campaignId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_CAMPAIGN_ID'
      ]
    ]
  ],
  'CaseKnowledgeBaseArticle' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'caseId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'knowledgeBaseArticleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'knowledgeBaseArticleId'
        ],
        'flags' => [],
        'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'caseId'
        ],
        'flags' => [],
        'key' => 'IDX_CASE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'knowledgeBaseArticleId',
          1 => 'caseId'
        ],
        'flags' => [],
        'key' => 'UNIQ_KNOWLEDGE_BASE_ARTICLE_ID_CASE_ID'
      ]
    ]
  ],
  'CaseContact' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'caseId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'caseId'
        ],
        'flags' => [],
        'key' => 'IDX_CASE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'contactId',
          1 => 'caseId'
        ],
        'flags' => [],
        'key' => 'UNIQ_CONTACT_ID_CASE_ID'
      ]
    ]
  ],
  'ContactDocument' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'documentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'documentId'
        ],
        'flags' => [],
        'key' => 'IDX_DOCUMENT_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'documentId',
          1 => 'contactId'
        ],
        'flags' => [],
        'key' => 'UNIQ_DOCUMENT_ID_CONTACT_ID'
      ]
    ]
  ],
  'ContactTargetList' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'optedOut' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'contactId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_CONTACT_ID'
      ]
    ]
  ],
  'ContactMeeting' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'meetingId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'meetingId'
        ],
        'flags' => [],
        'key' => 'IDX_MEETING_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'meetingId',
          1 => 'contactId'
        ],
        'flags' => [],
        'key' => 'UNIQ_MEETING_ID_CONTACT_ID'
      ]
    ]
  ],
  'ContactOpportunity' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'contactId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'opportunityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'role' => [
        'type' => 'varchar',
        'len' => 50
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'opportunityId'
        ],
        'flags' => [],
        'key' => 'IDX_OPPORTUNITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'contactId'
        ],
        'flags' => [],
        'key' => 'IDX_CONTACT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'opportunityId',
          1 => 'contactId'
        ],
        'flags' => [],
        'key' => 'UNIQ_OPPORTUNITY_ID_CONTACT_ID'
      ]
    ]
  ],
  'DocumentLead' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'documentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'leadId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'leadId'
        ],
        'flags' => [],
        'key' => 'IDX_LEAD_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'documentId'
        ],
        'flags' => [],
        'key' => 'IDX_DOCUMENT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'leadId',
          1 => 'documentId'
        ],
        'flags' => [],
        'key' => 'UNIQ_LEAD_ID_DOCUMENT_ID'
      ]
    ]
  ],
  'DocumentOpportunity' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'documentId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'opportunityId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'opportunityId'
        ],
        'flags' => [],
        'key' => 'IDX_OPPORTUNITY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'documentId'
        ],
        'flags' => [],
        'key' => 'IDX_DOCUMENT_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'opportunityId',
          1 => 'documentId'
        ],
        'flags' => [],
        'key' => 'UNIQ_OPPORTUNITY_ID_DOCUMENT_ID'
      ]
    ]
  ],
  'KnowledgeBaseArticleKnowledgeBaseCategory' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'knowledgeBaseArticleId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'knowledgeBaseCategoryId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'knowledgeBaseCategoryId'
        ],
        'flags' => [],
        'key' => 'IDX_KNOWLEDGE_BASE_CATEGORY_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'knowledgeBaseArticleId'
        ],
        'flags' => [],
        'key' => 'IDX_KNOWLEDGE_BASE_ARTICLE_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'knowledgeBaseCategoryId',
          1 => 'knowledgeBaseArticleId'
        ],
        'flags' => [],
        'key' => 'UNIQ_KNOWLEDGE_BASE_CATEGORY_ID_KNOWLEDGE_BASE_ARTICLE_ID'
      ]
    ]
  ],
  'LeadTargetList' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'leadId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'optedOut' => [
        'type' => 'bool',
        'default' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'leadId'
        ],
        'flags' => [],
        'key' => 'IDX_LEAD_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'leadId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_LEAD_ID'
      ]
    ]
  ],
  'LeadMeeting' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'leadId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'meetingId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'status' => [
        'type' => 'varchar',
        'len' => 36,
        'default' => 'None'
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'meetingId'
        ],
        'flags' => [],
        'key' => 'IDX_MEETING_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'leadId'
        ],
        'flags' => [],
        'key' => 'IDX_LEAD_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'meetingId',
          1 => 'leadId'
        ],
        'flags' => [],
        'key' => 'UNIQ_MEETING_ID_LEAD_ID'
      ]
    ]
  ],
  'MassEmailTargetListExcluding' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'massEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'massEmailId'
        ],
        'flags' => [],
        'key' => 'IDX_MASS_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'massEmailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_MASS_EMAIL_ID'
      ]
    ]
  ],
  'MassEmailTargetList' => [
    'skipRebuild' => true,
    'attributes' => [
      'id' => [
        'type' => 'id',
        'autoincrement' => true,
        'dbType' => 'bigint'
      ],
      'deleted' => [
        'type' => 'bool',
        'default' => false
      ],
      'massEmailId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ],
      'targetListId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'notNull' => false
      ]
    ],
    'indexes' => [
      0 => [
        'type' => 'index',
        'columns' => [
          0 => 'targetListId'
        ],
        'flags' => [],
        'key' => 'IDX_TARGET_LIST_ID'
      ],
      1 => [
        'type' => 'index',
        'columns' => [
          0 => 'massEmailId'
        ],
        'flags' => [],
        'key' => 'IDX_MASS_EMAIL_ID'
      ],
      2 => [
        'type' => 'unique',
        'columns' => [
          0 => 'targetListId',
          1 => 'massEmailId'
        ],
        'flags' => [],
        'key' => 'UNIQ_TARGET_LIST_ID_MASS_EMAIL_ID'
      ]
    ]
  ],
  'EmailTemplateCategoryPath' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'integer',
        'len' => 11,
        'autoincrement' => true
      ],
      'ascendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'descendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ]
    ],
    'indexes' => [
      'ascendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'ascendorId'
        ],
        'key' => 'IDX_ASCENDOR_ID'
      ],
      'descendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'descendorId'
        ],
        'key' => 'IDX_DESCENDOR_ID'
      ]
    ]
  ],
  'DocumentFolderPath' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'integer',
        'len' => 11,
        'autoincrement' => true
      ],
      'ascendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'descendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ]
    ],
    'indexes' => [
      'ascendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'ascendorId'
        ],
        'key' => 'IDX_ASCENDOR_ID'
      ],
      'descendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'descendorId'
        ],
        'key' => 'IDX_DESCENDOR_ID'
      ]
    ]
  ],
  'KnowledgeBaseCategoryPath' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'integer',
        'len' => 11,
        'autoincrement' => true
      ],
      'ascendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'descendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ]
    ],
    'indexes' => [
      'ascendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'ascendorId'
        ],
        'key' => 'IDX_ASCENDOR_ID'
      ],
      'descendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'descendorId'
        ],
        'key' => 'IDX_DESCENDOR_ID'
      ]
    ]
  ],
  'TargetListCategoryPath' => [
    'attributes' => [
      'id' => [
        'type' => 'id',
        'dbType' => 'integer',
        'len' => 11,
        'autoincrement' => true
      ],
      'ascendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ],
      'descendorId' => [
        'len' => 17,
        'dbType' => 'string',
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false
      ]
    ],
    'indexes' => [
      'ascendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'ascendorId'
        ],
        'key' => 'IDX_ASCENDOR_ID'
      ],
      'descendorId' => [
        'type' => 'index',
        'columns' => [
          0 => 'descendorId'
        ],
        'key' => 'IDX_DESCENDOR_ID'
      ]
    ]
  ]
];
