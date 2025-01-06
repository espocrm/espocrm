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

use DateTime;
use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Job\QueueName;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\Entities\Template as TemplateEntity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\Tools\Pdf\Data\DataLoaderManager;
use Espo\Tools\Pdf\Jobs\RemoveMassFile;

class MassService
{
    private const DEFAULT_ENGINE = 'Dompdf';
    private const ATTACHMENT_MASS_PDF_ROLE = 'Mass Pdf';
    private const REMOVE_MASS_PDF_PERIOD = '1 hour';

    private ServiceContainer $serviceContainer;
    private Config $config;
    private EntityManager $entityManager;
    private Acl $acl;
    private DataLoaderManager $dataLoaderManager;
    private SelectBuilderFactory $selectBuilderFactory;
    private Builder $builder;
    private Language $defaultLanguage;
    private JobSchedulerFactory $jobSchedulerFactory;
    private FileStorageManager $fileStorageManager;

    public function __construct(
        ServiceContainer $serviceContainer,
        Config $config,
        EntityManager $entityManager,
        Acl $acl,
        DataLoaderManager $dataLoaderManager,
        SelectBuilderFactory $selectBuilderFactory,
        Builder $builder,
        Language $defaultLanguage,
        JobSchedulerFactory $jobSchedulerFactory,
        FileStorageManager $fileStorageManager
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->dataLoaderManager = $dataLoaderManager;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->builder = $builder;
        $this->defaultLanguage = $defaultLanguage;
        $this->jobSchedulerFactory = $jobSchedulerFactory;
        $this->fileStorageManager = $fileStorageManager;
    }

    /**
     * Generate a PDF for multiple records.
     *
     * @param string[] $idList
     * @throws Error
     * @throws NotFound
     * @throws Forbidden
     */
    public function generate(
        string $entityType,
        array $idList,
        string $templateId,
        bool $withAcl = true
    ): string {

        $service = $this->serviceContainer->get($entityType);

        $maxCount = $this->config->get('massPrintPdfMaxCount');

        if ($maxCount && count($idList) > $maxCount) {
            throw new Error("Mass print to PDF max count exceeded.");
        }

        /** @var ?TemplateEntity $template */
        $template = $this->entityManager->getEntityById(TemplateEntity::ENTITY_TYPE, $templateId);

        if (!$template) {
            throw new NotFound();
        }

        $params = Params::create();

        if ($withAcl) {
            if (!$this->acl->check($template)) {
                throw new Forbidden();
            }

            if (!$this->acl->checkScope($entityType)) {
                throw new Forbidden();
            }

            $params = $params->withAcl();
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType);

        if ($withAcl) {
            $selectBuilder->withAccessControlFilter();
        }

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($selectBuilder->build())
            ->where([
                Attribute::ID => $idList,
            ])
            ->find();

        $idDataMap = IdDataMap::create();

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

        $templateWrapper = new TemplateWrapper($template);

        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printCollection($collection, $params, $idDataMap);

        $entityTypeTranslated = $this->defaultLanguage->translateLabel($entityType, 'scopeNamesPlural');

        $type = $contents instanceof ZipContents ?
            'application/zip' :
            'application/pdf';

        $filename = $contents instanceof ZipContents ?
            Util::sanitizeFileName($entityTypeTranslated) . '.zip' :
            Util::sanitizeFileName($entityTypeTranslated) . '.pdf';

        /** @var Attachment $attachment */
        $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

        $attachment
            ->setName($filename)
            ->setType($type)
            ->setRole(self::ATTACHMENT_MASS_PDF_ROLE)
            ->setSize($contents->getStream()->getSize());

        $this->entityManager->saveEntity($attachment);

        $this->fileStorageManager->putStream($attachment, $contents->getStream());

        $this->jobSchedulerFactory
            ->create()
            ->setClassName(RemoveMassFile::class)
            ->setData(
                JobData
                    ::create()
                    ->withTargetId($attachment->getId())
                    ->withTargetType(Attachment::ENTITY_TYPE)
            )
            ->setTime(
                (new DateTime())->modify('+' . self::REMOVE_MASS_PDF_PERIOD)
            )
            ->setQueue(QueueName::Q1)
            ->schedule();

        return $attachment->getId();
    }
}
