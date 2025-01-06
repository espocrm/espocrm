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

/** @module utils */

const IS_MAC = /Mac/.test(navigator.userAgent);

/**
 * Utility functions.
 */
Espo.Utils = {

    /**
     * Handle a click event action.
     *
     * @param {module:view} view A view.
     * @param {MouseEvent} event An event.
     * @param {HTMLElement} element An  element.
     * @param {{
     *     action?: string,
     *     handler?: string,
     *     actionFunction?: string,
     *     actionItems?: Array<{
     *         onClick?: function(),
     *         name?: string,
     *         handler?: string,
     *         actionFunction?: string,
     *     }>,
     *     className?: string,
     * }} [actionData] Data. If an action is not specified, it will be fetched from a target element.
     * @return {boolean} True if handled.
     */
    handleAction: function (view, event, element, actionData) {
        actionData = actionData || {};

        const $target = $(element);
        const action = actionData.action || $target.data('action');

        const name = $target.data('name') || action;

        let method;
        let handler;

        if (
            name &&
            actionData.actionItems &&
            (
                !actionData.className ||
                element.classList.contains(actionData.className)
            )
        ) {
            const data = actionData.actionItems.find(item => {
                return item.name === name || item.action === name;
            });

            if (data && data.onClick) {
                data.onClick();

                return true;
            }

            if (data) {
                handler = data.handler;
                method = data.actionFunction;
            }
        }

        if (!action && !actionData.actionFunction && !method) {
            return false;
        }

        if (event.ctrlKey || event.metaKey || event.shiftKey) {
            const href = $target.attr('href');

            if (href && href !== 'javascript:') {
                return false;
            }
        }

        const data = $target.data();
        method = actionData.actionFunction || method || 'action' + Espo.Utils.upperCaseFirst(action);
        handler = actionData.handler || handler || data.handler;

        let fired = false;

        if (handler) {
            event.preventDefault();
            event.stopPropagation();

            fired = true;

            Espo.loader.require(handler, Handler => {
                const handler = new Handler(view);

                handler[method].call(handler, data, event);
            });
        }
        else if (typeof view[method] === 'function') {
            view[method].call(view, data, event);

            event.preventDefault();
            event.stopPropagation();

            fired = true;
        }

        if (!fired) {
            return false;
        }

        this._processAfterActionDropdown($target);

        return true;
    },

    /**
     * @private
     * @param {JQuery} $target
     */
    _processAfterActionDropdown: function ($target) {
        const $dropdown = $target.closest('.dropdown-menu');

        if (!$dropdown.length) {
            return;
        }

        const $dropdownToggle = $dropdown.parent().find('[data-toggle="dropdown"]');

        if (!$dropdownToggle.length) {
            return;
        }

        let isDisabled = false;

        if ($dropdownToggle.attr('disabled')) {
            isDisabled = true;

            $dropdownToggle.removeAttr('disabled').removeClass('disabled');
        }

        // noinspection JSUnresolvedReference
        $dropdownToggle.dropdown('toggle');

        $dropdownToggle.focus();

        if (isDisabled) {
            $dropdownToggle.attr('disabled', 'disabled').addClass('disabled');
        }
    },

    /**
     * @typedef {Object} Espo.Utils~ActionAvailabilityDefs
     *
     * @property {string|null} [configCheck] A config path to check. Path items are separated
     *   by the dot. If a config value is not empty, then the action is allowed.
     *   The `!` prefix reverses the check.
     */

    /**
     * Check action availability.
     *
     * @param {module:view-helper} helper A view helper.
     * @param {Espo.Utils~ActionAvailabilityDefs} item Definitions.
     * @returns {boolean}
     */
    checkActionAvailability: function (helper, item) {
        const config = helper.config;

        if (item.configCheck) {
            let configCheck = item.configCheck;

            let opposite = false;

            if (configCheck.substring(0, 1) === '!') {
                opposite = true;

                configCheck = configCheck.substring(1);
            }

            let configCheckResult = config.getByPath(configCheck.split('.'));

            if (opposite) {
                configCheckResult = !configCheckResult;
            }

            if (!configCheckResult) {
                return false;
            }
        }

        return true;
    },

    /**
     * @typedef {Object} Espo.Utils~ActionAccessDefs
     *
     * @property {'create'|'read'|'edit'|'stream'|'delete'|null} acl An ACL action to check.
     * @property {string|null} [aclScope] A scope to check.
     * @property {string|null} [scope] Deprecated. Use `aclScope`.
     */

    /**
     * Check access to an action.
     *
     * @param {module:acl-manager} acl An ACL manager.
     * @param {string|module:model|null} [obj] A scope or a model.
     * @param {Espo.Utils~ActionAccessDefs} item Definitions.
     * @param {boolean} [isPrecise=false] To return `null` if not enough data is set in a model.
     *   E.g. the `teams` field is not yet loaded.
     * @returns {boolean|null}
     */
    checkActionAccess: function (acl, obj, item, isPrecise) {
        let hasAccess = true;

        if (item.acl) {
            if (!item.aclScope) {
                if (obj) {
                    if (typeof obj === 'string' || obj instanceof String) {
                        hasAccess = acl.check(obj, item.acl);
                    }
                    else {
                        hasAccess = acl.checkModel(obj, item.acl, isPrecise);
                    }
                }
                else {
                    hasAccess = acl.check(item.scope, item.acl);
                }
            }
            else {
                hasAccess = acl.check(item.aclScope, item.acl);
            }
        }
        else if (item.aclScope) {
            hasAccess = acl.checkScope(item.aclScope);
        }

        return hasAccess;
    },

    /**
     * @typedef {Object} Espo.Utils~AccessDefs
     *
     * @property {'create'|'read'|'edit'|'stream'|'delete'|null} action An ACL action to check.
     * @property {string|null} [scope] A scope to check.
     * @property {string[]} [portalIdList] A portal ID list. To check whether a user in one of portals.
     * @property {string[]} [teamIdList] A team ID list. To check whether a user in one of teams.
     * @property {boolean} [isPortalOnly=false] Allow for portal users only.
     * @property {boolean} [inPortalDisabled=false] Disable for portal users.
     * @property {boolean} [isAdminOnly=false] Allow for admin users only.
     */

    /**
     * Check access to an action.
     *
     * @param {module:utils~AccessDefs[]} dataList List of definitions.
     * @param {module:acl-manager} acl An ACL manager.
     * @param {module:models/user} user A user.
     * @param {module:model|null} [entity] A model.
     * @param {boolean} [allowAllForAdmin=false] Allow all for an admin.
     * @returns {boolean}
     */
    checkAccessDataList: function (dataList, acl, user, entity, allowAllForAdmin) {
        if (!dataList || !dataList.length) {
            return true;
        }

        for (const i in dataList) {
            const item = dataList[i];

            if (item.scope) {
                if (item.action) {
                    if (!acl.check(item.scope, item.action)) {
                        return false;
                    }
                } else {
                    if (!acl.checkScope(item.scope)) {
                        return false;
                    }
                }
            } else if (item.action) {
                if (entity) {
                    if (!acl.check(entity, item.action)) {
                        return false;
                    }
                }
            }

            if (item.teamIdList) {
                if (user && !(allowAllForAdmin && user.isAdmin())) {
                    let inTeam = false;

                    user.getLinkMultipleIdList('teams').forEach(teamId => {
                        if (~item.teamIdList.indexOf(teamId)) {
                            inTeam = true;
                        }
                    });

                    if (!inTeam) {
                        return false;
                    }
                }
            }

            if (item.portalIdList) {
                if (user && !(allowAllForAdmin && user.isAdmin())) {
                    let inPortal = false;

                    user.getLinkMultipleIdList('portals').forEach(portalId => {
                        if (~item.portalIdList.indexOf(portalId)) {
                            inPortal = true;
                        }
                    });

                    if (!inPortal) {
                        return false;
                    }
                }
            }

            if (item.isPortalOnly) {
                if (user && !(allowAllForAdmin && user.isAdmin())) {
                    if (!user.isPortal()) {
                        return false;
                    }
                }
            }
            else if (item.inPortalDisabled) {
                if (user && !(allowAllForAdmin && user.isAdmin())) {
                    if (user.isPortal()) {
                        return false;
                    }
                }
            }

            if (item.isAdminOnly) {
                if (user) {
                    if (!user.isAdmin()) {
                        return false;
                    }
                }
            }
        }

        return true;
    },

    /**
     * @private
     * @param {string} string
     * @param {string} p
     * @returns {string}
     */
    convert: function (string, p) {
        if (string === null) {
            return string;
        }

        let result = string;

        switch (p) {
            case 'c-h':
            case 'C-h':
                result = Espo.Utils.camelCaseToHyphen(string);

                break;

            case 'h-c':
                result = Espo.Utils.hyphenToCamelCase(string);

                break;

            case 'h-C':
                result = Espo.Utils.hyphenToUpperCamelCase(string);

                break;
        }

        return result;
    },

    /**
     * Is object.
     *
     * @param {*} obj What to check.
     * @returns {boolean}
     */
    isObject: function (obj) {
        if (obj === null) {
            return false;
        }

        return typeof obj === 'object';
    },

    /**
     * A shallow clone.
     *
     * @template {*} TObject
     * @param {TObject} obj An object.
     * @returns {TObject}
     */
    clone: function (obj) {
        if (!Espo.Utils.isObject(obj)) {
            return obj;
        }

        return _.isArray(obj) ? obj.slice() : _.extend({}, obj);
    },

    /**
     * A deep clone.
     *
     * @template {*} TObject
     * @param {TObject} data An object.
     * @returns {TObject}
     */
    cloneDeep: function (data) {
        data = Espo.Utils.clone(data);

        if (Espo.Utils.isObject(data) || _.isArray(data)) {
            for (const i in data) {
                data[i] = this.cloneDeep(data[i]);
            }
        }

        return data;
    },

    /**
     * Deep comparison.
     *
     * @param {Object} a1 An argument 1.
     * @param {Object} a2 An argument 2.
     * @return {boolean}
     */
    areEqual: function (a1, a2) {
        return _.isEqual(a1, a2);
    },

    /**
     * Compose a class name.
     *
     * @param {string} module A module.
     * @param {string} name A name.
     * @param {string} [location=''] A location.
     * @return {string}
     */
    composeClassName: function (module, name, location) {
        if (module) {
            module = this.camelCaseToHyphen(module);
            name = this.camelCaseToHyphen(name).split('.').join('/');
            location = this.camelCaseToHyphen(location || '');

            return module + ':' + location + '/' + name;
        }
        else {
            name = this.camelCaseToHyphen(name).split('.').join('/');

            return location + '/' + name;
        }
    },

    /**
     * Compose a view class name.
     *
     * @param {string} name A name.
     * @returns {string}
     */
    composeViewClassName: function (name) {
        if (name && name[0] === name[0].toLowerCase()) {
            return name;
        }

        if (name.indexOf(':') !== -1) {
            const arr = name.split(':');
            let modPart = arr[0];
            let namePart = arr[1];

            modPart = this.camelCaseToHyphen(modPart);
            namePart = this.camelCaseToHyphen(namePart).split('.').join('/');

            return modPart + ':' + 'views' + '/' + namePart;
        }
        else {
            name = this.camelCaseToHyphen(name).split('.').join('/');

            return 'views' + '/' + name;
        }
    },

    /**
     * Convert a string from camelCase to hyphen and replace dots with hyphens.
     * Useful for setting to DOM attributes.
     *
     * @param {string} string A string.
     * @returns {string}
     */
    toDom: function (string) {
        return Espo.Utils.convert(string, 'c-h')
            .split('.')
            .join('-');
    },

    /**
     * Lower-case a first character.
     *
     * @param  {string} string A string.
     * @returns {string}
     */
    lowerCaseFirst: function (string) {
        if (string === null) {
            return string;
        }

        return string.charAt(0).toLowerCase() + string.slice(1);
    },

    /**
     * Upper-case a first character.
     *
     * @param  {string} string A string.
     * @returns {string}
     */
    upperCaseFirst: function (string) {
        if (string === null) {
            return string;
        }

        return string.charAt(0).toUpperCase() + string.slice(1);
    },

    /**
     * Hyphen to UpperCamelCase.
     *
     * @param {string} string A string.
     * @returns {string}
     */
    hyphenToUpperCamelCase: function (string) {
        if (string === null) {
            return string;
        }

        return this.upperCaseFirst(
            string.replace(
                /-([a-z])/g,
                function (g) {
                    return g[1].toUpperCase();
                }
            )
        );
    },

    /**
     * Hyphen to camelCase.
     *
     * @param {string} string A string.
     * @returns {string}
     */
    hyphenToCamelCase: function (string) {
        if (string === null) {
            return string;
        }

        return string.replace(
            /-([a-z])/g,
            function (g) {
                return g[1].toUpperCase();
            }
        );
    },

    /**
     * CamelCase to hyphen.
     *
     * @param {string} string A string.
     * @returns {string}
     */
    camelCaseToHyphen: function (string) {
        if (string === null) {
            return string;
        }

        return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    },

    /**
     * Trim an ending slash.
     *
     * @param {String} str A string.
     * @returns {string}
     */
    trimSlash: function (str) {
        if (str.slice(-1) === '/') {
            return str.slice(0, -1);
        }

        return str;
    },

    /**
     * Parse params in string URL options.
     *
     * @param {string} string An URL part.
     * @returns {Object.<string,string>}
     */
    parseUrlOptionsParam: function (string) {
        if (!string) {
            return {};
        }

        if (string.indexOf('&') === -1 && string.indexOf('=') === -1) {
            return {};
        }

        const options = {};

        if (typeof string !== 'undefined') {
            string.split('&').forEach(item => {
                const p = item.split('=');

                options[p[0]] = true;

                if (p.length > 1) {
                    options[p[0]] = p[1];
                }
            });
        }

        return options;
    },

    /**
     * Key a key from a key-event.
     *
     * @param {JQueryKeyEventObject|KeyboardEvent} e A key event.
     * @return {string}
     */
    getKeyFromKeyEvent: function (e) {
        let key = e.code;

        key = keyMap[key] || key;

        if (e.shiftKey) {
            key = 'Shift+' + key;
        }

        if (e.altKey) {
            key = 'Alt+' + key;
        }

        if (IS_MAC ? e.metaKey : e.ctrlKey) {
            key = 'Control+' + key;
        }

        return key;
    },

    /**
     * Generate an ID. Not to be used by 3rd party code.
     *
     * @internal
     * @return {string}
     */
    generateId: function () {
        return (Math.floor(Math.random() * 10000001)).toString()
    },

    /**
     * Not to be used in custom code. Can be removed in future versions.
     * @internal
     * @return {string}
     */
    obtainBaseUrl: function () {
        let baseUrl = window.location.origin + window.location.pathname;

        if (baseUrl.slice(-1) !== '/') {
            baseUrl = window.location.pathname.includes('.') ?
                baseUrl.slice(0, baseUrl.lastIndexOf('/')) + '/' :
                baseUrl + '/';
        }

        return baseUrl;
    }
};

const keyMap = {
    'NumpadEnter': 'Enter',
};

/**
 * @deprecated Use `Espo.Utils`.
 */
Espo.utils = Espo.Utils;

export default Espo.Utils;
