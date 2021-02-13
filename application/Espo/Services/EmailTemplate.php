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

namespace Espo\Services;

use Espo\ORM\Entity;
use Espo\Core\Entities\Person;

use Espo\Core\Exceptions\NotFound;

use Espo\Core\Di;

use DateTime;
use DateTimezone;

class EmailTemplate extends Record implements

    Di\FileStorageManagerAware,
    Di\DateTimeAware,
    Di\LanguageAware,
    Di\NumberAware,
    Di\HtmlizerFactoryAware,
    Di\FieldUtilAware
{
    use Di\FileStorageManagerSetter;
    use Di\DateTimeSetter;
    use Di\LanguageSetter;
    use Di\NumberSetter;
    use Di\HtmlizerFactorySetter;
    use Di\FieldUtilSetter;

    protected function getFileStorageManager()
    {
        return $this->fileStorageManager;
    }

    protected function getDateTime()
    {
        return $this->dateTime;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function getNumber()
    {
        return $this->number;
    }

    public function parseTemplate(Entity $emailTemplate, array $params = [], $copyAttachments = false, $skipAcl = false)
    {
        $entityHash = [];

        if (!empty($params['entityHash']) && is_array($params['entityHash'])) {
            $entityHash = $params['entityHash'];
        }

        if (!isset($entityHash['User'])) {
            $entityHash['User'] = $this->getUser();
        }

        if (!empty($params['emailAddress'])) {
            $emailAddress = $this->getEntityManager()
                ->getRepository('EmailAddress')
                ->where([
                    'lower' => $params['emailAddress'],
                ])
                ->findOne();

            $entity = $this->getEntityManager()
                ->getRepository('EmailAddress')
                ->getEntityByAddress($params['emailAddress'],
                    null, ['Contact', 'Lead', 'Account', 'User']
                );

            if ($entity) {
                if ($entity instanceof Person) {
                    $entityHash['Person'] = $entity;
                }
                if (empty($entityHash[$entity->getEntityType()])) {
                    $entityHash[$entity->getEntityType()] = $entity;
                }
            }
        }

        if (empty($params['parent'])) {
            if (!empty($params['parentId']) && !empty($params['parentType'])) {
                $parent = $this->getEntityManager()->getEntity($params['parentType'], $params['parentId']);
                if ($parent) {
                    $params['parent'] = $parent;
                }
            }
        }

        if (!empty($params['parent'])) {
            $parent = $params['parent'];
            $entityHash[$parent->getEntityType()] = $parent;
            $entityHash['Parent'] = $parent;

            if (empty($entityHash['Person']) && ($parent instanceof Person)) {
                $entityHash['Person'] = $parent;
            }
        }

        if (!empty($params['relatedId']) && !empty($params['relatedType'])) {
            $related = $this->getEntityManager()->getEntity($params['relatedType'], $params['relatedId']);

            if ($related) {
                $entityHash[$related->getEntityType()] = $related;
            }
        }

        $subject = $emailTemplate->get('subject') ?? '';
        $body = $emailTemplate->get('body') ?? '';

        $parent = $entityHash['Parent'] ?? null;

        $htmlizer = null;

        if ($parent && !$this->getConfig()->get('emailTemplateHtmlizerDisabled')) {
            $handlebarsInSubject = strpos($subject, '{{') !== false && strpos($subject, '}}') !== false;
            $handlebarsInBody = strpos($body, '{{') !== false && strpos($body, '}}') !== false;

            if ($handlebarsInSubject || $handlebarsInBody) {
                $htmlizer = $this->htmlizerFactory->create($skipAcl);

                if ($handlebarsInSubject) {
                    $subject = $htmlizer->render($parent, $subject);
                }

                if ($handlebarsInBody) {
                    $body = $htmlizer->render($parent, $body);
                }
            }
        }

        foreach ($entityHash as $type => $entity) {
            $subject = $this->parseText($type, $entity, $subject, false, null, $skipAcl);
        }
        foreach ($entityHash as $type => $entity) {
            $body = $this->parseText($type, $entity, $body, false, null, $skipAcl);
        }

        $attachmentsIds = [];
        $attachmentsNames = (object) [];

        if ($copyAttachments) {
            $attachmentList = $this->getEntityManager()
                ->getRepository('EmailTemplate')
                ->getRelation($emailTemplate, 'attachments')
                ->find();

            if (!empty($attachmentList)) {
                foreach ($attachmentList as $attachment) {
                    $clone = $this->getEntityManager()->getEntity('Attachment');

                    $data = $attachment->toArray();

                    unset($data['parentType']);
                    unset($data['parentId']);
                    unset($data['id']);

                    $clone->set($data);
                    $clone->set('sourceId', $attachment->getSourceId());
                    $clone->set('storage', $attachment->get('storage'));

                    if (!$this->getFileStorageManager()->isFile($attachment)) {
                        continue;
                    }

                    $this->getEntityManager()->saveEntity($clone);

                    $attachmentsIds[] = $id = $clone->id;
                    $attachmentsNames->$id = $clone->get('name');
                }
            }
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'attachmentsIds' => $attachmentsIds,
            'attachmentsNames' => $attachmentsNames,
            'isHtml' => $emailTemplate->get('isHtml'),
        ];
    }

    public function parse(string $id, array $params = [], bool $copyAttachments = false)
    {
        $emailTemplate = $this->getEntity($id);

        if (empty($emailTemplate)) {
            throw new NotFound();
        }

        return $this->parseTemplate($emailTemplate, $params, $copyAttachments);
    }

    protected function parseText($type, Entity $entity, $text, $skipLinks = false, $prefixLink = null, $skipAcl = false)
    {
        $attributeList = array_keys($entity->getAttributes());

        if ($skipAcl) {
            $forbiddenAttributeList = [];
            $forbiddenLinkList = [];
        } else {
            $forbiddenAttributeList = $this->getAcl()->getScopeForbiddenAttributeList($entity->getEntityType(), 'read');

            $forbiddenAttributeList = array_merge(
                $forbiddenAttributeList,
                $this->getAcl()->getScopeRestrictedAttributeList($entity->getEntityType(), ['forbidden', 'internal', 'onlyAdmin'])
            );

            $forbiddenLinkList = $this->getAcl()->getScopeRestrictedLinkList(
                $entity->getEntityType(), ['forbidden', 'internal', 'onlyAdmin']
            );
        }

        foreach ($attributeList as $attribute) {
            if (in_array($attribute, $forbiddenAttributeList)) {
                continue;
            }

            $value = $entity->get($attribute);

            if (is_object($value)) {
                continue;
            }

            if (!$entity->getAttributeType($attribute)) {
                continue;
            }

            $value = $this->formatAttributeValue($entity, $attribute);

            if (is_null($value)) {
                continue;
            }

            $variableName = $attribute;

            if (!is_null($prefixLink)) {
                $variableName = $prefixLink . '.' . $attribute;
            }

            $text = str_replace('{' . $type . '.' . $variableName . '}', $value, $text);
        }

        if (!$skipLinks && $entity->id) {
            foreach ($entity->getRelationList() as $relation) {
                if (in_array($relation, $forbiddenLinkList)) {
                    continue;
                }

                $relationType = $entity->getRelationType($relation);

                if (
                    $relationType === 'belongsTo' ||
                    $relationType === 'belongsToParent'
                ) {
                    $relatedEntity = $this->getEntityManager()
                        ->getRepository($entity->getEntityType())
                        ->getRelation($entity, $relation)
                        ->findOne();

                    if (!$relatedEntity) {
                        continue;
                    }

                    if ($this->getAcl()) {
                        if (!$this->getAcl()->check($relatedEntity, 'read')) {
                            continue;
                        }
                    }

                    $text = $this->parseText($type, $relatedEntity, $text, true, $relation, $skipAcl);
                }
            }
        }

        $replaceData = [];

        $replaceData['today'] = $this->getDateTime()->getTodayString();
        $replaceData['now'] = $this->getDateTime()->getNowString();

        $timeZone = $this->getConfig()->get('timeZone');

        $now = new DateTime('now', new DateTimezone($timeZone));

        $replaceData['currentYear'] = $now->format('Y');

        foreach ($replaceData as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    public function formatAttributeValue(Entity $entity, string $attribute) : ?string
    {
        $value = $entity->get($attribute);

        $fieldType = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'type']);

        $attributeType = $entity->getAttributeType($attribute);

        if ($fieldType === 'enum') {
            $value = $this->getLanguage()->translateOption($value, $attribute, $entity->getEntityType());
        } else if ($fieldType === 'array' || $fieldType === 'multiEnum' || $fieldType === 'checklist') {
            $valueList = [];

            if (is_array($value)) {
                foreach ($value as $v) {
                    $valueList[] = $this->getLanguage()->translateOption($v, $attribute, $entity->getEntityType());
                }
            }

            $value = implode(', ', $valueList);
            $value = $this->getLanguage()->translateOption($value, $attribute, $entity->getEntityType());
        } else {
            if ($attributeType == 'date') {
                if ($value) {
                    $value = $this->getDateTime()->convertSystemDate($value);
                }
            } else if ($attributeType == 'datetime') {
                if ($value) {
                    $value = $this->getDateTime()->convertSystemDateTime($value);
                }
            } else if ($attributeType == 'text') {
                if (!is_string($value)) {
                    $value = '';
                }

                $value = nl2br($value);
            } else if ($attributeType == 'float') {
                if (is_float($value)) {
                    $decimalPlaces = 2;

                    if ($fieldType === 'currency') {
                        $decimalPlaces = $this->getConfig()->get('currencyDecimalPlaces');
                    }

                    $value = $this->getNumber()->format($value, $decimalPlaces);
                }
            } else if ($attributeType == 'int') {
                if (is_int($value)) {
                    $value = $this->getNumber()->format($value);
                }
            }
        }

        if (!is_string($value) && is_scalar($value) || is_callable([$value, '__toString'])) {
            $value = strval($value);
        } else if ($value === null) {
            $value = '';
        }

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function getInsertFieldData(array $params)
    {
        $to = $params['to'] ?? null;
        $parentId = $params['parentId'] ?? null;
        $parentType = $params['parentType'] ?? null;

        $result = (object) [];

        $emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');

        $dataList = [];

        if ($parentId && $parentType) {
            $e = $this->getEntityManager()->getEntity($parentType, $parentId);

            if ($e && $this->getAcl()->check($e)) {
                $dataList[] = [
                    'type' => 'parent',
                    'entity' => $e,
                ];
            }
        }

        if ($to) {
            $e = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($to, null,
                ['Contact', 'Lead', 'Account']
            );
            if ($e && $e->getEntityType() !== 'User' && $this->getAcl()->check($e)) {
                $dataList[] = [
                    'type' => 'to',
                    'entity' => $e,
                ];
            }
        }

        $fm = $this->fieldUtil;

        foreach ($dataList as $item) {
            $type = $item['type'];
            $e = $item['entity'];

            $entityType = $e->getEntityType();

            $recordService = $this->getServiceFactory()->create($entityType);

            $recordService->prepareEntityForOutput($e);

            $ignoreTypeList = ['image', 'file', 'map', 'wysiwyg', 'linkMultiple', 'attachmentMultiple', 'bool'];

            foreach ($fm->getEntityTypeFieldList($entityType) as $field) {
                $fieldType = $fm->getEntityTypeFieldParam($entityType, $field, 'type');
                $fieldAttributeList = $fm->getAttributeList($entityType, $field);

                if (
                    $fm->getEntityTypeFieldParam($entityType, $field, 'disabled') ||
                    $fm->getEntityTypeFieldParam($entityType, $field, 'directAccessDisabled') ||
                    $fm->getEntityTypeFieldParam($entityType, $field, 'templatePlaceholderDisabled') ||
                    in_array($fieldType, $ignoreTypeList)
                ) {
                    foreach ($fieldAttributeList as $a) {
                        $e->clear($a);
                    }
                }
            }

            $attributeList = $fm->getEntityTypeAttributeList($entityType);

            $values = (object) [];

            foreach ($attributeList as $a) {
                if (!$e->has($a)) {
                    continue;
                }

                $value = $emailTemplateService->formatAttributeValue($e, $a);

                if ($value != '') {
                    $values->$a = $value;
                }
            }

            $result->$type = (object) [
                'entityType' => $e->getEntityType(),
                'id' => $e->id,
                'values' => $values,
                'name' => $e->get('name'),
            ];
        }

        return $result;
    }
}
