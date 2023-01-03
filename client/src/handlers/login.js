/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('handlers/login', [], function () {

    /**
     * Custom login handling. To be extended.
     *
     * @class
     * @name Class
     * @memberOf module:handlers/login
     * @abstract
     */
    class Class {

        /**
         * @param {module:views/login.Class} loginView A login view.
         * @param {Object.<string, *>} data Additional metadata.
         */
        constructor(loginView, data) {
            /**
             * A login view.
             * @protected
             * @type {module:views/login.Class}
             */
            this.loginView = loginView;

            /**
             * Additional metadata.
             * @protected
             * @type {Object.<string, *>}
             */
            this.data = data;
        }

        /**
         * Process. Called on 'Sign in' button click.
         *
         * @public
         * @abstract
         * @return Promise<{Object.<string, string>}> Resolved with headers to be sent to the `App/user` endpoint.
         */
        process() {
            return Promise.resolve({});
        }
    }

    return Class;
});
