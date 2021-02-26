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

namespace Espo\Core\Pdf;

define('K_TCPDF_EXTERNAL_CONFIG', true);

define('K_TCPDF_CALLS_IN_HTML', true);

define('K_BLANK_IMAGE', '_blank.png');
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_CREATOR', 'TCPDF');
define('PDF_AUTHOR', 'TCPDF');
define('PDF_UNIT', 'mm');
define('PDF_MARGIN_HEADER', 5);
define('PDF_MARGIN_FOOTER', 10);
define('PDF_MARGIN_TOP', 27);
define('PDF_MARGIN_BOTTOM', 25);
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_FONT_NAME_MAIN', 'helvetica');
define('PDF_FONT_SIZE_MAIN', 10);
define('PDF_FONT_NAME_DATA', 'helvetica');
define('PDF_FONT_SIZE_DATA', 8);
define('PDF_FONT_MONOSPACED', 'courier');
define('PDF_IMAGE_SCALE_RATIO', 1.25);
define('HEAD_MAGNIFICATION', 1.1);
define('K_CELL_HEIGHT_RATIO', 1.25);
define('K_TITLE_MAGNIFICATION', 1.3);
define('K_SMALL_RATIO', 2/3);
define('K_THAI_TOPCHARS', true);
define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
define('K_TIMEZONE', 'UTC');

require "vendor/tecnickcom/tcpdf/tcpdf.php";

use \TCPDF_STATIC;
use \TCPDF_FONTS;

use Espo\Core\Utils\Util;

class Tcpdf extends \TCPDF
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

        if ($this->isUnicodeFont()) {
            $html = str_replace('{totalPageNumber}', '{{:ptp:}}', $html);
        } else {
            $html = str_replace('{totalPageNumber}', '{:ptp:}', $html);
        }

        $this->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, '', 0, false, 'T');
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

        if ($this->isUnicodeFont()) {
            $html = str_replace('{totalPageNumber}', '{{:ptp:}}', $html);
        } else {
            $html = str_replace('{totalPageNumber}', '{:ptp:}', $html);
        }

        $this->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, '', 0, false, 'T');

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
