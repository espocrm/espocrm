<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils;

class ControllerUtil
{
    public static function fetchListParamsFromRequest(&$params, $request, $data)
    {
        $params['where'] = $request->get('where');
        $params['maxSize'] = $request->get('maxSize');
        $params['offset'] = $request->get('offset');

        if ($request->get('orderBy')) {
            $params['orderBy'] = $request->get('orderBy');
        } else if ($request->get('sortBy')) {
            $params['orderBy'] = $request->get('sortBy');
        }

        if ($request->get('order')) {
            $params['order'] = $request->get('order');
        } else if ($request->get('asc')) {
            $params['order'] = $request->get('asc') === 'true' ? 'asc' : 'desc';
        }

        if ($request->get('q')) {
            $params['q'] = trim($request->get('q'));
        }
        if ($request->get('textFilter')) {
            $params['textFilter'] = $request->get('textFilter');
        }
        if ($request->get('primaryFilter')) {
            $params['primaryFilter'] = $request->get('primaryFilter');
        }
        if ($request->get('boolFilterList')) {
            $params['boolFilterList'] = $request->get('boolFilterList');
        }
        if ($request->get('filterList')) {
            $params['filterList'] = $request->get('filterList');
        }

        if ($request->get('select')) {
            $params['select'] = explode(',', $request->get('select'));
        }
    }
}
