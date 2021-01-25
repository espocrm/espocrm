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

use Espo\Core\Exceptions\{
    Forbidden,
    NotFound,
    Error,
};

use Espo\Core\{
    ServiceFactory,
    Acl,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    Utils\Util,
    Htmlizer\Htmlizer,
    Htmlizer\Factory as HtmlizerFactory,
    ORM\EntityManager,
    ORM\Entity,
    Pdf\Tcpdf,
    Select\SelectBuilderFactory,
};

use Espo\{
    Tools\Pdf\Builder,
    Tools\Pdf\Contents,
    Tools\Pdf\TemplateWrapper,
    Tools\Pdf\Data,
};

use Espo\ORM\{
    QueryParams\Select,
};

use DateTime;

class Pdf
{
    protected $removeMassFilePeriod = '1 hour';

    protected $config;
    protected $serviceFactory;
    protected $metadata;
    protected $entityManager;
    protected $acl;
    protected $defaultLanguage;
    protected $htmlizerFactory;
    protected $selectBuilderFactory;
    protected $builder;

    public function __construct(
        Config $config,
        ServiceFactory $serviceFactory,
        Metadata $metadata,
        EntityManager $entityManager,
        Acl $acl,
        Language $defaultLanguage,
        HtmlizerFactory $htmlizerFactory,
        SelectBuilderFactory $selectBuilderFactory,
        Builder $builder
    ) {
        $this->config = $config;
        $this->serviceFactory = $serviceFactory;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->defaultLanguage = $defaultLanguage;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->builder = $builder;
    }

    public function generateMailMerge(
        string $entityType, iterable $entityList, Entity $template, string $name, ?string $campaignId = null
    ) : string {
        $collection = $this->entityManager->getCollectionFactory()->create($entityType);

        foreach ($entityList as $entity) {
            $collection[] = $entity;
        }

        if ($this->serviceFactory->checkExists($entityType)) {
            $service = $this->serviceFactory->create($entityType);
        } else {
            $service = $this->serviceFactory->create('Record');
        }

        foreach ($entityList as $entity) {
            $service->loadAdditionalFields($entity);

            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
        }

        $templateWrapper = new TemplateWrapper($template);

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine('Tcpdf')
            ->build();

        $contents = $printer->printCollection($collection);

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

        return $attachment->id;
    }

    public function massGenerate(string $entityType, array $idList, string $templateId, bool $checkAcl = false) : string
    {
        if ($this->serviceFactory->checkExists($entityType)) {
            $service = $this->serviceFactory->create($entityType);
        } else {
            $service = $this->serviceFactory->create('Record');
        }

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

        if ($checkAcl) {
            if (!$this->acl->check($template)) {
                throw new Forbidden();
            }

            if (!$this->acl->checkScope($entityType)) {
                throw new Forbidden();
            }
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withAccessControlFilter()
            ->build();

        $collection = $this->entityManager
            ->getRepository($entityType)
            ->clone($query)
            ->where([
                'id' => $idList,
            ])
            ->find();

        foreach ($collection as $entity) {
            $service->loadAdditionalFields($entity);

            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
        }

        $templateWrapper = new TemplateWrapper($template);

        $engine = $this->config->get('pdfEngine') ?? 'Tcpdf';

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printCollection($collection);

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
                'id' => $attachment->id
            ],
            'executeTime' => (new DateTime())->modify('+' . $this->removeMassFilePeriod)->format('Y-m-d H:i:s'),
            'queue' => 'q1',
        ]);

        $this->entityManager->saveEntity($job);

        return $attachment->id;
    }

    public function removeMassFileJob($data)
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

    public function buildFromTemplate(Entity $entity, Entity $template, $displayInline = false, ?array $additionalData = null)
    {
        $entityType = $entity->getEntityType();

        if ($this->serviceFactory->checkExists($entityType)) {
            $service = $this->serviceFactory->create($entityType);
        } else {
            $service = $this->serviceFactory->create('Record');
        }

        $service->loadAdditionalFields($entity);

        if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
            $service->loadAdditionalFieldsForPdf($entity);
        }

        if ($template->get('entityType') !== $entityType) {
            throw new Error("Not matching entity types.");
        }

        if (!$this->acl->check($entity, 'read') || !$this->acl->check($template, 'read')) {
            throw new Forbidden();
        }

        $templateWrapper = new TemplateWrapper($template);

        $data = Data::createFromArray([
            'additionalTemplateData' => $additionalData,
        ]);

        $engine = $this->config->get('pdfEngine') ?? 'Tcpdf';

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printEntity($entity, $data);

        if ($displayInline) {
            $this->displayInline($entity, $contents);

            return;
        }

        return $contents->getString();
    }

    protected function displayInline(Entity $entity, Contents $contents)
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

        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) OR empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header('Content-Length: '. $contents->getLength());
        }

        echo $contents->getString();
    }

    protected function createHtmlizer()
    {
        return $this->htmlizerFactory->create();
    }
}
