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

namespace Espo\Services;

use \Espo\ORM\Entity;
use \Espo\Core\Entities\Person;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;


class EmailTemplate extends Record
{

    protected function init()
    {
        parent::init();

        $this->addDependency('fileStorageManager');
        $this->addDependency('dateTime');
        $this->addDependency('language');
        $this->addDependency('number');
    }

    protected function getFileStorageManager()
    {
        return $this->getInjection('fileStorageManager');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function getNumber()
    {
        return $this->getInjection('number');
    }

    public function parseTemplate(Entity $emailTemplate, array $params = [], $copyAttachments = false, $skipAcl = false)
    {
        $entityHash = array();
        if (!empty($params['entityHash']) && is_array($params['entityHash'])) {
            $entityHash = $params['entityHash'];
        }

        if (!isset($entityHash['User'])) {
            $entityHash['User'] = $this->getUser();
        }

        if (!empty($params['emailAddress'])) {
            $emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where([
                'lower' => $params['emailAddress']
            ])->findOne();

            $entity = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($params['emailAddress']);

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

        $subject = $emailTemplate->get('subject');
        $body = $emailTemplate->get('body');

        foreach ($entityHash as $type => $entity) {
            $subject = $this->parseText($type, $entity, $subject, false, null, $skipAcl);
        }
        foreach ($entityHash as $type => $entity) {
            $body = $this->parseText($type, $entity, $body, false, null, $skipAcl);
        }

        $attachmentsIds = array();
        $attachmentsNames = new \StdClass();

        if ($copyAttachments) {
            $attachmentList = $emailTemplate->get('attachments');
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
            'isHtml' => $emailTemplate->get('isHtml')
        ];
    }

    public function parse($id, array $params = array(), $copyAttachments = false)
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

            $forbiddenLinkList = $this->getAcl()->getScopeRestrictedLinkList($entity->getEntityType(), ['forbidden', 'internal', 'onlyAdmin']);
        }

        foreach ($attributeList as $attribute) {
            if (in_array($attribute, $forbiddenAttributeList)) continue;

            $value = $entity->get($attribute);
            if (is_object($value)) {
                continue;
            }

            $fieldType = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields', $attribute, 'type']);

            if ($fieldType === 'enum') {
                $value = $this->getLanguage()->translateOption($value, $attribute, $entity->getEntityType());
            } else if ($fieldType === 'array' || $fieldType === 'multiEnum') {
                $valueList = [];
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $valueList[] = $this->getLanguage()->translateOption($v, $attribute, $entity->getEntityType());
                    }
                }
                $value = implode(', ', $valueList);
                $value = $this->getLanguage()->translateOption($value, $attribute, $entity->getEntityType());
            } else {
                $attributeType = $entity->getAttributeType($attribute);
                if (!$attributeType) continue;

                if ($attributeType == 'date') {
                    $value = $this->getDateTime()->convertSystemDate($value);
                } else if ($attributeType == 'datetime') {
                    $value = $this->getDateTime()->convertSystemDateTime($value);
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

            if (is_string($value) || $value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                $variableName = $attribute;
                if (!is_null($prefixLink)) {
                    $variableName = $prefixLink . '.' . $attribute;
                }
                $text = str_replace('{' . $type . '.' . $variableName . '}', $value, $text);
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

                    $text = $this->parseText($type, $relatedEntity, $text, true, $relation, $skipAcl);
                }
            }
        }

        $replaceData = [];
        $replaceData['today'] = $this->getDateTime()->getTodayString();
        $replaceData['now'] = $this->getDateTime()->getNowString();

        $timeZone = $this->getConfig()->get('timeZone');
        $now = new \DateTime('now', new \DateTimezone($timeZone));

        $replaceData['currentYear'] = $now->format('Y');

        foreach ($replaceData as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }
}
