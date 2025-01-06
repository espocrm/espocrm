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

namespace Espo\Modules\Crm\Classes\RecordHooks\TargetList;

use Espo\Core\Acl;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\Hook\CreateHook;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Tools\TargetList\MetadataProvider;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements CreateHook<TargetList>
 */
class AfterCreateDuplicate implements CreateHook
{
    public function __construct(
        private Acl $acl,
        private EntityManager $entityManager,
        private MetadataProvider $metadataProvider
    ) {}

    public function process(Entity $entity, CreateParams $params): void
    {
        $id = $params->getDuplicateSourceId();

        if (!$id) {
            return;
        }

        $sourceEntity = $this->entityManager->getRDBRepositoryByClass(TargetList::class)->getById($id);

        if (!$sourceEntity) {
            return;
        }

        if (!$this->acl->check($sourceEntity, Acl\Table::ACTION_READ)) {
            return;
        }

        $this->duplicateLinks($entity, $sourceEntity);
    }

    private function duplicateLinks(TargetList $entity, TargetList $sourceEntity): void
    {
        $repository = $this->entityManager->getRDBRepositoryByClass(TargetList::class);

        foreach ($this->metadataProvider->getTargetLinkList() as $link) {
            $collection = $repository
                ->getRelation($sourceEntity, $link)
                ->where(['@relation.optedOut' => false])
                ->find();

            foreach ($collection as $relatedEntity) {
                $repository
                    ->getRelation($entity, $link)
                    ->relate($relatedEntity, ['optedOut' => false]);
            }
        }
    }
}
