/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/modals/select-category-tree-records', 'views/modals/select-records', function (Dep) {

    return Dep.extend({

        setup: function () {
            this.filters = this.options.filters || {};
            this.boolFilterList = this.options.boolFilterList || {};
            this.primaryFilterName = this.options.primaryFilterName || null;

            if ('multiple' in this.options) {
                this.multiple = this.options.multiple;
            }

            this.createButton = false;
            this.massRelateEnabled = this.options.massRelateEnabled;

            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            if (this.multiple) {
                this.buttonList.unshift({
                    name: 'select',
                    style: 'primary',
                    label: 'Select',
                    onClick: function (dialog) {
                        var listView = this.getView('list');

                        if (listView.allResultIsChecked) {
                            var where = this.collection.where;
                            this.trigger('select', {
                                massRelate: true,
                                where: where
                            });
                        } else {
                            var list = listView.getSelected();
                            if (list.length) {
                                this.trigger('select', list);
                            }
                        }
                        dialog.close();
                    }.bind(this),
                });
            }

            this.scope = this.options.scope;

            this.headerHtml = '';
            var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
            this.headerHtml += this.translate('Select') + ': ';
            this.headerHtml += this.getLanguage().translate(this.scope, 'scopeNamesPlural');
            this.headerHtml = iconHtml + this.headerHtml;

            this.waitForView('list');

            Espo.require('search-manager', function (SearchManager) {
                this.getCollectionFactory().create(this.scope, function (collection) {

                    collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                    this.collection = collection;

                    var searchManager = new SearchManager(collection, 'listSelect', null, this.getDateTime());
                    searchManager.emptyOnReset = true;
                    if (this.filters) {
                        searchManager.setAdvanced(this.filters);
                    }
                    if (this.boolFilterList) {
                        searchManager.setBool(this.boolFilterList);
                    }
                    if (this.primaryFilterName) {
                        searchManager.setPrimary(this.primaryFilterName);
                    }

                    collection.where = searchManager.getWhere();
                    collection.url = collection.name + '/action/listTree';

                    var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelectCategoryTree') ||
                                   'views/record/list-tree';

                    this.listenToOnce(collection, 'sync', function () {
                        this.createView('list', viewName, {
                            collection: collection,
                            el: this.containerSelector + ' .list-container',
                            createDisabled: true,
                            selectable: true,
                            checkboxes: this.multiple,
                            massActionsDisabled: true,
                            searchManager: searchManager,
                            checkAllResultDisabled: true,
                            buttonsDisabled: true
                        }, function (list) {
                            list.once('select', function (model) {
                                this.trigger('select', model);
                                this.close();
                            }.bind(this));
                        }, this);
                    }, this);

                    collection.fetch();

                }, this);
            }.bind(this));
        },

    });
});

