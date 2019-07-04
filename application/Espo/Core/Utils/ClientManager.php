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

namespace Espo\Core\Utils;

class ClientManager
{
    private $themeManager;

    private $config;

    private $metadata;

    protected $mainHtmlFilePath = 'html/main.html';

    protected $runScript = "app.start();";

    protected $basePath = '';

    public function __construct(Config $config, ThemeManager $themeManager, Metadata $metadata)
    {
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->metadata = $metadata;
    }

    protected function getThemeManager()
    {
        return $this->themeManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    protected function getCacheTimestamp()
    {
        if (!$this->getConfig()->get('useCache')) {
            return (string) time();
        }
        return $this->getConfig()->get('cacheTimestamp', 0);
    }

    public function display($runScript = null, $htmlFilePath = null, $vars = [])
    {
        if (is_null($runScript)) {
            $runScript = $this->runScript;
        }
        if (is_null($htmlFilePath)) {
            $htmlFilePath = $this->mainHtmlFilePath;
        }

        $isDeveloperMode = $this->getConfig()->get('isDeveloperMode');

        $cacheTimestamp = $this->getCacheTimestamp();

        if ($isDeveloperMode) {
            $useCache = $this->getConfig()->get('useCacheInDeveloperMode');
            $jsFileList = $this->getMetadata()->get(['app', 'client', 'developerModeScriptList'], []);
            $loaderCacheTimestamp = 'null';
        } else {
            $useCache = $this->getConfig()->get('useCache');
            $jsFileList = $this->getMetadata()->get(['app', 'client', 'scriptList'], []);
            $loaderCacheTimestamp = $cacheTimestamp;
        }

        $cssFileList = $this->getMetadata()->get(['app', 'client', 'cssList'], []);

        $linkList = $this->getMetadata()->get(['app', 'client', 'linkList'], []);

        $scriptsHtml = '';
        foreach ($jsFileList as $jsFile) {
            $src = $this->basePath . $jsFile . '?r=' . $cacheTimestamp;
            $scriptsHtml .= "\n        " .
                "<script type=\"text/javascript\" src=\"{$src}\" data-base-path=\"{$this->basePath}\"></script>";
        }

        $additionalStyleSheetsHtml = '';
        foreach ($cssFileList as $cssFile) {
            $src = $this->basePath . $cssFile . '?r=' . $cacheTimestamp;
            $additionalStyleSheetsHtml .= "\n        <link rel=\"stylesheet\" href=\"{$src}\">";
        }

        $linksHtml = '';
        foreach ($linkList as $item) {
            $href = $this->basePath . $item['href'];
            if (empty($item['noTimestamp'])) {
                $href .= '?r=' . $cacheTimestamp;
            }
            $as = $item['as'] ?? '';
            $rel = $item['rel'] ?? '';
            $type = $item['type'] ?? '';
            $additinalPlaceholder = '';
            if (!empty($item['crossorigin'])) {
                $additinalPlaceholder .= ' crossorigin';
            }
            $linksHtml .= "\n        <link rel=\"{$rel}\" href=\"{$href}\" as=\"{$as}\" as=\"{$type}\"{$additinalPlaceholder}>";
        }

        $data = [
            'applicationId' => 'espocrm-application-id',
            'apiUrl' => 'api/v1',
            'applicationName' => $this->getConfig()->get('applicationName', 'EspoCRM'),
            'cacheTimestamp' => $cacheTimestamp,
            'loaderCacheTimestamp' => $loaderCacheTimestamp,
            'stylesheet' => $this->getThemeManager()->getStylesheet(),
            'runScript' => $runScript,
            'basePath' => $this->basePath,
            'useCache' => $useCache ? 'true' : 'false',
            'appClientClassName' => 'app',
            'scriptsHtml' => $scriptsHtml,
            'additionalStyleSheetsHtml' => $additionalStyleSheetsHtml,
            'linksHtml' => $linksHtml,
        ];

        $html = file_get_contents($htmlFilePath);

        foreach ($vars as $key => $value) {
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $vars)) continue;
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        echo $html;
    }
}
