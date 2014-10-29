<?php

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Metadata;
use PHPUnit_Framework_MockObject_MockObject;
use tests\ReflectionHelper;


class MetadataTest extends
    \PHPUnit_Framework_TestCase
{

    /**
     * @var Metadata
     */
    protected $object;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $objects;

    /**
     * @var ReflectionHelper
     */
    protected $reflection;

    function testGet()
    {
        $result = 'System';
        $this->assertEquals($result, $this->object->get('app.adminPanel.system.label'));
        $result = 'fields';
        $this->assertArrayHasKey($result, $this->object->get('entityDefs.User'));
    }

    protected function setUp()
    {
        $GLOBALS['log'] = $this->getMockBuilder('\Espo\Core\Utils\Log')->disableOriginalConstructor()->getMock();
        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        $this->objects['fileManager'] = new Manager();
        //set to use cache
        $this->objects['config']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(true));
        $this->object = new Metadata($this->objects['config'], $this->objects['fileManager']);
        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('cacheFile', 'tests/testData/Utils/Metadata/metadata.php');
        $this->reflection->setProperty('ormCacheFile', 'tests/testData/Utils/Metadata/ormMetadata.php');
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}

?>
