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

namespace Espo\Core\Formula\Functions\EntityGroup;

use Espo\Core\Exceptions\Error;

class AddLinkMultipleIdType extends \Espo\Core\Formula\Functions\Base
{
    /**
     * @return void
     * @throws Error
     * @throws \Espo\Core\Formula\Exceptions\Error
     */
    public function process(\stdClass $item)
    {
        if (count($item->value) < 2) {
            throw new Error("addLinkMultipleId function: Too few arguments.");
        }

        $link = $this->evaluate($item->value[0]);
        $id = $this->evaluate($item->value[1]);

        if (!is_string($link)) {
            throw new Error();
        }

        if (is_array($id)) {
            $idList = $id;

            foreach ($idList as $id) {
                if (!is_string($id)) {
                    throw new Error();
                }

                $this->getEntity()->addLinkMultipleId($link, $id);
            }
        } else {
            if (!is_string($id)) {
                return;
            }

            $this->getEntity()->addLinkMultipleId($link, $id);
        }
    }
}
