<?php

namespace tests\Api;

require_once('tests/testBootstrap.php');


class SettingsTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	require_once('tests/Api/RestTesterClass.php');
        $this->fixture = new RestTesterClass();
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

		//config get if the customTest item exists
		$savedValue = $GLOBALS['app']->getContainer()->get('config')->get('customTest');
		$this->assertObjectHasAttribute('test', $savedValue);
	}


}

?>