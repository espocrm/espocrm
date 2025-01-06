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

define('crm:views/target-list/record/panels/opted-out',  ['views/record/panels/relationship', 'multi-collection'],
function (Dep, MultiCollection) {

    return Dep.extend({

        name: 'optedOut',

        template: 'crm:target-list/record/panels/opted-out',

        scopeList: ['Contact', 'Lead', 'User', 'Account'],

        data: function () {
            return {
                currentTab: this.currentTab,
                scopeList: this.scopeList,
            };
        },

        getStorageKey: function () {
            return 'target-list-opted-out-' + this.model.entityType + '-' + this.name;
        },

        setup: function () {
            this.seeds = {};

            let linkList = this.getMetadata().get(['scopes', 'TargetList', 'targetLinkList']) || [];

            this.scopeList = [];

            linkList.forEach(link => {
                let entityType = this.getMetadata().get(['entityDefs', 'TargetList', 'links', link, 'entity']);

                if (entityType) {
                    this.scopeList.push(entityType);
                }
            });

            this.listLayout = {};

            this.scopeList.forEach(scope => {
                this.listLayout[scope] = {
                    rows: [
                        [
                            {
                                name: 'name',
                                link: true,
                            }
                        ]
                    ]
                };
            });

            if (this.scopeList.length) {
                this.wait(true);

                var i = 0;

                this.scopeList.forEach(scope => {
                    this.getModelFactory().create(scope, seed => {
                        this.seeds[scope] = seed;

                        i++;

                        if (i === this.scopeList.length) {
                            this.wait(false);
                        }
                    });
                });
            }

            this.listenTo(this.model, 'opt-out', () => {
                this.actionRefresh();
            });

            this.listenTo(this.model, 'cancel-opt-out', () => {
                this.actionRefresh();
            });
        },

        afterRender: function () {
            var url = 'TargetList/' + this.model.id + '/' + this.name;

            this.collection = new MultiCollection();
            this.collection.seeds = this.seeds;
            this.collection.url = url;

            this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

            this.listenToOnce(this.collection, 'sync', () => {
                this.createView('list', 'views/record/list-expanded', {
                    selector: '> .list-container',
                    pagination: false,
                    type: 'listRelationship',
                    rowActionsView: 'crm:views/target-list/record/row-actions/opted-out',
                    checkboxes: false,
                    collection: this.collection,
                    listLayout: this.listLayout,
                }, view => {
                    view.render();
                });
            });

            this.collection.fetch();
        },

        actionRefresh: function () {
            this.collection.fetch();
        },

        actionCancelOptOut: function (data) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ajax.postRequest('TargetList/action/cancelOptOut', {
                    id: this.model.id,
                    targetId: data.id,
                    targetType: data.type,
                }).then(() => {
                    this.collection.fetch();
                });
            });
        },
    });
});
