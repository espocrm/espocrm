<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Stream;

use Espo\Core\Acl\Exceptions\NotAvailable;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;

use Espo\Core\Utils\SystemUser;
use Espo\Entities\Subscription;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Order;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;

use Espo\Entities\User;
use Espo\Entities\Note;
use Espo\Entities\Email;
use Espo\Entities\EmailAddress;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\AclManager;
use Espo\Core\Acl\Table;
use Espo\Core\Acl\Exceptions\NotImplemented as AclNotImplemented;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Acl\UserAclManagerProvider;

use stdClass;
use DateTime;
use LogicException;

class Service
{
    /** @var ?array<string, string> */
    private $statusStyles = null;
    /** @var ?array<string, string> */
    private $statusFields = null;
    /** @var string[] */
    private $successDefaultStyleList = [
        'Held',
        'Closed Won',
        'Closed',
        'Completed',
        'Complete',
        'Sold',
    ];
    /** @var string[] */
    private $dangerDefaultStyleList = [
        'Not Held',
        'Closed Lost',
        'Dead',
    ];

    /**
     * @var array<
     *   string,
     *   array<
     *     string,
     *     array{
     *       actualList: string[],
     *       notActualList: string[],
     *       fieldType: string,
     *     }
     *   >
     * >
     */
    private $auditedFieldsCache = [];

    /**
     * When a record is re-assigned, ACL will be recalculated for related notes
     * created within the period.
     */
    private const NOTE_ACL_PERIOD = '3 days';
    private const NOTE_ACL_LIMIT = 50;
    /**
     * Not used currently.
     */
    private const NOTE_NOTIFICATION_PERIOD = '1 hour';

    private EntityManager $entityManager;
    private Config $config;
    private User $user;
    private Metadata $metadata;
    private AclManager $aclManager;
    private FieldUtil $fieldUtil;
    private SelectBuilderFactory $selectBuilderFactory;
    private UserAclManagerProvider $userAclManagerProvider;
    private RecordServiceContainer $recordServiceContainer;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        User $user,
        Metadata $metadata,
        AclManager $aclManager,
        FieldUtil $fieldUtil,
        SelectBuilderFactory $selectBuilderFactory,
        UserAclManagerProvider $userAclManagerProvider,
        RecordServiceContainer $recordServiceContainer,
        private SystemUser $systemUser
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->user = $user;
        $this->metadata = $metadata;
        $this->aclManager = $aclManager;
        $this->fieldUtil = $fieldUtil;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->userAclManagerProvider = $userAclManagerProvider;
        $this->recordServiceContainer = $recordServiceContainer;
    }

    /**
     * @return array<string, string>
     */
    private function getStatusStyles(): array
    {
        if (empty($this->statusStyles)) {
            $this->statusStyles = $this->metadata->get('entityDefs.Note.statusStyles', []);
        }

        return $this->statusStyles;
    }

    /**
     * @return array<string, string>
     */
    private function getStatusFields(): array
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = [];

            /** @var array<string, array<string, mixed>> $scopes */
            $scopes = $this->metadata->get('scopes', []);

            foreach ($scopes as $scope => $data) {
                /** @var ?string $statusField */
                $statusField = $data['statusField'] ?? null;

                if (!$statusField) {
                    continue;
                }

                $this->statusFields[$scope] = $statusField;
            }
        }

        return $this->statusFields;
    }

    public function checkIsFollowed(Entity $entity, ?string $userId = null): bool
    {
        if (!$userId) {
            $userId = $this->user->getId();
        }

        return (bool) $this->entityManager
            ->getRDBRepository(Subscription::ENTITY_TYPE)
            ->select(['id'])
            ->where([
                'userId' => $userId,
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->findOne();
    }

    /**
     * @param string[] $sourceUserIdList
     *
     * @internal Must be left for bc.
     */
    public function followEntityMass(Entity $entity, array $sourceUserIdList, bool $skipAclCheck = false): void
    {
        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return;
        }

        $userIdList = [];

        foreach ($sourceUserIdList as $id) {
            if ($id === $this->systemUser->getId()) {
                continue;
            }

            $userIdList[] = $id;
        }

        $userIdList = array_unique($userIdList);

        if (!$skipAclCheck) {
            foreach ($userIdList as $i => $userId) {
                $user = $this->entityManager
                    ->getRDBRepository(User::ENTITY_TYPE)
                    ->select([
                        'id',
                        'type',
                        'isActive',
                    ])
                    ->where([
                        'id' => $userId,
                        'isActive' => true,
                    ])
                    ->findOne();

                if (!$user) {
                    unset($userIdList[$i]);

                    continue;
                }

                try {
                    $hasAccess = $this->aclManager->checkEntityStream($user, $entity);
                }
                catch (AclNotImplemented $e) {
                    $hasAccess = false;
                }

                if (!$hasAccess) {
                    unset($userIdList[$i]);
                }
            }

            $userIdList = array_values($userIdList);
        }

        if (empty($userIdList)) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Subscription::ENTITY_TYPE)
            ->where([
                'userId' => $userIdList,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $collection = new EntityCollection();

        foreach ($userIdList as $userId) {
            $subscription = $this->entityManager->getNewEntity(Subscription::ENTITY_TYPE);

            $subscription->set([
                'userId' => $userId,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ]);

            $collection[] = $subscription;
        }

        $this->entityManager->getMapper()->massInsert($collection);
    }

    public function followEntity(Entity $entity, string $userId, bool $skipAclCheck = false): bool
    {
        if ($userId === $this->systemUser->getId()) {
            return false;
        }

        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return false;
        }

        if (!$skipAclCheck) {
            $user = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
                ->where([
                    'id' => $userId,
                    'isActive' => true,
                ])
                ->findOne();

            if (!$user) {
                return false;
            }

            $aclManager = $this->getUserAclManager($user);

            if (!$aclManager) {
                return false;
            }

            if (!$aclManager->check($user, $entity, Table::ACTION_STREAM)) {
                return false;
            }
        }

        if ($this->checkIsFollowed($entity, $userId)) {
            return true;
        }

        $this->entityManager->createEntity(Subscription::ENTITY_TYPE, [
            'entityId' => $entity->getId(),
            'entityType' => $entity->getEntityType(),
            'userId' => $userId,
        ]);

        return true;
    }

    public function unfollowEntity(Entity $entity, string $userId): bool
    {
        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return false;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Subscription::ENTITY_TYPE)
            ->where([
                'userId' => $userId,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        return true;
    }

    public function unfollowAllUsersFromEntity(Entity $entity): void
    {
        if (!$entity->hasId()) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(Subscription::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }


    private function loadAssignedUserName(Entity $entity): void
    {
        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select(['name'])
            ->where([
                'id' =>  $entity->get('assignedUserId'),
            ])
            ->findOne();

        if ($user) {
            $entity->set('assignedUserName', $user->getName());
        }
    }

    /**
     * Notes having `related` or `superParent` are subjects to access control
     * through `users` and `teams` fields.
     *
     * When users or teams of `related` or `parent` record are changed
     * the note record will be changed too.
     */
    private function processNoteTeamsUsers(Note $note, Entity $entity): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $note->setAclIsProcessed();

        $note->set('teamsIds', []);
        $note->set('usersIds', []);

        if ($entity->hasLinkMultipleField('teams') && $entity->has('teamsIds')) {
            $teamIdList = $entity->get('teamsIds');

            if (!empty($teamIdList)) {
                $note->set('teamsIds', $teamIdList);
            }
        }

        $ownerUserField = $this->aclManager->getReadOwnerUserField($entity->getEntityType());

        if (!$ownerUserField) {
            return;
        }

        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        if (!$defs->hasField($ownerUserField)) {
            return;
        }

        $fieldDefs = $defs->getField($ownerUserField);

        if ($fieldDefs->getType() === 'linkMultiple') {
            $ownerUserIdAttribute = $ownerUserField . 'Ids';
        }
        else if ($fieldDefs->getType() === 'link') {
            $ownerUserIdAttribute = $ownerUserField . 'Id';
        }
        else {
            return;
        }

        if (!$entity->has($ownerUserIdAttribute)) {
            return;
        }

        if ($fieldDefs->getType() === 'linkMultiple') {
            $userIdList = $entity->getLinkMultipleIdList($ownerUserField);
        }
        else {
            $userId = $entity->get($ownerUserIdAttribute);

            if (!$userId) {
                return;
            }

            $userIdList = [$userId];
        }

        $note->set('usersIds', $userIdList);
    }

    public function noteEmailReceived(Entity $entity, Email $email, bool $isInitial = false): void
    {
        $entityType = $entity->getEntityType();

        if (
            $this->entityManager
                ->getRDBRepository(Note::ENTITY_TYPE)
                ->where([
                    'type' => Note::TYPE_EMAIL_RECEIVED,
                    'parentId' => $entity->getId(),
                    'parentType' => $entityType,
                    'relatedId' => $email->getId(),
                    'relatedType' => Email::ENTITY_TYPE,
                ])
                ->findOne()
        ) {
            return;
        }

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_EMAIL_RECEIVED);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->getId());
        $note->set('relatedType', Email::ENTITY_TYPE);

        $this->processNoteTeamsUsers($note, $email);

        if ($email->get('accountId')) {
            $note->set('superParentId', $email->get('accountId'));
            $note->set('superParentType', Account::ENTITY_TYPE);
        }

        $withContent = in_array($entityType, $this->config->get('streamEmailWithContentEntityTypeList', []));

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = [];

        $data['emailId'] = $email->getId();
        $data['emailName'] = $email->get('name');
        $data['isInitial'] = $isInitial;

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $from = $email->get('from');

        if ($from) {
            $person = $this->getEmailAddressRepository()->getEntityByAddress($from);

            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->getId();
            }
        }

        $note->set('data', (object) $data);

        $this->entityManager->saveEntity($note);
    }

    public function noteEmailSent(Entity $entity, Email $email): void
    {
        $entityType = $entity->getEntityType();

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_EMAIL_SENT);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->getId());
        $note->set('relatedType', Email::ENTITY_TYPE);

        $this->processNoteTeamsUsers($note, $email);

        $accountLink = $email->getAccount();

        if ($accountLink) {
            $note->set('superParentId', $accountLink->getId());
            $note->set('superParentType', Account::ENTITY_TYPE);
        }

        $withContent = in_array($entityType, $this->config->get('streamEmailWithContentEntityTypeList', []));

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = [];

        $data['emailId'] = $email->getId();
        $data['emailName'] = $email->getSubject();

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $user = $this->user;

        $person = null;

        if (!$user->isSystem()) {
            $person = $user;
        }
        else {
            $from = $email->get('from');

            if ($from) {
                $person = $this->getEmailAddressRepository()->getEntityByAddress($from);
            }
        }

        if ($person) {
            $data['personEntityType'] = $person->getEntityType();
            $data['personEntityName'] = $person->get('name');
            $data['personEntityId'] = $person->getId();
        }

        $note->set('data', (object) $data);

        $this->entityManager->saveEntity($note);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteCreate(Entity $entity, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_CREATE);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', Account::ENTITY_TYPE);

            // only if has super parent
            $this->processNoteTeamsUsers($note, $entity);
        }

        $data = [];

        if ($entity->get('assignedUserId')) {
            if (!$entity->has('assignedUserName')) {
                $this->loadAssignedUserName($entity);
            }

            $data['assignedUserId'] = $entity->get('assignedUserId');
            $data['assignedUserName'] = $entity->get('assignedUserName');
        }

        $statusFields = $this->getStatusFields();

        if (!empty($statusFields[$entityType])) {
            $field = $statusFields[$entityType];
            $value = $entity->get($field);

            if (!empty($value)) {
                $data['statusValue'] = $value;
                $data['statusField'] = $field;
                $data['statusStyle'] = $this->getStatusStyle($entityType, $field, $value);
            }
        }

        $note->set('data', (object) $data);

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param mixed $value
     */
    private function getStatusStyle(string $entityType, string $field, $value): string
    {
        $style = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'style', $value]);

        if ($style) {
            return $style;
        }

        $statusStyles = $this->getStatusStyles();

        if (isset($statusStyles[$entityType]) && isset($statusStyles[$entityType][$value])) {
            return (string) $statusStyles[$entityType][$value];
        }

        if (in_array($value, $this->successDefaultStyleList)) {
            return 'success';
        }

        if (in_array($value, $this->dangerDefaultStyleList)) {
            return 'danger';
        }

        return 'default';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteCreateRelated(
        Entity $entity,
        string $parentType,
        string $parentId,
        array $options = []
    ): void {

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $entityType = $entity->getEntityType();

        $note->set('type', Note::TYPE_CREATE_RELATED);
        $note->set('parentId', $parentId);
        $note->set('parentType', $parentType);
        $note->set([
            'relatedType' => $entityType,
            'relatedId' => $entity->getId(),
        ]);

        $this->processNoteTeamsUsers($note, $entity);

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', Account::ENTITY_TYPE);
        }

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteRelate(Entity $entity, string $parentType, string $parentId, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $existing = $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->select(['id'])
            ->where([
                'type' => Note::TYPE_RELATE,
                'parentId' => $parentId,
                'parentType' => $parentType,
                'relatedId' => $entity->getId(),
                'relatedType' => $entityType,
            ])
            ->findOne();

        if ($existing) {
            return;
        }

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set([
            'type' => Note::TYPE_RELATE,
            'parentId' => $parentId,
            'parentType' => $parentType,
            'relatedType' => $entityType,
            'relatedId' => $entity->getId(),
        ]);

        $this->processNoteTeamsUsers($note, $entity);

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteUnrelate(Entity $entity, string $parentType, string $parentId, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $existing = $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->select(['id'])
            ->where([
                'type' => Note::TYPE_UNRELATE,
                'parentId' => $parentId,
                'parentType' => $parentType,
                'relatedId' => $entity->getId(),
                'relatedType' => $entityType,
            ])
            ->findOne();

        if ($existing) {
            return;
        }

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set([
            'type' => Note::TYPE_UNRELATE,
            'parentId' => $parentId,
            'parentType' => $parentType,
            'relatedType' => $entityType,
            'relatedId' => $entity->getId(),
        ]);

        $this->processNoteTeamsUsers($note, $entity);

        $o = [];

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteAssign(Entity $entity, array $options = []): void
    {
        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_ASSIGN);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entity->getEntityType());

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', Account::ENTITY_TYPE);

            // only if has super parent
            $this->processNoteTeamsUsers($note, $entity);
        }

        if ($entity->get('assignedUserId')) {
            if (!$entity->has('assignedUserName')) {
                $this->loadAssignedUserName($entity);
            }

            $note->set('data', [
                'assignedUserId' => $entity->get('assignedUserId'),
                'assignedUserName' => $entity->get('assignedUserName'),
            ]);
        } else {
            $note->set('data', [
                'assignedUserId' => null
            ]);
        }

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteStatus(Entity $entity, string $field, array $options = []): void
    {
        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_STATUS);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entity->getEntityType());

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', Account::ENTITY_TYPE);

            $this->processNoteTeamsUsers($note, $entity);
        }

        $entityType = $entity->getEntityType();

        $value = $entity->get($field);

        $style = $this->getStatusStyle($entityType, $field, $value);

        $note->set('data', [
            'field' => $field,
            'value' => $value,
            'style' => $style,
        ]);

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @return array<
     *   string,
     *   array{
     *     actualList: string[],
     *     notActualList: string[],
     *     fieldType: string,
     *   }
     * >
     */
    private function getAuditedFieldsData(Entity $entity): array
    {
        $entityType = $entity->getEntityType();

        $statusFields = $this->getStatusFields();

        if (array_key_exists($entityType, $this->auditedFieldsCache)) {
            return $this->auditedFieldsCache[$entityType];
        }

        /** @var array<string, array<string, mixed>> $fields */
        $fields = $this->metadata->get(['entityDefs', $entityType, 'fields']);

        $auditedFields = [];

        foreach ($fields as $field => $defs) {
            if (empty($defs['audited'])) {
                continue;
            }

            if (!empty($statusFields[$entityType]) && $statusFields[$entityType] === $field) {
                continue;
            }

            /** @var ?string $type */
            $type = $defs['type'] ?? null;

            if (!$type) {
                continue;
            }

            $auditedFields[$field] = [];

            $auditedFields[$field]['actualList'] =
                $this->fieldUtil->getActualAttributeList($entityType, $field);

            $auditedFields[$field]['notActualList'] =
                $this->fieldUtil->getNotActualAttributeList($entityType, $field);

            $auditedFields[$field]['fieldType'] = $type;
        }

        $this->auditedFieldsCache[$entityType] = $auditedFields;

        return $this->auditedFieldsCache[$entityType];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function handleAudited(Entity $entity, array $options = []): void
    {
        $auditedFields = $this->getAuditedFieldsData($entity);

        $updatedFieldList = [];

        $was = [];
        $became = [];

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($auditedFields as $field => $item) {
            $updated = false;

            foreach ($item['actualList'] as $attribute) {
                if ($entity->hasFetched($attribute) && $entity->isAttributeChanged($attribute)) {
                    $updated = true;

                    break;
                }
            }

            if (!$updated) {
                continue;
            }

            $updatedFieldList[] = $field;

            $fieldDefs = $entityDefs->hasField($field) ? $entityDefs->getField($field) : null;

            if (
                $fieldDefs &&
                in_array($fieldDefs->getType(), ['text', 'wysiwyg'])
            ) {
                continue;
            }

            foreach ($item['actualList'] as $attribute) {
                $was[$attribute] = $entity->getFetched($attribute);
                $became[$attribute] = $entity->get($attribute);
            }

            foreach ($item['notActualList'] as $attribute) {
                $was[$attribute] = $entity->getFetched($attribute);
                $became[$attribute] = $entity->get($attribute);
            }

            if ($item['fieldType'] === 'linkParent') {
                $wasParentType = $was[$field . 'Type'];
                $wasParentId = $was[$field . 'Id'];

                if ($wasParentType && $wasParentId && $this->entityManager->hasRepository($wasParentType)) {
                    $wasParent = $this->entityManager->getEntity($wasParentType, $wasParentId);

                    if ($wasParent) {
                        $was[$field . 'Name'] = $wasParent->get('name');
                    }
                }
            }
        }

        if (count($updatedFieldList) === 0) {
            return;
        }

        /** @var Note $note */
        $note = $this->entityManager->getNewEntity(Note::ENTITY_TYPE);

        $note->set('type', Note::TYPE_UPDATE);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entity->getEntityType());

        $note->set('data', [
            'fields' => $updatedFieldList,
            'attributes' => [
                'was' => (object) $was,
                'became' => (object) $became,
            ],
        ]);

        $o = [];

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @return string[]
     * @deprecated Use `getEntityFollowerIdList`.
     */
    public function getEntityFolowerIdList(Entity $entity): array
    {
        return $this->getEntityFollowerIdList($entity);
    }

    /**
     * @return string[]
     * @internal Must be left for backward compatibility.
     */
    public function getEntityFollowerIdList(Entity $entity): array
    {
        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id'])
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'subscription.userId=:' => 'user.id',
                    'subscription.entityId' => $entity->getId(),
                    'subscription.entityType' => $entity->getEntityType(),
                ]
            )
            ->where(['isActive' => true])
            ->find();

        $idList = [];

        foreach ($userList as $user) {
            $idList[] = $user->getId();
        }

        return $idList;
    }

    /**
     * @return RecordCollection<User>
     */
    public function findEntityFollowers(Entity $entity, SearchParams $searchParams): RecordCollection
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(User::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        if (!$searchParams->getOrderBy()) {
            $builder->order([]);
            $builder->order(
                Order::createByPositionInList(Expr::column('id'), [$this->user->getId()])
            );
            $builder->order('name');
        }

        $builder->join(
            Subscription::ENTITY_TYPE,
            'subscription',
            [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->getId(),
                'subscription.entityType' => $entity->getEntityType(),
            ]
        );

        $query = $builder->build();

        /** @var \Espo\ORM\Collection<User> $collection */
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->clone($query)
            ->find();

        $total = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->count();

        $userService = $this->recordServiceContainer->get(User::ENTITY_TYPE);

        foreach ($collection as $e) {
            $userService->prepareEntityForOutput($e);
        }

        /** @var RecordCollection<User> */
        return new RecordCollection($collection, $total);
    }

    /**
     * @return array{
     *   idList: string[],
     *   nameMap: stdClass,
     * }
     */
    public function getEntityFollowers(Entity $entity, int $offset = 0, ?int $limit = null): array
    {
        if (!$limit) {
            $limit = 200;
        }

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id', 'name'])
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'subscription.userId=:' => 'user.id',
                    'subscription.entityId' => $entity->getId(),
                    'subscription.entityType' => $entity->getEntityType()
                ]
            )
            ->limit($offset, $limit)
            ->where([
                'isActive' => true,
            ])
            ->order(
                Order::createByPositionInList(Expr::column('id'), [$this->user->getId()])
            )
            ->order('name')
            ->find();

        $data = [
            'idList' => [],
            'nameMap' => (object) [],
        ];

        foreach ($userList as $user) {
            /** @var string $id */
            $id = $user->getId();

            $data['idList'][] = $id;
            $data['nameMap']->$id = $user->get('name');
        }

        return $data;
    }

    private function getUserAclManager(User $user): ?AclManager
    {
        try {
            return $this->userAclManagerProvider->get($user);
        }
        catch (NotAvailable) {
            return null;
        }
    }

    /**
     * @return Collection<User>
     */
    public function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false): Collection
    {
        if (!$this->metadata->get(['scopes', $parentType, 'stream'])) {
            /** @var Collection<User> */
            return $this->entityManager->getCollectionFactory()->create(User::ENTITY_TYPE, []);
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Subscription::ENTITY_TYPE)
            ->select('userId')
            ->where([
                'entityId' => $parentId,
                'entityType' => $parentType,
            ]);

        if ($isInternal) {
            $builder
                ->join(User::ENTITY_TYPE, 'user', ['user.id:' => 'userId'])
                ->where([
                    'user.type!=' => User::TYPE_PORTAL,
                ]);
        }

        $subQuery = $builder->build();

        /** @var Collection<User> */
        return $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'isActive' => true,
                'id=s' => $subQuery,
            ])
            ->select(['id', 'type'])
            ->find();
    }

    /**
     * Changes users and teams of notes related to an entity according users and teams of the entity.
     *
     * Notes having `related` or `superParent` are subjects to access control
     * through `users` and `teams` fields.
     *
     * When users or teams of `related` or `parent` record are changed
     * the note record will be changed too.
     */
    public function processNoteAcl(Entity $entity, bool $forceProcessNoteNotifications = false): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $entityType = $entity->getEntityType();

        if (in_array($entityType, ['Note', 'User', 'Team', 'Role', 'Portal', 'PortalRole'])) {
            return;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'acl'])) {
            return;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'object'])) {
            return;
        }

        $usersAttributeIsChanged = false;
        $teamsAttributeIsChanged = false;

        $ownerUserField = $this->aclManager->getReadOwnerUserField($entityType);

        /* @var \Espo\ORM\Defs\EntityDefs $defs */
        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $userIdList = [];
        $teamIdList = [];

        if ($ownerUserField) {
            if (!$defs->hasField($ownerUserField)) {
                throw new LogicException("Non-existing read-owner user field.");
            }

            $fieldDefs = $defs->getField($ownerUserField);

            if ($fieldDefs->getType() === 'linkMultiple') {
                $ownerUserIdAttribute = $ownerUserField . 'Ids';
            }
            else if ($fieldDefs->getType() === 'link') {
                $ownerUserIdAttribute = $ownerUserField . 'Id';
            }
            else {
                throw new LogicException("Bad read-owner user field type.");
            }

            if ($entity->isAttributeChanged($ownerUserIdAttribute)) {
                $usersAttributeIsChanged = true;
            }

            if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
                if ($fieldDefs->getType() === 'linkMultiple') {
                    $userIdList = $entity->getLinkMultipleIdList($ownerUserField);
                }
                else {
                    $userId = $entity->get($ownerUserIdAttribute);

                    $userIdList = $userId ? [$userId] : [];
                }
            }
        }

        if ($entity->hasLinkMultipleField('teams')) {
            if ($entity->isAttributeChanged('teamsIds')) {
                $teamsAttributeIsChanged = true;
            }

            if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
                $teamIdList = $entity->getLinkMultipleIdList('teams');
            }
        }

        if (!$usersAttributeIsChanged && !$teamsAttributeIsChanged && !$forceProcessNoteNotifications) {
            return;
        }

        $limit = $this->config->get('noteAclLimit', self::NOTE_ACL_LIMIT);

        $noteList = $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->where([
                'OR' => [
                    [
                        'relatedId' => $entity->getId(),
                        'relatedType' => $entityType,
                    ],
                    [
                        'parentId' => $entity->getId(),
                        'parentType' => $entityType,
                        'superParentId!=' => null,
                        'relatedId' => null,
                    ]
                ]
            ])
            ->select([
                'id',
                'parentType',
                'parentId',
                'superParentType',
                'superParentId',
                'isInternal',
                'relatedType',
                'relatedId',
                'createdAt',
            ])
            ->order('number', 'DESC')
            ->limit(0, $limit)
            ->find();

        $notificationPeriod = '-' . $this->config->get('noteNotificationPeriod', self::NOTE_NOTIFICATION_PERIOD);
        $aclPeriod = '-' . $this->config->get('noteAclPeriod', self::NOTE_ACL_PERIOD);

        $notificationThreshold = (new DateTime())->modify($notificationPeriod);
        $aclThreshold = (new DateTime())->modify($aclPeriod);

        foreach ($noteList as $note) {
            $this->processNoteAclItem($entity, $note, [
                'teamsAttributeIsChanged' => $teamsAttributeIsChanged,
                'usersAttributeIsChanged' => $usersAttributeIsChanged,
                'forceProcessNoteNotifications' => $forceProcessNoteNotifications,
                'teamIdList' => $teamIdList,
                'userIdList' => $userIdList,
                'notificationThreshold' => $notificationThreshold,
                'aclThreshold' => $aclThreshold,
            ]);
        }
    }

    /**
     * @param array{
     *   teamsAttributeIsChanged: bool,
     *   usersAttributeIsChanged: bool,
     *   forceProcessNoteNotifications: bool,
     *   teamIdList: string[],
     *   userIdList: string[],
     *   notificationThreshold: \DateTimeInterface,
     *   aclThreshold: \DateTimeInterface,
     * } $params
     * @return void
     */
    private function processNoteAclItem(Entity $entity, Note $note, array $params): void
    {
        $teamsAttributeIsChanged = $params['teamsAttributeIsChanged'];
        $usersAttributeIsChanged = $params['usersAttributeIsChanged'];
        $forceProcessNoteNotifications = $params['forceProcessNoteNotifications'];

        $teamIdList = $params['teamIdList'];
        $userIdList = $params['userIdList'];

        $notificationThreshold = $params['notificationThreshold'];
        $aclThreshold = $params['aclThreshold'];

        $createdAt = $note->getCreatedAt();

        if (!$createdAt) {
            return;
        }

        if (!$entity->isNew()) {
            if ($createdAt->toTimestamp() < $notificationThreshold->getTimestamp()) {
                $forceProcessNoteNotifications = false;
            }

            if ($createdAt->toTimestamp() < $aclThreshold->getTimestamp()) {
                return;
            }
        }

        if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
            $note->set('teamsIds', $teamIdList);
        }

        if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
            $note->set('usersIds', $userIdList);
        }

        $this->entityManager->saveEntity($note, [
            'forceProcessNotifications' => $forceProcessNoteNotifications,
        ]);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
