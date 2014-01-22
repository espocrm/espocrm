<?php

namespace Espo\Core\Utils\Database\DBAL\Driver\PDOMySql;

class Driver extends \Doctrine\DBAL\Driver\PDOMySql\Driver 
{

	public function getDatabasePlatform()
    {
        return new \Espo\Core\Utils\Database\DBAL\Platforms\MySqlPlatform();
    }
    
}