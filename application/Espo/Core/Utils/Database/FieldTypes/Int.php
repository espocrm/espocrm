<?php

namespace Espo\Core\Utils\Database\FieldTypes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;


class Int extends Type
{
	const INTtype = 'int';


    public function getName()
    {
        return self::INTtype;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }


    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : (int) $value;
    }

    
    public function getBindingType()
    {
        return \PDO::PARAM_INT;
    }
}