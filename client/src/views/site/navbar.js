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

define('views/site/navbar', 'view', function (Dep) {

    return Dep.extend({

        template: 'site/navbar',

        currentTab: null,

        data: function () {
            return {
                tabDefsList: this.tabDefsList,
                title: this.options.title,
                menuDataList: this.getMenuDataList(),
                quickCreateList: this.quickCreateList,
                enableQuickCreate: this.quickCreateList.length > 0,
                userId: this.getUser().id,
                logoSrc: this.getLogoSrc(),
            };
        },

        events: {
            'click .navbar-collapse.in a.nav-link': function (e) {
                var $a = $(e.currentTarget);
                var href = $a.attr('href');
                if (href) {
                    this.xsCollapse();
                }
            },
            'click a.nav-link': function (e) {
                if (this.isSideMenuOpened) {
                    this.closeSideMenu();
                }
            },
            'click a.navbar-brand.nav-link': function (e) {
                this.xsCollapse();
            },
            'click a[data-action="quick-create"]': function (e) {
                e.preventDefault();
                var scope = $(e.currentTarget).data('name');
                this.quickCreate(scope);
            },
            'click a.minimizer': function () {
                this.switchMinimizer();
            },
            'click a.side-menu-button': function () {
                this.switchSideMenu();
            },
            'click a.action': function (e) {
                var $el = $(e.currentTarget);

                var action = $el.data('action');
                var method = 'action' + Espo.Utils.upperCaseFirst(action);
                if (typeof this[method] == 'function') {
                    var data = $el.data();
                    this[method](data, e);
                    e.preventDefault();
                }
            },
            'click [data-action="toggleCollapsable"]': function () {
                this.toggleCollapsable();
            },
            'click li.show-more a': function (e) {
                e.stopPropagation();
                this.showMoreTabs();
            },
            'click .not-in-more > .nav-link-group': function (e) {
                this.handleGroupDropdownClick(e);
            },
            'click .in-more .nav-link-group': function (e) {
                this.handleGroupDropdownClick(e);
            },
        },

        handleGroupDropdownClick: function (e) {
            var $target = $(e.currentTarget).parent();

            if ($target.parent().hasClass('more-dropdown-menu')) {
                e.stopPropagation();

                if ($target.hasClass('open')) {
                    $target.removeClass('open');

                    return;
                }

                this.handleGroupDropdownInMoreOpen($target);

                return;
            }

            if ($target.hasClass('open')) {
                return;
            }

            this.handleGroupDropdownOpen($target);
        },

        handleGroupMenuPosition: function ($menu, $target) {
            if (this.navbarAdjustmentHandler && this.navbarAdjustmentHandler.handleGroupMenuPosition()) {
                this.handleGroupMenuPosition($menu, $target);

                return;
            }

            var rectItem = $target.get(0).getBoundingClientRect();

            var windowHeight = window.innerHeight;

            var isVertical = this.getThemeManager().getParam('navbarIsVertical');

            if (
                !isVertical &&
                !$target.parent().hasClass('more-dropdown-menu')
            ) {
                var maxHeight = windowHeight - rectItem.bottom;

                this.handleGroupMenuScrolling($menu, $target, maxHeight);

                return;
            }

            var itemCount = $menu.children().length;

            if (isVertical) {
                var tabHeight = this.$tabs.find('> .tab').height();
            } else {
                var tabHeight = this.$tabs.find('.tab-group > ul > li:visible').height();
            }

            var menuHeight = tabHeight * itemCount;

            var top = rectItem.top - 1;

            if (top + menuHeight > windowHeight) {
                top = windowHeight - menuHeight - 2;

                if (top < 0) {
                    top = 0;
                }
            }

            $menu.css({
                top: top + 'px',
            });

            var maxHeight = windowHeight - top;

            this.handleGroupMenuScrolling($menu, $target, maxHeight);
        },

        handleGroupMenuScrolling: function ($menu, $target, maxHeight) {
            $menu.css({
                maxHeight: maxHeight + 'px',
            });

            var $window = $(window);

            $window.off('scroll.navbar-tab-group');

            $window.on('scroll.navbar-tab-group', function () {
                if (!$menu.get(0) || !$target.get(0)) {
                    return;
                }

                if (!$target.hasClass('open')) {
                    return;
                }

                $menu.scrollTop($window.scrollTop());
            });
        },

        handleGroupDropdownOpen: function ($target) {
            var $menu = $target.find('.dropdown-menu');

            this.handleGroupMenuPosition($menu, $target);

            setTimeout(function () {
                this.adjustBodyMinHeight();
            }.bind(this), 50);

            $target.off('hidden.bs.dropdown');

            $target.on('hidden.bs.dropdown', function () {
                this.adjustBodyMinHeight();
            }.bind(this));
        },

        handleGroupDropdownInMoreOpen: function ($target) {
            this.$el.find('.tab-group.tab.dropdown').removeClass('open');

            var $parentDropdown = this.$el.find('.more-dropdown-menu');

            $target.addClass('open');

            var $menu = $target.find('.dropdown-menu');

            var rectDropdown = $parentDropdown.get(0).getBoundingClientRect();

            var left = rectDropdown.right;

            $menu.css({
                left: left + 'px',
            });

            this.handleGroupMenuPosition($menu, $target);

            this.adjustBodyMinHeight();

            if (!this.getThemeManager().getParam('isVertical')) {
                if (left + $menu.width() > window.innerWidth) {
                    $menu.css({
                        left: rectDropdown.left - $menu.width() - 2,
                    });
                }
            }
        },

        isCollapsableVisible: function () {
            return this.$el.find('.navbar-body').hasClass('in');
        },

        toggleCollapsable: function () {
            if (this.isCollapsableVisible()) {
                this.hideCollapsable();
            } else {
                this.showCollapsable();
            }
        },

        hideCollapsable: function () {
            this.$el.find('.navbar-body').removeClass('in');
        },

        showCollapsable: function () {
            this.$el.find('.navbar-body').addClass('in');
        },

        xsCollapse: function () {
            this.hideCollapsable();
        },

        isMinimized: function () {
            return this.$body.hasClass('minimized');
        },

        switchSideMenu: function () {
            if (!this.isMinimized()) return;

            if (this.isSideMenuOpened) {
                this.closeSideMenu();
            } else {
                this.openSideMenu();
            }
        },

        openSideMenu: function () {
            this.isSideMenuOpened = true;
            this.$body.addClass('side-menu-opened');

            this.$sideMenuBackdrop = $('<div>').addClass('side-menu-backdrop');
            this.$sideMenuBackdrop.click(function () {
                this.closeSideMenu();
            }.bind(this));
            this.$sideMenuBackdrop.appendTo(this.$body);

            this.$sideMenuBackdrop2 = $('<div>').addClass('side-menu-backdrop');
            this.$sideMenuBackdrop2.click(function () {
                this.closeSideMenu();
            }.bind(this));
            this.$sideMenuBackdrop2.appendTo(this.$navbarRightContainer);
        },

        closeSideMenu: function () {
            this.isSideMenuOpened = false;
            this.$body.removeClass('side-menu-opened');
            this.$sideMenuBackdrop.remove();
            this.$sideMenuBackdrop2.remove();
        },

        switchMinimizer: function () {
            var $body = this.$body;
            if (this.isMinimized()) {
                if (this.isSideMenuOpened) {
                    this.closeSideMenu();
                }
                $body.removeClass('minimized');
                this.getStorage().set('state', 'siteLayoutState', 'expanded');
            } else {
                $body.addClass('minimized');
                this.getStorage().set('state', 'siteLayoutState', 'collapsed');
            }
            if (window.Event) {
                try {
                    window.dispatchEvent(new Event('resize'));
                } catch (e) {}
            }
        },

        getLogoSrc: function () {
            var companyLogoId = this.getConfig().get('companyLogoId');
            if (!companyLogoId) {
                return this.getBasePath() + (this.getThemeManager().getParam('logo') || 'client/img/logo.png');
            }
            return this.getBasePath() + '?entryPoint=LogoImage&id='+companyLogoId;
        },

        getTabList: function () {
            var tabList = this.getPreferences().get('useCustomTabList') ?
                this.getPreferences().get('tabList') :
                this.getConfig().get('tabList');

            tabList = Espo.Utils.cloneDeep(tabList || []);

            if (this.getThemeManager().getParam('navbarIsVertical')) {
                tabList.unshift('Home');
            }

            return tabList;
        },

        getQuickCreateList: function () {
            return this.getConfig().get('quickCreateList') || [];
        },

        setup: function () {
            this.getRouter().on('routed', function (e) {
                if (e.controller) {
                    this.selectTab(e.controller);
                } else {
                    this.selectTab(false);
                }
            }.bind(this));

            var tabList = this.getTabList();

            var scopes = this.getMetadata().get('scopes') || {};

            this.tabList = tabList.filter(
                function (item) {
                    if (!item) {
                        return false;
                    }

                    if (typeof item === 'object') {
                        item.itemList = item.itemList || [];

                        item.itemList = item.itemList.filter(
                            function (item) {
                                return this.filterTabItem(item);
                            },
                            this
                        );

                        if (!item.itemList.length) {
                            return false;
                        }

                        return true;
                    }

                    return this.filterTabItem(item);
                },
                this
            );

            this.quickCreateList = this.getQuickCreateList().filter(function (scope) {
                if (!scopes[scope]) return false;
                if ((scopes[scope] || {}).disabled) return;
                if ((scopes[scope] || {}).acl) {
                    return this.getAcl().check(scope, 'create');
                }
                return true;
            }, this);

            this.createView('notificationsBadge', 'views/notification/badge', {
                el: this.options.el + ' .notifications-badge-container'
            });

            this.setupGlobalSearch();

            this.setupTabDefsList();

            this.once('remove', function () {
                $(window).off('resize.navbar');
                $(window).off('scroll.navbar');
                $(window).off('scroll.navbar-tab-group');
            });
        },

        filterTabItem: function (scope) {
            if (~['Home', '_delimiter_', '_delimiter-ext_'].indexOf(scope)) {
                return true;
            }

            var scopes = this.getMetadata().get('scopes') || {};

            if (!scopes[scope]) {
                return false;
            }

            var defs = scopes[scope] || {};

            if (defs.disabled) {
                return;
            }

            if (defs.acl) {
                return this.getAcl().check(scope);
            }
            if (defs.tabAclPermission) {
                var level = this.getAcl().get(defs.tabAclPermission);

                return level && level !== 'no';
            }

            return true;
        },

        setupGlobalSearch: function () {
            this.globalSearchAvailable = false;
            (this.getConfig().get('globalSearchEntityList') || []).forEach(function (scope) {
                if (this.globalSearchAvailable) return;
                if (this.getAcl().checkScope(scope)) {
                    this.globalSearchAvailable = true;
                }
            }, this);

            if (this.globalSearchAvailable) {
                this.createView('globalSearch', 'views/global-search/global-search', {
                    el: this.options.el + ' .global-search-container'
                });
            }
        },

        adjustHorizontal: function () {
            var smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');
            var navbarHeight = this.getNavbarHeight();

            var $window = $(window);

            var $tabs = this.$tabs;
            var $more = this.$more;
            var $moreDropdown = this.$moreDropdown;

            $window.on('resize.navbar', function() {
                updateWidth();
            });

            $window.on('scroll.navbar', function () {
                if (!this.isMoreDropdownShown) return;
                $more.scrollTop($window.scrollTop());
            }.bind(this));

            this.$moreDropdown.on('shown.bs.dropdown', function () {
                $more.scrollTop($window.scrollTop());
            });

            this.on('show-more-tabs', function () {
                $more.scrollTop($window.scrollTop());
            });

            var updateMoreHeight = function () {
                var windowHeight = window.innerHeight;
                var windowWidth = window.innerWidth;

                if (windowWidth < smallScreenWidth) {
                    $more.css('max-height', '');
                    $more.css('overflow-y', '');
                } else {
                    $more.css('overflow-y', 'hidden');
                    $more.css('max-height', (windowHeight - navbarHeight) + 'px');
                }
            }.bind(this);

            $window.on('resize.navbar', function() {
                updateMoreHeight();
            });
            updateMoreHeight();

            var hideOneTab = function () {
                var count = $tabs.children().length;
                if (count <= 1) return;
                var $one = $tabs.children().eq(count - 2);
                $one.prependTo($more);
            };
            var unhideOneTab = function () {
                var $one = $more.children().eq(0);
                if ($one.length) {
                    $one.insertBefore($moreDropdown);
                }
            };

            var $navbar = $('#navbar .navbar');

            if (window.innerWidth >= smallScreenWidth) {
                $tabs.children('li').each(function (i, li) {
                    hideOneTab();
                });
                $navbar.css('max-height', 'unset');
                $navbar.css('overflow', 'visible');
            }

            var navbarBaseWidth = this.getThemeManager().getParam('navbarBaseWidth') || 565;

            var tabCount = this.tabList.length;

            var navbarNeededHeight = navbarHeight + 1;

            this.adjustBodyMinHeightMethodName = 'adjustBodyMinHeightHorizontal';

            $moreDd = $('#nav-more-tabs-dropdown');
            $moreLi = $moreDd.closest('li');

            var updateWidth = function () {
                var windowWidth = window.innerWidth;
                var moreWidth = $moreLi.width();

                $more.children('li.not-in-more').each(function (i, li) {
                    unhideOneTab();
                });

                if (windowWidth < smallScreenWidth) {
                    return;
                }

                $navbar.css('max-height', navbarHeight + 'px');
                $navbar.css('overflow', 'hidden');

                $more.parent().addClass('hidden');

                var headerWidth = this.$el.width();

                var maxWidth = headerWidth - navbarBaseWidth - moreWidth;
                var width = $tabs.width();

                var i = 0;
                while (width > maxWidth) {
                    hideOneTab();
                    width = $tabs.width();
                    i++;
                    if (i >= tabCount) {
                        setTimeout(function () {
                            updateWidth();
                        }, 100);
                        break;
                    }
                }

                $navbar.css('max-height', 'unset');
                $navbar.css('overflow', 'visible');

                if ($more.children().length > 0) {
                    $moreDropdown.removeClass('hidden');
                }
            }.bind(this);

            var processUpdateWidth = function (isRecursive) {
                if ($navbar.height() > navbarNeededHeight) {
                    updateWidth();
                    setTimeout(function () {
                        processUpdateWidth(true);
                    }, 200);
                } else {
                    if (!isRecursive) {
                        updateWidth();
                        setTimeout(function () {
                            processUpdateWidth(true);
                        }, 10);
                    }
                    setTimeout(function () {
                        processUpdateWidth(true);
                    }, 1000);
                }
            }.bind(this);

            if ($navbar.height() <= navbarNeededHeight && $more.children().length === 0) {
                $more.parent().addClass('hidden');
            }

            processUpdateWidth();
        },

        adjustVertical: function () {
            var smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');
            var navbarStaticItemsHeight = this.getStaticItemsHeight();

            var $window = $(window);

            var $tabs = this.$tabs;
            var $more = this.$more;

            this.adjustBodyMinHeightMethodName = 'adjustBodyMinHeightVertical';

            if ($more.children().length === 0) {
                $more.parent().addClass('hidden');
            }

            $window.on('scroll.navbar', function () {
                $tabs.scrollTop($window.scrollTop());
                if (!this.isMoreDropdownShown) return;
                $more.scrollTop($window.scrollTop());
            }.bind(this));

            this.$moreDropdown.on('shown.bs.dropdown', function () {
                $more.scrollTop($window.scrollTop());
            });

            this.on('show-more-tabs', function () {
                $more.scrollTop($window.scrollTop());
            });

            var updateSizeForVertical = function () {
                var windowHeight = window.innerHeight;
                var windowWidth = window.innerWidth;

                if (windowWidth < smallScreenWidth) {
                    $tabs.css('height', 'auto');
                    $more.css('max-height', '');
                } else {
                    $tabs.css('height', (windowHeight - navbarStaticItemsHeight) + 'px');
                    $more.css('max-height', windowHeight + 'px');
                }
            }.bind(this);

            $(window).on('resize.navbar', function() {
                updateSizeForVertical();
            });
            updateSizeForVertical();

            this.$el.find('.notifications-badge-container').insertAfter(this.$el.find('.quick-create-container'));

            this.adjustBodyMinHeight();
        },

        getNavbarHeight: function () {
            return this.getThemeManager().getParam('navbarHeight') || 43;
        },

        getStaticItemsHeight: function () {
            return this.getThemeManager().getParam('navbarStaticItemsHeight') || 91;
        },

        adjustBodyMinHeight: function () {
            if (!this.adjustBodyMinHeightMethodName) return;

            this[this.adjustBodyMinHeightMethodName]();
        },

        adjustBodyMinHeightVertical: function () {
            var minHeight = this.$tabs.get(0).scrollHeight + this.getStaticItemsHeight();

            var moreHeight = 0;
            this.$more.find('> li:visible').each(function (i, el) {
                var $el = $(el);
                moreHeight += $el.height();
            }.bind(this));

            minHeight = Math.max(minHeight, moreHeight);

            var tabHeight = this.$tabs.find('> .tab').height();

            this.tabList.forEach(function (item, i) {
                if (typeof item !== 'object') {
                    return;
                }

                var $li = this.$el.find('li.tab[data-name="group-'+i+'"]');

                if (!$li.hasClass('open')) {
                    return;
                }

                var tabCount = (item.itemList || []).length;

                var menuHeight = tabHeight * tabCount;

                if (menuHeight > minHeight) {
                    minHeight = menuHeight;
                }
            }, this);

            this.$body.css('minHeight', minHeight + 'px');
        },

        adjustBodyMinHeightHorizontal: function () {
            var minHeight = this.getNavbarHeight();

            this.$more.find('> li').each(function (i, el) {
                var $el = $(el);

                if (!this.isMoreTabsShown) {
                    if ($el.hasClass('after-show-more')) {
                        return;
                    }
                } else {
                    if ($el.hasClass('show-more')) {
                        return;
                    }
                }

                minHeight += $el.height();
            }.bind(this));

            var tabHeight = this.$tabs.find('.tab-group > ul > li:visible').height();

            this.tabList.forEach(function (item, i) {
                if (typeof item !== 'object') {
                    return;
                }

                var $li = this.$el.find('li.tab[data-name="group-'+i+'"]');

                if (!$li.hasClass('open')) {
                    return;
                }

                var tabCount = (item.itemList || []).length;

                var menuHeight = tabHeight * tabCount;

                if (menuHeight > minHeight) {
                    minHeight = menuHeight;
                }
            }, this);

            this.$body.css('minHeight', minHeight + 'px');
        },

        afterRender: function () {
            this.$body = $('body');
            this.$tabs = this.$el.find('ul.tabs');
            this.$more = this.$tabs.find('li.more > ul');

            var $moreDd = this.$moreDropdown = this.$tabs.find('li.more');

            $moreDd.on('shown.bs.dropdown', function () {
                this.isMoreDropdownShown = true;
                this.adjustBodyMinHeight();
            }.bind(this));

            $moreDd.on('hidden.bs.dropdown', function () {
                this.isMoreDropdownShown = false;
                this.hideMoreTabs();
                this.adjustBodyMinHeight();
            }.bind(this));

            this.selectTab(this.getRouter().getLast().controller);

            var layoutState = this.getStorage().get('state', 'siteLayoutState');
            if (!layoutState) {
                layoutState = $(window).width() > 1320 ? 'expanded' : 'collapsed';
            }

            var layoutMinimized = false;
            if (layoutState === 'collapsed') {
                layoutMinimized = true;
            }

            if (layoutMinimized) {
                var $body = $('body');
                $body.addClass('minimized');
            }
            this.$navbar = this.$el.find('> .navbar');
            this.$navbarRightContainer = this.$navbar.find('> .navbar-body > .navbar-right-container');

            var handlerClassName = this.getThemeManager().getParam('navbarAdjustmentHandler');
            if (handlerClassName) {
                require(handlerClassName, function (Handler) {
                    var handler = new Handler(this);

                    this.navbarAdjustmentHandler = handler;

                    handler.process();
                }.bind(this));
            }

            if (this.getThemeManager().getParam('skipDefaultNavbarAdjustment')) return;

            if (this.getThemeManager().getParam('navbarIsVertical')) {
                var process = function () {
                    if (this.$navbar.height() < $(window).height() / 2) {
                        setTimeout(function () {
                            process();
                        }.bind(this), 50);
                        return;
                    }
                    if (this.getThemeManager().isUserTheme()) {
                        setTimeout(function () {
                            this.adjustVertical();
                        }.bind(this), 10);
                        return;
                    }
                    this.adjustVertical();
                }.bind(this);
                process();
            } else {
                var process = function () {
                    if (this.$el.width() < $(window).width() / 2) {
                        setTimeout(function () {
                            process();
                        }.bind(this), 50);
                        return;
                    }
                    if (this.getThemeManager().isUserTheme()) {
                        setTimeout(function () {
                            this.adjustHorizontal();
                        }.bind(this), 10);
                        return;
                    }
                    this.adjustHorizontal();
                }.bind(this);
                process();
            }
        },

        selectTab: function (name) {
            if (this.currentTab != name) {
                this.$el.find('ul.tabs li.active').removeClass('active');
                if (name) {
                    this.$el.find('ul.tabs li[data-name="' + name + '"]').addClass('active');
                }
                this.currentTab = name;
            }
        },

        setupTabDefsList: function () {
            var tabDefsList = [];

            var moreIsMet = false;
            var isHidden = false;

            var colorsDisabled =
                this.getPreferences().get('scopeColorsDisabled') ||
                this.getPreferences().get('tabColorsDisabled') ||
                this.getConfig().get('scopeColorsDisabled') ||
                this.getConfig().get('tabColorsDisabled');

            var tabIconsDisabled = this.getConfig().get('tabIconsDisabled');

            var params = {
                colorsDisabled: colorsDisabled,
                tabIconsDisabled: tabIconsDisabled,
            };

            var vars = {
                moreIsMet: false,
                isHidden: false,
            };

            this.tabList.forEach(function (tab, i) {
                if (tab === '_delimiter_' || tab === '_delimiter-ext_') {
                    if (!vars.moreIsMet) {
                        vars.moreIsMet = true;

                        return;
                    } else {
                        if (i == this.tabList.length - 1) {
                            return;
                        }

                        vars.isHidden = true;

                        tabDefsList.push({
                            name: 'show-more',
                            link: 'javascript:',
                            isInMore: true,
                            className: 'show-more',
                            html: '<span class="fas fa-ellipsis-h more-icon"></span>',
                        });

                        return;
                    }
                }

                tabDefsList.push(
                    this.prepareTabItemDefs(params, tab, i, vars)
                );
            }, this);

            this.tabDefsList = tabDefsList;
        },

        prepareTabItemDefs: function (params, tab, i, vars) {
            var label;
            var link;

            var iconClass = null;

            var color = null;

            var isGroup = false;

            var name = tab;

            var aClassName = 'nav-link';

            if (tab == 'Home') {
                label = this.getLanguage().translate(tab);
                link = '#';
            }
            else if (typeof tab === 'object') {
                isGroup = true;

                label = tab.text;
                color = tab.color;
                iconClass = tab.iconClass;

                name = 'group-' + i;

                link = 'javascript:';

                aClassName = 'nav-link-group';

                if (label.indexOf('label@') === 0) {
                    label = this.translate(label.substr(6), 'tabs');
                }
            }
            else {
                label = this.getLanguage().translate(tab, 'scopeNamesPlural');
                link = '#' + tab;
            }

            label = label || '';

            var shortLabel = label.substr(0, 2);

            if (!params.colorsDisabled && !isGroup) {
                color = this.getMetadata().get(['clientDefs', tab, 'color']);
            }

            if (!params.tabIconsDisabled && !isGroup) {
                iconClass = this.getMetadata().get(['clientDefs', tab, 'iconClass'])
            }

            var o = {
                link: link,
                label: label,
                shortLabel: shortLabel,
                name: name,
                isInMore: vars.moreIsMet,
                color: color,
                iconClass: iconClass,
                isAfterShowMore: vars.isHidden,
                aClassName: aClassName,
                isGroup: isGroup,
            };

            if (isGroup) {
                o.itemList = tab.itemList.map(
                    function (tab, i) {
                        return this.prepareTabItemDefs(params, tab, i, vars);
                    },
                    this
                );
            }

            if (vars.isHidden) {
                o.className = 'after-show-more';
            }

            if (color && !iconClass) {
                o.colorIconClass = 'color-icon fas fa-square-full';
            }

            return o;
        },

        getMenuDataList: function () {
            var avatarHtml = this.getHelper().getAvatarHtml(this.getUser().id, 'small', 16, 'avatar-link');
            if (avatarHtml) avatarHtml += ' ';

            var list = [
                {
                    link: '#User/view/' + this.getUser().id,
                    html: avatarHtml + this.getHelper().escapeString(this.getUser().get('name')),
                },
                {divider: true}
            ];

            if (this.getUser().isAdmin()) {
                list.push({
                    link: '#Admin',
                    label: this.getLanguage().translate('Administration')
                });
            }

            list.push({
                link: '#Preferences',
                label: this.getLanguage().translate('Preferences')
            });

            if (!this.getConfig().get('actionHistoryDisabled')) {
                list.push({
                    divider: true
                });
                list.push({
                    action: 'showLastViewed',
                    link: '#LastViewed',
                    label: this.getLanguage().translate('LastViewed', 'scopeNamesPlural')
                });
            }

            list = list.concat([
                {
                    divider: true
                },
                {
                    link: '#About',
                    label: this.getLanguage().translate('About')
                },
                {
                    action: 'logout',
                    label: this.getLanguage().translate('Log Out')
                }
            ]);

            return list;
        },

        quickCreate: function (scope) {
            Espo.Ui.notify(this.translate('Loading...'));
            var type = this.getMetadata().get(['clientDefs', scope, 'quickCreateModalType']) || 'edit';
            var viewName = this.getMetadata().get(['clientDefs', scope, 'modalViews', type]) || 'views/modals/edit';
            this.createView('quickCreate', viewName , {scope: scope}, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                });
                view.render();
            });
        },

        actionLogout: function () {
            this.getRouter().logout();
        },

        actionShowLastViewed: function () {
            this.createView('dialog', 'views/modals/last-viewed', {}, function (view) {
                view.render();
                this.listenTo(view, 'close', function () {
                    this.clearView('dialog');
                }, this);
            }, this);
        },

        actionShowHistory: function () {
            this.createView('dialog', 'views/modals/action-history', {}, function (view) {
                view.render();
                this.listenTo(view, 'close', function () {
                    this.clearView('dialog');
                }, this);
            }, this);
        },

        showMoreTabs: function () {
            this.$el.find('.tab-group.tab.dropdown').removeClass('open');

            this.isMoreTabsShown = true;

            this.$more.addClass('more-expanded');

            this.adjustBodyMinHeight();

            this.trigger('show-more-tabs');
        },

        hideMoreTabs: function () {
            if (!this.isMoreTabsShown) {
                return;
            }

            this.$more.removeClass('more-expanded');

            this.adjustBodyMinHeight();

            this.isMoreTabsShown = false;
        },
    });
});
