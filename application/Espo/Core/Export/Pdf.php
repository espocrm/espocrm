<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Export;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Htmlizer\Htmlizer;
use \Espo\Core\ORM\Entity;

class Pdf extends \Espo\Core\Injectable
{
    protected $dependencyList = [
        'language',
        'metadata',
        'config',
        'dateTime',
        'entityManager',
        'fileStorageManager',
        'fileManager'
    ];

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    public function loadAdditionalFields(Entity $entity, $fieldList)
    {
        foreach ($entity->getRelationList() as $link) {
            if (in_array($link, $fieldList)) {
                if ($entity->getRelationType($link) === 'belongsToParent') {
                    if (!$entity->get($link . 'Name')) {
                        $entity->loadParentNameField($link);
                    }
                } else if (
                    (
                        (
                            $entity->getRelationType($link) === 'belongsTo'
                            &&
                            $entity->getRelationParam($link, 'noJoin')
                        )
                        ||
                        $entity->getRelationType($link) === 'hasOne'
                    )
                    &&
                    $entity->hasAttribute($link . 'Name')
                ) {
                    if (!$entity->get($link . 'Name') || !$entity->get($link . 'Id')) {
                        $entity->loadLinkField($link);
                    }
                }
            }
        }
        foreach ($fieldList as $field) {
            if ($this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']) === 'linkMultiple') {
                if (!$entity->has($field . 'Ids')) {
                    $entity->loadLinkMultipleField($field);
                }
            }
        }
    }

    public function filterFieldList($entityType, $fieldList, $exportAllFields)
    {
        if ($exportAllFields) {
            foreach ($fieldList as $i => $field) {
                $type = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field, 'type']);
                if (in_array($type, ['linkMultiple', 'attachmentMultiple'])) {
                    unset($fieldList[$i]);
                }
            }
        }

        return array_values($fieldList);
    }

    public function addAdditionalAttributes($entityType, &$attributeList, $fieldList)
    {
        $linkList = [];

        if (!in_array('id', $attributeList)) {
            $attributeList[] = 'id';
        }

        $linkDefs = $this->getMetadata()->get(['entityDefs', $entityType, 'links']);
        if (is_array($linkDefs)) {
            foreach ($linkDefs as $link => $defs) {
                if (empty($defs['type'])) continue;
                if ($defs['type'] === 'belongsToParent') {
                    $linkList[] = $link;
                } else if ($defs['type'] === 'belongsTo' && !empty($defs['noJoin'])) {
                    if ($this->getMetadata()->get(['entityDefs', $entityType, 'fields', $link])) {
                        $linkList[] = $link;
                    }
                }
            }
        }
        foreach ($linkList as $item) {
            if (in_array($item, $fieldList) && !in_array($item . 'Name', $attributeList)) {
                $attributeList[] = $item . 'Name';
            }
        }

        foreach ($fieldList as $field) {
            $type = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field, 'type']);
            if ($type === 'currencyConverted') {
                if (!in_array($field, $attributeList)) {
                    $attributeList[] = $field;
                }
            }
        }
    }

    public function process($entityType, $params, $dataList)
    {        
        $pdf = new \Espo\Core\Pdf\Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($entityType. ' PDF');

        $pdf->setPrintHeader(true);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $pdf->SetFont('dejavusans', '', 10, '', true);

        // Add a page        
        $pageSettings = $params['pdfDesign'];
        if(!$pageSettings) {
            $pdf->AddPage('P', 'A4');
        }else {
            $pdf->AddPage('L', 'A4');
        }

        // set JPEG quality
        $pdf->setJPEGQuality(75);          

        $fieldList = $params['fieldList'];
                
        // Set some content to print
        $html = '<table border="0.5pt" cellpadding="2" class="table table-bordered">';

        $html.= '<tr>';
        foreach ($fieldList as $i => $name) {
            $value =  $this->getInjection('language')->translate($name, 'fields', $entityType);
            $html.= '<th align="left"><b>'.$value.'</b></th>';
        }
        $html .= '</tr>';

        foreach ($dataList as $row) {
            $html .= '<tr>';
            $i = 0;
            foreach ($fieldList as $i => $name) {
                $defs = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name]);
                if (!$defs) {
                    $defs = array();
                    $defs['type'] = 'base';
                }

                $type = $defs['type'];
                $foreignField = $name;
                $linkName = null;
                if (strpos($name, '_') !== false) {
                    list($linkName, $foreignField) = explode('_', $name);
                    $foreignScope = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);
                    if ($foreignScope) {
                        $type = $this->getInjection('metadata')->get(['entityDefs', $foreignScope, 'fields', $foreignField, 'type'], $type);
                    }
                }
                if ($type === 'foreign') {
                    $linkName = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name, 'link']);
                    $foreignField = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name, 'field']);
                    $foreignScope = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);
                    if ($foreignScope) {
                        $type = $this->getInjection('metadata')->get(['entityDefs', $foreignScope, 'fields', $foreignField, 'type'], $type);
                    }
                }                
                
                if ($type == 'link') {
                    if (array_key_exists($name.'Name', $row)) {                        
                        $html .= '<td>'.$row[$name.'Name'].'</td>';
                    }
                } else if ($type == 'linkParent') {
                    if (array_key_exists($name.'Name', $row)) {                                                
                        $html .= '<td>'.$row[$name.'Name'].'</td>';
                    }
                } else if ($type == 'int') {
                    $value = $row[$name] ?: 0;
                    $html .= '<td>'.$value.'</td>';
                } else if ($type == 'float') {
                    $value = $row[$name] ?: 0;
                    $html .= '<td>'.$value.'</td>';
                } else if ($type == 'currency') {
                    if (array_key_exists($name.'Currency', $row) && array_key_exists($name, $row)) {
                        $currencyAmount = number_format((float)$row[$name], 2, ',', '');
                        $currency = $row[$name . 'Currency'];                        
                        $html .= '<td>'.$currencyAmount.' '.$currency.'</td>';
                    }
                } else if ($type == 'currencyConverted') {
                    if (array_key_exists($name, $row)) {
                        $currency = $this->getConfig()->get('defaultCurrency');
                        $html .= '<td>'. number_format((float)$row[$name], 2, ',', '').' '.$currency.'</td>';
                    }
                } else if ($type == 'personName') {
                    if (!empty($row['name'])) {
                        $html .= '<td>'.$row['name'].'</td>';
                    } else {
                        $personName = '';
                        if (!empty($row['firstName'])) {
                            $personName .= $row['firstName'];
                        }
                        if (!empty($row['lastName'])) {
                            if (!empty($row['firstName'])) {
                                $personName .= ' ';
                            }
                            $personName .= $row['lastName'];
                        }                        
                        $html .= '<td>'.$personName.'</td>';
                    }
                } else if ($type == 'date') {
                    if (isset($row[$name])) {                        
                        $html .= '<td>'.$row[$name].'</td>';
                    }
                } else if ($type == 'datetime' || $type == 'datetimeOptional') {
                    $value = null;
                    if ($type == 'datetimeOptional') {
                        if (isset($row[$name . 'Date']) && $row[$name . 'Date']) {
                            $value = $row[$name . 'Date'];
                        }
                    }
                    if (!$value) {
                        if (isset($row[$name])) {
                            $value = $row[$name];
                        }
                    }
                    if ($value && strlen($value) > 11) {
                        try {
                            $timeZone = $this->getInjection('config')->get('timeZone');
                            $dt = new \DateTime($value);
                            $dt->setTimezone(new \DateTimeZone($timeZone));
                            $value = $dt->format($this->getInjection('dateTime')->getInternalDateTimeFormat());
                        } catch (\Exception $e) {
                            $value = '';
                        }
                    }
                    if ($value) {                                                
                        $html .= '<td>'.date('d.m.Y H:i', strtotime($value)).'</td>';
                    }
                } else if ($type == 'image') {
                    if (isset($row[$name . 'Id']) && $row[$name . 'Id']) {
                        $attachment = $this->getEntityManager()->getEntity('Attachment', $row[$name . 'Id']);

                        if ($attachment) {
                            $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                            $filePath = $this->getInjection('fileStorageManager')->getLocalFilePath($attachment);                            

                            if ($filePath && file_exists($filePath)) {
                                // Image example with resizing                                                                
                                $html .= '<td> <img src="'.$filePath.'" width="85"> </td>';
                            }
                        }
                    }

                } else if ($type == 'file') {
                    if (array_key_exists($name.'Name', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/?entryPoint=download&id=" . $row[$name.'Id'];                        
                        $html .= '<td> <a href=" '.$link.' " download> '.$row[$name.'Name'].' </a> </td>';
                    }
                } else if ($type == 'enum') {
                    if (array_key_exists($name, $row)) {
                        if ($linkName) {
                            $value = $this->getInjection('language')->translateOption($row[$name], $foreignField, $foreignScope);
                        } else {
                            $value = $this->getInjection('language')->translateOption($row[$name], $name, $entityType);
                        }
                        $html .= '<td>'.$value.'</td>';
                    }
                } else if ($type == 'linkMultiple') {
                    if (array_key_exists($name . 'Ids', $row) && array_key_exists($name . 'Names', $row)) {
                        $nameList = [];
                        foreach ($row[$name . 'Ids'] as $relatedId) {
                            $relatedName = $relatedId;
                            if (property_exists($row[$name . 'Names'], $relatedId)) {
                                $relatedName = $row[$name . 'Names']->$relatedId;
                            }
                            $nameList[] = $relatedName;
                        }                        
                        $html .= '<td>'.$nameList.'</td>';
                    }
                } else if ($type == 'address') {
                    $value = '';
                    if (!empty($row[$name . 'Street'])) {
                        $value = $value .= $row[$name.'Street'];
                    }
                    if (!empty($row[$name.'City']) || !empty($row[$name.'State']) || !empty($row[$name.'PostalCode'])) {
                        if ($value) {
                            $value .= "\n";
                        }
                        if (!empty($row[$name.'City'])) {
                            $value .= $row[$name.'City'];
                            if (
                                !empty($row[$name.'State']) || !empty($row[$name.'PostalCode'])
                            ) {
                                $value .= ', ';
                            }
                        }
                        if (!empty($row[$name.'State'])) {
                            $value .= $row[$name.'State'];
                            if (!empty($row[$name.'PostalCode'])) {
                                $value .= ' ';
                            }
                        }
                        if (!empty($row[$name.'PostalCode'])) {
                            $value .= $row[$name.'PostalCode'];
                        }
                    }
                    if (!empty($row[$name.'Country'])) {
                        if ($value) {
                            $value .= "\n";
                        }
                        $value .= $row[$name.'Country'];
                    }                    
                    $html .= '<td>'.$value.'</td>';
                } else if ($type == 'duration') {
                    if (!empty($row[$name])) {
                        $seconds = intval($row[$name]);

                        $days = intval(floor($seconds / 86400));
                        $seconds = $seconds - $days * 86400;
                        $hours = intval(floor($seconds / 3600));
                        $seconds = $seconds - $hours * 3600;
                        $minutes = intval(floor($seconds / 60));

                        $value = '';
                        if ($days) {
                            $value .= (string) $days . $this->getInjection('language')->translate('d', 'durationUnits');
                            if ($minutes || $hours) {
                                $value .= ' ';
                            }
                        }
                        if ($hours) {
                            $value .= (string) $hours . $this->getInjection('language')->translate('h', 'durationUnits');
                            if ($minutes) {
                                $value .= ' ';
                            }
                        }
                        if ($minutes) {
                            $value .= (string) $minutes . $this->getInjection('language')->translate('m', 'durationUnits');
                        }
                        
                        $html .= '<td>'.$value.'</td>';
                    }

                } else {
                    if (array_key_exists($name, $row)) {
                        $html .= '<td>'.$row[$name].'</td>';
                    }
                }               
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);        

        // Close and output PDF document
        return $pdf->Output('', 'S');
    }
}
