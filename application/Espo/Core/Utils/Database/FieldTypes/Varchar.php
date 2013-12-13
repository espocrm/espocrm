<?php

namespace Espo\Core\Utils\Database\FieldTypes;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;


class Varchar extends Type
{
	const VARCHAR = 'varchar';

	public function getName()
    {
        return self::VARCHAR;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		//return 'varchar';
    }


    public function getDefaultLength(AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }
}