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

namespace Espo\Classes\FieldProcessing\Email;

use Espo\ORM\Entity;
use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;
use Espo\Repositories\Email as EmailRepository;
use Espo\Entities\Email;

/**
 * @implements Loader<Email>
 */
class AddressDataLoader implements Loader
{
    public function __construct(private EntityManager $entityManager)
    {}

    /**
     * @param Email $entity
     */
    public function process(Entity $entity, Params $params): void
    {
        /** @var EmailRepository $repository */
        $repository = $this->entityManager->getRepository(Email::ENTITY_TYPE);

        $repository->loadFromField($entity);
        $repository->loadToField($entity);
        $repository->loadCcField($entity);
        $repository->loadBccField($entity);
        $repository->loadReplyToField($entity);
        $repository->loadNameHash($entity);
    }
}
