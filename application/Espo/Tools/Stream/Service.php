<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Field\DateTime;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\StreamSubscription;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\UpdateBuilder;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;

use Espo\Entities\User;
use Espo\Entities\Note;
use Espo\Entities\Email;
use Espo\Entities\EmailAddress;

use Espo\Core\Acl\Exceptions\NotAvailable;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\Utils\SystemUser;
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
        'Closed Lost',
    ];

    private const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;

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

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private User $user,
        private Metadata $metadata,
        private AclManager $aclManager,
        private FieldUtil $fieldUtil,
        private SelectBuilderFactory $selectBuilderFactory,
        private UserAclManagerProvider $userAclManagerProvider,
        private RecordServiceContainer $recordServiceContainer,
        private SystemUser $systemUser
    ) {}

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

    private function getStatusField(string $entityType): ?string
    {
        return $this->getStatusFields()[$entityType] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function getStatusFields(): array
    {
        if ($this->statusFields === null) {
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
            ->getRDBRepository(StreamSubscription::ENTITY_TYPE)
            ->select([Attribute::ID])
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
        if (!$this->checkIsEnabled($entity->getEntityType())) {
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
                        Attribute::ID,
                        'type',
                        'isActive',
                    ])
                    ->where([
                        Attribute::ID => $userId,
                        'isActive' => true,
                    ])
                    ->findOne();

                if (!$user) {
                    unset($userIdList[$i]);

                    continue;
                }

                try {
                    $hasAccess = $this->aclManager->checkEntityStream($user, $entity);
                } catch (AclNotImplemented) {
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
            ->from(StreamSubscription::ENTITY_TYPE)
            ->where([
                'userId' => $userIdList,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $collection = new EntityCollection();

        foreach ($userIdList as $userId) {
            $subscription = $this->entityManager->getNewEntity(StreamSubscription::ENTITY_TYPE);

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

        if (!$this->checkIsEnabled($entity->getEntityType())) {
            return false;
        }

        if (!$skipAclCheck) {
            $user = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $userId,
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

        $this->entityManager->createEntity(StreamSubscription::ENTITY_TYPE, [
            'entityId' => $entity->getId(),
            'entityType' => $entity->getEntityType(),
            'userId' => $userId,
        ]);

        return true;
    }

    public function unfollowEntity(Entity $entity, string $userId): bool
    {
        if (!$this->checkIsEnabled($entity->getEntityType())) {
            return false;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(StreamSubscription::ENTITY_TYPE)
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
            ->from(StreamSubscription::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    /**
     * Unfollow all portal users from an entity.
     *
     * @since 9.0.0
     */
    public function unfollowPortalUsersFromEntity(Entity $entity): void
    {
        if (!$entity->hasId()) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(StreamSubscription::ENTITY_TYPE)
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->where(
                Condition::in(
                    Expression::column('userId'),
                    SelectBuilder::create()
                        ->from(User::ENTITY_TYPE)
                        ->select('id')
                        ->where(['type' => User::TYPE_PORTAL])
                        ->build()
                )
            )
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    private function loadAssignedUserName(Entity $entity): void
    {
        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select(['name'])
            ->where([
                Attribute::ID =>  $entity->get('assignedUserId'),
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
     * When users or teams of `related` or `parent` record are changed,
     * the note record will be changed too.
     */
    private function processNoteTeamsUsers(Note $note, Entity $entity): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $note->setAclIsProcessed();
        $note->setTeamsIds([]);

        if ($entity->hasLinkMultipleField(Field::TEAMS)) {
            $note->setTeamsIds($entity->getLinkMultipleIdList(Field::TEAMS));
        }

        $userIds = array_merge(
            $this->getAssignedUserIds($entity),
            $this->getCollaboratorIds($entity)
        );

        $userIds = array_values(array_unique($userIds));

        $note->setUsersIds($userIds);
    }

    /**
     * @return string[]
     */
    private function getAssignedUserIds(CoreEntity $entity): array
    {
        $ownerUserField = $this->aclManager->getReadOwnerUserField($entity->getEntityType());

        if (!$ownerUserField) {
            return [];
        }

        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        if (!$defs->hasField($ownerUserField)) {
            return [];
        }

        $fieldDefs = $defs->getField($ownerUserField);

        if ($fieldDefs->getType() === FieldType::LINK_MULTIPLE) {
            $ownerUserIdAttribute = $ownerUserField . 'Ids';
        } else if ($fieldDefs->getType() === FieldType::LINK) {
            $ownerUserIdAttribute = $ownerUserField . 'Id';
        } else {
            return [];
        }

        if (!$entity->has($ownerUserIdAttribute)) {
            return [];
        }

        if ($fieldDefs->getType() === FieldType::LINK_MULTIPLE) {
            return $entity->getLinkMultipleIdList($ownerUserField);
        }

        $userId = $entity->get($ownerUserIdAttribute);

        if ($userId) {
            return [$userId];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function getCollaboratorIds(CoreEntity $entity): array
    {
        if (!$this->metadata->get("scopes.{$entity->getEntityType()}.collaborators")) {
            return [];
        }

        $field = Field::COLLABORATORS;

        if (!$entity->hasLinkMultipleField($field)) {
            return [];
        }

        return $entity->getLinkMultipleIdList($field);
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

        $note = $this->getNewNote();

        $note->setType(Note::TYPE_EMAIL_RECEIVED);
        $note->setParent($entity);
        $note->setRelated($email);

        $this->processNoteTeamsUsers($note, $email);

        if ($email->getAccount()) {
            $note->setSuperParent($email->getAccount());
        }

        $data = [
            'emailId' => $email->getId(),
            'emailName' => $email->getSubject(),
            'isInitial' => $isInitial,
            'hasAttachment' => count($email->getAttachmentIdList()) > 0,
        ];

        $withContent = $this->toStoreEmailContent($entityType);

        if ($withContent) {
            $note->setPost($email->getBodyPlainWithoutReplyPart());
            $data['attachmentsIds'] = $email->getAttachmentIdList();
        }

        $from = $email->getFromAddress();

        if ($from) {
            $person = $this->getEmailAddressRepository()->getEntityByAddress($from);

            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get(Field::NAME);
                $data['personEntityId'] = $person->getId();

                if (
                    !$isInitial &&
                    $person instanceof User &&
                    ($person->isRegular() || $person->isAdmin())
                ) {
                    $note->setType(Note::TYPE_EMAIL_SENT);
                }
            }
        }

        $note->setData($data);

        $this->entityManager->saveEntity($note);

        // @todo Also for the super-parent.
        $this->updateStreamUpdatedAt($entity);
    }

    public function noteEmailSent(Entity $entity, Email $email): void
    {
        $entityType = $entity->getEntityType();

        $note = $this->getNewNote();

        $note->setType(Note::TYPE_EMAIL_SENT);
        $note->setParent($entity);
        $note->setRelated($email);

        $this->processNoteTeamsUsers($note, $email);

        if ($email->getAccount()) {
            $note->setSuperParent($email->getAccount());
        }

        $data = [
            'emailId' => $email->getId(),
            'emailName' => $email->getSubject(),
            'hasAttachment' => count($email->getAttachmentIdList()) > 0,
        ];

        $withContent = $this->toStoreEmailContent($entityType);

        if ($withContent) {
            $note->setPost($email->getBodyPlainWithoutReplyPart());
            $data['attachmentsIds'] = $email->getAttachmentIdList();
        }

        $user = $this->user;

        $person = null;

        if (!$user->isSystem()) {
            $person = $user;
        } else {
            $from = $email->getFromAddress();

            if ($from) {
                $person = $this->getEmailAddressRepository()->getEntityByAddress($from);
            }
        }

        if ($person) {
            $data['personEntityType'] = $person->getEntityType();
            $data['personEntityName'] = $person->get(Field::NAME);
            $data['personEntityId'] = $person->getId();
        }

        $note->setData($data);

        $this->entityManager->saveEntity($note);

        // @todo Also for the super-parent.
        $this->updateStreamUpdatedAt($entity);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteCreate(Entity $entity, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $note = $this->getNewNote();

        $note->setType(Note::TYPE_CREATE);
        $note->setParent(LinkParent::createFromEntity($entity));

        $this->setSuperParent($entity, $note, true);

        $data = [];

        if ($entity->get('assignedUserId')) {
            $this->loadAssignedUserName($entity);

            $data['assignedUserId'] = $entity->get('assignedUserId');
            $data['assignedUserName'] = $entity->get('assignedUserName');
        } else if (
            $entity instanceof CoreEntity &&
            $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS) &&
            $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS) !== [] &&
            // Exclude for Email as the assignedUsers serves not for direct assignment.
            $entity->getEntityType() !== Email::ENTITY_TYPE
        ) {
            /** @var LinkMultiple $users */
            $users = $entity->getValueObject(self::FIELD_ASSIGNED_USERS);

            $data['assignedUsers'] = array_map(function ($it) {
                return [
                    Attribute::ID => $it->getId(),
                    'name' => $it->getName(),
                ];
            }, $users->getList());
        }

        $field = $this->getStatusField($entityType);

        if ($field) {
            $value = $entity->get($field);

            if ($value) {
                $data['statusValue'] = $value;
                $data['statusField'] = $field;
                $data['statusStyle'] = $this->getStatusStyle($entityType, $field, $value);
            }
        }

        $note->set('data', (object) $data);

        $noteOptions = [];

        if (!empty($options[SaveOption::CREATED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::CREATED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);

        $superParent = $note->getSuperParent();

        if ($superParent && $superParent->getEntityType() !== $entity->getEntityType()) {
            $this->updateStreamUpdatedAt($superParent);
        }
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

        if (isset($statusStyles[$entityType][$value])) {
            return $statusStyles[$entityType][$value];
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

        $note = $this->getNewNote();

        $note->setType(Note::TYPE_CREATE_RELATED);
        $note->setParent(LinkParent::create($parentType, $parentId));
        $note->setRelated($entity);

        $this->processNoteTeamsUsers($note, $entity);

        $this->setSuperParent($entity, $note, false);

        $noteOptions = [];

        if (!empty($options[SaveOption::CREATED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::CREATED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if ($parent) {
            $this->updateStreamUpdatedAt($parent);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteRelate(Entity $entity, Entity $parent, array $options = []): void
    {
        $note = $this->getNewNote();

        $note->setType(Note::TYPE_RELATE);
        $note->setParent($parent);
        $note->setRelated($entity);

        $this->processNoteTeamsUsers($note, $entity);

        $noteOptions = [];

        if (!empty($options[SaveOption::CREATED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::CREATED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);

        if (!$this->checkIsEnabled($parent->getEntityType())) {
            return;
        }

        $this->updateStreamUpdatedAt($parent);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteUnrelate(Entity $entity, Entity $parent, array $options = []): void
    {
        $note = $this->getNewNote();

        $note->setType(Note::TYPE_UNRELATE);
        $note->setParent($parent);
        $note->setRelated($entity);

        $this->processNoteTeamsUsers($note, $entity);

        $noteOptions = [];

        if (!empty($options[SaveOption::MODIFIED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::MODIFIED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);

        if (!$this->checkIsEnabled($parent->getEntityType())) {
            return;
        }

        $this->updateStreamUpdatedAt($parent);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteAssign(Entity $entity, array $options = []): void
    {
        $note = $this->getNewNote();

        $note->setType(Note::TYPE_ASSIGN);
        $note->setParent(LinkParent::createFromEntity($entity));

        $this->setSuperParent($entity, $note, true);
        $this->setAssignData($entity, $note);

        $noteOptions = [];

        if (!empty($options[SaveOption::CREATED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::CREATED_BY_ID];
        }

        if (!empty($options[SaveOption::MODIFIED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::MODIFIED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function noteStatus(Entity $entity, string $field, array $options = []): void
    {
        $note = $this->getNewNote();

        $note->setType(Note::TYPE_STATUS);
        $note->setParent(LinkParent::createFromEntity($entity));

        $this->setSuperParent($entity, $note, true);

        $entityType = $entity->getEntityType();

        $value = $entity->get($field);

        $style = $this->getStatusStyle($entityType, $field, $value);

        $note->set('data', [
            'field' => $field,
            'value' => $value,
            'style' => $style,
        ]);

        $noteOptions = [];

        if (!empty($options[SaveOption::CREATED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::CREATED_BY_ID];
        }

        if (!empty($options[SaveOption::MODIFIED_BY_ID])) {
            $noteOptions[SaveOption::CREATED_BY_ID] = $options[SaveOption::MODIFIED_BY_ID];
        }

        $this->entityManager->saveEntity($note, $noteOptions);
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

        if (array_key_exists($entityType, $this->auditedFieldsCache)) {
            return $this->auditedFieldsCache[$entityType];
        }

        /** @var array<string, array<string, mixed>> $fields */
        $fields = $this->metadata->get(['entityDefs', $entityType, 'fields']);

        $hasStream = (bool) $this->metadata->get("scopes.$entityType.stream");

        $auditedFields = [];

        foreach ($fields as $field => $defs) {
            if (empty($defs['audited'])) {
                continue;
            }

            if ($hasStream && $this->getStatusField($entityType) === $field) {
                continue;
            }

            /** @var ?string $type */
            $type = $defs['type'] ?? null;

            if (!$type) {
                continue;
            }

            $auditedFields[$field] = [
                'actualList' => $this->fieldUtil->getActualAttributeList($entityType, $field),
                'notActualList' => $this->fieldUtil->getNotActualAttributeList($entityType, $field),
                'fieldType' => $type,
            ];
        }

        $this->auditedFieldsCache[$entityType] = $auditedFields;

        return $this->auditedFieldsCache[$entityType];
    }

    /**
     * @since 9.0.0
     * @internal
     */
    public function checkEntityNeedsUpdatedAt(Entity $entity): bool
    {
        if ($entity->isNew() && $entity->get(Field::STREAM_UPDATED_AT)) {
            return false;
        }

        return
            $entity->isNew() ||
            $this->hasAuditedFieldChanged($entity) ||
            $this->hasStatusFieldChanged($entity) ||
            $this->hasAssignedUserChanged($entity) &&
            $this->checkIsEnabled($entity->getEntityType());
    }

    private function hasAssignedUserChanged(Entity $entity): bool
    {
        if (
            $entity->hasAttribute(Field::ASSIGNED_USER . 'Id') &&
            $entity->isAttributeChanged(Field::ASSIGNED_USER . 'Id')
        ) {
            return true;
        }

        if (
            $entity instanceof CoreEntity &&
            $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS) &&
            $entity->isAttributeChanged(self::FIELD_ASSIGNED_USERS . 'Ids')
        ) {
            return true;
        }

        return false;
    }

    private function hasStatusFieldChanged(Entity $entity): bool
    {
        $field = $this->getStatusField($entity->getEntityType());

        if (!$field) {
            return false;
        }

        return $entity->isAttributeChanged($field);
    }

    private function hasAuditedFieldChanged(Entity $entity): bool
    {
        $auditedFields = $this->getAuditedFieldsData($entity);

        foreach ($auditedFields as $item) {
            foreach ($item['actualList'] as $attribute) {
                if ($entity->isAttributeChanged($attribute)) {
                    return true;
                }
            }
        }

        return false;
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

            foreach ($item['actualList'] as $attribute) {
                $was[$attribute] = $entity->getFetched($attribute);
                $became[$attribute] = $entity->get($attribute);
            }

            foreach ($item['notActualList'] as $attribute) {
                $was[$attribute] = $entity->getFetched($attribute);
                $became[$attribute] = $entity->get($attribute);
            }

            if ($item['fieldType'] === FieldType::LINK_PARENT) {
                $wasParentType = $was[$field . 'Type'];
                $wasParentId = $was[$field . 'Id'];

                if (
                    $wasParentType &&
                    $wasParentId &&
                    $this->entityManager->hasRepository($wasParentType)
                ) {
                    $wasParent = $this->entityManager->getEntityById($wasParentType, $wasParentId);

                    if ($wasParent) {
                        $was[$field . 'Name'] = $wasParent->get(Field::NAME);
                    }
                }
            }
        }

        if (count($updatedFieldList) === 0) {
            return;
        }

        $note = $this->getNewNote();

        $note->setType(Note::TYPE_UPDATE);
        $note->setParent(LinkParent::createFromEntity($entity));

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
     * @internal Must be left for backward compatibility.
     */
    public function getEntityFollowerIdList(Entity $entity): array
    {
        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select([Attribute::ID])
            ->join(
                StreamSubscription::ENTITY_TYPE,
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
     * @throws Forbidden
     * @throws BadRequest
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
                Order::createByPositionInList(Expr::column(Attribute::ID), [$this->user->getId()])
            );
            $builder->order('name');
        }

        $builder->join(
            StreamSubscription::ENTITY_TYPE,
            'subscription',
            [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->getId(),
                'subscription.entityType' => $entity->getEntityType(),
            ]
        );

        $query = $builder->build();

        /** @var Collection<User> $collection */
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
            ->select([Attribute::ID, 'name'])
            ->join(
                StreamSubscription::ENTITY_TYPE,
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
                Order::createByPositionInList(Expr::column(Attribute::ID), [$this->user->getId()])
            )
            ->order('name')
            ->find();

        $data = [
            'idList' => [],
            'nameMap' => (object) [],
        ];

        foreach ($userList as $user) {
            $id = $user->getId();

            $data['idList'][] = $id;
            $data['nameMap']->$id = $user->get(Field::NAME);
        }

        return $data;
    }

    private function getUserAclManager(User $user): ?AclManager
    {
        try {
            return $this->userAclManagerProvider->get($user);
        } catch (NotAvailable) {
            return null;
        }
    }

    /**
     * @return Collection<User>
     */
    public function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false): Collection
    {
        if (!$this->checkIsEnabled($parentType)) {
            /** @var Collection<User> */
            return $this->entityManager->getCollectionFactory()->create(User::ENTITY_TYPE);
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(StreamSubscription::ENTITY_TYPE)
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
            ->select([Attribute::ID, 'type'])
            ->find();
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }

    private function setSuperParent(Entity $entity, Note $note, bool $processTeamsUsers): void
    {
        $accountId = $entity->get('accountId');

        if (!$accountId) {
            return;
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        $foreignEntityType = $entityDefs->tryGetRelation('account')?->tryGetForeignEntityType();

        if ($foreignEntityType !== Account::ENTITY_TYPE) {
            return;
        }

        $note->setSuperParent(LinkParent::create(Account::ENTITY_TYPE, $accountId));

        if ($processTeamsUsers) {
            // only if it has super parent
            $this->processNoteTeamsUsers($note, $entity);
        }
    }

    private function getNewNote(): Note
    {
        /** @var Note */
        return $this->entityManager->getNewEntity(Note::ENTITY_TYPE);
    }

    private function setAssignData(Entity $entity, Note $note): void
    {
        if (
            $entity instanceof CoreEntity &&
            $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS) &&
            // Exclude for Email as the assignedUsers serves not for direct assignment.
            $entity->getEntityType() !== Email::ENTITY_TYPE
        ) {
            $data = [];

            $newIds = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);
            /** @var array<string, ?string> $newNames */
            $newNames = get_object_vars($entity->get(self::FIELD_ASSIGNED_USERS . 'Names') ?? (object) []);

            /** @var string[] $prevIds */
            $prevIds = $entity->getFetched(self::FIELD_ASSIGNED_USERS . 'Ids') ?? [];
            /** @var array<string, ?string> $prevNames */
            $prevNames = get_object_vars($entity->getFetched(self::FIELD_ASSIGNED_USERS . 'Names') ?? (object) []);

            $addedIds = array_values(array_diff($newIds, $prevIds));
            $removedIds = array_values(array_diff($prevIds, $newIds));
            $names = array_merge($prevNames, $newNames);

            $data['addedAssignedUsers'] = array_map(function ($id) use ($names) {
                return [
                    'id' => $id,
                    'name' => $names[$id] ?? null,
                ];
            }, $addedIds);

            $data['removedAssignedUsers'] = array_map(function ($id) use ($names) {
                return [
                    'id' => $id,
                    'name' => $names[$id] ?? null,
                ];
            }, $removedIds);

            $note->setData($data);

            return;
        }

        if ($entity->get('assignedUserId')) {
            $this->loadAssignedUserName($entity);

            $note->setData([
                'assignedUserId' => $entity->get('assignedUserId'),
                'assignedUserName' => $entity->get('assignedUserName'),
            ]);

            return;
        }

        $note->setData(['assignedUserId' => null]);
    }

    /**
     * Whether the Stream is enabled for an entity type.
     */
    public function checkIsEnabled(string $entityType): bool
    {
        return (bool) $this->metadata->get("scopes.$entityType.stream");
    }

    /**
     * @since 9.0.0
     */
    public function updateStreamUpdatedAt(Entity $entity): void
    {
        if (!$entity->hasAttribute(Field::STREAM_UPDATED_AT)) {
            return;
        }

        $now = DateTime::createNow()->toString();

        $updateQuery = UpdateBuilder::create()
            ->in($entity->getEntityType())
            ->where([Attribute::ID => $entity->getId()])
            ->set([Field::STREAM_UPDATED_AT => $now])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);

        $entity->set(Field::STREAM_UPDATED_AT, $now);
        $entity->setFetched(Field::STREAM_UPDATED_AT, $now);
    }

    private function toStoreEmailContent(string $entityType): bool
    {
        return in_array($entityType, $this->config->get('streamEmailWithContentEntityTypeList', []));
    }
}
