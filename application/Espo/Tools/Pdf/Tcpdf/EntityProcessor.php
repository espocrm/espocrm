<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Tools\Pdf\Tcpdf;

use Espo\Core\{
    Exceptions\Error,
    Utils\Config,
    Htmlizer\Factory as HtmlizerFactory,
    Pdf\Tcpdf,
};

use Espo\{
    ORM\Entity,
    Tools\Pdf\Template,
    Tools\Pdf\Data,
};

class EntityProcessor
{
    protected $fontFace = 'freesans';

    protected $fontSize = 12;

    protected $config;
    protected $htmlizerFactory;

    public function __construct(Config $config, HtmlizerFactory $htmlizerFactory)
    {
        $this->config = $config;
        $this->htmlizerFactory = $htmlizerFactory;
    }

    public function process(Tcpdf $pdf, Template $template, Entity $entity, Data $data)
    {
        $additionalData = $data->getAdditionalTemplateData();

        $htmlizer = $this->htmlizerFactory->create();

        $fontFace = $this->config->get('pdfFontFace', $this->fontFace);

        if ($template->getFontFace()) {
            $fontFace = $template->getFontFace();
        }

        $pdf->setFont($fontFace, '', $this->fontSize, '', true);

        $pdf->setAutoPageBreak(true, $template->getBottomMargin());

        $pdf->setMargins(
            $template->getLeftMargin(),
            $template->getTopMargin(),
            $template->getRightMargin()
        );

        if ($template->hasFooter()) {
            $htmlFooter = $htmlizer->render(
                $entity,
                $template->getFooter(),
                null,
                $additionalData
            );

            $pdf->setFooterFont([$fontFace, '', $this->fontSize]);
            $pdf->setFooterPosition($template->getFooterPosition());
            $pdf->setFooterHtml($htmlFooter);
        }
        else {
            $pdf->setPrintFooter(false);
        }

        $pageOrientation = $template->getPageOrientation();

        $pageFormat = $template->getPageFormat();

        if ($pageFormat === 'Custom') {
            $pageFormat = [
                $template->getPageWidth(),
                $template->getPageHeight(),
            ];
        }

        $pageOrientationCode = 'P';

        if ($pageOrientation === 'Landscape') {
            $pageOrientationCode = 'L';
        }

        $htmlHeader = $htmlizer->render(
            $entity,
            $template->getHeader(),
            null,
            $additionalData
        );

        if ($template->hasHeader()) {
            $pdf->setHeaderFont([$fontFace, '', $this->fontSize]);
            $pdf->setHeaderPosition($template->getHeaderPosition());
            $pdf->setHeaderHtml($htmlHeader);

            $pdf->addPage($pageOrientationCode, $pageFormat);
        }
        else {
            $pdf->addPage($pageOrientationCode, $pageFormat);

            $pdf->setPrintHeader(false);

            $pdf->writeHTML($htmlHeader, true, false, true, false, '');
        }

        $htmlBody = $htmlizer->render(
            $entity,
            $template->getBody(),
            null,
            $additionalData
        );

        $pdf->writeHTML($htmlBody, true, false, true, false, '');
    }
}
