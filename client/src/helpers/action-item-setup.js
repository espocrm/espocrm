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
import ViewHelper from 'view-helper';
import AclManager from 'acl-manager';
import Language from 'language';

/** @module helpers/action-item-setup */

class ActionItemSetupHelper {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {ViewHelper}
     */
    @inject(ViewHelper)
    viewHelper

    /**
     * @private
     * @type {AclManager}
     */
    @inject(AclManager)
    acl

    /**
     * @private
     * @type {Language}
     */
    @inject(Language)
    language

    /**
     * @param {module:view} view
     * @param {string} type
     * @param {function(Promise): void} waitFunc
     * @param {function(Object): void} addFunc
     * @param {function(string): void} showFunc
     * @param {function(string): void} hideFunc
     * @param {{listenToViewModelSync?: boolean}} [options]
     */
    setup(view, type, waitFunc, addFunc, showFunc, hideFunc, options) {
        options = options || {};
        const actionList = [];

        // noinspection JSUnresolvedReference
        const scope = view.scope || view.model.entityType;

        if (!scope) {
            throw new Error();
        }

        const actionDefsList = [
            ...this.metadata.get(['clientDefs', 'Global', type + 'ActionList']) || [],
            ...this.metadata.get(['clientDefs', scope, type + 'ActionList']) || [],
        ];

        actionDefsList.forEach(item => {
            if (typeof item === 'string') {
                item = {name: item};
            }

            item = Espo.Utils.cloneDeep(item);

            const name = item.name;

            if (!item.label) {
                item.html = this.language.translate(name, 'actions', scope);
            }

            item.data = item.data || {};

            const handlerName = item.handler || item.data.handler;

            if (handlerName && !item.data.handler) {
                item.data.handler = handlerName;
            }

            addFunc(item);

            if (!Espo.Utils.checkActionAvailability(this.viewHelper, item)) {
                return;
            }

            if (!Espo.Utils.checkActionAccess(this.acl, view.model, item, true)) {
                item.hidden = true;
            }

            actionList.push(item);

            if (!handlerName) {
                return;
            }

            if (!item.initFunction && !item.checkVisibilityFunction) {
                return;
            }

            waitFunc(new Promise(resolve => {
                Espo.loader.require(handlerName, Handler => {
                    const handler = new Handler(view);

                    if (item.initFunction) {
                        handler[item.initFunction].call(handler);
                    }

                    if (item.checkVisibilityFunction) {
                        const isNotVisible = !handler[item.checkVisibilityFunction].call(handler);

                        if (isNotVisible) {
                            hideFunc(item.name);
                        }
                    }

                    item.handlerInstance = handler;

                    resolve();
                });
            }));
        });

        if (!actionList.length) {
            return;
        }

        const onSync = () => {
            actionList.forEach(item => {
                if (item.handlerInstance && item.checkVisibilityFunction) {
                    const isNotVisible = !item.handlerInstance[item.checkVisibilityFunction]
                        .call(item.handlerInstance);

                    if (isNotVisible) {
                        hideFunc(item.name);

                        return;
                    }
                }

                if (Espo.Utils.checkActionAccess(this.acl, view.model, item, true)) {
                    showFunc(item.name);

                    return;
                }

                hideFunc(item.name);
            });
        };

        if (options.listenToViewModelSync) {
            view.listenTo(view, 'model-sync', () => onSync());

            return;
        }

        view.listenTo(view.model, 'sync', () => onSync());
    }
}

export default ActionItemSetupHelper;
