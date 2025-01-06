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

namespace Espo\Tools\Export\Format\Csv;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Entities\Preferences;
use Espo\ORM\Entity;
use Espo\Tools\Export\Collection;
use Espo\Tools\Export\Processor as ProcessorInterface;
use Espo\Tools\Export\Processor\Params;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

use RuntimeException;

use const JSON_UNESCAPED_UNICODE;

class Processor implements ProcessorInterface
{
    public function __construct(
        private Config $config,
        private Preferences $preferences
    ) {}

    public function process(Params $params, Collection $collection): StreamInterface
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

        foreach ($collection as $entity) {
            $preparedRow = $this->prepareRow($entity, $attributeList);

            fputcsv($fp, $preparedRow, $delimiter, '"' , "\0");
        }

        rewind($fp);

        return new Stream($fp);
    }

    /**
     * @param string[] $attributeList
     * @return string[]
     */
    private function prepareRow(Entity $entity, array $attributeList): array
    {
        $preparedRow = [];

        foreach ($attributeList as $attribute) {
            $value = $entity->get($attribute);

            if (is_array($value) || is_object($value)) {
                $value = Json::encode($value, JSON_UNESCAPED_UNICODE);
            }

            $value = (string) $value;

            $preparedRow[] = $this->sanitizeCellValue($value);
        }

        return $preparedRow;
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
