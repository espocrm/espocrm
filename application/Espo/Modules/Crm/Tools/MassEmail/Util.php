<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;

use Espo\Core\Exceptions\Error;

use Espo\Modules\Crm\Entities\TargetList;

class Util
{
    private Defs $ormDefs;

    private Metadata $metadata;

    /**
     * @var string[]
     */
    private array $targetLinkList;

    public function __construct(Defs $ormDefs, Metadata $metadata)
    {
        $this->ormDefs = $ormDefs;
        $this->metadata = $metadata;

        $this->targetLinkList = $this->metadata->get(['scopes', 'TargetList', 'targetLinkList']) ?? [];
    }

    public function getLinkByEntityType(string $entityType): string
    {
        foreach ($this->targetLinkList as $link) {
            $itemEntityType = $this->ormDefs
                ->getEntity(TargetList::ENTITY_TYPE)
                ->getRelation($link)
                ->getForeignEntityType();

            if ($itemEntityType === $entityType) {
                return $link;
            }
        }

        throw new Error();
    }
}
