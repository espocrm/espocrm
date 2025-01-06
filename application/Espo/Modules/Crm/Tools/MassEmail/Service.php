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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Entities\InboundEmail;
use Espo\Modules\Crm\Entities\MassEmail as MassEmailEntity;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use stdClass;

class Service
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private QueueCreator $queueCreator,
        private SendingProcessor $sendingProcessor
    ) {}

    /**
     * SMTP data for the front-end.
     *
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getSmtpAccountDataList(): array
    {
        if (
            !$this->acl->checkScope(MassEmailEntity::ENTITY_TYPE, Table::ACTION_CREATE) &&
            !$this->acl->checkScope(MassEmailEntity::ENTITY_TYPE, Table::ACTION_EDIT)
        ) {
            throw new Forbidden();
        }

        $dataList = [];

        /** @var Collection<InboundEmail> $inboundEmailList */
        $inboundEmailList = $this->entityManager
            ->getRDBRepository(InboundEmail::ENTITY_TYPE)
            ->where([
                'useSmtp' => true,
                'status' => InboundEmail::STATUS_ACTIVE,
                'smtpIsForMassEmail' => true,
                ['emailAddress!=' => ''],
                ['emailAddress!=' => null],
            ])
            ->find();

        foreach ($inboundEmailList as $inboundEmail) {
            $item = (object) [];

            $key = 'inboundEmail:' . $inboundEmail->getId();

            $item->key = $key;
            $item->emailAddress = $inboundEmail->getEmailAddress();
            $item->fromName = $inboundEmail->getFromName();

            $dataList[] = $item;
        }

        return $dataList;
    }

    /**
     * Send test.
     *
     * @param stdClass[] $targetDataList
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     * @throws NotFound
     * @throws NoSmtp
     */
    public function processTest(string $id, array $targetDataList): void
    {
        $targetList = [];

        if (count($targetDataList) === 0) {
            throw new BadRequest("Empty target list.");
        }

        foreach ($targetDataList as $item) {
            if (empty($item->id) || empty($item->type)) {
                throw new BadRequest();
            }

            $targetId = $item->id;
            $targetType = $item->type;

            $target = $this->entityManager->getEntityById($targetType, $targetId);

            if (!$target) {
                throw new Error("Target not found.");
            }

            if (!$this->acl->check($target, Table::ACTION_READ)) {
                throw new Forbidden();
            }

            $targetList[] = $target;
        }

        /** @var ?MassEmailEntity $massEmail */
        $massEmail = $this->entityManager->getEntityById(MassEmailEntity::ENTITY_TYPE, $id);

        if (!$massEmail) {
            throw new NotFound();
        }

        if (!$this->acl->check($massEmail, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $this->createTestQueue($massEmail, $targetList);
        $this->processTestSending($massEmail);
    }

    /**
     * @param iterable<Entity> $targetList
     * @throws Error
     */
    private function createTestQueue(MassEmailEntity $massEmail, iterable $targetList): void
    {
        $this->queueCreator->create($massEmail, true, $targetList);
    }

    /**
     * @throws Error
     * @throws NoSmtp
     */
    private function processTestSending(MassEmailEntity $massEmail): void
    {
        $this->sendingProcessor->process($massEmail, true);
    }
}
