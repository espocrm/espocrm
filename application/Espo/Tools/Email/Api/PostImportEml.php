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

namespace Espo\Tools\Email\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Tools\Email\ImportEmlService;

/**
 * @noinspection PhpUnused
 */
class PostImportEml implements Action
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private ImportEmlService $service,
    ) {}

    public function process(Request $request): Response
    {
        $this->checkAccess();

        $fileId = $request->getParsedBody()->fileId ?? null;

        if (!is_string($fileId)) {
            throw new BadRequest("No 'fileId'.");
        }

        $email = $this->service->import($fileId, $this->user->getId());

        return ResponseComposer::json(['id' => $email->getId()]);
    }

    /**
     * @throws Forbidden
     */
    private function checkAccess(): void
    {
        if (!$this->acl->checkScope(Email::ENTITY_TYPE, Acl\Table::ACTION_CREATE)) {
            throw new Forbidden("No 'create' access.");
        }

        if (!$this->acl->checkScope('Import')) {
            throw new Forbidden("No access to 'Import'.");
        }
    }
}
