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
use Espo\Core\Utils\Number;

require('vendor/zordius/lightncandy/src/lightncandy.php');

class Htmlizer
{
    protected $fileManager;

    protected $dateTime;

    protected $config;

    public function __construct(FileManager $fileManager, DateTime $dateTime, Number $number)
    {
        $this->fileManager = $fileManager;
        $this->dateTime = $dateTime;
        $this->number = $number;
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

    protected function getDataFromEntity(Entity $entity)
    {
        $data = $entity->toArray();



        $fieldDefs = $entity->getFields();
        $fieldList = array_keys($fieldDefs);

        foreach ($fieldList as $field) {
            $type = null;
            if (!empty($fieldDefs[$field]['type'])) {
                $type = $fieldDefs[$field]['type'];
            }
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
            }

            if (array_key_exists($field, $data)) {
               $data[$field] = $this->format($data[$field]);
            }
        }

        return $data;
    }

    public function render(Entity $entity, $template)
    {
        $code = \LightnCandy::compile($template);
        $id = uniqid('', true);
        $fileName = 'data/cache/template-' . $id;
        $this->fileManager->putContents($fileName, $code);
        $renderer = include($fileName);
        $this->fileManager->removeFile($fileName);

        $data = $this->getDataFromEntity($entity);

        $html = $renderer($data);

        $html = str_replace('?entryPoint=attachment&amp;', '?entryPoint=attachment&', $html);
        $html = preg_replace('/\?entryPoint=attachment\&id=(.*)/', 'data/upload/$1', $html);

        return $html;
    }
}