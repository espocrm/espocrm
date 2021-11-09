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

namespace Espo\Core\Notificators;

use Espo\ORM\Entity;

use Espo\Entities\User;

use Espo\Core\Notification\AssignmentNotificator\Params;

use Espo\Core\{
    ORM\EntityManager,
    Notification\DefaultAssignmentNotificator
};

/**
 * @deprecated
 */
class DefaultNotificator
{
    protected $entityType;

    protected $user;

    protected $entityManager;

    private $base;

    public function __construct(User $user, EntityManager $entityManager, DefaultAssignmentNotificator $base)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->base = $base;
    }

    public function process(Entity $entity, array $options = [])
    {
        $this->base->process($entity, Params::create()->withRawOptions($options));
    }

    /**
     * For backward compatibility.
      */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * For backward compatibility.
     */
    protected function getUser()
    {
        return $this->user;
    }
}
