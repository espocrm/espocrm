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

use Espo\Core\ORM\Entity;
use Espo\Entities\EmailTemplate;
use Espo\ORM\EntityCollection;

class MassEmail extends Entity
{
    public const ENTITY_TYPE = 'MassEmail';

    public const STATUS_COMPLETE = 'Complete';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_IN_PROCESS = 'In Process';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_DRAFT = 'Draft';

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function getEmailTemplateId(): ?string
    {
        return $this->get('emailTemplateId');
    }

    /**
     * @return EntityCollection<TargetList>
     */
    public function getTargetLists(): EntityCollection
    {
        /** @var EntityCollection<TargetList>  */
        return $this->relations->getMany('targetLists');
    }

    /**
     * @return EntityCollection<TargetList>
     */
    public function getExcludingTargetLists(): EntityCollection
    {
        /** @var EntityCollection<TargetList>  */
        return $this->relations->getMany('excludingTargetLists');
    }

    public function getEmailTemplate(): ?EmailTemplate
    {
        /** @var ?EmailTemplate */
        return $this->relations->getOne('emailTemplate');
    }

    public function getCampaign(): ?Campaign
    {
        /** @var ?Campaign */
        return $this->relations->getOne('campaign');
    }

    public function getCampaignId(): ?string
    {
        return $this->get('campaignId');
    }

    public function getInboundEmailId(): ?string
    {
        return $this->get('inboundEmailId');
    }

    public function getFromName(): ?string
    {
        return $this->get('fromName');
    }

    public function getReplyToName(): ?string
    {
        return $this->get('replyToName');
    }

    public function getFromAddress(): ?string
    {
        return $this->get('fromAddress');
    }

    public function getReplyToAddress(): ?string
    {
        return $this->get('replyToAddress');
    }

    public function storeSentEmails(): bool
    {
        return (bool) $this->get('storeSentEmails');
    }

    public function optOutEntirely(): bool
    {
        return (bool) $this->get('optOutEntirely');
    }

    public function setStatus(string $status): self
    {
        $this->set('status', $status);

        return $this;
    }
}
