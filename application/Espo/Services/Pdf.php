<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

use Espo\ORM\Entity;

use \Espo\Core\Htmlizer\Htmlizer;

class Pdf extends \Espo\Core\Services\Base
{

    protected $fontFace = 'freesans';

    protected $fontSize = 12;


    protected function init()
    {
        $this->addDependency('fileManager');
        $this->addDependency('acl');
        $this->addDependency('metadata');
        $this->addDependency('serviceFactory');
        $this->addDependency('dateTime');
        $this->addDependency('number');
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

    public function buildFromTemplate(Entity $entity, Entity $template, $displayInline = false)
    {
        $entityType = $entity->getEntityType();

        $service = $this->getServiceFactory()->create($entityType);

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

        $htmlizer = new Htmlizer($this->getFileManager(), $this->getInjection('dateTime'), $this->getInjection('number'), $this->getAcl());

        $pdf = new \Espo\Core\Pdf\Tcpdf();

        $fontFace = $this->getConfig()->get('pdfFontFace', $this->fontFace);

        $pdf->setFont($fontFace, '', $this->fontSize, '', true);
        $pdf->setPrintHeader(false);

        $pdf->setAutoPageBreak(true, $template->get('bottomMargin'));
        $pdf->setMargins($template->get('leftMargin'), $template->get('topMargin'), $template->get('rightMargin'));

        if ($template->get('printFooter')) {
            $htmlFooter = $htmlizer->render($entity, $template->get('footer'));
            $pdf->setFooterPosition($template->get('footerPosition'));
            $pdf->setFooterHtml($htmlFooter);
        } else {
            $pdf->setPrintFooter(false);
        }

        $pdf->addPage();

        $htmlHeader = $htmlizer->render($entity, $template->get('header'));
        $pdf->writeHTML($htmlHeader, true, false, true, false, '');

        $htmlBody = $htmlizer->render($entity, $template->get('body'));
        $pdf->writeHTML($htmlBody, true, false, true, false, '');

        if ($displayInline) {
            $name = $entity->get('name');
            $name = \Espo\Core\Utils\Util::sanitizeFileName($name);
            $fileName = $name . '.pdf';

            $pdf->output($fileName, 'I');
            return;
        }

        return $pdf->output('', 'S');
    }
}

