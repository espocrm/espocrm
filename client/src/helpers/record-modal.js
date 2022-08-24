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
 * @module helpers/record-modal
 */
define(() => {

    /**
     * @memberOf module:helpers/record-modal
     */
    class Class {
        /**
         * @param {module:metadata.Class} metadata
         * @param {module:acl-manager.Class} acl
         */
        constructor(metadata, acl) {
            this.metadata = metadata;
            this.acl = acl;
        }

        /**
         * @param {module:view.Class} view
         * @param {{
         *   id: string,
         *   scope: string,
         *   model?: module:model.Class,
         *   editDisabled?: boolean,
         *   rootUrl?: string,
         * }} params
         * @return {Promise}
         */
        showDetail(view, params) {
            let id = params.id;
            let scope = params.scope;
            let model = params.model;

            if (!id || !scope) {
                console.error("Bad data.");

                return Promise.reject();
            }

            if (!this.acl.checkScope(scope, 'read')) {
                return Promise.reject();
            }

            let viewName = this.metadata.get(['clientDefs', scope, 'modalViews', 'detail']) ||
                'views/modals/detail';

            Espo.Ui.notify(view.translate('loading', 'messages'));

            let options = {
                scope: scope,
                model: model,
                id: id,
                quickEditDisabled: params.editDisabled,
                rootUrl: params.rootUrl,
            };

            return view.createView('modal', viewName, options, modalView => {
                modalView.render()
                    .then(() => Espo.Ui.notify(false));

                view.listenToOnce(modalView, 'remove', () => {
                    view.clearView('modal');
                });
            });
        }
    }

    return Class;
});
