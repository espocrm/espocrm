<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class FileManagerTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

	protected $filesPath= 'tests/testData/FileManager';

    protected function setUp()
    {
        $this->fixture = new Utils\FileManager();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


	function testGetSeparator()
	{
    	$this->assertNotEmpty($this->fixture->getSeparator());
	}

	function testGetFileList()
	{
        $result= array('Dir1', 'file1.json','file1.php',); //no recursive
    	$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', false));

		$result= array('Dir1'); //no recursive
    	$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', false, '', 'dir'));

		$result= array('file1.json','file1.php',); //no recursive
    	$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', false, '', 'file'));


		$result= array(
			'Dir1' => array(
				'file2.json',
			),
			'file1.json',
			'file1.php',
		);
		$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', true));
	}

    function testGetFileListWithFilter()
	{
        $result= array('file1.json'); //no recursive
    	$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', false, '.*\.json$'));


		$result= array(
			'Dir1' => array(
				'file2.json',
			),
			'file1.json',
		);
		$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', true, '.*\.json$'));

		$result= array(
			'file1.php',
            'Dir1' => array(
			),
		);
		$this->assertEquals($result, $this->fixture->getFileList($this->filesPath.'/getFileList', true, '.*\.php$'));
	}

   	function testGetContent()
	{
    	$testPath= $this->filesPath.'/getContent';

        $result= '{"testData":"Test"}';
    	$this->assertEquals($result, $this->fixture->getContent($testPath, 'test.json'));

		$result= '{"testData":"Test"}';
    	$this->assertEquals($result, $this->fixture->getContent($testPath.'/test.json'));
	}

	function testSetContent()
	{
    	$testPath= $this->filesPath.'/setContent';

        $result= 'next value';
		$this->assertTrue($this->fixture->setContent($result, $testPath, 'test.json'));
    	//$this->assertEquals($result, $this->fixture->getContent($testPath, 'test.json'));

    	//$this->assertTrue($this->fixture->setContent('initial value', $testPath.'/test.json'));
	}

	function testMergeContent()
	{
		$testPath= $this->filesPath.'/setContent';

		$initialVal= $this->fixture->getContent($testPath, 'test2.json');

		$result= '{"var1":"val1","var2":"val2"}';
		$this->assertTrue($this->fixture->setContent($result, $testPath, 'test2.json'));

		$result= '{"var2":"new val"}';
		$this->assertTrue($this->fixture->mergeContent($result, $testPath, 'test2.json', true));

		$result= '{"var1":"val1","var2":"new val"}';
        $this->assertEquals($result, $this->fixture->getContent($testPath, 'test2.json'));
	}

	function testGetCurrentPermission()
	{
		$this->assertEquals(4, strlen($this->fixture->getCurrentPermission($this->filesPath.'/setContent/test.json')));
		$this->assertStringMatchesFormat('%d', $this->fixture->getCurrentPermission($this->filesPath.'/setContent/test.json'));
	}

	function testCheckCreateFile()
	{
		$filePath= $this->filesPath.'/setContent/test.json';

        $this->assertTrue($this->fixture->checkCreateFile($filePath));

		$perm= $this->fixture->getDefaultPermissions();
        $this->assertTrue( in_array($this->fixture->getCurrentPermission($filePath), array($perm->file, $perm->dir)) );
	}


	function testGetDefaultPermissions()
	{
       $this->assertObjectHasAttribute('dir', $this->fixture->getDefaultPermissions());
       $this->assertObjectHasAttribute('file', $this->fixture->getDefaultPermissions());
       $this->assertObjectHasAttribute('user', $this->fixture->getDefaultPermissions());
       $this->assertObjectHasAttribute('group', $this->fixture->getDefaultPermissions());
	}

	function testConcatPath()
	{
		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, $this->fixture->concatPath('dir1/dir2', 'file1.json'));

		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, $this->fixture->concatPath('dir1/dir2/', 'file1.json'));

		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, $this->fixture->concatPath('dir1/dir2/file1.json'));
	}

	function testGetFileName()
	{
		$result= 'file1';
    	$this->assertEquals($result, $this->fixture->getFileName('file1.json'));

		$result= 'file1';
    	$this->assertEquals($result, $this->fixture->getFileName('file1.json', 'json'));

		$result= 'file1';
    	$this->assertEquals($result, $this->fixture->getFileName('file1.json', '.json'));
	}

	function testGetDirName()
	{
		$result= 'dirname';
    	$this->assertEquals('dirname', $this->fixture->getDirName('test/dirname/Test.json'));

		$result= 'dirname';
    	$this->assertEquals('dirname', $this->fixture->getDirName('test/dirname/'));

		$result= 'dirname';
    	$this->assertEquals('dirname', $this->fixture->getDirName('test/dirname'));
	}



}

?>