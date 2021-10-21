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

namespace Espo\Modules\Crm\Entities;

use Espo\Core\{
    ORM\Entity,
    Field\Currency,
    Field\Date,
};

class Opportunity extends Entity
{
    public const ENTITY_TYPE = 'Opportunity';

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function setName(?string $name): self
    {
        $this->set('name', $name);

        return $this;
    }

    public function getAmount(): ?Currency
    {
        return $this->getValueObject('amount');
    }

    public function setAmount(?Currency $amount): self
    {
        $this->setValueObject('amount', $amount);

        return $this;
    }

    public function getCloseDate(): ?Date
    {
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

    public function getProbability(): ?int
    {
        return $this->get('probability');
    }

    public function setProbability(?int $probability): void
    {
        $this->set('probability', $probability);
    }
}
