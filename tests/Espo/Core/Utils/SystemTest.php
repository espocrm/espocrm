<?php

namespace tests\Espo\Core\Utils;

class SystemTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $reflection;


    protected function setUp()
    {
        $this->object = new \Espo\Core\Utils\System();
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    public function testGetServerType()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.17 (Ubuntu)';
        $this->assertEquals( 'apache', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'Apache';
        $this->assertEquals( 'apache', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.5.12';
        $this->assertEquals( 'nginx', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/8.0';
        $this->assertEquals( 'microsoft-iis', $this->object->getServerType());

        $_SERVER['SERVER_SOFTWARE'] = 'apache/2.4.10 (win32) openssl/1.0.1i php';
        $this->assertEquals( 'apache', $this->object->getServerType());
    }


    public function testGetOS()
    {
        $possibleValues = array(
            'windows',
            'mac',
            'linux',
        );

        $this->assertTrue( in_array($this->object->getOS(), $possibleValues));
    }

    public function testGetRootDir()
    {
        $rootDir = dirname(__FILE__);
        $rootDir = preg_replace('/\/tests\/Espo\/Core\/Utils\/?/', '', $rootDir, 1);

        $this->assertEquals($rootDir, $this->object->getRootDir());
    }

    public function testGetPhpBin()
    {
        $phpBin = @exec('which php');

        if (isset($phpBin)) {
            $this->assertEquals($phpBin, $this->object->getPhpBin());
        }
    }



}

?>