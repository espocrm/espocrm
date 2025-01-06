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

namespace Espo\Tools\DataPrivacy;

use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FieldProcessing\EmailAddress\AccessChecker as EmailAddressAccessChecker;
use Espo\Core\FieldProcessing\PhoneNumber\AccessChecker as PhoneNumberAccessChecker;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;

use Espo\Core\Di;
use Espo\Entities\Attachment;

class Erasor implements

    Di\AclAware,
    Di\AclManagerAware,
    Di\MetadataAware,
    Di\ServiceFactoryAware,
    Di\EntityManagerAware,
    Di\FieldUtilAware,
    Di\UserAware
{
    use Di\AclSetter;
    use Di\AclManagerSetter;
    use Di\MetadataSetter;
    use Di\ServiceFactorySetter;
    use Di\EntityManagerSetter;
    use Di\FieldUtilSetter;
    use Di\UserSetter;

    public function __construct(
        private RecordServiceContainer $recordServiceContainer,
        private EmailAddressAccessChecker $emailAddressAccessChecker,
        private PhoneNumberAccessChecker $phoneNumberAccessChecker
    ) {}

    /**
     * @param string[] $fieldList
     * @throws Forbidden
     * @throws NotFound
     */
    public function erase(string $entityType, string $id, array $fieldList): void
    {
        if ($this->acl->getPermissionLevel(Permission::DATA_PRIVACY) === Table::LEVEL_NO) {
            throw new Forbidden();
        }

        $service = $this->recordServiceContainer->get($entityType);

        $entity = $this->entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access.");
        }

        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($entityType, Table::ACTION_EDIT);

        foreach ($fieldList as $field) {
            if (in_array($field, $forbiddenFieldList)) {
                throw new Forbidden("Field '$field' is forbidden to edit.");
            }
        }

        $service->loadAdditionalFields($entity);

        $fieldUtil = $this->fieldUtil;

        foreach ($fieldList as $field) {
            $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

            $attributeList = $fieldUtil->getActualAttributeList($entityType, $field);

            if ($type === FieldType::EMAIL) {
                $emailAddressList = $entity->get('emailAddresses');

                foreach ($emailAddressList as $emailAddress) {
                    if (
                        $this->emailAddressAccessChecker
                            ->checkEdit($this->user, $emailAddress, $entity)
                    ) {
                        $emailAddress->set(Field::NAME, 'ERASED:' . $emailAddress->id);
                        $emailAddress->set('optOut', true);
                        $this->entityManager->saveEntity($emailAddress);
                    }
                }

                $entity->clear($field);
                $entity->clear($field . 'Data');

                continue;
            } else if ($type === FieldType::PHONE) {
                $phoneNumberList = $entity->get('phoneNumbers');

                foreach ($phoneNumberList as $phoneNumber) {
                    if (
                        $this->phoneNumberAccessChecker
                            ->checkEdit($this->user, $phoneNumber, $entity)
                    ) {
                        $phoneNumber->set(Field::NAME, 'ERASED:' . $phoneNumber->id);

                        $this->entityManager->saveEntity($phoneNumber);
                    }
                }

                $entity->clear($field);
                $entity->clear($field . 'Data');

                continue;
            } else if ($type === FieldType::FILE || $type === FieldType::IMAGE) {
                $attachmentId = $entity->get($field . 'Id');

                if ($attachmentId) {
                    $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $attachmentId);

                    if ($attachment) {
                        $this->entityManager->removeEntity($attachment);
                    }
                }
            } else if ($type === FieldType::ATTACHMENT_MULTIPLE) {
                $attachmentList = $entity->get($field);

                foreach ($attachmentList as $attachment) {
                    $this->entityManager->removeEntity($attachment);
                }
            }

            foreach ($attributeList as $attribute) {
                if (
                    in_array($entity->getAttributeType($attribute), [$entity::VARCHAR, $entity::TEXT]) &&
                    $entity->get($attribute)
                ) {
                    $entity->set($attribute, null);
                } else {
                    $entity->set($attribute, null);
                }
            }
        }

        $this->entityManager->saveEntity($entity);
    }
}
