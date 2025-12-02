<?php

namespace Espo\Tools\OpenApi;

class FieldSchemaResult
{
    /**
     * @param array<string, array<string, mixed>> $properties
     * @param string[] $required
     */
    public function __construct(
        public array $properties,
        public array $required = [],
    ) {}
}
