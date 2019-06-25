<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

use Espo\ORM\Entity;

use \Espo\Core\Htmlizer\Htmlizer;

class Pdf extends \Espo\Core\Services\Base
{

    protected $fontFace = 'freesans';

    protected $fontSize = 12;

    protected $removeMassFilePeriod = '1 hour';

    protected function init()
    {
        $this->addDependency('fileManager');
        $this->addDependency('acl');
        $this->addDependency('metadata');
        $this->addDependency('serviceFactory');
        $this->addDependency('dateTime');
        $this->addDependency('number');
        $this->addDependency('entityManager');
        $this->addDependency('defaultLanguage');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function printEntity(Entity $entity, Entity $template, Htmlizer $htmlizer, \Espo\Core\Pdf\Tcpdf $pdf)
    {
        $fontFace = $this->getConfig()->get('pdfFontFace', $this->fontFace);
        if ($template->get('fontFace')) {
            $fontFace = $template->get('fontFace');
        }

        $pdf->setFont($fontFace, '', $this->fontSize, '', true);

        $pdf->setPrintHeader(false);

        $pdf->setAutoPageBreak(true, $template->get('bottomMargin'));
        $pdf->setMargins($template->get('leftMargin'), $template->get('topMargin'), $template->get('rightMargin'));

        if ($template->get('printFooter')) {
            $htmlFooter = $htmlizer->render($entity, $template->get('footer'));
            $pdf->setFooterFont([$fontFace, '', $this->fontSize]);
            $pdf->setFooterPosition($template->get('footerPosition'));
            $pdf->setFooterHtml($htmlFooter);
        } else {
            $pdf->setPrintFooter(false);
        }

        $pageOrientation = 'Portrait';
        if ($template->get('pageOrientation')) {
            $pageOrientation = $template->get('pageOrientation');
        }
        $pageFormat = 'A4';
        if ($template->get('pageFormat')) {
            $pageFormat = $template->get('pageFormat');
        }
        if ($pageFormat === 'Custom') {
            $pageFormat = [$template->get('pageWidth'), $template->get('pageHeight')];
        }
        $pageOrientationCode = 'P';
        if ($pageOrientation === 'Landscape') {
            $pageOrientationCode = 'L';
        }

        $pdf->addPage($pageOrientationCode, $pageFormat);

        $htmlHeader = $htmlizer->render($entity, $template->get('header'));
        $pdf->writeHTML($htmlHeader, true, false, true, false, '');

        $htmlBody = $htmlizer->render($entity, $template->get('body'));
        $pdf->writeHTML($htmlBody, true, false, true, false, '');
    }

    public function generateMailMerge($entityType, $entityList, Entity $template, $name, $campaignId = null)
    {
        $htmlizer = $this->createHtmlizer();
        $pdf = new \Espo\Core\Pdf\Tcpdf();
        $pdf->setUseGroupNumbers(true);

        if ($this->getServiceFactory()->checkExists($entityType)) {
            $service = $this->getServiceFactory()->create($entityType);
        } else {
            $service = $this->getServiceFactory()->create('Record');
        }

        foreach ($entityList as $entity) {
            $service->loadAdditionalFields($entity);
            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
            $pdf->startPageGroup();
            $this->printEntity($entity, $template, $htmlizer, $pdf);
        }

        $filename = \Espo\Core\Utils\Util::sanitizeFileName($name) . '.pdf';

        $attachment = $this->getEntityManager()->getEntity('Attachment');

        $content = $pdf->output('', 'S');

        $attachment->set([
            'name' => $filename,
            'relatedType' => 'Campaign',
            'type' => 'application/pdf',
            'relatedId' => $campaignId,
            'role' => 'Mail Merge',
            'contents' => $content
        ]);

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment->id;
    }

    public function massGenerate($entityType, $idList, $templateId, $checkAcl = false)
    {
        if ($this->getServiceFactory()->checkExists($entityType)) {
            $service = $this->getServiceFactory()->create($entityType);
        } else {
            $service = $this->getServiceFactory()->create('Record');
        }

        $maxCount = $this->getConfig()->get('massPrintPdfMaxCount');
        if ($maxCount) {
            if (count($idList) > $maxCount) {
                throw new Error("Mass print to PDF max count exceeded.");
            }
        }

        $template = $this->getEntityManager()->getEntity('Template', $templateId);

        if (!$template) {
            throw new NotFound();
        }

        if ($checkAcl) {
            if (!$this->getAcl()->check($template)) {
                throw new Forbidden();
            }
            if (!$this->getAcl()->checkScope($entityType)) {
                throw new Forbidden();
            }
        }

        $htmlizer = $this->createHtmlizer();
        $pdf = new \Espo\Core\Pdf\Tcpdf();
        $pdf->setUseGroupNumbers(true);

        $entityList = $this->getEntityManager()->getRepository($entityType)->where([
            'id' => $idList
        ])->find();

        foreach ($entityList as $entity) {
            if ($checkAcl) {
                if (!$this->getAcl()->check($entity)) continue;
            }
            $service->loadAdditionalFields($entity);
            if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
                $service->loadAdditionalFieldsForPdf($entity);
            }
            $pdf->startPageGroup();
            $this->printEntity($entity, $template, $htmlizer, $pdf);
        }

        $content = $pdf->output('', 'S');

        $entityTypeTranslated = $this->getInjection('defaultLanguage')->translate($entityType, 'scopeNamesPlural');
        $filename = \Espo\Core\Utils\Util::sanitizeFileName($entityTypeTranslated) . '.pdf';

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set([
            'name' => $filename,
            'type' => 'application/pdf',
            'role' => 'Mass Pdf',
            'contents' => $content
        ]);
        $this->getEntityManager()->saveEntity($attachment);

        $job = $this->getEntityManager()->getEntity('Job');
        $job->set([
            'serviceName' => 'Pdf',
            'methodName' => 'removeMassFileJob',
            'data' => [
                'id' => $attachment->id
            ],
            'executeTime' => (new \DateTime())->modify('+' . $this->removeMassFilePeriod)->format('Y-m-d H:i:s'),
            'queue' => 'q1'
        ]);
        $this->getEntityManager()->saveEntity($job);

        return $attachment->id;
    }

    public function removeMassFileJob($data)
    {
        if (empty($data->id)) {
            return;
        }
        $attachment = $this->getEntityManager()->getEntity('Attachment', $data->id);
        if (!$attachment) return;
        if ($attachment->get('role') !== 'Mass Pdf') return;
        $this->getEntityManager()->removeEntity($attachment);
    }

    public function buildFromTemplate(Entity $entity, Entity $template, $displayInline = false)
    {
        $entityType = $entity->getEntityType();

        if ($this->getServiceFactory()->checkExists($entityType)) {
            $service = $this->getServiceFactory()->create($entityType);
        } else {
            $service = $this->getServiceFactory()->create('Record');
        }

        $service->loadAdditionalFields($entity);

        if (method_exists($service, 'loadAdditionalFieldsForPdf')) {
            $service->loadAdditionalFieldsForPdf($entity);
        }

        if ($template->get('entityType') !== $entityType) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($entity, 'read') || !$this->getAcl()->check($template, 'read')) {
            throw new Forbidden();
        }

        $htmlizer = $this->createHtmlizer();
        $pdf = new \Espo\Core\Pdf\Tcpdf();

        $this->printEntity($entity, $template, $htmlizer, $pdf);

        if ($displayInline) {
            $name = $entity->get('name');
            $name = \Espo\Core\Utils\Util::sanitizeFileName($name);
            $fileName = $name . '.pdf';

            $pdf->output($fileName, 'I');
            return;
        }

        return $pdf->output('', 'S');
    }

    protected function createHtmlizer()
    {
        return new Htmlizer(
            $this->getFileManager(),
            $this->getInjection('dateTime'),
            $this->getInjection('number'),
            $this->getAcl(),
            $this->getInjection('entityManager'),
            $this->getInjection('metadata'),
            $this->getInjection('defaultLanguage')
        );
    }
}
