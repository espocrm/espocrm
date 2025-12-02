<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Core\Currency\ConfigDataProvider;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class CurrencyType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
        private ConfigDataProvider $configDataProvider,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if ($fieldDefs->getParam(FieldParam::DECIMAL)) {
            $schema->type = Type::STRING;
            $schema->pattern = '^-?\d+(\.\d+)?$';
            $schema->description = 'A numeric string';
        } else {
            $schema->type = Type::NUMBER;

            if ($fieldDefs->getParam(FieldParam::MIN) !== null) {
                $schema->minimum = $fieldDefs->getParam(FieldParam::MIN);
            }

            if ($fieldDefs->getParam(FieldParam::MAX) !== null) {
                $schema->maximum = $fieldDefs->getParam(FieldParam::MAX);
            }
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $schema->description = 'A currency amount.';

        $codeSchema = (object) [];
        $codeSchema->type = Type::STRING;
        $codeSchema->enum = $this->configDataProvider->getCurrencyList();
        $codeSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $codeSchema->type = [
                $codeSchema->type,
                Type::NULL,
            ];
        }

        $codeSchema->description = 'A code.';

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field;
            $required[] = $field . 'Currency';
        }

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
                $field . 'Currency' => get_object_vars($codeSchema),
            ],
            required: $required,
        );
    }
}
