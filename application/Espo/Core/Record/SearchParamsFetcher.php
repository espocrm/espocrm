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

namespace Espo\Core\Record;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Config;
use Espo\Core\Api\Request;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Text\MetadataProvider as TextMetadataProvider;
use Espo\Core\Utils\Json;

use InvalidArgumentException;
use JsonException;

class SearchParamsFetcher
{
    private const MAX_SIZE_LIMIT = 200;

    public function __construct(
        private Config $config,
        private TextMetadataProvider $textMetadataProvider
    ) {}

    /**
     * Fetch search params from a request.
     *
     * @throws BadRequest
     * @throws Forbidden
     */
    public function fetch(Request $request): SearchParams
    {
        try {
            return SearchParams::fromRaw($this->fetchRaw($request));
        } catch (InvalidArgumentException $e) {
            throw new BadRequest($e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     * @throws Forbidden
     */
    private function fetchRaw(Request $request): array
    {
        $params = $request->hasQueryParam('searchParams') ?
            $this->fetchRawJsonSearchParams($request):
            $this->fetchRawMultipleParams($request);

        $this->handleRawParams($params, $request);

        return $params;
    }

    /**
     * @return array<string, mixed>
     * @throws BadRequest
     */
    private function fetchRawJsonSearchParams(Request $request): array
    {
        try {
            return Json::decode($request->getQueryParam('searchParams') ?? '', true);
        } catch (JsonException) {
            throw new BadRequest("Invalid search params JSON.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRawMultipleParams(Request $request): array
    {
        $params = [];

        $queryParams = $request->getQueryParams();

        $params['where'] = $queryParams['whereGroup'] ?? $queryParams['where'] ?? null;

        if ($params['where'] !== null && !is_array($params['where'])) {
            $params['where'] = null;
        }

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
        } else if ($request->getQueryParam('sortBy')) {
            // legacy
            $params['orderBy'] = $request->getQueryParam('sortBy');
        }

        if ($request->getQueryParam('order')) {
            $params['order'] = strtoupper($request->getQueryParam('order'));
        } else if ($request->getQueryParam('asc')) {
            // legacy
            $params['order'] = $request->getQueryParam('asc') === 'true' ?
                SearchParams::ORDER_ASC : SearchParams::ORDER_DESC;
        }

        $q = $request->getQueryParam('q');

        if ($q) {
            $params['q'] = trim($q);
        }

        if ($request->getQueryParam('textFilter')) {
            $params['textFilter'] = $request->getQueryParam('textFilter');
        }

        if ($request->getQueryParam('primaryFilter')) {
            $params['primaryFilter'] = $request->getQueryParam('primaryFilter');
        }

        if ($queryParams['boolFilterList'] ?? null) {
            $params['boolFilterList'] = (array) $queryParams['boolFilterList'];
        }

        if ($queryParams['filterList'] ?? null) {
            $params['filterList'] = (array) $queryParams['filterList'];
        }

        $select = $request->getQueryParam('attributeSelect') ?? $request->getQueryParam('select');

        if ($select) {
            $params['select'] = explode(',', $select);
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     * @throws BadRequest
     * @throws Forbidden
     */
    private function handleRawParams(array &$params, Request $request): void
    {
        if (isset($params['maxSize']) && !is_int($params['maxSize'])) {
            throw new BadRequest('maxSize must be integer.');
        }

        $this->handleQ($params, $request);
        $this->handleMaxSize($params);
    }

    private function hasFullTextSearch(Request $request): bool
    {
        $scope = $request->getRouteParam('controller');

        if (!$scope) {
            return false;
        }

        if ($request->getRouteParam('action') !== 'index') {
            return false;
        }

        return $this->textMetadataProvider->hasFullTextSearch($scope);
    }

    /**
     * @param array<string, mixed> $params
     * @throws Forbidden
     */
    private function handleMaxSize(array &$params): void
    {
        $value = $params['maxSize'] ?? null;

        $limit = $this->config->get('recordListMaxSizeLimit') ?? self::MAX_SIZE_LIMIT;

        if ($value === null) {
            $params['maxSize'] = $limit;
        }

        if ($value > $limit) {
            throw new Forbidden("Max size should not exceed $limit. Use offset and limit.");
        }
    }

    /**
     * @param array<string, mixed> $params
     * @throws BadRequest
     */
    private function handleQ(array &$params, Request $request): void
    {
        $q = $params['q'] ?? null;

        if ($q === null) {
            return;
        }

        if (!is_string($q)) {
            throw new BadRequest("q must be string.");
        }

        if (!$this->config->get('quickSearchFullTextAppendWildcard')) {
            return;
        }

        if (
            !str_contains($q, '*') &&
            !str_contains($q, '"') &&
            !str_contains($q, '+') &&
            !str_contains($q, '-') &&
            $this->hasFullTextSearch($request)
        ) {
            $params['q'] = $q . '*';
        }
    }
}
