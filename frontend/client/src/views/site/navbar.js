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

Espo.define('Views.Site.Navbar', 'View', function (Dep) {

    return Dep.extend({

        template: 'site.navbar',

        currentTab: null,

        data: function () {
            return {
                tabs: this.tabDefs,
                title: this.options.title,
                menu: this.getMenuDefs(),
                quickCreateList: this.quickCreateList,
                enableQuickCreate: this.quickCreateList.length > 0,
                userName: this.getUser().get('name'),
                userId: this.getUser().id,
                logoSrc: this.getLogoSrc()
            };
        },

        events: {
            'click .navbar-collapse.in a': function (e) {
                var $a = $(e.currentTarget);
                var href = $a.attr('href');
                if (href && href != '#') {
                    this.$el.find('.navbar-collapse.in').collapse('hide');
                }
            },
            'click .navbar-header a.navbar-brand': function (e) {
                this.$el.find('.navbar-collapse.in').collapse('hide');
            },
            'click a[data-action="quick-create"]': function (e) {
                e.preventDefault();
                var scope = $(e.currentTarget).data('name');
                this.notify('Loading...');
                this.createView('quickCreate', 'Modals.Edit', {scope: scope}, function (view) {
                    view.once('after:render', function () {
                        this.notify(false);
                    });
                    view.render();
                });
            },
        },

        getLogoSrc: function () {
            var companyLogoId = this.getConfig().get('companyLogoId');
            if (!companyLogoId) {
                return 'client/img/logo.png';
            }
            return '?entryPoint=LogoImage&size=small-logo';
        },

        setup: function () {
            this.getRouter().on('routed', function (e) {
                if (e.controller) {
                    this.selectTab(e.controller);
                } else {
                    this.selectTab(false);
                }
            }.bind(this));

            this.tabList = this.getConfig().get('tabList').filter(function (scope) {
                if (this.getMetadata().get('scopes.' + scope + '.acl')) {
                    return this.getAcl().check(scope);
                }
                return true;
            }, this);

            this.quickCreateList = (this.getConfig().get('quickCreateList') || []).filter(function (scope) {
                if (this.getMetadata().get('scopes.' + scope + '.acl')) {
                    return this.getAcl().check(scope, 'edit');
                }
                return true;
            }, this);

            this.createView('notificationsBadge', 'Notifications.Badge', {
                el: this.options.el + ' .notifications-badge-container'
            });

            this.createView('globalSearch', 'GlobalSearch.GlobalSearch', {
                el: this.options.el + ' .global-search-container'
            });

            this.tabDefs = this.getTabDefs();

            this.once('remove', function () {
                $(window).off('resize.navbar');
            });
        },


        afterRender: function () {
            this.selectTab(this.getRouter().getLast().controller);

            var self = this;

            var navbarIsVertical = this.getThemeManager().getParam('navbarIsVertical');
            var navbarStaticItemsHeight = this.getThemeManager().getParam('navbarStaticItemsHeight') || 0;

            var smallScreenWidth = 768;

            if (!navbarIsVertical) {
                var $tabs = this.$el.find('ul.tabs');
                var $more = $tabs.find('li.dropdown > ul');

                $(window).on('resize.navbar', function() {
                    updateWidth();
                });

                var hideOneTab = function () {
                    var count = $tabs.children().size();
                    var $one = $tabs.children().eq(count - 2);
                    $one.prependTo($more);
                };
                var unhideOneTab = function () {
                    var count = $tabs.children().size();
                    var $one = $more.children().eq(0);
                    if ($one.size()) {
                        $one.insertAfter($tabs.children().eq(count - 2));
                    }
                };

                var tabCount = this.tabList.length;
                var $navbar = $('#navbar .navbar');
                var navbarNeededHeight = 45;
                var moreWidth = $('#nav-more-tabs-dropdown').width();

                var updateWidth = function () {
                    var windowWidth = $(window.document).width();

                    var windowWidth = window.innerWidth;

                    $more.children('li').each(function (i, li) {
                        unhideOneTab();
                    });

                    $more.parent().addClass('hide');

                    if (windowWidth < smallScreenWidth) {
                        return;
                    }

                    var headerWidth = this.$el.width();

                    var maxWidth = headerWidth - 546 - moreWidth;

                    var width = $tabs.width();
                    while (width > maxWidth) {
                        hideOneTab();
                        width = $tabs.width();
                    }

                    if ($more.children().size() > 0) {
                        $more.parent().removeClass('hide');
                    }
                }.bind(this);

                var processUpdateWidth = function () {
                    if ($navbar.height() > navbarNeededHeight) {
                        updateWidth();
                        setTimeout(function () {
                            processUpdateWidth();
                        }, 200);
                    } else {
                        setTimeout(function () {
                            processUpdateWidth();
                        }, 1000);
                    }
                };

                if ($navbar.height() <= navbarNeededHeight) {
                    $more.parent().addClass('hide');
                }

                processUpdateWidth();
            } else {
                var $tabs = this.$el.find('ul.tabs');

                var updateHeight = function () {
                    var windowHeight = window.innerHeight;
                    var windowWidth = window.innerWidth;

                    if (windowWidth < smallScreenWidth) {
                        $tabs.css('height', 'none');
                        return;
                    }

                    $tabs.css('height', (windowHeight - navbarStaticItemsHeight) + 'px');
                }.bind(this);

                $(window).on('resize.navbar', function() {
                    updateHeight();
                });
                updateHeight();
            }

        },

        selectTab: function (name) {
            if (this.currentTab != name) {
                this.$el.find('ul.tabs li.active').removeClass('active');
                if (name) {
                    this.$el.find('ul.tabs  li[data-name="' + name + '"]').addClass('active');
                }
                this.currentTab = name;
            }
        },

        getTabList: function () {
            return this.tabList;
        },

        getTabDefs: function () {
            var tabDefs = [];
            this.getTabList().forEach(function (tab, i) {
                var o = {
                    link: '#' + tab,
                    label: this.getLanguage().translate(tab, 'scopeNamesPlural'),
                    name: tab
                };
                tabDefs.push(o);
            }, this);
            return tabDefs;
        },

        getMenuDefs: function () {
            var menuDefs = [
                {
                    link: '#Preferences',
                    label: this.getLanguage().translate('Preferences'),
                },
                {
                    link: '#About',
                    label: this.getLanguage().translate('About'),
                },
                {
                    divider: true,
                },
                {
                    link: '#clearCache',
                    label: this.getLanguage().translate('Clear Local Cache'),
                },
                {
                    link: '#logout',
                    label: this.getLanguage().translate('Log Out'),
                },
            ];

            if (this.getUser().isAdmin()) {
                menuDefs.unshift({
                    link: '#Admin',
                    label: this.getLanguage().translate('Administration'),
                });
            }
            return menuDefs;
        }
    });

});


