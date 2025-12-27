<?php

namespace Espo\Custom\EntryPoints;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Utils\Config;

class ConnectraThemeCss implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private Config $config
    ) {}

    public function run(Request $request, Response $response): void
    {
        $css = $this->generateDynamicCss();

        $response->setHeader('Content-Type', 'text/css; charset=utf-8');
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');
        $response->writeBody($css);
    }

    private function generateDynamicCss(): string
    {
        $css = ':root {' . PHP_EOL;

        $mappings = [
            'flavourTextColor' => '--text-color',
            'flavourBackgroundColor' => '--body-bg',
            'flavourBrandPrimary' => '--brand-primary',
            'flavourBrandInfo' => '--brand-info',
            'flavourBrandDanger' => '--brand-danger',
            'flavourBrandSuccess' => '--brand-success',
            'flavourBrandWarning' => '--brand-warning',
            'flavourNavbarColor' => '--navbar-bg',
            'flavourTabsMenuBgColor' => '--navbar-inverse-bg',
            'flavourLogoBackgroundColor' => '--connectra-logo-bg',
        ];

        foreach ($mappings as $settingKey => $cssVar) {
            $value = $this->config->get($settingKey);
            if ($value) {
                $css .= "    {$cssVar}: {$value};" . PHP_EOL;
            }
        }

        $css .= '}' . PHP_EOL;

        // Add logo position styling
        $logoPosition = $this->config->get('flavourLogoPosition', 'center');
        $css .= $this->getLogoPositionCss($logoPosition);

        // Add side menu hover expand styling
        if ($this->config->get('flavourSideMenuHoverExpand', true)) {
            $css .= $this->getSideMenuHoverCss();
        }

        // Add login background image
        $loginBgImageId = $this->config->get('flavourLoginBackgroundImageId');
        if ($loginBgImageId) {
            $css .= $this->getLoginBackgroundCss($loginBgImageId);
        }

        // Add sidebar menu styling
        $css .= $this->getSidebarMenuCss();

        return $css;
    }

    private function getSidebarMenuCss(): string
    {
        $css = '';

        // Menu item icon color
        $iconColor = $this->config->get('flavourSidenavIconColor');
        if ($iconColor) {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li > a > span.short-label > span {
    color: {$iconColor} !important;
}
body[data-navbar='side'] #navbar ul.tabs > li > a > span.short-label > span.short-label-text {
    color: {$iconColor} !important;
}
";
        }

        // Menu item text color
        $textColor = $this->config->get('flavourSidenavTextColor');
        if ($textColor) {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li > a {
    color: {$textColor} !important;
}
body[data-navbar='side'] #navbar ul.tabs > li > a > span.full-label {
    color: {$textColor} !important;
}
";
        }

        // Menu font size
        $fontSize = $this->config->get('flavourSidenavFontSize');
        if ($fontSize && $fontSize !== '14') {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li > a {
    font-size: {$fontSize}px !important;
}
";
        }

        // Menu font weight
        $fontWeight = $this->config->get('flavourSidenavFontWeight');
        if ($fontWeight && $fontWeight !== 'normal') {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li > a {
    font-weight: {$fontWeight} !important;
}
";
        }

        // Active item icon color
        $activeIconColor = $this->config->get('flavourSidenavActiveIconColor');
        if ($activeIconColor) {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li.active > a > span.short-label > span {
    color: {$activeIconColor} !important;
}
";
        }

        // Active item text color
        $activeFontColor = $this->config->get('flavourSidenavActiveFontColor');
        if ($activeFontColor) {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li.active > a {
    color: {$activeFontColor} !important;
}
body[data-navbar='side'] #navbar ul.tabs > li.active > a > span.full-label {
    color: {$activeFontColor} !important;
}
";
        }

        // Active item background color
        $activeBgColor = $this->config->get('flavourSidenavActiveBgColor');
        if ($activeBgColor) {
            $css .= "
body[data-navbar='side'] #navbar ul.tabs > li.active > a {
    background-color: {$activeBgColor} !important;
}
";
        }

        return $css;
    }

    private function getLogoPositionCss(string $position): string
    {
        $flexAlignment = match ($position) {
            'left' => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };

        return "
#login .panel-heading .logo-container,
.navbar-logo-container {
    justify-content: {$flexAlignment};
    display: flex;
}
";
    }

    private function getSideMenuHoverCss(): string
    {
        return "
body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar .navbar {
    transition: width 0.25s ease-in-out;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover .navbar {
    width: var(--navbar-width);
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover .navbar-logo-container {
    display: flex;
    opacity: 1;
    transition: opacity 0.2s ease-in-out 0.1s;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover .navbar-brand img {
    display: inline;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs {
    width: var(--navbar-width);
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs > li > a > span.full-label {
    display: inline-block;
    opacity: 1;
    transition: opacity 0.2s ease-in-out 0.1s;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs > li > a > span.short-label {
    display: none;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs > li > a > span.short-label > .short-label-text {
    visibility: hidden;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs > li.tab-divider > div > .label-text {
    display: inline;
    opacity: 1;
    transition: opacity 0.2s ease-in-out 0.1s;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover ul.tabs > li.tab-group .group-caret {
    right: var(--13px);
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:hover a.minimizer {
    display: inline-block;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:not(:hover) .navbar-logo-container {
    opacity: 0;
    transition: opacity 0.1s ease-in-out;
}

body[data-navbar='side'].minimized:not(.side-menu-opened) #navbar:not(:hover) ul.tabs > li > a > span.full-label {
    opacity: 0;
    transition: opacity 0.1s ease-in-out;
}
";
    }

    private function getLoginBackgroundCss(string $imageId): string
    {
        return "
body > .container.content:has(#login) {
    background-image: url('?entryPoint=Image&id={$imageId}');
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    min-height: 100vh;
}

body > .container.content:has(#login) #login .panel {
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
}
";
    }
}
