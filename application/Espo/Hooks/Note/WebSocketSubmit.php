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

namespace Espo\Hooks\Note;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Entities\Note;
use Espo\ORM\Entity;
use Espo\Core\WebSocket\Submission as WebSocketSubmission;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Note>
 */
class WebSocketSubmit implements AfterSave
{
    public static int $order = 20;

    public function __construct(
        private WebSocketSubmission $webSocketSubmission,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew()) {
            $updateTopic = "recordUpdate.Note.{$entity->getId()}";

            $this->webSocketSubmission->submit($updateTopic);
        }

        $parentId = $entity->getParentId();
        $parentType = $entity->getParentType();

        if (!$parentId || !$parentType) {
            return;
        }

        $data = (object) [
            'createdById' => $entity->getCreatedById(),
        ];

        if (!$entity->isNew()) {
            $data->noteId = $entity->getId();
            $data->pin = $entity->isAttributeChanged('isPinned');
        }

        $topic = "streamUpdate.$parentType.$parentId";

        $this->webSocketSubmission->submit($topic, null, $data);
    }
}
