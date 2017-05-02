<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class Xlsx extends \Espo\Core\Injectable
{
    protected $dependencyList = [
        'language',
        'metadata',
        'config'
    ];

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    public function process($entityType, $params, $dataList)
    {
        if (!is_array($params['fieldList'])) {
            throw new Error();
        }

        $phpExcel = new \PHPExcel();
        $sheet = $phpExcel->setActiveSheetIndex(0);

        if (isset($params['exportName'])) {
            $exportName = $params['exportName'];
        } else {
            $exportName = $this->getInjection('language')->translate($entityType, 'scopeNamesPlural');
        }

        $sheet->setTitle($exportName);

        $fieldList = $params['fieldList'];

        $titleStyle = array(
            'alignment' => array(
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'font' => array(
                'bold' => true,
                 'size' => 18
            )
        );
        $dateStyle = array(
            'alignment' => array(
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'font'  => array(
               'size' => 16
            )
        );

        $sheet->setCellValue('B1', $exportName);

        $sheet->setCellValue('C1', date('D M j G:i:s Y'));

        $sheet->getRowDimension('1')->setRowHeight(40);
        $sheet->getStyle('B1')->applyFromArray($titleStyle);
        $sheet->getStyle('C1')->applyFromArray($dateStyle);

        //\PHPExcel_Shared_Font::setTrueTypeFontPath('/usr/share/fonts/truetype/msttcorefonts/');
        //\PHPExcel_Shared_Font::setAutoSizeMethod(\PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

        $azRange = range('A', 'Z');

        $rowNumber = 2;
        $linkColumns = [];

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            $defs = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name]);

            if (!$defs) {
                $defs['type'] = 'base';
            }

            $label = $this->getInjection('language')->translate($name, 'fields', $entityType);

            $sheet->setCellValue($col . $rowNumber, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            if ($defs['type'] == 'phone'
                || $defs['type'] == 'email'
                || $defs['type'] == 'url'
                || $defs['type'] == 'link'
                || $defs['type'] == 'linkParent'
                || $defs['type'] == 'belongsTo'
                || $defs['type'] == 'belongsToParent') {
                $linkColumns[] = $col;
            } else if ($name == 'name') {
                if (in_array('id', $fieldList)) {
                    $linkColumns[] = $col;
                }
            }
        }
        $col = chr(ord($col) - 1);
        $headerStyle = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 12
            )
        );

        $sheet->getStyle("A$rowNumber:$col$rowNumber")->applyFromArray($headerStyle);
        $sheet->setAutoFilter("A$rowNumber:$col$rowNumber");

        $rowNumber++;
        foreach ($dataList as $row) {
            $i = 0;
            foreach ($fieldList as $i => $name) {
                $col = $azRange[$i];

                $defs = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name]);
                if (!$defs) {
                    $defs['type'] = 'base';
                }
                $link = null;
                if ($defs['type'] == 'link' || $defs['type'] == 'linkParent') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                } else if ($defs['type'] == 'belongsTo') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                } else if ($defs['type'] == 'belongsToParent') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                } else if ($defs['type'] == 'int') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name] ?: 0);
                } else if ($defs['type'] == 'personName') {
                    if (!empty($row['name'])) {
                        $sheet->setCellValue("$col$rowNumber", $row['name']);
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
                        $sheet->setCellValue("$col$rowNumber", $personName);
                    }
                } else if ($defs['type'] == 'date') {
                    if (isset($row[$name])) {
                        $sheet->setCellValue("$col$rowNumber", \PHPExcel_Shared_Date::PHPToExcel(strtotime($row[$name])));
                    }
                } else if ($defs['type'] == 'datetime' || $defs['type'] == 'datetimeOptional') {
                    if (isset($row[$name])) {
                        $sheet->setCellValue("$col$rowNumber", \PHPExcel_Shared_Date::PHPToExcel(strtotime($row[$name])));
                    }
                } else if ($defs['type'] == 'image') {

                } else if ($defs['type'] == 'file') {

                } else {
                    if (array_key_exists($name, $row)) {
                        $sheet->setCellValue("$col$rowNumber", $row[$name]);
                    }
                }

                $link = false;

                if ($name == 'name') {
                    if (array_key_exists('id', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/#".$entityType . "/view/" . $row['id'];
                    }
                } else if ($defs['type'] == 'url') {
                    if (array_key_exists($name, $row) && filter_var($row[$name], FILTER_VALIDATE_URL)) {
                        $link = $row[$name];
                    }
                } else if ($defs['type'] == 'link') {
                    if (array_key_exists($name.'Id', $row)) {
                        $foreignEntity = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $name, 'entity']);
                        if ($foreignEntity) {
                            $link = $this->getConfig()->getSiteUrl() . "/#" . $foreignEntity. "/view/". $row[$name.'Id'];
                        }
                    }
                } else if ($defs['type'] == 'linkParent') {
                    if (array_key_exists($name.'Id', $row) && array_key_exists($name.'Type', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/#".$row[$name.'Type']."/view/". $row[$name.'Id'];
                    }
                } else if ($defs['type'] == 'phone') {
                    if (array_key_exists($name, $row)) {
                        $link = "tel:".$row[$name];
                    }
                } else if ($defs['type'] == 'email' && array_key_exists($name, $row)) {
                    if (array_key_exists($name, $row)) {
                        $link = "mailto:".$row[$name];
                    }
                }
                if ($link) {
                    $sheet->getCell("$col$rowNumber")->getHyperlink()->setUrl($link);
                    $sheet->getCell("$col$rowNumber")->getHyperlink()->setTooltip($link);
                }
            }
            $rowNumber++;
        }

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            $defs = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name]);
            if (!$defs) {
                $defs['type'] = 'base';
            }

            if ($col == 'A') {
                $sheet->getStyle("A2:A$rowNumber")
                    ->getNumberFormat()
                    ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            } else {
                switch($defs['type']) {
                    case 'currency': {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('£#,##0_-');
                    } break;
                    case 'int': {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('0');
                    } break;
                    case 'date': {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('dd/mm/yyyy');
                    } break;
                    case 'datetime': {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('h:mm dd/mm/yyyy');
                    } break;
                    case 'datetimeOptional': {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('h:mm dd/mm/yyyy');
                    } break;
                    default: {
                        $sheet->getStyle($col.'3:'.$col.$rowNumber)
                            ->getNumberFormat()
                            ->setFormatCode('@');
                    } break;
                }
            }
        }

        $linkStyle = [
            'font'  => [
                'color' => ['rgb' => '0000FF'],
                'underline' => 'single'
            ]
        ];
        foreach ($linkColumns as $linkColumn) {
            $sheet->getStyle($linkColumn.'3:'.$linkColumn.$rowNumber)->applyFromArray($linkStyle);
        }

        $tempOutput = tempnam('/tmp/', 'ESPO');
        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
        $objWriter->save($tempOutput);
        $fp = fopen($tempOutput, 'r');
        $xlsx = stream_get_contents($fp);
        unlink($tempOutput);

        return $xlsx;

    }
}