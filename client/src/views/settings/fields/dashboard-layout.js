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

define('views/settings/fields/dashboard-layout', ['views/fields/base', 'lib!gridstack'], function (Dep, GridStack) {

    return Dep.extend({

        detailTemplate: 'settings/fields/dashboard-layout/detail',
        editTemplate: 'settings/fields/dashboard-layout/edit',

        validationElementSelector: 'button[data-action="addDashlet"]',

        WIDTH_MULTIPLIER: 3,

        events: {
            'click button[data-action="selectTab"]': function (e) {
                var tab = parseInt($(e.currentTarget).data('tab'));
                this.selectTab(tab);
            },
            'click [data-action="removeDashlet"]': function (e) {
                var id = $(e.currentTarget).data('id');
                this.removeDashlet(id);
            },
            'click [data-action="editDashlet"]': function (e) {
                var id = $(e.currentTarget).data('id');
                var name = $(e.currentTarget).data('name');

                this.editDashlet(id, name);
            },
            'click button[data-action="editTabs"]': function () {
                this.editTabs();
            },
            'click button[data-action="addDashlet"]': function () {
                this.createView('addDashlet', 'views/modals/add-dashlet', {
                    parentType: this.model.entityType,
                }, view => {
                    view.render();

                    this.listenToOnce(view, 'add', (name) => {
                        this.addDashlet(name);
                    });
                });
            },
        },

        data: function () {
            return {
                dashboardLayout: this.dashboardLayout,
                currentTab: this.currentTab,
                isEmpty: this.isEmpty(),
            };
        },

        hasLocked: function () {
            return this.model.entityType === 'Preferences';
        },

        setup: function () {
            this.dashboardLayout = Espo.Utils.cloneDeep(this.model.get(this.name) || []);
            this.dashletsOptions = Espo.Utils.cloneDeep(this.model.get('dashletsOptions') || {});

            if (this.hasLocked()) {
                this.dashboardLocked = this.model.get('dashboardLocked') || false;
            }

            this.listenTo(this.model, 'change', () => {
                if (this.model.hasChanged(this.name)) {
                    this.dashboardLayout = Espo.Utils.cloneDeep(this.model.get(this.name) || []);
                }

                if (this.model.hasChanged('dashletsOptions')) {
                    this.dashletsOptions = Espo.Utils.cloneDeep(this.model.get('dashletsOptions') || {});
                }

                if (this.model.hasChanged(this.name)) {
                    if (this.dashboardLayout.length) {
                        if (this.isDetailMode()) {
                            this.selectTab(0);
                        }
                    }
                }

                if (this.hasLocked()) {
                    this.dashboardLocked = this.model.get('dashboardLocked') || false;
                }
            });

            this.currentTab = -1;
            this.currentTabLayout = null;

            if (this.dashboardLayout.length) {
                this.selectTab(0);
            }
        },

        selectTab: function (tab) {
            this.currentTab = tab;
            this.setupCurrentTabLayout();

            if (this.isRendered()) {
                this.reRender()
                    .then(() => {
                        this.$el
                            .find(`[data-action="selectTab"][data-tab="${tab}"]`)
                            .focus();
                    })
            }
        },

        setupCurrentTabLayout: function () {
            if (!~this.currentTab) {
                this.currentTabLayout = null;
            }

            var tabLayout = this.dashboardLayout[this.currentTab].layout || [];

            tabLayout = GridStack.Utils.sort(tabLayout);

            this.currentTabLayout = tabLayout;
        },

        addDashletHtml: function (id, name) {
            var $item = this.prepareGridstackItem(id, name);

            this.grid.addWidget(
                $item.get(0),
                {
                    x: 0,
                    y: 0,
                    w: 2 * this.WIDTH_MULTIPLIER,
                    h: 2,
                }
            );
        },

        generateId: function () {
            return (Math.floor(Math.random() * 10000001)).toString();
        },

        addDashlet: function (name) {
            var id = 'd' + (Math.floor(Math.random() * 1000001)).toString();

            if (!~this.currentTab) {
                this.dashboardLayout.push({
                    name: 'My Espo',
                    layout: [],
                    id: this.generateId(),
                });

                this.currentTab = 0;
                this.setupCurrentTabLayout();

                this.once('after:render', () => {
                    setTimeout(() => {
                        this.addDashletHtml(id, name);
                        this.fetchLayout();
                    }, 50);
                });

                this.reRender();
            }
            else {
                this.addDashletHtml(id, name);
                this.fetchLayout();
            }
        },

        removeDashlet: function (id) {
            let $item = this.$gridstack.find('.grid-stack-item[data-id="'+id+'"]');

            this.grid.removeWidget($item.get(0), true);

            var layout = this.dashboardLayout[this.currentTab].layout;

            layout.forEach((o, i) => {
                if (o.id === id) {
                    layout.splice(i, 1);
                }
            });

            delete this.dashletsOptions[id];

            this.setupCurrentTabLayout();
        },

        editTabs: function () {
            let options = {
                dashboardLayout: this.dashboardLayout,
                tabListIsNotRequired: true,
            };

            if (this.hasLocked()) {
                options.dashboardLocked = this.dashboardLocked;
            }

            this.createView('editTabs', 'views/modals/edit-dashboard', options, view => {
                view.render();

                this.listenToOnce(view, 'after:save', data => {
                    view.close();

                    let dashboardLayout = [];

                    data.dashboardTabList.forEach(name => {
                        var layout = [];
                        var id = this.generateId();

                        this.dashboardLayout.forEach(d => {
                            if (d.name === name) {
                                layout = d.layout;
                                id = d.id;
                            }
                        });

                        if (name in data.renameMap) {
                            name = data.renameMap[name];
                        }

                        dashboardLayout.push({
                            name: name,
                            layout: layout,
                            id: id,
                        });
                    });

                    this.dashboardLayout = dashboardLayout;

                    if (this.hasLocked()) {
                        this.dashboardLocked = data.dashboardLocked;
                    }

                    this.selectTab(0);

                    this.deleteNotExistingDashletsOptions();
                });
            });
        },

        deleteNotExistingDashletsOptions: function () {
            var idListMet = [];

            (this.dashboardLayout || []).forEach((itemTab) => {
                (itemTab.layout || []).forEach((item) => {
                    idListMet.push(item.id);
                });
            });

            Object.keys(this.dashletsOptions).forEach((id) => {
                if (!~idListMet.indexOf(id)) {
                    delete this.dashletsOptions[id];
                }
            });
        },

        editDashlet: function (id, name) {
            var options = this.dashletsOptions[id] || {};
            options = Espo.Utils.cloneDeep(options);

            var defaultOptions = this.getMetadata().get(['dashlets', name , 'options', 'defaults']) || {};

            Object.keys(defaultOptions).forEach((item) => {
                if (item in options) {
                    return;
                }

                options[item] = Espo.Utils.cloneDeep(defaultOptions[item]);
            });

            if (!('title' in options)) {
                options.title = this.translate(name, 'dashlets');
            }

            var optionsView = this.getMetadata().get(['dashlets', name, 'options', 'view']) ||
                'views/dashlets/options/base';

            this.createView('options', optionsView, {
                name: name,
                optionsData: options,
                fields: this.getMetadata().get(['dashlets', name, 'options', 'fields']) || {},
                userId: this.model.entityType === 'Preferences' ? this.model.id : null,
            }, view => {
                view.render();

                this.listenToOnce(view, 'save', (attributes) => {
                    this.dashletsOptions[id] = attributes;

                    view.close();

                    if ('title' in attributes) {
                        var title = attributes.title;

                        if (!title) {
                            title = this.translate(name, 'dashlets');
                        }

                        this.$el.find('[data-id="'+id+'"] .panel-title').text(title);
                    }
                });
            });
        },

        fetchLayout: function () {
            if (!~this.currentTab) {
                return;
            }

            this.dashboardLayout[this.currentTab].layout = _.map(this.$gridstack.find('.grid-stack-item'), el => {
                var $el = $(el);

                let x = $el.attr('gs-x');
                let y = $el.attr('gs-y');
                let h = $el.attr('gs-h');
                let w = $el.attr('gs-w');

                return {
                    id: $el.data('id'),
                    name: $el.data('name'),
                    x: x / this.WIDTH_MULTIPLIER,
                    y: y,
                    width: w / this.WIDTH_MULTIPLIER,
                    height: h,
                };
            });

            this.setupCurrentTabLayout();
        },

        afterRender: function () {
            if (this.currentTabLayout) {
                var $gridstack = this.$gridstack = this.$el.find('> .grid-stack');

                var grid = this.grid = GridStack.init({
                    minWidth: 4,
                    cellHeight: 60,
                    margin: 10,
                    column: 12,
                    resizable: {
                        handles: 'se',
                        helper: false
                    },
                    disableOneColumnMode: true,
                    animate: false,
                    staticGrid: this.mode !== 'edit',
                    disableResize: this.mode !== 'edit',
                    disableDrag: this.mode !== 'edit',
                });

                grid.removeAll();

                this.currentTabLayout.forEach((o) => {
                    var $item = this.prepareGridstackItem(o.id, o.name);

                    this.grid.addWidget(
                        $item.get(0),
                        {
                            x: o.x * this.WIDTH_MULTIPLIER,
                            y: o.y,
                            w: o.width * this.WIDTH_MULTIPLIER,
                            h: o.height,
                        }
                    );
                });

                $gridstack.find(' .grid-stack-item').css('position', 'absolute');

                $gridstack.on('change', (e, itemList) => {
                    this.fetchLayout();
                    this.trigger('change');
                });
            }
        },

        prepareGridstackItem: function (id, name) {
            let $item = $('<div>').addClass('grid-stack-item');
            let actionsHtml = '';

            if (this.isEditMode()) {
                actionsHtml +=
                    $('<div>')
                        .addClass('btn-group pull-right')
                        .append(
                            $('<button>')
                                .addClass('btn btn-default')
                                .attr('data-action', 'removeDashlet')
                                .attr('data-id', id)
                                .attr('title', this.translate('Remove'))
                                .append(
                                    $('<span>').addClass('fas fa-times')
                                )
                        )
                        .get(0)
                        .outerHTML;

                actionsHtml += $('<div>')
                    .addClass('btn-group pull-right')
                    .append(
                        $('<button>')
                            .addClass('btn btn-default')
                            .attr('data-action', 'editDashlet')
                            .attr('data-id', id)
                            .attr('data-name', name)
                            .attr('title', this.translate('Edit'))
                            .append(
                                $('<span>')
                                    .addClass('fas fa-pencil-alt fa-sm')
                                    .css({
                                        position: 'relative',
                                        top: '-1px',
                                    })
                            )
                    )
                    .get(0)
                    .outerHTML;
            }

            let title = this.getOption(id, 'title');

            if (!title) {
                title = this.translate(name, 'dashlets');
            }

            let headerHtml = $('<div>')
                .addClass('panel-heading')
                .append(actionsHtml)
                .append(
                    $('<h4>').addClass('panel-title').text(title)
                )
                .get(0).outerHTML;

            let $container =
                $('<div>')
                    .addClass('grid-stack-item-content panel panel-default')
                    .append(headerHtml);

            $container.attr('data-id', id);
            $container.attr('data-name', name);
            $item.attr('data-id', id);
            $item.attr('data-name', name);
            $item.append($container);

            return $item;
        },

        getOption: function (id, optionName) {
            var options = (this.model.get('dashletsOptions') || {})[id] || {};

            return options[optionName];
        },

        isEmpty: function () {
            var isEmpty = true;

            if (this.dashboardLayout && this.dashboardLayout.length) {
                this.dashboardLayout.forEach((item) => {
                    if (item.layout && item.layout.length) {
                        isEmpty = false;
                    }
                });
            }

            return isEmpty;
        },

        validateRequired: function () {
            if (!this.isRequired()) {
                return;
            }

            if (this.isEmpty()) {
                var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                this.showValidationMessage(msg);

                return true;
            }
        },

        fetch: function () {
            let data = {};

            if (!this.dashboardLayout || !this.dashboardLayout.length) {
                data[this.name] = null;
                data['dashletsOptions'] = {};

                return data;
            }

            data[this.name] = Espo.Utils.cloneDeep(this.dashboardLayout);

            data.dashletsOptions = Espo.Utils.cloneDeep(this.dashletsOptions);

            if (this.hasLocked()) {
                data.dashboardLocked = this.dashboardLocked;
            }

            return data;
        },
    });
});
