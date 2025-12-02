<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class VarcharType implements FieldSchemaBuilder
{
    const int DEFAULT_MAX_LENGTH = 255;

    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->maxLength = $fieldDefs->getParam(FieldParam::MAX_LENGTH) ?? self::DEFAULT_MAX_LENGTH;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $schema->description = 'A one-line string.';

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED) && !$fieldDefs->getParam(FieldParam::DEFAULT)) {
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
