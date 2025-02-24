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

namespace Espo\Modules\Crm\Classes\RecordHooks\MassEmail;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\ORM\Entity;

/**
 * @implements SaveHook<MassEmail>
 */
class BeforeCreate implements SaveHook
{
    public function __construct(
        private Acl $acl
    ) {}

    public function process(Entity $entity): void
    {
        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            throw new Forbidden("No 'edit' access.");
        }

        $this->checkCampaign($entity);
    }

    /**
     * @throws Forbidden
     */
    private function checkCampaign(MassEmail $entity): void
    {
        if (
            !$entity->getCampaign() || in_array($entity->getCampaign()->getType(), [
                Campaign::TYPE_EMAIL,
                Campaign::TYPE_NEWSLETTER,
                Campaign::TYPE_INFORMATIONAL_EMAIL,
            ])
        ) {
            return;
        }

        throw new Forbidden("Cannot create mass email for non-email campaign.");
    }
}
