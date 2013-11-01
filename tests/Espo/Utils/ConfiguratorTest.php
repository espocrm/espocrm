<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
        $this->fixture = new Utils\Configurator();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testGet()
	{
       $this->assertStringEndsWith('.php', $this->fixture->get('configPath'));
       $this->assertNotNull($this->fixture->get('cachePath'));
       $this->assertNotNull($this->fixture->get('layoutConfig'));
       $this->assertNotNull($this->fixture->get('metadataConfig'));
       $this->assertNotNull($this->fixture->get('languageConfig'));

	   //permission
	   $this->assertObjectHasAttribute('dir', $this->fixture->get('defaultPermissions'));
       $this->assertObjectHasAttribute('file', $this->fixture->get('defaultPermissions'));
       $this->assertObjectHasAttribute('user', $this->fixture->get('defaultPermissions'));
       $this->assertObjectHasAttribute('group', $this->fixture->get('defaultPermissions'));

	   //database
	   $this->assertObjectHasAttribute('driver', $this->fixture->get('database'));

	   //logger
	   $this->assertObjectHasAttribute('dir', $this->fixture->get('logger'));
	   $this->assertObjectHasAttribute('file', $this->fixture->get('logger'));
	   $this->assertObjectHasAttribute('level', $this->fixture->get('logger'));
	}

	function testGetJSON()
	{
		$this->assertObjectNotHasAttribute('metadataConfig', json_decode($this->fixture->getJSON()));
	}

    /*function testSet()
	{
        $setKey= 'testOption';
		$setValue= 'Test';

        $this->assertTrue($this->fixture->set($setKey, $setValue));
        $this->assertEquals($setValue, $this->fixture->get($setKey));
	}*/



}

?>