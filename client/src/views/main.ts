/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import {inject} from 'di';
import ShortcutManager from 'helpers/site/shortcut-manager';
import type Model from 'model';
import type Collection from 'collection';
import type {AccessDefs} from 'utils';
import Utils from 'utils';

/**
 * A top-right menu item (button or dropdown action).
 * Handled by a class method `action{Action}`, a click handler or a handler class.
 */
export interface MenuItem {
    /**
     * A name.
     */
    name?: string | null;
    /**
     * An action.
     */
    action?: string | null;
    /**
     * A link.
     */
    link?: string;
    /**
     * A translatable label.
     */
    label?: string;
    /**
     * A label translation path.
     */
    labelTranslation?: string;
    /**
     * A style. Only for buttons.
     */
    style?: 'default' | 'danger' | 'success' | 'warning' | 'text';
    /**
     * Hidden.
     */
    hidden?: boolean;
    /**
     * Disabled.
     */
    disabled?: boolean;
    /**
     * Data attribute values.
     */
    data?: Record<string, string | number | boolean>;
    /**
     * A title.
     */
    title?: string;
    /**
     * An icon HTML.
     */
    iconHtml?: string;
    /**
     * An icon class.
     */
    iconClass?: string;
    /**
     * An HTML.
     */
    html?: string;
    /**
     * A text.
     */
    text?: string;
    /**
     * An additional class name. Only for buttons.
     */
    className?: string;
    /**
     * Access to a record (or a scope if `aclScope` specified) required for a menu item.
     */
    acl?: 'create' | 'read' | 'edit' | 'stream' | 'delete';
    /**
     * A scope to check access to with the `acl` parameter.
     */
    aclScope?: string;
    /**
     * A config parameter defining a menu item availability. If starts with `!`, then the result is negated.
     */
    configCheck?: string;
    /**
     * Access definitions.
     */
    accessDataList?: AccessDefs[];
    /**
     * A handler
     */
    handler?: string;
    /**
     * An init method in the handler.
     */
    initFunction?: string;
    /**
     * An action method in the handler.
     */
    actionFunction?: string;
    /**
     * A method in the handler that determine whether an item is available.
     */
    checkVisibilityFunction?: string;
    /**
     * A click handler.
     */
    onClick?: () => void;
    /**
     * An order index. Only for buttons. If not specified, 0 is implied.
     * @since 9.4.0
     */
    index?: number;
}

/**
 * Options.
 */
export interface MainViewOptions {
    /**
     * A scope.
     */
    scope?: string;
    /**
     * Parameters.
     */
    params?: Record<string, unknown> & {rootUrl?: string};
    /**
     * A root URL.
     */
    rootUrl?: string;
}

export interface MainViewSchema {
    model?: Model;
    collection?: Collection;
    options: Record<string, any> & MainViewOptions;
}

/**
 * A base main view. The detail, edit, list views to be extended from.
 */
class MainView<S extends MainViewSchema = MainViewSchema> extends View<S> {

    /**
     * A scope name.
     */
    scope: string = ''

    /**
     * A name.
     */
    readonly name: string = ''

    /**
     * Top-right menu definitions.
     *
     * @internal
     */
    private menu: {
        buttons: MenuItem[];
        dropdown: (MenuItem | false)[];
        actions: (MenuItem | false)[];
    }

    private $headerActionsContainer: JQuery

    /**
     * A shortcut-key => action map.
     */
    protected shortcutKeys: (Record<string, (event: KeyboardEvent) => void>) | null = null

    @inject(ShortcutManager)
    private shortcutManager: ShortcutManager

    /**
     * @internal
     */
    lastUrl: string | null = null

    private readonly headerActionItemTypeList: ('buttons' | 'dropdown' | 'actions')[] = [
        'buttons',
        'dropdown',
        'actions',
    ]

    private _menuHandlers: Record<string, any>

    /**
     * A root URL.
     */
    protected rootUrl: string | null = null

    private _reRenderHeaderOnSync = false

    readonly menuDisabled: boolean = false

    protected init() {
        this.scope = this.options.scope ?? this.scope;
        let menu: {
            buttons?: MenuItem[];
            dropdown?: (MenuItem | false)[];
            actions?: (MenuItem | false)[];
        } = {};

        this.options.params = this.options.params ?? {};

        if (this.name && this.scope) {
            const key = `clientDefs.${this.scope}.menu.${Utils.lowerCaseFirst(this.name)}`;

            menu = this.getMetadata().get(key) ?? {};
        }

        menu = Utils.cloneDeep(menu);

        let globalMenu = {} as any;

        if (this.name) {
            const key = `clientDefs.Global.menu.${Utils.lowerCaseFirst(this.name)}`;

            globalMenu = Utils.cloneDeep(this.getMetadata().get(key) ?? {});
        }

        this._reRenderHeaderOnSync = false;
        this._menuHandlers = {};

        this.headerActionItemTypeList.forEach(type => {
            let itemList = (menu[type] ?? []).concat(globalMenu[type] ?? []) as Record<string, any>[];

            if (type === 'buttons') {
                itemList = itemList.sort((a, b) => {
                    return (a.index ?? 0) - (b.index ?? 0);
                });
            }

            menu[type] = itemList;

            itemList.forEach(item => {
                const viewObject = this;

                // @todo Set _reRenderHeaderOnSync to true if `acl` is set `ascScope` is not set?
                //     Set _reRenderHeaderOnSync in `addMenuItem` method.

                if (
                    (item.initFunction || item.checkVisibilityFunction) &&
                    (item.handler || item.data && item.data.handler)
                ) {
                    this.wait(new Promise(resolve => {
                        const handler = item.handler || item.data.handler;

                        Espo.loader.require(handler, Handler => {
                            const handler = new Handler(viewObject);

                            const name = item.name || item.action;

                            if (name) {
                                this._menuHandlers[name] = handler;
                            }

                            if (item.initFunction) {
                                handler[item.initFunction].call(handler);
                            }

                            if (item.checkVisibilityFunction && this.model) {
                                this._reRenderHeaderOnSync = true;
                            }

                            resolve(undefined);
                        });
                    }));
                }
            });
        });

        this.menu = menu as any;

        if (this.model) {
            this.whenReady().then(() => {
                if (!this._reRenderHeaderOnSync) {
                    return;
                }

                this.listenTo(this.model as Model, 'sync', () => {
                    this.reRenderHeader();
                });
            });
        }

        this.updateLastUrl();

        this.on('after:render-internal', () => {
            this.$headerActionsContainer = this.$el.find('.page-header .header-buttons');
        });

        this.on('header-rendered', () => {
            this.$headerActionsContainer = this.$el.find('.page-header .header-buttons');

            this.adjustButtons();
        });

        this.on('after:render', () => this.adjustButtons());

        if (this.shortcutKeys) {
            this.shortcutKeys = Utils.cloneDeep(this.shortcutKeys);
        } else {
            this.shortcutKeys = {};
        }

        this.addHandler('click', '.action', (event, target) => {
            Utils.handleAction(this, event as MouseEvent, target, {
                actionItems: [...this.menu.buttons, ...this.menu.dropdown],
                className: 'main-header-manu-action',
            });
        });
    }

    private reRenderHeader() {
        this.getHeaderView()?.reRender({buffer: true});
    }

    private initShortcuts() {
        if (!this.shortcutKeys) {
            return;
        }

        this.shortcutManager.add(this, this.shortcutKeys);

        this.once('remove', () => {
            this.shortcutManager.remove(this);
        });
    }

    protected setupFinal() {
        this.initShortcuts();
    }

    /**
     * Update a last history URL.
     */
    updateLastUrl() {
        this.lastUrl = this.getRouter().getCurrentUrl();
    }

    /**
     * @internal
     */
    getMenu(): {
        buttons?: MenuItem[];
        dropdown?: MenuItem | false;
        actions?: MenuItem[];
    } {
        if (this.menuDisabled || !this.menu) {
            return {};
        }

        const menu = {} as any;

        this.headerActionItemTypeList.forEach(type => {
            (this.menu[type] || []).forEach(item => {
                if (item === false) {
                    menu[type].push(false);

                    return;
                }

                item = Utils.clone(item);

                menu[type] = menu[type] || [];

                if (!Utils.checkActionAvailability(this.getHelper(), item)) {
                    return;
                }

                if (!Utils.checkActionAccess(this.getAcl(), this.model || this.scope, item)) {
                    return;
                }

                if (item.accessDataList) {
                    if (!Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                        return;
                    }
                }

                item.name = item.name ?? item.action ?? null;
                item.action = item.action ?? null;

                if (item.name && this._menuHandlers[item.name] && item.checkVisibilityFunction) {
                    const handler = this._menuHandlers[item.name];

                    if (!handler[item.checkVisibilityFunction](item.name)) {
                        return;
                    }
                }

                if (item.labelTranslation) {
                    item.html = this.getHelper().escapeString(
                        this.getLanguage().translatePath(item.labelTranslation)
                    );
                }

                menu[type].push(item);
            });
        });

        return menu;
    }

    /**
     * Get a header HTML. To be overridden.
     *
     * @returns HTML.
     */
    getHeader(): string {
        return '';
    }

    /**
     * Build a header HTML. To be called from the #getHeader method.
     * Beware of XSS.
     *
     * @param itemList A breadcrumb path. Like: Account > Name > edit.
     * @returns HTML
     */
    protected buildHeaderHtml(
        itemList: (string | Element | JQuery)[],
    ): string {

        const $itemList = itemList.map(item => {
            return $('<div>')
                .addClass('breadcrumb-item')
                .append(item);
        });

        const $div = $('<div>')
            .addClass('header-breadcrumbs');

        $itemList.forEach(($item, i) => {
            $div.append($item);

            if (i === $itemList.length - 1) {
                return;
            }

            $div.append(
                $('<div>')
                    .addClass('breadcrumb-separator')
                    .append(
                        $('<span>')
                    )
            )
        });

        return $div.get(0)?.outerHTML as string;
    }


    /**
     * Get an icon HTML.
     *
     * @returns HTML
     */
    protected getHeaderIconHtml(): string {
        return this.getHelper().getScopeColorIconHtml(this.scope);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'showModal'.
     *
     * @internal
     */
    async actionShowModal(data: Record<string, any>) {
        const viewName = data.view;

        if (!viewName) {
            return;
        }

        const view = await this.createView('modal', viewName, {
            model: this.model,
            collection: this.collection,
        });

        this.listenTo(view, 'after:save', () => {
            if (this.model) {
                this.model.fetch();
            }

            if (this.collection) {
                this.collection.fetch();
            }
        });

        await view.render();
    }

    /**
     * Update a menu item.
     *
     * @param  name An item name.
     * @param item New item definitions to write.
     * @param doNotReRender Skip re-render.
     *
     * @since 8.2.0
     */
    updateMenuItem(name: string, item: Partial<MenuItem>, doNotReRender: boolean = false) {
        const actionItem = this._getHeaderActionItem(name);

        if (!actionItem) {
            return;
        }

        for (const key in item) {
            (actionItem as any)[key] = (item as any)[key];
        }

        if (doNotReRender) {
            return;
        }

        this.reRenderHeader();
    }

    /**
     * Add a menu item.
     *
     * @param type A type.
     * @param item Item definitions.
     * @param toBeginning To beginning.
     * @param doNotReRender Skip re-render.
     */
    addMenuItem(
        type: 'buttons' | 'dropdown',
        item: MenuItem | false,
        toBeginning: boolean = false,
        doNotReRender: boolean = false,
    ) {
        const list = this.menu[type];

        if (item) {
            item.name = item.name ?? item.action ?? Utils.generateId();
            const name = item.name;

            const index = list.findIndex(it => (it || {}).name === name);

            if (~index) {
                list.splice(index, 1);
            }
        }

        if (type === 'buttons' && item) {
            const itemIndex = item.index ?? 0;

            if (toBeginning) {
                const index = list.findIndex(it => ((it || {}).index ?? 0) >= itemIndex);

                if (index === -1) {
                    itemIndex < ((list[list.length - 1] as MenuItem)?.index ?? 0) ?
                        list.unshift(item) :
                        list.push(item);
                } else {
                    list.splice(index, 0, item);
                }
            } else {
                const index = list.length -
                    list.slice().reverse().findIndex(it => ((it || {}).index ?? 0) <= itemIndex);

                if (index === list.length + 1) {
                    itemIndex < ((list[0] as MenuItem)?.index ?? 0) ?
                        list.unshift(item) :
                        list.push(item);
                } else {
                    list.splice(index, 0, item);
                }
            }
        }

        if (type !== 'buttons') {
            const list = this.menu[type]

            toBeginning ?
                list.unshift(item) :
                list.push(item);
        }

        if (doNotReRender) {
            return;
        }

        this.reRenderHeader();
    }

    /**
     * Remove a menu item.
     *
     * @param name An item name.
     * @param doNotReRender Skip re-render.
     */
    removeMenuItem(name: string, doNotReRender: boolean) {
        let index = -1;
        let type: 'buttons' | 'dropdown' | 'actions' | false = false;

        this.headerActionItemTypeList.forEach(t => {
            (this.menu[t] ?? []).forEach((item, i) => {
                item = item || {};

                if (item.name === name) {
                    index = i;
                    type = t;
                }
            });
        });

        if (~index && type) {
            const items = this.menu[type] as any[];

            items.splice(index, 1);
        }

        if (!doNotReRender) {
            this.reRenderHeader();

            return;
        }

        if (doNotReRender && this.isRendered()) {
            this.$headerActionsContainer.find('[data-name="' + name + '"]').remove();
        }
    }

    /**
     * Disable a menu item.
     *
     * @param name A name.
     */
    disableMenuItem(name: string) {
        const item = this._getHeaderActionItem(name);

        if (item) {
            item.disabled = true;
        }

        const process = () => {
            this.$headerActionsContainer
                .find(`[data-name="${name}"]`)
                .addClass('disabled')
                .attr('disabled');
        };

        if (this.isBeingRendered()) {
            this.whenRendered().then(() => process());

            return;
        }

        if (!this.isRendered()) {
            return;
        }

        process();
    }

    /**
     * Enable a menu item.
     *
     * @param name A name.
     */
    enableMenuItem(name: string) {
        const item = this._getHeaderActionItem(name);

        if (item) {
            item.disabled = false;
        }

        const process = () => {
            this.$headerActionsContainer
                .find(`[data-name="${name}"]`)
                .removeClass('disabled')
                .removeAttr('disabled');
        };

        if (this.isBeingRendered()) {
            this.whenRendered().then(() => process());

            return;
        }

        if (!this.isRendered()) {
            return;
        }

        process();
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'navigateToRoot'.
     *
     * @param data
     * @param event
     */
    actionNavigateToRoot(data: Record<string, unknown>, event: MouseEvent) {
        // noinspection BadExpressionStatementJS
        data;

        event.stopPropagation();

        this.getRouter().checkConfirmLeaveOut(() => {
            const rootUrl = this.rootUrl ??
                this.options.rootUrl ??
                this.options.params?.rootUrl ??
                `#${this.scope}`;

            this.getRouter().navigate(rootUrl, {trigger: true, isReturn: true});
        });
    }

    private _getHeaderActionItem(name: string): MenuItem | undefined {
        for (const type of this.headerActionItemTypeList) {
            if (!this.menu[type]) {
                continue;
            }

            for (const item of this.menu[type]) {
                if (item && item.name === name) {
                    return item;
                }
            }
        }

        return undefined;
    }

    /**
     * Hide a menu item.
     *
     * @param name A name.
     */
    hideHeaderActionItem(name: string) {
        const item = this._getHeaderActionItem(name);

        if (item) {
            item.hidden = true;
        }

        if (!this.isRendered()) {
            return;
        }

        this.$headerActionsContainer.find(`li > .action[data-name="${name}"]`).parent().addClass('hidden');
        this.$headerActionsContainer.find(`a.action[data-name="${name}"]`).addClass('hidden');

        this.controlMenuDropdownVisibility();
        this.adjustButtons();

        this.getHeaderView()?.trigger('action-item-update');
    }

    /**
     * Show a hidden menu item.
     *
     * @param name A name.
     */
    showHeaderActionItem(name: string) {
        const item = this._getHeaderActionItem(name);

        if (item) {
            item.hidden = false;
        }

        const processUi = () => {
            const $dropdownItem = this.$headerActionsContainer.find(`li > .action[data-name="${name}"]`).parent();
            const $button = this.$headerActionsContainer.find(`a.action[data-name="${name}"]`);

            // Item can be available but not rendered as it was skipped by access check in getMenu.
            if (item && !$dropdownItem.length && !$button.length) {
                this.reRenderHeader();

                return;
            }

            $dropdownItem.removeClass('hidden');
            $button.removeClass('hidden');

            this.controlMenuDropdownVisibility();
            this.adjustButtons();

            this.getHeaderView()?.trigger('action-item-update');
        };

        if (!this.isRendered()) {
            if (this.isBeingRendered()) {
                this.whenRendered().then(() => processUi());
            }

            return;
        }

        processUi();
    }

    /**
     * Whether a menu has any non-hidden dropdown items.
     */
    private hasMenuVisibleDropdownItems(): boolean {
        let hasItems = false;

        (this.menu.dropdown || []).forEach(item => {
            if (item && !item.hidden) {
                hasItems = true;
            }
        });

        return hasItems;
    }

    private controlMenuDropdownVisibility() {
        const $group = this.$headerActionsContainer.find('.dropdown-group');

        if (this.hasMenuVisibleDropdownItems()) {
            $group.removeClass('hidden');
            $group.find('> button').removeClass('hidden');

            return;
        }

        $group.addClass('hidden');
        $group.find('> button').addClass('hidden');
    }

    protected getHeaderView(): import('views/header').default {
        return this.getView('header') as import('views/header').default;
    }

    private adjustButtons() {
        const container = this.$headerActionsContainer.get(0);

        const nodes = container?.querySelectorAll<HTMLElement>('.btn');

        if (!nodes) {
            return;
        }

        let buttons = [...nodes];

        if (!buttons) {
            return;
        }

        for (const it of buttons) {
            it.classList.remove('radius-left', 'radius-right');
        }

        buttons = buttons.filter(it => !it.classList.contains('hidden'));

        for (const [i, it] of buttons.entries()) {
            if (i === 0 || buttons[i - 1].classList.contains('btn-text')) {
                it.classList.add('radius-left');
            }

            if (i === buttons.length - 1 || buttons[i + 1].classList.contains('btn-text')) {
                it.classList.add('radius-right');
            }
        }
    }

    /**
     * Called when a stored view is reused (by the controller).
     *
     * @param params Routing params.
     */
    setupReuse(params: Record<string, unknown>) {
        // noinspection BadExpressionStatementJS
        params;

        this.initShortcuts();
    }

    updatePageTitle() {}
}

export default MainView;
