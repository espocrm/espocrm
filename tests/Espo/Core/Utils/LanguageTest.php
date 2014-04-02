<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class LanguageTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $objects;

	protected $reflection;

    protected function setUp()
    {
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();

		$this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
		$map = array(
          array('useCache', false)
        );
        $this->objects['config']->expects($this->any())
             ->method('get')
             ->will($this->returnValueMap($map));

        $this->objects['preferences'] = $this->getMockBuilder('\Espo\Entities\Preferences')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\Language($this->objects['fileManager'], $this->objects['config'], $this->objects['preferences']);

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


    public function testLanguage()
	{
		$this->assertEquals('en_US', $this->object->getLanguage());

		$originalLang = $this->object->getLanguage();
		$this->object->setLanguage('lang_TEST');
		$this->assertEquals('lang_TEST', $this->object->getLanguage());

		$this->object->setLanguage($originalLang);
	}


	public function testGetLangCacheFile()
	{
		$this->assertEquals('tests/testData/Utils/I18n/cache/application/languages/en_US.php', $this->reflection->invokeMethod('getLangCacheFile', array()) );

		$originalLang = $this->object->getLanguage();
		$this->object->setLanguage('lang_TEST');
		$this->assertEquals('tests/testData/Utils/I18n/cache/application/languages/lang_TEST.php', $this->reflection->invokeMethod('getLangCacheFile', array()) );

		$this->object->setLanguage($originalLang);
	}


	public function testGetData()
	{
		$result = array (
			'User' =>
			array(
			  'fields' =>
			  array (
			    'name' => 'User',
			    'label' => 'Core',
			    'source' => 'Core',
			  ),
			),
			'Account' =>
			array (
				'fields' =>
			  	array (
					'name' => 'Account',
					'label' => 'Custom',
					'source' => 'Crm Module',
				),
			),
			'Contact' =>
			array (
				'fields' =>
			  	array (
					'name' => 'Contact',
					'label' => 'Custom',
					'source' => 'Crm Module',
				),
			),
			'Global' =>
			array (
				'options' =>
			  	array (
					'language' =>
					array (
				      'en_US' => 'English (United States)',
				    )
				),
			),
		);

		$this->assertEquals($result, $this->reflection->invokeMethod('getData', array()));
	}


	public function testGet()
	{
		$result = array (
			'fields' =>
			array(
				'name' => 'User',
				'label' => 'Core',
				'source' => 'Core',
			),
		);
		$this->assertEquals($result, $this->object->get('User'));

		$result = 'User';
		$this->assertEquals($result, $this->object->get('User.fields.name'));
	}

	public function testTranslate()
	{
		$this->assertEquals('Core', $this->object->translate('label', 'fields', 'User'));

		$input = array(
			'name',
			'label',
		);
		$result = array(
			'name' => 'User',
			'label' => 'Core',
		);
		$this->assertEquals($result, $this->object->translate($input, 'fields', 'User'));
	}

	public function testTranslateTestGlobal()
	{
		$result = array(
			'en_US' => 'English (United States)',
		);
		$this->assertEquals($result, $this->object->translate('language', 'options', 'User'));
	}

	public function testTranslateOption()
	{
		$result = array(
			'en_US' => 'English (United States)',
		);
		$this->assertEquals($result, $this->object->translate('language', 'options'));
	}

	public function testTranslateOptionWithRequiredOptions()
	{
		$result = array(
			'en_US' => 'English (United States)',
			'de_DE' => 'de_DE',
		);
		$requiredOptions = array(
			'en_US',
			'de_DE',
		);

		$this->assertEquals($result, $this->object->translate('language', 'options', 'Global', $requiredOptions));
	}


	public function testTranslateArray()
	{
		$input = array(
			'name',
			'label',
		);
		$result = array(
			'name' => 'User',
			'label' => 'Core',
		);
		$this->assertEquals($result, $this->object->translate($input, 'fields', 'User'));
	}

	public function testTranslateSubLabels()
	{
		$result = 'English (United States)';
		$this->assertEquals($result, $this->object->translate('language.en_US', 'options'));
	}



}

?>
