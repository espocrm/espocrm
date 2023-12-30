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

namespace Espo\Modules\Crm\Entities;

use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;

class CaseObj extends \Espo\Core\ORM\Entity
{
    public const ENTITY_TYPE = 'Case';

    public const STATUS_NEW = 'New';
    public const STATUS_ASSIGNED = 'Assigned';
    public const STATUS_CLOSED = 'Closed';
    public const STATUS_PENDING = 'Pending';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_DUPLICATE = 'Duplicate';

    protected $entityType = 'Case';

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function getInboundEmailId(): ?string
    {
        return $this->get('inboundEmailId');
    }

    public function getAccount(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('account');
    }

    /**
     * A primary contact.
     */
    public function getContact(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('contact');
    }

    public function getContacts(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('contacts');
    }

    public function getLead(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('lead');
    }

    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('assignedUser');
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('teams');
    }

    /**
     * @return string[]
     */
    public function getAttachmentIdList(): array
    {
        /** @var string[] */
        return $this->getLinkMultipleIdList('attachments');
    }
}
