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

define('views/dashboard', ['view', 'lib!gridstack'], function (Dep, Gridstack) {

    return Dep.extend({

        template: 'dashboard',

        dashboardLayout: null,

        currentTab: null,

        WIDTH_MULTIPLIER: 3,

        events: {
            'click button[data-action="selectTab"]': function (e) {
                var tab = parseInt($(e.currentTarget).data('tab'));
                this.selectTab(tab);
            },
            'click button[data-action="addDashlet"]': function () {
                this.createView('addDashlet', 'views/modals/add-dashlet', {}, view => {
                    view.render();

                    this.listenToOnce(view, 'add', name => {
                        this.addDashlet(name);
                    });
                });
            },
            'click button[data-action="editTabs"]': function () {
                this.createView('editTabs', 'views/modals/edit-dashboard', {
                    dashboardLayout: this.dashboardLayout,
                }, view => {
                    view.render();

                    this.listenToOnce(view, 'after:save', data => {
                        view.close();

                        let dashboardLayout = [];

                        (data.dashboardTabList).forEach(name => {
                            var layout = [];
                            var id = null;

                            this.dashboardLayout.forEach(d => {
                                if (d.name === name) {
                                    layout = d.layout;
                                    id = d.id;
                                }
                            });

                            if (name in data.renameMap) {
                                name = data.renameMap[name];
                            }

                            var o = {
                                name: name,
                                layout: layout,
                            };

                            if (id) {
                                o.id = id;
                            }

                            dashboardLayout.push(o);
                        });

                        this.dashletIdList.forEach(item => {
                            this.clearView('dashlet-' + item);
                        });

                        this.dashboardLayout = dashboardLayout;
                        this.saveLayout();

                        this.storeCurrentTab(0);
                        this.currentTab = 0;
                        this.setupCurrentTabLayout();

                        this.reRender();
                    });
                });
            },
        },

        data: function () {
            return {
                displayTitle: this.options.displayTitle,
                currentTab: this.currentTab,
                tabCount: this.dashboardLayout.length,
                dashboardLayout: this.dashboardLayout,
                layoutReadOnly: this.layoutReadOnly,
            };
        },

        generateId: function () {
            return (Math.floor(Math.random() * 10000001)).toString();
        },

        setupCurrentTabLayout: function () {
            if (!this.dashboardLayout) {
                var defaultLayout = [
                    {
                        "name": "My Espo",
                        "layout": [],
                    }
                ];

                if (this.getConfig().get('forcedDashboardLayout')) {
                    this.dashboardLayout = this.getConfig().get('forcedDashboardLayout') || [];
                }
                else if (this.getUser().get('portalId')) {
                    this.dashboardLayout = this.getConfig().get('dashboardLayout') || [];
                }
                else {
                    this.dashboardLayout = this.getPreferences().get('dashboardLayout') || defaultLayout;
                }

                if (
                    this.dashboardLayout.length === 0 ||
                    Object.prototype.toString.call(this.dashboardLayout) !== '[object Array]'
                ) {
                    this.dashboardLayout = defaultLayout;
                }
            }

            var dashboardLayout = this.dashboardLayout || [];

            if (dashboardLayout.length <= this.currentTab) {
                this.currentTab = 0;
            }

            var tabLayout = dashboardLayout[this.currentTab].layout || [];

            tabLayout = GridStack.Utils.sort(tabLayout);

            this.currentTabLayout = tabLayout;
        },

        storeCurrentTab: function (tab) {
            this.getStorage().set('state', 'dashboardTab', tab);
        },

        selectTab: function (tab) {
            this.$el.find('.page-header button[data-action="selectTab"]').removeClass('active');
            this.$el.find('.page-header button[data-action="selectTab"][data-tab="'+tab+'"]').addClass('active');

            this.currentTab = tab;
            this.storeCurrentTab(tab);

            this.setupCurrentTabLayout();

            this.dashletIdList.forEach(id => {
                this.clearView('dashlet-'+id);
            });

            this.dashletIdList = [];

            this.reRender();
        },

        setup: function () {
            this.currentTab = this.getStorage().get('state', 'dashboardTab') || 0;
            this.setupCurrentTabLayout();

            this.dashletIdList = [];

            this.screenWidthXs = this.getThemeManager().getParam('screenWidthXs');

            if (this.getUser().get('portalId')) {
                this.layoutReadOnly = true;
                this.dashletsReadOnly = true;
            }
            else {
                var forbiddenPreferencesFieldList = this.getAcl().getScopeForbiddenFieldList('Preferences', 'edit');

                if (~forbiddenPreferencesFieldList.indexOf('dashboardLayout')) {
                    this.layoutReadOnly = true;
                }

                if (~forbiddenPreferencesFieldList.indexOf('dashletsOptions')) {
                    this.dashletthis.gridsReadOnly = true;
                }
            }

            this.once('remove', () => {
                if (this.grid) {
                    this.grid.destroy();
                }

                if (this.fallbackModeTimeout) {
                    clearTimeout(this.fallbackModeTimeout);
                }

                $(window).off('resize.dashboard');
            });
        },

        afterRender: function () {
            this.$dashboard = this.$el.find('> .dashlets');

            if (window.innerWidth >= this.screenWidthXs) {
                this.initGridstack();
            }
            else {
                this.initFallbackMode();
            }

            $(window).off('resize.dashboard');
            $(window).on('resize.dashboard', this.onResize.bind(this));
        },

        onResize: function () {
            if (this.isFallbackMode() && window.innerWidth >= this.screenWidthXs) {
                this.initGridstack();
            }
            else if (!this.isFallbackMode() && window.innerWidth < this.screenWidthXs) {
                this.initFallbackMode();
            }
        },

        isFallbackMode: function () {
            return this.$dashboard.hasClass('fallback');
        },

        preserveDashletViews: function () {
            this.preservedDashletViews = {};
            this.preservedDashletElements = {};

            this.currentTabLayout.forEach(o => {
                var key = 'dashlet-' + o.id;
                var view = this.getView(key);

                this.unchainView(key);

                this.preservedDashletViews[o.id] = view;

                var $el = view.$el.children(0);

                this.preservedDashletElements[o.id] = $el;

                $el.detach();
            });
        },

        addPreservedDashlet: function (id) {
            var view = this.preservedDashletViews[id];
            var $el = this.preservedDashletElements[id];

            this.$el.find('.dashlet-container[data-id="'+id+'"]').append($el);

            this.setView('dashlet-' + id, view);
        },

        clearPreservedDashlets: function () {
            this.preservedDashletViews = null;
            this.preservedDashletElements = null;
        },

        hasPreservedDashlets: function () {
            return !!this.preservedDashletViews;
        },

        initFallbackMode: function () {
            if (this.grid) {
                this.grid.destroy(false);
                this.grid = null;

                this.preserveDashletViews();
            }

            this.$dashboard.empty();

            var $dashboard = this.$dashboard;

            $dashboard.addClass('fallback');

            this.currentTabLayout.forEach(o => {
                var $item = this.prepareFallbackItem(o);

                $dashboard.append($item);
            });

            this.currentTabLayout.forEach(o => {
                if (!o.id || !o.name) {
                    return;
                }

                if (!this.getMetadata().get(['dashlets', o.name])) {
                    console.error("Dashlet " + o.name + " doesn't exist or not available.");

                    return;
                }

                if (this.hasPreservedDashlets()) {
                    this.addPreservedDashlet(o.id);

                    return;
                }

                this.createDashletView(o.id, o.name);
            });

            this.clearPreservedDashlets();

            if (this.fallbackModeTimeout) {
                clearTimeout(this.fallbackModeTimeout);
            }

            this.$dashboard.css('height', '');

            this.fallbackControlHeights();
        },

        fallbackControlHeights: function () {
            this.currentTabLayout.forEach(o => {
                var $container = this.$dashboard.find('.dashlet-container[data-id="'+o.id+'"]');

                var headerHeight = $container.find('.panel-heading').outerHeight();

                var $body = $container.find('.dashlet-body');

                var bodyEl = $body.get(0);

                if (!bodyEl) {
                    return;
                }

                if (bodyEl.scrollHeight > bodyEl.offsetHeight) {
                    var height = bodyEl.scrollHeight + headerHeight;

                    $container.css('height', height + 'px');
                }
            });

            this.fallbackModeTimeout = setTimeout(() => {
                this.fallbackControlHeights();
            }, 300);
        },

        initGridstack: function () {
            if (this.isFallbackMode()) {
                this.preserveDashletViews();
            }

            this.$dashboard.empty();

            var $gridstack = this.$gridstack = this.$dashboard;

            $gridstack.removeClass('fallback');

            if (this.fallbackModeTimeout) {
                clearTimeout(this.fallbackModeTimeout);
            }

            var draggable = false;
            var resizable = false;
            var disableDrag = false;
            var disableResize = false;

            if (this.getUser().isPortal()) {
                draggable = {
                    handle: '.dashlet-container .panel-heading',
                };

                resizable = {
                    handles: 'se',
                    helper: false,
                };

                disableDrag = true;
                disableResize = true;
            }

            var grid = this.grid = GridStack.init(
                {
                    cellHeight: this.getThemeManager().getParam('dashboardCellHeight'),
                    verticalMargin: this.getThemeManager().getParam('dashboardCellMargin'),
                    column: 12,
                    handle: '.dashlet-container .panel-heading',
                    disableDrag: disableDrag,
                    disableResize: disableResize,
                    disableOneColumnMode: true,
                },
                $gridstack.get(0)
            );

            grid.removeAll();

            this.currentTabLayout.forEach(o => {
                var $item = this.prepareGridstackItem(o.id, o.name);

                if (!this.getMetadata().get(['dashlets', o.name])) {
                    return;
                }

                grid.addWidget(
                    $item.get(0),
                    {
                        x: o.x * this.WIDTH_MULTIPLIER,
                        y: o.y,
                        width: o.width * this.WIDTH_MULTIPLIER,
                        height: o.height,
                    }
                );
            });

            $gridstack.find(' .grid-stack-item').css('position', 'absolute');

            this.currentTabLayout.forEach(o => {
                if (!o.id || !o.name) {
                    return;
                }

                if (!this.getMetadata().get(['dashlets', o.name])) {
                    console.error("Dashlet " + o.name + " doesn't exist or not available.");

                    return;
                }

                if (this.hasPreservedDashlets()) {
                    this.addPreservedDashlet(o.id);

                    return;
                }

                this.createDashletView(o.id, o.name);
            });

            this.clearPreservedDashlets();

            this.grid.on('change', (e, itemList) => {
                this.fetchLayout();
                this.saveLayout();
            });

            this.grid.on('resizestop', (e, ui) => {
                var id = $(e.target).data('id');
                var view = this.getView('dashlet-' + id);

                if (!view) {
                    return;
                }

                view.trigger('resize');
            });
        },

        fetchLayout: function () {
            var layout = _.map(this.$gridstack.find('.grid-stack-item'), el => {
                var $el = $(el);
                var node = $el.data('_gridstack_node') || {};

                return {
                    id: $el.data('id'),
                    name: $el.data('name'),
                    x: node.x / this.WIDTH_MULTIPLIER,
                    y: node.y,
                    width: node.width / this.WIDTH_MULTIPLIER,
                    height: node.height,
                };
            });

            this.dashboardLayout[this.currentTab].layout = layout;
        },

        prepareGridstackItem: function (id, name) {
            var $item = $('<div></div>');
            var $container = $('<div class="grid-stack-item-content dashlet-container"></div>');

            $container.attr('data-id', id);
            $container.attr('data-name', name);

            $item.attr('data-id', id);
            $item.attr('data-name', name);

            $item.append($container);

            return $item;
        },

        prepareFallbackItem: function (o) {
            var $item = $('<div></div>');
            var $container = $('<div class="dashlet-container"></div>');

            $container.attr('data-id', o.id);
            $container.attr('data-name', o.name);
            $container.attr('data-x', o.x);
            $container.attr('data-y', o.y);
            $container.attr('data-height', o.height);
            $container.attr('data-width', o.width);
            $container.css('height', (o.height * this.getThemeManager().getParam('dashboardCellHeight')) + 'px');

            $item.attr('data-id', o.id);
            $item.attr('data-name', o.name);

            $item.append($container);

            return $item;
        },

        saveLayout: function () {
            if (this.layoutReadOnly) {
                return;
            }

            this.getPreferences().save({
                dashboardLayout: this.dashboardLayout,
            }, {patch: true});

            this.getPreferences().trigger('update');
        },

        removeDashlet: function (id) {
            var revertToFallback = false;

            if (this.isFallbackMode()) {
                this.initGridstack();

                revertToFallback = true;
            }

            var $item = this.$gridstack.find('.grid-stack-item[data-id="'+id+'"]');

            this.grid.removeWidget($item.get(0), true);

            var layout = this.dashboardLayout[this.currentTab].layout;

            layout.forEach((o, i) => {
                if (o.id === id) {
                    layout.splice(i, 1);

                    return;
                }
            });

            var o = {};

            o.dashletsOptions = this.getPreferences().get('dashletsOptions') || {};

            delete o.dashletsOptions[id];

            o.dashboardLayout = this.dashboardLayout;

            if (this.layoutReadOnly) {
                return;
            }

            this.getPreferences().save(o, {patch: true});
            this.getPreferences().trigger('update');

            var index = this.dashletIdList.indexOf(id);

            if (~index) {
                this.dashletIdList.splice(index, index);
            }

            this.clearView('dashlet-' + id);

            this.setupCurrentTabLayout();

            if (revertToFallback) {
                this.initFallbackMode();
            }
        },

        addDashlet: function (name) {
            var revertToFallback = false;

            if (this.isFallbackMode()) {
                this.initGridstack();

                revertToFallback = true;
            }

            var id = 'd' + (Math.floor(Math.random() * 1000001)).toString();

            var $item = this.prepareGridstackItem(id, name);

            this.grid.addWidget(
                $item.get(0),
                {
                    x: 0 * this.WIDTH_MULTIPLIER,
                    y: 0,
                    width: 2 * this.WIDTH_MULTIPLIER,
                    height: 2,
                }
            );

            this.createDashletView(id, name, name, view => {
                this.fetchLayout();
                this.saveLayout();

                this.setupCurrentTabLayout();

                if (view.getView('body') && view.getView('body').afterAdding) {
                    view.getView('body').afterAdding.call(view.getView('body'));
                }

                if (revertToFallback) {
                    this.initFallbackMode();
                }
            });
        },

        createDashletView: function (id, name, label, callback, context) {
            var context = context || this;

            var o = {
                id: id,
                name: name,
            };

            if (label) {
                o.label = label;
            }

            return this.createView('dashlet-' + id, 'views/dashlet', {
                label: name,
                name: name,
                id: id,
                el: this.options.el + ' > .dashlets .dashlet-container[data-id="'+id+'"]',
                readOnly: this.dashletsReadOnly,
            }, view => {
                this.dashletIdList.push(id);

                view.render();

                this.listenToOnce(view, 'change', () => {
                    this.clearView(id);

                    this.createDashletView(id, name, label, view => {});
                });

                this.listenToOnce(view, 'remove-dashlet', () => {
                    this.removeDashlet(id);
                });

                if (callback) {
                    callback.call(this, view);
                }
            });
        },
    });
});
