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

namespace Espo\Modules\Crm\Services;

use Espo\ORM\Entity;
use Espo\Modules\Crm\Entities\TargetList as TargetListEntity;
use Espo\Services\Record;
use Espo\Core\Utils\Metadata;

/**
 * @extends Record<TargetListEntity>
 */
class TargetList extends Record
{
    public function setMetadata(Metadata $metadata): void
    {
        parent::setMetadata($metadata);

        $targetLinkList = $this->metadata->get(['scopes', 'TargetList', 'targetLinkList']) ?? [];

        $this->duplicatingLinkList = $targetLinkList;
        $this->noEditAccessRequiredLinkList = $targetLinkList;

        foreach ($targetLinkList as $link) {
            /** @var string $link */
            $this->linkMandatorySelectAttributeList[$link] = ['targetListIsOptedOut'];
        }
    }

    /**
     * @todo Don't use additionalColumnsConditions.
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity): void
    {
        $repository = $this->getRepository();

        foreach ($this->duplicatingLinkList as $link) {
            $linkedList = $repository
                ->getRelation($duplicatingEntity, $link)
                ->where(['@relation.optedOut' => false])
                ->find();

            foreach ($linkedList as $linked) {
                $repository
                    ->getRelation($entity, $link)
                    ->relate($linked, ['optedOut' => false]);
            }
        }
    }
}
