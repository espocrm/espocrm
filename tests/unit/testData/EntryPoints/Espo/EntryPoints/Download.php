<?php

namespace tests\unit\testData\EntryPoints\Espo\EntryPoints;


use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;

class Download implements EntryPoint
{
    public function run(Request $request, Response $response): void
    {}
}

