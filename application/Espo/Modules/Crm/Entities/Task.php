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

namespace Espo\Modules\Crm\Entities;

use Espo\Core\Field\DateTimeOptional;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Entities\Attachment;
use Espo\Entities\User;
use Espo\ORM\Entity as OrmEntity;
use Espo\ORM\EntityCollection;

class Task extends Entity
{
    public const ENTITY_TYPE = 'Task';

    public const STATUS_NOT_STARTED = 'Not Started';
    public const STATUS_STARTED = 'Started';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELED = 'Canceled';
    public const STATUS_DEFERRED = 'Deferred';

    public function setName(?string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    public function setDescription(?string $description): self
    {
        return $this->set('description', $description);
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function setStatus(string $status): self
    {
        return $this->set('status', $status);
    }

    public function getDateStart(): ?DateTimeOptional
    {
        /** @var ?DateTimeOptional */
        return $this->getValueObject('dateStart');
    }

    public function setDateStart(?DateTimeOptional $dateStart): void
    {
        $this->setValueObject('dateStart', $dateStart);
    }

    public function getDateEnd(): ?DateTimeOptional
    {
        /** @var ?DateTimeOptional */
        return $this->getValueObject('dateEnd');
    }

    public function setDateEnd(?DateTimeOptional $dateEnd): void
    {
        $this->setValueObject('dateEnd', $dateEnd);
    }

    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::ASSIGNED_USER);
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Field::TEAMS);
    }

    /**
     * @return string[]
     */
    public function getAttachmentIdList(): array
    {
        /** @var string[] */
        return $this->getLinkMultipleIdList('attachments');
    }

    public function setParent(Entity|LinkParent|null $parent): self
    {
        return $this->setRelatedLinkOrEntity(Field::PARENT, $parent);
    }

    /**
     * @return EntityCollection<Attachment>
     */
    public function getAttachments(): EntityCollection
    {
        /** @var EntityCollection<Attachment> */
        return $this->relations->getMany('attachments');
    }

    public function setAssignedUser(Link|User|null $assignedUser): self
    {
        return $this->setRelatedLinkOrEntity(Field::ASSIGNED_USER, $assignedUser);
    }

    public function setTeams(LinkMultiple $teams): self
    {
        $this->setValueObject(Field::TEAMS, $teams);

        return $this;
    }

    public function setAccount(Link|Account|null $account): self
    {
        return $this->setRelatedLinkOrEntity('account', $account);
    }

    public function getAccount(): ?Account
    {
        /** @var ?Account */
        return $this->relations->getOne('account');
    }

    public function getParent(): ?OrmEntity
    {
        return $this->relations->getOne(Field::PARENT);
    }
}
