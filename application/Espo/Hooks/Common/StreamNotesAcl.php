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

namespace Espo\Hooks\Common;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\Entity;

use Espo\Tools\Stream\Service as Service;

/**
 * Notes having `related` or `superParent` are subjects to access control
 * through `users` and `teams` fields.
 *
 * When users or teams of `related` or `parent` record are changed
 * the note record will be changed too.
 */
class StreamNotesAcl
{
    public static int $order = 10;

    private Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param array<string, mixed> $options
     * @throws \Espo\Core\Exceptions\Error
     */
    public function afterSave(Entity $entity, array $options): void
    {
        if (!empty($options['noStream'])) {
            return;
        }

        if (!empty($options[SaveOption::SILENT])) {
            return;
        }

        if (!empty($options['skipStreamNotesAcl'])) {
            return;
        }

        if ($entity->isNew()) {
            return;
        }

        $forceProcessNoteNotifications = !empty($options['forceProcessNoteNotifications']);

        $this->service->processNoteAcl($entity, $forceProcessNoteNotifications);
    }
}
