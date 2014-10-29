<?php

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use tests\ReflectionHelper;

class ConfigTest extends
    \PHPUnit_Framework_TestCase
{

    /**
     * @var Config
     */
    protected $object;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $objects;

    protected $defaultTestConfig = 'tests/testData/Utils/Config/config.php';

    protected $configPath = 'tests/testData/cache/config.php';

    protected $systemConfigPath = 'tests/testData/Utils/Config/systemConfig.php';

    /**
     * @var ReflectionHelper
     */
    protected $reflection;

    public function testLoadConfig()
    {
        $this->assertArrayHasKey('database', $this->reflection->invokeMethod('loadConfig', array()));
        $this->assertArrayHasKey('dateFormat', $this->reflection->invokeMethod('loadConfig', array()));
    }

    public function testGet()
    {
        $result = array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'dbname' => 'espocrm',
            'user' => 'root',
            'password' => '',
        );
        $this->assertEquals($result, $this->object->get('database'));
        $result = 'pdo_mysql';
        $this->assertEquals($result, $this->object->get('database.driver'));
        $result = 'YYYY-MM-DD';
        $this->assertEquals($result, $this->object->get('dateFormat'));
        $this->assertTrue($this->object->get('isInstalled'));
    }

    public function testSet()
    {
        $setKey = 'testOption';
        $setValue = 'Test';
        $this->object->set($setKey, $setValue);
        $this->assertTrue($this->object->save());
        $this->assertEquals($setValue, $this->object->get($setKey));
        $this->object->set($setKey, 'Another Wrong Value');
        $this->assertTrue($this->object->save());
    }

    public function testSetNull()
    {
        $setKey = 'testOption';
        $setValue = 'Test';
        $this->object->set($setKey, $setValue);
        $this->assertTrue($this->object->save());
        $this->assertEquals($setValue, $this->object->get($setKey));
        $this->object->set($setKey, null);
        $this->assertTrue($this->object->save());
        $this->assertNull($this->object->get($setKey));
    }

    public function testSetArray()
    {
        $values = array(
            'testOption' => 'Test',
            'testOption2' => 'Test2',
        );
        $this->object->set($values);
        $this->assertTrue($this->object->save());
        $this->assertEquals('Test', $this->object->get('testOption'));
        $this->assertEquals('Test2', $this->object->get('testOption2'));
        $wrongArray = array(
            'testOption' => 'Another Wrong Value',
        );
        $this->object->set($wrongArray);
        $this->assertTrue($this->object->save());
    }

    public function testRemove()
    {
        $optKey = 'removeOption';
        $optValue = 'Test';
        $this->object->set($optKey, $optValue);
        $this->assertTrue($this->object->save());
        $this->assertTrue($this->object->remove($optKey));
        $this->assertNull($this->object->get($optKey));
    }

    public function testSystemConfigMerge()
    {
        /**
         * @var Manager $fileManager
         */
        $fileManager = $this->objects['fileManager'];
        $configDataWithoutSystem = $fileManager->getContents($this->configPath);
        $this->assertArrayNotHasKey('systemItems', $configDataWithoutSystem);
        $this->assertArrayNotHasKey('adminItems', $configDataWithoutSystem);
        $configData = $this->reflection->invokeMethod('loadConfig', array());
        $this->assertArrayHasKey('systemItems', $configData);
        $this->assertArrayHasKey('adminItems', $configData);
    }

    protected function setUp()
    {
        $this->objects['fileManager'] = new Manager();
        /*copy defaultTestConfig file to cache*/
        if (!file_exists($this->configPath)) {
            copy($this->defaultTestConfig, $this->configPath);
        }
        $this->object = new Config($this->objects['fileManager']);
        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('configPath', $this->configPath);
        $this->reflection->setProperty('systemConfigPath', $this->systemConfigPath);
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}