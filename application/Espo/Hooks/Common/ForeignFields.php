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

namespace Espo\Hooks\Common;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\ORM\Type\AttributeType;

/**
 * @implements AfterSave<Entity>
 * @noinspection PhpUnused
 */
class ForeignFields implements AfterSave
{
    public static int $order = 8;

    public function __construct(
        private Defs $defs,
        private EntityManager $entityManager
    ) {}


    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$options->get(SaveOption::API) || $entity->isNew()) {
            return;
        }

        $defs = $this->defs->getEntity($entity->getEntityType());

        $foreignList = array_filter(
            $entity->getAttributeList(), fn ($it) => $entity->getAttributeType($it) === AttributeType::FOREIGN);

        $relationList = array_map(
            fn ($it) => $defs->getAttribute($it)->getParam(AttributeParam::RELATION), $foreignList);
        $relationList = array_filter($relationList, fn ($it) => $entity->isAttributeChanged($it . 'Id'));
        $relationList = array_values($relationList);

        if ($relationList === []) {
            return;
        }

        $copy = $this->entityManager->getEntityById($entity->getEntityType(), $entity->getId());

        if (!$copy) {
            return;
        }

        foreach ($foreignList as $attribute) {
            $entity->set($attribute, $copy->get($attribute));
        }
    }
}
