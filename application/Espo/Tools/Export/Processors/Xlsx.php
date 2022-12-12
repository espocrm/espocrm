<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Field\Currency;
use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime as DateTimeValue;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Tools\Export\Processor;
use Espo\Tools\Export\Processor\Data;
use Espo\Tools\Export\Processor\Params;

use Espo\Tools\Export\Processors\Xlsx\CellValuePreparator;
use Espo\Tools\Export\Processors\Xlsx\CellValuePreparatorFactory;
use Espo\Tools\Export\Processors\Xlsx\FieldHelper;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
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
use RuntimeException;

class Xlsx implements Processor
{
    /** @var array<string, CellValuePreparator> */
    private array $preparatorsCache = [];

    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private Language $language,
        private DateTimeUtil $dateTime,
        private EntityManager $entityManager,
        private FileStorageManager $fileStorageManager,
        private FieldHelper $fieldHelper,
        private CellValuePreparatorFactory $cellValuePreparatorFactory
    ) {}

    /**
     * @throws SpreadsheetException
     * @throws WriterException
     */
    public function process(Params $params, Data $data): StreamInterface
    {
        $entityType = $params->getEntityType();

        $fieldList = $params->getFieldList();

        if ($fieldList === null) {
            throw new RuntimeException("Field list is required.");
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
        $sheet->setCellValue('B1',
            SharedDate::PHPToExcel(strtotime($now->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT)))
        );

        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getStyle('B1')->applyFromArray($dateStyle);

        $sheet->getStyle('B1')
            ->getNumberFormat()
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
                $defs = [
                    'type' => 'base',
                ];
            }

            $label = $name;

            if (str_contains($name, '_')) {
                list($linkName, $foreignField) = explode('_', $name);

                $foreignScope = $this->metadata->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);

                if ($foreignScope) {
                    $label =
                        $this->language->translateLabel($linkName, 'links', $entityType) . '.' .
                        $this->language->translateLabel($foreignField, 'fields', $foreignScope);
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

        $col = $azRange[$lastIndex];

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

        while (true) {
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

                case 'datetimeOptional':
                case 'datetime': {
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

        if ($resource === false) {
            throw new RuntimeException("Could not open temp.");
        }

        $objWriter->save($resource);

        $stream = new Stream($resource);

        $stream->seek(0);

        return $stream;
    }

    /**
     * @param array<string, mixed> $row
     * @param string[] $fieldList
     * @param string[] $azRange
     * @param array<string, string> $typesCache
     * @throws SpreadsheetException
     */
    private function processRow(
        string $entityType,
        array $row,
        Worksheet $sheet,
        int $rowNumber,
        array $fieldList,
        array $azRange,
        array &$typesCache
    ): void {

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            $coordinate = $col . $rowNumber;

            $this->processCell(
                $entityType,
                $row,
                $sheet,
                $rowNumber,
                $coordinate,
                $name,
                $typesCache
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $typesCache
     * @throws SpreadsheetException
     */
    private function processCell(
        string $entityType,
        array $row,
        Worksheet $sheet,
        int $rowNumber,
        string $coordinate,
        string $name,
        array &$typesCache
    ): void {

        $type = $typesCache[$name] ?? null;

        if (!$type) {
            $fieldData = $this->fieldHelper->getData($entityType, $name);

            $type = $fieldData ? $fieldData->getType() : 'base';

            $typesCache[$name] = $type;
        }

        $preparator = $this->getPreparator($type);

        $value = $preparator->prepare($entityType, $name, $row);

        if ($type === 'image') {
            $this->applyImage(
                $row,
                $coordinate,
                $sheet,
                $rowNumber,
                $name
            );

            $value = null;
        }

        if (is_string($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
        }
        else if (is_int($value) || is_float($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);
        }
        if (is_bool($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_BOOL);
        }
        else if ($value instanceof Date) {
            $sheet->setCellValue(
                $coordinate,
                SharedDate::PHPToExcel(
                    strtotime($value->getString())
                )
            );
        }
        else if ($value instanceof DateTimeValue) {
            $sheet->setCellValue(
                $coordinate,
                SharedDate::PHPToExcel(
                    strtotime($value->getDateTime()->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT))
                )
            );
        }
        else if ($value instanceof Currency) {
            $sheet->setCellValue($coordinate, $value->getAmount());

            $sheet->getStyle($coordinate)
                ->getNumberFormat()
                ->setFormatCode($this->getCurrencyFormatCode($value->getCode()));
        }

        $this->applyLinks(
            $type,
            $entityType,
            $row,
            $sheet,
            $coordinate,
            $name
        );
    }

    /**
     * @param array<string, mixed> $row
     * @throws SpreadsheetException
     */
    private function applyImage(
        array $row,
        string $coordinate,
        Worksheet $sheet,
        int $rowNumber,
        string $name
    ): void {
        $attachmentId = $row[$name . 'Id'] ?? null;

        if (!$attachmentId) {
            return;
        }

        /** @var ?Attachment $attachment */
        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $attachmentId);

        if (!$attachment) {
            return;
        }

        $objDrawing = new Drawing();
        $filePath = $this->fileStorageManager->getLocalFilePath($attachment);

        if (!$filePath || !file_exists($filePath)) {
            return;
        }

        $objDrawing->setPath($filePath);
        $objDrawing->setHeight(100);
        $objDrawing->setCoordinates($coordinate);
        $objDrawing->setWorksheet($sheet);

        $sheet->getRowDimension($rowNumber)->setRowHeight(100);
    }

    /**
     * @param array<string, mixed> $row
     * @throws SpreadsheetException
     */
    private function applyLinks(
        string $type,
        string $entityType,
        array $row,
        Worksheet $sheet,
        string $coordinate,
        string $name
    ): void {
        $link = null;

        $foreignLink = null;
        $foreignField = null;

        if (strpos($name, '_')) {
            list($foreignLink, $foreignField) = explode('_', $name);
        }

        $siteUrl = $this->config->getSiteUrl();

        if ($name === 'name') {
            if (array_key_exists('id', $row)) {
                $link = $siteUrl . "/#" . $entityType . "/view/" . $row['id'];
            }
        }
        else if ($type === 'url') {
            if (array_key_exists($name, $row) && filter_var($row[$name], FILTER_VALIDATE_URL)) {
                $link = $row[$name];
            }
        }
        else if ($type === 'link') {
            $idKey = $name . 'Id';

            if (array_key_exists($idKey, $row) && $foreignField) {
                if (!$foreignLink) {
                    $foreignEntity = $this->metadata->get(['entityDefs', $entityType, 'links', $name, 'entity']);
                }
                else {
                    $foreignEntity1 = $this->metadata
                        ->get(['entityDefs', $entityType, 'links', $foreignLink, 'entity']);

                    $foreignEntity = $this->metadata
                        ->get(['entityDefs', $foreignEntity1, 'links', $foreignField, 'entity']);
                }

                if ($foreignEntity) {
                    $link = $siteUrl . "/#" . $foreignEntity . "/view/" . $row[$idKey];
                }
            }
        }
        else if ($type === 'file') {
            $idKey = $name . 'Id';

            if (array_key_exists($idKey, $row)) {
                $link = $siteUrl . "/?entryPoint=download&id=" . $row[$idKey];
            }
        }
        else if ($type === 'linkParent') {
            $idKey = $name . 'Id';
            $typeKey = $name . 'Type';

            if (array_key_exists($idKey, $row) && array_key_exists($typeKey, $row)) {
                $link = $siteUrl . "/#" . $typeKey . "/view/" . $idKey;
            }
        }
        else if ($type === 'phone') {
            if (array_key_exists($name, $row)) {
                $link = "tel:" . $row[$name];
            }
        }

        else if ($type === 'email') {
            if (array_key_exists($name, $row)) {
                $link = "mailto:" . $row[$name];
            }
        }

        if (!$link) {
            return;
        }

        $cell = $sheet->getCell($coordinate);

        assert($cell !== null);

        $hyperLink = $cell->getHyperlink();

        $hyperLink->setUrl($link);
        $hyperLink->setTooltip($link);
    }

    private function getPreparator(string $type): CellValuePreparator
    {
        if (!array_key_exists($type, $this->preparatorsCache)) {
            $this->preparatorsCache[$type] = $this->cellValuePreparatorFactory->create($type);
        }

        return $this->preparatorsCache[$type];
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

    /**
     * @param string[] $fieldList
     */
    public function loadAdditionalFields(Entity $entity, array $fieldList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($entity->getRelationList() as $link) {
            if (!in_array($link, $fieldList)) {
                continue;
            }

            if ($entity->getRelationType($link) === Entity::BELONGS_TO_PARENT) {
                if (!$entity->get($link . 'Name')) {
                    $entity->loadParentNameField($link);
                }
            }
            else if (
                (
                    (
                        $entity->getRelationType($link) === Entity::BELONGS_TO &&
                        $entity->getRelationParam($link, 'noJoin')
                    ) ||
                    $entity->getRelationType($link) === Entity::HAS_ONE
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

    /**
     * @param string[] $fieldList
     * @return string[]
     */
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

    /**
     * @param string[] $attributeList
     * @param string[] $fieldList
     */
    public function addAdditionalAttributes(string $entityType, array &$attributeList, array $fieldList): void
    {
        $linkList = [];

        if (!in_array('id', $attributeList)) {
            $attributeList[] = 'id';
        }

        $linkDefs = $this->metadata->get(['entityDefs', $entityType, 'links']);

        if (is_array($linkDefs)) {
            foreach ($linkDefs as $link => $defs) {
                $linkType = $defs['type'] ?? null;

                if (!$linkType) {
                    continue;
                }

                if ($linkType === Entity::BELONGS_TO_PARENT) {
                    $linkList[] = $link;
                }
                else if ($linkType === Entity::BELONGS_TO && !empty($defs['noJoin'])) {
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
            $this->language->translateLabel($params->getEntityType(), 'scopeNamesPlural');

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

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
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
