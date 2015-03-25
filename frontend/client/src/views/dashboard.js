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

Espo.define('Views.Dashboard', 'View', function (Dep) {

    return Dep.extend({

        template: 'dashboard',

        dashboardLayout: null,

        currentTab: null,

        events: {
            'click button[data-action="selectTab"]': function (e) {
                var tab = parseInt($(e.currentTarget).data('tab'));
                this.selectTab(tab);
            },
            'click button[data-action="addDashlet"]': function () {
                this.createView('addDashlet', 'Modals.AddDashlet', {}, function (view) {
                    view.render();
                });
            },
            'click button[data-action="editTabs"]': function () {
                this.createView('editTabs', 'Modals.EditDashboard', {
                    dashboardLayout: this.dashboardLayout
                }, function (view) {
                    view.render();

                    this.listenToOnce(view, 'after:save', function (data) {
                        view.close();
                        var dashboardLayout = [];

                        dashboardLayout = dashboardLayout.filter(function (item, i) {
                            return dashboardLayout.indexOf(item) == i;
                        });

                        (data.dashboardTabList).forEach(function (name) {
                            var isExisting = false;
                            var layout = [[],[]];
                            this.dashboardLayout.forEach(function (d) {
                                if (d.name == name) {
                                    isExisting = true;
                                    layout = d.layout;
                                }
                            }, this);
                            dashboardLayout.push({
                                name: name,
                                layout: layout
                            });
                        }, this);

                        this.dashboardLayout = dashboardLayout;
                        this.updateDashletsLayout();

                        this.storeCurrentTab(0);
                        this.currentTab = 0;

                        this.setupDashlets(function () {
                            this.reRender();
                        }.bind(this));
                    }, this);
                }.bind(this));
            },
        },

        data: function () {
            return {
                displayTitle: this.options.displayTitle,
                currentTab: this.currentTab,
                tabCount: this.dashboardLayout.length,
                dashboardLayout: this.dashboardLayout
            };
        },

        getDashletsLayout: function (callback, context) {
            if (!this.dashboardLayout) {
                var defaultLayout = [
                    {
                        "name": "My Espo",
                        "layout": [[],[]]
                    }
                ];
                this.dashboardLayout = this.getPreferences().get('dashboardLayout') || defaultLayout;

                if (this.dashboardLayout.length == 0 || Object.prototype.toString.call(this.dashboardLayout) !== '[object Array]') {
                    this.dashboardLayout = defaultLayout;
                }
            }
            var dashboardLayout = this.dashboardLayout;

            var tabLayout = dashboardLayout[this.currentTab].layout || [];

            var layout = this.convertLayout(tabLayout);

            callback.call(context, layout);
        },

        convertLayout: function (tabLayout) {
            var layout = {
                type: 'columns-2',
                layout: [],
            };
            tabLayout.forEach(function (col) {
                var c = [];
                col.forEach(function (defs) {
                    if (defs && defs.name && defs.id) {
                        var o = {
                            name: 'dashlet-' + defs.id,
                            id: 'dashlet-container-' + defs.id,
                            view: 'Dashlet',
                            options: {
                                name: defs.name,
                                id: defs.id,
                            }
                        };
                        c.push(o);
                    }
                });
                layout.layout.push(c);
            });
            return layout;
        },

        storeCurrentTab: function (tab) {
            this.getStorage().set('state', 'dashboardTab', tab);
        },

        selectTab: function (tab) {
            this.$el.find('.page-header button[data-action="selectTab"]').removeClass('active');
            this.$el.find('.page-header button[data-action="selectTab"][data-tab="'+tab+'"]').addClass('active');

            this.currentTab = tab;
            this.storeCurrentTab(tab);
            this.setupDashlets(function (view) {
                view.render();
                this.makeSortable();
            }.bind(this));
        },

        setupDashlets: function (callback1, callback2) {
            this.clearView('dashlets');
            this.getDashletsLayout(function (layout) {
                this.createView('dashlets', 'Base', {
                    _layout: layout,
                    el: '#dashlets',
                    noCache: true,
                }, callback1);
                if (callback2) {
                    callback2();
                }
            }, this);
        },

        setup: function () {
            this.wait(true);

            this.currentTab = this.getStorage().get('state', 'dashboardTab') || 0;

            this.setupDashlets(null, function () {
                this.wait(false);
            }.bind(this));
        },

        afterRender: function () {
            this.makeSortable();
        },

        makeSortable: function () {
            $('#dashlets > div').css('min-height', '100px');
            $('#dashlets > div').sortable({
                handle: '.dashlet .panel-heading',
                connectWith: '#dashlets > div',
                forcePlaceholderSize: true,
                placeholder: 'dashlet-placeholder',
                start: function (e, ui) {
                    $(ui.placeholder).css('height', $(ui.item).height());
                },
                stop: function (e, ui) {
                    this.updateDom();
                    this.updateDashletsLayout();
                }.bind(this)
            });
        },

        updateDom: function () {
            var layout = [];
            $('#dashlets > div').each(function (i, col) {
                var c = [];
                $(col).children().each(function (i, cell) {
                    var name = $(cell).find('.dashlet').data('name');
                    var id = $(cell).find('.dashlet').data('id');
                    c.push({
                        name: name,
                        id: id,
                    });
                });
                layout.push(c);
            });
            this.dashboardLayout[this.currentTab].layout = layout;
        },

        updateDashletsLayout: function () {
            this.getPreferences().save({
                dashboardLayout: this.dashboardLayout
            }, {patch: true});
            this.getPreferences().trigger('update');
        },

        removeDashlet: function (id) {
            this.dashboardLayout[this.currentTab].layout.forEach(function (col, i) {
                col.forEach(function (o, j) {
                    if (o.id == id) {
                        col.splice(j, 1);
                        return;
                    }
                });
            });

            var o = {};
            o.dashletOptions = this.getPreferences().get('dashletOptions') || {};
            delete o.dashletOptions[id];
            o.dashboardLayout = this.dashboardLayout;

            this.getPreferences().save(o, {patch: true});
            this.getPreferences().trigger('update');
        },

        addDashlet: function (name) {
            var id = 'd' + (Math.floor(Math.random() * 1000001)).toString();

            this.dashboardLayout[this.currentTab].layout[0].unshift({
                name: name,
                id: id
            });

            this.updateDashletsLayout();

            $('#dashlets').children().first().prepend('<div id="dashlet-container-' + id + '"></div>');

            this.getView('dashlets').createView('dashlet-' + id, 'Dashlet', {
                label: name,
                name: name,
                id: id,
                el: '#dashlet-container-' + id
            }, function (view) {
                view.render();
            });
        },
    });
});

