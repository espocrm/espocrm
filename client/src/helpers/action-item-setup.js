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
 * @module helpers/action-item-setup
 */
define(() => {

    /**
     * @memberOf module:helpers/action-item-setup
     */
    class Class {
        /**
         * @param {module:metadata.Class} metadata
         * @param {module:view-helper.Class} viewHelper
         * @param {module:acl-manager.Class} acl
         * @param {module:language.Class} language
         */
        constructor(metadata, viewHelper, acl, language) {
            this.metadata = metadata;
            this.viewHelper = viewHelper;
            this.acl = acl;
            this.language = language;
        }

        /**
         * @param {module:view.Class} view
         * @param {string} type
         * @param {function(Promise): void} waitFunc
         * @param {function(Object): void} addFunc
         * @param {function(string): void} showFunc
         * @param {function(string): void} hideFunc
         * @param {{listenToViewModelSync?: boolean}} options
         */
        setup(view, type, waitFunc, addFunc, showFunc, hideFunc, options) {
            options = options || {};
            let actionList = [];

            let scope = view.scope || view.model.entityType;

            if (!scope) {
                throw new Error();
            }

            let actionDefsList = (this.metadata
                .get(['clientDefs', scope, type + 'ActionList']) || [])
                .concat(this.metadata.get(['clientDefs', 'Global', type + 'ActionList']) || []);

            actionDefsList.forEach(item => {
                if (typeof item === 'string') {
                    item = {name: item};
                }

                item = Espo.Utils.cloneDeep(item);

                let name = item.name;

                if (!item.label) {
                    item.html = this.language.translate(name, 'actions', scope);
                }

                addFunc(item);

                if (!Espo.Utils.checkActionAvailability(this.viewHelper, item)) {
                    return;
                }

                if (!Espo.Utils.checkActionAccess(this.acl, view.model, item, true)) {
                    item.hidden = true;
                }

                actionList.push(item);

                let data = item.data || {};
                let handlerName = item.handler || data.handler;

                if (!handlerName) {
                    return;
                }

                if (!item.initFunction && !item.checkVisibilityFunction) {
                    return;
                }

                waitFunc(new Promise(resolve => {
                    require(handlerName, Handler => {
                        let handler = new Handler(view);

                        if (item.initFunction) {
                            handler[item.initFunction].call(handler);
                        }

                        if (item.checkVisibilityFunction) {
                            let isNotVisible = !handler[item.checkVisibilityFunction].call(handler);

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

            let onSync = () => {
                actionList.forEach(item => {
                    if (item.handlerInstance && item.checkVisibilityFunction) {
                        let isNotVisible = !item.handlerInstance[item.checkVisibilityFunction]
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

    return Class;
});
