<?php


class Language
{
	private $defaultLanguage = 'en_US';

	private $systemHelper;

	public function __construct()
	{
		require_once 'SystemHelper.php';
		$this->systemHelper = new SystemHelper();
	}

	protected function getSystemHelper()
	{
		return $this->systemHelper;
	}

	public function get($language)
	{
		if (empty($language)) {
			$language = $this->defaultLanguage;
		}

		$langFileName = 'install/core/i18n/'.$language.'/install.json';
		if (!file_exists($langFileName)) {
			$langFileName = 'install/core/i18n/'.$this->defaultLanguage.'/install.json';
		}

		$i18n = file_get_contents($langFileName);
		$i18n = json_decode($i18n, true);

		$this->afterRetrieve($i18n);

		return $i18n;
	}

	/**
	 * After retrieve actions
	 *
	 * @param  array $i18n
	 * @return array $i18n
	 */
	protected function afterRetrieve(array &$i18n)
	{
		/** Get rewrite rules */
		$serverType = $this->getSystemHelper()->getServerType();
		$rewriteRules = $this->getSystemHelper()->getRewriteRules();
		$i18n['options']['modRewriteHelp'][$serverType] = str_replace('{0}', $rewriteRules, $i18n['options']['modRewriteHelp'][$serverType]);
	}


}
