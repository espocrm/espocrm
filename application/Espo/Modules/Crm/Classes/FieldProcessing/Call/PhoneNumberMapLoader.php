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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Call;

use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;

use Espo\ORM\Name\Attribute;
use stdClass;

/**
 * @implements Loader<Call>
 */
class PhoneNumberMapLoader implements Loader
{
    private const ERASED_PART = 'ERASED:';

    public function __construct(private EntityManager $entityManager)
    {}

    public function process(Entity $entity, Params $params): void
    {
        $map = (object) [];

        assert($entity instanceof CoreEntity);

        $contactIdList = $entity->getLinkMultipleIdList(Meeting::LINK_CONTACTS);

        if (count($contactIdList)) {
            $this->populate($map, Contact::ENTITY_TYPE, $contactIdList);
        }

        $leadIdList = $entity->getLinkMultipleIdList(Meeting::LINK_LEADS);

        if (count($leadIdList)) {
            $this->populate($map, Lead::ENTITY_TYPE, $leadIdList);
        }

        $entity->set('phoneNumbersMap', $map);
    }

    /**
     * @param string[] $idList
     */
    private function populate(stdClass $map, string $entityType, array $idList): void
    {
        $entityList = $this->entityManager
            ->getRDBRepository($entityType)
            ->where([
                Attribute::ID => $idList,
            ])
            ->select([Attribute::ID, 'phoneNumber'])
            ->find();

        foreach ($entityList as $entity) {
            $phoneNumber = $entity->get('phoneNumber');

            if (!$phoneNumber) {
                continue;
            }

            if (str_starts_with($phoneNumber, self::ERASED_PART)) {
                continue;
            }

            $key = $entity->getEntityType() . '_' . $entity->getId();

            $map->$key = $phoneNumber;
        }
    }
}
