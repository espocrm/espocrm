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

define('models/preferences', ['model'], function (Dep) {

    /**
     * User preferences.
     *
     * @class
     * @name Class
     * @extends module:model.Class
     *
     * @memberOf module:models/preferences
     */
    return Dep.extend(/** @lends module:models/preferences.Class# */{

        /**
         * @inheritDoc
         */
        name: 'Preferences',

        /**
         * Get dashlet options.
         *
         * @param {string} id A dashlet ID.
         * @returns {Object|null}
         */
        getDashletOptions: function (id) {
            let value = this.get('dashletsOptions') || {};

            return value[id] || null;
        },

        /**
         * Whether a user is portal.
         *
         * @returns {boolean}
         */
        isPortal: function () {
            return this.get('isPortalUser');
        },
    });
});
