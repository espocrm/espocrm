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

namespace Espo\Modules\Crm\Tools\Meeting;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Tools\Meeting\Invitation\Sender;
use Espo\Modules\Crm\Tools\Meeting\Invitation\Invitee;
use Espo\ORM\Entity;

class InvitationService
{
    private const TYPE_INVITATION = 'invitation';
    private const TYPE_CANCELLATION = 'cancellation';

    public function __construct(
        private RecordServiceContainer $recordServiceContainer,
        private Acl $acl,
        private Sender $invitationSender,
    ) {}

    /**
     * Send invitation emails for a meeting (or call). Checks access. Uses user's SMTP if available.
     *
     * @param ?Invitee[] $targets
     * @return Entity[] Entities an invitation was sent to.
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    public function send(string $entityType, string $id, ?array $targets = null): array
    {
        return $this->sendInternal($entityType, $id, $targets, self::TYPE_INVITATION);
    }

    /**
     * Send cancellation emails for a meeting (or call). Checks access. Uses user's SMTP if available.
     *
     * @param ?Invitee[] $targets
     * @return Entity[] Entities a cancellation was sent to.
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    public function sendCancellation(string $entityType, string $id, ?array $targets = null): array
    {
        return $this->sendInternal($entityType, $id, $targets, self::TYPE_CANCELLATION);
    }

    /**
     * @param ?Invitee[] $targets
     * @return Entity[]
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    private function sendInternal(
        string $entityType,
        string $id,
        ?array $targets,
        string $type,
    ): array {

        $entity = $this->recordServiceContainer
            ->get($entityType)
            ->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden("No edit access.");
        }

        if (!$entity instanceof Meeting && !$entity instanceof Call) {
            throw new Error("Not supported entity type.");
        }

        if ($type === self::TYPE_CANCELLATION) {
            return $this->invitationSender->sendCancellation($entity, $targets);
        }

        return $this->invitationSender->sendInvitation($entity, $targets);
    }
}
