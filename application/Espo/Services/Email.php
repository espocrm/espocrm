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

namespace Espo\Services;

use Espo\Tools\Email\SendService;
use Espo\ORM\Entity;
use Espo\Entities\Email as EmailEntity;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Record\CreateParams;
use stdClass;

/**
 * @extends Record<EmailEntity>
 */
class Email extends Record
{
    protected bool $getEntityBeforeUpdate = true;

    private ?SendService $sendService = null;

    private function getSendService(): SendService
    {
        if (!$this->sendService) {
            $this->sendService = $this->injectableFactory->create(SendService::class);
        }

        return $this->sendService;
    }

    /**
     * @todo Move to hook? Make sure needed data is loaded before sending.
     *
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     * @throws Conflict
     * @throws BadRequest
     * @throws SendingError
     */
    public function create(stdClass $data, CreateParams $params): Entity
    {
        /** @var EmailEntity $entity */
        $entity = parent::create($data, $params);

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING) {
            $this->getSendService()->send($entity, $this->user);
        }

        return $entity;
    }
}
