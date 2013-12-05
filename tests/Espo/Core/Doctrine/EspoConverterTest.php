<?php

namespace tests\Espo\Core\Utils;

require_once('tests/testBootstrap.php');


use Espo\Utils as Utils;

class EspoConverterTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

	private $app;

	public function __construct()
	{
		$this->app = $GLOBALS['app'];
	}


    protected function setUp()
    {
        $this->fixture = new \Espo\Core\Doctrine\EspoConverter($this->app->getContainer()->get('entityManager'), $this->app->getMetadata(), $this->app->getContainer()->get('fileManager'));
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }



	function testGetFieldType()
	{
		$this->assertEquals('string', $this->fixture->getFieldType('varchar'));
		$this->assertEquals('float', $this->fixture->getFieldType('float'));
	}




}

?>