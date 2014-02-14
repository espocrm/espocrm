<?php

namespace tests\Espo\Core\Utils\File;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $filesPath= 'tests/testData/FileManager';

    protected function setUp()
    {  
    	$this->object = new \Espo\Core\Utils\File\Manager(
			array(
				'defaultPermissions' => array (
				    'dir' => '0775',
				    'file' => '0664',
				    'user' => '',
				    'group' => '',
			  ),
			)
		);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

	
	function testGetFileName()
	{
		$this->assertEquals('Donwload', $this->object->getFileName('Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('/Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('\Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('application/Espo/EntryPoints/Donwload.php'));
	}


}

?>
