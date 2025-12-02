<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class IntType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->type = Type::INTEGER;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if ($fieldDefs->getParam(FieldParam::MIN) !== null) {
            $schema->minimum = $fieldDefs->getParam(FieldParam::MIN);
        }

        if ($fieldDefs->getParam(FieldParam::MAX) !== null) {
            $schema->maximum = $fieldDefs->getParam(FieldParam::MAX);
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

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
