<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\Htmlizer;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\NumberUtil;

require('vendor/zordius/lightncandy/src/lightncandy.php');

class Htmlizer
{
    protected $fileManager;

    protected $dateTime;

    protected $config;

    protected $acl;

    public function __construct(FileManager $fileManager, DateTime $dateTime, NumberUtil $number, $acl = null)
    {
        $this->fileManager = $fileManager;
        $this->dateTime = $dateTime;
        $this->number = $number;
        $this->acl = $acl;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function formatNumber($value)
    {
        return $this->number->format($value);
    }

    protected function format($value)
    {
        if (is_float($value) || is_int($value)) {
            $value = $this->formatNumber($value);
        } else if (is_string($value)) {
            $value = nl2br($value);
        }
        return $value;
    }

    protected function getDataFromEntity(Entity $entity, $skipLinks = false)
    {
        $data = $entity->toArray();

        $fieldDefs = $entity->getFields();
        $fieldList = array_keys($fieldDefs);

        $forbidenAttributeList = [];

        if ($this->getAcl()) {
            $forbidenAttributeList = $this->getAcl()->getScopeForbiddenAttributeList($entity->getEntityType(), 'read');
        }

        foreach ($fieldList as $field) {
            if (in_array($field, $forbidenAttributeList)) continue;


            $type = $entity->getAttributeType($field);

            if ($type == Entity::DATETIME) {
                if (!empty($data[$field])) {
                    $data[$field] = $this->dateTime->convertSystemDateTime($data[$field]);
                }
            } else if ($type == Entity::DATE) {
                if (!empty($data[$field])) {
                    $data[$field] = $this->dateTime->convertSystemDate($data[$field]);
                }
            } else if ($type == Entity::JSON_ARRAY) {
                if (!empty($data[$field])) {
                    $list = $data[$field];
                    $newList = [];
                    foreach ($list as $item) {
                        $v = $item;
                        if ($item instanceof \StdClass) {
                            $v = get_object_vars($v);
                        }
                        foreach ($v as $k => $w) {
                            $v[$k] = $this->format($v[$k]);
                        }
                        $newList[] = $v;
                    }
                    $data[$field] = $newList;
                }
            } else if ($type == Entity::JSON_OBJECT) {
                if (!empty($data[$field])) {
                    $value = $data[$field];
                    if ($value instanceof \StdClass) {
                        $data[$field] = get_object_vars($value);
                    }
                    foreach ($data[$field] as $k => $w) {
                        $data[$field][$k] = $this->format($data[$field][$k]);
                    }
                }
            } else if ($type === Entity::PASSWORD) {
                unset($data[$field]);
            }

            if (array_key_exists($field, $data)) {
               $data[$field] = $this->format($data[$field]);
            }
        }

        if (!$skipLinks) {
            $relationDefs = $entity->getRelations();
            foreach ($entity->getRelationList() as $relation) {
                if (
                    !empty($relationDefs[$relation]['type'])
                    &&
                    ($entity->getRelationType($relation) === 'belongsTo' || $entity->getRelationType($relation) === 'belongsToParent')
                ) {
                    $relatedEntity = $entity->get($relation);
                    if (!$relatedEntity) continue;
                    if ($this->getAcl()) {
                        if (!$this->getAcl()->check($relatedEntity, 'read')) continue;
                    }

                    $data[$relation] = $this->getDataFromEntity($relatedEntity, true);
                }
            }
        }

        return $data;
    }

    public function render(Entity $entity, $template, $id = null, $additionalData = array(), $skipLinks = false)
    {
        $code = \LightnCandy::compile($template);

        $toRemove = false;
        if ($id === null) {
            $id = uniqid('', true);
            $toRemove = true;
        }

        $fileName = 'data/cache/templates/' . $id . '.php';

        $this->fileManager->putContents($fileName, $code);
        $renderer = $this->fileManager->getPhpContents($fileName);

        if ($toRemove) {
            $this->fileManager->removeFile($fileName);
        }

        $data = $this->getDataFromEntity($entity, $skipLinks);

        foreach ($additionalData as $k => $value) {
            $data[$k] = $value;
        }

        $html = $renderer($data);

        $html = str_replace('?entryPoint=attachment&amp;', '?entryPoint=attachment&', $html);
        $html = preg_replace('/\?entryPoint=attachment\&id=(.*)/', 'data/upload/$1', $html);

        return $html;
    }
}