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

define('helpers/mass-action', [], function () {

    /**
     * A mass-action helper.
     *
     * @memberOf module:helpers/mass-action
     */
    class Class {
        /**
         * @param {module:view.Class} view A view.
         */
        constructor(view) {
            /**
             * @private
             * @type {module:view.Class}
             */
            this.view = view;

            /**
             * @private
             * @type {module:models/settings.Class}
             */
            this.config = view.getConfig();
        }

        /**
         * Check whether an action should be run in idle.
         *
         * @param {number} totalCount A total record count.
         * @returns {boolean}
         */
        checkIsIdle(totalCount) {
            if (this.view.getUser().isPortal()) {
                return false;
            }

            if (typeof totalCount === 'undefined') {
                totalCount = this.view.options.totalCount;
            }

            return totalCount === -1 || totalCount > this.config.get('massActionIdleCountThreshold');
        }

        /**
         * Process.
         *
         * @param {string} id An ID.
         * @param {string} action An action.
         * @returns {Promise<module:view.Class>}  Resolves with a dialog view.
         *   The view emits the 'close:success' event.
         */
        process(id, action) {
            Espo.Ui.notify(false);

            return new Promise(resolve => {
                this.view
                    .createView('dialog', 'views/modals/mass-action', {
                        id: id,
                        action: action,
                    })
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

    return Class;
});
