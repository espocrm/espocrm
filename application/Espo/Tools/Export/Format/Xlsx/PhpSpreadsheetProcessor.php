<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Export\Format\Xlsx;

use Espo\Core\Field\Currency;
use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime as DateTimeValue;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Entities\Attachment;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\Tools\Export\Collection;
use Espo\Tools\Export\Format\CellValuePreparator;
use Espo\Tools\Export\Format\CellValuePreparatorFactory;
use Espo\Tools\Export\Processor as ProcessorInterface;
use Espo\Tools\Export\Processor\Params;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

use DateTime;
use DateTimeZone;
use RuntimeException;

class PhpSpreadsheetProcessor implements ProcessorInterface
{
    private const FORMAT = 'xlsx';
    private const PARAM_RECORD_LINKS = 'recordLinks';
    private const PARAM_TITLE = 'title';

    /** @var array<string, CellValuePreparator> */
    private array $preparatorsCache = [];

    /** @var array<string, mixed> */
    private array $titleStyle = [
        'font' => [
            'bold' => true,
            'size' => 12,
        ]
    ];
    /** @var array<string, mixed> */
    private array $dateStyle = [
        'font'  => [
            'size' => 12,
        ]
    ];
    /** @var array<string, mixed> */
    private array $headerStyle = [
        'font' => [
            'bold' => true,
            'size' => 12,
        ]
    ];
    /** @var array<string, mixed> */
    private array $linkStyle = [
        'font' => [
            'color' => ['rgb' => '345b7c'],
            'underline' => 'single',
        ]
    ];

    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private Language $language,
        private DateTimeUtil $dateTime,
        private EntityManager $entityManager,
        private FileStorageManager $fileStorageManager,
        private FieldHelper $fieldHelper,
        private CellValuePreparatorFactory $cellValuePreparatorFactory,
        private ApplicationConfig $applicationConfig,
    ) {}

    /**
     * @throws SpreadsheetException
     * @throws WriterException
     */
    public function process(Params $params, Collection $collection): StreamInterface
    {
        $entityType = $params->getEntityType();
        $fieldList = $params->getFieldList();

        if ($fieldList === null) {
            throw new RuntimeException("Field list is required.");
        }

        $sheetName = $this->getSheetNameFromParams($params);
        $exportName = $params->getName() ??
            $this->language->translate($entityType, 'scopeNamesPlural');

        $phpExcel = new Spreadsheet();

        $headerRowNumber = $params->getParam(self::PARAM_TITLE) ? 3 : 1;

        $sheet = $phpExcel->setActiveSheetIndex(0)
            ->setTitle($sheetName)
            ->freezePane('A' . ($headerRowNumber + 1));

        $now = new DateTime();
        $now->setTimezone(new DateTimeZone($this->config->get('timeZone', 'UTC')));

        if ($params->getParam(self::PARAM_TITLE)) {
            $sheet
                ->setCellValue('A1', $this->sanitizeCellValue($exportName))
                ->setCellValue('A2',
                    SharedDate::PHPToExcel(strtotime($now->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT)))
                );

            $sheet->getStyle('A1')->applyFromArray($this->titleStyle);
            $sheet->getStyle('A2')->applyFromArray($this->dateStyle);
            $sheet->getStyle('A2')
                ->getNumberFormat()
                ->setFormatCode($this->dateTime->getDateTimeFormat());
        }

        $azRange = $this->getColumnsRange($fieldList);

        $rowNumber = $headerRowNumber;
        $linkColList = [];
        $lastIndex = 0;

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];
            $type = 'base';

            $label = $this->translateLabel($entityType, $name);

            $fieldData = $this->fieldHelper->getData($entityType, $name);

            if ($fieldData) {
                $type = $fieldData->getType();
            }

            $sheet->setCellValue($col . $rowNumber, $this->sanitizeCellValue($label));
            $sheet->getColumnDimension($col)->setAutoSize(true);

            $linkTypeList = $params->getParam(self::PARAM_RECORD_LINKS) ?
                [FieldType::URL, FieldType::PHONE, FieldType::EMAIL, FieldType::LINK, FieldType::LINK_PARENT] :
                ['url'];

            if (
                in_array($type, $linkTypeList) ||
                $params->getParam(self::PARAM_RECORD_LINKS) && $name === 'name'
            ) {
                $linkColList[] = $col;
            }

            $lastIndex = $i;
        }

        $col = $azRange[$lastIndex];

        $sheet->getStyle("A$rowNumber:$col$rowNumber")->applyFromArray($this->headerStyle);
        $sheet->setAutoFilter("A$rowNumber:$col$rowNumber");

        $typesCache = [];

        $rowNumber++;

        foreach ($collection as $entity) {
            $this->processRow(
                $entity,
                $sheet,
                $rowNumber,
                $fieldList,
                $azRange,
                $typesCache
            );

            $rowNumber++;
        }

        $sheet->getStyle("A$headerRowNumber:A$rowNumber")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_TEXT);

        $startingRowNumber = 2;

        if ($params->getParam(self::PARAM_TITLE)) {
            $startingRowNumber += 2;
        }

        foreach ($fieldList as $i => $name) {
            $col = $azRange[$i];

            if (!array_key_exists($name, $typesCache)) {
                break;
            }

            $type = $typesCache[$name];

            $coordinate = "$col$startingRowNumber:$col$rowNumber";

            switch ($type) {
                case FieldType::CURRENCY:
                case FieldType::CURRENCY_CONVERTED:
                    break;

                case FieldType::INT:
                    $sheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode('0');

                    break;

                case FieldType::FLOAT:
                    $sheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    break;

                case FieldType::DATE:
                    $sheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode($this->dateTime->getDateFormat());

                    break;

                case FieldType::DATETIME_OPTIONAL:
                case FieldType::DATETIME:
                    $sheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode($this->dateTime->getDateTimeFormat());

                    break;

                default:
                    $sheet->getStyle($coordinate)
                        ->getNumberFormat()
                        ->setFormatCode('@');

                    break;
            }
        }

        foreach ($linkColList as $linkColumn) {
            $sheet
                ->getStyle($linkColumn . $startingRowNumber . ':' . $linkColumn . $rowNumber)
                ->applyFromArray($this->linkStyle);
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

    private function translateLabel(string $entityType, string $name): string
    {
        $label = $name;

        $fieldData = $this->fieldHelper->getData($entityType, $name);
        $isForeignReference = $this->fieldHelper->isForeignReference($name);

        if ($isForeignReference && $fieldData && $fieldData->getLink()) {
            $label =
                $this->language->translateLabel($fieldData->getLink(), 'links', $entityType) . '.' .
                $this->language->translateLabel($fieldData->getField(), 'fields', $fieldData->getEntityType());
        }

        if (!$isForeignReference) {
            $label = $this->language->translateLabel($name, 'fields', $entityType);
        }

        return $label;
    }

    /**
     * @param string[] $fieldList
     * @return string[]
     */
    private function getColumnsRange(array $fieldList): array
    {
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

        return $azRange;
    }

    /**
     * @param string[] $fieldList
     * @param string[] $azRange
     * @param array<string, string> $typesCache
     * @throws SpreadsheetException
     */
    private function processRow(
        Entity $entity,
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
                $entity,
                $sheet,
                $rowNumber,
                $coordinate,
                $name,
                $typesCache
            );
        }
    }

    /**
     * @param array<string, string> $typesCache
     * @throws SpreadsheetException
     */
    private function processCell(
        Entity $entity,
        Worksheet $sheet,
        int $rowNumber,
        string $coordinate,
        string $name,
        array &$typesCache
    ): void {

        $entityType = $entity->getEntityType();

        $type = $typesCache[$name] ?? null;

        if (!$type) {
            $fieldData = $this->fieldHelper->getData($entityType, $name);
            $type = $fieldData ? $fieldData->getType() : 'base';
            $typesCache[$name] = $type;
        }

        $preparator = $this->getPreparator($type);

        $value = $preparator->prepare($entity, $name);

        if ($type === FieldType::IMAGE) {
            $this->applyImage(
                $entity,
                $coordinate,
                $sheet,
                $rowNumber,
                $name
            );

            $value = null;
        }

        $value = $this->sanitizeCellValue($value);

        if (is_string($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
        } else if (is_int($value) || is_float($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);
        }

        if (is_bool($value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_BOOL);
        } else if ($value instanceof Date) {
            $sheet->setCellValue(
                $coordinate,
                SharedDate::PHPToExcel(
                    strtotime($value->toString())
                )
            );
        } else if ($value instanceof DateTimeValue) {
            $sheet->setCellValue(
                $coordinate,
                SharedDate::PHPToExcel(
                    strtotime($value->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT))
                )
            );
        } else if ($value instanceof Currency) {
            $sheet->setCellValue($coordinate, $value->getAmount());

            $sheet->getStyle($coordinate)
                ->getNumberFormat()
                ->setFormatCode($this->getCurrencyFormatCode($value->getCode()));
        }

        $this->applyLinks(
            $type,
            $entity,
            $sheet,
            $coordinate,
            $name
        );
    }

    /**
     * @throws SpreadsheetException
     */
    private function applyImage(
        Entity $entity,
        string $coordinate,
        Worksheet $sheet,
        int $rowNumber,
        string $name
    ): void {

        $attachmentId = $entity->get($name . 'Id');

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
     * @throws SpreadsheetException
     */
    private function applyLinks(
        string $type,
        Entity $entity,
        Worksheet $sheet,
        string $coordinate,
        string $name
    ): void {

        $entityType = $entity->getEntityType();

        $link = null;

        $foreignLink = null;
        $foreignField = null;

        if (strpos($name, '_')) {
            [$foreignLink, $foreignField] = explode('_', $name);
        }

        $siteUrl = $this->applicationConfig->getSiteUrl();

        if ($name === 'name') {
            if ($entity->hasId()) {
                $link = "$siteUrl/#$entityType/view/{$entity->getId()}";
            }
        } else if ($type === FieldType::URL) {
            $value = $entity->get($name);

            if ($value) {
                $link = $this->sanitizeUrl($value);
            }
        } else if ($type === FieldType::LINK) {
            $idValue = $entity->get($name . 'Id');

            if ($idValue && $foreignField) {
                if (!$foreignLink) {
                    $foreignEntity =
                        $this->metadata->get(['entityDefs', $entityType, 'links', $name, RelationParam::ENTITY]);
                } else {
                    $foreignEntity1 = $this->metadata
                        ->get(['entityDefs', $entityType, 'links', $foreignLink, 'entity']);

                    $foreignEntity = $this->metadata
                        ->get(['entityDefs', $foreignEntity1, 'links', $foreignField, 'entity']);
                }

                if ($foreignEntity) {
                    $link = "$siteUrl/#$foreignEntity/view/$idValue";
                }
            }
        } else if ($type === FieldType::FILE) {
            $idValue = $entity->get($name . 'Id');

            if ($idValue) {
                $link = "$siteUrl/?entryPoint=download&id=$idValue";
            }
        } else if ($type === FieldType::LINK_PARENT) {
            $idValue = $entity->get($name . 'Id');
            $typeValue = $entity->get($name . 'Type');

            if ($idValue && $typeValue) {
                $link = "$siteUrl/#$typeValue/view/$idValue";
            }
        } else if ($type === FieldType::PHONE) {
            $value = $entity->get($name);

            if ($value) {
                $link = "tel:$value";
            }
        } else if ($type === FieldType::EMAIL) {
            $value = $entity->get($name);

            if ($value) {
                $link = "mailto:$value";
            }
        }

        if (!$link) {
            return;
        }

        $cell = $sheet->getCell($coordinate);

        $hyperLink = $cell->getHyperlink();

        $hyperLink->setUrl($link);
        $hyperLink->setTooltip($link);
    }

    private function getPreparator(string $type): CellValuePreparator
    {
        if (!array_key_exists($type, $this->preparatorsCache)) {
            $this->preparatorsCache[$type] = $this->cellValuePreparatorFactory->create(self::FORMAT, $type);
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

    private function getSheetNameFromParams(Params $params): string
    {
        $exportName =
            $params->getName() ??
            $this->language->translateLabel($params->getEntityType(), 'scopeNamesPlural');

        $badCharList = ['*', ':', '/', '\\', '?', '[', ']'];

        $sheetName = mb_substr($exportName, 0, 30, 'utf-8');
        $sheetName = str_replace($badCharList, ' ', $sheetName);

        return str_replace('\'', '', $sheetName);
    }

    private function sanitizeCellValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (in_array($value[0], ['+', '-', '@', '='])) {
            return "'" . $value;
        }

        return $value;
    }

    private function sanitizeUrl(string $value): ?string
    {
        $link = $value;

        if (!preg_match("/[a-z]+:\/\//", $link)) {
            $link = 'https://' . $link;
        }

        if (filter_var($link, FILTER_VALIDATE_URL)) {
            return $link;
        }

        return null;
    }
}
