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

define('action-handler', [], function () {

    /**
     * An action handler. To be extended by specific action handlers.
     *
     * @class
     * @name Class
     * @param {module:view.Class} view A view.
     * @memberOf module:action-handler
     */
    let ActionHandler = function (view) {
        /**
         * @type {module:view.Class}
         * @protected
         */
        this.view = view;
    };

    _.extend(ActionHandler.prototype, /** @lends {module:action-handler.Class#} */ {

        /**
         * @deprecated Use `this.view`.
         */
        getConfig: function () {
            return this.view.getConfig();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getMetadata: function () {
            return this.view.getMetadata();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getAcl: function () {
            return this.view.getAcl();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getUser: function () {
            return this.view.getUser();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getRouter: function () {
            return this.view.getRouter();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getHelper: function () {
            return this.view.getHelper();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getLanguage: function () {
            return this.view.getLanguage();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getModelFactory: function () {
            return this.view.getModelFactory();
        },

        /**
         * @deprecated Use `this.view`.
         */
        getCollectionFactory: function () {
            return this.view.getCollectionFactory();
        },

        /**
         * @deprecated Use `Espo.Ajax`.
         */
        ajaxPostRequest: function () {
            return this.view.ajaxPostRequest.apply(this.view, arguments);
        },

        /**
         * @deprecated Use `Espo.Ajax`.
         */
        ajaxPutRequest: function () {
            return this.view.ajaxPutRequest.apply(this.view, arguments);
        },

        /**
         * @deprecated Use `Espo.Ajax`.
         */
        ajaxGetRequest: function () {
            return this.view.ajaxGetRequest.apply(this.view, arguments);
        },

        /**
         * @deprecated Use `this.view`.
         */
        confirm: function () {
            return this.view.confirm.apply(this.view, arguments);
        },
    });

    ActionHandler.extend = Backbone.Router.extend;

    return ActionHandler;
});
