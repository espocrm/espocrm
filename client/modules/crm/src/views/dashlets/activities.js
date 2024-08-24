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

import BaseDashletView from 'views/dashlets/abstract/base';
import MultiCollection from 'multi-collection';

class ActivitiesDashletView extends BaseDashletView {

    name = 'Activities'

    // language=Handlebars
    templateContent = '<div class="list-container">{{{list}}}</div>'

    rowActionsView = 'crm:views/record/row-actions/activities-dashlet'

    defaultListLayout = {
        rows: [
            [
                {
                    name: 'ico',
                    view: 'crm:views/fields/ico',
                    params: {
                        notRelationship: true,
                    },
                },
                {
                    name: 'name',
                    link: true,
                },
            ],
            [
                {
                    name: 'dateStart',
                    soft: true
                },
                {
                    name: 'parent',
                },
            ],
        ],
    }

    listLayoutEntityTypeMap = {
        Task: {
            rows: [
                [
                    {
                        name: 'ico',
                        view: 'crm:views/fields/ico',
                        params: {
                            notRelationship: true
                        },
                    },
                    {
                        name: 'name',
                        link: true,
                    },
                ],
                [
                    {
                        name: 'dateEnd',
                        soft: true
                    },
                    {
                        name: 'priority',
                        view: 'crm:views/task/fields/priority-for-dashlet',
                    },
                    {
                        name: 'parent',
                    },
                ],
            ]
        }
    }

    setup() {
        this.seeds = {};

        this.scopeList = this.getOption('enabledScopeList') || [];

        this.listLayout = {};

        this.scopeList.forEach((item) => {
            if (item in this.listLayoutEntityTypeMap) {
                this.listLayout[item] = this.listLayoutEntityTypeMap[item];

                return;
            }

            this.listLayout[item] = this.defaultListLayout;
        });

        this.wait(true);
        let i = 0;

        this.scopeList.forEach(scope => {
            this.getModelFactory().create(scope, seed => {
                this.seeds[scope] = seed;

                i++;

                if (i === this.scopeList.length) {
                    this.wait(false);
                }
            });
        });

        this.scopeList.slice(0).reverse().forEach(scope => {
            if (this.getAcl().checkScope(scope, 'create')) {
                this.actionList.unshift({
                    name: 'createActivity',
                    text: this.translate('Create ' + scope, 'labels', scope),
                    iconHtml: '<span class="fas fa-plus"></span>',
                    url: '#' + scope + '/create',
                    data: {
                        scope: scope,
                    },
                });
            }
        });
    }

    afterRender() {
        this.collection = new MultiCollection();
        this.collection.seeds = this.seeds;
        this.collection.url = 'Activities/upcoming';
        this.collection.maxSize = this.getOption('displayRecords') ||
            this.getConfig().get('recordsPerPageSmall') || 5;
        this.collection.data.entityTypeList = this.scopeList;
        this.collection.data.futureDays = this.getOption('futureDays');

        this.listenToOnce(this.collection, 'sync', () => {
            this.createView('list', 'crm:views/record/list-activities-dashlet', {
                selector: '> .list-container',
                pagination: false,
                type: 'list',
                rowActionsView: this.rowActionsView,
                checkboxes: false,
                collection: this.collection,
                listLayout: this.listLayout,
            }, view => {
                view.render();
            });
        });

        this.collection.fetch();
    }

    actionRefresh() {
        this.collection.fetch({
            previousTotal: this.collection.total,
            previousDataList: this.collection.models.map(model => {
                return Espo.Utils.cloneDeep(model.attributes);
            }),
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateActivity(data) {
        const scope = data.scope;
        const attributes = {};

        this.populateAttributesAssignedUser(scope, attributes);

        Espo.Ui.notify(' ... ');

        const viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';

        this.createView('quickCreate', viewName, {
            scope: scope,
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                this.actionRefresh();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateMeeting() {
        const attributes = {};

        this.populateAttributesAssignedUser('Meeting', attributes);

        Espo.Ui.notify(' ... ');

        const viewName = this.getMetadata().get('clientDefs.Meeting.modalViews.edit') || 'views/modals/edit';

        this.createView('quickCreate', viewName, {
            scope: 'Meeting',
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                this.actionRefresh();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateCall() {
        const attributes = {};

        this.populateAttributesAssignedUser('Call', attributes);

        Espo.Ui.notify(' ... ');

        const viewName = this.getMetadata().get('clientDefs.Call.modalViews.edit') || 'views/modals/edit';

        this.createView('quickCreate', viewName, {
            scope: 'Call',
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                this.actionRefresh();
            });
        });
    }

    populateAttributesAssignedUser(scope, attributes) {
        if (this.getMetadata().get(['entityDefs', scope, 'fields', 'assignedUsers'])) {
            attributes['assignedUsersIds'] = [this.getUser().id];
            attributes['assignedUsersNames'] = {};
            attributes['assignedUsersNames'][this.getUser().id] = this.getUser().get('name');
        } else {
            attributes['assignedUserId'] = this.getUser().id;
            attributes['assignedUserName'] = this.getUser().get('name');
        }
    }
}

export default ActivitiesDashletView;
