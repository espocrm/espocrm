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

namespace Espo\Tools\Export\Formats;

use Espo\Core\Exceptions\Error;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
};

use Espo\Entities\Preferences;

class Csv
{
    protected $config;
    protected $preferences;
    protected $metadata;

    public function __construct(Config $config, Preferences $preferences, Metadata $metadata)
    {
        $this->config = $config;
        $this->preferences = $preferences;
        $this->metadata = $metadata;
    }

    public function loadAdditionalFields(Entity $entity, $fieldList)
    {
        foreach ($fieldList as $field) {
            $fieldType = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields', $field, 'type']);

            if (
                $fieldType === 'linkMultiple' || $fieldType === 'attachmentMultiple'
            ) {
                if (!$entity->has($field . 'Ids')) {
                    $entity->loadLinkMultipleField($field);
                }
            }
        }
    }

    public function process(string $entityType, array $params, ?array $dataList, $dataFp = null)
    {
        if (!is_array($params['attributeList'])) {
            throw new Error();
        }

        $attributeList = $params['attributeList'];

        $delimiter = $this->preferences->get('exportDelimiter');
        if (empty($delimiter)) {
            $delimiter = $this->config->get('exportDelimiter', ',');
        }

        $delimiter = str_replace('\t', "\t", $delimiter);

        $fp = fopen('php://temp', 'w');
        fputcsv($fp, $attributeList, $delimiter);

        if ($dataFp) {
            while (($line = fgets($dataFp)) !== false) {
                $row = unserialize(base64_decode($line));
                $preparedRow = $this->prepareRow($row);
                fputcsv($fp, $preparedRow, $delimiter);
            }
        } else {
            foreach ($dataList as $row) {
                $preparedRow = $this->prepareRow($row);
                fputcsv($fp, $preparedRow, $delimiter);
            }
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    protected function prepareRow($row)
    {
        $preparedRow = [];
        foreach ($row as $item) {
            if (is_array($item) || is_object($item)) {
                $item = \Espo\Core\Utils\Json::encode($item);
            }
            $preparedRow[] = $item;
        }
        return $preparedRow;
    }
}