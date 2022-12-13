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

use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Tools\Export\Processor;
use Espo\Tools\Export\Processor\Data;
use Espo\Tools\Export\Processor\Params;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

use RuntimeException;

class Csv implements Processor
{
    public function __construct(
        private Config $config,
        private Preferences $preferences,
        private Metadata $metadata
    ) {}

    public function process(Params $params, Data $data): StreamInterface
    {
        $attributeList = $params->getAttributeList();

        $delimiterRaw =
            $this->preferences->get('exportDelimiter') ??
            $this->config->get('exportDelimiter') ??
            ',';

        $delimiter = str_replace('\t', "\t", $delimiterRaw);

        $fp = fopen('php://temp', 'w');

        if ($fp === false) {
            throw new RuntimeException("Could not open temp.");
        }

        fputcsv($fp, $attributeList, $delimiter);

        while (($row = $data->readRow()) !== null) {
            $preparedRow = $this->prepareRow($row);

            fputcsv($fp, $preparedRow, $delimiter, '"' , "\0");
        }

        rewind($fp);

        return new Stream($fp);
    }

    /**
     * @param array<string, mixed> $row
     * @return mixed[]
     */
    private function prepareRow(array $row): array
    {
        $preparedRow = [];

        foreach ($row as $item) {
            if (is_array($item) || is_object($item)) {
                $item = Json::encode($item);
            }

            $preparedRow[] = $this->sanitizeCell($item);
        }

        return $preparedRow;
    }

    /**
     * @param string[] $fieldList
     */
    public function loadAdditionalFields(Entity $entity, array $fieldList): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        foreach ($fieldList as $field) {
            $fieldType = $this->metadata
                ->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

            if (
                $fieldType === 'linkMultiple' ||
                $fieldType === 'attachmentMultiple'
            ) {
                if (!$entity->has($field . 'Ids')) {
                    $entity->loadLinkMultipleField($field);
                }
            }
        }
    }

    private function sanitizeCell(mixed $value): mixed
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
}
