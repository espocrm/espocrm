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

namespace Espo\Tools\LinkManager\Hook\Hooks;

use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Tools\LinkManager\Hook\DeleteHook;
use Espo\Tools\LinkManager\Params;
use Espo\Tools\LinkManager\Type;
use Espo\Modules\Crm\Entities\TargetList;

use Espo\Core\Utils\Metadata;

class TargetListDelete implements DeleteHook
{
    public function __construct(private Metadata $metadata)
    {}

    public function process(Params $params): void
    {
        $toProcess =
            (
                $params->getEntityType() === TargetList::ENTITY_TYPE ||
                $params->getForeignEntityType() === TargetList::ENTITY_TYPE
            ) &&
            $params->getType() === Type::MANY_TO_MANY;

        if (!$toProcess) {
            return;
        }

        [$entityType, $link, $foreignLink] = $params->getEntityType() === TargetList::ENTITY_TYPE ?
            [
                $params->getForeignEntityType(),
                $params->getForeignLink(),
                $params->getLink(),
            ] :
            [
                $params->getEntityType(),
                $params->getLink(),
                $params->getForeignLink(),
            ];

        if (!$entityType) {
            return;
        }

        $type = $this->metadata->get(['scopes', $entityType, 'type']);

        if (!in_array($type, [Person::TEMPLATE_TYPE, Company::TEMPLATE_TYPE])) {
            return;
        }

        if ($link !== 'targetLists') {
            return;
        }

        $this->processInternal($entityType, $link, $foreignLink);
    }

    private function processInternal(string $entityType, string $link, string $foreignLink): void
    {
        $this->metadata->delete('entityDefs', $entityType, ['fields.targetListIsOptedOut']);

        $targetLinkList = $this->metadata->get(['scopes', TargetList::ENTITY_TYPE, 'targetLinkList']) ?? [];

        if (in_array($foreignLink, $targetLinkList)) {
            $targetLinkList = array_diff($targetLinkList, [$foreignLink]);

            $this->metadata->set('scopes', TargetList::ENTITY_TYPE, [
                'targetLinkList' => $targetLinkList,
            ]);
        }

        $this->metadata->delete('clientDefs', TargetList::ENTITY_TYPE, ["relationshipPanels.$foreignLink"]);
        $this->metadata->delete('recordDefs', TargetList::ENTITY_TYPE, ["relationships.$foreignLink"]);

        $this->metadata->save();
    }
}
