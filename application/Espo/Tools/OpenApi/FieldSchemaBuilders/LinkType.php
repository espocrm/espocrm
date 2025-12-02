<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class LinkType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);
        $linkDefs = $this->defs->getEntity($entityType)->tryGetRelation($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if ($linkDefs && $linkDefs->tryGetForeignEntityType()) {
            $foreignEntityType = $linkDefs->tryGetForeignEntityType();

            $schema->description = "An ID of the record of the $foreignEntityType type.";
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $nameSchema = (object) [];
        $nameSchema->type = Type::STRING;
        $nameSchema->readOnly = true;
        $nameSchema->type = [
            $nameSchema->type,
            Type::NULL,
        ];

        $nameSchema->description = 'A foreign record name.';

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field . 'Id';
        }

        return new FieldSchemaResult(
            properties: [
                $field . 'Id' => get_object_vars($schema),
                $field . 'Name' => get_object_vars($nameSchema),
            ],
            required: $required,
        );
    }
}
