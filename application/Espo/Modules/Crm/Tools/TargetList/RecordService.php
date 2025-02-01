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

namespace Espo\Modules\Crm\Tools\TargetList;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use RuntimeException;

class RecordService
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private HookManager $hookManager,
        private MetadataProvider $metadataProvider
    ) {}

    /**
     * Unlink all targets.
     *
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function unlinkAll(string $id, string $link): void
    {
        $entity = $this->getEntity($id);

        $linkEntityType = $this->getLinkEntityType($entity, $link);

        $updateQuery = $this->entityManager->getQueryBuilder()
            ->update()
            ->in($linkEntityType)
            ->set([Attribute::DELETED => true])
            ->where(['targetListId' => $entity->getId()])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);

        $this->hookManager->process(TargetList::ENTITY_TYPE, 'afterUnlinkAll', $entity, [], ['link' => $link]);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function getEntity(string $id): TargetList
    {
        $entity = $this->entityManager->getRDBRepositoryByClass(TargetList::class)->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            throw new Forbidden();
        }

        return $entity;
    }

    /**
     * @throws BadRequest
     */
    private function getLinkEntityType(TargetList $entity, string $link): string
    {
        if (!in_array($link, $this->metadataProvider->getTargetLinkList())) {
            throw new BadRequest("Not supported link.");
        }

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (!$foreignEntityType) {
            throw new RuntimeException();
        }

        $linkEntityType = ucfirst($entity->getRelationParam($link, RelationParam::RELATION_NAME) ?? '');

        if ($linkEntityType === '') {
            throw new RuntimeException();
        }

        return $linkEntityType;
    }
}
