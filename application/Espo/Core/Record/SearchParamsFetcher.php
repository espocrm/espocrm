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

namespace Espo\Core\Record;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Utils\Config,
    Api\Request,
    Select\SearchParams,
    Utils\Json,
};

use JsonException;

class SearchParamsFetcher
{
    private const MAX_SIZE_LIMIT = 200;

    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function fetch(Request $request): SearchParams
    {
        return SearchParams::fromRaw(
            $this->fetchRaw($request)
        );
    }

    private function fetchRaw(Request $request): array
    {
        $params = $request->hasQueryParam('searchParams') ?
            $this->fetchRawJsonSearchParams($request):
            $this->fetchRawMultipleParams($request);

        $this->handleRawParams($params);

        return $params;
    }

    private function fetchRawJsonSearchParams(Request $request): array
    {
        try {
            return Json::decode($request->getQueryParam('searchParams'), true);
        }
        catch (JsonException $e) {
            throw new BadRequest("Invalid search params JSON.");
        }
    }

    private function fetchRawMultipleParams(Request $request): array
    {
        $params = [];

        $params['where'] = $request->getQueryParam('where');
        $params['maxSize'] = $request->getQueryParam('maxSize');
        $params['offset'] = $request->getQueryParam('offset');

        if ($params['maxSize'] === '') {
            $params['maxSize'] = null;
        }

        if ($params['offset'] === '') {
            $params['offset'] = null;
        }

        if ($params['maxSize'] !== null) {
            $params['maxSize'] = intval($params['maxSize']);
        }

        if ($params['offset'] !== null) {
            $params['offset'] = intval($params['offset']);
        }

        if ($request->getQueryParam('orderBy')) {
            $params['orderBy'] = $request->getQueryParam('orderBy');
        }
        else if ($request->getQueryParam('sortBy')) {
            // legacy
            $params['orderBy'] = $request->getQueryParam('sortBy');
        }

        if ($request->getQueryParam('order')) {
            $params['order'] = strtoupper($request->getQueryParam('order'));
        }
        else if ($request->getQueryParam('asc')) {
            // legacy
            $params['order'] = $request->getQueryParam('asc') === 'true' ?
                SearchParams::ORDER_ASC : SearchParams::ORDER_DESC;
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

    private function handleRawParams(array &$params): void
    {
        if (isset($params['maxSize']) && !is_int($params['maxSize'])) {
            throw new BadRequest('maxSize must be integer.');
        }

        $this->handleMaxSize($params);
    }

    private function handleMaxSize(array &$params): void
    {
        $value = $params['maxSize'];

        $limit = $this->config->get('recordListMaxSizeLimit') ?? self::MAX_SIZE_LIMIT;

        if ($value === null) {
            $params['maxSize'] = $limit;
        }

        if ($value > $limit) {
            throw new Forbidden(
                "Max size should not exceed " . $limit . ". Use offset and limit."
            );
        }
    }
}
