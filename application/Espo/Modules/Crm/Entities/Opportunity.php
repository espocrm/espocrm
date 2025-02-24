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

use Espo\Core\Field\Currency;
use Espo\Core\Field\Date;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Entities\User;

class Opportunity extends Entity
{
    public const ENTITY_TYPE = 'Opportunity';

    public const STAGE_CLOSED_WON = 'Closed Won';
    public const STAGE_CLOSED_LOST = 'Closed Lost';

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function setName(?string $name): self
    {
        $this->set(Field::NAME, $name);

        return $this;
    }

    public function setDescription(?string $description): self
    {
        return $this->set('description', $description);
    }

    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    public function getAmount(): ?Currency
    {
        /** @var ?Currency */
        return $this->getValueObject('amount');
    }

    public function setAmount(?Currency $amount): self
    {
        $this->setValueObject('amount', $amount);

        return $this;
    }

    public function getCloseDate(): ?Date
    {
        /** @var ?Date */
        return $this->getValueObject('closeDate');
    }

    public function setCloseDate(?Date $closeDate): self
    {
        $this->setValueObject('closeDate', $closeDate);

        return $this;
    }

    public function getStage(): ?string
    {
        return $this->get('stage');
    }

    public function setStage(?string $stage): void
    {
        $this->set('stage', $stage);
    }

    public function getLastStage(): ?string
    {
        return $this->get('lastStage');
    }

    public function setLastStage(?string $lastStage): void
    {
        $this->set('lastStage', $lastStage);
    }

    public function getProbability(): ?int
    {
        return $this->get('probability');
    }

    public function setProbability(?int $probability): void
    {
        $this->set('probability', $probability);
    }

    public function getAccount(): ?Account
    {
        /** @var ?Account */
        return $this->relations->getOne('account');
    }

    /**
     * A primary contact.
     */
    public function getContact(): ?Contact
    {
        /** @var ?Contact */
        return $this->relations->getOne('contact');
    }

    public function getContacts(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('contacts');
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

    public function setAccount(Account|Link|null $account): self
    {
        return $this->setRelatedLinkOrEntity('account', $account);
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
}
