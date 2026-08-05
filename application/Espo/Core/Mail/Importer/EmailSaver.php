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

namespace Espo\Core\Mail\Importer;

use Espo\Entities\Email;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use PDOException;

/**
 * @internal
 */
class EmailSaver
{
    private const int SAVE_RETRY_COUNT = 2;

    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function save(Email $email): void
    {
        for ($i = 0; $i < self::SAVE_RETRY_COUNT; $i ++) {
            try {
                $this->saveInternal($email);
            } catch (PDOException $e) {
                $code = (int) ($e->errorInfo[1] ?? '0');

                // Handles a snapshot isolation conflict.
                if ($code === 1020) {
                    if ($i === self::SAVE_RETRY_COUNT - 1) {
                        throw $e;
                    }

                    continue;
                }

                throw $e;
            }

            break;
        }
    }

    private function saveInternal(Email $email): void
    {
        $this->entityManager->getTransactionManager()->run(function () use ($email) {
            $this->entityManager
                ->getRDBRepositoryByClass(Email::class)
                ->forUpdate()
                ->select(Attribute::ID)
                ->where([Attribute::ID => $email->getId()])
                ->findOne();

            $this->entityManager->saveEntity($email, [Email::SAVE_OPTION_IS_BEING_IMPORTED => true]);
        });
    }
}
