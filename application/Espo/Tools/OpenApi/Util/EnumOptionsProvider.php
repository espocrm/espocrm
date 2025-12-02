<?php

namespace Espo\Tools\OpenApi\Util;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\FieldDefs;

class EnumOptionsProvider
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    /**
     * @return ?string[]
     */
    public function get(FieldDefs $fieldDefs): ?array
    {
        /** @var ?string $path */
        $path = $fieldDefs->getParam('optionsPath');
        /** @var ?string $path */
        $ref = $fieldDefs->getParam('optionsReference');

        if (!$path && $ref && str_contains($ref, '.')) {
            [$refEntityType, $refField] = explode('.', $ref);

            $path = "entityDefs.$refEntityType.fields.$refField.options";
        }

        /** @var ?string[] $optionList */
        $optionList = $path ?
            $this->metadata->get($path) :
            $fieldDefs->getParam('options');

        return $optionList;
    }
}
