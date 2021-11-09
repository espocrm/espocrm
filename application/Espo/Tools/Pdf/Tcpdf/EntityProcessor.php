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

namespace Espo\Tools\Pdf\Tcpdf;

use Espo\Core\Utils\Config;
use Espo\Core\Htmlizer\TemplateRendererFactory;
use Espo\Core\Htmlizer\TemplateRenderer;

use Espo\ORM\Entity;

use Espo\Tools\Pdf\Template;
use Espo\Tools\Pdf\Data;
use Espo\Tools\Pdf\Params;
use Espo\Tools\Pdf\Tcpdf\Tcpdf;

class EntityProcessor
{
    private $fontFace = 'freesans';

    private $fontSize = 12;

    private $config;

    private $templateRendererFactory;

    public function __construct(Config $config, TemplateRendererFactory $templateRendererFactory)
    {
        $this->config = $config;
        $this->templateRendererFactory = $templateRendererFactory;
    }

    public function process(Tcpdf $pdf, Template $template, Entity $entity, Params $params, Data $data): void
    {
        $renderer = $this->templateRendererFactory
            ->create()
            ->setApplyAcl($params->applyAcl())
            ->setEntity($entity)
            ->setData($data->getAdditionalTemplateData());

        $fontFace = $this->config->get('pdfFontFace', $this->fontFace);
        $fontSize = $this->config->get('pdfFontSize', $this->fontSize);

        if ($template->getFontFace()) {
            $fontFace = $template->getFontFace();
        }

        if ($template->hasTitle()) {
            $title = $this->replacePlaceholders($template->getTitle(), $entity);

            $pdf->SetTitle($title);
        }

        $pdf->setFont($fontFace, '', $fontSize, '', true);

        $pdf->setAutoPageBreak(true, $template->getBottomMargin());

        $pdf->setMargins(
            $template->getLeftMargin(),
            $template->getTopMargin(),
            $template->getRightMargin()
        );

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

        if ($template->hasFooter()) {
            $htmlFooter = $this->render($renderer, $template->getFooter());

            $pdf->setFooterFont([$fontFace, '', $this->fontSize]);
            $pdf->setFooterPosition($template->getFooterPosition());

            $pdf->setFooterHtml($htmlFooter);
        }
        else {
            $pdf->setPrintFooter(false);
        }

        if ($template->hasHeader()) {
            $htmlHeader = $this->render($renderer, $template->getHeader());

            $pdf->setHeaderFont([$fontFace, '', $this->fontSize]);
            $pdf->setHeaderPosition($template->getHeaderPosition());

            $pdf->setHeaderHtml($htmlHeader);
        }
        else {
            $pdf->setPrintHeader(false);
        }

        $pdf->addPage($pageOrientationCode, $pageFormat);

        $htmlBody = $this->render($renderer, $template->getBody());

        $pdf->writeHTML($htmlBody, true, false, true, false, '');
    }

    private function render(TemplateRenderer $renderer, string $template): string
    {
        $html = $renderer->renderTemplate($template);

        return preg_replace_callback(
            '/<barcodeimage data="([^"]+)"\/>/',
            function ($matches) {
                $dataString = $matches[1];

                $data = json_decode(urldecode($dataString), true);

                return $this->composeBarcodeTag($data);
            },
            $html
        );
    }

    private function composeBarcodeTag(array $data): string
    {
        $value = $data['value'] ?? null;

        $codeType = $data['type'] ?? 'CODE128';

        $typeMap = [
            "CODE128" => 'C128',
            "CODE128A" => 'C128A',
            "CODE128B" => 'C128B',
            "CODE128C" => 'C128C',
            "EAN13" => 'EAN13',
            "EAN8" => 'EAN8',
            "EAN5" => 'EAN5',
            "EAN2" => 'EAN2',
            "UPC" => 'UPCA',
            "UPCE" => 'UPCE',
            "ITF14" => 'I25',
            "pharmacode" => 'PHARMA',
            "QRcode" => 'QRCODE,H',
        ];

        if ($codeType === 'QRcode') {
            $function = 'write2DBarcode';

            $params = [
                $value,
                $typeMap[$codeType] ?? null, /** @phpstan-ignore-line */
                '', '',
                $data['width'] ?? 40,
                $data['height'] ?? 40,
                [
                    'border' => false,
                    'vpadding' => $data['padding'] ?? 2,
                    'hpadding' => $data['padding'] ?? 2,
                    'fgcolor' => $data['color'] ?? [0, 0, 0],
                    'bgcolor' => $data['bgcolor'] ?? false,
                    'module_width' => 1,
                    'module_height' => 1,
                ],
                'N',
            ];
        }
        else {
            $function = 'write1DBarcode';

            $params = [
                $value,
                $typeMap[$codeType] ?? null,
                '', '',
                $data['width'] ?? 60,
                $data['height'] ?? 30,
                0.4,
                [
                    'position' => 'S',
                    'border' => false,
                    'padding' => $data['padding'] ?? 0,
                    'fgcolor' => $data['color'] ?? [0, 0, 0],
                    'bgcolor' => $data['bgcolor'] ?? [255, 255, 255],
                    'text' => $data['text'] ?? true,
                    'font' => 'helvetica',
                    'fontsize' => $data['fontsize'] ?? 14,
                    'stretchtext' => 4,
                ],
                'N',
            ];
        }

        $paramsString = urlencode(json_encode($params));

        return "<tcpdf method=\"{$function}\" params=\"{$paramsString}\" />";
    }

    private function replacePlaceholders(string $string, Entity $entity): string
    {
        $newString = $string;

        $attributeList = ['name'];

        foreach ($attributeList as $attribute) {
            $value = (string) ($entity->get($attribute) ?? '');

            $newString = str_replace('{$' . $attribute . '}', $value, $newString);
        }

        return $newString;
    }
}
