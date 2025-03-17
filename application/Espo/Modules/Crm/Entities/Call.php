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

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Entities\User;
use Espo\ORM\Entity as OrmEntity;

class Call extends Entity
{
    public const ENTITY_TYPE = 'Call';

    public const STATUS_PLANNED = 'Planned';
    public const STATUS_HELD = 'Held';
    public const STATUS_NOT_HELD = 'Not Held';

    public function setName(?string $name): self
    {
        return $this->set(Field::NAME, $name);
    }

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
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

    public function getDateStart(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('dateStart');
    }

    public function setDateStart(?DateTime $dateStart): self
    {
        $this->setValueObject('dateStart', $dateStart);

        return $this;
    }

    public function getDateEnd(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('dateEnd');
    }

    public function setDateEnd(?DateTime $dateEnd): self
    {
        $this->setValueObject('dateEnd', $dateEnd);

        return $this;
    }

    public function setAssignedUserId(?string $assignedUserId): self
    {
        $this->set('assignedUserId', $assignedUserId);

        return $this;
    }

    public function getCreatedBy(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::CREATED_BY);
    }

    public function getModifiedBy(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::MODIFIED_BY);
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

    public function getUsers(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Meeting::LINK_USERS);
    }

    public function getContacts(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Meeting::LINK_CONTACTS);
    }

    public function getLeads(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Meeting::LINK_LEADS);
    }

    public function setUsers(LinkMultiple $users): self
    {
        return $this->setValueObject(Meeting::LINK_USERS, $users);
    }

    public function setContacts(LinkMultiple $contacts): self
    {
        return $this->setValueObject(Meeting::LINK_CONTACTS, $contacts);
    }

    public function setLeads(LinkMultiple $leads): self
    {
        return $this->setValueObject(Meeting::LINK_LEADS, $leads);
    }

    public function setParent(Entity|LinkParent|null $parent): self
    {
        if ($parent instanceof LinkParent) {
            $this->setValueObject(Field::PARENT, $parent);

            return $this;
        }

        $this->relations->set(Field::PARENT, $parent);

        return $this;
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

    public function getUid(): ?string
    {
        return $this->get('uid');
    }

    public function setUid(?string $uid): self
    {
        return $this->set('uid', $uid);
    }
}
