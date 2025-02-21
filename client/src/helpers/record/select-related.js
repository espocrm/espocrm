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

/**
 * @internal
 */
class SelectRelatedHelper {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @param {import('view').default} view
     */
    constructor(view) {
        /** @private */
        this.view = view;
    }

    /**
     * @param {import('model').default} model
     * @param {string} link
     * @param {{
     *     foreignEntityType?: string,
     *     massSelect?: boolean,
     *     primaryFilterName?: string,
     *     boolFilterList?: string[]|string,
     *     viewKey?: string,
     *     hasCreate?: boolean,
     *     onCreate?: function(): void,
     * }} options
     */
    process(model, link, options = {}) {
        if (!options.foreignEntityType && !model.defs['links'][link]) {
            throw new Error(`Link ${link} does not exist.`);
        }

        const scope = options.foreignEntityType || model.defs['links'][link].entity;

        /** @var {Object.<string, *>} */
        const panelDefs = this.metadata.get(['clientDefs', model.entityType, 'relationshipPanels', link]) || {};

        const massRelateEnabled = options.massSelect || panelDefs.massSelect;

        let advanced = {};

        const foreignLink = model.getLinkParam(link, 'foreign');

        if (foreignLink && scope) {
            // Select only records not related with any.
            const foreignLinkType = this.metadata.get(['entityDefs', scope, 'links', foreignLink, 'type']);
            const foreignLinkFieldType = this.metadata.get(['entityDefs', scope, 'fields', foreignLink, 'type']);

            if (
                ['belongsTo', 'belongsToParent'].includes(foreignLinkType) &&
                foreignLinkFieldType &&
                !advanced[foreignLink] &&
                ['link', 'linkParent'].includes(foreignLinkFieldType)
            ) {
                advanced[foreignLink] = {
                    type: 'isNull',
                    attribute: foreignLink + 'Id',
                    data: {
                        type: 'isEmpty',
                    },
                };
            }
        }

        let primaryFilterName = options.primaryFilterName || null;

        if (typeof primaryFilterName === 'function') {
            primaryFilterName = primaryFilterName.call(this);
        }

        let dataBoolFilterList = options.boolFilterList;

        if (typeof options.boolFilterList === 'string') {
            dataBoolFilterList = options.boolFilterList.split(',');
        }

        let boolFilterList = dataBoolFilterList || panelDefs.selectBoolFilterList;

        if (typeof boolFilterList === 'function') {
            boolFilterList = boolFilterList.call(this);
        }

        boolFilterList = Espo.Utils.clone(boolFilterList);

        primaryFilterName = primaryFilterName || panelDefs.selectPrimaryFilterName || null;

        const viewKey = options.viewKey || 'select';

        const viewName = panelDefs.selectModalView ||
            this.metadata.get(['clientDefs', scope, 'modalViews', viewKey]) ||
            'views/modals/select-records';

        Espo.Ui.notifyWait();

        const handler = panelDefs.selectHandler || null;

        new Promise(resolve => {
            if (!handler) {
                resolve({});

                return;
            }

            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.view.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    handler.getFilters(model)
                        .then(filters => resolve(filters));
                });
        }).then(filters => {
            advanced = {...advanced, ...(filters.advanced || {})};

            if (boolFilterList || filters.bool) {
                boolFilterList = [
                    ...(boolFilterList || []),
                    ...(filters.bool || []),
                ];
            }

            if (filters.primary && !primaryFilterName) {
                primaryFilterName = filters.primary;
            }

            const orderBy = filters.orderBy || panelDefs.selectOrderBy;
            const orderDirection = filters.orderBy ? filters.order : panelDefs.selectOrderDirection;

            const createButton = options.hasCreate === true && options.onCreate !== undefined;

            /** @type {import('views/modals/select-records').default} */
            let modalView;

            this.view.createView('dialogSelectRelated', viewName, {
                scope: scope,
                multiple: true,
                filters: advanced,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
                mandatorySelectAttributeList: panelDefs.selectMandatoryAttributeList,
                layoutName: panelDefs.selectLayout,
                orderBy: orderBy,
                orderDirection: orderDirection,
                createButton: createButton,
                onCreate: () => {
                    modalView.close();

                    if (options.onCreate) {
                        options.onCreate();
                    }
                },
            }, view => {
                modalView = view;

                view.render();

                Espo.Ui.notify(false);

                this.view.listenToOnce(view, 'select', (selectObj) => {
                    const data = {};

                    if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                        const ids = [];

                        selectObj.forEach(model => ids.push(model.id));

                        data.ids = ids;
                    }  else if (selectObj.massRelate) {
                        data.massRelate = true;
                        data.where = selectObj.where;
                        data.searchParams = selectObj.searchParams;
                    } else {
                        data.id = selectObj.id;
                    }

                    const url = `${model.entityType}/${model.id}/${link}`;

                    Espo.Ajax.postRequest(url, data)
                        .then(() => {
                            Espo.Ui.success(this.view.translate('Linked'))

                            model.trigger(`update-related:${link}`);

                            model.trigger('after:relate');
                            model.trigger(`after:relate:${link}`);
                        });
                });
            });
        });
    }
}

export default SelectRelatedHelper;
