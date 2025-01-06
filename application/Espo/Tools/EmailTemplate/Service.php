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

namespace Espo\Tools\EmailTemplate;

use Espo\Core\Acl;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\EmailTemplate;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class Service
{
    public function __construct(
        private Processor $processor,
        private User $user,
        private Acl $acl,
        private EntityManager $entityManager
    ) {}

    /**
     * Prepare an email data with an applied template.
     *
     * @throws NotFound
     * @throws ForbiddenSilent
     */
    public function process(string $emailTemplateId, Data $data, ?Params $params = null): Result
    {
        /** @var ?EmailTemplate $emailTemplate */
        $emailTemplate = $this->entityManager->getEntityById(EmailTemplate::ENTITY_TYPE, $emailTemplateId);

        if (!$emailTemplate) {
            throw new NotFound();
        }

        $params ??= Params::create()
            ->withApplyAcl(true)
            ->withCopyAttachments(true);

        if (
            $params->applyAcl() &&
            !$this->acl->checkEntityRead($emailTemplate)
        ) {
            throw new ForbiddenSilent();
        }

        if (!$data->getUser()) {
            $data = $data->withUser($this->user);
        }

        return $this->processor->process($emailTemplate, $params, $data);
    }
}
