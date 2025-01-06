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

namespace Espo\Tools\Stream\NoteAcl;

use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;

/**
 * Changes users and teams of notes related to an entity according users and teams of the entity.
 *
 * Notes having `related` or `superParent` are subjects to access control
 * through `users` and `teams` fields.
 *
 * When users or teams of `related` or `parent` record are changed
 * the note record will be changed too.
 *
 * @internal
 * @todo Job to process the rest, after the last ID.
 */
class AccessModifier
{
    /** @var string[] */
    private array $ignoreEntityTypeList = [
        'Note',
        'User',
        'Team',
        'Role',
        'Portal',
        'PortalRole',
    ];

    public function __construct(
        private Metadata $metadata,
        private Processor $processor
    ) {}

    /**
     * @internal
     */
    public function process(Entity $entity): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        if (!$this->toProcess($entity)) {
            return;
        }

        $this->processor->process($entity);
    }

    private function toProcess(CoreEntity $entity): bool
    {
        $entityType = $entity->getEntityType();

        if (in_array($entityType, $this->ignoreEntityTypeList)) {
            return false;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'acl'])) {
            return false;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'object'])) {
            return false;
        }

        return true;
    }
}
