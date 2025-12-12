<?php

namespace Espo\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\Forbidden;

/**
 * @noinspection PhpUnused
 */
class CurrencyRecord extends Record
{
    public function postActionCreate(Request $request, Response $response): never
    {
        throw new Forbidden();
    }

    public function putActionUpdate(Request $request, Response $response): never
    {
        throw new Forbidden();
    }

    public function deleteActionDelete(Request $request, Response $response): never
    {
        throw new Forbidden();
    }
}
