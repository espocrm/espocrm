<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class JSONTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

    protected function setUp()
    {
        $this->fixture = new Utils\JSON();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testEncode()
	{
		$testVal= array('testOption'=>'Test');
		$this->assertEquals(json_encode($testVal), $this->fixture->encode($testVal));
	}

	function testDecode()
	{
		$testVal= array('testOption'=>'Test');
		$this->assertEquals($testVal, $this->fixture->decode(json_encode($testVal), true));

        $test= '{"folder":"data/logs"}';
        $this->assertEquals('data/logs', $this->fixture->decode($test)->folder);

		$test= '{"folder":"data\/logs"}';
        $this->assertEquals('data/logs', $this->fixture->decode($test)->folder);

		$test= '{"folder":"\\\Entity\\\Logs"}';
        $this->assertEquals('\Entity\Logs', $this->fixture->decode($test)->folder);

		//$test= '{"folder":"\Entity\\Logs"}';
        //$this->assertEquals('\Entity\Logs', $this->fixture->decode($test)->folder);
	}

	function testIsJSON()
	{
		$this->assertTrue($this->fixture->isJSON('{"database":{"driver":"pdo_mysql","host":"localhost"},"devMode":true}'));
		$this->assertFalse($this->fixture->isJSON('some string'));
	}



}

?>