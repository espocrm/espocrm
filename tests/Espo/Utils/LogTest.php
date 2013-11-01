<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class LogTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
        $this->fixture = new Utils\Log();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testGetOptions()
	{
	   $this->assertObjectHasAttribute('dir', $this->fixture->getOptions());
	   $this->assertObjectHasAttribute('file', $this->fixture->getOptions());
	   $this->assertObjectHasAttribute('level', $this->fixture->getOptions());
	}

	function testDatetime()
	{
		$this->assertTrue(is_object($this->fixture->getObject('Datetime')));
	}

	function testFileManager()
	{
		$this->assertTrue(is_object($this->fixture->getObject('FileManager')));
	}

	/*function testAdd()
	{
    	$this->assertTrue($this->fixture->add('UnitTest', 'Test log'));
	}*/


	function testGetLevelValue()
	{
    	$this->assertEquals(8, $this->fixture->getLevelValue('notice'));
    	$this->assertEquals(2048, $this->fixture->getLevelValue('STRICT'));
    	$this->assertEquals(32767, $this->fixture->getLevelValue('error')); 
	}

}

?>