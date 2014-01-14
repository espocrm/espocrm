<?php

namespace tests\Espo\Core\Utils\Database\Schema;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $reflection;

    protected function setUp()
    {
        /*$fileManager = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->setConstructorArgs(array(
		(object) array(
			'defaultPermissions' => (object)  array (
			    'dir' => '0775',
			    'file' => '0664',
			    'user' => '',
			    'group' => '',
		  ),
		)
		))->getMock(); */

		$fileManager = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\Database\Schema\Converter($fileManager);

        $this->reflection = new \ReflectionClass(get_class($this->object));
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

	protected function invokeMethod($methodName, array $parameters = array())
	{
	    $method = $this->reflection->getMethod($methodName);
	    $method->setAccessible(true);

	    return $method->invokeArgs($this->object, $parameters);
	}

	protected function getProperty($name)
	{
		$property = $this->reflection->getProperty($name);
		$property->setAccessible(true);
		return $property->getValue($this->object);
	}

	protected function setProperty($name, $value)
	{
		$property = $this->reflection->getProperty($name);
		$property->setAccessible(true);
		$property->setValue($this->object, $value);
	}



	public function testGetCustomTables()
	{
		$originalValue = $this->getProperty('customTablePath');
		$this->setProperty('customTablePath', 'tests/testData/Utils/Database/Schema/Tables');


		$file1 = include('tests/testData/Utils/Database/Schema/Tables/Subscription.php');
		$file2 = include('tests/testData/Utils/Database/Schema/Tables/Comment.php');

		$result = array_merge($file1, $file2);

		//todo fix the mockObject 'fileManager'
		//$this->assertEquals($result, $this->invokeMethod('getCustomTables', array()));

		$this->setProperty('customTablePath', $originalValue);
	}







}