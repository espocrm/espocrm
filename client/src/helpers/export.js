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

import {inject} from 'di';
import Settings from 'models/settings';
import User from 'models/user';

/**
 * An export helper.
 */
class ExportHelper {

    /**
     * @private
     * @type {Settings}
     */
    @inject(Settings)
    config

    /**
     * @private
     * @type {User}
     */
    @inject(User)
    user

    /**
     * @param {module:view} view A view.
     */
    constructor(view) {
        /**
         * @private
         * @type {module:view}
         */
        this.view = view;
    }

    /**
     * Check whether an export should be run in idle.
     *
     * @param {number} totalCount A total record count.
     * @returns {boolean}
     */
    checkIsIdle(totalCount) {
        if (this.user.isPortal()) {
            return false;
        }

        if (typeof totalCount === 'undefined') {
            totalCount = this.view.options.totalCount;
        }

        return totalCount === -1 || totalCount > this.config.get('exportIdleCountThreshold');
    }

    /**
     * Process export.
     *
     * @param {string} id An ID.
     * @returns {Promise<module:view>} Resolves with a dialog view.
     *   The view emits the 'close:success' event.
     */
    process(id) {
        Espo.Ui.notify(false);

        return new Promise(resolve => {
            this.view.createView('dialog', 'views/export/modals/idle', {id: id})
                .then(view => {
                    view.render();

                    resolve(view);

                    this.view.listenToOnce(view, 'success', data => {
                        resolve(data);

                        this.view.listenToOnce(view, 'close', () => {
                            view.trigger('close:success', data);
                        });
                    });
                });
        });
    }
}

export default ExportHelper;
