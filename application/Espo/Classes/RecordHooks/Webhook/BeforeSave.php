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

namespace Espo\Classes\RecordHooks\Webhook;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Entities\Webhook;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Webhook>
 */
class BeforeSave implements SaveHook
{
    private const WEBHOOK_MAX_COUNT_PER_USER = 50;

    /** @var string[] */
    private $eventTypeList = [
        'create',
        'update',
        'delete',
        'fieldUpdate',
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
        private Config $config
    ) {}

    public function process(Entity $entity): void
    {
        if ($entity->skipOwn() && !$entity->getUserId()) {
            $entity->setSkipOwn(false);
        }

        $this->checkEntityUserIsApi($entity);
        $this->processEntityEventData($entity);

        if ($entity->isNew() && !$this->user->isAdmin()) {
            $this->checkMaxCount();
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkEntityUserIsApi(Webhook $entity): void
    {
        $userId = $entity->getUserId();

        if (!$userId) {
            return;
        }

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if ($user && $user->isApi()) {
            return;
        }

        throw new Forbidden("User must be an API User.");
    }

    /**
     * @throws Forbidden
     */
    private function processEntityEventData(Webhook $entity): void
    {
        $event = $entity->get('event');

        if (!$event) {
            throw new Forbidden("Event is empty.");
        }

        if (!$entity->isNew() && $entity->isAttributeChanged('event')) {
            throw new Forbidden("Event can't be changed.");
        }

        $arr = explode('.', $event);

        if (count($arr) !== 2 && count($arr) !== 3) {
            throw new Forbidden("Not supported event.");
        }

        $entityType = $arr[0];
        $type = $arr[1];

        $entity->set('entityType', $entityType);
        $entity->set('type', $type);

        $field = null;

        if (!$entityType) {
            throw new Forbidden("Entity Type is empty.");
        }

        if (!$this->metadata->get(['scopes', $entityType, 'object'])) {
            throw new Forbidden("Entity type is not available for Webhooks.");
        }

        if (!$this->entityManager->hasRepository($entityType)) {
            throw new Forbidden("Not existing Entity Type.");
        }

        if (!$this->acl->checkScope($entityType, Acl\Table::ACTION_READ)) {
            throw new Forbidden("Entity type is forbidden.");
        }

        if (!in_array($type, $this->eventTypeList)) {
            throw new Forbidden("Not supported event.");
        }

        if ($type === 'fieldUpdate') {
            if (count($arr) == 3) {
                $field = $arr[2];
            }

            $entity->set('field', $field);

            if (!$field) {
                throw new Forbidden("Field is empty.");
            }

            if (!$this->acl->checkField($entityType, $field)) {
                throw new Forbidden("Field is forbidden.");
            }

            if (!$this->metadata->get(['entityDefs', $entityType, 'fields', $field])) {
                throw new Forbidden("Field does not exist.");
            }

            return;
        }

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $entity->set('field', null);
    }

    /**
     * @throws Forbidden
     */
    private function checkMaxCount(): void
    {
        $maxCount = $this->config->get('webhookMaxCountPerUser', self::WEBHOOK_MAX_COUNT_PER_USER);

        $count = $this->entityManager
            ->getRDBRepositoryByClass(Webhook::class)
            ->where(['userId' => $this->user->getId()])
            ->count();

        if ($maxCount && $count >= $maxCount) {
            throw new Forbidden("Webhook number per user exceeded the limit.");
        }
    }
}
