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

use Espo\Core\Field\LinkParent;

use stdClass;

class Notification extends \Espo\Core\ORM\Entity
{
    public const ENTITY_TYPE = 'Notification';

    public const TYPE_ENTITY_REMOVED = 'EntityRemoved';

    public const TYPE_ASSIGN = 'Assign';

    public const TYPE_EMAIL_RECEIVED = 'EmailReceived';

    public const TYPE_NOTE = 'Note';

    public const TYPE_MENTION_IN_POST = 'MentionInPost';

    public const TYPE_MESSAGE = 'Message';

    public const TYPE_SYSTEM = 'System';

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

    public function setData(stdClass $data): self
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
        return $this->getValueObject('related');
    }

    public function setRelated(?LinkParent $related): self
    {
        $this->setValueObject('related', $related);

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
}
