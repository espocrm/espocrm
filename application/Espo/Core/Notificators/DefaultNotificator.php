<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Notificators;

use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\Notification\AssignmentNotificator\Params;
use Espo\Core\Notification\DefaultAssignmentNotificator;
use Espo\Core\ORM\EntityManager;

/**
 * @deprecated As of v7.0. Use plain classes that implement `Espo\Core\Notification\AssignmentNotificator`.
 * @todo Remove in v9.0.
 */
class DefaultNotificator
{
    protected $entityType; /** @phpstan-ignore-line */
    protected $user; /** @phpstan-ignore-line */
    protected $entityManager; /** @phpstan-ignore-line */
    private $base; /** @phpstan-ignore-line */

    public function __construct(User $user, EntityManager $entityManager, DefaultAssignmentNotificator $base)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->base = $base;
    }

    public function process(Entity $entity, array $options = []) /** @phpstan-ignore-line */
    {
        $this->base->process($entity, Params::create()->withRawOptions($options));
    }

    /**
     * For backward compatibility.
      */
    protected function getEntityManager() /** @phpstan-ignore-line */
    {
        return $this->entityManager;
    }

    /**
     * For backward compatibility.
     */
    protected function getUser() /** @phpstan-ignore-line */
    {
        return $this->user;
    }
}
