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
    Utils\Client\DevModeJsFileListProvider,
    Utils\Module,
    Utils\Json,
};

/**
 * Renders the main HTML page.
 */
class ClientManager
{
    protected $mainHtmlFilePath = 'html/main.html';

    protected $runScript = "app.start();";

    private $basePath = '';

    private $libsConfigPath = 'client/cfg/libs.json';

    private $themeManager;

    private $config;

    private $metadata;

    private $fileManager;

    private $devModeJsFileListProvider;

    private $module;

    private const APP_DESCRIPTION = "EspoCRM - Open Source CRM application.";

    public function __construct(
        Config $config,
        ThemeManager $themeManager,
        Metadata $metadata,
        FileManager $fileManager,
        DevModeJsFileListProvider $devModeJsFileListProvider,
        Module $module
    ) {
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->devModeJsFileListProvider = $devModeJsFileListProvider;
        $this->module = $module;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    protected function getCacheTimestamp(): int
    {
        if (!$this->config->get('useCache')) {
            return time();
        }

        return $this->config->get('cacheTimestamp', 0);
    }

    public function display(?string $runScript = null, ?string $htmlFilePath = null, array $vars = []): void
    {
        echo $this->render($runScript, $htmlFilePath, $vars);
    }

    public function render(?string $runScript = null, ?string $htmlFilePath = null, array $vars = []): string
    {
        if (is_null($runScript)) {
            $runScript = $this->runScript;
        }

        if (is_null($htmlFilePath)) {
            $htmlFilePath = $this->mainHtmlFilePath;
        }

        $cacheTimestamp = $this->getCacheTimestamp();

        $jsFileList = $this->getJsFileList();

        if ($this->config->get('isDeveloperMode')) {
            $useCache = $this->config->get('useCacheInDeveloperMode');
            $loaderCacheTimestamp = 'null';
        }
        else {
            $useCache = $this->config->get('useCache');
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
            $additionalPlaceholder = '';

            if (!empty($item['crossorigin'])) {
                $additionalPlaceholder .= ' crossorigin';
            }

            $linksHtml .= "\n        " .
                "<link rel=\"{$rel}\" href=\"{$href}\" as=\"{$as}\" as=\"{$type}\"{$additionalPlaceholder}>";
        }

        $favicon196Path = $this->metadata->get(['app', 'client', 'favicon196']) ??
            'client/img/favicon196x196.png';

        $faviconPath = $this->metadata->get(['app', 'client', 'favicon']) ?? 'client/img/favicon.ico';

        $internalModuleList = array_map(
            function (string $moduleName): string {
                return Util::fromCamelCase($moduleName, '-');
            },
            $this->module->getInternalList()
        );

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
            'libsConfigPath' => $this->libsConfigPath,
            'internalModuleList' => Json::encode($internalModuleList),
            'applicationDescription' => $this->config->get('applicationDescription') ?? self::APP_DESCRIPTION,
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

    /**
     * @return string[]
     */
    private function getJsFileList(): array
    {
        if ($this->config->get('isDeveloperMode')) {
            return array_merge(
                $this->getDeveloperModeBundleLibFileList(),
                $this->metadata->get(['app', 'client', 'developerModeScriptList']) ?? [],
            );
        }

        return $this->metadata->get(['app', 'client', 'scriptList']) ?? [];
    }

    /**
     * @return string[]
     */
    private function getDeveloperModeBundleLibFileList(): array
    {
        return $this->devModeJsFileListProvider->get();
    }
}
