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

namespace Espo\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Account\PersonalAccount\AccountFactory;
use Espo\Core\Mail\Exceptions\NoSmtp;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Record\CreateParams;

use Espo\Entities\EmailAccount as EmailAccountEntity;

use Espo\Core\Di;

use stdClass;

/**
 * @extends Record<\Espo\Entities\EmailAccount>
 */
class EmailAccount extends Record implements

    Di\CryptAware
{
    use Di\CryptSetter;

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (property_exists($data, 'password')) {
            $data->password = $this->crypt->encrypt($data->password);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->crypt->encrypt($data->smtpPassword);
        }
    }

    public function processValidation(Entity $entity, $data)
    {
        parent::processValidation($entity, $data);

        if ($entity->get('useImap')) {
            if (!$entity->get('fetchSince')) {
                throw new BadRequest("EmailAccount validation: fetchSince is required.");
            }
        }
    }

    public function create(stdClass $data, CreateParams $params): Entity
    {
        if (!$this->user->isAdmin()) {
            $count = $this->entityManager
                ->getRDBRepository(EmailAccountEntity::ENTITY_TYPE)
                ->where([
                    'assignedUserId' => $this->user->getId()
                ])
                ->count();

            if ($count >= $this->config->get('maxEmailAccountCount', \PHP_INT_MAX)) {
                throw new Forbidden();
            }
        }

        $entity = parent::create($data, $params);

        if (!$this->user->isAdmin()) {
            $entity->set('assignedUserId', $this->user->getId());
        }

        $this->entityManager->saveEntity($entity);

        return $entity;
    }

    /**
     * @return ?array<string, mixed>
     * @throws Error
     * @throws NoSmtp
     * @internal Left for bc.
     * @deprecated As of v7.3. Use Espo\Core\Mail\Account\PersonalAccount.
     * @todo Remove in v9.0.
     */
    public function getSmtpParamsFromAccount(EmailAccountEntity $emailAccount): ?array
    {
        $params = $this->injectableFactory
            ->create(AccountFactory::class)
            ->create($emailAccount->getId())
            ->getSmtpParams();

        if (!$params) {
            return null;
        }

        return $params->toArray();
    }
}
