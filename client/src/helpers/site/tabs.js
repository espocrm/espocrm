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

export default class TabsHelper {

    /**
     * @param {import('models/settings').default} config
     * @param {import('models/preferences').default} preferences
     * @param {import('models/user').default} user
     * @param {import('acl-manager').default} acl
     * @param {import('metadata').default} metadata
     * @param {import('language').default} language
     */
    constructor(config, preferences, user, acl, metadata, language) {
        /** @private */
        this.config = config;
        /** @private */
        this.preferences = preferences;
        /** @private */
        this.user = user;
        /** @private */
        this.acl = acl;
        /** @private */
        this.metadata = metadata;
        /** @private */
        this.language = language;
    }

    /**
     * @typedef {Object} TabsHelper~item
     * @property {string} [url]
     * @property {string} [text]
     * @property {'url'|'divider'} [type]
     * @property {(TabsHelper~item|string)[]} [itemList]
     */

    /**
     * Get the tab list.
     *
     * @return {(TabsHelper~item|string)[]}
     */
    getTabList() {
        let tabList = this.preferences.get('useCustomTabList') && !this.preferences.get('addCustomTabs') ?
            this.preferences.get('tabList') :
            this.config.get('tabList');

        if (this.preferences.get('useCustomTabList') && this.preferences.get('addCustomTabs')) {
            tabList = [
                ...tabList,
                ...(this.preferences.get('tabList') || []),
            ];
        }

        return Espo.Utils.cloneDeep(tabList) || [];
    }

    /**
     * Is a tab a divider.
     *
     * @param {string|{type?: string}} item
     */
    isTabDivider(item) {
        return typeof item === 'object' && item.type === 'divider';
    }

    /**
     * Is a tab more-delimiter.
     *
     * @param {string|{type?: string}} item
     */
    isTabMoreDelimiter(item) {
        return item === '_delimiter_' || item === '_delimiter-ext_';
    }

    /**
     * Is a tab a URL.
     *
     * @param {string|{type?: string}} item
     */
    isTabUrl(item) {
        return typeof item === 'object' && item.type === 'url';
    }

    /**
     * Is a tab a group.
     *
     * @param {string|{type?: string}} item
     */
    isTabGroup(item) {
        if (!this.isTabDivider(item) && !this.isTabUrl(item) && typeof item === 'object') {
            return true;
        }

        return false;
    }

    /**
     * Is a tab a scope.
     *
     * @param {string|{type?: string}} item
     */
    isTabScope(item) {
        if (typeof item === 'object' || this.isTabMoreDelimiter(item) || item === 'Home') {
            return false;
        }

        return true;
    }

    /**
     * Get a translated tab label.
     *
     * @param {{text?: string}|string} item
     */
    getTranslatedTabLabel(item) {
        const translateLabel = label => {
            if (label.indexOf('$') === 0) {
                return this.language.translate(label.slice(1), 'navbarTabs');
            }

            return label;
        };

        if (this.isTabDivider(item) || this.isTabUrl(item) || this.isTabUrl(item) || this.isTabGroup(item)) {
            if (item.text) {
                return translateLabel(item.text);
            }

            return ''
        }

        if (item === 'Home') {
            return this.language.translate('Home');
        }

        if (typeof item === 'object') {
            return '';
        }

        return this.language.translate(item, 'scopeNamesPlural');
    }

    /**
     * Check tab access.
     *
     * @param {Record|string} item
     * @return {boolean}
     */
    checkTabAccess(item) {
        if (this.isTabUrl(item)) {
            if (item.onlyAdmin && !this.user.isAdmin()) {
                return false;
            }

            if (!item.aclScope) {
                return true;
            }

            return this.acl.check(item.aclScope);
        }

        if (item === 'Home' || this.isTabMoreDelimiter(item)) {
            return true;
        }

        /** @type {Record<string, {disabled?: boolean, acl?: boolean, tabAclPermission?: string}>} */
        const scopes = this.metadata.get('scopes') || {};

        if (!scopes[item]) {
            return false;
        }

        const defs = scopes[item] || {};

        if (defs.disabled) {
            return false;
        }

        if (defs.acl) {
            return this.acl.check(item);
        }

        if (defs.tabAclPermission) {
            const level = this.acl.getPermissionLevel(defs.tabAclPermission);

            return level && level !== 'no';
        }

        return true;
    }
}
