<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class AutoincrementType implements FieldSchemaBuilder
{
    public function __construct(
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $schema = (object) [
            'type' => Type::INTEGER,
            'readOnly' => true,
            'description' => 'An auto-increment number.',
        ];

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
        );
    }
}
