<?php

namespace Espo\Tools\OpenApi\Provider;

readonly class Params
{
    public function __construct(
        public bool $skipCustom = false,
    ) {}
}
