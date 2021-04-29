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

namespace Espo\Core\Utils;

use Espo\Core\Api\Request;

class ControllerUtil
{
    public static function fetchSearchParamsFromRequest(Request $request): array
    {
        $params = [];

        $params['where'] = $request->getQueryParam('where');
        $params['maxSize'] = $request->getQueryParam('maxSize');
        $params['offset'] = $request->getQueryParam('offset');

        if ($params['maxSize']) {
            $params['maxSize'] = intval($params['maxSize']);
        }

        if ($params['offset']) {
            $params['offset'] = intval($params['offset']);
        }

        if ($request->getQueryParam('orderBy')) {
            $params['orderBy'] = $request->getQueryParam('orderBy');
        }
        else if ($request->getQueryParam('sortBy')) {
            $params['orderBy'] = $request->getQueryParam('sortBy');
        }

        if ($request->getQueryParam('order')) {
            $params['order'] = $request->getQueryParam('order');
        }
        else if ($request->getQueryParam('asc')) {
            $params['order'] = $request->getQueryParam('asc') === 'true' ? 'asc' : 'desc';
        }

        if ($request->getQueryParam('q')) {
            $params['q'] = trim($request->getQueryParam('q'));
        }

        if ($request->getQueryParam('textFilter')) {
            $params['textFilter'] = $request->getQueryParam('textFilter');
        }

        if ($request->getQueryParam('primaryFilter')) {
            $params['primaryFilter'] = $request->getQueryParam('primaryFilter');
        }

        if ($request->getQueryParam('boolFilterList')) {
            $params['boolFilterList'] = $request->getQueryParam('boolFilterList');
        }

        if ($request->getQueryParam('filterList')) {
            $params['filterList'] = $request->getQueryParam('filterList');
        }

        if ($request->getQueryParam('select')) {
            $params['select'] = explode(',', $request->getQueryParam('select'));
        }

        return $params;
    }
}
