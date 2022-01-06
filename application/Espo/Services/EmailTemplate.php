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

use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\Tools\EmailTemplate\Processor;
use Espo\Tools\EmailTemplate\Params;
use Espo\Tools\EmailTemplate\Data;
use Espo\Tools\EmailTemplate\Formatter;

use Espo\Entities\EmailTemplate as EmailTemplateEntity;
use Espo\Entities\EmailAddress;

use Espo\Core\Exceptions\NotFound;

use Espo\Core\Di;

use stdClass;

class EmailTemplate extends Record implements

    Di\FieldUtilAware
{
    use Di\FieldUtilSetter;

    public function parseTemplate(
        EmailTemplateEntity $emailTemplate,
        array $params = [],
        $copyAttachments = false,
        $skipAcl = false
    ): array {

        $paramsInternal = Params::create()
            ->withApplyAcl(!$skipAcl)
            ->withCopyAttachments($copyAttachments);

        $data = Data::create()
            ->withEmailAddress($params['emailAddress'] ?? null)
            ->withEntityHash($params['entityHash'] ?? [])
            ->withParent($params['parent'] ?? null)
            ->withParentId($params['parentId'] ?? null)
            ->withParentType($params['parentType'] ?? null)
            ->withRelatedId($params['relatedId'] ?? null)
            ->withRelatedType($params['relatedType'] ?? null)
            ->withUser($this->user);

        $result = $this->createProcessor()->process($emailTemplate, $paramsInternal, $data);

        return get_object_vars($result->getValueMap());
    }

    public function parse(string $id, array $params = [], bool $copyAttachments = false): array
    {
        /** @var EmailTemplateEntity|null */
        $emailTemplate = $this->getEntity($id);

        if (empty($emailTemplate)) {
            throw new NotFound();
        }

        return $this->parseTemplate($emailTemplate, $params, $copyAttachments);
    }

    public function getInsertFieldData(array $params): stdClass
    {
        $to = $params['to'] ?? null;
        $parentId = $params['parentId'] ?? null;
        $parentType = $params['parentType'] ?? null;

        $result = (object) [];

        $dataList = [];

        if ($parentId && $parentType) {
            $e = $this->entityManager->getEntity($parentType, $parentId);

            if ($e && $this->acl->check($e)) {
                $dataList[] = [
                    'type' => 'parent',
                    'entity' => $e,
                ];
            }
        }

        if ($to) {
            $e = $this->getEmailAddressRepository()->getEntityByAddress($to, null, ['Contact', 'Lead', 'Account']);

            if ($e && $e->getEntityType() !== 'User' && $this->acl->check($e)) {
                $dataList[] = [
                    'type' => 'to',
                    'entity' => $e,
                ];
            }
        }

        $fm = $this->fieldUtil;

        $formatter = $this->createFormatter();

        foreach ($dataList as $item) {
            $type = $item['type'];
            $e = $item['entity'];

            $entityType = $e->getEntityType();

            $recordService = $this->recordServiceContainer->get($entityType);

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

                $value = $formatter->formatAttributeValue($e, $a);

                if ($value !== null && $value !== '') {
                    $values->$a = $value;
                }
            }

            $result->$type = (object) [
                'entityType' => $e->getEntityType(),
                'id' => $e->getId(),
                'values' => $values,
                'name' => $e->get('name'),
            ];
        }

        return $result;
    }

    private function createProcessor(): Processor
    {
        return $this->injectableFactory->create(Processor::class);
    }

    private function createFormatter(): Formatter
    {
        return $this->injectableFactory->create(Formatter::class);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
