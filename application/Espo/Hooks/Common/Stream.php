<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;
use Espo\Tools\Stream\HookProcessor;

class Stream
{
    public static $order = 9;

    private $processor;

    public function __construct(HookProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function afterSave(Entity $entity, array $options): void
    {
        $this->processor->afterSave($entity, $options);
    }

    public function afterRemove(Entity $entity): void
    {
        $this->processor->afterRemove($entity);
    }

    public function afterRelate(Entity $entity, array $options, array $data): void
    {
        $link = $data['relationName'] ?? null;
        $foreignEntity = $data['foreignEntity'] ?? null;

        if (!$link || !$foreignEntity instanceof Entity) {
            return;
        }

        $this->processor->afterRelate($entity, $foreignEntity, $link, $options);
    }

    public function afterUnrelate(Entity $entity, array $options, array $data): void
    {
        $link = $data['relationName'] ?? null;
        $foreignEntity = $data['foreignEntity'] ?? null;

        if (!$link || !$foreignEntity instanceof Entity) {
            return;
        }

        $this->processor->afterUnrelate($entity, $foreignEntity, $link, $options);
    }
}
