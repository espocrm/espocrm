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

import View from 'view';
import $ from 'jquery';

class NavbarSiteView extends View {

    template = 'site/navbar'

    currentTab = null

    events = {
        /** @this NavbarSiteView */
        'click .navbar-collapse.in a.nav-link': function (e) {
            const $a = $(e.currentTarget);
            const href = $a.attr('href');

            if (href) {
                this.xsCollapse();
            }
        },
        /** @this NavbarSiteView */
        'click a.nav-link': function () {
            if (this.isSideMenuOpened) {
                this.closeSideMenu();
            }
        },
        /** @this NavbarSiteView */
        'click a.navbar-brand.nav-link': function () {
            this.xsCollapse();
        },
        /** @this NavbarSiteView */
        'click a.minimizer': function () {
            this.switchMinimizer();
        },
        /** @this NavbarSiteView */
        'click a.side-menu-button': function () {
            this.switchSideMenu();
        },
        /** @this NavbarSiteView */
        'click a.action': function (e) {
            Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget);
        },
        /** @this NavbarSiteView */
        'click [data-action="toggleCollapsable"]': function () {
            this.toggleCollapsable();
        },
        /** @this NavbarSiteView */
        'click li.show-more a': function (e) {
            e.stopPropagation();
            this.showMoreTabs();
        },
        /** @this NavbarSiteView */
        'click .not-in-more > .nav-link-group': function (e) {
            this.handleGroupDropdownClick(e);
        },
        /** @this NavbarSiteView */
        'click .in-more .nav-link-group': function (e) {
            this.handleGroupDropdownClick(e);
        },
    }

    data() {
        return {
            tabDefsList1: this.tabDefsList.filter(item => !item.isInMore),
            tabDefsList2: this.tabDefsList.filter(item => item.isInMore),
            title: this.options.title,
            menuDataList: this.getMenuDataList(),
            userId: this.getUser().id,
            logoSrc: this.getLogoSrc(),
            itemDataList: this.getItemDataList(),
        };
    }

    handleGroupDropdownClick(e) {
        const $target = $(e.currentTarget).parent();

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
    }

    handleGroupMenuPosition($menu, $target) {
        if (this.navbarAdjustmentHandler && this.navbarAdjustmentHandler.handleGroupMenuPosition()) {
            this.handleGroupMenuPosition($menu, $target);

            return;
        }

        const rectItem = $target.get(0).getBoundingClientRect();

        const windowHeight = window.innerHeight;

        const isSide = this.isSide();

        if (
            !isSide &&
            !$target.parent().hasClass('more-dropdown-menu')
        ) {
            const maxHeight = windowHeight - rectItem.bottom;

            this.handleGroupMenuScrolling($menu, $target, maxHeight);

            return;
        }

        const itemCount = $menu.children().length;

        const tabHeight = isSide ?
            this.$tabs.find('> .tab:not(.tab-divider)').height() :
            this.$tabs.find('.tab-group > ul > li:visible').height();

        const menuHeight = tabHeight * itemCount;

        let top = rectItem.top - 1;

        if (top + menuHeight > windowHeight) {
            top = windowHeight - menuHeight - 2;

            if (top < 0) {
                top = 0;
            }
        }

        $menu.css({top: top + 'px'});

        const maxHeight = windowHeight - top;

        this.handleGroupMenuScrolling($menu, $target, maxHeight);
    }

    handleGroupMenuScrolling($menu, $target, maxHeight) {
        $menu.css({
            maxHeight: maxHeight + 'px',
        });

        const $window = $(window);

        $window.off('scroll.navbar-tab-group');

        $window.on('scroll.navbar-tab-group', () => {
            if (!$menu.get(0) || !$target.get(0)) {
                return;
            }

            if (!$target.hasClass('open')) {
                return;
            }

            $menu.scrollTop($window.scrollTop());
        });
    }

    handleGroupDropdownOpen($target) {
        const $menu = $target.find('.dropdown-menu');

        this.handleGroupMenuPosition($menu, $target);

        setTimeout(() => {
            this.adjustBodyMinHeight();
        }, 50);

        $target.off('hidden.bs.dropdown');

        $target.on('hidden.bs.dropdown', () => {
            this.adjustBodyMinHeight();
        });
    }

    handleGroupDropdownInMoreOpen($target) {
        this.$el.find('.tab-group.tab.dropdown').removeClass('open');

        const $parentDropdown = this.$el.find('.more-dropdown-menu');

        $target.addClass('open');

        const $menu = $target.find('.dropdown-menu');

        const rectDropdown = $parentDropdown.get(0).getBoundingClientRect();

        const left = rectDropdown.right;

        $menu.css({
            left: left + 'px',
        });

        this.handleGroupMenuPosition($menu, $target);

        this.adjustBodyMinHeight();

        if (!this.isSide()) {
            if (left + $menu.width() > window.innerWidth) {
                $menu.css({
                    left: rectDropdown.left - $menu.width() - 2,
                });
            }
        }
    }

    isCollapsableVisible() {
        return this.$el.find('.navbar-body').hasClass('in');
    }

    toggleCollapsable() {
        if (this.isCollapsableVisible()) {
            this.hideCollapsable();
        } else {
            this.showCollapsable();
        }
    }

    hideCollapsable() {
        this.$el.find('.navbar-body').removeClass('in');
    }

    showCollapsable() {
        this.$el.find('.navbar-body').addClass('in');
    }

    xsCollapse() {
        this.hideCollapsable();
    }

    isMinimized() {
        return this.$body.hasClass('minimized');
    }

    switchSideMenu() {
        if (!this.isMinimized()) return;

        if (this.isSideMenuOpened) {
            this.closeSideMenu();
        } else {
            this.openSideMenu();
        }
    }

    openSideMenu() {
        this.isSideMenuOpened = true;

        this.$body.addClass('side-menu-opened');

        this.$sideMenuBackdrop =
            $('<div>')
                .addClass('side-menu-backdrop')
                .click(() => this.closeSideMenu())
                .appendTo(this.$body);

        this.$sideMenuBackdrop2 =
            $('<div>')
                .addClass('side-menu-backdrop')
                .click(() => this.closeSideMenu())
                .appendTo(this.$navbarRightContainer);
    }

    closeSideMenu() {
        this.isSideMenuOpened = false;
        this.$body.removeClass('side-menu-opened');
        this.$sideMenuBackdrop.remove();
        this.$sideMenuBackdrop2.remove();
    }

    switchMinimizer() {
        const $body = this.$body;

        if (this.isMinimized()) {
            if (this.isSideMenuOpened) {
                this.closeSideMenu();
            }

            $body.removeClass('minimized');

            this.getStorage().set('state', 'siteLayoutState', 'expanded');
        }
        else {
            $body.addClass('minimized');

            this.getStorage().set('state', 'siteLayoutState', 'collapsed');
        }

        if (window.Event) {
            try {
                window.dispatchEvent(new Event('resize'));
            } catch (e) {}
        }
    }

    getLogoSrc() {
        const companyLogoId = this.getConfig().get('companyLogoId');

        if (!companyLogoId) {
            return this.getBasePath() + (this.getThemeManager().getParam('logo') || 'client/img/logo.svg');
        }

        return this.getBasePath() + '?entryPoint=LogoImage&id='+companyLogoId;
    }

    getTabList() {
        let tabList = this.getPreferences().get('useCustomTabList') ?
            this.getPreferences().get('tabList') :
            this.getConfig().get('tabList');

        tabList = Espo.Utils.cloneDeep(tabList || []);

        if (this.isSide()) {
            tabList.unshift('Home');
        }

        return tabList;
    }

    setup() {
        this.getRouter().on('routed', (e) => {
            if (e.controller) {
                this.selectTab(e.controller);

                return;
            }

            this.selectTab(false);
        });

        const itemDefs = this.getMetadata().get(['app', 'clientNavbar', 'items']) || {};

        /** @type {string[]} */
        this.itemList = Object.keys(itemDefs)
            .filter(name => !itemDefs[name].disabled)
            .sort((name1, name2) => {
                const order1 = itemDefs[name1].order || 0;
                const order2 = itemDefs[name2].order || 0;

                return order1 - order2;
            });

        this.createView('notificationsBadge', 'views/notification/badge', {
            selector: '.notifications-badge-container',
        });

        const setup = () => {
            this.setupTabDefsList();

            return Promise
                .all(this.itemList.map(item => this.createItemView(item)));
        };

        const update = () => {
            setup().then(() => this.reRender());
        };

        this.setupGlobalSearch();
        setup();

        this.listenTo(this.getHelper().settings, 'sync', () => update());
        this.listenTo(this.getHelper().language, 'sync', () => update());

        this.once('remove', () => {
            $(window).off('resize.navbar');
            $(window).off('scroll.navbar');
            $(window).off('scroll.navbar-tab-group');

            this.$body.removeClass('has-navbar');
        });
    }

    getItemDataList() {
        const defsMap = {};

        this.itemList.forEach(name => {
            defsMap[name] = this.getItemDefs(name);
        });

        return this.itemList
            .filter(name => {
                const defs = defsMap[name];

                if (!defs) {
                    return false;
                }

                const view = this.getView(name + 'Item');

                if ('isAvailable' in view) {
                    return view.isAvailable();
                }

                return true;
            })
            .map(name => {
                return {
                    key: name + 'Item',
                    name: name,
                    class: defsMap[name].class || '',
                };
            });
    }

    /**
     *
     * @param {string} name
     * @return {{view: string, class: string}}
     */
    getItemDefs(name) {
        return this.getMetadata().get(['app', 'clientNavbar', 'items', name]);
    }

    /**
     * @param {string} name
     * @return {Promise}
     */
    createItemView(name) {
        const defs = this.getItemDefs(name)

        if (!defs || !defs.view) {
            return Promise.resolve();
        }

        const key = name + 'Item';

        return this.createView(key, defs.view, {selector: `[data-item="${name}"]`});
    }

    filterTabItem(scope) {
        if (~['Home', '_delimiter_', '_delimiter-ext_'].indexOf(scope)) {
            return true;
        }

        const scopes = this.getMetadata().get('scopes') || {};

        if (!scopes[scope]) {
            return false;
        }

        const defs = /** @type {{disabled?: boolean, acl?: boolean, tabAclPermission?: string}} */
            scopes[scope] || {};

        if (defs.disabled) {
            return;
        }

        if (defs.acl) {
            return this.getAcl().check(scope);
        }

        if (defs.tabAclPermission) {
            const level = this.getAcl().getPermissionLevel(defs.tabAclPermission);

            return level && level !== 'no';
        }

        return true;
    }

    setupGlobalSearch() {
        this.globalSearchAvailable = false;

        (this.getConfig().get('globalSearchEntityList') || []).forEach(scope => {
            if (this.globalSearchAvailable) {
                return;
            }

            if (this.getAcl().checkScope(scope)) {
                this.globalSearchAvailable = true;
            }
        });

        if (this.globalSearchAvailable) {
            this.createView('globalSearch', 'views/global-search/global-search', {
                selector: '.global-search-container',
            });
        }
    }

    adjustTop() {
        const smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');
        const navbarHeight = this.getNavbarHeight();

        const $window = $(window);

        const $tabs = this.$tabs;
        const $more = this.$more;
        const $moreDropdown = this.$moreDropdown;

        $window.on('resize.navbar', () => updateWidth());

        $window.on('scroll.navbar', () => {
            if (!this.isMoreDropdownShown) {
                return;
            }

            $more.scrollTop($window.scrollTop());
        });

        this.$moreDropdown.on('shown.bs.dropdown', () => {
            $more.scrollTop($window.scrollTop());
        });

        this.on('show-more-tabs', () => {
            $more.scrollTop($window.scrollTop());
        });

        const updateMoreHeight = () => {
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;

            if (windowWidth < smallScreenWidth) {
                $more.css('max-height', '');
                $more.css('overflow-y', '');
            } else {
                $more.css('overflow-y', 'hidden');
                $more.css('max-height', (windowHeight - navbarHeight) + 'px');
            }
        };

        $window.on('resize.navbar', () => {
            updateMoreHeight();
        });

        updateMoreHeight();

        const hideOneTab = () => {
            const count = $tabs.children().length;

            if (count <= 1) {
                return;
            }

            const $one = $tabs.children().eq(count - 2);

            $one.prependTo($more);
        };

        const unhideOneTab = () => {
            const $one = $more.children().eq(0);

            if ($one.length) {
                $one.insertBefore($moreDropdown);
            }
        };

        const $navbar = $('#navbar .navbar');

        if (window.innerWidth >= smallScreenWidth) {
            $tabs.children('li').each(() => {
                hideOneTab();
            });

            $navbar.css('max-height', 'unset');
            $navbar.css('overflow', 'visible');
        }

        const navbarBaseWidth = this.getThemeManager().getParam('navbarBaseWidth') || 555;

        const tabCount = this.tabList.length;

        const navbarNeededHeight = navbarHeight + 1;

        this.adjustBodyMinHeightMethodName = 'adjustBodyMinHeightTop';

        const $moreDd = $('#nav-more-tabs-dropdown');
        const $moreLi = $moreDd.closest('li');

        const updateWidth = () => {
            const windowWidth = window.innerWidth;
            const moreWidth = $moreLi.width();

            $more.children('li.not-in-more').each(() => {
                unhideOneTab();
            });

            if (windowWidth < smallScreenWidth) {
                return;
            }

            $navbar.css('max-height', navbarHeight + 'px');
            $navbar.css('overflow', 'hidden');

            $more.parent().addClass('hidden');

            const headerWidth = this.$el.width();

            const maxWidth = headerWidth - navbarBaseWidth - moreWidth;
            let width = $tabs.width();

            let i = 0;

            while (width > maxWidth) {
                hideOneTab();
                width = $tabs.width();
                i++;

                if (i >= tabCount) {
                    setTimeout(() => updateWidth(), 100);

                    break;
                }
            }

            $navbar.css('max-height', 'unset');
            $navbar.css('overflow', 'visible');

            if ($more.children().length > 0) {
                $moreDropdown.removeClass('hidden');
            }
        };

        const processUpdateWidth = isRecursive => {
            if ($navbar.height() > navbarNeededHeight) {
                updateWidth();
                setTimeout(() => processUpdateWidth(true), 200);

                return;
            }

            if (!isRecursive) {
                updateWidth();
                setTimeout(() => processUpdateWidth(true), 10);
            }

            setTimeout(() => processUpdateWidth(true), 1000);
        };

        if ($navbar.height() <= navbarNeededHeight && $more.children().length === 0) {
            $more.parent().addClass('hidden');
        }

        processUpdateWidth();
    }

    adjustSide() {
        const smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');
        const navbarStaticItemsHeight = this.getStaticItemsHeight();

        const $window = $(window);
        const $tabs = this.$tabs;
        const $more = this.$more;

        this.adjustBodyMinHeightMethodName = 'adjustBodyMinHeightSide';

        if ($more.children().length === 0) {
            $more.parent().addClass('hidden');
        }

        $window.on('scroll.navbar', () => {
            $window.scrollTop() ?
                this.$navbarRight.addClass('shadowed') :
                this.$navbarRight.removeClass('shadowed');

            $tabs.scrollTop($window.scrollTop());

            if (!this.isMoreDropdownShown) {
                return;
            }

            $more.scrollTop($window.scrollTop());
        });

        this.$moreDropdown.on('shown.bs.dropdown', () => {
            $more.scrollTop($window.scrollTop());
        });

        this.on('show-more-tabs', () => {
            $more.scrollTop($window.scrollTop());
        });

        const updateSizeForSide = () => {
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;

            this.$minimizer.removeClass('hidden');

            if (windowWidth < smallScreenWidth) {
                $tabs.css('height', 'auto');
                $more.css('max-height', '');

                return;
            }

            $tabs.css('height', (windowHeight - navbarStaticItemsHeight) + 'px');
            $more.css('max-height', windowHeight + 'px');
        };

        $(window).on('resize.navbar', () => {
            updateSizeForSide();
        });

        updateSizeForSide();

        this.adjustBodyMinHeight();
    }

    getNavbarHeight() {
        return this.getThemeManager().getParam('navbarHeight') || 43;
    }

    isSide() {
        return this.getThemeManager().getParam('navbar') === 'side';
    }

    getStaticItemsHeight() {
        return this.getThemeManager().getParam('navbarStaticItemsHeight') || 97;
    }

    adjustBodyMinHeight() {
        if (!this.adjustBodyMinHeightMethodName) {
            return;
        }

        this[this.adjustBodyMinHeightMethodName]();
    }

    adjustBodyMinHeightSide() {
        let minHeight = this.$tabs.get(0).scrollHeight + this.getStaticItemsHeight();

        let moreHeight = 0;

        this.$more.find('> li:visible').each((i, el) => {
            const $el = $(el);

            moreHeight += $el.outerHeight(true);
        });

        minHeight = Math.max(minHeight, moreHeight);

        const tabHeight = this.$tabs.find('> .tab:not(.tab-divider)').height();

        this.tabList.forEach((item, i) => {
            if (typeof item !== 'object') {
                return;
            }

            const $li = this.$el.find('li.tab[data-name="group-' + i + '"]');

            if (!$li.hasClass('open')) {
                return;
            }

            const tabCount = (item.itemList || []).length;

            const menuHeight = tabHeight * tabCount;

            if (menuHeight > minHeight) {
                minHeight = menuHeight;
            }
        });

        this.$body.css('minHeight', minHeight + 'px');
    }

    adjustBodyMinHeightTop() {
        let minHeight = this.getNavbarHeight();

        this.$more.find('> li').each((i, el) => {
            const $el = $(el);

            if (!this.isMoreTabsShown) {
                if ($el.hasClass('after-show-more')) {
                    return;
                }
            }
            else {
                if ($el.hasClass('show-more')) {
                    return;
                }
            }

            minHeight += $el.height();
        });

        const tabHeight = this.$tabs.find('.tab-group > ul > li:visible').height();

        this.tabList.forEach((item, i) => {
            if (typeof item !== 'object') {
                return;
            }

            const $li = this.$el.find('li.tab[data-name="group-' + i + '"]');

            if (!$li.hasClass('open')) {
                return;
            }

            const tabCount = (item.itemList || []).length;

            const menuHeight = tabHeight * tabCount;

            if (menuHeight > minHeight) {
                minHeight = menuHeight;
            }
        });

        this.$body.css('minHeight', minHeight + 'px');
    }

    afterRender() {
        this.$body = $('body');
        this.$tabs = this.$el.find('ul.tabs');
        this.$more = this.$tabs.find('li.more > ul');
        this.$minimizer = this.$el.find('a.minimizer');

        this.$body.addClass('has-navbar');

        const $moreDd = this.$moreDropdown = this.$tabs.find('li.more');

        $moreDd.on('shown.bs.dropdown', () => {
            this.isMoreDropdownShown = true;
            this.adjustBodyMinHeight();
        });

        $moreDd.on('hidden.bs.dropdown', () => {
            this.isMoreDropdownShown = false;
            this.hideMoreTabs();
            this.adjustBodyMinHeight();
        });

        this.selectTab(this.getRouter().getLast().controller);

        let layoutState = this.getStorage().get('state', 'siteLayoutState');

        if (!layoutState) {
            layoutState = $(window).width() > 1320 ? 'expanded' : 'collapsed';
        }

        let layoutMinimized = false;

        if (layoutState === 'collapsed') {
            layoutMinimized = true;
        }

        if (layoutMinimized) {
            this.$body.addClass('minimized');
        }

        this.$navbar = this.$el.find('> .navbar');
        this.$navbarRightContainer = this.$navbar.find('> .navbar-body > .navbar-right-container');
        this.$navbarRight = this.$navbarRightContainer.children();

        const handlerClassName = this.getThemeManager().getParam('navbarAdjustmentHandler');

        if (handlerClassName) {
            Espo.loader.require(handlerClassName, Handler => {
                const handler = new Handler(this);

                this.navbarAdjustmentHandler = handler;

                handler.process();
            });

            return;
        }

        if (this.getThemeManager().getParam('skipDefaultNavbarAdjustment')) {
            return;
        }

        this.adjustAfterRender();
    }

    adjustAfterRender() {
        if (this.isSide()) {
            const processSide = () => {
                if (this.$navbar.height() < $(window).height() / 2) {
                    setTimeout(() => processSide(), 50);

                    return;
                }

                if (this.getThemeManager().isUserTheme()) {
                    setTimeout(() => this.adjustSide(), 10);

                    return;
                }

                this.adjustSide();
            };

            processSide();

            return;
        }

        const process = () => {
            if (this.$el.width() < $(window).width() / 2) {
                setTimeout(() => process(), 50);

                return;
            }

            if (this.getThemeManager().isUserTheme()) {
                setTimeout(() => this.adjustTop(), 10);

                return;
            }

            this.adjustTop();
        };

        process();
    }

    selectTab(name) {
        if (this.currentTab !== name) {
            this.$el.find('ul.tabs li.active').removeClass('active');

            if (name) {
                this.$el.find('ul.tabs li[data-name="' + name + '"]').addClass('active');
            }

            this.currentTab = name;
        }
    }

    setupTabDefsList() {
        function isMoreDelimiter(item) {
            return item === '_delimiter_' || item === '_delimiter-ext_';
        }

        function isDivider(item) {
            return typeof item === 'object' && item.type === 'divider';
        }

        this.tabList = this.getTabList().filter(item => {
            if (!item) {
                return false;
            }

            if (typeof item === 'object') {
                if (item.type === 'divider') {
                    if (!this.isSide()) {
                        return false;
                    }

                    return true;
                }

                let itemList = (item.itemList || []).filter((item) => {
                    if (isDivider(item)) {
                        return true;
                    }

                    return this.filterTabItem(item);
                });

                itemList = itemList.filter((item, i) => {
                    if (!isDivider(item)) {
                        return true;
                    }

                    const nextItem = itemList[i + 1];

                    if (!nextItem) {
                        return true;
                    }

                    if (isDivider(nextItem)) {
                        return false;
                    }

                    return true;
                });

                itemList = itemList.filter((item, i) => {
                    if (!isDivider(item)) {
                        return true;
                    }

                    if (i === 0 || i === itemList.length - 1) {
                        return false;
                    }

                    return true;
                });

                item.itemList = itemList;

                return !!itemList.length;
            }

            return this.filterTabItem(item);
        });

        let moreIsMet = false;

        this.tabList = this.tabList.filter((item, i) => {
            const nextItem = this.tabList[i + 1];
            const prevItem = this.tabList[i - 1];

            if (isMoreDelimiter(item)) {
                moreIsMet = true;
            }

            if (!isDivider(item)) {
                return true;
            }

            if (!nextItem) {
                return true;
            }

            if (isDivider(nextItem)) {
                return false;
            }

            if (isDivider(prevItem) && isMoreDelimiter(nextItem) && moreIsMet) {
                return false;
            }

            return true;
        });

        const tabDefsList = [];

        const colorsDisabled =
            this.getPreferences().get('scopeColorsDisabled') ||
            this.getPreferences().get('tabColorsDisabled') ||
            this.getConfig().get('scopeColorsDisabled') ||
            this.getConfig().get('tabColorsDisabled');

        const tabIconsDisabled = this.getConfig().get('tabIconsDisabled');

        const params = {
            colorsDisabled: colorsDisabled,
            tabIconsDisabled: tabIconsDisabled,
        };

        const vars = {
            moreIsMet: false,
            isHidden: false,
        };

        this.tabList.forEach((tab, i) => {
            if (isMoreDelimiter(tab)) {
                if (!vars.moreIsMet) {
                    vars.moreIsMet = true;

                    return;
                }

                if (i === this.tabList.length - 1) {
                    return;
                }

                vars.isHidden = true;

                tabDefsList.push({
                    name: 'show-more',
                    isInMore: true,
                    className: 'show-more',
                    html: '<span class="fas fa-ellipsis-h more-icon"></span>',
                });

                return;
            }

            tabDefsList.push(
                this.prepareTabItemDefs(params, tab, i, vars)
            );
        });

        this.tabDefsList = tabDefsList;
    }

    prepareTabItemDefs(params, tab, i, vars) {
        let label;
        let link;

        let iconClass = null;
        let color = null;
        let isGroup = false;
        let isDivider = false;
        let name = tab;
        let aClassName = 'nav-link';

        const translateLabel = label => {
            if (label.indexOf('$') === 0) {
                return this.translate(label.slice(1), 'navbarTabs');
            }

            return label;
        };

        if (tab === 'Home') {
            label = this.getLanguage().translate(tab);
            link = '#';
        }
        else if (typeof tab === 'object' && tab.type === 'divider') {
            isDivider = true;
            label = tab.text;
            aClassName = 'nav-divider-text';
            name = 'divider-' + i;

            if (label) {
                label = translateLabel(label);
            }
        }
        else if (typeof tab === 'object') {
            isGroup = true;

            label = tab.text || '';
            color = tab.color;
            iconClass = tab.iconClass;

            name = 'group-' + i;

            link = null;

            aClassName = 'nav-link-group';

            if (label) {
                label = translateLabel(label);
            }
        }
        else {
            label = this.getLanguage().translate(tab, 'scopeNamesPlural');
            link = '#' + tab;
        }

        label = label || '';

        const shortLabel = label.substring(0, 2);

        if (!params.colorsDisabled && !isGroup && !isDivider) {
            color = this.getMetadata().get(['clientDefs', tab, 'color']);
        }

        if (!params.tabIconsDisabled && !isGroup && !isDivider) {
            iconClass = this.getMetadata().get(['clientDefs', tab, 'iconClass'])
        }

        const o = {
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
            isDivider: isDivider,
        };

        if (isGroup) {
            o.itemList = tab.itemList.map((tab, i) => {
                return this.prepareTabItemDefs(params, tab, i, vars);
            });
        }

        if (vars.isHidden) {
            o.className = 'after-show-more';
        }

        if (color && !iconClass) {
            o.colorIconClass = 'color-icon fas fa-square';
        }

        return o;
    }

    /**
     * @typedef {Object} MenuDataItem
     * @property {string} [link]
     * @property {string} [html]
     * @property {true} [divider]
     */

    /**
     * @return {MenuDataItem[]}
     */
    getMenuDataList() {
        let avatarHtml = this.getHelper().getAvatarHtml(this.getUser().id, 'small', 16, 'avatar-link');

        if (avatarHtml) {
            avatarHtml += ' ';
        }

        /** @type {MenuDataItem[]}*/
        let list = [
            {
                link: '#User/view/' + this.getUser().id,
                html: avatarHtml + this.getHelper().escapeString(this.getUser().get('name')),
            },
            {divider: true}
        ];

        if (this.getUser().isAdmin()) {
            list.push({
                link: '#Admin',
                label: this.getLanguage().translate('Administration'),
            });
        }

        list.push({
            link: '#Preferences',
            label: this.getLanguage().translate('Preferences'),
        });

        if (!this.getConfig().get('actionHistoryDisabled')) {
            list.push({divider: true});

            list.push({
                action: 'showLastViewed',
                link: '#LastViewed',
                label: this.getLanguage().translate('LastViewed', 'scopeNamesPlural'),
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
            },
        ]);

        return list;
    }

    // noinspection JSUnusedGlobalSymbols
    actionLogout() {
        this.getRouter().logout();
    }

    // noinspection JSUnusedGlobalSymbols
    actionShowLastViewed() {
        Espo.Ui.notify(' ... ');

        this.createView('dialog', 'views/modals/last-viewed', {}, (view) => {
            view.render();

            Espo.Ui.notify(false);

            this.listenToOnce(view, 'close', () => {
                this.clearView('dialog');
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionShowHistory() {
        this.createView('dialog', 'views/modals/action-history', {}, (view) => {
            view.render();

            this.listenTo(view, 'close', () => {
                this.clearView('dialog');
            });
        });
    }

    showMoreTabs() {
        this.$el.find('.tab-group.tab.dropdown').removeClass('open');

        this.isMoreTabsShown = true;
        this.$more.addClass('more-expanded');
        this.adjustBodyMinHeight();
        this.trigger('show-more-tabs');
    }

    hideMoreTabs() {
        if (!this.isMoreTabsShown) {
            return;
        }

        this.$more.removeClass('more-expanded');
        this.adjustBodyMinHeight();
        this.isMoreTabsShown = false;
    }
}

export default NavbarSiteView;
