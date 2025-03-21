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

import View from 'view';
import $ from 'jquery';
import TabsHelper from 'helpers/site/tabs';

class NavbarSiteView extends View {

    template = 'site/navbar'

    /**
     * @private
     * @type {string|false|null}
     */
    currentTab = null

    /**
     * @private
     * @type {HTMLElement}
     */
    navbarHeaderElement

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
            menuDataList: this.menuDataList,
            userId: this.getUser().id,
            logoSrc: this.getLogoSrc(),
            itemDataList: this.getItemDataList(),
        };
    }

    /**
     * @private
     */
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

    /**
     * @private
     */
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

    /**
     * @private
     */
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

    /**
     * @private
     */
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

    /**
     * @private
     */
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

    /**
     * @private
     */
    isCollapsibleVisible() {
        return this.$el.find('.navbar-body').hasClass('in');
    }

    /**
     * @private
     */
    toggleCollapsable() {
        if (this.isCollapsibleVisible()) {
            this.hideCollapsable();
        } else {
            this.showCollapsable();
        }
    }

    /**
     * @private
     */
    hideCollapsable() {
        this.$el.find('.navbar-body').removeClass('in');
    }

    /**
     * @private
     */
    showCollapsable() {
        this.$el.find('.navbar-body').addClass('in');
    }

    /**
     * @private
     */
    xsCollapse() {
        this.hideCollapsable();
    }

    /**
     * @private
     * @return {boolean}
     */
    isMinimized() {
        return document.body.classList.contains('minimized');
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

        document.body.classList.add('side-menu-opened');

        this.$sideMenuBackdrop =
            $('<div>')
                .addClass('side-menu-backdrop')
                .click(() => this.closeSideMenu())
                .appendTo(document.body);

        this.$sideMenuBackdrop2 =
            $('<div>')
                .addClass('side-menu-backdrop')
                .click(() => this.closeSideMenu())
                .appendTo(this.$navbarRightContainer);
    }

    /**
     * @private
     */
    closeSideMenu() {
        this.isSideMenuOpened = false;

        document.body.classList.remove('side-menu-opened')

        this.$sideMenuBackdrop.remove();
        this.$sideMenuBackdrop2.remove();
    }

    /**
     * @private
     */
    switchMinimizer() {
        if (this.isMinimized()) {
            if (this.isSideMenuOpened) {
                this.closeSideMenu();
            }

            document.body.classList.remove('minimized');

            this.getStorage().set('state', 'siteLayoutState', 'expanded');
        }  else {
            document.body.classList.add('minimized');

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

        return `${this.getBasePath()}?entryPoint=LogoImage&id=${companyLogoId}`;
    }

    /**
     * @return {(Object|string)[]}
     */
    getTabList() {
        const tabList = this.tabsHelper.getTabList();

        if (this.isSide()) {
            tabList.unshift('Home');
        }

        return tabList;
    }

    setup() {
        this.addHandler('click', 'a.action', (/** MouseEvent */event, target) => {
            let actionData;
            const name = target.dataset.name;

            if (name) {
                const item = this.menuDataList.find(it => it.name === name);

                if (item.handler && item.actionFunction) {
                    actionData = {
                        handler: item.handler,
                        actionFunction: item.actionFunction,
                    };
                }
            }

            Espo.Utils.handleAction(this, event, target, actionData);
        });

        this.getRouter().on('routed', (e) => {
            if (e.controller) {
                this.selectTab(e.controller);

                return;
            }

            this.selectTab(false);
        });

        /** @private */
        this.tabsHelper = new TabsHelper(
            this.getConfig(),
            this.getPreferences(),
            this.getUser(),
            this.getAcl(),
            this.getMetadata(),
            this.getLanguage()
        );

        const itemDefs = this.getMetadata().get('app.clientNavbar.items') || {};

        /** @type {string[]} */
        this.itemList = Object.keys(itemDefs)
            .filter(name => !itemDefs[name].disabled)
            .sort((name1, name2) => {
                const order1 = itemDefs[name1].order || 0;
                const order2 = itemDefs[name2].order || 0;

                return order1 - order2;
            });

        const setup = () => {
            this.setupTabDefsList();

            return Promise
                .all(this.itemList.map(item => this.createItemView(item)));
        };

        const update = () => {
            setup().then(() => this.reRender());
        };

        setup();

        this.listenTo(this.getHelper().settings, 'sync', () => update());
        this.listenTo(this.getHelper().language, 'sync', () => update());

        this.listenTo(this.getHelper().preferences, 'update', (/** string[] */attributeList) => {
            if (!attributeList) {
                return;
            }

            if (
                attributeList.includes('tabList') ||
                attributeList.includes('addCustomTabs') ||
                attributeList.includes('useCustomTabList')
            ) {
                update();
            }
        });


        this.once('remove', () => {
            $(window).off('resize.navbar');
            $(window).off('scroll.navbar');
            $(window).off('scroll.navbar-tab-group');

            document.body.classList.remove('has-navbar');
        });

        this.setupMenu();
    }

    getItemDataList() {
        const defsMap = {};

        this.itemList.forEach(name => {
            defsMap[name] = this.getItemDefs(name);
        });

        return this.itemList
            .filter(name => {
                const item = defsMap[name];

                if (!item) {
                    return false;
                }

                if (
                    item.accessDataList &&
                    !Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())
                ) {
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
     * @return {{
     *     view: string,
     *     class: string,
     *     accessDataList?: module:utils~AccessDefs[],
     * }}
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

    /**
     * @private
     */
    adjustTop() {
        const smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');
        const navbarHeight = this.getNavbarHeight();

        const $window = $(window);

        const $tabs = this.$tabs;
        const $more = this.$more;
        const $moreDropdown = this.$moreDropdown;


        $window.off('scroll.navbar');
        $window.off('resize.navbar');
        this.$moreDropdown.off('shown.bs.dropdown.navbar');
        this.off('show-more-tabs');

        $window.on('resize.navbar', () => updateWidth());

        $window.on('scroll.navbar', () => {
            if (!this.isMoreDropdownShown) {
                return;
            }

            $more.scrollTop($window.scrollTop());
        });

        this.$moreDropdown.on('shown.bs.dropdown.navbar', () => {
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

        $window.on('resize.navbar', () => updateMoreHeight());

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

        const navbarBaseWidth = this.navbarHeaderElement.clientWidth + this.$navbarRight.width();

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

    /**
     * @private
     */
    adjustSide() {
        const smallScreenWidth = this.getThemeManager().getParam('screenWidthXs');

        const $window = $(window);
        const $tabs = this.$tabs;
        const $more = this.$more;

        /** @type {HTMLElement} */
        const tabsElement = this.$tabs.get(0);

        /** @type {HTMLElement} */
        const moreElement = this.$more.get(0);

        this.adjustBodyMinHeightMethodName = 'adjustBodyMinHeightSide';

        if ($more.children().length === 0) {
            $more.parent().addClass('hidden');
        }

        $window.off('scroll.navbar');
        $window.off('resize.navbar');
        this.$moreDropdown.off('shown.bs.dropdown.navbar');
        this.off('show-more-tabs');

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

        this.$moreDropdown.on('shown.bs.dropdown.navbar', () => {
            $more.scrollTop($window.scrollTop());
        });

        this.on('show-more-tabs', () => {
            $more.scrollTop($window.scrollTop());
        });

        const updateSizeForSide = () => {
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;

            const navbarStaticItemsHeight = this.getStaticItemsHeight();

            this.$minimizer.removeClass('hidden');

            if (windowWidth < smallScreenWidth) {
                tabsElement.style.height = 'auto';

                if (moreElement) {
                    moreElement.style.maxHeight = '';
                }

                return;
            }

            tabsElement.style.height = (windowHeight - navbarStaticItemsHeight) + 'px';

            if (moreElement) {
                moreElement.style.maxHeight = windowHeight + 'px';
            }
        };

        $window.on('resize.navbar', () => {
            updateSizeForSide();
            this.adjustBodyMinHeight();
        });

        updateSizeForSide();
        this.adjustBodyMinHeight();
    }

    /**
     * @private
     * @return {number}
     */
    getNavbarHeight() {
        return this.getFontSizeFactor() * (this.getThemeManager().getParam('navbarHeight') || 43);
    }

    /**
     * @private
     * @return {boolean}
     */
    isSide() {
        return this.getThemeManager().getParam('navbar') === 'side';
    }

    /**
     * @private
     * @return {number}
     */
    getStaticItemsHeight() {
        return this.getFontSizeFactor() * (this.getThemeManager().getParam('navbarStaticItemsHeight') || 97);
    }

    /**
     * @private
     */
    getFontSizeFactor() {
        return this.getThemeManager().getFontSizeFactor();
    }

    /**
     * @private
     */
    adjustBodyMinHeight() {
        if (!this.adjustBodyMinHeightMethodName) {
            return;
        }

        this[this.adjustBodyMinHeightMethodName]();
    }

    /**
     * @private
     */
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

        document.body.style.minHeight = minHeight + 'px';
    }

    /**
     * @private
     */
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

        document.body.style.minHeight = minHeight + 'px';
    }

    afterRender() {
         this.$tabs = this.$el.find('ul.tabs');
        this.$more = this.$tabs.find('li.more > ul');
        this.$minimizer = this.$el.find('a.minimizer');

        document.body.classList.add('has-navbar');

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
            document.body.classList.add('minimized');
        }

        this.$navbar = this.$el.find('> .navbar');
        this.$navbarRightContainer = this.$navbar.find('> .navbar-body > .navbar-right-container');
        this.$navbarRight = this.$navbarRightContainer.children();

        this.navbarHeaderElement = this.element.querySelector('.navbar-header');

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

    /**
     * @private
     */
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

    /**
     * @param {string|false} name
     */
    selectTab(name) {
        const $tabs = this.$el.find('ul.tabs');

        $tabs.find('li.active').removeClass('active');

        if (name) {
            $tabs.find(`li[data-name="${name}"]`).addClass('active');
        }

        this.currentTab = name;

        const url = this.getRouter().getCurrentUrl();

        this.urlList
            .filter(item => url.startsWith(item.url))
            .forEach(item => {
                $tabs.find(`li[data-name="${item.name}"]`).addClass('active');
            });
    }

    /**
     * @private
     */
    setupTabDefsList() {
        /** @type {{url: string, name: string}[]} */
        this.urlList = [];

        const allTabList = this.getTabList();

        this.tabList = allTabList.filter((item, i) => {
            if (!item) {
                return false;
            }

            if (typeof item !== 'object') {
                return this.tabsHelper.checkTabAccess(item);
            }

            if (this.tabsHelper.isTabDivider(item)) {
                if (!this.isSide()) {
                    return false;
                }

                if (i === allTabList.length - 1) {
                    return false;
                }

                return true;
            }

            if (this.tabsHelper.isTabUrl(item)) {
                return this.tabsHelper.checkTabAccess(item);
            }

            /** @type {(Record|string)[]} */
            let itemList = (item.itemList || []).filter(item => {
                if (this.tabsHelper.isTabDivider(item)) {
                    return true;
                }

                return this.tabsHelper.checkTabAccess(item);
            });

            itemList = itemList.filter((item, i) => {
                if (!this.tabsHelper.isTabDivider(item)) {
                    return true;
                }

                const nextItem = itemList[i + 1];

                if (!nextItem) {
                    return true;
                }

                if (this.tabsHelper.isTabDivider(nextItem)) {
                    return false;
                }

                return true;
            });

            itemList = itemList.filter((item, i) => {
                if (!this.tabsHelper.isTabDivider(item)) {
                    return true;
                }

                if (i === 0 || i === itemList.length - 1) {
                    return false;
                }

                return true;
            });

            item.itemList = itemList;

            return !!itemList.length;
        });

        let moreIsMet = false;

        this.tabList = this.tabList.filter((item, i) => {
            const nextItem = this.tabList[i + 1];
            const prevItem = this.tabList[i - 1];

            if (this.tabsHelper.isTabMoreDelimiter(item)) {
                moreIsMet = true;
            }

            if (!this.tabsHelper.isTabDivider(item)) {
                return true;
            }

            if (!nextItem) {
                return true;
            }

            if (this.tabsHelper.isTabDivider(nextItem)) {
                return false;
            }

            if (this.tabsHelper.isTabDivider(prevItem) && this.tabsHelper.isTabMoreDelimiter(nextItem) && moreIsMet) {
                return false;
            }

            return true;
        });

        if (moreIsMet) {
            let end = this.tabList.length;

            for (let i = this.tabList.length - 1; i >= 0; i --) {
                const item = this.tabList[i];

                if (!this.tabsHelper.isTabDivider(item)) {
                    break;
                }

                end = this.tabList.length - 1;
            }

            this.tabList = this.tabList.slice(0, end);
        }

        const tabDefsList = [];

        const colorsDisabled =
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
            if (this.tabsHelper.isTabMoreDelimiter(tab)) {
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

    /**
     * @private
     * @param {{
     *     colorsDisabled: boolean,
     *     tabIconsDisabled: boolean,
     * }} params
     * @param {Record|string} tab
     * @param {number} i
     * @param {Object} vars
     * @return {{
     *     isAfterShowMore: boolean,
     *     isDivider: boolean,
     *     color: null,
     *     link: string,
     *     name: string,
     *     isInMore: boolean,
     *     shortLabel: string,
     *     label: string,
     *     isGroup: boolean,
     *     aClassName: string,
     *     iconClass: null
     * }}
     */
    prepareTabItemDefs(params, tab, i, vars) {
        let link;

        let iconClass = null;
        let color = null;
        let isGroup = false;
        let isDivider = false;
        let isUrl = false;
        let name = tab;
        let aClassName = 'nav-link';

        const label = this.tabsHelper.getTranslatedTabLabel(tab);

        if (tab === 'Home') {
            link = '#';
        } else if (this.tabsHelper.isTabDivider(tab)) {
            isDivider = true;

            aClassName = 'nav-divider-text';
            name = `divider-${i}`;
        } else if (this.tabsHelper.isTabUrl(tab)) {
            isUrl = true;

            name = `url-${i}`;
            link = tab.url || '#';
            color = tab.color;
            iconClass = tab.iconClass;

            this.urlList.push({name: name, url: link});
        } else if (this.tabsHelper.isTabGroup(tab)) {
            isGroup = true;

            color = tab.color;
            iconClass = tab.iconClass;

            name = `group-${i}`;

            link = null;

            aClassName = 'nav-link-group';
        } else {
            link = '#' + tab;
        }

        const shortLabel = label.substring(0, 2);

        if (!params.colorsDisabled && !isGroup && !isDivider && !isUrl) {
            color = this.getMetadata().get(['clientDefs', tab, 'color']);
        }

        if (
            color &&
            !/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color)
        ) {
            color = null;
        }

        if (!params.tabIconsDisabled && !isGroup && !isDivider && !isUrl) {
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
     * @property {string} [name]
     * @property {string} [html]
     * @property {string} [handler]
     * @property {string} [actionFunction]
     * @property {true} [divider]
     */

    /**
     * @private
     */
    setupMenu() {
        let avatarHtml = this.getHelper().getAvatarHtml(this.getUser().id, 'small', 20, 'avatar-link');

        if (avatarHtml) {
            avatarHtml += ' ';
        }

        /** @type {MenuDataItem[]} */
        this.menuDataList = [
            {
                link: `#User/view/${this.getUser().id}`,
                html: avatarHtml + this.getHelper().escapeString(this.getUser().get('name')),
            },
            {divider: true}
        ];

        /**
         * @type {Record<string, {
         *     order?: number,
         *     groupIndex?: number,
         *     link?: string,
         *     labelTranslation?: string,
         *     configCheck?: string,
         *     disabled:? boolean,
         *     handler?: string,
         *     actionFunction?: string,
         *     accessDataList?: module:utils~AccessDefs[],
         * }>} items
         */
        const items = this.getMetadata().get('app.clientNavbar.menuItems') || {};

        const nameList = Object.keys(items).sort((n1, n2) => {
            const o1 = items[n1].order;
            const o2 = items[n2].order;

            const g1 = items[n1].groupIndex;
            const g2 = items[n2].groupIndex;

            if (g2 === g1) {
                return o1 - o2;
            }

            return g1 - g2;
        });

        let currentGroup = 0;

        for (const name of nameList) {
            const item = items[name];

            if (item.groupIndex !== currentGroup) {
                currentGroup = item.groupIndex;

                this.menuDataList.push({divider: true});
            }

            if (item.disabled) {
                continue;
            }

            if (
                item.configCheck &&
                !Espo.Utils.checkActionAvailability(this.getHelper(), item)
            ) {
                continue;
            }

            if (
                item.accessDataList &&
                !Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())
            ) {
                continue;
            }

            this.menuDataList.push({
                name: name,
                link: item.link,
                label: this.getLanguage().translatePath(item.labelTranslation),
                handler: item.handler,
                actionFunction: item.actionFunction,
            });
        }
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
