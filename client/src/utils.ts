/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import type View from 'view';
import type ViewHelper from 'view-helper';
import type AclManager from 'acl-manager';
import type Model from 'model';
import type User from 'models/user';
import _ from 'underscore';

export interface AccessDefs {
    /**
     * An ACL action to check.
     */
    action: 'create' | 'read' | 'edit' | 'stream' | 'delete' | null;
    /**
     * A scope to check.
     */
    scope?: string | null;
    /**
     * A portal ID list. To check whether a user in one of portals.
     */
    portalIdList?: string[];
    /**
     * A team ID list. To check whether a user in one of teams.
     */
    teamIdList?: string[];
    /**
     * Allow for portal users only.
     */
    isPortalOnly?: boolean;
    /**
     * Disable for portal users.
     */
    inPortalDisabled?: boolean;
    /**
     * Allow for admin users only.
     */
    isAdminOnly?: boolean;
}

export interface ActionAvailabilityDefs {
    /**
     * A config path to check. Path items are separated
     * by the dot. If a config value is not empty, then the action is allowed.
     * The `!` prefix reverses the check.
     */
    configCheck?: string | null;
}

export interface ActionAccessDefs {
    /**
     * An ACL action to check.
     */
    acl?: 'create' | 'read' | 'edit' | 'stream' | 'delete' | null;
    /**
     * A scope to check.
     */
    aclScope?: string | null;
    /**
     * @deprecated Use `aclScope`.
     */
    scope?: string | null;
}

const IS_MAC = /Mac/.test(navigator.userAgent);

/**
 * Utility functions.
 */
const Utils = {

    /**
     * Handle a click event action.
     *
     * @param view A view.
     * @param event An event.
     * @param element An  element.
     * @param actionData Data. If an action is not specified, it will be fetched from a target element.
     * @return True if handled.
     */
    handleAction: function (
        view: View<any>,
        event: MouseEvent,
        element: HTMLElement,
        actionData?: {
            action?: string;
            handler?: string;
            actionFunction?: string;
            actionItems?: ({
                onClick?: () => void;
                name?: string | null;
                handler?: string;
                actionFunction?: string;
                /**
                 * @internal
                 */
                action?: string | null;
            } | false)[];
            className?: string;
        },
    ): boolean {

        actionData = actionData || {};

        const $target = $(element);

        const action = actionData.action || element.dataset.action || null;
        const name = element.dataset.name || action;

        let method: string | null = null;
        let handler: string | null = null;

        if (
            name &&
            actionData.actionItems &&
            (
                !actionData.className ||
                element.classList.contains(actionData.className)
            )
        ) {
            const data = actionData.actionItems.find(item => {
                return item && (item.name === name || item.action === name);
            });

            if (data && data.onClick) {
                data.onClick();

                return true;
            }

            if (data) {
                handler = data.handler ?? null;
                method = data.actionFunction ?? null;
            }
        }

        if (!action && !actionData.actionFunction && !method) {
            return false;
        }

        if (event.ctrlKey || event.metaKey || event.shiftKey) {
            const href = element.getAttribute('href');

            if (href && href !== 'javascript:') {
                return false;
            }
        }

        const data = $target.data();

        method = actionData.actionFunction ?? method ?? null;

        if (!method) {
            if (!action) {
                throw new Error(`No action.`);
            }

            method = 'action' + Utils.upperCaseFirst(action);
        }

        // @todo Drop `data.handler` support in v10.1.
        handler = actionData.handler ?? handler ?? data.handler ?? null;

        let fired = false;

        if (handler) {
            event.preventDefault();
            event.stopPropagation();

            fired = true;

            Espo.loader.require(handler, Handler => {
                const handler = new Handler(view);

                handler[method].call(handler, data, event);
            });
        } else if (
            // @ts-ignore
            typeof view[method] === 'function'
        ) {
            // @ts-ignore
            if (view?.events[`click [data-action="${action}"]`]) {
                // Prevents from firing if a handler is already assigned. Important.
                // Does not prevent if handled from a nested view. @todo
                return false;
            }

            // @ts-ignore
            view[method].call(view, data, event);

            event.preventDefault();
            event.stopPropagation();

            fired = true;
        }

        if (!fired) {
            return false;
        }

        _processAfterActionDropdown($target);

        return true;
    },

    /**
     * Check action availability.
     *
     * @param helper A view helper.
     * @param item Definitions.
     */
    checkActionAvailability: function (helper: ViewHelper, item: ActionAvailabilityDefs): boolean {
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
     * Check access to an action.
     *
     * @param acl An ACL manager.
     * @param [obj] A scope or a model.
     * @param item Definitions.
     * @param [isPrecise=false] To return `null` if not enough data is set in a model.
     *   E.g. the `teams` field is not yet loaded.
     */
    checkActionAccess: function (
        acl: AclManager,
        obj: string | Model | null,
        item: ActionAccessDefs,
        isPrecise: boolean = false,
    ): boolean | null {

        let hasAccess: boolean | null = true;

        if (item.acl) {
            if (!item.aclScope) {
                if (obj) {
                    if (typeof obj === 'string' || obj instanceof String) {
                        hasAccess = acl.check(obj, item.acl);
                    } else {
                        hasAccess = acl.checkModel(obj, item.acl, isPrecise);
                    }
                } else {
                    hasAccess = acl.check((item as any).scope, item.acl);
                }
            } else {
                hasAccess = acl.check(item.aclScope, item.acl);
            }
        } else if (item.aclScope) {
            hasAccess = acl.checkScope(item.aclScope);
        }

        return hasAccess;
    },

    /**
     * Check access to an action.
     *
     * @param dataList List of definitions.
     * @param acl An ACL manager.
     * @param user A user.
     * @param [entity] A model.
     * @param [allowAllForAdmin=false] Allow all for an admin.
     */
    checkAccessDataList: function (
        dataList: AccessDefs[],
        acl: AclManager,
        user: User,
        entity: Model | null = null,
        allowAllForAdmin: boolean = false,
    ): boolean {

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
                } else if (!acl.checkScope(item.scope)) {
                    return false;
                }
            } else if (item.action && entity && !acl.check(entity, item.action)) {
                return false;
            }

            if (item.teamIdList && user && !(allowAllForAdmin && user.isAdmin())) {
                let inTeam = false;

                user.getLinkMultipleIdList('teams').forEach(teamId => {
                    if (item.teamIdList?.indexOf(teamId)) {
                        inTeam = true;
                    }
                });

                if (!inTeam) {
                    return false;
                }
            }

            if (item.portalIdList) {
                if (user && !(allowAllForAdmin && user.isAdmin())) {
                    let inPortal = false;

                    user.getLinkMultipleIdList('portals').forEach(portalId => {
                        if (item.portalIdList?.includes(portalId)) {
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
     * @internal
     */
    convert: function (string: string, p: string): string {
        if (string === null) {
            return string;
        }

        let result = string;

        switch (p) {
            case 'c-h':
            case 'C-h':
                result = Utils.camelCaseToHyphen(string);

                break;

            case 'h-c':
                result = Utils.hyphenToCamelCase(string);

                break;

            case 'h-C':
                result = Utils.hyphenToUpperCamelCase(string);

                break;
        }

        return result;
    },

    /**
     * Is object.
     *
     * @param obj What to check.
     */
    isObject: function (obj: unknown): boolean {
        if (obj === null) {
            return false;
        }

        return typeof obj === 'object';
    },

    /**
     * A shallow clone.
     *
     * @param obj What to clone.
     * @returns A clone.
     */
    clone: function<T> (obj: T): T {
        if (!Utils.isObject(obj)) {
            return obj;
        }

        return (_.isArray(obj) ? obj.slice() : _.extend({}, obj)) as T;
    },

    /**
     * A deep clone.
     *
     * @param data What to clone.
     * @return A clone.
     */
    cloneDeep: function<T> (data: T): T {
        data = Utils.clone(data);

        if (Utils.isObject(data) || _.isArray(data)) {
            for (const i in data) {
                data[i] = this.cloneDeep(data[i]);
            }
        }

        return data;
    },

    /**
     * Deep comparison.
     *
     * @param a1 An argument 1.
     * @param a2 An argument 2.
     */
    areEqual: function (a1: unknown, a2: unknown): boolean {
        return _.isEqual(a1, a2);
    },

    /**
     * Compose a class name.
     *
     * @param module A module.
     * @param name A name.
     * @param location A location.
     * @return
     */
    composeClassName: function (module: string, name: string, location: string = ''): string {
        if (module) {
            module = this.camelCaseToHyphen(module);
            name = this.camelCaseToHyphen(name).split('.').join('/');
            location = this.camelCaseToHyphen(location || '');

            return module + ':' + location + '/' + name;
        }

        name = this.camelCaseToHyphen(name).split('.').join('/');

        return location + '/' + name;
    },

    /**
     * Compose a view class name.
     *
     * @param name A name.
     */
    composeViewClassName: function (name: string): string {
        if (name && name[0] === name[0].toLowerCase()) {
            return name;
        }

        if (name.indexOf(':') !== -1) {
            const arr = name.split(':');
            let modPart = arr[0];
            let namePart = arr[1];

            modPart = this.camelCaseToHyphen(modPart);
            namePart = this.camelCaseToHyphen(namePart).split('.').join('/');

            return `${modPart}:views/${namePart}`;
        }

        name = this.camelCaseToHyphen(name).split('.').join('/');

        return `views/${name}`;
    },

    /**
     * Convert a string from camelCase to hyphen and replace dots with hyphens.
     * Useful for setting to DOM attributes.
     *
     * @param string A string.
     */
    toDom: function (string: string): string {
        return Utils.convert(string, 'c-h')
            .split('.')
            .join('-');
    },

    /**
     * Lower-case a first character.
     *
     * @param string A string.
     */
    lowerCaseFirst: function (string: string): string {
        if (string === null) {
            return string;
        }

        return string.charAt(0).toLowerCase() + string.slice(1);
    },

    /**
     * Upper-case a first character.
     *
     * @param string A string.
     */
    upperCaseFirst: function (string: string): string {
        if (string === null) {
            return string;
        }

        return string.charAt(0).toUpperCase() + string.slice(1);
    },

    /**
     * Hyphen to UpperCamelCase.
     *
     * @param string A string.
     */
    hyphenToUpperCamelCase: function (string: string): string {
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
     * @param string A string.
     */
    hyphenToCamelCase: function (string: string): string {
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
     * @param string A string.
     */
    camelCaseToHyphen: function (string: string) {
        if (string === null) {
            return string;
        }

        return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    },

    /**
     * Trim an ending slash.
     *
     * @param string A string.
     */
    trimSlash: function (string: string): string {
        if (string.slice(-1) === '/') {
            return string.slice(0, -1);
        }

        return string;
    },

    /**
     * Parse params in string URL options.
     *
     * @param string An URL part.
     */
    parseUrlOptionsParam: function (string: string): Record<string, string | boolean> {
        if (!string) {
            return {};
        }

        if (string.indexOf('&') === -1 && string.indexOf('=') === -1) {
            return {};
        }

        const options = {} as Record<string, string | boolean>;

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
     * @param event A key event.
     * @return {string}
     */
    getKeyFromKeyEvent: function (event: KeyboardEvent | JQuery.Event): string {
        let key: string = (event as any).code;

        key = (keyMap as Record<string, string>)[key] ?? key;

        if (event.shiftKey) {
            key = 'Shift+' + key;
        }

        if (event.altKey) {
            key = 'Alt+' + key;
        }

        if (IS_MAC ? event.metaKey : event.ctrlKey) {
            key = 'Control+' + key;
        }

        return key;
    },

    /**
     * Check whether the pressed key is in a text input.
     *
     * @param event A key event.
     * @since 9.2.0
     */
    isKeyEventInTextInput: function (event: KeyboardEvent): boolean {
        if (!(event.target instanceof HTMLElement)) {
            return false;
        }

        if (event.target.tagName === 'TEXTAREA') {
            return true;
        }

        if (event.target instanceof HTMLInputElement) {
            if (
                event.target.type === 'radio' ||
                event.target.type === 'checkbox'
            ) {
                return false;
            }

            return true;
        }

        if (event.target.classList.contains('note-editable')) {
            return true;
        }

        return false;
    },

    /**
     * Generate an ID. Not to be used by 3rd party code.
     *
     * @internal
     */
    generateId: function (): string {
        return (Math.floor(Math.random() * 10000001)).toString()
    },

    /**
     * Not to be used in custom code. Can be removed in future versions.
     *
     * @internal
     */
    obtainBaseUrl: function (): string {
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

Espo.Utils = Utils;

export default Utils;

function _processAfterActionDropdown($target: JQuery) {
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
    // @ts-ignore
    $dropdownToggle.dropdown('toggle');

    $dropdownToggle.trigger('focus');

    if (isDisabled) {
        $dropdownToggle.attr('disabled', 'disabled').addClass('disabled');
    }
}
