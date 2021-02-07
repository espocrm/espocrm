<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\{
    Utils\File\Manager as FileManager,
};

/**
 * Renders the main HTML page.
 */
class ClientManager
{
    private $themeManager;

    private $config;

    private $metadata;

    private $fileManager;

    protected $mainHtmlFilePath = 'html/main.html';

    protected $runScript = "app.start();";

    protected $basePath = '';

    public function __construct(
        Config $config, ThemeManager $themeManager, Metadata $metadata, FileManager $fileManager
    ) {
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
    }

    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getBasePath() : string
    {
        return $this->basePath;
    }

    protected function getCacheTimestamp()
    {
        if (!$this->config->get('useCache')) {
            return (string) time();
        }

        return $this->config->get('cacheTimestamp', 0);
    }

    public function display(?string $runScript = null, ?string $htmlFilePath = null, array $vars = []) : void
    {
        echo $this->render($runScript, $htmlFilePath, $vars);
    }

    public function render(?string $runScript = null, ?string $htmlFilePath = null, array $vars = []) : string
    {
        if (is_null($runScript)) {
            $runScript = $this->runScript;
        }

        if (is_null($htmlFilePath)) {
            $htmlFilePath = $this->mainHtmlFilePath;
        }

        $isDeveloperMode = $this->config->get('isDeveloperMode');

        $cacheTimestamp = $this->getCacheTimestamp();

        if ($isDeveloperMode) {
            $useCache = $this->config->get('useCacheInDeveloperMode');
            $jsFileList = $this->metadata->get(['app', 'client', 'developerModeScriptList'], []);

            $loaderCacheTimestamp = 'null';
        }
        else {
            $useCache = $this->config->get('useCache');
            $jsFileList = $this->metadata->get(['app', 'client', 'scriptList'], []);

            $loaderCacheTimestamp = $cacheTimestamp;
        }

        $cssFileList = $this->metadata->get(['app', 'client', 'cssList'], []);

        $linkList = $this->metadata->get(['app', 'client', 'linkList'], []);

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

            $linksHtml .= "\n        " .
                "<link rel=\"{$rel}\" href=\"{$href}\" as=\"{$as}\" as=\"{$type}\"{$additinalPlaceholder}>";
        }

        $favicon196Path = $this->metadata->get(['app', 'client', 'favicon196']) ??
            'client/img/favicon196x196.png';

        $faviconPath = $this->metadata->get(['app', 'client', 'favicon']) ?? 'client/img/favicon.ico';

        $data = [
            'applicationId' => 'espocrm-application-id',
            'apiUrl' => 'api/v1',
            'applicationName' => $this->config->get('applicationName', 'EspoCRM'),
            'cacheTimestamp' => $cacheTimestamp,
            'loaderCacheTimestamp' => $loaderCacheTimestamp,
            'stylesheet' => $this->themeManager->getStylesheet(),
            'runScript' => $runScript,
            'basePath' => $this->basePath,
            'useCache' => $useCache ? 'true' : 'false',
            'appClientClassName' => 'app',
            'scriptsHtml' => $scriptsHtml,
            'additionalStyleSheetsHtml' => $additionalStyleSheetsHtml,
            'linksHtml' => $linksHtml,
            'favicon196Path' => $favicon196Path,
            'faviconPath' => $faviconPath,
            'ajaxTimeout' => $this->config->get('ajaxTimeout') ?? 60000,
        ];

        $html = $this->fileManager->getContents($htmlFilePath);

        foreach ($vars as $key => $value) {
            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $vars)) {
                continue;
            }

            $html = str_replace('{{'.$key.'}}', $value, $html);
        }

        return $html;
    }
}
