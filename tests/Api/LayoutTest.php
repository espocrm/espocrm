<?php

namespace tests\Api;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
    	$config = include('tests/Api/config.php');
    	if (!$config['apiTestsEnabled']) {
    		$this->markTestSkipped('API tests are not enabled.');
    	}

    	require_once('tests/Api/RestTesterClass.php');
        $this->fixture = new RestTesterClass();

		/****************************************/
        $this->fixture->setUrl('/layout');
		/****************************************/
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testPut()
	{
		$this->fixture->setType('PUT');

		$this->fixture->setUrl('/CustomTest/layout/testPut');
		$data= '["amount","account","closeDate","leadSource","stage","probability","assignedUser"]';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$file = 'application/Espo/Custom/Resources/layouts/CustomTest/testPut.json';

		$fileExists = file_exists($file);
		$this->assertTrue($fileExists);

		if ($fileExists) {
			$content = file_get_contents($file);

			$this->assertEquals($data, $content);

			@unlink($file);
		}
	}

	function testPatch()
	{
		$this->fixture->setType('PATCH');

		$this->fixture->setUrl('/CustomTest/layout/testPatch');
		$data= '[{"label":"MyLabel"}]';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		$data= '[{"isPathed":true}]';
		$this->assertTrue($this->fixture->isSuccess( $data ));

		//check if file exists
		$file = 'application/Espo/Custom/Resources/layouts/CustomTest/testPatch.json';

		$fileExists = file_exists($file);
		$this->assertTrue($fileExists);

		if ($fileExists) {

			$content = file_get_contents($file);

			//$data= '[{"label":"MyLabel","isPathed":true}]';
			$data= '[{"isPathed":true}]';  //now PATCH works like PUT
			$this->assertEquals($data, $content);

			@unlink($file);
		}
	}

	function testGet()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/CustomTest/layout/detail');
		$this->assertTrue($this->fixture->isSuccess( ));

		$this->fixture->setUrl('/needToBeNotReal/layout/notReal');
		$response= $this->fixture->getResponse();
		$this->assertEquals(404, $response['code']);
	}


}

?>