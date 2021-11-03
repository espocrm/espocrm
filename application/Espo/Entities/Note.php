<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

use Espo\Core\Field\DateTime;

use stdClass;

class Note extends Entity
{
    public const ENTITY_TYPE = 'Note';

    public const TARGET_SELF = 'self';

    public const TARGET_ALL = 'all';

    public const TARGET_TEAMS = 'teams';

    public const TARGET_USERS = 'users';

    public const TARGET_PORTALS = 'portals';

    public const TYPE_POST = 'Post';

    public const TYPE_UPDATE = 'Update';

    public const TYPE_STATUS = 'Status';

    public const TYPE_CREATE = 'Create';

    private $aclIsProcessed = false;

    public function isPost(): bool
    {
        return $this->getType() === self::TYPE_POST;
    }

    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function getTargetType(): ?string
    {
        return $this->get('targetType');
    }

    public function getParentType(): ?string
    {
        return $this->get('parentType');
    }

    public function getParentId(): ?string
    {
        return $this->get('parentId');
    }

    public function getSuperParentType(): ?string
    {
        return $this->get('superParentType');
    }

    public function getSuperParentId(): ?string
    {
        return $this->get('superParentId');
    }

    public function getRelatedType(): ?string
    {
        return $this->get('relatedType');
    }

    public function getRelatedId(): ?string
    {
        return $this->get('relatedId');
    }

    public function getData(): stdClass
    {
        return $this->get('data') ?? (object) [];
    }

    public function isInternal(): bool
    {
        return (bool) $this->get('isInternal');
    }

    public function getPost(): ?string
    {
        return $this->get('post');
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->getValueObject('createdAt');
    }

    public function setAclIsProcessed(): void
    {
        $this->aclIsProcessed = true;
    }

    public function isAclProcessed(): bool
    {
        return (bool) $this->aclIsProcessed;
    }

    public function loadAttachments(): void
    {
        $data = $this->get('data');

        if (empty($data) || empty($data->attachmentsIds) || !is_array($data->attachmentsIds)) {
            $this->loadLinkMultipleField('attachments');

            return;
        }

        $attachmentsIds = $data->attachmentsIds;

        $collection = $this->entityManager
            ->getRDBRepository('Attachment')
            ->select(['id', 'name', 'type'])
            ->order('createdAt')
            ->where([
                'id' => $attachmentsIds
            ])
            ->find();

        $ids = [];

        $names = (object) [];
        $types = (object) [];

        foreach ($collection as $e) {
            $id = $e->getId();

            $ids[] = $id;

            $names->$id = $e->get('name');
            $types->$id = $e->get('type');
        }

        $this->set('attachmentsIds', $ids);
        $this->set('attachmentsNames', $names);
        $this->set('attachmentsTypes', $types);
    }

    public function addNotifiedUserId($userId): void
    {
        $userIdList = $this->get('notifiedUserIdList');

        if (!is_array($userIdList)) {
            $userIdList = [];
        }

        if (!in_array($userId, $userIdList)) {
            $userIdList[] = $userId;
        }

        $this->set('notifiedUserIdList', $userIdList);
    }

    public function isUserIdNotified($userId): bool
    {
        $userIdList = $this->get('notifiedUserIdList') ?? [];

        return in_array($userId, $userIdList);
    }
}
