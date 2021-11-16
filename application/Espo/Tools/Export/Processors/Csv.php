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
    Utils\Json,
};

use Espo\Entities\Preferences;

use Espo\Tools\Export\{
    Processor\Params,
    Processor\Data,
    Processor,
};

use Psr\Http\Message\StreamInterface;

use GuzzleHttp\Psr7\Stream;

class Csv implements Processor
{
    private $config;

    private $preferences;

    private $metadata;

    public function __construct(Config $config, Preferences $preferences, Metadata $metadata)
    {
        $this->config = $config;
        $this->preferences = $preferences;
        $this->metadata = $metadata;
    }

    public function process(Params $params, Data $data): StreamInterface
    {
        $attributeList = $params->getAttributeList();

        $delimiterRaw =
            $this->preferences->get('exportDelimiter') ??
            $this->config->get('exportDelimiter') ??
            ',';

        $delimiter = str_replace('\t', "\t", $delimiterRaw);

        $fp = fopen('php://temp', 'w');

        fputcsv($fp, $attributeList, $delimiter);

        while (($row = $data->readRow()) !== null) {
            $preparedRow = $this->prepareRow($row);

            fputcsv($fp, $preparedRow, $delimiter, '"' , "\0");
        }

        rewind($fp);

        return new Stream($fp);
    }

    protected function prepareRow(array $row): array
    {
        $preparedRow = [];

        foreach ($row as $item) {
            if (is_array($item) || is_object($item)) {
                $item = Json::encode($item);
            }

            $preparedRow[] = $item;
        }

        return $preparedRow;
    }

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
}
