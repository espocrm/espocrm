<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

class Language{
    private $defaultLanguage = 'en_US';

    private $systemHelper;

    private $data = array();

    protected $defaultLabels = [
        'nginx' => 'linux',
        'apache' => 'linux',
        'microsoft-iis' => 'windows',
    ];

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

        $label = $i18n['options']['modRewriteInstruction'][$serverType][$serverOs] ?? null;

        if (!isset($label) && isset($this->defaultLabels[$serverType])) {
            $defaultLabel = $this->defaultLabels[$serverType];

            if (!isset($i18n['options']['modRewriteInstruction'][$serverType][$defaultLabel])) {
                $defaultLangFile = 'install/core/i18n/' . $this->defaultLanguage . '/install.json';
                $defaultData = $this->getLangData($defaultLangFile);

                $i18n['options']['modRewriteInstruction'][$serverType][$defaultLabel] = $defaultData['options']['modRewriteInstruction'][$serverType][$defaultLabel];
            }

            $label = $i18n['options']['modRewriteInstruction'][$serverType][$defaultLabel];
        }

        if (!$label) {
            return;
        }

        preg_match_all('/\{(.*?)\}/', $label, $match);
        if (isset($match[1])) {
            foreach ($match[1] as $varName) {
                if (isset($rewriteRules[$varName])) {
                    $label = str_replace('{'.$varName.'}', $rewriteRules[$varName], $label);
                }
            }
        }

        $i18n['options']['modRewriteInstruction'][$serverType][$serverOs] = $label;
    }
}
