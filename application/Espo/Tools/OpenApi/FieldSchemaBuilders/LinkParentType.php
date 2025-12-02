<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class LinkParentType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $idSchema = (object) [];
        $idSchema->type = Type::STRING;
        $idSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        $idSchema->description = "A foreign record ID.";

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idSchema->type = [
                $idSchema->type,
                Type::NULL,
            ];
        }

        $typeSchema = (object) [];
        $typeSchema->type = Type::STRING;
        $typeSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $typeSchema->type = [
                $typeSchema->type,
                Type::NULL,
            ];
        }

        if ($fieldDefs->getParam('entityList')) {
            $typeSchema->enum = $fieldDefs->getParam('entityList');
        }

        $typeSchema->description = "An entity type.";

        $nameSchema = (object) [];
        $nameSchema->type = Type::STRING;
        $nameSchema->readOnly = true;

        $nameSchema->type = [
            $nameSchema->type,
            Type::NULL,
        ];

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field . 'Id';
            $required[] = $field . 'Type';
        }

        $nameSchema->description = 'A foreign record name.';

        return new FieldSchemaResult(
            properties: [
                $field . 'Id' => get_object_vars($idSchema),
                $field . 'Type' => get_object_vars($typeSchema),
                $field . 'Name' => get_object_vars($nameSchema),
            ],
            required: $required,
        );
    }
}
