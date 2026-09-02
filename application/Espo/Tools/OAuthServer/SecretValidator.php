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

namespace Espo\Tools\OAuthServer;

use Espo\Core\Name\Field;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\PasswordHash;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use SensitiveParameter;

class SecretValidator
{
    public function __construct(
        private EntityManager $entityManager,
        private DateTime $dateTime,
        private PasswordHash $passwordHash,
    ) {}

    public function validate(Client $client, #[SensitiveParameter] string $secret): bool
    {
        $secrets = $this->entityManager
            ->getRDBRepositoryByClass(ClientSecret::class)
            ->sth()
            ->where([
                ClientSecret::ATTR_CLIENT_ID => $client->getId(),
                ClientSecret::FIELD_STATUS => ClientSecret::STATUS_ACTIVE,
            ])
            ->order(Field::CREATED_AT, Order::DESC)
            ->find();

        foreach ($secrets as $entry) {
            /** @noinspection PhpParamsInspection */
            if ($this->match($entry, $secret)) {
                return true;
            }
        }

        return false;
    }

    private function match(ClientSecret $entry, #[SensitiveParameter] string $secret): bool
    {
        if ($entry->getExpirationDate()?->isLessThanOrEqualTo($this->dateTime->getToday())) {
            return false;
        }

        return $this->passwordHash->verify($secret, $entry->getValue());
    }
}
