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

namespace Espo\Core\Action;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ReadParams;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;

use Espo\ORM\Entity;

use stdClass;

class Service
{
    public function __construct(
        private ActionFactory $factory,
        private Acl $acl,
        private RecordServiceContainer $recordServiceContainer
    ) {}

    /**
     * Perform an action.
     *
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function process(string $entityType, string $action, string $id, stdClass $data): Entity
    {
        if (!$this->acl->checkScope($entityType)) {
            throw new ForbiddenSilent();
        }

        if (!$action || !$id) {
            throw new BadRequest();
        }

        $actionParams = new Params($entityType, $id);

        $actionProcessor = $this->factory->create($action, $entityType);

        $actionProcessor->process(
            $actionParams,
            Data::fromRaw($data)
        );

        $service = $this->recordServiceContainer->get($entityType);

        return $service->read($id, ReadParams::create());
    }
}
