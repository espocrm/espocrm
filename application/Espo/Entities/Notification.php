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

namespace Espo\Entities;

use Espo\Core\Field\LinkParent;

use Espo\Core\ORM\Entity;
use stdClass;

class Notification extends Entity
{
    public const ENTITY_TYPE = 'Notification';

    public const TYPE_ENTITY_REMOVED = 'EntityRemoved';
    public const TYPE_ASSIGN = 'Assign';
    public const TYPE_EMAIL_RECEIVED = 'EmailReceived';
    public const TYPE_NOTE = 'Note';
    public const TYPE_MENTION_IN_POST = 'MentionInPost';
    public const TYPE_MESSAGE = 'Message';
    public const TYPE_USER_REACTION = 'UserReaction';
    public const TYPE_SYSTEM = 'System';

    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function setMessage(?string $message): self
    {
        $this->set('message', $message);

        return $this;
    }

    public function setType(string $type): self
    {
        $this->set('type', $type);

        return $this;
    }

    public function getData(): ?stdClass
    {
        return $this->get('data');
    }

    public function getUserId(): ?string
    {
        return $this->get('userId');
    }

    /**
     * @param stdClass|array<string, mixed> $data
     */
    public function setData(stdClass|array $data): self
    {
        $this->set('data', $data);

        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->set('userId', $userId);

        return $this;
    }

    public function getRelated(): ?LinkParent
    {
        /** @var ?LinkParent */
        return $this->getValueObject('related');
    }

    public function setRelated(LinkParent|Entity|null $related): self
    {
        if ($related instanceof LinkParent) {
            $this->setValueObject('related', $related);

            return $this;
        }

        $this->relations->set('related', $related);

        return $this;
    }

    public function setRelatedParent(LinkParent|Entity|null $relatedParent): self
    {
        if ($relatedParent instanceof LinkParent) {
            $this->setValueObject('relatedParent', $relatedParent);

            return $this;
        }

        $this->relations->set('relatedParent', $relatedParent);

        return $this;
    }

    public function setRelatedType(?string $relatedType): self
    {
        $this->set('relatedType', $relatedType);

        return $this;
    }

    public function setRelatedId(?string $relatedId): self
    {
        $this->set('relatedId', $relatedId);

        return $this;
    }

    public function isRead(): bool
    {
        return $this->get('read');
    }

    public function setRead(bool $read = true): self
    {
        $this->set('read', $read);

        return $this;
    }
}
