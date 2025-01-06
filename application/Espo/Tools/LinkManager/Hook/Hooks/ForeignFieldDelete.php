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

use Espo\Core\ORM\Type\FieldType;
use Espo\Tools\LinkManager\Hook\DeleteHook;
use Espo\Tools\LinkManager\Params;
use Espo\Core\Utils\Metadata;

use Espo\ORM\Defs;

class ForeignFieldDelete implements DeleteHook
{
    public function __construct(
        private Metadata $metadata,
        private Defs $defs
    ) {}

    public function process(Params $params): void
    {
        $this->processInternal($params->getEntityType(), $params->getLink());

        if ($params->getForeignEntityType()) {
            $this->processInternal($params->getForeignEntityType(), $params->getForeignLink());
        }
    }

    private function processInternal(string $entityType, string $link): void
    {
        if (!$this->defs->hasEntity($entityType)) {
            return;
        }

        foreach ($this->defs->getEntity($entityType)->getFieldList() as $fieldDefs) {
            if ($fieldDefs->getType() !== FieldType::FOREIGN) {
                continue;
            }

            if ($fieldDefs->getParam('link') === $link) {
                $this->deleteForeignField($entityType, $fieldDefs->getName());
            }
        }
    }

    private function deleteForeignField(string $entityType, string $field): void
    {
        $this->metadata->delete('entityDefs', $entityType, ['fields.' . $field]);

        $this->metadata->save();
    }
}
