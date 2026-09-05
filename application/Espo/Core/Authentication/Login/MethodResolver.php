<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Authentication\Login;

use Espo\Core\Api\Request;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Authentication\HeaderKey;

/**
 * @internal
 *
 * @todo Test.
 */
class MethodResolver
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
    ) {}

    public function resolve(Request $request): ?string
    {
        if ($request->hasHeader(HeaderKey::AUTHORIZATION)) {
            return null;
        }

        $paramsList = $this->configDataProvider->getLoginMetadataParamsList();

        $paramsList = array_filter($paramsList, function ($params) use ($request): bool {
            $headerName = $params->getCredentialsHeader();

            if (
                !$params->isApi() ||
                !$headerName ||
                !$request->hasHeader($headerName)
            ) {
                return false;
            }

            $headerValue = $request->getHeader($headerName) ?? '';

            $scheme = $params->getCredentialsHeaderScheme();

            if ($scheme !== null && !str_starts_with($headerValue, $scheme . ' ')) {
                return false;
            }

            return true;
        });

        $paramsList = array_values($paramsList);

        if (count($paramsList)) {
            return $paramsList[0]->getMethod();
        }

        return null;
    }
}
