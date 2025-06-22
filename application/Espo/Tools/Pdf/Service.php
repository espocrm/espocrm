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

namespace Espo\Tools\Pdf;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Template as TemplateEntity;
use Espo\ORM\EntityManager;
use Espo\Tools\Pdf\Data\DataLoaderManager;

class Service
{
    private const DEFAULT_ENGINE = 'Dompdf';

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private ServiceContainer $serviceContainer,
        private DataLoaderManager $dataLoaderManager,
        private Config $config,
        private Builder $builder,
        private Metadata $metadata,
    ) {}

    /**
     * Generate a PDF.
     *
     * @param string $entityType An entity type.
     * @param string $id A record ID.
     * @param string $templateId A template ID.
     * @param ?Params $params Params. If null, a params with the apply-acl will be used.
     * @params ?Data $data Data.
     *
     * @throws Error
     * @throws NotFound
     * @throws Forbidden
     */
    public function generate(
        string $entityType,
        string $id,
        string $templateId,
        ?Params $params = null,
        ?Data $data = null
    ): Contents {

        $params = $params ?? Params::create()->withAcl(true);

        $applyAcl = $params->applyAcl();

        $entity = $this->entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        $template = $this->entityManager
            ->getRDBRepositoryByClass(TemplateEntity::class)
            ->getById($templateId);

        if (!$template) {
            throw new NotFound("Template not found.");
        }

        if (!$template->isActive()) {
            throw new Forbidden("Template is not active.");
        }

        if ($applyAcl && !$this->acl->checkEntityRead($entity)) {
            throw new Forbidden("No access to record.");
        }

        if ($applyAcl && !$this->acl->checkEntityRead($template)) {
            throw new Forbidden("No access to template.");
        }

        $service = $this->serviceContainer->get($entityType);

        $service->loadAdditionalFields($entity);

        if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
            // For bc.
            $service->loadAdditionalFieldsForPdf($entity);
        }

        if ($template->getTargetEntityType() !== $entityType) {
            throw new Error("Not matching entity types.");
        }

        $pdfA = $this->metadata->get("pdfDefs.{$entity->getEntityType()}.pdfA") ?? false;

        $params = $params->withPdfA($pdfA);

        $data = $this->dataLoaderManager->load($entity, $params, $data);
        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

        $printer = $this->builder
            ->setTemplate(new TemplateWrapper($template))
            ->setEngine($engine)
            ->build();

        return $printer->printEntity($entity, $params, $data);
    }
}
