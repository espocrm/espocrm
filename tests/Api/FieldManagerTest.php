<?php

namespace tests\Api;

class FieldManagerTest extends \PHPUnit_Framework_TestCase
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
		$this->fixture->setUrl('/Admin/fieldManager');
		/****************************************/
	}

	protected function tearDown()
	{
		$this->fixture = NULL;
	}


	public function testCreate()
	{
		$this->fixture->setType('POST');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity');
		$data = '{"name":"customField","type":"varchar","maxLength":50}';
		$this->assertTrue($this->fixture->isSuccess($data));
	}


	public function testReadAfterCreate()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity/customField');
		$data = '{"type":"varchar","maxLength":50,"isCustom":true}';
		$this->assertEquals($data, $this->fixture->getResponse()['response']);
	}

	public function testUpdate()
	{
		$this->fixture->setType('PUT');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity/customField');
		$data = '{"type":"varchar","maxLength":50,"default":"this is a test"}';
		$this->assertTrue($this->fixture->isSuccess($data));
	}

	public function testReadAfterUpdate()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity/customField');
		$data = '{"type":"varchar","maxLength":50,"isCustom":true,"default":"this is a test"}';
		$this->assertEquals($data, $this->fixture->getResponse()['response']);
	}

	public function testDelete()
	{
		$this->fixture->setType('DELETE');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity/customField');
		$this->assertTrue($this->fixture->isSuccess());
	}

	public function testReadAfterDetele()
	{
		$this->fixture->setType('GET');

		$this->fixture->setUrl('/Admin/fieldManager/CustomEntity/customField');
		$response= $this->fixture->getResponse();
		$this->assertEquals(404, $response['code']);
	}

	public function testDeleteTestFile()
	{
		$file = 'custom/Espo/Custom/Resources/metadata/entityDefs/CustomEntity.json';
		if (file_exists($file)) {
			@unlink($file);
		}
	}




}

?>