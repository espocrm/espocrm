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

/**
 * @module utils
 */
define('utils', [], function () {

    const IS_MAC = /Mac/.test(navigator.userAgent);

    /**
     * Utility functions.
     */
    Espo.Utils = {

        /**
         * Process a view event action.
         *
         * @param {module:view.Class} viewObject A view.
         * @param {JQueryKeyEventObject} e An event.
         * @param {string} [action] An action. If not specified, will be fetched from a target element.
         * @param {string} [handler] A handler name.
         */
        handleAction: function (viewObject, e, action, handler) {
            let $target = $(e.currentTarget);

            action = action || $target.data('action');

            let fired = false;

            if (!action) {
                return;
            }

            if (e.ctrlKey || e.metaKey || e.shiftKey) {
                let href = $target.attr('href');

                if (href && href !== 'javascript:') {
                    return;
                }
            }

            let data = $target.data();
            let method = 'action' + Espo.Utils.upperCaseFirst(action);

            handler = handler || data.handler;

            if (typeof viewObject[method] === 'function') {
                viewObject[method].call(viewObject, data, e);

                e.preventDefault();
                e.stopPropagation();

                fired = true;
            }
            else if (handler) {
                e.preventDefault();
                e.stopPropagation();

                fired = true;

                require(handler, function (Handler) {
                    let handler = new Handler(viewObject);

                    handler[method].call(handler, data, e);
                });
            }

            if (!fired) {
                return;
            }

            let $dropdown = $target.closest('.dropdown-menu');

            if (!$dropdown.length) {
                return;
            }

            let $dropdownToggle = $dropdown.parent().find('[data-toggle="dropdown"]');

            if (!$dropdownToggle.length) {
                return;
            }

            let isDisabled = false;

            if ($dropdownToggle.attr('disabled')) {
                isDisabled = true;

                $dropdownToggle.removeAttr('disabled').removeClass('disabled');
            }

            $dropdownToggle.dropdown('toggle');

            if (isDisabled) {
                $dropdownToggle.attr('disabled', 'disabled').addClass('disabled');
            }
        },

        /**
         * @typedef {Object} module:utils~ActionAvailabilityDefs
         *
         * @property {string|null} [configCheck] A config path to check. Path items are separated
         *   by the dot. If a config value is not empty, then the action is allowed.
         *   The `!` prefix reverses the check.
         */

        /**
         * Check action availability.
         *
         * @param {module:view-helper.Class} helper A view helper.
         * @param {module:utils~ActionAvailabilityDefs} item Definitions.
         * @returns {boolean}
         */
        checkActionAvailability: function (helper, item) {
            let config = helper.config;

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
         * @typedef {Object} module:utils~ActionAccessDefs
         *
         * @property {'create'|'read'|'edit'|'stream'|'delete'|null} acl An ACL action to check.
         * @property {string|null} [aclScope] A scope to check.
         * @property {string|null} [scope] Deprecated. Use `aclScope`.
         */

        /**
         * Check access to an action.
         *
         * @param {module:acl-manager.Class} acl An ACL manager.
         * @param {string|module:model.Class|null} [obj] A scope or a model.
         * @param {module:utils~ActionAccessDefs} item Definitions.
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
         * @typedef {Object} module:utils~AccessDefs
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
         * @param {module:acl-manager.Class} acl An ACL manager.
         * @param {module:models/user.Class} user A user.
         * @param {module:model.Class|null} [entity] A model.
         * @param {boolean} [allowAllForAdmin=false] Allow all for an admin.
         * @returns {boolean}
         */
        checkAccessDataList: function (dataList, acl, user, entity, allowAllForAdmin) {
            if (!dataList || !dataList.length) {
                return true;
            }

            for (var i in dataList) {
                var item = dataList[i];

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
                        var inTeam = false;

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
                        var inPortal = false;

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

            var result = string;

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
         * @param {*} obj An object.
         * @returns {*}
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
         * @param {*} data An object.
         * @returns {*}
         */
        cloneDeep: function (data) {
            data = Espo.Utils.clone(data);

            if (Espo.Utils.isObject(data) || _.isArray(data)) {
                for (var i in data) {
                    data[i] = this.cloneDeep(data[i]);
                }
            }

            return data;
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
                var arr = name.split(':');
                var modPart = arr[0];
                var namePart = arr[1];

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

            let options = {};

            if (typeof string !== 'undefined') {
                string.split('&').forEach(item => {
                    let p = item.split('=');

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
         * @param {JQueryKeyEventObject} e A key event.
         * @return {string}
         */
        getKeyFromKeyEvent: function (e) {
            let key = e.code;

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
    };

    /**
     * @deprecated Use `Espo.Utils`.
     */
    Espo.utils = Espo.Utils;

    return Espo.Utils;
});
