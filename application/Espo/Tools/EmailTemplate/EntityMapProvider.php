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

namespace Espo\Tools\EmailTemplate;

use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\AclManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @since 9.2.0
 * @internal
 */
class EntityMapProvider
{
    public function __construct(
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private ServiceContainer $serviceContainer,
        private Metadata $metadata,
    ) {}

    /**
     * @return array<string, Entity>
     */
    public function get(Entity $entity, User $user, bool $applyAcl): array
    {
        /** @var array<string, string> $map */
        $map = $this->metadata->get("app.emailTemplate.entityLinkMapping.{$entity->getEntityType()}") ?? [];

        $output = [];

        foreach ($map as $entityType => $link) {
            $related = $this->getRelated(
                entity: $entity,
                link: $link,
                user: $user,
                applyAcl: $applyAcl,
            );

            if ($related) {
                $output[$entityType] = $related;
            }
        }

        return $output;
    }

    private function getRelated(
        Entity $entity,
        string $link,
        User $user,
        bool $applyAcl,
    ): ?Entity {

        $entityDefs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $forbiddenLinkList = $this->aclManager->getScopeRestrictedLinkList(
            $entity->getEntityType(),
            [
                GlobalRestriction::TYPE_FORBIDDEN,
                GlobalRestriction::TYPE_INTERNAL,
                GlobalRestriction::TYPE_ONLY_ADMIN,
            ]
        );

        if ($applyAcl) {
            if (
                $entityDefs->hasField($link) &&
                !$this->aclManager->checkField($user, $entity->getEntityType(), $link)
            ) {
                return null;
            }

            if (in_array($link, $forbiddenLinkList)) {
                return null;
            }
        }

        $related = $this->entityManager
            ->getRelation($entity, $link)
            ->findOne();

        if (!$related) {
            return null;
        }

        if (
            $applyAcl &&
            !$this->aclManager->checkEntityRead($user, $related)
        ) {
            return null;
        }

        $this->serviceContainer
            ->get($related->getEntityType())
            ->loadAdditionalFields($related);

        return $related;
    }
}
