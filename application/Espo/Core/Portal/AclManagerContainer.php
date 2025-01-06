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

use Espo\Entities\Portal;
use Espo\Core\InjectableFactory;

use LogicException;

/**
 * Used when logged to CRM (not to portal) to provide an access checking ability for a specific portal.
 * E.g. check whether a portal user has access to some record within a specific portal.
 */
class AclManagerContainer
{
    /**
     * @var array<string, AclManager>
     */
    private $data = [];

    public function __construct(private InjectableFactory $injectableFactory)
    {}

    public function get(Portal $portal): AclManager
    {
        if (!$portal->hasId()) {
            throw new LogicException("AclManagerContainer: portal should have ID.");
        }

        $id = $portal->getId();

        if (!isset($this->data[$id])) {
            $aclManager = $this->injectableFactory->create(AclManager::class);
            $aclManager->setPortal($portal);

            $this->data[$id] = $aclManager;
        }

        return $this->data[$id];
    }
}
