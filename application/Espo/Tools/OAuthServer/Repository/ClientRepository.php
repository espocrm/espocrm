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

namespace Espo\Tools\OAuthServer\Repository;

use Espo\Core\Name\Field;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\RefreshToken;

class ClientRepository
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function getActiveByIdentifier(string $identifier): ?Client
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(Client::class)
            ->where([
                Client::FIELD_IDENTIFIER => $identifier,
                Client::FIELD_STATUS => Client::STATUS_ACTIVE,
            ])
            ->findOne();
    }

    public function getByIdentifier(string $identifier): ?Client
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(Client::class)
            ->where([
                Client::FIELD_IDENTIFIER => $identifier,
            ])
            ->findOne();
    }

    /**
     * @return EntityCollection<Client>
     */
    public function findConnectedForUser(string $userId, int $limit): EntityCollection
    {
        return $this->entityManager->getRDBRepositoryByClass(Client::class)
            ->where(
                Cond::or(
                    Cond::in(
                        Expr::column(Attribute::ID),
                        SelectBuilder::create()
                            ->from(RefreshToken::ENTITY_TYPE)
                            ->select([RefreshToken::FIELD_CLIENT . 'Id'])
                            ->where([
                                RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE,
                                RefreshToken::FIELD_USER . 'Id' => $userId,
                            ])
                            ->build()
                    ),
                    Cond::in(
                        Expr::column(Attribute::ID),
                        SelectBuilder::create()
                            ->from(AccessToken::ENTITY_TYPE)
                            ->select([RefreshToken::FIELD_CLIENT . 'Id'])
                            ->where([
                                AccessToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE,
                                AccessToken::FIELD_USER . 'Id' => $userId,
                            ])
                            ->build()
                    ),
                )
            )
            ->where([
                Client::FIELD_IDENTIFIER . '!=' => null,
            ])
            ->order(Field::NAME)
            ->limit(0, $limit)
            ->find();
    }
}
