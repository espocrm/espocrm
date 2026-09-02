<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\OAuthServer\League\Entities;

use Espo\Tools\OAuthServer\ClientType;
use Espo\Tools\OAuthServer\Entities\Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use UnexpectedValueException;

class ClientEntity implements ClientEntityInterface
{
    public function __construct(
        private Client $entity,
    ) {}

    public function getIdentifier()
    {
        return $this->entity->getIdentifier() ?? throw new UnexpectedValueException("No client identifier.");
    }

    public function getName()
    {
        return $this->entity->getName() ?? $this->entity->getId();
    }

    public function getRedirectUri()
    {
        return $this->entity->getRedirectUris();
    }

    public function isConfidential()
    {
        return $this->entity->getClientType() === ClientType::Confidential;
    }

    public function getEntity(): Client
    {
        return $this->getEntity();
    }
}
