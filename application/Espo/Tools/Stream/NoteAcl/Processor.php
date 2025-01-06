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

namespace Espo\Tools\Stream\NoteAcl;

use DateTimeImmutable;
use Espo\Core\AclManager;
use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Entities\Note;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;

use DateTimeInterface;
use LogicException;

/**
 * @internal
 */
class Processor
{
    /**
     * When a record is re-assigned, ACL will be recalculated for related notes
     * created within the period.
     */
    private const NOTE_ACL_PERIOD = '3 days';
    private const NOTE_ACL_LIMIT = 50;
    private const NOTE_NOTIFICATION_PERIOD = '1 hour';

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private AclManager $aclManager,
    ) {}

    /**
     * @param bool $notify Process notifications for notes.
     */
    public function process(CoreEntity $entity, bool $notify = false): void
    {
        $entityType = $entity->getEntityType();

        $usersAttributeIsChanged = false;
        $teamsAttributeIsChanged = false;

        $ownerUserField = $this->aclManager->getReadOwnerUserField($entityType);

        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $userIdList = [];
        $teamIdList = [];

        if ($ownerUserField) {
            if (!$defs->hasField($ownerUserField)) {
                throw new LogicException("Non-existing read-owner user field.");
            }

            $fieldDefs = $defs->getField($ownerUserField);

            if ($fieldDefs->getType() === FieldType::LINK_MULTIPLE) {
                $ownerUserIdAttribute = $ownerUserField . 'Ids';
            } else if ($fieldDefs->getType() === FieldType::LINK) {
                $ownerUserIdAttribute = $ownerUserField . 'Id';
            } else {
                throw new LogicException("Bad read-owner user field type.");
            }

            if ($entity->isAttributeChanged($ownerUserIdAttribute)) {
                $usersAttributeIsChanged = true;
            }

            if ($usersAttributeIsChanged || $notify) {
                if ($fieldDefs->getType() === FieldType::LINK_MULTIPLE) {
                    $userIdList = $entity->getLinkMultipleIdList($ownerUserField);
                } else {
                    $userId = $entity->get($ownerUserIdAttribute);

                    $userIdList = $userId ? [$userId] : [];
                }
            }
        }

        if ($entity->hasLinkMultipleField(Field::TEAMS)) {
            if ($entity->isAttributeChanged(Field::TEAMS . 'Ids')) {
                $teamsAttributeIsChanged = true;
            }

            if ($teamsAttributeIsChanged || $notify) {
                $teamIdList = $entity->getLinkMultipleIdList(Field::TEAMS);
            }
        }

        if (!$usersAttributeIsChanged && !$teamsAttributeIsChanged && !$notify) {
            return;
        }

        $notificationThreshold = $this->getNotificationThreshold();

        foreach ($this->getNotes($entity) as $note) {
            $this->processNoteAclItem($entity, $note, [
                'teamsAttributeIsChanged' => $teamsAttributeIsChanged,
                'usersAttributeIsChanged' => $usersAttributeIsChanged,
                'notify' => $notify,
                'teamIdList' => $teamIdList,
                'userIdList' => $userIdList,
                'notificationThreshold' => $notificationThreshold,
            ]);
        }
    }

    /**
     * @param array{
     *   teamsAttributeIsChanged: bool,
     *   usersAttributeIsChanged: bool,
     *   notify: bool,
     *   teamIdList: string[],
     *   userIdList: string[],
     *   notificationThreshold: DateTimeInterface,
     * } $params
     */
    private function processNoteAclItem(Entity $entity, Note $note, array $params): void
    {
        $teamsAttributeIsChanged = $params['teamsAttributeIsChanged'];
        $usersAttributeIsChanged = $params['usersAttributeIsChanged'];
        $notify = $params['notify'];

        $teamIdList = $params['teamIdList'];
        $userIdList = $params['userIdList'];

        $notificationThreshold = $params['notificationThreshold'];

        $createdAt = $note->getCreatedAt();

        if (!$createdAt) {
            return;
        }

        if (!$entity->isNew() && $createdAt->toTimestamp() < $notificationThreshold->getTimestamp()) {
            $notify = false;
        }

        if ($teamsAttributeIsChanged || $notify) {
            $note->setTeamsIds($teamIdList);
        }

        if ($usersAttributeIsChanged || $notify) {
            $note->setUsersIds($userIdList);
        }

        $this->entityManager->saveEntity($note, ['forceProcessNotifications' => $notify]);
    }

    /**
     * @return Collection<Note>
     */
    private function getNotes(CoreEntity $entity): Collection
    {
        $entityType = $entity->getEntityType();
        $limit = $this->config->get('noteAclLimit', self::NOTE_ACL_LIMIT);
        $aclThreshold = $this->getAclThreshold();

        return $this->entityManager
            ->getRDBRepository(Note::ENTITY_TYPE)
            ->sth()
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
                    ],
                ]
            ])
            ->where(['createdAt>=' => $aclThreshold->toString()])
            ->select([
                'id',
                'parentType',
                'parentId',
                'superParentType',
                'superParentId',
                'isInternal',
                'relatedType',
                'relatedId',
                Field::CREATED_AT,
            ])
            ->order('number', Order::DESC)
            ->limit(0, $limit)
            ->find();
    }

    private function getNotificationThreshold(): DateTimeInterface
    {
        $notificationPeriod = '-' . $this->config->get('noteNotificationPeriod', self::NOTE_NOTIFICATION_PERIOD);

        return (new DateTimeImmutable())->modify($notificationPeriod);
    }

    private function getAclThreshold(): DateTime
    {
        $aclPeriod = '-' . $this->config->get('noteAclPeriod', self::NOTE_ACL_PERIOD);

        return DateTime::createNow()->modify($aclPeriod);
    }
}
