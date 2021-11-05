<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Action;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\ForbiddenSilent,
    Exceptions\BadRequest,
    Exceptions\NotFound,
    Record\ServiceContainer as RecordServiceContainer,
    Record\ReadParams,
    Acl,
};

use Espo\ORM\Entity;

use stdClass;

class Service
{
    private $factory;

    private $acl;

    private $recordServiceContainer;

    public function __construct(
        ActionFactory $factory,
        Acl $acl,
        RecordServiceContainer $recordServiceContainer
    ) {
        $this->factory = $factory;
        $this->acl = $acl;
        $this->recordServiceContainer = $recordServiceContainer;
    }

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

        $entity = $service->read($id, ReadParams::create());

        return $entity;
    }
}
