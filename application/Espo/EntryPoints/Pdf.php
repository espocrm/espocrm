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

namespace Espo\EntryPoints;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\EntryPoints\{
    EntryPoint,
};

use Espo\Core\{
    ORM\EntityManager,
    ServiceFactory,
    Api\Request,
    Api\Response,
};

class Pdf implements EntryPoint
{
    protected $entityManager;
    protected $serviceFactory;

    public function __construct(EntityManager $entityManager, ServiceFactory $serviceFactory)
    {
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
    }

    public function run(Request $request, Response $response) : void
    {
        $entityId = $request->getQueryParam('entityId');
        $entityType = $request->getQueryParam('entityType');
        $templateId = $request->getQueryParam('templateId');

        if (!$entityId || !$entityType || !$templateId) {
            throw new BadRequest();
        }

        $entity = $this->entityManager->getEntity($entityType, $entityId);
        $template = $this->entityManager->getEntity('Template', $templateId);

        if (!$entity || !$template) {
            throw new NotFound();
        }

        $this->serviceFactory->create('Pdf')->buildFromTemplate($entity, $template, true);

        exit;
    }
}
