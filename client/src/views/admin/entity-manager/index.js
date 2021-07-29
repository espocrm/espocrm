/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/entity-manager/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/entity-manager/index',

        scopeDataList: null,

        scope: null,

        data: function () {
            return {
                scopeDataList: this.scopeDataList,
            };
        },

        events: {
            'click button[data-action="createEntity"]': function (e) {
                this.createEntity();
            },

            'keyup input[data-name="quick-search"]': function (e) {
                this.processQuickSearch(e.currentTarget.value);
            },
        },

        setupScopeData: function () {
            this.scopeDataList = [];

            var scopeList = Object.keys(this.getMetadata().get('scopes'))
                .sort((v1, v2) => {
                    return v1.localeCompare(v2);
                });

            var scopeListSorted = [];

            scopeList.forEach((scope) => {
                var d = this.getMetadata().get('scopes.' + scope);

                if (d.entity && d.customizable) {
                    scopeListSorted.push(scope);
                }
            });

            scopeList.forEach((scope) => {
                var d = this.getMetadata().get('scopes.' + scope);

                if (d.entity && !d.customizable) {
                    scopeListSorted.push(scope);
                }
            });

            scopeList = scopeListSorted;

            scopeList.forEach((scope) => {
                var d = this.getMetadata().get('scopes.' + scope);

                var isRemovable = !!d.isCustom;

                if (d.isNotRemovable) {
                    isRemovable = false;
                }

                this.scopeDataList.push({
                    name: scope,
                    isCustom: d.isCustom,
                    isRemovable: isRemovable,
                    customizable: d.customizable,
                    type: d.type,
                    label: this.getLanguage().translate(scope, 'scopeNames'),
                    layouts: d.layouts,
                });
            });
        },

        setup: function () {
            this.setupScopeData();
        },

        createEntity: function () {
            this.createView('edit', 'views/admin/entity-manager/modals/edit-entity', {}, (view) => {
                view.render();

                this.listenTo(view, 'after:save', (o) => {
                    this.clearView('edit');

                    this.getRouter().navigate('#Admin/entityManager/scope=' + o.scope, {trigger: true});
                });

                this.listenTo(view, 'close', () => {
                    this.clearView('edit');
                });
            });
        },

        afterRender: function () {
            this.$noData = this.$el.find('.no-data');
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
        },

        processQuickSearch: function (text) {
            text = text.trim();

            let $noData = this.$noData;

            $noData.addClass('hidden');

            if (!text) {
                this.$el.find('table tr.scope-row').removeClass('hidden');

                return;
            }

            let matchedList = [];

            let lowerCaseText = text.toLowerCase();

            this.scopeDataList.forEach(item => {
                let matched = false;

                if (
                    item.label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                    item.name.toLowerCase().indexOf(lowerCaseText) === 0
                ) {
                    matched = true;
                }

                if (!matched) {
                    let wordList = item.label.split(' ')
                        .concat(
                            item.label.split(' ')
                        );

                    wordList.forEach((word) => {
                        if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                            matched = true;
                        }
                    });
                }

                if (matched) {
                    matchedList.push(item.name);
                }
            });

            if (matchedList.length === 0) {
                this.$el.find('table tr.scope-row').addClass('hidden');

                $noData.removeClass('hidden');

                return;
            }

            this.scopeDataList
                .map(item => item.name)
                .forEach(scope => {
                    if (!~matchedList.indexOf(scope)) {
                        this.$el.find('table tr.scope-row[data-scope="'+scope+'"]').addClass('hidden');

                        return;
                    }

                    this.$el.find('table tr.scope-row[data-scope="'+scope+'"]').removeClass('hidden');
                });
        },
    });
});
