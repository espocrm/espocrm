/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

    /**
     * A theme manager.
     *
     * @class
     * @name Class
     * @memberOf module:theme-manager
     *
     * @param {module:models/settings.Class} config A config.
     * @param {module:models/preferences.Class} preferences Preferences.
     * @param {module:metadata.Class} metadata Metadata.
     * @param {?string} [name] A name. If not set, then will be obtained from config and preferences.
     */
    let ThemeManager = function (config, preferences, metadata, name) {
        /**
         * @private
         * @type {module:models/settings.Class}
         */
        this.config = config;
        /**
         * @private
         * @type {module:models/preferences.Class}
         */
        this.preferences = preferences;
        /**
         * @private
         * @type {module:metadata.Class}
         */
        this.metadata = metadata;

        /**
         * @private
         * @type {?string}
         */
        this.name = name || null;
    };

    _.extend(ThemeManager.prototype, /** module:theme-manager.Class# */{

        /**
         * @private
         */
        defaultParams: {
            screenWidthXs: 768,
            dashboardCellHeight: 155,
            dashboardCellMargin: 19,
        },

        /**
         * Get a theme name for the current user.
         *
         * @returns {string}
         */
        getName: function () {
            if (this.name) {
                return this.name;
            }

            if (!this.config.get('userThemesDisabled')) {
                let name = this.preferences.get('theme');

                if (name && name !== '') {
                    return name;
                }
            }

            return this.config.get('theme');
        },

        /**
         * Get a theme name currently applied to the DOM.
         *
         * @returns {string|null} Null if not applied.
         */
        getAppliedName: function () {
            let name = window.getComputedStyle(document.body).getPropertyValue('--theme-name');

            if (!name) {
                return null;
            }

            return name.trim();
        },

        /**
         * Whether a current theme is applied to the DOM.
         *
         * @returns {boolean}
         */
        isApplied: function () {
            let appliedName = this.getAppliedName();

            if (!appliedName) {
                return true;
            }

            return this.getName() === appliedName;
        },

        /**
         * Get a stylesheet path for a current theme.
         *
         * @returns {string}
         */
        getStylesheet: function () {
            let link = this.getParam('stylesheet') || 'client/css/espo/espo.css';

            if (this.config.get('cacheTimestamp')) {
                link += '?r=' + this.config.get('cacheTimestamp').toString();
            }

            return link;
        },

        /**
         * Get an iframe stylesheet path for a current theme.
         *
         * @returns {string}
         */
        getIframeStylesheet: function () {
            let link = this.getParam('stylesheetIframe') || 'client/css/espo/espo-iframe.css';

            if (this.config.get('cacheTimestamp')) {
                link += '?r=' + this.config.get('cacheTimestamp').toString();
            }

            return link;
        },

        /**
         * Get an iframe-fallback stylesheet path for a current theme.
         *
         * @returns {string}
         */
        getIframeFallbackStylesheet: function () {
            let link = this.getParam('stylesheetIframeFallback') || 'client/css/espo/espo-iframe.css'

            if (this.config.get('cacheTimestamp')) {
                link += '?r=' + this.config.get('cacheTimestamp').toString();
            }

            return link;
        },

        /**
         * Get a theme parameter.
         *
         * @param {string} name A parameter name.
         * @returns {*} Null if not set.
         */
        getParam: function (name) {
            if (name !== 'params' && name !== 'mappedParams') {
                let varValue = this.getVarParam(name);

                if (varValue !== null) {
                    return varValue;
                }

                let mappedValue = this.getMappedParam(name);

                if (mappedValue !== null) {
                    return mappedValue;
                }
            }

            let value = this.metadata.get(['themes', this.getName(), name]);

            if (value !== null) {
                return value;
            }

            value = this.metadata.get(['themes', this.getParentName(), name]);

            if (value !== null) {
                return value;
            }

            return this.defaultParams[name] || null;
        },

        /**
         * @private
         * @param {string} name
         * @returns {*}
         */
        getVarParam: function (name) {
            let params = this.getParam('params') || {};

            if (!(name in params)) {
                return null;
            }

            let values = null;

            if (!this.config.get('userThemesDisabled') && this.preferences.get('theme')) {
                values = this.preferences.get('themeParams');
            }

            if (!values) {
                values = this.config.get('themeParams');
            }

            if (values && (name in values)) {
                return values[name];
            }

            if ('default' in params[name]) {
                return params[name].default;
            }

            return null;
        },

        /**
         * @private
         * @param {string} name
         * @returns {*}
         */
        getMappedParam: function (name) {
            let mappedParams = this.getParam('mappedParams') || {};

            if (!(name in mappedParams)) {
                return null;
            }

            let mapped = mappedParams[name].param;
            let valueMap = mappedParams[name].valueMap;

            if (mapped && valueMap) {
                let key = this.getParam(mapped);

                return valueMap[key];
            }

            return null;
        },

        /**
         * @private
         * @returns {string}
         */
        getParentName: function () {
            return this.metadata.get(['themes', this.getName(), 'parent']) || 'Espo';
        },

        /**
         * Whether a current theme is different from a system default theme.
         *
         * @returns {boolean}
         */
        isUserTheme: function () {
            if (this.config.get('userThemesDisabled')) {
                return false;
            }

            let name = this.preferences.get('theme');

            if (!name || name === '') {
                return false;
            }

            return name !== this.config.get('theme');
        },
    });

    return ThemeManager;
});
