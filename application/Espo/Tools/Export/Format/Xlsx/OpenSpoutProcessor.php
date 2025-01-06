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
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Tools\Export\Collection;
use Espo\Tools\Export\Format\CellValuePreparator;
use Espo\Tools\Export\Format\CellValuePreparatorFactory;
use Espo\Tools\Export\Processor as ProcessorInterface;
use Espo\Tools\Export\Processor\Params;

use GuzzleHttp\Psr7\Stream;
use LogicException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class OpenSpoutProcessor implements ProcessorInterface
{
    private const FORMAT = 'xlsx';

    /** @var array<string, CellValuePreparator> */
    private array $preparatorsCache = [];
    /** @var array<string, string> */
    private array $typesCache = [];

    public function __construct(
        private FieldHelper $fieldHelper,
        private CellValuePreparatorFactory $cellValuePreparatorFactory,
        private Language $language,
        private DateTimeUtil $dateTime,
        private Config $config,
        private Metadata $metadata
    ) {}

    /**
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws InvalidArgumentException
     */
    public function process(Params $params, Collection $collection): StreamInterface
    {
        if (!$params->getFieldList()) {
            throw new LogicException("No field list.");
        }

        $filePath = tempnam(sys_get_temp_dir(), 'espo-export');

        if (!$filePath) {
            throw new RuntimeException("Could not create a temp file.");
        }

        $options = new Options();
        $options->setColumnWidthForRange(20, 1, count($params->getFieldList()));

        $writer = new Writer($options);

        $writer->openToFile($filePath);

        $sheetView = new SheetView();
        $sheetView->setFreezeRow(2);

        $writer->getCurrentSheet()->setSheetView($sheetView);

        $headerCells = [];

        foreach ($params->getFieldList() as $name) {
            $label = $this->translateLabel($params->getEntityType(), $name);

            $headerCells[] = Cell::fromValue($label, (new Style())->setFontBold());
        }

        $writer->addRow(new Row($headerCells));

        foreach ($collection as $entity) {
            $this->processRow($params, $entity, $writer);
        }

        $writer->close();

        $resource = fopen($filePath, 'r+');

        if ($resource === false) {
            throw new RuntimeException("Could not open temp.");
        }

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

    private function processRow(Params $params, Entity $entity, Writer $writer): void
    {
        $cells = [];

        foreach ($params->getFieldList() ?? [] as $name) {
            $cells[] = $this->prepareCell($params, $entity, $name);
        }

        $writer->addRow(new Row($cells));
    }

    private function prepareCell(Params $params, Entity $entity, mixed $name): Cell
    {
        $type = $this->getFieldType($params->getEntityType(), $name);

        $value = $this->getPreparator($type)
            ->prepare($entity, $name);

        if (is_string($value)) {
            $value = $this->sanitizeCellValue($value);

            return Cell\StringCell::fromValue($value);
        }

        if (is_int($value)) {
            return Cell\NumericCell::fromValue($value);
        }

        if (is_float($value)) {
            return Cell\NumericCell::fromValue($value);
        }

        if (is_bool($value)) {
            return Cell\BooleanCell::fromValue($value);
        }

        if ($value instanceof Date) {
            $dateFormat = self::convertDateFormat($this->dateTime->getDateFormat());

            $style = new Style();
            $style->setFormat($dateFormat);

            return Cell\DateTimeCell::fromValue($value->toDateTime(), $style);
        }

        if ($value instanceof DateTime) {
            $dateTimeFormat = self::convertDateFormat($this->dateTime->getDateTimeFormat());

            $style = new Style();
            $style->setFormat($dateTimeFormat);

            return Cell\DateTimeCell::fromValue($value->toDateTime(), $style);
        }

        if ($value instanceof Currency) {
            $format = $this->getCurrencyFormat($value->getCode());

            $style = new Style();
            $style->setFormat($format);

            return Cell\NumericCell::fromValue($value->getAmount(), $style);
        }

        return Cell::fromValue('');
    }

    private function getFieldType(string $entityType, string $name): string
    {
        $key = $entityType . '-' . $name;

        $type = $this->typesCache[$key] ?? null;

        if (!$type) {
            $fieldData = $this->fieldHelper->getData($entityType, $name);
            $type = $fieldData ? $fieldData->getType() : 'base';
            $this->typesCache[$key] = $type;
        }

        return $type;
    }

    private function getPreparator(string $type): CellValuePreparator
    {
        if (!array_key_exists($type, $this->preparatorsCache)) {
            $this->preparatorsCache[$type] = $this->cellValuePreparatorFactory->create(self::FORMAT, $type);
        }

        return $this->preparatorsCache[$type];
    }

    private static function convertDateFormat(string $format): string
    {
        $map = [
            'MM' => 'mm',
            'DD' => 'dd',
            'YYYY' => 'yyyy',
            'HH' => 'hh',
            'mm' => 'mm',
            'hh' => 'hh',
            'A' => 'AM/PM',
            'a' => 'AM/PM',
            'ss' => 'ss',
        ];

        return str_replace(
            array_keys($map),
            array_values($map),
            $format
        );
    }

    private function getCurrencyFormat(string $code): string
    {
        $currencySymbol = $this->metadata->get(['app', 'currency', 'symbolMap', $code], '');

        $currencyFormat = $this->config->get('currencyFormat') ?? 2;

        if ($currencyFormat === 3) {
            return '#,##0.00_-"' . $currencySymbol . '"';
        }

        return '[$' . $currencySymbol . '-409]#,##0.00;-[$' . $currencySymbol . '-409]#,##0.00';
    }

    private function sanitizeCellValue(string $value): string
    {
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
}
