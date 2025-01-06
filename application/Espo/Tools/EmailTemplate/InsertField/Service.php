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

namespace Espo\Tools\EmailTemplate\InsertField;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\Email;
use Espo\Entities\EmailAddress;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Tools\EmailTemplate\Formatter;
use stdClass;

class Service
{
    private EntityManager $entityManager;
    private Acl $acl;
    private Formatter $formatter;
    private FieldUtil $fieldUtil;
    private ServiceContainer $recordServiceContainer;

    public function __construct(
        EntityManager $entityManager,
        Acl $acl,
        Formatter $formatter,
        FieldUtil $fieldUtil,
        ServiceContainer $recordServiceContainer
    ) {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->formatter = $formatter;
        $this->fieldUtil = $fieldUtil;
        $this->recordServiceContainer = $recordServiceContainer;
    }

    /**
     * @throws Forbidden
     */
    public function getData(?string $parentType, ?string $parentId, ?string $to): stdClass
    {
        if (!$this->acl->checkScope(Email::ENTITY_TYPE, Table::ACTION_CREATE)) {
            throw new Forbidden();
        }

        $result = (object) [];

        $dataList = [];

        if ($parentId && $parentType) {
            $e = $this->entityManager->getEntityById($parentType, $parentId);

            if ($e && $this->acl->check($e)) {
                $dataList[] = [
                    'type' => 'parent',
                    'entity' => $e,
                ];
            }
        }

        if ($to) {
            $e = $this->getEmailAddressRepository()
                ->getEntityByAddress($to, null,
                    [Contact::ENTITY_TYPE, Lead::ENTITY_TYPE, Account::ENTITY_TYPE]);

            if ($e && $e->getEntityType() !== User::ENTITY_TYPE && $this->acl->check($e)) {
                $dataList[] = [
                    'type' => 'to',
                    'entity' => $e,
                ];
            }
        }

        $fm = $this->fieldUtil;

        $formatter = $this->formatter;

        foreach ($dataList as $item) {
            $type = $item['type'];
            $e = $item['entity'];

            $entityType = $e->getEntityType();

            $recordService = $this->recordServiceContainer->get($entityType);

            $recordService->loadAdditionalFields($e);
            $recordService->prepareEntityForOutput($e);

            $ignoreTypeList = [
                FieldType::IMAGE,
                FieldType::FILE,
                FieldType::WYSIWYG,
                FieldType::LINK_MULTIPLE,
                FieldType::ATTACHMENT_MULTIPLE,
                FieldType::BOOL,
                'map',
            ];

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
                'name' => $e->get(Field::NAME),
            ];
        }

        return $result;
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
