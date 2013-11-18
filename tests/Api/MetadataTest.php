<?php

namespace tests\Api;

require_once('tests/testBootstrap.php');


class MetadataTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	require_once('tests/Api/RestTesterClass.php');
        $this->fixture = new RestTesterClass();

		/****************************************/
        $this->fixture->setUrl('/metadata');
		/****************************************/
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testGet()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/metadata');
		$this->assertTrue($this->fixture->isSuccess( ));
	}

	function testPut()
	{
		$this->fixture->setType('PUT');

		$this->fixture->setUrl('/metadata/custom-test/account');
		$data= '{"module":"Test"}';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$this->assertTrue(file_exists('application/Modules/Crm/Resources/metadata/customTest/Account.json'));


		$this->fixture->setUrl('/metadata/custom-test/custom-test');
		$data= '{"module":"Test","var1":{"subvar1":"NEWsubval1","subvar55":"subval55"}}';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$this->assertTrue(file_exists('application/Espo/Resources/metadata/customTest/CustomTest.json'));
	}


}

?>