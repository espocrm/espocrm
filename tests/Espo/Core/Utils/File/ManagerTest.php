<?php

namespace tests\Espo\Core\Utils\File;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $filesPath= 'tests/testData/FileManager';

    protected function setUp()
    {
        $this->object = new \Espo\Core\Utils\File\Manager(
			(object) array(
				'defaultPermissions' => (object)  array (
				    'dir' => '0775',
				    'file' => '0664',
				    'user' => '',
				    'group' => '',
			  ),
			)
		);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

	public function invokeMethod($methodName, array $parameters = array())
	{
	    $reflection = new \ReflectionClass(get_class($this->object));
	    $method = $reflection->getMethod($methodName);
	    $method->setAccessible(true);

	    return $method->invokeArgs($this->object, $parameters);
	}



	function testGetFileList()
	{
        $result= array('Dir1', 'file1.json','file1.php',); //no recursive
    	$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', false));

		$result= array('Dir1'); //no recursive
    	$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', false, '', 'dir'));

		$result= array('file1.json','file1.php',); //no recursive
    	$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', false, '', 'file'));


		$result= array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					 'file3.json'
				),
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', true));
	}

    function testGetFileListWithFilter()
	{
        $result= array('file1.json'); //no recursive
    	$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', false, '.*\.json$'));


		$result= array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					'file3.json'
				),
			),
			'file1.json',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', true, '.*\.json$'));

		$result= array(
			'file1.php',
            'Dir1' => array(
				'Dir2' => array (
				),
			),
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', true, '.*\.php$'));
	}

	function testGetFileListRecursively()
	{
    	$result= array(
			'Dir1',
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', false));


		$result= array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					'file3.json'
				),
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', true));


		$result= array(
			'Dir1' => array(
				'Dir2',
				'file2.json',
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', 1));


		$result= array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					'file3.json'
				),
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', 2));

		
		$result= array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					'file3.json'
				),
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->object->getFileList($this->filesPath.'/getFileList', 3));
	}

   	function testGetContent()
	{
    	$testPath= $this->filesPath.'/getContent';

        $result= '{"testData":"Test"}';
    	$this->assertEquals($result, $this->object->getContent($testPath, 'test.json'));

		$result= '{"testData":"Test"}';
    	$this->assertEquals($result, $this->object->getContent($testPath.'/test.json'));
	}

	/*function testSetContent()
	{
    	$testPath= $this->filesPath.'/setContent';

        $result= 'next value';
		$this->assertTrue($this->object->setContent($result, $testPath, 'test.json'));
    	//$this->assertEquals($result, $this->object->getContent($testPath, 'test.json'));

    	//$this->assertTrue($this->object->setContent('initial value', $testPath.'/test.json'));
	} */

	/*function testMergeContent()
	{
		$testPath= $this->filesPath.'/setContent';

		$initialVal= $this->object->getContent($testPath, 'test2.json');

		$result= '{"var1":"val1","var2":"val2"}';
		$this->assertTrue($this->object->setContent($result, $testPath, 'test2.json'));

		$result= '{"var2":"new val"}';
		$this->assertTrue($this->object->mergeContent($result, $testPath, 'test2.json', true));

		$result= '{"var1":"val1","var2":"new val"}';
        $this->assertEquals($result, $this->object->getContent($testPath, 'test2.json'));
	} */

	function testGetCurrentPermission()
	{
		$this->assertEquals(4, strlen($this->object->getCurrentPermission($this->filesPath.'/setContent/test.json')));
		$this->assertStringMatchesFormat('%d', $this->object->getCurrentPermission($this->filesPath.'/setContent/test.json'));
	}

	/*function testCheckCreateFile()
	{
		$filePath= $this->filesPath.'/setContent/test.json';

        $this->assertTrue($this->object->checkCreateFile($filePath));

		$perm= $this->object->getDefaultPermissions();
        $this->assertTrue( in_array($this->object->getCurrentPermission($filePath), array($perm->file, $perm->dir)) );
	}*/


	function testGetDefaultPermissions()
	{
       $this->assertObjectHasAttribute('dir', $this->object->getDefaultPermissions());
       $this->assertObjectHasAttribute('file', $this->object->getDefaultPermissions());
       $this->assertObjectHasAttribute('user', $this->object->getDefaultPermissions());
       $this->assertObjectHasAttribute('group', $this->object->getDefaultPermissions());
	}


	function testGetFileName()
	{
		$result= 'file1';
    	$this->assertEquals($result, $this->object->getFileName('file1.json'));

		$result= 'file1';
    	$this->assertEquals($result, $this->object->getFileName('file1.json', 'json'));

		$result= 'file1';
    	$this->assertEquals($result, $this->object->getFileName('file1.json', '.json'));
	}

	function testGetDirName()
	{
		$result= 'dirname';
    	$this->assertEquals('dirname', $this->object->getDirName('test/dirname/Test.json'));

		$result= 'dirname';
    	$this->assertEquals('dirname', $this->object->getDirName('test/dirname/'));

		$result= 'dirname';
    	$this->assertEquals('dirname', $this->object->getDirName('test/dirname'));
	}

	function testGetSingeFileList()
	{
		$input = array(
			'Dir1' => array(
				'file2.json',
				'Dir2' => array (
					'file3.json'
				),
			),
			'file1.json',
			'file1.php',
		);

		$result = array(
			'Dir1/file2.json',
			'Dir1/Dir2/file3.json',
			'file1.json',
			'file1.php',
		);		

		$this->assertEquals($result, $this->invokeMethod('getSingeFileList', array($input)));
	}



}

?>
