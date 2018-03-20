<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

    protected $entityManager;

    protected $metadata;

    protected $language;

    public function __construct(FileManager $fileManager, DateTime $dateTime, NumberUtil $number, $acl = null, $entityManager = null, $metadata = null, $language = null)
    {
        $this->fileManager = $fileManager;
        $this->dateTime = $dateTime;
        $this->number = $number;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->language = $language;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function format($value)
    {
        if (is_float($value)) {
            $value = $this->number->format($value, 2);
        } else if (is_int($value)) {
            $value = $this->number->format($value);
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
                            $v = json_decode(json_encode($v), true);
                        }
                        if (is_array($v)) {
                            foreach ($v as $k => $w) {
                                $keyRaw = $k . '_RAW';
                                $v[$keyRaw] = $v[$k];
                                $v[$k] = $this->format($v[$k]);
                            }
                        }

                        $newList[] = $v;
                    }
                    $data[$field] = $newList;
                }
            } else if ($type == Entity::JSON_OBJECT) {
                if (!empty($data[$field])) {
                    $value = $data[$field];
                    if ($value instanceof \StdClass) {
                        $data[$field] = json_decode(json_encode($value), true);
                    }
                    foreach ($data[$field] as $k => $w) {
                        $keyRaw = $k . '_RAW';
                        $data[$field][$keyRaw] = $data[$field][$k];
                        $data[$field][$k] = $this->format($data[$field][$k]);
                    }
                }
            } else if ($type === Entity::PASSWORD) {
                unset($data[$field]);
            }

            if (array_key_exists($field, $data)) {
                $keyRaw = $field . '_RAW';
                $data[$keyRaw] = $data[$field];

                $fieldType = $this->getFieldType($entity->getEntityType(), $field);
                if ($fieldType === 'enum') {
                    if ($this->language) {
                        $data[$field] = $this->language->translateOption($data[$field], $field, $entity->getEntityType());
                    }
                }

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
        $code = \LightnCandy::compile($template, [
            'flags' => \LightnCandy::FLAG_HANDLEBARSJS,
            'helpers' => [
                'file' => function ($context, $options) {
                    if (count($context) && $context[0]) {
                        $id = $context[0];
                        return "?entryPoint=attachment&id=" . $id;
                    }
                },
                'numberFormat' => function ($context, $options) {
                    if ($context && isset($context[0])) {
                        $number = $context[0];

                        $decimals = 0;
                        $decimalPoint = '.';
                        $thousandsSeparator = ',';

                        if (isset($options['decimals'])) {
                            $decimals = $options['decimals'];
                        }
                        if (isset($options['decimalPoint'])) {
                            $decimalPoint = $options['decimalPoint'];
                        }
                        if (isset($options['thousandsSeparator'])) {
                            $thousandsSeparator = $options['thousandsSeparator'];
                        }
                        return number_format($number, $decimals, $decimalPoint, $thousandsSeparator);
                    }
                    return '';
                }
            ],
            'hbhelpers' => [
                'ifEqual' => function () {
                    $args = func_get_args();
                    $context = $args[count($args) - 1];
                    if ($args[0] === $args[1]) {
                        return $context['fn']();
                    } else {
                        return $context['inverse'] ? $context['inverse']() : '';
                    }
                },
                'ifNotEqual' => function () {
                    $args = func_get_args();
                    $context = $args[count($args) - 1];
                    if ($args[0] !== $args[1]) {
                        return $context['fn']();
                    } else {
                        return $context['inverse'] ? $context['inverse']() : '';
                    }
                }
            ]
        ]);

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

        if (!array_key_exists('today', $data)) {
            $data['today'] = $this->dateTime->getTodayString();
        }

        if (!array_key_exists('now', $data)) {
            $data['now'] = $this->dateTime->getNowString();
        }

        foreach ($additionalData as $k => $value) {
            $data[$k] = $value;
        }

        $html = $renderer($data);

        $html = str_replace('?entryPoint=attachment&amp;', '?entryPoint=attachment&', $html);

        if ($this->getEntityManager()) {
            $html = preg_replace_callback('/\?entryPoint=attachment\&id=([A-Za-z0-9]*)/', function ($matches) {
                $id = $matches[1];
                $attachment = $this->getEntityManager()->getEntity('Attachment', $id);

                if ($attachment) {
                    $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($attachment);
                    return $filePath;
                }
            }, $html);
        }

        return $html;
    }

    protected function getFieldType($entityType, $field) {
        if (!$this->metadata) return;
        return $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);
    }
}