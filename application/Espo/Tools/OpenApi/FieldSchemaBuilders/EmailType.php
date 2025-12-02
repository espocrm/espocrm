<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class EmailType implements FieldSchemaBuilder
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
        $schema->maxLength = self::DEFAULT_MAX_LENGTH;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $schema->description = 'A primary email address.';

        $itemSchema = (object) [];
        $itemSchema->type = Type::OBJECT;
        $itemSchema->properties = [
            'emailAddress' => [
                'type' => Type::STRING,
            ],
            'primary' => [
                'type' => Type::BOOLEAN,
            ],
            'optOut' => [
                'type' => Type::BOOLEAN,
            ],
            'invalid' => [
                'type' => Type::BOOLEAN,
            ],
            'lower' => [
                'type' => Type::STRING,
                'readOnly' => true,
            ],
        ];
        $itemSchema->required = [
            'emailAddress',
            'primary',
        ];

        $dataSchema = (object) [];
        $dataSchema->type = Type::ARRAY;
        $dataSchema->items = get_object_vars($itemSchema);
        $dataSchema->description = 'Multiple email addresses';

        $dataSchema->type = [
            $dataSchema->type,
            Type::NULL,
        ];

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field;
        }

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
                $field . 'Data' => get_object_vars($dataSchema),
            ],
            required: $required,
        );
    }
}
