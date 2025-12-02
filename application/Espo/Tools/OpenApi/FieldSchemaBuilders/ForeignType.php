<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class ForeignType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $schema = (object) [];
        $schema->readOnly = true;

        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $link = $fieldDefs->getParam(FieldParam::LINK);
        $foreignField = $fieldDefs->getParam(FieldParam::FIELD);

        $foreignEntityType = $this->defs
            ->getEntity($entityType)
            ->tryGetRelation($link)
            ?->tryGetForeignEntityType();

        if (!$foreignEntityType) {
            return new FieldSchemaResult([]);
        }

        $foreignFieldDefs = $this->defs
            ->getEntity($foreignEntityType)
            ->tryGetField($foreignField);

        if (!$foreignFieldDefs) {
            return new FieldSchemaResult([]);
        }

        $fieldType = $foreignFieldDefs->getType();

        if (
            $fieldType === FieldType::ENUM ||
            $fieldType === FieldType::VARCHAR ||
            $fieldType === FieldType::TEXT ||
            $fieldType === FieldType::DATE ||
            $fieldType === FieldType::DATETIME ||
            $fieldType === FieldType::EMAIL ||
            $fieldType === FieldType::PHONE ||
            $fieldType === FieldType::WYSIWYG ||
            $fieldType === FieldType::DECIMAL
        ) {
            $schema->type = Type::STRING;
        } else if (
            $foreignEntityType === FieldType::INT
        ) {
            $schema->type = Type::INTEGER;
        } else if (
            $foreignEntityType === FieldType::FLOAT
        ) {
            $schema->type = Type::NUMBER;
        } else {
            return new FieldSchemaResult([]);
        }

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

