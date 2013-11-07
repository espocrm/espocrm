<?php

namespace Espo\Tests\Api;

require_once('bootstrap.php');

use Espo\Utils as Utils,
	Espo\Tests\Api as API;


class MetadataTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	require_once('tests/api/RestTesterClass.php');
        $this->fixture = new API\RestTesterClass();

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
		$this->assertTrue($this->fixture->isSuccess( $this->fixture->getResponse() ));
	}

	function testPut()
	{
		$this->fixture->setType('PUT');

		$this->fixture->setUrl('/metadata/custom-test/account');
		$data= '{"module":"Test"}';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		$this->fixture->setUrl('/metadata/custom-test/custom-test');
		$data= '{"module":"Test","var1":{"subvar1":"NEWsubval1","subvar55":"subval55"}}';
		$this->assertTrue($this->fixture->isSuccess( $data ));
	}


}

?>