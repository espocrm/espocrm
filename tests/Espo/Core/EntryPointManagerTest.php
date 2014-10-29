<?php

namespace tests\Espo\Core;

use Espo\Core\EntryPointManager;
use Espo\Core\Utils\File\Manager;
use tests\ReflectionHelper;


class EntryPointManagerTest extends
    \PHPUnit_Framework_TestCase
{

    protected $object;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $objects;

    protected $filesPath = 'tests/testData/EntryPoints';

    /**
     * @var ReflectionHelper
     */
    protected $reflection;

    function testGet()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',
        ));
        $this->assertEquals('\tests\testData\EntryPoints\Espo\EntryPoints\Test',
            $this->reflection->invokeMethod('getClassName', array('test')));
    }

    function testRun()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',
        ));
        $this->assertNull($this->reflection->invokeMethod('run', array('test')));
    }

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\\Espo\\Core\\Container')->disableOriginalConstructor()->getMock();
        $this->object = new EntryPointManager($this->objects['container']);
        $this->reflection = new ReflectionHelper($this->object);
        $fileManager = new Manager();
        $this->reflection->setProperty('fileManager', $fileManager);
        $this->reflection->setProperty('cacheFile', 'tests/testData/EntryPoints/cache/entryPoints.php');
        $this->reflection->setProperty('paths', array(
            'corePath' => 'tests/testData/EntryPoints/Espo/EntryPoints',
            'modulePath' => 'tests/testData/EntryPoints/Espo/Modules/Crm/EntryPoints',
        ));
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}

?>
