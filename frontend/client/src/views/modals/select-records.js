/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('Views.Modals.SelectRecords', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'select-modal',

        multiple: false,

        header: false,

        template: 'modals.select-records',

        createButton: true,

        data: function () {
            return {
                createButton: this.createButton && this.getAcl().check(this.scope, 'edit')
            };
        },

        events: {
            'click button[data-action="create"]': function () {
        this.create();
            },
            'click .list a': function (e) {
                e.preventDefault();
            }
        },

        setup: function () {
            this.filters = this.options.filters || {};
            this.boolFilterList = this.options.boolFilterList || [];
            this.primaryFilterName = this.options.primaryFilterName || null;

            if ('multiple' in this.options) {
                this.multiple = this.options.multiple;
            }

            if ('createButton' in this.options) {
                this.createButton = this.options.createButton;
            }

            this.massRelateEnabled = this.options.massRelateEnabled;

            this.buttons = [
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            if (this.multiple) {
                this.buttons.unshift({
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

            if (['User', 'Team', 'Acl'].indexOf(this.scope) !== -1) {
                this.createButton = false;
            }

            this.header = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

            this.waitForView('list');
            this.waitForView('search');

            Espo.require('SearchManager', function (SearchManager) {
                this.getCollectionFactory().create(this.scope, function (collection) {

                    collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                    this.collection = collection;

                    var searchManager = new SearchManager(collection, 'listSelect', null, this.getDateTime());
                    searchManager.emptyOnReset = true;
                    if (this.filters) {
                        searchManager.setAdvanced(this.filters);
                    }
                    if (this.boolFilterList) {
                        var d = {};
                        this.boolFilterList.forEach(function (item) {
                            d[item] = true;
                        });
                        searchManager.setBool(d);
                    }
                    if (this.primaryFilterName) {
                        searchManager.setPrimary(this.primaryFilterName);
                    }

                    collection.where = searchManager.getWhere();

                    this.createView('search', 'Record.Search', {
                        collection: collection,
                        el: this.containerSelector + ' .search-container',
                        searchManager: searchManager,
                        disableSavePreset: true,
                    });

                    var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
                                   this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
                                   'Record.List';

                    this.listenToOnce(collection, 'sync', function () {
                        this.createView('list', viewName, {
                            collection: collection,
                            el: this.containerSelector + ' .list-container',
                            selectable: true,
                            checkboxes: this.multiple,
                            massActionsDisabled: true,
                            rowActionsView: false,
                            type: 'listSmall',
                            searchManager: searchManager,
                            checkAllResultDisabled: !this.massRelateEnabled,
                            buttonsDisabled: true
                        }, function (list) {
                            list.once('select', function (model) {
                                this.trigger('select', model);
                                this.close();
                            }.bind(this));
                        }.bind(this));

                    }.bind(this));

                    collection.fetch();

                }.bind(this));
            }.bind(this));
        },

        create: function () {
            var self = this;

            this.notify('Loading...');
            this.createView('quickCreate', 'Modals.Edit', {
                scope: this.scope,
                fullFormDisabled: true,
            }, function (view) {
                view.once('after:render', function () {
                    self.notify(false);
                });
                view.render();

                self.listenToOnce(view, 'leave', function () {
                    view.close();
                    self.close();
                });
                self.listenToOnce(view, 'after:save', function (model) {
                    view.close();
                    self.trigger('select', model);
                    setTimeout(function () {
                        self.close();
                    }, 10);

                }.bind(this));
            });
        },
    });
});

