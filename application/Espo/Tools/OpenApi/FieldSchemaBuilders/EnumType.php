<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

class EnumType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
        private EnumOptionsProvider $optionsProvider,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        $optionList = $this->optionsProvider->get($fieldDefs);

        if ($optionList) {
            if (in_array('', $optionList) && !$fieldDefs->getParam(FieldParam::REQUIRED)) {
                $schema->type = [
                    Type::STRING,
                    Type::NULL,
                ];
            }

            $optionList = array_filter($optionList, fn ($it) => $it !== '');
            $optionList = array_values($optionList);

            $schema->enum = $optionList;
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
