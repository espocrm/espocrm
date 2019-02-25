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

namespace Espo\Core\Pdf;

require "vendor/tecnickcom/tcpdf/tcpdf.php";

use \TCPDF_STATIC;
use \TCPDF_FONTS;

class Tcpdf extends \TCPDF
{
    protected $footerHtml = '';

    protected $footerPosition = 15;

    protected $useGroupNumbers = false;

    public function setUseGroupNumbers($value)
    {
        $this->useGroupNumbers = $value;
    }

    public function setFooterHtml($html)
    {
        $this->footerHtml = $html;
    }

    public function setFooterPosition($position)
    {
        $this->footerPosition = $position;
    }

    public function Footer() {
        $breakMargin = $this->getBreakMargin();
        $autoPageBreak = $this->AutoPageBreak;

        $this->SetAutoPageBreak(false, 0);

        $this->SetY((-1) * $this->footerPosition);

        $html = $this->footerHtml;

        if ($this->useGroupNumbers) {
            $html = str_replace('{pageNumber}', '{:png:}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{:pnp:}', $html);
        } else {
            $html = str_replace('{pageNumber}', '{:pnp:}', $html);
            $html = str_replace('{pageAbsoluteNumber}', '{:pnp:}', $html);
        }

        $this->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, '', 0, false, 'T');

        $this->SetAutoPageBreak($autoPageBreak, $breakMargin);
    }

    protected function _putpages() {
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        // get internal aliases for page numbers
        $pnalias = $this->getAllInternalPageNumberAliases();
        $num_pages = $this->numpages;
        $ptpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $num_pages - 1));
        $ptpu = TCPDF_FONTS::UTF8ToUTF16BE($ptpa, false, $this->isunicode, $this->CurrentFont);
        $ptp_num_chars = $this->GetNumChars($ptpa);
        $pagegroupnum = 0;
        $groupnum = 0;
        $ptgu = 1;
        $ptga = 1;
        $ptg_num_chars = 1;
        for ($n = 1; $n <= $num_pages; ++$n) {
            // get current page
            $temppage = $this->getPageBuffer($n);
            $pagelen = strlen($temppage);
            // set replacements for total pages number
            $pnpa = TCPDF_STATIC::formatPageNumber(($this->starting_page_number + $n - 1));
            $pnpu = TCPDF_FONTS::UTF8ToUTF16BE($pnpa, false, $this->isunicode, $this->CurrentFont);
            $pnp_num_chars = $this->GetNumChars($pnpa);
            $pdiff = 0; // difference used for right shift alignment of page numbers
            $gdiff = 0; // difference used for right shift alignment of page group numbers
            if (!empty($this->pagegroups)) {
                if (isset($this->newpagegroup[$n])) {
                    $pagegroupnum = 0;
                    ++$groupnum;
                    $ptga = TCPDF_STATIC::formatPageNumber($this->pagegroups[$groupnum]);
                    $ptgu = TCPDF_FONTS::UTF8ToUTF16BE($ptga, false, $this->isunicode, $this->CurrentFont);
                    $ptg_num_chars = $this->GetNumChars($ptga);
                }
                ++$pagegroupnum;
                $pnga = TCPDF_STATIC::formatPageNumber($pagegroupnum);
                $pngu = TCPDF_FONTS::UTF8ToUTF16BE($pnga, false, $this->isunicode, $this->CurrentFont);

                $pnga = $pngu;

                $png_num_chars = $this->GetNumChars($pnga);
                // replace page numbers
                $replace = array();
                $replace[] = array($ptgu, $ptg_num_chars, 9, $pnalias[2]['u']);
                $replace[] = array($ptga, $ptg_num_chars, 7, $pnalias[2]['a']);
                $replace[] = array($pngu, $png_num_chars, 9, $pnalias[3]['u']);
                $replace[] = array($pnga, $png_num_chars, 7, $pnalias[3]['a']);
                list($temppage, $gdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $gdiff);
            }
            // replace page numbers
            $replace = array();
            $replace[] = array($ptpu, $ptp_num_chars, 9, $pnalias[0]['u']);
            $replace[] = array($ptpa, $ptp_num_chars, 7, $pnalias[0]['a']);
            $replace[] = array($pnpu, $pnp_num_chars, 9, $pnalias[1]['u']);

            $pnpa = $pnpu;

            $replace[] = array($pnpa, $pnp_num_chars, 7, $pnalias[1]['a']);
            list($temppage, $pdiff) = TCPDF_STATIC::replacePageNumAliases($temppage, $replace, $pdiff);
            // replace right shift alias
            $temppage = $this->replaceRightShiftPageNumAliases($temppage, $pnalias[4], max($pdiff, $gdiff));
            // replace EPS marker
            $temppage = str_replace($this->epsmarker, '', $temppage);
            //Page
            $this->page_obj_id[$n] = $this->_newobj();
            $out = '<<';
            $out .= ' /Type /Page';
            $out .= ' /Parent 1 0 R';
            if (empty($this->signature_data['approval']) OR ($this->signature_data['approval'] != 'A')) {
                $out .= ' /LastModified '.$this->_datestring(0, $this->doc_modification_timestamp);
            }
            $out .= ' /Resources 2 0 R';
            foreach ($this->page_boxes as $box) {
                $out .= ' /'.$box;
                $out .= sprintf(' [%F %F %F %F]', $this->pagedim[$n][$box]['llx'], $this->pagedim[$n][$box]['lly'], $this->pagedim[$n][$box]['urx'], $this->pagedim[$n][$box]['ury']);
            }
            if (isset($this->pagedim[$n]['BoxColorInfo']) AND !empty($this->pagedim[$n]['BoxColorInfo'])) {
                $out .= ' /BoxColorInfo <<';
                foreach ($this->page_boxes as $box) {
                    if (isset($this->pagedim[$n]['BoxColorInfo'][$box])) {
                        $out .= ' /'.$box.' <<';
                        if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['C'])) {
                            $color = $this->pagedim[$n]['BoxColorInfo'][$box]['C'];
                            $out .= ' /C [';
                            $out .= sprintf(' %F %F %F', ($color[0] / 255), ($color[1] / 255), ($color[2] / 255));
                            $out .= ' ]';
                        }
                        if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['W'])) {
                            $out .= ' /W '.($this->pagedim[$n]['BoxColorInfo'][$box]['W'] * $this->k);
                        }
                        if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['S'])) {
                            $out .= ' /S /'.$this->pagedim[$n]['BoxColorInfo'][$box]['S'];
                        }
                        if (isset($this->pagedim[$n]['BoxColorInfo'][$box]['D'])) {
                            $dashes = $this->pagedim[$n]['BoxColorInfo'][$box]['D'];
                            $out .= ' /D [';
                            foreach ($dashes as $dash) {
                                $out .= sprintf(' %F', ($dash * $this->k));
                            }
                            $out .= ' ]';
                        }
                        $out .= ' >>';
                    }
                }
                $out .= ' >>';
            }
            $out .= ' /Contents '.($this->n + 1).' 0 R';
            $out .= ' /Rotate '.$this->pagedim[$n]['Rotate'];
            if (!$this->pdfa_mode) {
                $out .= ' /Group << /Type /Group /S /Transparency /CS /DeviceRGB >>';
            }
            if (isset($this->pagedim[$n]['trans']) AND !empty($this->pagedim[$n]['trans'])) {
                // page transitions
                if (isset($this->pagedim[$n]['trans']['Dur'])) {
                    $out .= ' /Dur '.$this->pagedim[$n]['trans']['Dur'];
                }
                $out .= ' /Trans <<';
                $out .= ' /Type /Trans';
                if (isset($this->pagedim[$n]['trans']['S'])) {
                    $out .= ' /S /'.$this->pagedim[$n]['trans']['S'];
                }
                if (isset($this->pagedim[$n]['trans']['D'])) {
                    $out .= ' /D '.$this->pagedim[$n]['trans']['D'];
                }
                if (isset($this->pagedim[$n]['trans']['Dm'])) {
                    $out .= ' /Dm /'.$this->pagedim[$n]['trans']['Dm'];
                }
                if (isset($this->pagedim[$n]['trans']['M'])) {
                    $out .= ' /M /'.$this->pagedim[$n]['trans']['M'];
                }
                if (isset($this->pagedim[$n]['trans']['Di'])) {
                    $out .= ' /Di '.$this->pagedim[$n]['trans']['Di'];
                }
                if (isset($this->pagedim[$n]['trans']['SS'])) {
                    $out .= ' /SS '.$this->pagedim[$n]['trans']['SS'];
                }
                if (isset($this->pagedim[$n]['trans']['B'])) {
                    $out .= ' /B '.$this->pagedim[$n]['trans']['B'];
                }
                $out .= ' >>';
            }
            $out .= $this->_getannotsrefs($n);
            $out .= ' /PZ '.$this->pagedim[$n]['PZ'];
            $out .= ' >>';
            $out .= "\n".'endobj';
            $this->_out($out);
            //Page content
            $p = ($this->compress) ? gzcompress($temppage) : $temppage;
            $this->_newobj();
            $p = $this->_getrawstream($p);
            $this->_out('<<'.$filter.'/Length '.strlen($p).'>> stream'."\n".$p."\n".'endstream'."\n".'endobj');
        }
        //Pages root
        $out = $this->_getobj(1)."\n";
        $out .= '<< /Type /Pages /Kids [';
        foreach($this->page_obj_id as $page_obj) {
            $out .= ' '.$page_obj.' 0 R';
        }
        $out .= ' ] /Count '.$num_pages.' >>';
        $out .= "\n".'endobj';
        $this->_out($out);
    }

}
