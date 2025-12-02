<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class NumberType  implements FieldSchemaBuilder
{
    public function __construct(
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->readOnly = true;

        $schema->description = 'A number. Auto-incrementing with a prefix.';

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
        );
    }
}
