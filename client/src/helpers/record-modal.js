/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/**
 * A record-modal helper.
 */
class RecordModalHelper {
    /**
     * @param {module:metadata} metadata
     * @param {module:acl-manager} acl
     */
    constructor(metadata, acl) {
        this.metadata = metadata;
        this.acl = acl;
    }

    /**
     * @param {module:view} view
     * @param {{
     *   id: string,
     *   scope: string,
     *   model?: module:model,
     *   editDisabled?: boolean,
     *   rootUrl?: string,
     * }} params
     * @return {Promise}
     */
    showDetail(view, params) {
        const id = params.id;
        const scope = params.scope;
        const model = params.model;

        if (!id || !scope) {
            console.error("Bad data.");

            return Promise.reject();
        }

        if (model && !this.acl.checkScope(model.entityType, 'read')) {
            return Promise.reject();
        }

        const viewName = this.metadata.get(['clientDefs', scope, 'modalViews', 'detail']) ||
            'views/modals/detail';

        Espo.Ui.notify(' ... ');

        const options = {
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

export default RecordModalHelper;
