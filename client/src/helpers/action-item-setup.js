/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
     * @param {{
     *     view: import('view').default<any>,
     *     type: string,
     *     waitFunc: (Promise) => void,
     *     addFunc: (
     *          item: import('views/record/detail').DropdownItem |
     *              import('views/record/detail').Button
 *         ) => void,
     *     showFunc: (string) => void,
     *     hideFunc: (string) => void,
     *     enableFunc?: (string) => void,
     *     disableFunc?: (string) => void,
     *     listenToViewModelSync?: boolean,
     *     syncEvent?: 'sync'|'change',
     * }} options
     */
    setup(options) {
        const view = options.view;
        const type = options.type;
        const waitFunc = options.waitFunc;
        const addFunc = options.addFunc;
        const showFunc = options.showFunc;
        const hideFunc = options.hideFunc;
        const enableFunc = options.enableFunc ?? (() => {});
        const disableFunc = options.disableFunc ?? (() => {});

        /** @type {Record[]} */
        const actionList = [];

        // noinspection JSUnresolvedReference
        const scope = view.scope || view.model.entityType;

        if (!scope) {
            throw new Error();
        }

        const path = type.split('.');

        /** @type {({name?: string} & Record | string)[]} */
        const actionDefsListOriginal = [
            ...this.metadata.get(['clientDefs', 'Global', ...path], []),
            ...this.metadata.get(['clientDefs', scope, ...path], []),
        ];

        /** @type {({name?: string} & Record<string, any>)[]} */
        let actionDefsList = actionDefsListOriginal.map(item => {
            if (typeof item === 'string') {
                return {name: item};
            }

            return Espo.Utils.cloneDeep(item);
        })

        actionDefsList.reverse();

        actionDefsList = actionDefsList.filter((it, i, self) => {
            return self.findIndex(sIt => sIt.name === it.name) === i;
        });

        actionDefsList.reverse();

        actionDefsList.forEach(item => {
            const name = item.name;

            if (!item.label && !item.labelTranslation && !item.iconClass) {
                item.text = this.language.translate(name, 'actions', scope);
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

            if (!item.initFunction && !item.checkVisibilityFunction && !item.checkAvailabilityFunction) {
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

                    if (item?.checkAvailabilityFunction) {
                        const isNotAvailable = !handler[item.checkAvailabilityFunction].call(handler);

                        if (isNotAvailable) {
                            disableFunc(item.name);
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

        const onChange = () => {
            actionList.forEach(item => {
                const handler = item.handlerInstance;

                if (!handler || !item.checkAvailabilityFunction) {
                    return;
                }
                const isAvailable = handler[item.checkAvailabilityFunction].call(handler);

                isAvailable ?
                    enableFunc(item.name) :
                    disableFunc(item.name);
            });
        }

        const onSync = () => {
            actionList.forEach(item => {
                const handler = item.handlerInstance;

                if (handler && item.checkVisibilityFunction) {
                    const isNotVisible = !handler[item.checkVisibilityFunction].call(handler);

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

        view.listenTo(view.model, 'change', () => onChange());
        view.listenTo(view.model, options.syncEvent ?? 'sync', () => onSync());
    }
}

export default ActionItemSetupHelper;
