<?php

namespace Espo\Tools\OpenApi\Provider;

readonly class Params
{
    public function __construct(
        public bool $skipCustom = false,
        public ?string $module = null,
    ) {}

    public function withSkipCustom(bool $skipCustom): self
    {
        return new self(
            skipCustom: $skipCustom,
            module: $this->module,
        );
    }
}
