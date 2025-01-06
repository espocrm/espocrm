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

namespace Espo\Core\FieldProcessing\Stream;

use Espo\Core\Name\Field;
use Espo\ORM\Entity;
use Espo\Core\Acl;
use Espo\Core\FieldProcessing\Loader as LoaderInterface;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Tools\Stream\Service as StreamService;

/**
 * @implements LoaderInterface<Entity>
 */
class FollowersLoader implements LoaderInterface
{
    private const FOLLOWERS_LIMIT = 6;

    public function __construct(
        private StreamService $streamService,
        private Metadata $metadata,
        private User $user,
        private Acl $acl,
        private Config $config
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $this->processIsFollowed($entity);
        $this->processFollowers($entity);
    }

    public function processIsFollowed(Entity $entity): void
    {
        if (!$entity->hasAttribute(Field::IS_FOLLOWED)) {
            return;
        }

        $isFollowed = $this->streamService->checkIsFollowed($entity);

        $entity->set(Field::IS_FOLLOWED, $isFollowed);
    }

    public function processFollowers(Entity $entity): void
    {
        if ($this->user->isPortal()) {
            return;
        }

        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return;
        }

        if (!$this->acl->checkEntityStream($entity)) {
            return;
        }

        $limit = $this->config->get('recordFollowersLoadLimit') ?? self::FOLLOWERS_LIMIT;

        $data = $this->streamService->getEntityFollowers($entity, 0, $limit);

        $entity->set(Field::FOLLOWERS . 'Ids', $data['idList']);
        $entity->set(Field::FOLLOWERS . 'Names', $data['nameMap']);
    }
}
