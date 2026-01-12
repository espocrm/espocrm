<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\OpenApi;

use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;
use Espo\Tools\OpenApi\FieldSchemaBuilders\NoSupport;
use Espo\Tools\OpenApi\FieldSchemaBuilders\EnumType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\MultiEnumType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\PhoneType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\TextType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\VarcharType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\BoolType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\IntType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\FloatType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\DecimalType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\AutoincrementType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\CurrencyType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\CurrencyConvertedType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\NumberType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\DateType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\DatetimeType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\DatetimeOptionalType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\ForeignType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\EmailType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\LinkType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\LinkParentType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\LinkMultipleType;
use Espo\Tools\OpenApi\FieldSchemaBuilders\IdType;

class FieldSchemaBuilderFactory
{
    /** @var array<string, class-string<FieldSchemaBuilder>> */
    private array $map = [
        FieldType::VARCHAR => VarcharType::class,
        FieldType::BARCODE => VarcharType::class,
        FieldType::URL => VarcharType::class,
        FieldType::ENUM => EnumType::class,
        FieldType::TEXT => TextType::class,
        FieldType::WYSIWYG => TextType::class,
        FieldType::NUMBER => NumberType::class,
        FieldType::BOOL => BoolType::class,
        FieldType::INT => IntType::class,
        FieldType::FLOAT => FloatType::class,
        FieldType::DECIMAL => DecimalType::class,
        FieldType::CURRENCY => CurrencyType::class,
        FieldType::AUTOINCREMENT => AutoincrementType::class,
        FieldType::CURRENCY_CONVERTED => CurrencyConvertedType::class,
        FieldType::FOREIGN => ForeignType::class,
        FieldType::EMAIL => EmailType::class,
        FieldType::PHONE => PhoneType::class,
        FieldType::DATE => DateType::class,
        FieldType::DATETIME => DatetimeType::class,
        FieldType::DATETIME_OPTIONAL => DatetimeOptionalType::class,
        FieldType::MULTI_ENUM => MultiEnumType::class,
        FieldType::ARRAY => MultiEnumType::class,
        FieldType::CHECKLIST => MultiEnumType::class,
        FieldType::URL_MULTIPLE => MultiEnumType::class,
        FieldType::LINK => LinkType::class,
        FieldType::LINK_ONE => LinkType::class,
        FieldType::FILE => LinkType::class,
        FieldType::IMAGE => LinkType::class,
        FieldType::LINK_PARENT => LinkParentType::class,
        FieldType::LINK_MULTIPLE => LinkMultipleType::class,
    ];

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Defs $defs,
    ) {}

    public function create(string $entityType, string $field): FieldSchemaBuilder
    {
        $className = $this->getClassName($field, $entityType);

        return $this->injectableFactory->create($className);
    }

    /**
     * @return class-string<FieldSchemaBuilder>
     */
    private function getClassName(string $field, string $entityType): string
    {
        if ($field === Attribute::ID) {
            return IdType::class;
        }

        $fieldDefs = $this->defs
            ->getEntity($entityType)
            ->getField($field);

        $type = $fieldDefs->getType();

        return $this->map[$type] ?? NoSupport::class;
    }
}
