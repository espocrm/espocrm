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

namespace Espo\Hooks\Common;

use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Webhook\Options;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Metadata;
use Espo\Core\Webhook\Manager as WebhookManager;
use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Entity>
 * @implements AfterRemove<Entity>
 */
class Webhook implements AfterSave, AfterRemove
{
    public static int $order = 101;

    public function __construct(
        private Metadata $metadata,
        private WebhookManager $webhookManager,
        private User $user,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (
            $this->toSkip($options, $entity) ||
            !$entity instanceof CoreEntity
        ) {
            return;
        }

        $userIdParam = $entity->isNew() ? SaveOption::CREATED_BY_ID : SaveOption::MODIFIED_BY_ID;

        $eventOptions = new Options(
            userId: $options->get($userIdParam) ?? $this->user->getId(),
        );

        if ($entity->isNew()) {
            $this->webhookManager->processCreate($entity, $eventOptions);

            return;
        }

        $this->webhookManager->processUpdate($entity, $eventOptions);
    }

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        if (
            $this->toSkip($options, $entity) ||
            !$entity instanceof CoreEntity
        ) {
            return;
        }

        $eventOptions = new Options(
            userId: $options->get(SaveOption::MODIFIED_BY_ID) ?? $this->user->getId(),
        );

        $this->webhookManager->processDelete($entity, $eventOptions);
    }

    private function toSkip(SaveOptions|RemoveOptions $options, Entity $entity): bool
    {
        return $options->get(SaveOption::SILENT) ||
            !$this->metadata->get("scopes.{$entity->getEntityType()}.object");
    }
}
