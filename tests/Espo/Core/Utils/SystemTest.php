<?php

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\System;
use Espo\Core\Utils\Util;
use tests\ReflectionHelper;

class SystemTest extends
    \PHPUnit_Framework_TestCase
{

    /**
     * @var System
     */
    protected $object;

    /**
     * @var ReflectionHelper
     */
    protected $reflection;

    public function testGetServerType()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.17 (Ubuntu)';
        $this->assertEquals('apache', $this->object->getServerType());
        $_SERVER['SERVER_SOFTWARE'] = 'Apache';
        $this->assertEquals('apache', $this->object->getServerType());
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.5.12';
        $this->assertEquals('nginx', $this->object->getServerType());
        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/8.0';
        $this->assertEquals('microsoft-iis', $this->object->getServerType());
        $_SERVER['SERVER_SOFTWARE'] = 'apache/2.4.10 (win32) openssl/1.0.1i php';
        $this->assertEquals('apache', $this->object->getServerType());
    }

    public function testGetOS()
    {
        $possibleValues = array(
            'windows',
            'mac',
            'linux',
        );
        $this->assertTrue(in_array($this->object->getOS(), $possibleValues));
    }

    public function testGetRootDir()
    {
        $rootDir = dirname(__FILE__);
        $rootDir = Util::fixPath($rootDir);
        $path = Util::fixPath('/tests/Espo/Core/Utils');
        $rootDir = str_replace($path, '', $rootDir);
        $this->assertEquals($rootDir, $this->object->getRootDir());
    }

    public function testGetPhpBin()
    {
        $osExt = array(
            'windows' => '.exe',
            'linux' => '',
            'mac' => '',
        );
        if (defined('PHP_BINDIR')) {
            $phpBin = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
        } else {
            $phpBin = 'php';
        }
        $phpBin = str_replace($osExt[$this->object->getOS()], '', $phpBin);
        if (isset($phpBin)) {
            $this->assertEquals($phpBin, $this->object->getPhpBin());
        }
    }

    protected function setUp()
    {
        $this->object = new System();
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}

?>