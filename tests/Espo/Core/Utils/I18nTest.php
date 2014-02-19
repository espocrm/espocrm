<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class I18nTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $reflection;

    protected function setUp()
    {                          
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager( array(
				'defaultPermissions' => array (
				    'dir' => '0775',
				    'file' => '0664',
				    'user' => '',
				    'group' => '',
			  ),
			) 
		); 

		$this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();	 	
		$map = array(   
          array('useCache', false)
        );     
        $this->objects['config']->expects($this->any())
             ->method('get')
             ->will($this->returnValueMap($map));	

        $this->objects['preferences'] = $this->getMockBuilder('\Espo\Entities\Preferences')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\I18n($this->objects['fileManager'], $this->objects['config'], $this->objects['preferences']);

        $this->reflection = new ReflectionHelper($this->object);  
        $this->reflection->setProperty('cacheFile', 'tests/testData/Utils/I18n/cache/application/languages/{*}.php');       
        $this->reflection->setProperty('paths', array(
			'corePath' => 'tests/testData/Utils/I18n/Espo/Resources/i18n',
			'modulePath' => 'tests/testData/Utils/I18n/Espo/Modules/{*}/Resources/i18n',
			'customPath' => 'tests/testData/Utils/I18n/Espo/Custom/Resources/i18n',	                              			
		) ); 
		$this->reflection->setProperty('currentLanguage', 'en_US');      
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    function testLanguage()
	{   
		$this->assertEquals('en_US', $this->object->getLanguage());

		$originalLang = $this->object->getLanguage();
		$this->object->setLanguage('lang_TEST');
		$this->assertEquals('lang_TEST', $this->object->getLanguage());	

		$this->object->setLanguage($originalLang);			   
	}

	
	function testGetLangCacheFile()
	{    
		$this->assertEquals('tests/testData/Utils/I18n/cache/application/languages/en_US.php', $this->reflection->invokeMethod('getLangCacheFile', array()) );  	

		$originalLang = $this->object->getLanguage();
		$this->object->setLanguage('lang_TEST');	
		$this->assertEquals('tests/testData/Utils/I18n/cache/application/languages/lang_TEST.php', $this->reflection->invokeMethod('getLangCacheFile', array()) );

		$this->object->setLanguage($originalLang);				   
	}


	function testGetData()
	{
		$result = array (
		  'User' => 
		  array (
		    'name' => 'User',
		    'label' => 'Core',
		    'source' => 'Core',
		  ),
		  'Account' => 
		  array (
		    'name' => 'Account',
		    'label' => 'Custom',
		    'source' => 'Crm Module',
		  ),
		  'Contact' => 
		  array (
		    'name' => 'Contact',
		    'label' => 'Custom',
		    'source' => 'Crm Module',
		  ),
		);
		$this->assertEquals($result, $this->reflection->invokeMethod('getData', array()));	
	}


	function testGet()
	{
		$result = array (
			'name' => 'User',
			'label' => 'Core',
			'source' => 'Core',
		);
		$this->assertEquals($result, $this->object->get('User'));

		$result = 'User';
		$this->assertEquals($result, $this->object->get('User.name'));
	}



}

?>
