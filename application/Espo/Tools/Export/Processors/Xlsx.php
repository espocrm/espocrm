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

namespace Espo\Tools\Export\Processors;

use Espo\ORM\Entity;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    Utils\DateTime as DateTimeUtil,
    FileStorage\Manager as FileStorageManager,
    ORM\EntityManager,
    Field\Address,
    Field\Address\AddressFormatterFactory,
};

use Espo\Tools\Export\{
    Processor\Params,
    Processor\Data,
    Processor,
};

use Psr\Http\Message\StreamInterface;

use GuzzleHttp\Psr7\Stream;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * @todo Refactor.
 */
class Xlsx implements Processor
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var DateTimeUtil
     */
    protected $dateTime;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var FileStorageManager
     */
    protected $fileStorageManager;

    /**
     * @var AddressFormatterFactory
     */
    protected $addressFormatterFactory;

    public function __construct(
        Config $config,
        Metadata $metadata,
        Language $language,
        DateTimeUtil $dateTime,
        EntityManager $entityManager,
        FileStorageManager $fileStorageManager,
        AddressFormatterFactory $addressFormatterFactory
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->language = $language;
        $this->dateTime = $dateTime;
        $this->entityManager = $entityManager;
        $this->fileStorageManager = $fileStorageManager;
        $this->addressFormatterFactory = $addressFormatterFactory;
    }

    public function process(Params $params, Data $data): StreamInterface
    {
        $entityType = $params->getEntityType();

        $fieldList = $params->getFieldList();

        if ($fieldList === null) {
            throw new RuntimeException("Field list is required");
        }

        $phpExcel = new Spreadsheet();

        $sheet = $phpExcel->setActiveSheetIndex(0);

        $sheetName = $this->getSheetNameFromParams($params);

        $exportName =
            $params->getName() ??
            $this->language->translate($entityType, 'scopeNamesPlural');

        $sheet->setTitle($sheetName);

        $titleStyle = [
            'font' => [
               'bold' => true,
               'size' => 12,
            ],
        ];

        $dateStyle = [
            'font'  => [
               'size' => 12,
            ],
        ];

        $now = new DateTime();

        $now->setTimezone(new DateTimeZone($this->config->get('timeZone', 'UTC')));

        $sheet->setCellValue('A1', $this->sanitizeCell($exportName));
        $sheet->setCellValue('B1', SharedDate::PHPToExcel(strtotime($now->format('Y-m-d H:i:s'))));

        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getStyle('B1')->applyFromArray($dateStyle);

        $sheet->getStyle('B1')->getNumberFormat()
                            ->setFormatCode($this->dateTime->getDateTimeFormat());

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

            $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $name]);

            if (!$defs) {
                $defs['type'] = 'base';
            }

            $label = $name;

            if (strpos($name, '_') !== false) {
                list($linkName, $foreignField) = explode('_', $name);

                $foreignScope = $this->metadata->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);

                if ($foreignScope) {
                    $label =
                        $this->language->translate($linkName, 'links', $entityType) . '.' .
                        $this->language->translate($foreignField, 'fields', $foreignScope);
                }
            }
            else {
                $label = $this->language->translate($name, 'fields', $entityType);
            }

            $sheet->setCellValue($col . $rowNumber, $this->sanitizeCell($label));

            $sheet->getColumnDimension($col)->setAutoSize(true);

            if (in_array($defs['type'], ['phone', 'email', 'url', 'link', 'linkParent'])) {
                $linkColList[] = $col;
            }
            else if ($name == 'name') {
                $linkColList[] = $col;
            }

            $lastIndex = $i;
        }

        $col = $azRange[$i];

        $headerStyle = [
            'font'  => [
                'bold'  => true,
                'size'  => 12,
            ]
        ];

        $sheet->getStyle("A$rowNumber:$col$rowNumber")->applyFromArray($headerStyle);
        $sheet->setAutoFilter("A$rowNumber:$col$rowNumber");

        $typesCache = [];

        $rowNumber++;

        $lineIndex = -1;

        while (true) {
            $lineIndex++;

            $row = $data->readRow();

            if ($row === null) {
                break;
            }

            $this->processRow(
                $entityType,
                $this->sanitizeRow($row),
                $sheet,
                $rowNumber,
                $fieldList,
                $azRange,
                $typesCache
            );

            $rowNumber++;
        }

        $sheet->getStyle("A2:A$rowNumber")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

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
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                } break;

                case 'date': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->dateTime->getDateFormat());
                } break;

                case 'datetime': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->dateTime->getDateTimeFormat());
                } break;

                case 'datetimeOptional': {
                    $sheet->getStyle($col.$startingRowNumber.':'.$col.$rowNumber)
                        ->getNumberFormat()
                        ->setFormatCode($this->dateTime->getDateTimeFormat());
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
                'underline' => 'single',
            ]
        ];

        foreach ($linkColList as $linkColumn) {
            $sheet
                ->getStyle($linkColumn.$startingRowNumber . ':' . $linkColumn.$rowNumber)
                ->applyFromArray($linkStyle);
        }

        $objWriter = IOFactory::createWriter($phpExcel, 'Xlsx');

        $resource = fopen('php://temp', 'r+');

        $objWriter->save($resource);

        $stream = new Stream($resource);

        $stream->seek(0);

        return $stream;
    }

    private function processRow(
        string $entityType,
        array $row,
        Worksheet $sheet,
        int $rowNumber,
        array $fieldList,
        array $azRange,
        array &$typesCache
    ): void {

        $i = 0;

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            $defs = $this->metadata->get(['entityDefs', $entityType, 'fields', $name]);

            if (!$defs) {
                $defs = [];
                $defs['type'] = 'base';
            }

            $type = $defs['type'];
            $foreignField = $name;
            $linkName = null;

            $foreignScope = null;

            if (strpos($name, '_') !== false) {
                list($linkName, $foreignField) = explode('_', $name);

                $foreignScope = $this->metadata
                    ->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);

                if ($foreignScope) {
                    $type = $this->metadata
                        ->get(['entityDefs', $foreignScope, 'fields', $foreignField, 'type'], $type);
                }
            }

            if ($type === 'foreign') {
                $linkName = $this->metadata
                    ->get(['entityDefs', $entityType, 'fields', $name, 'link']);

                $foreignField = $this->metadata
                    ->get(['entityDefs', $entityType, 'fields', $name, 'field']);

                $foreignScope = $this->metadata
                    ->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);

                if ($foreignScope) {
                    $type = $this->metadata
                        ->get(['entityDefs', $foreignScope, 'fields', $foreignField, 'type'], $type);
                }
            }

            $typesCache[$name] = $type;

            if ($type === 'link' || $type === 'linkOne') {
                if (array_key_exists($name.'Name', $row)) {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                }
            }
            else if ($type === 'linkParent') {
                if (array_key_exists($name.'Name', $row)) {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                }
            }
            else if ($type === 'int') {
                $sheet->setCellValue("$col$rowNumber", $row[$name] ?: 0);
            }
            else if ($type === 'float') {
                $sheet->setCellValue("$col$rowNumber", $row[$name] ?: 0);
            }
            else if ($type === 'currency') {
                if (array_key_exists($name.'Currency', $row) && array_key_exists($name, $row)) {
                    $sheet->setCellValue("$col$rowNumber", $row[$name] ? $row[$name] : '');

                    $currency = $row[$name . 'Currency'] ?? $this->config->get('defaultCurrency');

                    $sheet->getStyle("$col$rowNumber")
                        ->getNumberFormat()
                        ->setFormatCode(
                            $this->getCurrencyFormatCode($currency)
                        );
                }
            }
            else if ($type === 'currencyConverted') {
                if (array_key_exists($name, $row)) {
                    $currency = $this->config->get('defaultCurrency');

                    $sheet->getStyle("$col$rowNumber")
                        ->getNumberFormat()
                        ->setFormatCode(
                            $this->getCurrencyFormatCode($currency)
                        );

                    $sheet->setCellValue("$col$rowNumber", $row[$name] ? $row[$name] : '');
                }
            }
            else if ($type === 'personName') {
                if (!empty($row['name'])) {
                    $sheet->setCellValue("$col$rowNumber", $row['name']);
                }
                else {
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

                    $sheet->setCellValue($col . $rowNumber, $personName);
                }
            }
            else if ($type === 'date') {
                if (isset($row[$name])) {
                    $sheet->setCellValue(
                        $col . $rowNumber,
                        SharedDate::PHPToExcel(strtotime($row[$name]))
                    );
                }
            }
            else if ($type === 'datetime' || $type === 'datetimeOptional') {
                $value = null;

                if ($type === 'datetimeOptional') {
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
                        $timeZone = $this->config->get('timeZone');

                        $dt = new DateTime($value);

                        $dt->setTimezone(new DateTimeZone($timeZone));

                        $value = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
                    }
                    catch (Exception $e) {
                        $value = '';
                    }
                }

                if ($value) {
                    $sheet->setCellValue("$col$rowNumber", SharedDate::PHPToExcel(strtotime($value)));
                }
            }
            else if ($type === 'image') {
                if (isset($row[$name . 'Id']) && $row[$name . 'Id']) {
                    $attachment = $this->entityManager->getEntity('Attachment', $row[$name . 'Id']);

                    if ($attachment) {
                        $objDrawing = new Drawing();
                        $filePath = $this->fileStorageManager->getLocalFilePath($attachment);

                        if ($filePath && file_exists($filePath)) {
                            $objDrawing->setPath($filePath);
                            $objDrawing->setHeight(100);
                            $objDrawing->setCoordinates("$col$rowNumber");
                            $objDrawing->setWorksheet($sheet);

                            $sheet->getRowDimension($rowNumber)->setRowHeight(100);
                        }
                    }
                }

            }
            else if ($type === 'file') {
                if (array_key_exists($name.'Name', $row)) {
                    $sheet->setCellValue("$col$rowNumber", $row[$name.'Name']);
                }
            }
            else if ($type === 'enum') {
                if (array_key_exists($name, $row)) {
                    if ($linkName) {
                        $value = $this->language->translateOption($row[$name], $foreignField, $foreignScope);
                    }
                    else {
                        $value = $this->language->translateOption($row[$name], $name, $entityType);
                    }

                    $sheet->setCellValue("$col$rowNumber", $value);
                }
            }
            else if ($type === 'linkMultiple' || $type === 'attachmentMultiple') {
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
            }
            else if ($type === 'address') {
                $address = Address::createBuilder()
                    ->setStreet($row[$name . 'Street'] ?? null)
                    ->setCity($row[$name . 'City'] ?? null)
                    ->setState($row[$name . 'State'] ?? null)
                    ->setCountry($row[$name . 'Country'] ?? null)
                    ->setPostalCode($row[$name . 'PostalCode'] ?? null)
                    ->build();

                $formatter = $this->addressFormatterFactory->createDefault();

                $value = $formatter->format($address);

                $sheet->setCellValue("$col$rowNumber", $value);
            }
            else if ($type == 'duration') {
                if (!empty($row[$name])) {
                    $seconds = intval($row[$name]);

                    $days = intval(floor($seconds / 86400));
                    $seconds = $seconds - $days * 86400;
                    $hours = intval(floor($seconds / 3600));
                    $seconds = $seconds - $hours * 3600;
                    $minutes = intval(floor($seconds / 60));

                    $value = '';

                    if ($days) {
                        $value .= (string) $days . $this->language->translate('d', 'durationUnits');

                        if ($minutes || $hours) {
                            $value .= ' ';
                        }
                    }

                    if ($hours) {
                        $value .= (string) $hours . $this->language->translate('h', 'durationUnits');

                        if ($minutes) {
                            $value .= ' ';
                        }
                    }

                    if ($minutes) {
                        $value .= (string) $minutes . $this->language->translate('m', 'durationUnits');
                    }

                    $sheet->setCellValue("$col$rowNumber", $value);
                }
            }
            else if ($type === 'multiEnum' || $type === 'array') {
                if (!empty($row[$name])) {
                    $array = json_decode($row[$name]);

                    if (!is_array($array)) {
                        $array = [];
                    }

                    foreach ($array as $i => $item) {
                        if ($linkName) {
                            $itemValue = $this->language
                                ->translateOption($item, $foreignField, $foreignScope);
                        }
                        else {
                            $itemValue = $this->language
                                ->translateOption($item, $name, $entityType);
                        }
                        $array[$i] = $itemValue;
                    }

                    $value = implode(', ', $array);

                    $sheet->setCellValue("$col$rowNumber", $value);
                }
            }
            else {
                if (array_key_exists($name, $row)) {
                    $sheet->setCellValueExplicit("$col$rowNumber", $row[$name], DataType::TYPE_STRING);
                }
            }

            $link = null;

            $foreignLink = null;
            $isForeign = false;

            if (strpos($name, '_')) {
                $isForeign = true;

                list($foreignLink, $foreignField) = explode('_', $name);
            }

            if ($name === 'name') {
                if (array_key_exists('id', $row)) {
                    $link = $this->config->getSiteUrl() . "/#".$entityType . "/view/" . $row['id'];
                }
            }
            else if ($type === 'url') {
                if (array_key_exists($name, $row) && filter_var($row[$name], FILTER_VALIDATE_URL)) {
                    $link = $row[$name];
                }
            }
            else if ($type === 'link') {
                if (array_key_exists($name.'Id', $row)) {
                    $foreignEntity = null;

                    if (!$isForeign) {
                        $foreignEntity = $this->metadata->get(
                            ['entityDefs', $entityType, 'links', $name, 'entity']
                        );
                    }
                    else {
                        $foreignEntity1 = $this->metadata->get(
                            ['entityDefs', $entityType, 'links', $foreignLink, 'entity']
                        );

                        $foreignEntity = $this->metadata->get(
                            ['entityDefs', $foreignEntity1, 'links', $foreignField, 'entity']
                        );
                    }

                    if ($foreignEntity) {
                        $link =
                            $this->config->getSiteUrl() .
                            "/#" . $foreignEntity. "/view/". $row[$name.'Id'];
                    }
                }
            }
            else if ($type === 'file') {
                if (array_key_exists($name.'Id', $row)) {
                    $link = $this->config->getSiteUrl() . "/?entryPoint=download&id=" . $row[$name.'Id'];
                }
            }
            else if ($type === 'linkParent') {
                if (array_key_exists($name.'Id', $row) && array_key_exists($name.'Type', $row)) {
                    $link = $this->config->getSiteUrl() . "/#" . $row[$name.'Type']."/view/" . $row[$name.'Id'];
                }
            }
            else if ($type === 'phone') {
                if (array_key_exists($name, $row)) {
                    $link = "tel:" . $row[$name];
                }
            }

            else if ($type === 'email' && array_key_exists($name, $row)) {
                if (array_key_exists($name, $row)) {
                    $link = "mailto:" . $row[$name];
                }
            }

            if ($link) {
                $sheet->getCell("$col$rowNumber")->getHyperlink()->setUrl($link);
                $sheet->getCell("$col$rowNumber")->getHyperlink()->setTooltip($link);
            }
        }
    }

    private function getCurrencyFormatCode(string $currency): string
    {
        $currencySymbol = $this->metadata->get(['app', 'currency', 'symbolMap', $currency], '');

        $currencyFormat = $this->config->get('currencyFormat') ?? 2;

        if ($currencyFormat == 3) {
            return '#,##0.00_-"' . $currencySymbol . '"';
        }

        return '[$'.$currencySymbol.'-409]#,##0.00;-[$'.$currencySymbol.'-409]#,##0.00';
    }

    public function loadAdditionalFields(Entity $entity, array $fieldList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($entity->getRelationList() as $link) {
            if (!in_array($link, $fieldList)) {
                continue;
            }

            if ($entity->getRelationType($link) === 'belongsToParent') {
                if (!$entity->get($link . 'Name')) {
                    $entity->loadParentNameField($link);
                }
            }
            else if (
                (
                    (
                        $entity->getRelationType($link) === 'belongsTo' &&
                        $entity->getRelationParam($link, 'noJoin')
                    ) ||
                    $entity->getRelationType($link) === 'hasOne'
                ) &&
                $entity->hasAttribute($link . 'Name')
            ) {
                if (!$entity->get($link . 'Name') || !$entity->get($link . 'Id')) {
                    $entity->loadLinkField($link);
                }
            }
        }

        foreach ($fieldList as $field) {
            $fieldType = $this->metadata
                ->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

            if ($fieldType === 'linkMultiple' || $fieldType === 'attachmentMultiple') {
                if (!$entity->has($field . 'Ids')) {
                    $entity->loadLinkMultipleField($field);
                }
            }
        }
    }

    public function filterFieldList(string $entityType, array $fieldList, bool $exportAllFields): array
    {
        if ($exportAllFields) {
            foreach ($fieldList as $i => $field) {
                $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

                if (in_array($type, ['linkMultiple', 'attachmentMultiple'])) {
                    unset($fieldList[$i]);
                }
            }
        }

        return array_values($fieldList);
    }

    public function addAdditionalAttributes(string $entityType, array &$attributeList, array $fieldList): void
    {
        $linkList = [];

        if (!in_array('id', $attributeList)) {
            $attributeList[] = 'id';
        }

        $linkDefs = $this->metadata->get(['entityDefs', $entityType, 'links']);

        if (is_array($linkDefs)) {
            foreach ($linkDefs as $link => $defs) {
                if (empty($defs['type'])) {
                    continue;
                }

                if ($defs['type'] === 'belongsToParent') {
                    $linkList[] = $link;
                }
                else if ($defs['type'] === 'belongsTo' && !empty($defs['noJoin'])) {
                    if ($this->metadata->get(['entityDefs', $entityType, 'fields', $link])) {
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
            $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

            if ($type === 'currencyConverted') {
                if (!in_array($field, $attributeList)) {
                    $attributeList[] = $field;
                }
            }
        }
    }

    private function getSheetNameFromParams(Params $params): string
    {
        $exportName =
            $params->getName() ??
            $this->language->translate($params->getEntityType(), 'scopeNamesPlural');

        $badCharList = ['*', ':', '/', '\\', '?', '[', ']'];

        $sheetName = mb_substr($exportName, 0, 30, 'utf-8');

        $sheetName = str_replace($badCharList, ' ', $sheetName);

        $sheetName = str_replace('\'', '', $sheetName);

        return $sheetName;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeCell($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($value === '') {
            return $value;
        }

        if (in_array($value[0], ['+', '-', '@', '='])) {
            return "'" . $value;
        }

        return $value;
    }

    private function sanitizeRow(array $row): array
    {
        return array_map(
            function ($item) {
                return $this->sanitizeCell($item);
            },
            $row
        );
    }
}
