<?php

namespace tests\Espo\Core;

use Espo\Core\CronManager;
use tests\ReflectionHelper;


class CronManagerTest extends
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

    function testCheckLastRunTimeFileDoesnotExist()
    {
        $this->objects['fileManager']
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue(false));
        $this->objects['config']
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(50));
        $this->assertTrue($this->reflection->invokeMethod('checkLastRunTime', array()));
    }

    function testCheckLastRunTime()
    {
        $this->objects['fileManager']
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue(time() - 60));
        $this->objects['config']
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(50));
        $this->assertTrue($this->reflection->invokeMethod('checkLastRunTime', array()));
    }

    function testCheckLastRunTimeTooFrequency()
    {
        $this->objects['fileManager']
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue(time() - 49));
        $this->objects['config']
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(50));
        $this->assertFalse($this->reflection->invokeMethod('checkLastRunTime', array()));
    }

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();
        $this->objects['serviceFactory'] = $this->getMockBuilder('\Espo\Core\ServiceFactory')->disableOriginalConstructor()->getMock();
        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        $this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();
        $map = array(
            array('config', $this->objects['config']),
            array('fileManager', $this->objects['fileManager']),
            array('serviceFactory', $this->objects['serviceFactory']),
        );
        $this->objects['container']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
        $this->object = new CronManager($this->objects['container']);
        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}

?>
