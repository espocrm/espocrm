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

/** @module router */

import Backbone from 'backbone';

/**
 * On route.
 *
 * @event Backbone.Router#route
 * @param {string} name A route name.
 * @param {any[]} args Arguments.
 */

/**
 * After dispatch.
 *
 * @event module:router#routed
 * @param {{
 *   controller: string,
 *   action:string,
 *   options: Object.<string,*>,
 * }} data A route data.
 */

/**
 * Subscribe.
 *
 * @function on
 * @memberof module:router#
 * @param {string} event An event.
 * @param {function(*): void} callback A callback.
 */

/**
 * Subscribe once.
 *
 * @function once
 * @memberof module:router#
 * @param {string} event An event.
 * @param {function(): void} callback A callback.
 */

/**
 * Unsubscribe.
 *
 * @function off
 * @memberof module:router#
 * @param {string} event An event.
 */

/**
 * Trigger an event.
 *
 * @function trigger
 * @memberof module:router#
 * @param {string} event An event.
 */

// noinspection JSUnusedGlobalSymbols
/**
 * A router.
 *
 * @class
 * @mixes Espo.Events
 */
const Router = Backbone.Router.extend(/** @lends Router# */ {

    /**
     * @private
     */
    routeList: [
        {
            route: "clearCache",
            resolution: "clearCache"
        },
        {
            route: ":controller/view/:id/:options",
            resolution: "view"
        },
        {
            route: ":controller/view/:id",
            resolution: "view"
        },
        {
            route: ":controller/edit/:id/:options",
            resolution: "edit"
        },
        {
            route: ":controller/edit/:id",
            resolution: "edit"
        },
        {
            route: ":controller/create",
            resolution: "create"
        },
        {
            route: ":controller/related/:id/:link",
            resolution: "related"
        },
        {
            route: ":controller/:action/:options",
            resolution: "action",
            order: 100
        },
        {
            route: ":controller/:action",
            resolution: "action",
            order: 200
        },
        {
            route: ":controller",
            resolution: "defaultAction",
            order: 300
        },
        {
            route: "*actions",
            resolution: "home",
            order: 500
        },
    ],

    /**
     * @private
     */
    _bindRoutes: function() {},

    /**
     * @private
     */
    setupRoutes: function () {
        this.routeParams = {};

        if (this.options.routes) {
            const routeList = [];

            Object.keys(this.options.routes).forEach(route => {
                const item = this.options.routes[route];

                routeList.push({
                    route: route,
                    resolution: item.resolution || 'defaultRoute',
                    order: item.order || 0
                });

                this.routeParams[route] = item.params || {};
            });

            this.routeList = Espo.Utils.clone(this.routeList);

            routeList.forEach(item => {
                this.routeList.push(item);
            });

            this.routeList = this.routeList.sort((v1, v2) => {
                return (v1.order || 0) - (v2.order || 0);
            });
        }

        this.routeList.reverse().forEach(item => {
            this.route(item.route, item.resolution);
        });
    },

    /**
     * @private
     */
    _last: null,

    /**
     * Whether a confirm-leave-out was set.
     *
     * @public
     * @type {boolean}
     */
    confirmLeaveOut: false,

    /**
     * Whether back has been processed.
     *
     * @public
     * @type {boolean}
     */
    backProcessed: false,

    /**
     * @type {string}
     * @internal
     */
    confirmLeaveOutMessage: 'Are you sure?',

    /**
     * @type {string}
     * @internal
     */
    confirmLeaveOutConfirmText: 'Yes',

    /**
     * @type {string}
     * @internal
     */
    confirmLeaveOutCancelText: 'No',

    /**
     * @private
     */
    initialize: function (options) {
        this.options = options || {};
        this.setupRoutes();

        this._isReturn = false;
        this.history = [];

        let hashHistory = [window.location.hash];

        window.addEventListener('hashchange', () => {
            const hash = window.location.hash;

            if (
                hashHistory.length > 1 &&
                hashHistory[hashHistory.length - 2] === hash
            ) {
                hashHistory = hashHistory.slice(0, -1);

                this.backProcessed = true;
                setTimeout(() => this.backProcessed = false, 50);

                return;
            }

            hashHistory.push(hash);
        });

        this.on('route', () => {
            this.history.push(Backbone.history.fragment);
        });

        window.addEventListener('beforeunload', event => {
            event = event || window.event;

            if (
                this.confirmLeaveOut ||
                this._leaveOutMap.size ||
                this._windowLeaveOutMap.size
            ) {
                event.preventDefault();

                event.returnValue = this.confirmLeaveOutMessage;

                return this.confirmLeaveOutMessage;
            }
        });

        /**
         * @private
         * @type {Map<Object, true>}
         */
        this._leaveOutMap = new Map();

        /**
         * @private
         * @type {Map<Object, true>}
         */
        this._windowLeaveOutMap = new Map();
    },

    /**
     * Get a current URL.
     *
     * @returns {string}
     */
    getCurrentUrl: function () {
        return '#' + Backbone.history.fragment;
    },

    /**
     * Whether there's any confirm-leave-out.
     *
     * @since 9.1.0
     * @return {boolean}
     */
    hasConfirmLeaveOut() {
        return this.confirmLeaveOut || this._leaveOutMap.size || this._windowLeaveOutMap.size;
    },

    /**
     * Refer an object (usually a view). Page won't be possible to close or change if there's at least one object.
     *
     * @param {Object} object
     * @since 9.1.0
     * @internal
     */
    addLeaveOutObject(object) {
        this._leaveOutMap.set(object, true);
    },

    /**
     * Un-refer an object.
     *
     * @param {Object} object
     * @since 9.1.0
     * @internal
     */
    removeLeaveOutObject(object) {
        this._leaveOutMap.delete(object);
    },

    /**
     * Refer an object (usually a view). Window won't be possible to close if there's at least one object.
     *
     * @param {Object} object
     * @since 9.1.0
     * @internal
     */
    addWindowLeaveOutObject(object) {
        this._windowLeaveOutMap.set(object, true);
    },

    /**
     * Un-refer an object.
     *
     * @param {Object} object
     * @since 9.1.0
     * @internal
     */
    removeWindowLeaveOutObject(object) {
        this._windowLeaveOutMap.delete(object);
    },

    /**
     * @callback module:router~checkConfirmLeaveOutCallback
     */

    /**
     * Process confirm-leave-out.
     *
     * @param {module:router~checkConfirmLeaveOutCallback} callback Proceed if confirmed.
     * @param {Object|null} [context] A context.
     * @param {boolean} [navigateBack] To navigate back if not confirmed.
     */
    checkConfirmLeaveOut: function (callback, context, navigateBack) {
        if (this.confirmLeaveOutDisplayed) {
            this.navigateBack({trigger: false});

            this.confirmLeaveOutCanceled = true;

            return;
        }

        context = context || this;

        if (this.confirmLeaveOut || this._leaveOutMap.size) {
            this.confirmLeaveOutDisplayed = true;
            this.confirmLeaveOutCanceled = false;

            Espo.Ui.confirm(
                this.confirmLeaveOutMessage,
                {
                    confirmText: this.confirmLeaveOutConfirmText,
                    cancelText: this.confirmLeaveOutCancelText,
                    backdrop: true,
                    cancelCallback: () => {
                        this.confirmLeaveOutDisplayed = false;

                        if (navigateBack) {
                            this.navigateBack({trigger: false});
                        }
                    },
                },
                () => {
                    this.confirmLeaveOutDisplayed = false;
                    this.confirmLeaveOut = false;

                    this._leaveOutMap.clear();

                    if (!this.confirmLeaveOutCanceled) {
                        callback.call(context);
                    }
                }
            );

            return;
        }

        callback.call(context);
    },

    /**
     * @private
     */
    route: function (route, name/*, callback*/) {
        const routeOriginal = route;

        if (!_.isRegExp(route)) {
            route = this._routeToRegExp(route);
        }

        let callback;

        // @todo Revise.
        /*if (_.isFunction(name)) {
            callback = name;
            name = '';
        }*/

        /*if (!callback) {
            callback = this['_' + name];
        }*/
        callback = this['_' + name];

        const router = this;

        Backbone.history.route(route, function (fragment) {
            const args = router._extractParameters(route, fragment);

            const options = {};

            if (name === 'defaultRoute') {
                const keyList = [];

                routeOriginal.split('/').forEach(key => {
                    if (key && key.indexOf(':') === 0) {
                        keyList.push(key.substr(1));
                    }
                });

                keyList.forEach((key, i) => {
                    options[key] = args[i];
                });
            }

            // @todo Revise.
            router.execute(callback, args, name, routeOriginal, options);
            //if (router.execute(callback, args, name, routeOriginal, options) !== false) {
                router.trigger.apply(router, ['route:' + name].concat(args));
                router.trigger('route', name, args);
                Backbone.history.trigger('route', router, name, args);
            //}
        });

        return this;
    },

    /**
     * @private
     */
    execute: function (callback, args, name, routeOriginal, options) {
        this.checkConfirmLeaveOut(() => {
            if (name === 'defaultRoute') {
                this._defaultRoute(this.routeParams[routeOriginal], options);

                return;
            }

            Backbone.Router.prototype.execute.call(this, callback, args, name);
        }, null, true);
    },

    /**
     * Navigate.
     *
     * @param {string} fragment An URL fragment.
     * @param {{
     *     trigger?: boolean,
     *     replace?: boolean,
     *     isReturn?: boolean,
     * }} [options] Options.
     */
    navigate: function (fragment, options = {}) {
        if (!options.trigger) {
            this.history.push(fragment);
        }

        if (options.isReturn) {
            this._isReturn = true;
        }

        return Backbone.Router.prototype.navigate.call(this, fragment, options);
    },

    /**
     * Navigate back.
     *
     * @param {Object} [options] Options: trigger, replace.
     */
    navigateBack: function (options) {
        let url;

        url = this.history.length > 1 ?
            this.history[this.history.length - 2] :
            this.history[0];

        this.navigate(url, options);
    },

    /**
     * @private
     */
    _parseOptionsParams: function (string) {
        if (!string) {
            return {};
        }

        if (string.indexOf('&') === -1 && string.indexOf('=') === -1) {
            return string;
        }

        const options = {};

        if (typeof string !== 'undefined') {
            string.split('&').forEach(item => {
                const p = item.split('=');

                options[p[0]] = true;

                if (p.length > 1) {
                    options[p[0]] = decodeURIComponent(p[1]);
                }
            });
        }

        return options;
    },

    /**
     * @private
     */
    _defaultRoute: function (params, options) {
        const controller = params.controller || options.controller;
        const action = params.action || options.action;

        this.dispatch(controller, action, options);
    },

    /**
     * @private
     */
    _record: function (controller, action, id, options) {
        options = this._parseOptionsParams(options);

        options.id = id;

        this.dispatch(controller, action, options);
    },

    /**
     * @private
     */
    _view: function (controller, id, options) {
        this._record(controller, 'view', id, options);
    },

    /**
     * @private
     */
    _edit: function (controller, id, options) {
        this._record(controller, 'edit', id, options);
    },

    /**
     * @private
     */
    _related: function (controller, id, link, options) {
        options = this._parseOptionsParams(options);

        options.id = id;
        options.link = link;

        this.dispatch(controller, 'related', options);
    },

    /**
     * @private
     */
    _create: function (controller, options) {
        this._record(controller, 'create', null, options);
    },

    /**
     * @private
     */
    _action: function (controller, action, options) {
        this.dispatch(controller, action, this._parseOptionsParams(options));
    },

    /**
     * @private
     */
    _defaultAction: function (controller) {
        this.dispatch(controller, null);
    },

    /**
     * @private
     */
    _home: function () {
        this.dispatch('Home', null);
    },

    /**
     * @private
     */
    _clearCache: function () {
        this.dispatch(null, 'clearCache');
    },

    /**
     * Process `logout` route.
     */
    logout: function () {
        this.dispatch(null, 'logout');

        this.navigate('', {trigger: false});
    },

    /**
     * Dispatch a controller action.
     *
     * @param {string|null} [controller] A controller.
     * @param {string|null} [action] An action.
     * @param {Object} [options] Options.
     * @fires module:router#routed
     */
    dispatch: function (controller, action, options) {
        if (this._isReturn) {
            options = {...options};
            options.isReturn = true;

            this._isReturn = false;
        }

        const o = {
            controller: controller,
            action: action,
            options: options,
        };

        if (controller && /[a-z]/.test(controller[0])) {
            o.controllerClassName = controller;
            delete o.controller;
        }

        this._last = o;

        this.trigger('routed', o);
    },

    /**
     * Get the last route data.
     *
     * @returns {Object}
     */
    getLast: function () {
        return this._last;
    },
});

export default Router;

function isIOS9UIWebView() {
    const userAgent = window.navigator.userAgent;

    return /(iPhone|iPad|iPod).* OS 9_\d/.test(userAgent) && !/Version\/9\./.test(userAgent);
}

// Fixes issue that navigate with {trigger: false} fired
// route change if there's a whitespace character.
Backbone.history.getHash = function (window) {
    const match = (window || this).location.href.match(/#(.*)$/);

    return match ? this.decodeFragment(match[1]) : '';
};

// Override `backbone.history.loadUrl()` and `backbone.history.navigate()`
// to fix the navigation issue (`location.hash` not changed immediately) on iOS9.
if (isIOS9UIWebView()) {
    Backbone.history.loadUrl = function (fragment, oldHash) {
        fragment = this.fragment = this.getFragment(fragment);

        return _.any(this.handlers, function (handler) {
            if (handler.route.test(fragment)) {
                function runCallback() {
                    handler.callback(fragment);
                }

                function wait() {
                    if (oldHash === location.hash) {
                        window.setTimeout(wait, 50);
                    } else {
                        runCallback();
                    }
                }

                wait();

                return true;
            }
        });
    };

    Backbone.history.navigate = function (fragment, options) {
        const pathStripper = /#.*$/;

        if (!Backbone.History.started) {
            return false;
        }

        if (!options || options === true) {
            options = {
                trigger: !!options
            };
        }

        let url = this.root + '#' + (fragment = this.getFragment(fragment || ''));

        fragment = fragment.replace(pathStripper, '');

        if (this.fragment === fragment) {
            return;
        }

        this.fragment = fragment;

        if (fragment === '' && url !== '/') {
            url = url.slice(0, -1);
        }

        const oldHash = location.hash;

        if (this._hasPushState) {
            this.history[options.replace ? 'replaceState' : 'pushState']({}, document.title, url);
        } else if (this._wantsHashChange) {
            this._updateHash(this.location, fragment, options.replace);

            if (
                this.iframe &&
                (fragment !== this.getFragment(this.getHash(this.iframe)))
            ) {
                if (!options.replace) {
                    this.iframe.document.open().close();
                }

                this._updateHash(this.iframe.location, fragment, options.replace);
            }
        } else {
            return this.location.assign(url);
        }

        if (options.trigger) {
            return this.loadUrl(fragment, oldHash);
        }
    };
}
