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

namespace Espo\Tools\Attachment;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\Entities\Settings;
use Espo\Entities\User;
use Espo\Core\ORM\Type\FieldType;

class AccessChecker
{
    /** @var string[] */
    private $adminOnlyHavingInlineAttachmentsEntityTypeList = ['TemplateManager'];

    /** @var string[] */
    private $attachmentFieldTypeList = [
        FieldType::FILE,
        FieldType::IMAGE,
        FieldType::ATTACHMENT_MULTIPLE,
    ];

    /** @var string[] */
    private $inlineAttachmentFieldTypeList = [
        FieldType::WYSIWYG,
    ];

    /** @var string[] */
    private $allowedRoleList = [
        Attachment::ROLE_ATTACHMENT,
        Attachment::ROLE_INLINE_ATTACHMENT,
    ];

    public function __construct(
        private User $user,
        private Acl $acl,
        private Metadata $metadata
    ) {}

    /**
     * Check access to a field and role allowance.
     *
     * @throws Forbidden
     */
    public function check(FieldData $fieldData, string $role = Attachment::ROLE_ATTACHMENT): void
    {
        if (!in_array($role, $this->allowedRoleList)) {
            throw new Forbidden("Role not allowed.");
        }

        $relatedEntityType = $fieldData->getParentType() ?? $fieldData->getRelatedType();
        $field = $fieldData->getField();

        if (!$relatedEntityType) {
            throw new Forbidden();
        }

        if (
            $this->user->isAdmin() &&
            $role === Attachment::ROLE_INLINE_ATTACHMENT &&
            in_array($relatedEntityType, $this->adminOnlyHavingInlineAttachmentsEntityTypeList)
        ) {
            return;
        }

        $fieldType = $this->metadata->get(['entityDefs', $relatedEntityType, 'fields', $field, 'type']);

        if (!$fieldType) {
            throw new Forbidden("Field '$field' does not exist.");
        }

        $fieldTypeList = $role === Attachment::ROLE_INLINE_ATTACHMENT ?
            $this->inlineAttachmentFieldTypeList :
            $this->attachmentFieldTypeList;

        if (!in_array($fieldType, $fieldTypeList)) {
            throw new Forbidden("Field type '$fieldType' is not allowed for $role.");
        }

        if ($this->user->isAdmin() && $relatedEntityType === Settings::ENTITY_TYPE) {
            return;
        }

        if (
            !$this->acl->checkScope($relatedEntityType, Table::ACTION_CREATE) &&
            !$this->acl->checkScope($relatedEntityType, Table::ACTION_EDIT)
        ) {
            throw new Forbidden("No access to " . $relatedEntityType . ".");
        }

        if (!$this->acl->checkField($relatedEntityType, $field, Table::ACTION_EDIT)) {
            throw new Forbidden("No access to field '$field'.");
        }
    }
}
