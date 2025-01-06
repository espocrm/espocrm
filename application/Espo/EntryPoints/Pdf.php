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

namespace Espo\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Util;
use Espo\Entities\Template;
use Espo\Tools\Pdf\Service;

class Pdf implements EntryPoint
{
    private EntityManager $entityManager;
    private Service $service;

    public function __construct(EntityManager $entityManager, Service $service)
    {
        $this->entityManager = $entityManager;
        $this->service = $service;
    }

    public function run(Request $request, Response $response): void
    {
        $entityId = $request->getQueryParam('entityId');
        $entityType = $request->getQueryParam('entityType');
        $templateId = $request->getQueryParam('templateId');

        if (!$entityId || !$entityType || !$templateId) {
            throw new BadRequest("No entityId or entityType or templateId.");
        }

        $entity = $this->entityManager->getEntityById($entityType, $entityId);
        /** @var ?Template $template */
        $template = $this->entityManager->getEntityById(Template::ENTITY_TYPE, $templateId);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        if (!$template) {
            throw new NotFound("Template not found.");
        }

        $contents = $this->service->generate($entityType, $entityId, $templateId);

        $fileName = Util::sanitizeFileName($entity->get(Field::NAME) ?? 'unnamed');

        $fileName = $fileName . '.pdf';

        $response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Cache-Control', 'private, must-revalidate, post-check=0, pre-check=0, max-age=1')
            ->setHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($fileName) . '"');

        if (!$request->getServerParam('HTTP_ACCEPT_ENCODING')) {
            $response->setHeader('Content-Length', (string) $contents->getStream()->getSize());
        }

        $response->writeBody($contents->getStream());
    }
}
