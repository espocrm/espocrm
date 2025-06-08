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

namespace Espo\Core\Htmlizer;

use Closure;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMXPath;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Attachment;
use Espo\Entities\User;
use Espo\ORM\Name\Attribute;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Core\Utils\Json;
use Espo\Core\Acl;
use Espo\Core\InjectableFactory;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\NumberUtil;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use LightnCandy\Flags;
use LightnCandy\LightnCandy as LightnCandy;

use LogicException;
use RuntimeException;
use stdClass;

use const JSON_PRESERVE_ZERO_FRACTION;

/**
 * Generates an HTML for an entity. Used by Print-to-PDF, system email notifications.
 * Not for direct use. Use `TemplateRenderer`.
 * @internal
 */
class Htmlizer
{
    private const LINK_LIMIT = 100;

    public function __construct(
        private DateTime $dateTime,
        private NumberUtil $number,
        private SelectBuilderFactory $selectBuilderFactory,
        private User $user,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Language $language,
        private Config $config,
        private Log $log,
        private InjectableFactory $injectableFactory,
        private ?Acl $acl = null,
        private ?ServiceFactory $serviceFactory = null,
    ) {}

    /**
     * Generate an HTML for entity by a given template.
     *
     * @param ?string $cacheId @deprecated To be skipped.
     * @param ?array<string, mixed> $additionalData Data will be passed to the template.
     * @param bool $skipLinks Do not process related records.
     */
    public function render(
        ?Entity $entity,
        string $template,
        ?string $cacheId = null,
        ?array $additionalData = null,
        bool $skipLinks = false,
        bool $skipInlineAttachmentHandling = false
    ): string {

        $helpers = $this->getHelpers();

        $template = $this->prepare($template, array_keys($helpers));

        $code = LightnCandy::compile($template, [
            'flags' => Flags::FLAG_HANDLEBARSJS | Flags::FLAG_ERROR_EXCEPTION,
            'helpers' => $helpers,
        ]);

        if ($code === false) {
            throw new RuntimeException("Template compile error.");
        }

        /**
         * @var Closure|false $renderer
         */
        $renderer = LightnCandy::prepare($code);

        if ($renderer === false) {
            throw new RuntimeException("Template compile error.");
        }

        if ($additionalData === null) {
            $additionalData = [];
        }

        $data = $entity ?
            $this->getDataFromEntity($entity, $skipLinks, 0, $template, $additionalData) :
            $additionalData;

        if (!array_key_exists('today', $data)) {
            $data['today'] = $this->dateTime->getTodayString();
            $data['today_RAW'] = date('Y-m-d');
        }

        if (!array_key_exists('now', $data)) {
            $data['now'] = $this->dateTime->getNowString();
            $data['now_RAW'] = date('Y-m-d H:i:s');
        }

        $data['__injectableFactory'] = $this->injectableFactory;
        $data['__config'] = $this->config;
        $data['__dateTime'] = $this->dateTime;
        $data['__metadata'] = $this->metadata;
        $data['__entityManager'] = $this->entityManager;
        $data['__language'] = $this->language;
        $data['__serviceFactory'] = $this->serviceFactory;
        $data['__log'] = $this->log;
        $data['__entityType'] = $entity?->getEntityType();

        $html = $renderer($data);

        if (!$skipInlineAttachmentHandling) {
            $html = str_replace('?entryPoint=attachment&amp;', '?entryPoint=attachment&', $html);
        }

        if (!$skipInlineAttachmentHandling) {
            /** @var string $html */
            $html = preg_replace_callback(
                '/\?entryPoint=attachment&id=([A-Za-z0-9\-]*)/',
                function ($matches) {
                    $id = $matches[1];

                    if (!$id) {
                        return '';
                    }

                    /** @var Attachment $attachment */
                    $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $id);

                    if (!$attachment) {
                        return '';
                    }

                    /** @var AttachmentRepository $repository */
                    $repository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

                    return $repository->getFilePath($attachment);
                },
                $html
            );
        }

        return $html;
    }

    private function format(mixed $value): mixed
    {
        if (is_float($value)) {
            return $this->number->format($value, 2);
        }

        if (is_int($value)) {
            return $this->number->format($value);
        }

        if (is_string($value)) {
            return nl2br($value);
        }

        return $value;
    }

    /**
     * @param ?array<string, mixed> $additionalData
     * @return array<string, mixed>
     */
    private function getDataFromEntity(
        Entity $entity,
        bool $skipLinks = false,
        int $level = 0,
        ?string $template = null,
        ?array $additionalData = null
    ): array {

        $entityType = $entity->getEntityType();

        $data = get_object_vars($entity->getValueMap());

        if ($additionalData) {
            foreach ($additionalData as $k => $value) {
                $data[$k] = $value;
            }
        }

        $attributeList = $entity->getAttributeList();

        $forbiddenAttributeList = [];
        $skipAttributeList = [];
        $forbiddenLinkList = [];

        if ($this->acl) {
            $forbiddenAttributeList = array_merge(
                $this->acl->getScopeForbiddenAttributeList($entityType),
                $this->acl->getScopeRestrictedAttributeList(
                    $entityType,
                    ['forbidden', 'internal', 'onlyAdmin']
                )
            );

            $forbiddenLinkList = $this->acl->getScopeRestrictedLinkList(
                $entity->getEntityType(),
                ['forbidden', 'internal', 'onlyAdmin']
            );
        }

        if (
            !$skipLinks &&
            $level === 0 &&
            $entity->hasId()
        ) {
            $this->loadRelatedCollections($entity, $template, $data);
        }

        foreach ($data as $key => $value) {
            if ($value instanceof Collection) {
                $skipAttributeList[] = $key;

                /** @var iterable<Entity> $collection */
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

            if ($additionalData && array_key_exists($attribute, $additionalData)) {
                continue;
            }

            $type = $entity->getAttributeType($attribute);

            $fieldType = null;

            if ($entity instanceof CoreEntity) {
                $fieldType = $entity->getAttributeParam($attribute, 'fieldType');
            }

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

                        if ($item instanceof stdClass) {
                            $v = json_decode(
                                Json::encode($v, JSON_PRESERVE_ZERO_FRACTION),
                                true
                            );
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

                    if ($value instanceof stdClass) {
                        $data[$attribute] = json_decode(
                            Json::encode($value, JSON_PRESERVE_ZERO_FRACTION),
                            true
                        );
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

            if (
                $fieldType === FieldType::CURRENCY &&
                $entity instanceof CoreEntity &&
                $entity->getAttributeParam($attribute, 'attributeRole') === 'currency'
            ) {
                $currencyValue = $data[$attribute] ?? null;

                if ($currencyValue) {
                    $data[$attribute . 'Symbol'] =
                        $this->metadata->get(['app', 'currency', 'symbolMap', $currencyValue]);
                }
            }

            if (array_key_exists($attribute, $data)) {
                $keyRaw = $attribute . '_RAW';

                if (!isset($data[$keyRaw])) {
                    $data[$keyRaw] = $data[$attribute];
                }

                $fieldType = $this->getFieldType($entity->getEntityType(), $attribute);

                if ($fieldType === FieldType::ENUM) {
                    $data[$attribute] = $this->language->translateOption(
                        $data[$attribute], $attribute, $entity->getEntityType()
                    );

                    $translationPath = $this->metadata
                        ->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'translation']);

                    if ($translationPath) {
                        $data[$attribute] = $this->language->get($translationPath . '.' . $attribute, $data[$attribute]);
                    }
                }

                $data[$attribute] = $this->format($data[$attribute]);
            }
        }

        if (!$skipLinks) {
            foreach ($entity->getRelationList() as $relation) {
                if (in_array($relation, $forbiddenLinkList)) {
                    continue;
                }

                $relationType = $entity->getRelationType($relation);

                if (
                    $relationType !== Entity::BELONGS_TO &&
                    $relationType !== Entity::BELONGS_TO_PARENT
                ) {
                    continue;
                }

                $relatedEntity = $this->entityManager
                    ->getRDBRepository($entity->getEntityType())
                    ->getRelation($entity, $relation)
                    ->findOne();

                if (!$relatedEntity) {
                    continue;
                }

                if ($this->acl && !$this->acl->checkEntityRead($relatedEntity)) {
                    continue;
                }

                $data[$relation] = $this->getDataFromEntity($relatedEntity, true, $level + 1);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function loadRelatedCollections(Entity $entity, ?string $template, array &$data): void
    {
        foreach ($entity->getRelationList() as $relation) {
            $collection = $this->loadRelatedCollection($entity, $relation, $template);

            if ($collection) {
                $data[$relation] = $collection;
            }
        }
    }

    /**
     * @return ?Collection<Entity>
     */
    private function loadRelatedCollection(Entity $entity, string $relation, ?string $template): ?Collection
    {
        $limit = $this->config->get('htmlizerLinkLimit', self::LINK_LIMIT);

        $orderData = $this->getRelationOrder($entity->getEntityType(), $relation);

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (
            $entity instanceof CoreEntity &&
            $entity->hasLinkMultipleField($relation) &&
            $entityDefs->hasField($relation) &&
            !$entityDefs->getField($relation)->getParam('noLoad')
        ) {
            return $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, $relation)
                ->limit(0, $limit)
                ->order($orderData)
                ->find();
        }

        if (
            $template &&
            in_array(
                $entity->getRelationType($relation),
                [
                    Entity::HAS_MANY,
                    Entity::MANY_MANY,
                    Entity::HAS_CHILDREN,
                ]
            ) &&
            mb_stripos($template, '{{#each ' . $relation . '}}') !== false
        ) {
            $foreignEntityType = $this->entityManager
                ->getDefs()
                ->getEntity($entity->getEntityType())
                ->getRelation($relation)
                ->getForeignEntityType();

            $selectBuilder = $this->selectBuilderFactory->create();

            $selectBuilder->from($foreignEntityType);

            if ($this->acl) {
                $selectBuilder
                    ->forUser($this->user)
                    ->withAccessControlFilter();
            }

            try {
                $query = $selectBuilder->build();
            } catch (BadRequest|Forbidden $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }

            return $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, $relation)
                ->clone($query)
                ->limit(0, $limit)
                ->order($orderData)
                ->find();
        }

        return null;
    }

    /**
     * @return array<string, callable>
     */
    private function getHelpers(): array
    {
        $helpers = [
            'and' => function () {
                $args = func_get_args();

                if (count($args) === 1) {
                    return false;
                }

                for ($i = 0; $i < count($args) - 1; $i++) {
                    $arg = $args[$i];

                    if (!$arg) {
                        return false;
                    }
                }

                return true;
            },
            'or' => function () {
                $args = func_get_args();

                for ($i = 0; $i < count($args) - 1; $i++) {
                    $arg = $args[$i];

                    if ($arg) {
                        return true;
                    }
                }

                return false;
            },
            'not' => function () {
                $args = func_get_args();

                if (count($args) !== 2) {
                    return false;
                }

                $arg = $args[0];

                return !$arg;
            },
            'equal' => function () {
                $args = func_get_args();

                $arg1 = $args[0] ?? null;
                $arg2 = $args[1] ?? null;

                return $arg1 === $arg2;
            },
            'notEqual' => function () {
                $args = func_get_args();

                $arg1 = $args[0] ?? null;
                $arg2 = $args[1] ?? null;

                return $arg1 !== $arg2;
            },
            'file' => function () {
                $args = func_get_args();

                $id = $args[0] ?? null;

                if (!$id) {
                    return '';
                }

                /** @noinspection PhpUndefinedClassInspection */
                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @phpstan-ignore-next-line */
                return new LightnCandy\SafeString("?entryPoint=attachment&id=" . $id);
            },
            'pagebreak' => function () {
                /** @noinspection PhpUndefinedClassInspection, HtmlUnknownAttribute */
                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @phpstan-ignore-next-line */
                return new LightnCandy\SafeString('<br pagebreak="true">');
            },
            'imageTag' => function () {
                $args = func_get_args();

                $context = $args[count($args) - 1];

                $field = $context['hash']['field'] ?? null;

                $id = null;

                if ($field) {
                    $id = $context['_this'][$field . 'Id'] ?? null;
                } else if (count($args) > 1) {
                    $id = $args[0];
                }

                if (!$id || !is_string($id)) {
                    return null;
                }

                $width = $context['hash']['width'] ?? null;
                $height = $context['hash']['height'] ?? null;

                $attributesPart = "";

                if ($width) {
                    $attributesPart .= " width=\"$width\"";
                }

                if ($height) {
                    $attributesPart .= " height=\"$height\"";
                }

                /** @noinspection HtmlRequiredAltAttribute */
                $html = "<img src=\"?entryPoint=attachment&id=$id\"$attributesPart>";

                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @noinspection PhpUndefinedClassInspection */
                /** @phpstan-ignore-next-line */
                return new LightnCandy\SafeString($html);
            },
            'var' => function () {
                $args = func_get_args();

                $c = $args[1] ?? [];
                $key = $args[0] ?? null;

                if (is_null($key)) {
                    return null;
                }

                return $c[$key] ?? null;
            },
            'numberFormat' => function () {
                $args = func_get_args();

                if (count($args) !== 2) {
                    return null;
                }

                $context = $args[count($args) - 1];
                $number = $args[0] ?? null;

                if (is_null($number)) {
                    return '';
                }

                $decimals = $context['hash']['decimals'] ?? 0;
                $decimalPoint = $context['hash']['decimalPoint'] ?? '.';
                $thousandsSeparator = $context['hash']['thousandsSeparator'] ?? ',';

                return number_format((float) $number, $decimals, $decimalPoint, $thousandsSeparator);
            },
            'dateFormat' => function () {
                $args = func_get_args();

                if (count($args) !== 2) {
                    return null;
                }

                $context = $args[count($args) - 1];
                $dateValue = $args[0];

                $format = $context['hash']['format'] ?? null;
                $timezone = $context['hash']['timezone'] ?? null;
                $language = $context['hash']['language'] ?? null;
                $dateTime = $context['data']['root']['__dateTime'];

                if (strlen($dateValue) > 11) {
                    return $dateTime->convertSystemDateTime($dateValue, $timezone, $format, $language);
                }

                return $dateTime->convertSystemDate($dateValue, $format, $language);
            },
            'barcodeImage' => function () {
                $args = func_get_args();

                if (count($args) !== 2) {
                    return null;
                }

                $context = $args[count($args) - 1];
                $value = $args[0];

                $params = $context['hash'];
                $params['value'] = $value;

                /** @phpstan-ignore-next-line */
                $paramsString = urlencode(json_encode($params));

                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @noinspection PhpUndefinedClassInspection */
                /** @phpstan-ignore-next-line */
                return new LightnCandy\SafeString("<barcodeimage data=\"$paramsString\"/>");
            },
            'ifEqual' => function () {
                $args = func_get_args();
                $context = $args[count($args) - 1];

                if ($args[0] === $args[1]) {
                    return $context['fn']();
                }

                return $context['inverse'] ? $context['inverse']() : '';
            },
            'ifNotEqual' => function () {
                $args = func_get_args();

                $context = $args[count($args) - 1];

                if ($args[0] !== $args[1]) {
                    return $context['fn']();
                }

                return $context['inverse'] ? $context['inverse']() : '';
            },
            'ifInArray' => function () {
                $args = func_get_args();

                $context = $args[count($args) - 1];

                $array = $args[1] ?? [];

                if (in_array($args[0], $array)) {
                    return $context['fn']();
                }

                return $context['inverse'] ? $context['inverse']() : '';
            },
            'ifMultipleOf' => function () {
                $args = func_get_args();

                $context = $args[count($args) - 1];

                if ($args[0] % $args[1] === 0) {
                    return $context['fn']();
                }

                return $context['inverse'] ? $context['inverse']() : '';
            },
            'checkboxTag' => function () {
                $args = func_get_args();

                $context = $args[count($args) - 1];

                if (count($args) < 2) {
                    return null;
                }

                $color = $context['hash']['color'] ?? '#000';

                $option = $context['hash']['option'] ?? null;

                if (is_null($option)) {
                    return null;
                }

                $option = strval($option);

                $list = $args[0] ?? [];

                if (!is_array($list)) {
                    return null;
                }

                $css = "font-family: zapfdingbats; color: $color";

                if (in_array($option, $list)) {
                    $html =
                        '<input type="checkbox" checked="checked" name="1" ' .
                        'readonly="true" value="1" style="'.$css.'">';
                } else {
                    $html = '<input type="checkbox" name="1" readonly="true" value="1" style="color: '.$css.'">';
                }

                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @noinspection PhpUndefinedClassInspection */
                /** @phpstan-ignore-next-line */
                return new LightnCandy\SafeString($html);
            },
        ];

        $customHelper = function () {
            $args = func_get_args();
            $argumentList = array_slice($args, 0, -1);
            $context = $args[count($args) - 1];

            $options = $context['hash'];
            $rootData = $context['data']['root'];

            $injectableFactory = $rootData['__injectableFactory'];
            $metadata = $rootData['__metadata'];

            $name = $context['name'];

            $className = $metadata->get(['app', 'templateHelpers', $name]);

            // Not using FQN deliberately.
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $data = new \Espo\Core\Htmlizer\Helper\Data(
                $name,
                $argumentList,
                (object) $options,
                $context['_this'],
                $rootData,
                $context['fn'] ?? null,
                $context['inverse'] ?? null,
                //$context['fn.blockParams'],
            );

            $helper = $injectableFactory->create($className);

            $result = $helper->render($data);

            $value = $result->getValue();

            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            if ($value instanceof \Espo\Core\Htmlizer\Helper\SafeString) {
                return $value->getWrappee();
            }

            return $value;
        };

        $additionalHelpers = array_filter(
            $this->metadata->get(['app', 'templateHelpers']) ?? [],
            function (string $item) {
                return str_contains($item, '::');
            }
        );

        $helpers = array_merge($helpers, $additionalHelpers);

        $additionalHelper2NameList = array_keys(
            array_filter(
                $this->metadata->get(['app', 'templateHelpers']) ?? [],
                function (string $item) {
                    return !str_contains($item, '::');
                }
            )
        );

        foreach ($additionalHelper2NameList as $name) {
            $helpers[$name] = $customHelper;
        }

        return $helpers;
    }

    private function getFieldType(string $entityType, string $field): ?string
    {
        return $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function getRelationOrder(string $entityType, string $relation): array
    {
        $relationDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getRelation($relation);

        if (!$relationDefs->hasForeignEntityType()) {
            return [];
        }

        $foreignEntityType = $relationDefs->getForeignEntityType();

        $collectionParams = $this->entityManager
            ->getDefs()
            ->getEntity($foreignEntityType)
            ->getParam('collection') ?? [];

        $order = $collectionParams['order'] ?? null;
        $orderBy = $collectionParams['orderBy'] ?? null;

        if ($order === null && $orderBy === null) {
            return [];
        }

        if ($orderBy === null) {
            $orderBy = Attribute::ID;
        }

        return [[$orderBy, $order]];
    }

    /**
     * @param string[] $helpers
     */
    private function handleAttributeHelper(string $template, string $attribute, string $helper, array $helpers): string
    {
        if ($template === '') {
            return $template;
        }

        if (!extension_loaded('dom')) {
            $this->log->warning("Extension 'dom' is not enabled. HTML templating functionality is restricted.");

            return $template;
        }

        $xml = new DOMDocument();

        $templateModified = "<!DOCTYPE html><meta charset=\"UTF-8\"><body>" . $template . "</body>";

        $loadResult = $xml->loadHTML($templateModified);

        if ($loadResult === false) {
            $this->log->warning("HTML template parsing error.");

            return $template;
        }

        $xpath = new DOMXPath($xml);

        $found = false;

        $elements = $xpath->query("//*[@$attribute]");

        if (!$elements) {
            return $template;
        }

        foreach ($elements as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }

            try {
                $wrapperElement = $xml->createElement("$attribute-wrapper");

                if (!$wrapperElement) {
                    throw new LogicException();
                }

                $wrapperElement->setAttribute('v', $element->getAttribute($attribute));
            } catch (DOMException $e) {
                throw new LogicException($e->getMessage());
            }

            $parentNode = $element->parentNode;

            if (!$parentNode) {
                throw new LogicException();
            }

            $newElement = $xml->importNode($element->cloneNode(true));

            if (!$newElement instanceof DOMElement) {
                throw new LogicException();
            }

            $newElement->removeAttribute($attribute);

            $wrapperElement->appendChild($newElement);
            $parentNode->replaceChild($wrapperElement, $element);

            $found = true;
        }

        if (!$found) {
            return $template;
        }

        $newTemplate = $xml->saveXML();

        if ($newTemplate === false || !is_string($newTemplate)) {
            $this->log->warning("DOM save error.");

            return $template;
        }

        $newTemplate = str_replace("</$attribute-wrapper>", "{{/$helper}}", $newTemplate);

        $from = strpos($newTemplate,'<body>') + 6;
        $to = strrpos($newTemplate, '</body>') - strlen($newTemplate);

        $newTemplate = substr($newTemplate, $from, $to);

        $regExp = '/<' . $attribute . '-wrapper v="{{(.*?)}}">/';

        $newTemplate = preg_replace_callback($regExp, function ($matches) use ($helpers, $helper) {
            $expression = trim($matches[1]);

            $isHelper = false;

            foreach ($helpers as $it) {
                if (str_starts_with($expression, $it . ' ')) {
                    $isHelper = true;

                    break;
                }
            }

            if ($isHelper) {
                $expression = "($expression)";
            }

            return "{{#$helper $expression}}";
        }, $newTemplate);

        return $newTemplate ?? '';
    }

    /**
     * @param string[] $helpers
     */
    private function prepare(string $template, array $helpers): string
    {
        $template = str_replace('<tcpdf ', '', $template);

        $template = $this->handleAttributeHelper($template, 'iterate', 'each', $helpers);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $template = $this->handleAttributeHelper($template, 'x-if', 'if', $helpers);

        return $template;
    }
}
