<?php

namespace tests\Espo\Core\Cron;

use tests\ReflectionHelper;


class ScheduledJobTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $objects;

    protected $cronSetup = array(
        'linux' => 'linux command',
        'windows' => 'windows command',
        'mac' => 'mac command',
        'default' => 'default command',
    );

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();

        $this->objects['language'] = $this->getMockBuilder('\Espo\Core\Utils\Language')->disableOriginalConstructor()->getMock();

        $map = array(
            array('language', $this->objects['language']),
        );

        $this->objects['container']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->object = new \Espo\Core\Cron\ScheduledJob( $this->objects['container'] );

        $this->reflection = new ReflectionHelper($this->object);

        $this->reflection->setProperty('cronSetup', $this->cronSetup);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    public function testGetSetupMessage()
    {
        $cronSetup = array (
            'linux' => 'linux message',
            'mac' => 'mac message',
            'windows' => 'windows message',
            'default' => 'default message',
        );

        $this->objects['language']
            ->expects($this->once())
            ->method('translate')
            ->will($this->returnValue($cronSetup));

        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.17 (Ubuntu)';

        $res = array(
            'message' => 'linux message',
            'command' => 'linux command',
        );

        $this->assertEquals( $res, $this->reflection->invokeMethod('getSetupMessage', array()) );
    }



}

?>
