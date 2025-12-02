<?php

namespace Espo\Tools\OpenApi;

/**
 * Not stable yet. May change.
 *
 * @internal
 */
interface FieldSchemaBuilder
{
    public function build(string $entityType, string $field): FieldSchemaResult;
}
