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

define('theme-manager', [], function () {

    var ThemeManager = function (config, preferences, metadata) {
        this.config = config;
        this.preferences = preferences;
        this.metadata = metadata;
    };

    _.extend(ThemeManager.prototype, {

        defaultParams: {
            screenWidthXs: 768,
            dashboardCellHeight: 155,
            dashboardCellMargin: 19
        },

        getName: function () {
            if (!this.config.get('userThemesDisabled')) {
                var name = this.preferences.get('theme');
                if (name && name !== '') {
                    return name;
                }
            }
            return this.config.get('theme');
        },

        getAppliedName: function () {
            var name = window.getComputedStyle(document.body).getPropertyValue('--theme-name');
            if (!name) return null;
            return name.trim();
        },

        isApplied: function () {
            var appliedName = this.getAppliedName();
            if (!appliedName) return true;
            return this.getName() === appliedName;
        },

        getStylesheet: function () {
            var link = this.metadata.get(['themes', this.getName(), 'stylesheet']) || 'client/css/espo/espo.css';
            if (this.config.get('cacheTimestamp')) {
                link += '?r=' + this.config.get('cacheTimestamp').toString();
            }
            return link
        },

        getIframeStylesheet: function () {
            var link = this.metadata.get(['themes', this.getName(), 'stylesheetIframe']) || 'client/css/espo/espo-iframe.css';
            if (this.config.get('cacheTimestamp')) {
                link += '?r=' + this.config.get('cacheTimestamp').toString();
            }
            return link
        },

        getParam: function (name) {
            return this.metadata.get(['themes', this.getName(), name]) || this.defaultParams[name] || null;
        },

        isUserTheme: function () {
            if (!this.config.get('userThemesDisabled')) {
                var name = this.preferences.get('theme');
                if (name && name !== '') {
                    if (name !== this.config.get('theme')) {
                        return true;
                    }
                }
            }
            return false;
        }

    });

    return ThemeManager;
});
