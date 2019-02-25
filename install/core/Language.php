<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

class Language{
    private $defaultLanguage = 'en_US';

    private $systemHelper;

    private $data = array();

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
        if (isset($this->data[$language])) {
            return $this->data[$language];
        }

        if (empty($language)) {
            $language = $this->defaultLanguage;
        }

        $langFileName = 'install/core/i18n/'.$language.'/install.json';
        if (!file_exists($langFileName)) {
            $langFileName = 'install/core/i18n/'.$this->defaultLanguage.'/install.json';
        }

        $i18n = $this->getLangData($langFileName);

        if ($language != $this->defaultLanguage) {
            $i18n = $this->mergeWithDefaults($i18n);
        }

        $this->afterRetrieve($i18n);

        $this->data[$language] = $i18n;

        return $this->data[$language];
    }

    /**
     * Merge current language with default one
     *
     * @param  array $data
     * @return array
     */
    protected function mergeWithDefaults($data)
    {
        $defaultLangFile = 'install/core/i18n/'.$this->defaultLanguage.'/install.json';
        $defaultData = $this->getLangData($defaultLangFile);

        foreach ($data as $categoryName => &$labels) {
            foreach ($defaultData[$categoryName] as $defaultLabelName => $defaultLabel) {
                if (!isset($labels[$defaultLabelName])) {
                    $labels[$defaultLabelName] = $defaultLabel;
                }
            }
        }

        $data = array_merge($defaultData, $data);

        return $data;
    }

    protected function getLangData($filePath)
    {
        $data = file_get_contents($filePath);
        $data = json_decode($data, true);

        return $data;
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
        $serverOs = $this->getSystemHelper()->getOs();

        $rewriteRules = $this->getSystemHelper()->getRewriteRules();
        if (isset($i18n['options']['modRewriteInstruction'][$serverType][$serverOs])) {
            $modRewriteInstruction = $i18n['options']['modRewriteInstruction'][$serverType][$serverOs];

            preg_match_all('/\{(.*?)\}/', $modRewriteInstruction, $match);
            if (isset($match[1])) {
                foreach ($match[1] as $varName) {
                    if (isset($rewriteRules[$varName])) {
                        $modRewriteInstruction = str_replace('{'.$varName.'}', $rewriteRules[$varName], $modRewriteInstruction);
                    }
                }
            }

            $i18n['options']['modRewriteInstruction'][$serverType][$serverOs] = $modRewriteInstruction;
        }
    }
}
