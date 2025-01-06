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

namespace Espo\Classes\RecordHooks\Event;

use Espo\Core\Record\Hook\UpdateHook;
use Espo\Core\Record\UpdateParams;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Field\DateTime;
use Espo\Core\Field\Date;

use Espo\ORM\Entity;
use Espo\ORM\Defs as OrmDefs;

/**
 * @implements UpdateHook<CoreEntity>
 */
class BeforeUpdatePreserveDuration implements UpdateHook
{
    private OrmDefs $ormDefs;

    public function __construct(OrmDefs $ormDefs)
    {
        $this->ormDefs = $ormDefs;
    }

    public function process(Entity $entity, UpdateParams $params): void
    {
        /** @var CoreEntity $entity */

        if (!$entity->isAttributeChanged('dateStart') && !$entity->isAttributeChanged('dateStartDate')) {
            return;
        }

        if ($entity->isAttributeWritten('dateEnd') || $entity->isAttributeWritten('dateEndDate')) {
            return;
        }

        $preserveDurationDisabled = $this->ormDefs
            ->getEntity($entity->getEntityType())
            ->getField('dateEnd')
            ->getParam('preserveDurationDisabled');

        if ($preserveDurationDisabled) {
            return;
        }

        $this->processDateTime($entity);
        $this->processDate($entity);
    }

    private function processDateTime(Entity $entity): void
    {
        $dateStartFetchedString = $entity->getFetched('dateStart');
        $dateStartString = $entity->get('dateStart');
        $dateEndString = $entity->get('dateEnd');

        if (!$dateStartFetchedString || !$dateStartString || !$dateEndString) {
            return;
        }

        $dateStartFetched = DateTime::fromString($dateStartFetchedString);
        $dateStart = DateTime::fromString($dateStartString);
        $dateEnd = DateTime::fromString($dateEndString);

        $diff = $dateStartFetched->diff($dateEnd);

        $dateEndModified = $dateStart->add($diff);

        $entity->set('dateEnd', $dateEndModified->toString());
    }

    private function processDate(Entity $entity): void
    {
        $dateStartFetchedString = $entity->getFetched('dateStartDate');
        $dateStartString = $entity->get('dateStartDate');
        $dateEndString = $entity->get('dateEndDate');

        if (!$dateStartFetchedString || !$dateStartString || !$dateEndString) {
            return;
        }

        $dateStartFetched = Date::fromString($dateStartFetchedString);
        $dateStart = Date::fromString($dateStartString);
        $dateEnd = Date::fromString($dateEndString);

        $diff = $dateStartFetched->diff($dateEnd);

        $dateEndModified = $dateStart->add($diff);

        $entity->set('dateEndDate', $dateEndModified->toString());
    }
}
