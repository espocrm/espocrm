<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Api;

use Psr\Http\Message\UriInterface;

interface Request
{
    /**
     * Get a query parameter.
     */
    public function get(string $name);

    /**
     * Get a header value.
     */
    public function getHeader(string $name) : ?string;

    /**
     * Whether a header is set.
     */
    public function hasHeader(string $name) : bool;

    /**
     * Get a request method.
     */
    public function getMethod() : string;

    /**
     * Get Uri.
     */
    public function getUri() : UriInterface;

    /**
     * Get a relative path of a request (w/o base path).
     */
    public function getResourcePath() : string;

    /**
     * Get body contents.
     */
    public function getBodyContents() : ?string;

    /**
     * Get a cookie param value.
     */
    public function getCookieParam(string $name) : ?string;

    /**
     * Get a server param value.
     */
    public function getServerParam(string $name);
}
