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

namespace Espo\Core\Export;

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class Xlsx extends \Espo\Core\Injectable
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

    public function process(string $entityType, array $params, ?array $dataList = null, $dataFp = null)
    {
        if (!is_array($params['fieldList'])) {
            throw new Error();
        }

        $phpExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $phpExcel->setActiveSheetIndex(0);

        if (isset($params['exportName'])) {
            $exportName = $params['exportName'];
        } else {
            $exportName = $this->getInjection('language')->translate($entityType, 'scopeNamesPlural');
        }

        $sheetName = mb_substr($exportName, 0, 30, 'utf-8');
        $badCharList = ['*', ':', '/', '\\', '?', '[', ']'];
        foreach ($badCharList as $badChar) {
            $sheetName = str_replace($badCharList, ' ', $sheetName);
        }
        $sheetName = str_replace('\'', '', $sheetName);

        $sheet->setTitle($sheetName);

        $fieldList = $params['fieldList'];

        $titleStyle = array(
            'font' => array(
               'bold' => true,
               'size' => 12
            )
        );
        $dateStyle = array(
            'font'  => array(
               'size' => 12
            )
        );

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($this->getInjection('config')->get('timeZone', 'UTC')));

        $sheet->setCellValue('A1', $exportName);
        $sheet->setCellValue('B1', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($now->format('Y-m-d H:i:s'))));

        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getStyle('B1')->applyFromArray($dateStyle);

        $sheet->getStyle('B1')->getNumberFormat()
                            ->setFormatCode($this->getInjection('dateTime')->getDateTimeFormat());

        $azRange = range('A', 'Z');
        $azRangeCopied = $azRange;
        foreach ($azRangeCopied as $i => $char1) {
            foreach ($azRangeCopied as $j => $char2) {
                $azRange[] = $char1 . $char2;
                if ($i * count($azRangeCopied) + $j === count($fieldList)) {
                    break 2;
                }
            }
        }

        $rowNumber = 3;

        $linkColList = [];

        $lastIndex = 0;

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            $defs = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'fields', $name]);

            if (!$defs) {
                $defs['type'] = 'base';
            }

            $label = $name;
            if (strpos($name, '_') !== false) {
                list($linkName, $foreignField) = explode('_', $name);
                $foreignScope = $this->getInjection('metadata')->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);
                if ($foreignScope) {
                    $label = $this->getInjection('language')->translate($linkName, 'links', $entityType) . '.' . $this->getInjection('language')->translate($foreignField, 'fields', $foreignScope);
                }
            } else {
                $label = $this->getInjection('language')->translate($name, 'fields', $entityType);
            }

            $sheet->setCellValue($col . $rowNumber, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            if (in_array($defs['type'], ['phone', 'email', 'url', 'link', 'linkParent'])) {
                $linkColList[] = $col;
            } else if ($name == 'name') {
                $linkColList[] = $col;
            }
            $lastIndex = $i;
        }

        $col = $azRange[$i];

        $headerStyle = array(
            'font'  => array(
                'bold'  => true,
                'size'  => 12
            )
        );

        $sheet->getStyle("A$rowNumber:$col$rowNumber")->applyFromArray($headerStyle);
        $sheet->setAutoFilter("A$rowNumber:$col$rowNumber");

        $typesCache = array();

        $rowNumber++;

        $lineIndex = -1;
        if ($dataList) {
            $lineCount = count($dataList);
        }

        while (true) {
            $lineIndex++;

            if ($dataFp) {
                $line = fgets($dataFp);
                if ($line === false) break;
                $row = unserialize(base64_decode($line));
            } else {
                if ($lineIndex >= $lineCount) break;
                $row = $dataList[$lineIndex];
            }

            $i = 0;
            foreach ($fieldList as $i => $name) {
                $col = $azRange[$i];

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
                $typesCache[$name] = $type;

                $link = null;
                if ($type == 'link') {
                    if (array_key_exists($name.'Name', $row)) {
                        $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                    }
                } else if ($type == 'linkParent') {
                    if (array_key_exists($name.'Name', $row)) {
                        $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                    }
                } else if ($type == 'int') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name] ?: 0);
                } else if ($type == 'float') {
                    $sheet->setCellValue("$col$rowNumber", $row[$name] ?: 0);
                } else if ($type == 'currency') {
                    if (array_key_exists($name.'Currency', $row) && array_key_exists($name, $row)) {
                        $sheet->setCellValue("$col$rowNumber", $row[$name] ? $row[$name] : '');
                        $currency = $row[$name . 'Currency'];
                        $currencySymbol = $this->getMetadata()->get(['app', 'currency', 'symbolMap', $currency], '');

                        $sheet->getStyle("$col$rowNumber")
                            ->getNumberFormat()
                            ->setFormatCode('[$'.$currencySymbol.'-409]#,##0.00;-[$'.$currencySymbol.'-409]#,##0.00');
                    }
                } else if ($type == 'currencyConverted') {
                    if (array_key_exists($name, $row)) {
                        $currency = $this->getConfig()->get('defaultCurrency');
                        $currencySymbol = $this->getMetadata()->get(['app', 'currency', 'symbolMap', $currency], '');

                        $sheet->getStyle("$col$rowNumber")
                            ->getNumberFormat()
                            ->setFormatCode('[$'.$currencySymbol.'-409]#,##0.00;-[$'.$currencySymbol.'-409]#,##0.00');

                        $sheet->setCellValue("$col$rowNumber", $row[$name] ? $row[$name] : '');
                    }
                } else if ($type == 'personName') {
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
                } else if ($type == 'date') {
                    if (isset($row[$name])) {
                        $sheet->setCellValue("$col$rowNumber", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($row[$name])));
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
                        $sheet->setCellValue("$col$rowNumber", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(strtotime($value)));
                    }
                } else if ($type == 'image') {
                    if (isset($row[$name . 'Id']) && $row[$name . 'Id']) {
                        $attachment = $this->getEntityManager()->getEntity('Attachment', $row[$name . 'Id']);

                        if ($attachment) {
                            $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                            $filePath = $this->getInjection('fileStorageManager')->getLocalFilePath($attachment);

                            if ($filePath && file_exists($filePath)) {
                                $objDrawing->setPath($filePath);
                                $objDrawing->setHeight(100);
                                $objDrawing->setCoordinates("$col$rowNumber");
                                $objDrawing->setWorksheet($sheet);
                                $sheet->getRowDimension($rowNumber)->setRowHeight(100);
                            }
                        }
                    }

                } else if ($type == 'file') {
                    if (array_key_exists($name.'Name', $row)) {
                        $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                    }
                } else if ($type == 'enum') {
                    if (array_key_exists($name, $row)) {
                        if ($linkName) {
                            $value = $this->getInjection('language')->translateOption($row[$name], $foreignField, $foreignScope);
                        } else {
                            $value = $this->getInjection('language')->translateOption($row[$name], $name, $entityType);
                        }
                        $sheet->setCellValue("$col$rowNumber", $value);
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
                        $sheet->setCellValue("$col$rowNumber", implode(', ', $nameList));
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
                    $sheet->setCellValue("$col$rowNumber", $value);
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

                        $sheet->setCellValue("$col$rowNumber", $value);
                    }
                } else if ($type == 'multiEnum' || $type == 'array') {
                    if (!empty($row[$name])) {
                        $array = json_decode($row[$name]);
                        if (is_array($array)) {
                            $value = implode(', ', $array);
                            $sheet->setCellValue("$col$rowNumber", $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    }
                } else {
                    if (array_key_exists($name, $row)) {
                        $sheet->setCellValueExplicit("$col$rowNumber", $row[$name], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }

                $link = false;

                $foreignLink = null;
                $isForeign = false;
                if (strpos($name, '_')) {
                    $isForeign = true;
                    list($foreignLink, $foreignField) = explode('_', $name);
                }

                if ($name == 'name') {
                    if (array_key_exists('id', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/#".$entityType . "/view/" . $row['id'];
                    }
                } else if ($type == 'url') {
                    if (array_key_exists($name, $row) && filter_var($row[$name], FILTER_VALIDATE_URL)) {
                        $link = $row[$name];
                    }
                } else if ($type == 'link') {
                    if (array_key_exists($name.'Id', $row)) {
                        $foreignEntity = null;
                        if (!$isForeign) {
                            $foreignEntity = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $name, 'entity']);
                        } else {
                            $foreignEntity1 = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $foreignLink, 'entity']);
                            $foreignEntity = $this->getMetadata()->get(['entityDefs', $foreignEntity1, 'links', $foreignField, 'entity']);
                        }
                        if ($foreignEntity) {
                            $link = $this->getConfig()->getSiteUrl() . "/#" . $foreignEntity. "/view/". $row[$name.'Id'];
                        }
                    }
                } else if ($type == 'file') {
                    if (array_key_exists($name.'Id', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/?entryPoint=download&id=" . $row[$name.'Id'];
                    }
                } else if ($type == 'linkParent') {
                    if (array_key_exists($name.'Id', $row) && array_key_exists($name.'Type', $row)) {
                        $link = $this->getConfig()->getSiteUrl() . "/#".$row[$name.'Type']."/view/". $row[$name.'Id'];
                    }
                } else if ($type == 'phone') {
                    if (array_key_exists($name, $row)) {
                        $link = "tel:".$row[$name];
                    }
                } else if ($type == 'email' && array_key_exists($name, $row)) {
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

        $sheet->getStyle("A2:A$rowNumber")
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $startingRowNumber = 4;

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];
            if (!array_key_exists($name, $typesCache)) {
                break;
            }
            $type = $typesCache[$name];

            switch ($type) {
                case 'currency':
                case 'currencyConverted': {

                } break;
                case 'int': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode('0');
                } break;
                case 'float': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                } break;
                case 'date': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->getInjection('dateTime')->getDateFormat());
                } break;
                case 'datetime': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->getInjection('dateTime')->getDateTimeFormat());
                } break;
                case 'datetimeOptional': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->getInjection('dateTime')->getDateTimeFormat());
                } break;
                default: {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode('@');
                } break;
            }
        }

        $linkStyle = [
            'font'  => [
                'color' => ['rgb' => '345b7c'],
                'underline' => 'single'
            ]
        ];
        foreach ($linkColList as $linkColumn) {
            $sheet->getStyle($linkColumn.$startingRowNumber.':'.$linkColumn.$rowNumber)->applyFromArray($linkStyle);
        }

        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpExcel, 'Xlsx');

        if (!$this->getInjection('fileManager')->isDir('data/cache/')) {
            $this->getInjection('fileManager')->mkdir('data/cache/');
        }
        $tempFileName = 'data/cache/' . 'export_' . substr(md5(rand()), 0, 7);

        $objWriter->save($tempFileName);
        $fp = fopen($tempFileName, 'r');
        $xlsx = stream_get_contents($fp);
        $this->getInjection('fileManager')->unlink($tempFileName);

        return $xlsx;
    }
}
