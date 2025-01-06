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

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;

use Espo\Core\Field\Date;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;

use stdClass;

class EmailAccount extends Entity
{
    public const ENTITY_TYPE = 'EmailAccount';

    public const STATUS_ACTIVE = 'Active';

    public function isAvailableForFetching(): bool
    {
        return $this->isActive() && $this->get('useImap');
    }

    public function isAvailableForSending(): bool
    {
        return $this->isActive() && $this->get('useSmtp');
    }

    public function isActive(): bool
    {
        return $this->get('status') === self::STATUS_ACTIVE;
    }

    public function getEmailAddress(): ?string
    {
        return $this->get('emailAddress');
    }

    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::ASSIGNED_USER);
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return LinkMultiple::create();
    }

    public function keepFetchedEmailsUnread(): bool
    {
        return (bool) $this->get('keepFetchedEmailsUnread');
    }

    public function storeSentEmails(): bool
    {
        return (bool) $this->get('storeSentEmails');
    }

    public function getFetchData(): stdClass
    {
        $data = $this->get('fetchData') ?? (object) [];

        if (!property_exists($data, 'lastUID')) {
            $data->lastUID = (object) [];
        }

        if (!property_exists($data, 'lastDate')) {
            $data->lastDate = (object) [];
        }

        if (!property_exists($data, 'byDate')) {
            $data->byDate = (object) [];
        }

        return $data;
    }

    public function getFetchSince(): ?Date
    {
        /** @var ?Date */
        return $this->getValueObject('fetchSince');
    }

    public function getEmailFolder(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('emailFolder');
    }

    /**
     * @return string[]
     */
    public function getMonitoredFolderList(): array
    {
        return $this->get('monitoredFolders') ?? [];
    }

    public function getHost(): ?string
    {
        return $this->get('host');
    }

    public function getPort(): ?int
    {
        return $this->get('port');
    }

    public function getUsername(): ?string
    {
        return $this->get('username');
    }

    public function getPassword(): ?string
    {
        return $this->get('password');
    }

    public function getSecurity(): ?string
    {
        return $this->get('security');
    }

    /**
     * @return ?class-string<object>
     */
    public function getImapHandlerClassName(): ?string
    {
        /** @var ?class-string<object> */
        return $this->get('imapHandler');
    }

    public function getSentFolder(): ?string
    {
        return $this->get('sentFolder');
    }

    public function getSmtpHost(): ?string
    {
        return $this->get('smtpHost');
    }

    public function getSmtpPort(): ?int
    {
        return $this->get('smtpPort');
    }

    public function getSmtpAuth(): bool
    {
        return $this->get('smtpAuth');
    }

    public function getSmtpSecurity(): ?string
    {
        return $this->get('smtpSecurity');
    }

    public function getSmtpUsername(): ?string
    {
        return $this->get('smtpUsername');
    }

    public function getSmtpPassword(): ?string
    {
        return $this->get('smtpPassword');
    }

    public function getSmtpAuthMechanism(): ?string
    {
        return $this->get('smtpAuthMechanism');
    }

    /**
     * @return ?class-string<object>
     */
    public function getSmtpHandlerClassName(): ?string
    {
        /** @var ?class-string<object> */
        return $this->get('smtpHandler');
    }
}
