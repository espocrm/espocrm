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

/** @module theme-manager */

/**
 * A theme manager.
 */
class ThemeManager {

    /**
     * @param {module:models/settings} config A config.
     * @param {module:models/preferences} preferences Preferences.
     * @param {module:metadata} metadata Metadata.
     * @param {string|null} [name] A name. If not set, then will be obtained from config and preferences.
     */
    constructor(config, preferences, metadata, name) {
        /**
         * @private
         * @type {module:models/settings}
         */
        this.config = config;

        /**
         * @private
         * @type {module:models/preferences}
         */
        this.preferences = preferences;

        /**
         * @private
         * @type {module:metadata}
         */
        this.metadata = metadata;

        /**
         * @private
         * @type {boolean}
         */
        this.useConfig = !name;

        /**
         * @private
         * @type {string|null}
         */
        this.name = name || null;
    }

    /**
     * @private
     */
    defaultParams = {
        screenWidthXs: 768,
        dashboardCellHeight: 40,
        dashboardCellMargin: 16,
    }

    /**
     * Get a theme name for the current user.
     *
     * @returns {string}
     */
    getName() {
        if (this.name) {
            return this.name;
        }

        if (!this.config.get('userThemesDisabled')) {
            const name = this.preferences.get('theme');

            if (name && name !== '') {
                return name;
            }
        }

        return this.config.get('theme');
    }

    /**
     * Get a theme name currently applied to the DOM.
     *
     * @returns {string|null} Null if not applied.
     */
    getAppliedName() {
        const name = window.getComputedStyle(document.body).getPropertyValue('--theme-name');

        if (!name) {
            return null;
        }

        return name.trim();
    }

    /**
     * Whether a current theme is applied to the DOM.
     *
     * @returns {boolean}
     */
    isApplied() {
        const appliedName = this.getAppliedName();

        if (!appliedName) {
            return true;
        }

        return this.getName() === appliedName;
    }

    /**
     * Get a stylesheet path for a current theme.
     *
     * @returns {string}
     */
    getStylesheet() {
        let link = this.getParam('stylesheet') || 'client/css/espo/espo.css';

        if (this.config.get('cacheTimestamp')) {
            link += '?r=' + this.config.get('cacheTimestamp').toString();
        }

        return link;
    }

    /**
     * Get an iframe stylesheet path for a current theme.
     *
     * @returns {string}
     */
    getIframeStylesheet() {
        let link = this.getParam('stylesheetIframe') || 'client/css/espo/espo-iframe.css';

        if (this.config.get('cacheTimestamp')) {
            link += '?r=' + this.config.get('cacheTimestamp').toString();
        }

        return link;
    }

    /**
     * Get an iframe-fallback stylesheet path for a current theme.
     *
     * @returns {string}
     */
    getIframeFallbackStylesheet() {
        let link = this.getParam('stylesheetIframeFallback') || 'client/css/espo/espo-iframe.css'

        if (this.config.get('cacheTimestamp')) {
            link += '?r=' + this.config.get('cacheTimestamp').toString();
        }

        return link;
    }

    /**
     * Get a theme parameter.
     *
     * @param {string} name A parameter name.
     * @returns {*} Null if not set.
     */
    getParam(name) {
        if (name !== 'params' && name !== 'mappedParams') {
            const varValue = this.getVarParam(name);

            if (varValue !== null) {
                return varValue;
            }

            const mappedValue = this.getMappedParam(name);

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
    }

    /**
     * @private
     * @param {string} name
     * @returns {*}
     */
    getVarParam(name) {
        const params = this.getParam('params') || {};

        if (!(name in params)) {
            return null;
        }

        let values = null;

        if (
            this.useConfig &&
            !this.config.get('userThemesDisabled') &&
            this.preferences.get('theme')
        ) {
            values = this.preferences.get('themeParams');
        }

        if (!values && this.useConfig) {
            values = this.config.get('themeParams');
        }

        if (values && (name in values)) {
            return values[name];
        }

        if ('default' in params[name]) {
            return params[name].default;
        }

        return null;
    }

    /**
     * @private
     * @param {string} name
     * @returns {*}
     */
    getMappedParam(name) {
        const mappedParams = this.getParam('mappedParams') || {};

        if (!(name in mappedParams)) {
            return null;
        }

        const mapped = mappedParams[name].param;
        const valueMap = mappedParams[name].valueMap;

        if (mapped && valueMap) {
            const key = this.getParam(mapped);

            return valueMap[key];
        }

        return null;
    }

    /**
     * @private
     * @returns {string}
     */
    getParentName() {
        return this.metadata.get(['themes', this.getName(), 'parent']) || 'Espo';
    }

    /**
     * Whether a current theme is different from a system default theme.
     *
     * @returns {boolean}
     */
    isUserTheme() {
        if (this.config.get('userThemesDisabled')) {
            return false;
        }

        const name = this.preferences.get('theme');

        if (!name || name === '') {
            return false;
        }

        return name !== this.config.get('theme');
    }

    /**
     * Get a font-size factor. To adjust px sizes based on font-size.
     *
     * @return {number}
     * @since 9.0.0
     * @internal Experimental.
     */
    getFontSizeFactor() {
        const paramFontSize = this.getParam('fontSize') || 14;
        const fontSize = parseInt(getComputedStyle(document.body).fontSize);

        return Math.round(fontSize / paramFontSize * 10000) / 10000;
    }
}

export default ThemeManager;
