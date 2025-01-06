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

/** @module page-title */

import $ from 'jquery';

/**
 * A page-title util.
 */
class PageTitle {

    /**
     * @class
     * @param {module:models/settings} config A config.
     */
    constructor(config) {

        /**
         * @private
         * @type {boolean}
         */
        this.displayNotificationNumber = config.get('newNotificationCountInTitle') || false;

        /**
         * @private
         * @type {string}
         */
        this.title = $('head title').text() || '';

        /**
         * @private
         * @type {number}
         */
        this.notificationNumber = 0;
    }

    /**
     * Set a title.
     *
     * @param {string} title A title.
     */
    setTitle(title) {
        this.title = title;

        this.update();
    }

    /**
     * Set a notification number.
     *
     * @param {number} notificationNumber A number.
     */
    setNotificationNumber(notificationNumber) {
        this.notificationNumber = notificationNumber;

        if (this.displayNotificationNumber) {
            this.update();
        }
    }

    /**
     * Update a page title.
     */
    update() {
        let value = '';

        if (this.displayNotificationNumber && this.notificationNumber) {
            value = '(' + this.notificationNumber.toString() + ')';

            if (this.title) {
                value += ' ';
            }
        }

        value += this.title;

        $('head title').text(value);
    }
}

export default PageTitle;
