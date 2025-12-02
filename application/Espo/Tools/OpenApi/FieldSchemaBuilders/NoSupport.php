<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class NoSupport implements FieldSchemaBuilder
{
    public function build(string $entityType, string $field): FieldSchemaResult
    {

        return new FieldSchemaResult(
            properties: [],
        );
    }
}
