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
 ************************************************************************/

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Htmlizer\Htmlizer;


class Pdf extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = true;

    public function run()
    {

        if (empty($_GET['entityId']) || empty($_GET['entityType']) || empty($_GET['templateId'])) {
            throw new BadRequest();
        }
        $entityId = $_GET['entityId'];
        $entityType = $_GET['entityType'];
        $templateId = $_GET['templateId'];

        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
        $template = $this->getEntityManager()->getEntity('Template', $templateId);

        $this->getContainer()->get('serviceFactory')->create($entityType)->loadAdditionalFields($entity);

        if (!$entity || !$template) {
            throw new NotFound();
        }

        if ($template->get('entityType') !== $entityType) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($entity, 'read') || !$this->getAcl()->check($template, 'read')) {
            throw new Forbidden();
        }

        $fileName = $entity->get('name') . '.pdf';

        $htmlizer = new Htmlizer($this->getFileManager(), $this->getDateTime(), $this->getNumber());

        $pdf = new \Espo\Core\Pdf\Tcpdf();
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

        $pdf->output($fileName);

        exit;
    }
}

