<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

	protected function setUp()
    {
        $this->fixture = new Utils\Layout();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }

	function testGetConfig()
	{
       $this->assertObjectHasAttribute('corePath', $this->fixture->getConfig());
	   $this->assertObjectHasAttribute('customPath', $this->fixture->getConfig());
	}

	function testGetLayoutPath()
	{
       $this->assertEquals('application/Espo/Layouts/User', $this->fixture->getLayoutPath('User', '/'));
       $this->assertEquals('application.Espo.Layouts.User', $this->fixture->getLayoutPath('User', '.'));

	   $this->assertEquals('application/Modules/Crm/Layouts/Account', $this->fixture->getLayoutPath('Account', '/'));
       $this->assertEquals('application.Modules.Crm.Layouts.Account', $this->fixture->getLayoutPath('Account', '.'));
	}



}

?>