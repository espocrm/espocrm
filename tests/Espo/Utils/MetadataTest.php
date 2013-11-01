<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

	protected function setUp()
    {
        $this->fixture = new Utils\Metadata();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }

	function testGetFileList()
	{
    	$this->assertTrue(Utils\JSON::isJSON($this->fixture->getMetadata(true, true)));

		$this->assertTrue(is_array($this->fixture->getMetadata(false, true)));
	}

	function testGetConfig()
	{
       $this->assertObjectHasAttribute('name', $this->fixture->getConfig());
       $this->assertObjectHasAttribute('cachePath', $this->fixture->getConfig());
       $this->assertObjectHasAttribute('corePath', $this->fixture->getConfig());
	}

	function testGetEntityPath()
	{
       $this->assertEquals('Espo\Entities\User', $this->fixture->getEntityPath('User', '\\'));
       $this->assertEquals('Espo.Entities.User', $this->fixture->getEntityPath('User', '.'));

	   $this->assertEquals('Modules\Crm\Entities\Account', $this->fixture->getEntityPath('Account', '\\'));
       $this->assertEquals('Modules.Crm.Entities.Account', $this->fixture->getEntityPath('Account', '.'));
	}



}

?>