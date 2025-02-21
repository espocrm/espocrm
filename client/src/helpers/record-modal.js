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
import Metadata from 'metadata';
import AclManager from 'acl-manager';
import Router from 'router';

/**
 * A record-modal helper. Use to render the quick view and quick edit modals.
 */
class RecordModalHelper {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {AclManager}
     */
    @inject(AclManager)
    acl

    /**
     * @private
     * @type {Router}
     */
    @inject(Router)
    router

    /**
     * Show the 'detail' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   id: string,
     *   entityType: string,
     *   model?: import('model').default,
     *   editDisabled?: boolean,
     *   removeDisabled?: boolean,
     *   fullFormDisabled?: string,
     *   rootUrl?: string,
     *   fullFormUrl?: string,
     *   layoutName?: string,
     *   afterSave?: function(import('model').default, {bypassClose: boolean}),
     *   afterDestroy?: function(import('model').default),
     *   beforeRender?: function(import('views/modals/detail').default),
     *   onClose?: function(),
     * }} params
     * @return {Promise<import('views/modals/detail').default>}
     */
    async showDetail(view, params) {
        const id = params.id;
        // noinspection JSUnresolvedReference
        const entityType = params.entityType || params.scope;
        const model = params.model;

        if (!id || !entityType) {
            console.error("Bad data.");

            return Promise.reject();
        }

        if (model && !this.acl.checkScope(model.entityType, 'read')) {
            return Promise.reject();
        }

        const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.detail`) ||
            'views/modals/detail';

        Espo.Ui.notifyWait();

        /** @type {module:views/modals/detail~options} */
        const options = {
            entityType: entityType,
            model: model,
            id: id,
            quickEditDisabled: params.editDisabled,
            rootUrl: params.rootUrl,
            removeDisabled: params.removeDisabled,
            layoutName: params.layoutName,
            fullFormDisabled: params.fullFormDisabled,
            fullFormUrl: params.fullFormUrl,
        };

        Espo.Ui.notifyWait();

        const modalView = /** @type {import('views/modals/detail').default} */
            await view.createView('modal', viewName, options);

        // @todo Revise.
        view.listenToOnce(modalView, 'remove', () => view.clearView('modal'));

        if (params.afterSave) {
            modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
                params.afterSave(model, {bypassClose: !!o.bypassClose});
            });
        }

        if (params.afterDestroy) {
            modalView.listenToOnce(modalView, 'after:destroy', model => params.afterDestroy(model));
        }

        if (params.beforeRender) {
            params.beforeRender(modalView);
        }

        if (params.onClose) {
            view.listenToOnce(modalView, 'close', () => params.onClose());
        }

        await modalView.render();

        Espo.Ui.notify();

        return modalView;
    }

    /**
     * Show the 'edit' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   entityType: string,
     *   id?: string,
     *   model?: import('model').default,
     *   rootUrl?: string,
     *   fullFormDisabled?: boolean,
     *   fullFormUrl?: string,
     *   returnUrl?: string,
     *   layoutName?: string,
     *   afterSave?: function(import('model').default, {bypassClose: boolean}),
     *   beforeRender?: function(import('views/modals/edit').default),
     *   onClose?: function(),
     *   returnDispatchParams?: {
     *       controller: string,
     *       action: string|null,
     *       options: {isReturn?: boolean} & Record,
     *   },
     * }} params
     * @return {Promise<import('views/modals/edit').default>}
     * @since 9.1.0
     */
    async showEdit(view, params) {
        const id = params.id;
        const entityType = params.entityType;
        const model = params.model;

        const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.edit`) ||
            'views/modals/edit';

        /** @type {module:views/modals/edit~options} */
        const options = {
            entityType: entityType,
            id: id,
            model: model,
            fullFormDisabled: params.fullFormDisabled,
            returnUrl: params.returnUrl || this.router.getCurrentUrl(),
            returnDispatchParams: params.returnDispatchParams,
            layoutName: params.layoutName,
            fullFormUrl: params.fullFormUrl,
        };

        if (params.rootUrl) {
            options.rootUrl = params.rootUrl;
        }

        Espo.Ui.notifyWait();

        const modalView = /** @type {import('views/modals/edit').default} */
            await view.createView('modal', viewName, options)

        // @todo Revise.
        modalView.listenToOnce(modalView, 'remove', () => view.clearView('modal'));

        if (params.afterSave) {
            modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
                params.afterSave(model, {bypassClose: !!o.bypassClose});
            });
        }

        if (params.beforeRender) {
            params.beforeRender(modalView);
        }

        if (params.onClose) {
            view.listenToOnce(modalView, 'close', () => params.onClose());
        }

        await modalView.render();

        Espo.Ui.notify();

        return modalView;
    }

    /**
     * Show the 'create' modal.
     *
     * @param {import('view').default} view
     * @param {{
     *   entityType: string,
     *   rootUrl?: string,
     *   fullFormDisabled?: boolean,
     *   fullFormUrl?: string,
     *   returnUrl?: string,
     *   relate?: model:model~setRelateItem | model:model~setRelateItem[],
     *   attributes?: Record.<string, *>,
     *   afterSave?: function(import('model').default, {bypassClose: boolean}),
     *   beforeRender?: function(import('views/modals/edit').default),
     *   onClose?: function(),
     *   focusForCreate?: boolean,
     *   layoutName?: string,
     *   returnDispatchParams?: {
     *       controller: string,
     *       action: string|null,
     *       options: {isReturn?: boolean} & Record,
     *   },
     * }} params
     * @return {Promise<import('views/modals/edit').default>}
     * @since 9.1.0
     */
    async showCreate(view, params) {
        const entityType = params.entityType;

        const viewName = this.metadata.get(`clientDefs.${entityType}.modalViews.edit`) ||
            'views/modals/edit';

        /** @type {module:views/modals/edit~options} */
        const options = {
            entityType: entityType,
            fullFormDisabled: params.fullFormDisabled,
            returnUrl: params.returnUrl || this.router.getCurrentUrl(),
            returnDispatchParams: params.returnDispatchParams,
            relate: params.relate,
            attributes: params.attributes,
            focusForCreate: params.focusForCreate,
            layoutName: params.layoutName,
            fullFormUrl: params.fullFormUrl,
        };

        if (params.rootUrl) {
            options.rootUrl = params.rootUrl;
        }

        Espo.Ui.notifyWait();

        const modalView = /** @type {import('views/modals/edit').default} */
            await view.createView('modal', viewName, options);

        // @todo Revise.
        modalView.listenToOnce(modalView, 'remove', () => view.clearView('modal'));

        if (params.afterSave) {
            modalView.listenTo(modalView, 'after:save', (model, /** Record */o) => {
                params.afterSave(model, {bypassClose: !!o.bypassClose});
            });
        }

        if (params.beforeRender) {
            params.beforeRender(modalView);
        }

        if (params.onClose) {
            view.listenToOnce(modalView, 'close', () => params.onClose());
        }

        await modalView.render();

        Espo.Ui.notify();

        return modalView;
    }
}

export default RecordModalHelper;
