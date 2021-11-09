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

namespace Espo\Services;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\{
    Forbidden,
    NotFound,
    Error,
};

use Espo\Core\{
    Acl,
    Acl\Table,
    Utils\Config,
    Utils\Language,
    Utils\Util,
    ORM\EntityManager,
    Select\SelectBuilderFactory,
    Record\ServiceContainer,
    Job\QueueName,
};

use Espo\{
    Tools\Pdf\Builder,
    Tools\Pdf\Contents,
    Tools\Pdf\TemplateWrapper,
    Tools\Pdf\Data,
    Tools\Pdf\IdDataMap,
    Tools\Pdf\Params,
    Tools\Pdf\Data\DataLoaderManager,
};

use Espo\Entities\Template;

use DateTime;
use stdClass;

class Pdf
{
    private const DEFAULT_ENGINE = 'Tcpdf';

    private $removeMassFilePeriod = '1 hour';

    private $config;

    private $entityManager;

    private $acl;

    private $defaultLanguage;

    private $selectBuilderFactory;

    private $builder;

    private $serviceContanier;

    private $dataLoaderManager;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        Acl $acl,
        Language $defaultLanguage,
        SelectBuilderFactory $selectBuilderFactory,
        Builder $builder,
        ServiceContainer $serviceContanier,
        DataLoaderManager $dataLoaderManager
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->defaultLanguage = $defaultLanguage;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->builder = $builder;
        $this->serviceContanier = $serviceContanier;
        $this->dataLoaderManager = $dataLoaderManager;
    }

    public function generateMailMerge(
        string $entityType,
        iterable $entityList,
        Template $template,
        string $name,
        ?string $campaignId = null
    ): string {

        $collection = $this->entityManager->getCollectionFactory()->create($entityType);

        foreach ($entityList as $entity) {
            $collection[] = $entity;
        }

        $params = Params::create()->withAcl();

        $idDataMap = IdDataMap::create();

        $service = $this->serviceContanier->get($entityType);

        foreach ($entityList as $entity) {
            $service->loadAdditionalFields($entity);

            $idDataMap->set(
                $entity->getId(),
                $this->dataLoaderManager->load($entity, $params)
            );

            // deprecated
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

        $filename = Util::sanitizeFileName($name) . '.pdf';

        $attachment = $this->entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => $filename,
            'relatedType' => 'Campaign',
            'type' => 'application/pdf',
            'relatedId' => $campaignId,
            'role' => 'Mail Merge',
            'contents' => $contents->getString(),
        ]);

        $this->entityManager->saveEntity($attachment);

        return $attachment->getId();
    }

    public function massGenerate(
        string $entityType,
        array $idList,
        string $templateId,
        bool $checkAcl = false
    ): string {

        $service = $this->serviceContanier->get($entityType);

        $maxCount = $this->config->get('massPrintPdfMaxCount');

        if ($maxCount) {
            if (count($idList) > $maxCount) {
                throw new Error("Mass print to PDF max count exceeded.");
            }
        }

        $template = $this->entityManager->getEntity('Template', $templateId);

        if (!$template) {
            throw new NotFound();
        }

        $params = Params::create();

        if ($checkAcl) {
            if (!$this->acl->check($template)) {
                throw new Forbidden();
            }

            if (!$this->acl->checkScope($entityType)) {
                throw new Forbidden();
            }

            $params = $params->withAcl();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withAccessControlFilter()
            ->build();

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->where([
                'id' => $idList,
            ])
            ->find();

        $idDataMap = IdDataMap::create();

        foreach ($collection as $entity) {
            $service->loadAdditionalFields($entity);

            $idDataMap->set(
                $entity->getId(),
                $this->dataLoaderManager->load($entity, $params)
            );

            // deprecated
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

        $entityTypeTranslated = $this->defaultLanguage->translate($entityType, 'scopeNamesPlural');

        $filename = Util::sanitizeFileName($entityTypeTranslated) . '.pdf';

        $attachment = $this->entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => $filename,
            'type' => 'application/pdf',
            'role' => 'Mass Pdf',
            'contents' => $contents->getString(),
        ]);

        $this->entityManager->saveEntity($attachment);

        $job = $this->entityManager->getEntity('Job');

        $job->set([
            'serviceName' => 'Pdf',
            'methodName' => 'removeMassFileJob',
            'data' => [
                'id' => $attachment->getId(),
            ],
            'executeTime' => (new DateTime())->modify('+' . $this->removeMassFilePeriod)->format('Y-m-d H:i:s'),
            'queue' => QueueName::Q1,
        ]);

        $this->entityManager->saveEntity($job);

        return $attachment->getId();
    }

    public function removeMassFileJob(stdClass $data): void
    {
        if (empty($data->id)) {
            return;
        }

        $attachment = $this->entityManager->getEntity('Attachment', $data->id);

        if (!$attachment) {
            return;
        }

        if ($attachment->get('role') !== 'Mass Pdf') {
            return;
        }

        $this->entityManager->removeEntity($attachment);
    }

    /**
     * Generate PDF. ACL check is processed if `$params` is null.
     */
    public function generate(Entity $entity, Template $template, ?Params $params = null, ?Data $data = null): string
    {
        if ($params === null) {
            $params = Params::create()->withAcl();
        }

        return $this->buildFromTemplateInternal($entity, $template, false, null, $params, $data);
    }

    /**
     * @deprecated
     */
    public function buildFromTemplate(
        Entity $entity,
        Template $template,
        bool $displayInline = false,
        ?array $additionalData = null
    ): ?string {

        return $this->buildFromTemplateInternal($entity, $template, $displayInline, $additionalData);
    }

    private function buildFromTemplateInternal(
        Entity $entity,
        Template $template,
        bool $displayInline = false,
        ?array $additionalData = null,
        ?Params $params = null,
        ?Data $data = null
    ): ?string {

        $entityType = $entity->getEntityType();

        $service = $this->serviceContanier->get($entityType);

        $service->loadAdditionalFields($entity);

        if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
            // deprecated
            $service->loadAdditionalFieldsForPdf($entity);
        }

        if ($template->get('entityType') !== $entityType) {
            throw new Error("Not matching entity types.");
        }

        $applyAcl = true;

        if ($params) {
            $applyAcl = $params->applyAcl();
        }

        if ($applyAcl) {
            if (
                !$this->acl->check($entity, Table::ACTION_READ) ||
                !$this->acl->check($template, Table::ACTION_READ)
            ) {
                throw new Forbidden();
            }
        }

        $templateWrapper = new TemplateWrapper($template);

        if (!$data) {
            $data = Data::create()
                ->withAdditionalTemplateData(
                    (object) ($additionalData ?? [])
                );
        }

        $data = $this->dataLoaderManager->load($entity, $params, $data);

        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printEntity($entity, $params, $data);

        if ($displayInline) {
            $this->displayInline($entity, $contents);

            return null;
        }

        return $contents->getString();
    }

    /**
     * @deprecated
     */
    private function displayInline(Entity $entity, Contents $contents): void
    {
        $fileName = Util::sanitizeFileName(
            $entity->get('name') ?? 'unnamed'
        );

        $fileName = $fileName . '.pdf';

        header('Content-Type: application/pdf');
        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Content-Disposition: inline; filename="'.basename($fileName).'"');

        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header('Content-Length: '. $contents->getLength());
        }

        echo $contents->getString();
    }
}
