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

namespace Espo\Hooks\Portal;

use Espo\ORM\Entity;

use Espo\Entities\Portal;

use Espo\Core\{
    Utils\Config,
    Utils\Config\ConfigWriter,
};

class WriteConfig
{
    private $config;

    private $configWriter;

    public function __construct(Config $config, ConfigWriter $configWriter)
    {
        $this->config = $config;
        $this->configWriter = $configWriter;
    }

    /**
     * @param Portal $entity
     */
    public function afterSave(Entity $entity)
    {
        if (!$entity->has('isDefault')) {
            return;
        }

        if ($entity->get('isDefault')) {
            $defaultPortalId = $this->config->get('defaultPortalId');

            if ($defaultPortalId === $entity->getId()) {
                return;
            }

            $this->configWriter->set('defaultPortalId', $entity->getId());

            $this->configWriter->save();
        }

        if ($entity->isAttributeChanged('isDefault') && $entity->getFetched('isDefault')) {
            $this->configWriter->set('defaultPortalId', null);

            $this->configWriter->save();
        }
    }
}
