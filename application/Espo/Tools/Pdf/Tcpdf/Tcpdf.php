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

use TCPDF as TcpdfOriginal;

use Espo\Core\Utils\Util;

use TCPDF_STATIC;

class Tcpdf extends TcpdfOriginal
{
    protected $footerHtml = '';

    protected $headerHtml = '';

    protected $footerPosition = 15;

    protected $headerPosition = 10;

    protected $useGroupNumbers = false;

    public function serializeTCPDFtagParameters($data)
    {
        return urlencode(json_encode($data));
    }

    protected function unserializeTCPDFtagParameters($data)
    {
        return json_decode(urldecode($data), true);
    }

    public function setUseGroupNumbers($value)
    {
        $this->useGroupNumbers = $value;
    }

    public function setHeaderHtml($html)
    {
        $this->headerHtml = $html;
    }

    public function setFooterHtml($html)
    {
        $this->footerHtml = $html;
    }

    public function setFooterPosition($position)
    {
        $this->footerPosition = $position;
    }

    public function setHeaderPosition($position)
    {
        $this->headerPosition = $position;
    }

    public function Header()
    {
        $this->SetY($this->headerPosition);

        $html = $this->headerHtml;

        if ($this->useGroupNumbers) {
            $html = str_replace('{pageNumber}', '{{:png:}}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{{:pnp:}}', $html);
        } else {
            $html = str_replace('{pageNumber}', '{{:pnp:}}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{{:pnp:}}', $html);
        }

        /** @phpstan-ignore-next-line */
        if ($this->isUnicodeFont()) {
            $html = str_replace('{totalPageNumber}', '{{:ptp:}}', $html);
        } else {
            $html = str_replace('{totalPageNumber}', '{:ptp:}', $html);
        }

        $this->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, '', 0, false);
    }

    public function Footer()
    {
        $breakMargin = $this->getBreakMargin();
        $autoPageBreak = $this->AutoPageBreak;

        $this->SetAutoPageBreak(false, 0);

        $this->SetY((-1) * $this->footerPosition);

        $html = $this->footerHtml;

        if ($this->useGroupNumbers) {
            $html = str_replace('{pageNumber}', '{{:png:}}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{{:pnp:}}', $html);
        } else {
            $html = str_replace('{pageNumber}', '{{:pnp:}}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{{:pnp:}}', $html);
        }

        /** @phpstan-ignore-next-line */
        if ($this->isUnicodeFont()) {
            $html = str_replace('{totalPageNumber}', '{{:ptp:}}', $html);
        } else {
            $html = str_replace('{totalPageNumber}', '{:ptp:}', $html);
        }

        $this->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, '', 0, false);

        $this->SetAutoPageBreak($autoPageBreak, $breakMargin);
    }

    public function Output($name = 'doc.pdf', $dest = 'I')
    {
        if ($dest === 'I' && !$this->sign && php_sapi_name() != 'cli') {
            if ($this->state < 3) {
                $this->Close();
            }
            $name = preg_replace('/[\s]+/', '_', $name);
            $name = Util::sanitizeFileName($name);

            if (ob_get_contents()) {
                $this->Error('Some data has already been output, can\'t send PDF file');
            }

            header('Content-Type: application/pdf');
            if (headers_sent()) {
                $this->Error('Some data has already been output to browser, can\'t send PDF file');
            }
            header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
            header('Pragma: public');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            header('Content-Disposition: inline; filename="'.$name.'"');
            TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);

            return '';
        }

        return parent::Output($name, $dest);
    }
}
