<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class DatetimeType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $schema->pattern = '^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$';
        $schema->examples = ['2026-11-29 12:34:56'];
        $schema->description = 'A timestamp in UTC.';

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field;
        }

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
            required: $required,
        );
    }
}
