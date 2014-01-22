<?php

namespace Espo\Core\Utils\Database\DBAL\FieldTypes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;


class Bool extends Type
{
	const BOOL = 'bool';

	public function getName()
    {
        return self::BOOL;
    }

	public static function getDbTypeName()
	{
		return 'TINYINT';
	}


    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBooleanTypeDeclarationSQL($fieldDeclaration);
    }


    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $platform->convertBooleans($value);
    }


    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : (bool) $value;
    }

    public function getBindingType()
    {
        return \PDO::PARAM_BOOL;
    }
}