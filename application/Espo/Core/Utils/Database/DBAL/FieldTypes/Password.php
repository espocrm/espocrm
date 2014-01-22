<?php

namespace Espo\Core\Utils\Database\DBAL\FieldTypes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Password extends Type
{
    const PASSWORD = 'password';

	public function getName()
    {
        return self::PASSWORD;
    }

	public static function getDbTypeName()
	{
		return 'VARCHAR';
	}

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		//return "MD5";
    }

    /*public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    } */


}

