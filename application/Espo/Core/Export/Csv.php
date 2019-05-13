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

use \Espo\Core\Exceptions\Error;

use \Espo\Core\ORM\Entity;

class Csv extends \Espo\Core\Injectable
{
    protected $dependencyList = [
        'config',
        'language',
        'metadata',
        'preferences'
    ];

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
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

    private function translate($label, $category, $scope, $requiredOptions = null)
    {
        return $this->getLanguage()->translate($label, $category, $scope, $requiredOptions);
    }

    private function translateField($fieldName)
    {
        $defs = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $name]);

        if (!$defs) {
            $defs['type'] = 'base';
        }
        
        $label = $fieldName;
        if (strpos($name, '_') !== false) {
            list($linkName, $foreignField) = explode('_', $fieldName);
            $foreignScope = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $linkName, 'entity']);
            if ($foreignScope) {
                $label = $this->translate($linkName, 'links', $entityType) . '.' . $this->translate($foreignField, 'fields', $foreignScope);
            }
        } else {
            $label = $this->translate($fieldName, 'fields', $entityType);
        }
        
        return $label;
    }

    public function process(string $entityType, array $params, ?array $dataList, $dataFp = null)
    {
        if (!is_array($params['attributeList'])) {
            throw new Error();
        }

        $attributeList = $params['attributeList'];
        $fieldList = $params['fieldList'];

        $delimiter = $this->getInjection('preferences')->get('exportDelimiter');
        if (empty($delimiter)) {
            $delimiter = $this->getConfig()->get('exportDelimiter', ';');
        }

        $fp = fopen('php://temp', 'w');
        
        $attributeListTranslated = [];

        foreach ($attributeList as $i => $name) {
            $attributeListTranslated[] = $this->translateField($name);
        }

        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8
        fputcsv($fp, $attributeListTranslated, $delimiter);

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