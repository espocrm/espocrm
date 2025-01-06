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

namespace Espo\Modules\Crm\Tools\Campaign;

use Espo\Core\Exceptions\Error;
use Espo\Core\Field\LinkParent;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\Entities\Template;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\Tools\Pdf\Builder;
use Espo\Tools\Pdf\Data\DataLoaderManager;
use Espo\Tools\Pdf\IdDataMap;
use Espo\Tools\Pdf\Params;
use Espo\Tools\Pdf\TemplateWrapper;
use Espo\Tools\Pdf\ZipContents;

class MailMergeGenerator
{
    private const DEFAULT_ENGINE = 'Dompdf';
    private const ATTACHMENT_MAIL_MERGE_ROLE = 'Mail Merge';

    private EntityManager $entityManager;
    private DataLoaderManager $dataLoaderManager;
    private ServiceContainer $serviceContainer;
    private Builder $builder;
    private Config $config;
    private FileStorageManager $fileStorageManager;

    public function __construct(
        EntityManager $entityManager,
        DataLoaderManager $dataLoaderManager,
        ServiceContainer $serviceContainer,
        Builder $builder,
        Config $config,
        FileStorageManager $fileStorageManager
    ) {
        $this->entityManager = $entityManager;
        $this->dataLoaderManager = $dataLoaderManager;
        $this->serviceContainer = $serviceContainer;
        $this->builder = $builder;
        $this->config = $config;
        $this->fileStorageManager = $fileStorageManager;
    }

    /**
     * Generate a mail-merge PDF.
     *
     * @return string An attachment ID.
     * @param EntityCollection<Entity> $collection
     * @throws Error
     */
    public function generate(
        EntityCollection $collection,
        Template $template,
        ?string $campaignId = null,
        ?string $name = null
    ): string {

        $entityType = $collection->getEntityType();

        if (!$entityType) {
            throw new Error("No entity type.");
        }

        $name = $name ?? $campaignId ?? $entityType;

        $params = Params::create()->withAcl();

        $idDataMap = IdDataMap::create();

        $service = $this->serviceContainer->get($entityType);

        foreach ($collection as $entity) {
            $service->loadAdditionalFields($entity);

            $idDataMap->set(
                $entity->getId(),
                $this->dataLoaderManager->load($entity, $params)
            );

            // For bc.
            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
        }

        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

        $templateWrapper = new TemplateWrapper($template);

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printCollection($collection, $params, $idDataMap);

        $type = $contents instanceof ZipContents ?
            'application/zip' :
            'application/pdf';

        $filename = $contents instanceof ZipContents ?
            Util::sanitizeFileName($name) . '.zip' :
            Util::sanitizeFileName($name) . '.pdf';

        /** @var Attachment $attachment */
        $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

        $relatedLink = $campaignId ?
            LinkParent::create(Campaign::ENTITY_TYPE, $campaignId) : null;

        $attachment
            ->setRelated($relatedLink)
            ->setSize($contents->getStream()->getSize())
            ->setRole(self::ATTACHMENT_MAIL_MERGE_ROLE)
            ->setName($filename)
            ->setType($type);

        $this->entityManager->saveEntity($attachment);

        $this->fileStorageManager->putStream($attachment, $contents->getStream());

        return $attachment->getId();
    }
}
