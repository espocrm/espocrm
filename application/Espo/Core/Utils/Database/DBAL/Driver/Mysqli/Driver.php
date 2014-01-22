<?php

namespace Espo\Core\Utils\Database\DBAL\Driver\Mysqli;

class Driver extends \Doctrine\DBAL\Driver\Mysqli\Driver 
{

	public function getDatabasePlatform()
    {
        return new \Espo\Core\Utils\Database\DBAL\Platforms\MySqlPlatform();
    }
    
}