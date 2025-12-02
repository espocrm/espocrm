<?php

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Doctrine\DBAL\Types\Types;
use Espo\ORM\Defs;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class IdType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->tryGetField($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->readOnly = true;

        $dbType = $fieldDefs?->getParam(Defs\Params\FieldParam::DB_TYPE);

        if ($dbType === Types::BIGINT || $dbType === Types::INTEGER) {
            $schema->type = Type::INTEGER;
        }

        $schema->description = 'An ID.';

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
        );
    }
}

