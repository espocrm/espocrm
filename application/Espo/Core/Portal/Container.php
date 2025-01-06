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

namespace Espo\Core\Portal;

use Espo\Core\Container\Exceptions\NotSettableException;
use Espo\Entities\Portal as PortalEntity;
use Espo\Core\Portal\Utils\Config;
use Espo\Core\Container as BaseContainer;

use Psr\Container\NotFoundExceptionInterface;

use LogicException;

class Container extends BaseContainer
{
    private const ID_PORTAL = 'portal';
    private const ID_CONFIG = 'config';
    private const ID_ACL_MANAGER = 'aclManager';

    private bool $portalIsSet = false;

    /**
     * @throws NotSettableException
     */
    public function setPortal(PortalEntity $portal): void
    {
        if ($this->portalIsSet) {
            throw new NotSettableException("Can't set portal second time.");
        }

        $this->portalIsSet = true;

        $this->setForced(self::ID_PORTAL, $portal);

        $data = [];

        foreach ($portal->getSettingsAttributeList() as $attribute) {
            $data[$attribute] = $portal->get($attribute);
        }

        try {
            /** @var Config $config */
            $config = $this->get(self::ID_CONFIG);
            $config->setPortalParameters($data);

            /** @var AclManager $aclManager */
            $aclManager = $this->get(self::ID_ACL_MANAGER);
        } catch (NotFoundExceptionInterface) {
            throw new LogicException();
        }

        $aclManager->setPortal($portal);
    }
}
