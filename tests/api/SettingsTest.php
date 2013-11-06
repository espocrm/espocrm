<?php

namespace Espo\Tests\Api;

require_once('bootstrap.php');
use Espo\Utils as Utils,
	Espo\Tests\Api as API;

class SettingsTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	require_once('tests/api/RestTesterClass.php');
        $this->fixture = new API\RestTesterClass();
        $this->fixture->setUrl('/settings');
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testGet()
	{
		$this->fixture->setType('GET');
		$this->assertTrue($this->fixture->isSuccess());
	}

	function testPatch()
	{   
		$this->fixture->setType('PATCH');


		$array= array(
			"customTest"=> array("test"=> "success"),
		);
		$json= json_encode($array);
       	$this->assertTrue( $this->fixture->isSuccess($json) );

		/*
        $config= new Utils\Configurator();
		$configPath= $config->get('configPath');

		$FileManager= new Utils\FileManager();
		$initContent= $FileManager->getContent($configPath);

		if (!empty($initContent)) {

			$array= array(
				"customTest"=> array("test"=> "success"),
			);
			$json= json_encode($array);
        	$this->assertTrue( $this->fixture->isSuccess($json) );

            $FileManager->setContent($initContent, $configPath);
		}   */
	}


}

?>