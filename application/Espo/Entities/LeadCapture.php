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

namespace Espo\Entities;

class LeadCapture extends \Espo\Core\ORM\Entity
{
    public const ENTITY_TYPE = 'LeadCapture';

    /**
     * @deprecated As of v7.2.
     */
    public function isToSubscribeContactIfExists(): bool
    {
        return $this->get('subscribeToTargetList') && $this->get('subscribeContactToTargetList');
    }

    /**
     * @return string[]
     */
    public function getFieldList(): array
    {
        return $this->get('fieldList') ?? [];
    }

    public function getOptInConfirmationSuccessMessage(): ?string
    {
        return $this->get('optInConfirmationSuccessMessage');
    }

    public function duplicateCheck(): bool
    {
        return (bool) $this->get('duplicateCheck');
    }

    public function skipOptInConfirmationIfSubscribed(): bool
    {
        return (bool) $this->get('skipOptInConfirmationIfSubscribed');
    }

    public function createLeadBeforeOptInConfirmation(): bool
    {
        return (bool) $this->get('createLeadBeforeOptInConfirmation');
    }

    public function optInConfirmation(): bool
    {
        return (bool) $this->get('optInConfirmation');
    }

    public function getOptInConfirmationLifetime(): ?int
    {
        return $this->get('optInConfirmationLifetime');
    }

    public function subscribeToTargetList(): bool
    {
        return (bool) $this->get('subscribeToTargetList');
    }

    public function subscribeContactToTargetList(): bool
    {
        return (bool) $this->get('subscribeContactToTargetList');
    }

    public function getApiKey(): ?string
    {
        return $this->get('apiKey');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getTargetTeamId(): ?string
    {
        return $this->get('targetTeamId');
    }

    public function getTargetListId(): ?string
    {
        return $this->get('targetListId');
    }

    public function getCampaignId(): ?string
    {
        return $this->get('campaignId');
    }

    public function getInboundEmailId(): ?string
    {
        return $this->get('inboundEmailId');
    }

    public function getLeadSource(): ?string
    {
        return $this->get('leadSource');
    }

    public function getOptInConfirmationEmailTemplateId(): ?string
    {
        return $this->get('optInConfirmationEmailTemplateId');
    }

    /**
     * @since 8.1.0
     */
    public function getPhoneNumberCountry(): ?string
    {
        return $this->get('phoneNumberCountry');
    }
}
