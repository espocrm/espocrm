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

namespace Espo\Tools\Stream;

use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\Core\Utils\Acl\UserAclManagerProvider;

class NoteAccessControl
{
    public function __construct(
        private UserAclManagerProvider $userAclManagerProvider,
        private Metadata $metadata,
    ) {}

    public function apply(Note $note, User $user): void
    {
        if ($note->getType() === Note::TYPE_UPDATE && $note->getParentType()) {
            $data = $note->getData();

            $fields = $data->fields ?? [];

            $data->attributes = $data->attributes ?? (object) [];
            $data->attributes->was = $data->attributes->was ?? (object) [];
            $data->attributes->became = $data->attributes->became ?? (object) [];

            $forbiddenFieldList = $this->userAclManagerProvider
                ->get($user)
                ->getScopeForbiddenFieldList($user, $note->getParentType());

            $aclManager = $this->userAclManagerProvider->get($user);

            $forbiddenAttributeList = $aclManager->getScopeForbiddenAttributeList($user, $note->getParentType());

            $data->fields = array_values(array_diff($fields, $forbiddenFieldList));

            foreach ($forbiddenAttributeList as $attribute) {
                unset($data->attributes->was->$attribute);
                unset($data->attributes->became->$attribute);
            }

            $statusField = $this->metadata->get("scopes.{$note->getParentType()}.statusField");

            if (
                $statusField &&
                !$aclManager->checkField($user, $note->getParentType(), $statusField)
            ) {
                unset($data->value);
            }

            $note->setData($data);
        }

        if ($note->getType() === Note::TYPE_CREATE && $note->getParentType()) {
            $forbiddenFieldList = $this->userAclManagerProvider
                ->get($user)
                ->getScopeForbiddenFieldList($user, $note->getParentType());

            $data = $note->getData();

            $field = $data->statusField ?? null;

            if (in_array($field, $forbiddenFieldList)) {
                $data->statusValue = null;
                $data->statusStyle = null; // Legacy.
            }

            $note->setData($data);
        }
    }
}
