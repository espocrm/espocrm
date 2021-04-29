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

namespace Espo\Core\Controllers;

use Espo\Core\Exceptions\Forbidden;

use Espo\Core\{
    Api\Request,
};

use StdClass;

class RecordTree extends RecordBase
{
    public static $defaultAction = 'list';

    /**
     * Get a category tree.
     */
    public function getActionListTree(Request $request): StdClass
    {
        if (method_exists($this, 'actionListTree')) {
            // For backward compatibility.
            return (object) $this->actionListTree($request->getRouteParams(), $request->getParsedBody(), $request);
        }

        $where = $request->get('where');
        $parentId = $request->get('parentId');
        $maxDepth = $request->get('maxDepth');
        $onlyNotEmpty = $request->get('onlyNotEmpty');

        $collection = $this->getRecordService()->getTree(
            $parentId,
            [
                'where' => $where,
                'onlyNotEmpty' => $onlyNotEmpty,
            ],
            $maxDepth
        );

        return (object) [
            'list' => $collection->getValueMapList(),
            'path' => $this->getRecordService()->getTreeItemPath($parentId),
        ];
    }

    public function getActionLastChildrenIdList($params, $data, $request): array
    {
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        $parentId = $request->get('parentId');

        return $this->getRecordService()->getLastChildrenIdList($parentId);
    }
}
