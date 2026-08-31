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

namespace Espo\Hooks\OAuthClientSecret;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Utils\PasswordHash;
use Espo\Core\Utils\Util;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use Espo\ORM\Entity;
use Espo\ORM\Exceptions\PersistenceException;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\OAuthServer\ClientType;

/**
 * @implements BeforeSave<ClientSecret>
 */
class SetFields implements BeforeSave
{

    public function __construct(
        private PasswordHash $passwordHash,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if ($entity->isNew()) {
            $this->setForNew($entity);
        }
    }

    private function prepareName(string $secret): string
    {
        return '...' . substr($secret, - 6);
    }

    private function setForNew(ClientSecret $entity): void
    {
        if ($entity->getClient()->getClientType() !== ClientType::Confidential) {
            throw new PersistenceException("Cannot create secret for Confidential client.");
        }

        $secret = Util::generateSecretKey();
        $hash = $this->passwordHash->hash($secret);

        $entity->setName($this->prepareName($secret));
        $entity->setHash($hash);
        $entity->setValue($secret);
    }
}
