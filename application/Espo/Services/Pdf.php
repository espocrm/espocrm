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
    Acl,
    Acl\Table,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    Utils\Util,
    ORM\EntityManager,
    ORM\Entity,
    Select\SelectBuilderFactory,
    Record\ServiceContainer,
};

use Espo\{
    Tools\Pdf\Builder,
    Tools\Pdf\Contents,
    Tools\Pdf\TemplateWrapper,
    Tools\Pdf\Data,
};

use Espo\Entities\Template;

use DateTime;
use StdClass;

class Pdf
{
    protected const DEFAULT_ENGINE = 'Tcpdf';

    protected $removeMassFilePeriod = '1 hour';

    private $config;

    private $metadata;

    private $entityManager;

    private $acl;

    private $defaultLanguage;

    private $selectBuilderFactory;

    private $builder;

    private $serviceContanier;

    public function __construct(
        Config $config,
        Metadata $metadata,
        EntityManager $entityManager,
        Acl $acl,
        Language $defaultLanguage,
        SelectBuilderFactory $selectBuilderFactory,
        Builder $builder,
        ServiceContainer $serviceContanier
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->defaultLanguage = $defaultLanguage;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->builder = $builder;
        $this->serviceContanier = $serviceContanier;
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

        $service = $this->serviceContanier->get($entityType);

        foreach ($entityList as $entity) {
            $service->loadAdditionalFields($entity);

            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
        }

        $templateWrapper = new TemplateWrapper($template);

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine(self::DEFAULT_ENGINE)
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

        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

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

    public function removeMassFileJob(StdClass $data): void
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
     * Generate PDF. ACL check is processed if `$data` is null.
     */
    public function generate(Entity $entity, Template $template, ?Data $data = null): string
    {
        return $this->buildFromTemplateInternal($entity, $template, false, null, $data);
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
        Entity $template,
        bool $displayInline = false,
        ?array $additionalData = null,
        ?Data $data = null
    ): ?string {

        $entityType = $entity->getEntityType();

        $service = $this->serviceContanier->get($entityType);

        $service->loadAdditionalFields($entity);

        if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
            $service->loadAdditionalFieldsForPdf($entity);
        }

        if ($template->get('entityType') !== $entityType) {
            throw new Error("Not matching entity types.");
        }

        $applyAcl = true;

        if ($data) {
            $applyAcl = $data->applyAcl();
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
            $data = Data
                ::fromNothing()
                ->withAdditionalTemplateData($additionalData ?? [])
                ->withAcl($applyAcl);
        }

        $engine = $this->config->get('pdfEngine') ?? self::DEFAULT_ENGINE;

        $printer = $this->builder
            ->setTemplate($templateWrapper)
            ->setEngine($engine)
            ->build();

        $contents = $printer->printEntity($entity, $data);

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
