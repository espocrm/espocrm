<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Htmlizer;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;
use Espo\Core\serviceFactory;

use LightnCandy\LightnCandy as LightnCandy;

class Htmlizer
{
    protected $fileManager;
    protected $dateTime;
    protected $config;
    protected $acl;
    protected $entityManager;
    protected $metadata;
    protected $language;
    protected $serviceFactory;

    public function __construct(
        FileManager $fileManager,
        DateTime $dateTime,
        NumberUtil $number,
        $acl = null,
        ?EntityManager $entityManager = null,
        ?Metadata $metadata = null,
        ?Language $language = null,
        ?Config $config = null,
        ?ServiceFactory $serviceFactory = null
    )
    {
        $this->fileManager = $fileManager;
        $this->dateTime = $dateTime;
        $this->number = $number;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->language = $language;
        $this->config = $config;
        $this->serviceFactory = $serviceFactory;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
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

    protected function getDataFromEntity(Entity $entity, $skipLinks = false, $level = 0, ?string $template = null)
    {
        $data = $entity->toArray();

        $attributeDefs = $entity->getAttributes();
        $attributeList = array_keys($attributeDefs);

        $forbiddenAttributeList = [];
        $skipAttributeList = [];
        $forbiddenLinkList = [];

        if ($this->getAcl()) {
            $forbiddenAttributeList = $this->getAcl()->getScopeForbiddenAttributeList($entity->getEntityType(), 'read');

            $forbiddenAttributeList = array_merge(
                $forbiddenAttributeList,
                $this->getAcl()->getScopeRestrictedAttributeList($entity->getEntityType(), ['forbidden', 'internal', 'onlyAdmin'])
            );

            $forbiddenLinkList = $this->getAcl()->getScopeRestrictedLinkList($entity->getEntityType(), ['forbidden', 'internal', 'onlyAdmin']);
        }

        $relationList = $entity->getRelationList();

        if (!$skipLinks && $level === 0) {
            foreach ($relationList as $relation) {
                $collection = null;

                if ($entity->hasLinkMultipleField($relation)) {
                    $toLoad = true;
                    $collection = $entity->getLinkCollection($relation);
                } else {
                    if (
                        $template && $entity->getRelationType($relation, ['hasMany', 'manyMany', 'hasChildren']) &&
                        mb_stripos($template, '{{#each '.$relation.'}}') !== false
                    ) {
                        $limit = 100;
                        if ($this->config) {
                            $limit = $this->config->get('htmlizerLinkLimit') ?? $limit;
                        }
                        $collection = $entity->getLinkCollection($relation, ['limit' => $limit]);
                    }
                }

                if ($collection) {
                    $data[$relation] = $collection;
                }
            }
        }

        foreach ($data as $key => $value) {
            if ($value instanceof \Espo\ORM\ICollection) {
                $skipAttributeList[] = $key;
                $collection = $value;
                $list = [];
                foreach ($collection as $item) {
                    $list[] = $this->getDataFromEntity($item, $skipLinks, $level + 1);
                }
                $data[$key] = $list;
            }
        }

        foreach ($attributeList as $attribute) {
            if (in_array($attribute, $forbiddenAttributeList)) {
                unset($data[$attribute]);
                continue;
            }
            if (in_array($attribute, $skipAttributeList)) {
                continue;
            }

            $type = $entity->getAttributeType($attribute);
            $fieldType = $entity->getAttributeParam($attribute, 'fieldType');

            if ($type == Entity::DATETIME) {
                if (!empty($data[$attribute])) {
                    $data[$attribute . '_RAW'] = $data[$attribute];
                    $data[$attribute] = $this->dateTime->convertSystemDateTime($data[$attribute]);
                }
            } else if ($type == Entity::DATE) {
                if (!empty($data[$attribute])) {
                    $data[$attribute . '_RAW'] = $data[$attribute];
                    $data[$attribute] = $this->dateTime->convertSystemDate($data[$attribute]);
                }
            } else if ($type == Entity::JSON_ARRAY) {
                if (!empty($data[$attribute])) {
                    $list = $data[$attribute];

                    $newList = [];
                    foreach ($list as $item) {
                        $v = $item;
                        if ($item instanceof \StdClass) {
                            $v = json_decode(json_encode($v, \JSON_PRESERVE_ZERO_FRACTION), true);
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
                    $data[$attribute] = $newList;
                }
            } else if ($type == Entity::JSON_OBJECT) {
                if (!empty($data[$attribute])) {
                    $value = $data[$attribute];
                    if ($value instanceof \StdClass) {
                        $data[$attribute] = json_decode(json_encode($value, \JSON_PRESERVE_ZERO_FRACTION), true);
                    }
                    foreach ($data[$attribute] as $k => $w) {
                        $keyRaw = $k . '_RAW';
                        $data[$attribute][$keyRaw] = $data[$attribute][$k];
                        $data[$attribute][$k] = $this->format($data[$attribute][$k]);
                    }
                }
            } else if ($type === Entity::PASSWORD) {
                unset($data[$attribute]);
            }

            if ($fieldType === 'currency' && $this->metadata) {
                if ($entity->getAttributeParam($attribute, 'attributeRole') === 'currency') {
                    if ($currencyValue = $data[$attribute]) {
                        $data[$attribute . 'Symbol'] = $this->metadata->get(['app', 'currency', 'symbolMap', $currencyValue]);
                    }
                }
            }

            if (array_key_exists($attribute, $data)) {
                $keyRaw = $attribute . '_RAW';

                if (!isset($data[$keyRaw]))
                    $data[$keyRaw] = $data[$attribute];

                $fieldType = $this->getFieldType($entity->getEntityType(), $attribute);
                if ($fieldType === 'enum') {
                    if ($this->language) {
                        $data[$attribute] = $this->language->translateOption($data[$attribute], $attribute, $entity->getEntityType());
                    }
                }

                $data[$attribute] = $this->format($data[$attribute]);
            }
        }

        if (!$skipLinks) {
            $relationDefs = $entity->getRelations();
            foreach ($entity->getRelationList() as $relation) {
                if (in_array($relation, $forbiddenLinkList)) continue;
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

                    $data[$relation] = $this->getDataFromEntity($relatedEntity, true, $level + 1);
                }
            }
        }

        return $data;
    }

    protected function getHelpers()
    {
        $helpers = [
            'file' => function () {
                $args = func_get_args();
                $id = $args[0] ?? null;
                if (!$id) return '';
                return new LightnCandy\SafeString("?entryPoint=attachment&id=" . $id);
            },
            'pagebreak' => function () {
                return new LightnCandy\SafeString('<br pagebreak="true">');
            },
            'imageTag' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                $field = $context['hash']['field'] ?? null;
                if ($field) {
                    $id = $context['_this'][$field . 'Id'] ?? null;
                } else {
                    if (count($args) > 1) {
                        $id = $args[0];
                    }
                }
                if (!$id || !is_string($id)) return null;

                $width = $context['hash']['width'] ?? null;
                $height = $context['hash']['height'] ?? null;

                $attributesPart = "";
                if ($width)
                    $attributesPart .= " width=\"" .strval($width) . "\"";

                if ($height)
                    $attributesPart .= " height=\"" .strval($height) . "\"";

                $html = "<img src=\"?entryPoint=attachment&id={$id}\"{$attributesPart}>";

                return new LightnCandy\SafeString($html);
            },
            'var' => function () {
                $args = func_get_args();
                $c = $args[1] ?? [];
                $key = $args[0] ?? null;
                if (is_null($key)) return null;
                return $c[$key] ?? null;
            },
            'numberFormat' => function () {
                $args = func_get_args();
                if (count($args) !== 2) return null;
                $context = $args[count($args) - 1];
                $number = $args[0] ?? null;

                if (is_null($number)) return '';

                $decimals = $context['hash']['decimals'] ?? 0;
                $decimalPoint = $context['hash']['decimalPoint'] ?? '.';
                $thousandsSeparator = $context['hash']['thousandsSeparator'] ?? ',';

                return number_format($number, $decimals, $decimalPoint, $thousandsSeparator);
            },
            'dateFormat' => function () {
                $args = func_get_args();
                if (count($args) !== 2) return null;
                $context = $args[count($args) - 1];
                $dateValue = $args[0];

                $format = $context['hash']['format'] ?? null;
                $timezone = $context['hash']['timezone'] ?? null;
                $language = $context['hash']['language'] ?? null;
                $dateTime = $context['data']['root']['__dateTime'];
                if (strlen($dateValue) > 11) return $dateTime->convertSystemDateTime($dateValue, $timezone, $format, $language);

                return $dateTime->convertSystemDate($dateValue, $format, $language);
            },
            'barcodeImage' => function () {
                $args = func_get_args();
                if (count($args) !== 2) return null;
                $context = $args[count($args) - 1];
                $value = $args[0];

                $codeType = $context['hash']['type'] ?? 'CODE128';

                $typeMap = [
                    "CODE128" => 'C128',
                    "CODE128A" => 'C128A',
                    "CODE128B" => 'C128B',
                    "CODE128C" => 'C128C',
                    "EAN13" => 'EAN13',
                    "EAN8" => 'EAN8',
                    "EAN5" => 'EAN5',
                    "EAN2" => 'EAN2',
                    "UPC" => 'UPCA',
                    "UPCE" => 'UPCE',
                    "ITF14" => 'I25',
                    "pharmacode" => 'PHARMA',
                    "QRcode" => 'QRCODE,H',
                ];

                if ($codeType === 'QRcode') {
                    $function = 'write2DBarcode';
                    $params = [
                        $value,
                        $typeMap[$codeType] ?? null,
                        '', '',
                        $context['hash']['width'] ?? 40,
                        $context['hash']['height'] ?? 40,
                        [
                            'border' => false,
                            'vpadding' => $context['hash']['padding'] ?? 2,
                            'hpadding' => $context['hash']['padding'] ?? 2,
                            'fgcolor' => $context['hash']['color'] ?? [0,0,0],
                            'bgcolor' => $context['hash']['bgcolor'] ?? false,
                            'module_width' => 1,
                            'module_height' => 1,
                        ],
                        'N',
                    ];
                } else {
                    $function = 'write1DBarcode';
                    $params = [
                        $value,
                        $typeMap[$codeType] ?? null,
                        '', '',
                        $context['hash']['width'] ?? 60,
                        $context['hash']['height'] ?? 30,
                        0.4,
                        [
                            'position' => 'S',
                            'border' => false,
                            'padding' => $context['hash']['padding'] ?? 0,
                            'fgcolor' => $context['hash']['color'] ?? [0,0,0],
                            'bgcolor' => $context['hash']['bgcolor'] ?? [255,255,255],
                            'text' => $context['hash']['text'] ?? true,
                            'font' => 'helvetica',
                            'fontsize' => $context['hash']['fontsize'] ?? 14,
                            'stretchtext' => 4,
                        ],
                        'N',
                    ];
                }

                $paramsString = urlencode(json_encode($params));

                return new LightnCandy\SafeString("<tcpdf method=\"{$function}\" params=\"{$paramsString}\" />");
            },
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
            },
            'ifInArray' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                $array = $args[1] ?? [];

                if (in_array($args[0], $array)) {
                    return $context['fn']();
                } else {
                    return $context['inverse'] ? $context['inverse']() : '';
                }
            },
            'tableTag' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                $border = $context['hash']['border'] ?? '0.5pt';
                $cellpadding = $context['hash']['cellpadding'] ?? '2';


                $width = $context['hash']['width'] ?? null;

                $attributesPart = "";
                if ($width) {
                    $attributesPart .= " width=\"{$width}\"";
                }

                return
                    new LightnCandy\SafeString("<table border=\"{$border}\" cellpadding=\"{$cellpadding}\" {$attributesPart}>") .
                    $context['fn']() .
                    new LightnCandy\SafeString("</table>");
            },
            'tdTag' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                $align = strtolower($context['hash']['align'] ?? 'left');
                if (!in_array($align, ['left', 'right', 'center'])) $align = 'left';

                $width = $context['hash']['width'] ?? null;

                $attributesPart = "align=\"{$align}\"";
                if ($width) {
                    $attributesPart .= " width=\"{$width}\"";
                }

                return
                    new LightnCandy\SafeString("<td {$attributesPart}>") .
                    $context['fn']() .
                    new LightnCandy\SafeString("</td>");
            },
            'trTag' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                return
                    new LightnCandy\SafeString("<tr>") .
                    $context['fn']() .
                    new LightnCandy\SafeString("</tr>");
            },
            'checkboxTag' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                if (count($args) < 2) return null;

                $color = $context['hash']['color'] ?? '#000';

                $option = $context['hash']['option'] ?? null;

                if (is_null($option)) return null;
                $option = strval($option);

                $list = $args[0] ?? [];

                if (!is_array($list)) return null;

                $css = "font-family: zapfdingbats; color: {$color}";

                if (in_array($option, $list)) {
                    $html = '<input type="checkbox" checked="checked" name="1" readonly="true" value="1" style="'.$css.'">';
                } else {
                    $html = '<input type="checkbox" name="1" readonly="true" value="1" style="color: '.$css.'">';
                }

                return new LightnCandy\SafeString($html);
            },
        ];

        if ($this->metadata) {
            $additionalHelpers = $this->metadata->get(['app', 'templateHelpers']) ?? [];
            $helpers = array_merge($helpers, $additionalHelpers);
        }

        return $helpers;
    }

    public function render(Entity $entity, $template, $id = null, ?array $additionalData = null, $skipLinks = false)
    {
        $template = str_replace('<tcpdf ', '', $template);

        $helpers = $this->getHelpers();

        $code = LightnCandy::compile($template, [
            'flags' => LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_ERROR_EXCEPTION,
            'helpers' => $this->getHelpers(),
        ]);

        $renderer = LightnCandy::prepare($code);

        $data = $this->getDataFromEntity($entity, $skipLinks, 0, $template);

        if (!array_key_exists('today', $data)) {
            $data['today'] = $this->dateTime->getTodayString();
            $data['today_RAW'] = date('Y-m-d');
        }

        if (!array_key_exists('now', $data)) {
            $data['now'] = $this->dateTime->getNowString();
            $data['now_RAW'] = date('Y-m-d H:i:s');
        }

        $additionalData = $additionalData ?? [];

        foreach ($additionalData as $k => $value) {
            $data[$k] = $value;
        }

        $data['__dateTime'] = $this->dateTime;
        $data['__metadata'] = $this->metadata;
        $data['__entityManager'] = $this->entityManager;
        $data['__language'] = $this->language;
        $data['__serviceFactory'] = $this->serviceFactory;

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
