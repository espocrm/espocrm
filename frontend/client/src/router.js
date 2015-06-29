/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
Espo.define('Router', [], function () {

    var Router = Backbone.Router.extend({

        routes: {
            "logout": "logout",
            "clearCache": "clearCache",
            "search/:text": "search",
            ":controller/view/:id/:options": "view",
            ":controller/view/:id": "view",
            ":controller/edit/:id/:options": "edit",
            ":controller/edit/:id": "edit",
            ":controller/create": "create",
            ":controller/:action/:options": "action",
            ":controller/:action": "action",
            ":controller": "defaultAction",
            "*actions": "home",
        },

        _last: null,

        confirmLayout: false,

        confirmLeaveOutMessage: 'Are you sure?', 

        initialize: function () {
            this.history = [];
            this.on('route', function () {
                this.history.push(Backbone.history.fragment);
            });
        },

        execute: function (callback, args, name) {
            if (this.confirmLayout) {
                if (confirm(this.confirmLeaveOutMessage)) {
                    this.confirmLayout = false;
                    Backbone.Router.prototype.execute.call(this, callback, args, name);
                } else {
                    this.navigateBack({trigger: false});
                }
            } else {
                Backbone.Router.prototype.execute.call(this, callback, args, name);
            }
        },

        navigate: function (fragment, options) {
            this.history.push(fragment);
            return Backbone.Router.prototype.navigate.call(this, fragment, options);
        },

        navigateBack: function (options) {
            var url;
            if (this.history.length > 1) {
                url = this.history[this.history.length - 1];
            } else {
                url = this.history[0];
            }
            this.navigate(url, options);
        },

        _parseOptionsParams: function (string) {
            if (!string) {
                return {};
            }

            if (string.indexOf('&') === -1 && string.indexOf('=') === -1) {
                return string;
            }

            var options = {};
            if (typeof string !== 'undefined') {
                string.split('&').forEach(function (item, i) {
                    var p = item.split('=');
                    options[p[0]] = true;
                    if (p.length > 1) {
                        options[p[0]] = p[1];
                    }
                });
            }
            return options;
        },

        record: function (controller, action, id, options) {
            var options = this._parseOptionsParams(options);
            options.id = id;
            this.dispatch(controller, action, options);
        },

        view: function (controller, id, options) {
            this.record(controller, 'view', id, options);
        },

        edit: function (controller, id, options) {
            this.record(controller, 'edit', id, options);
        },

        create: function (controller, options) {
            this.record(controller, 'create', null, options);
        },

        action: function (controller, action, options) {
            this.dispatch(controller, action, this._parseOptionsParams(options));
        },

        defaultAction: function (controller) {
            this.dispatch(controller, null);
        },

        home: function () {
            this.dispatch('Home', null);
        },

        search: function (text) {
            this.dispatch('Home', 'search', text);
        },

        logout: function () {
            this.dispatch(null, 'logout');
            this.navigate('', {trigger: false});
        },

        clearCache: function () {
            this.dispatch(null, 'clearCache');
        },

        dispatch: function (controller, action, options) {
            var o =    {
                controller: controller,
                action: action,
                options: options
            }
            this._last = o;
            this.trigger('routed', o);
        },

        getLast: function () {
            return this._last;
        }
    });

    return Router;

});
