<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class CurrencyConvertedType implements FieldSchemaBuilder
{
    public function __construct(
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $schema = (object) [];
        $schema->type = Type::NUMBER;
        $schema->readOnly = true;
        $schema->description = 'A currency amount converted to the default currency.';

        $schema->type = [
            $schema->type,
            Type::NULL,
        ];

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
        );
    }
}
