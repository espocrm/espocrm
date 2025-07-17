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

/** @module views/main */

import View from 'view';
import {inject} from 'di';
import ShortcutManager from 'helpers/site/shortcut-manager';

/**
 * A base main view. The detail, edit, list views to be extended from.
 */
class MainView extends View {

    /**
     * A scope name.
     *
     * @type {string} scope
     */
    scope = ''

    /**
     * A name.
     *
     * @type {string} name
     */
    name = ''

    /**
     * A top-right menu item (button or dropdown action).
     * Handled by a class method `action{Action}`, a click handler or a handler class.
     *
     * @typedef {Object} module:views/main~MenuItem
     *
     * @property {string} [name] A name.
     * @property {string} [action] An action.
     * @property {string} [link] A link.
     * @property {string} [label] A translatable label.
     * @property {string} [labelTranslation] A label translation path.
     * @property {'default'|'danger'|'success'|'warning'} [style] A style. Only for buttons.
     * @property {boolean} [hidden] Hidden.
     * @property {boolean} [disabled] Disabled.
     * @property {Object.<string,string|number|boolean>} [data] Data attribute values.
     * @property {string} [title] A title.
     * @property {string} [iconHtml] An icon HTML.
     * @property {string} [iconClass] An icon class.
     * @property {string} [html] An HTML.
     * @property {string} [text] A text.
     * @property {string} [className] An additional class name. Only for buttons.
     * @property {'create'|'read'|'edit'|'stream'|'delete'} [acl] Access to a record (or a scope if `aclScope` specified)
     *   required for a menu item.
     * @property {string} [aclScope] A scope to check access to with the `acl` parameter.
     * @property {string} [configCheck] A config parameter defining a menu item availability.
     *   If starts with `!`, then the result is negated.
     * @property {module:utils~AccessDefs[]} [accessDataList] Access definitions.
     * @property {string} [handler] A handler.
     * @property {string} [initFunction] An init method in the handler.
     * @property {string} [actionFunction] An action method in the handler.
     * @property {string} [checkVisibilityFunction] A method in the handler that determine whether an item is available.
     * @property {function()} [onClick] A click handler.
     */

    /**
     * Top-right menu definitions.
     *
     * @type {{
     *     buttons: module:views/main~MenuItem[],
     *     dropdown: module:views/main~MenuItem[],
     *     actions: module:views/main~MenuItem[],
     * }} menu
     * @private
     * @internal
     */
    menu = {}

    /**
     * @private
     * @type {JQuery|null}
     */
    $headerActionsContainer = null

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string, string|function (KeyboardEvent): void>}
     */
    shortcutKeys = null

    /**
     * @private
     * @type {ShortcutManager}
     */
    @inject(ShortcutManager)
    shortcutManager

    /** @inheritDoc */
    events = {
        /** @this MainView */
        'click .action': function (e) {
            Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
                actionItems: [...this.menu.buttons, ...this.menu.dropdown],
                className: 'main-header-manu-action',
            });
        },
    }

    lastUrl

    /** @inheritDoc */
    init() {
        this.scope = this.options.scope || this.scope;
        this.menu = {};

        this.options.params = this.options.params || {};

        if (this.name && this.scope) {
            const key = this.name.charAt(0).toLowerCase() + this.name.slice(1);

            this.menu = this.getMetadata().get(['clientDefs', this.scope, 'menu', key]) || {};
        }

        /**
         * @private
         * @type {string[]}
         */
        this.headerActionItemTypeList = ['buttons', 'dropdown', 'actions'];

        this.menu = Espo.Utils.cloneDeep(this.menu);

        let globalMenu = {};

        if (this.name) {
            globalMenu = Espo.Utils.cloneDeep(
                this.getMetadata()
                    .get(['clientDefs', 'Global', 'menu',
                        this.name.charAt(0).toLowerCase() + this.name.slice(1)]) || {}
            );
        }

        this._reRenderHeaderOnSync = false;

        this._menuHandlers = {};

        this.headerActionItemTypeList.forEach(type => {
            this.menu[type] = this.menu[type] || [];
            this.menu[type] = this.menu[type].concat(globalMenu[type] || []);

            const itemList = this.menu[type];

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

                            resolve();
                        });
                    }));
                }
            });
        });

        if (this.model) {
            this.whenReady().then(() => {
                if (!this._reRenderHeaderOnSync) {
                    return;
                }

                this.listenTo(this.model, 'sync', () => {
                    if (!this.getHeaderView()) {
                        return;
                    }

                    this.getHeaderView().reRender();
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
            this.shortcutKeys = Espo.Utils.cloneDeep(this.shortcutKeys);
        }
    }

    /**
     * @private
     */
    initShortcuts() {
        if (!this.shortcutKeys) {
            return;
        }

        this.shortcutManager.add(this, this.shortcutKeys);

        this.once('remove', () => {
            this.shortcutManager.remove(this);
        });
    }

    setupFinal() {
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
     * @returns {{
     *     buttons?: module:views/main~MenuItem[],
     *     dropdown?: module:views/main~MenuItem[],
     *     actions?: module:views/main~MenuItem[],
     * }}
     */
    getMenu() {
        if (this.menuDisabled || !this.menu) {
            return {};
        }

        const menu = {};

        this.headerActionItemTypeList.forEach(type => {
            (this.menu[type] || []).forEach(item => {
                if (item === false) {
                    menu[type].push(false);

                    return;
                }

                item = Espo.Utils.clone(item);

                menu[type] = menu[type] || [];

                if (!Espo.Utils.checkActionAvailability(this.getHelper(), item)) {
                    return;
                }

                if (!Espo.Utils.checkActionAccess(this.getAcl(), this.model || this.scope, item)) {
                    return;
                }

                if (item.accessDataList) {
                    if (!Espo.Utils
                        .checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())
                    ) {
                        return;
                    }
                }

                item.name = item.name || item.action;
                item.action = item.action || null;

                if (this._menuHandlers[item.name] && item.checkVisibilityFunction) {
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
     * @returns {string} HTML.
     */
    getHeader() {
        return '';
    }

    /**
     * Build a header HTML. To be called from the #getHeader method.
     * Beware of XSS.
     *
     * @param {(string|Element|JQuery)[]} itemList A breadcrumb path. Like: Account > Name > edit.
     * @returns {string} HTML
     */
    buildHeaderHtml(itemList) {
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

        return $div.get(0).outerHTML;
    }


    /**
     * Get an icon HTML.
     *
     * @returns {string} HTML
     */
    getHeaderIconHtml() {
        return this.getHelper().getScopeColorIconHtml(this.scope);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'showModal'.
     *
     * @todo Revise. To be removed?
     *
     * @param {Object} data
     */
    actionShowModal(data) {
        const view = data.view;

        if (!view) {
            return;
        }

        this.createView('modal', view, {
            model: this.model,
            collection: this.collection,
        }, view => {
            view.render();

            this.listenTo(view, 'after:save', () => {
                if (this.model) {
                    this.model.fetch();
                }

                if (this.collection) {
                    this.collection.fetch();
                }
            });
        });
    }

    /**
     * Update a menu item.
     *
     * @param {string} name An item name.
     * @param {module:views/main~MenuItem} item New item definitions to write.
     * @param {boolean} [doNotReRender=false] Skip re-render.
     *
     * @since 8.2.0
     */
    updateMenuItem(name, item, doNotReRender) {
        const actionItem = this._getHeaderActionItem(name);

        if (!actionItem) {
            return;
        }

        for (const key in item) {
            actionItem[key] = item[key];
        }

        if (doNotReRender) {
            return;
        }

        if (this.isRendered()) {
            this.getHeaderView().reRender();

            return;
        }

        if (this.isBeingRendered()) {
            this.whenRendered().then(() => {
                this.getHeaderView().reRender();
            })
        }
    }

    /**
     * Add a menu item.
     *
     * @param {'buttons'|'dropdown'} type A type.
     * @param {module:views/main~MenuItem|false} item Item definitions.
     * @param {boolean} [toBeginning=false] To beginning.
     * @param {boolean} [doNotReRender=false] Skip re-render.
     */
    addMenuItem(type, item, toBeginning, doNotReRender) {
        if (item) {
            item.name = item.name || item.action || Espo.Utils.generateId();

            const name = item.name;

            let index = -1;

            this.menu[type].forEach((data, i) => {
                data = data || {};

                if (data.name === name) {
                    index = i;
                }
            });

            if (~index) {
                this.menu[type].splice(index, 1);
            }
        }

        let method = 'push';

        if (toBeginning) {
            method  = 'unshift';
        }

        this.menu[type][method](item);

        if (!doNotReRender && this.isRendered()) {
            this.getHeaderView().reRender();

            return;
        }

        if (!doNotReRender && this.isBeingRendered()) {
            this.once('after:render', () => {
                this.getHeaderView().reRender();
            });
        }
    }

    /**
     * Remove a menu item.
     *
     * @param {string} name An item name.
     * @param {boolean} [doNotReRender] Skip re-render.
     */
    removeMenuItem(name, doNotReRender) {
        let index = -1;
        let type = false;

        this.headerActionItemTypeList.forEach(t => {
            (this.menu[t] || []).forEach((item, i) => {
                item = item || {};

                if (item.name === name) {
                    index = i;
                    type = t;
                }
            });
        });

        if (~index && type) {
            this.menu[type].splice(index, 1);
        }

        if (!doNotReRender && this.isRendered()) {
            this.getHeaderView().reRender();

            return;
        }

        if (!doNotReRender && this.isBeingRendered()) {
            this.once('after:render', () => {
                this.getHeaderView().reRender();

            });

            return;
        }

        if (doNotReRender && this.isRendered()) {
            this.$headerActionsContainer.find('[data-name="' + name + '"]').remove();
        }
    }

    /**
     * Disable a menu item.
     *
     * @param {string} name A name.
     */
    disableMenuItem(name) {
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
     * @param {string} name A name.
     */
    enableMenuItem(name) {
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
     * @param {Object} data
     * @param {MouseEvent} event
     */
    actionNavigateToRoot(data, event) {
        event.stopPropagation();

        this.getRouter().checkConfirmLeaveOut(() => {
            const rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            this.getRouter().navigate(rootUrl, {trigger: true, isReturn: true});
        });
    }

    /**
     * @private
     * @param {string} name
     * @return {module:views/main~MenuItem|undefined}
     */
    _getHeaderActionItem(name) {
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
     * @param {string} name A name.
     */
    hideHeaderActionItem(name) {
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

        if (this.getHeaderView()) {
            this.getHeaderView().trigger('action-item-update');
        }
    }

    /**
     * Show a hidden menu item.
     *
     * @param {string} name A name.
     */
    showHeaderActionItem(name) {
        const item = this._getHeaderActionItem(name);

        if (item) {
            item.hidden = false;
        }

        const processUi = () => {
            const $dropdownItem = this.$headerActionsContainer.find(`li > .action[data-name="${name}"]`).parent();
            const $button = this.$headerActionsContainer.find(`a.action[data-name="${name}"]`);

            // Item can be available but not rendered as it was skipped by access check in getMenu.
            if (item && !$dropdownItem.length && !$button.length) {
                if (this.getHeaderView()) {
                    this.getHeaderView().reRender();
                }

                return;
            }

            $dropdownItem.removeClass('hidden');
            $button.removeClass('hidden');

            this.controlMenuDropdownVisibility();
            this.adjustButtons();

            if (this.getHeaderView()) {
                this.getHeaderView().trigger('action-item-update');
            }
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
     *
     * @private
     * @returns {boolean}
     */
    hasMenuVisibleDropdownItems() {
        let hasItems = false;

        (this.menu.dropdown || []).forEach(item => {
            if (!item.hidden) {
                hasItems = true;
            }
        });

        return hasItems;
    }

    /**
     * @private
     */
    controlMenuDropdownVisibility() {
        const $group = this.$headerActionsContainer.find('.dropdown-group');

        if (this.hasMenuVisibleDropdownItems()) {
            $group.removeClass('hidden');
            $group.find('> button').removeClass('hidden');

            return;
        }

        $group.addClass('hidden');
        $group.find('> button').addClass('hidden');
    }

    /**
     * @protected
     * @return {module:views/header}
     */
    getHeaderView() {
        return this.getView('header');
    }

    /**
     * @private
     */
    adjustButtons() {
        const $buttons = this.$headerActionsContainer.find('.btn');

        $buttons
            .removeClass('radius-left')
            .removeClass('radius-right');

        const $buttonsVisible = $buttons.filter(':not(.hidden)');

        $buttonsVisible.first().addClass('radius-left');
        $buttonsVisible.last().addClass('radius-right');
    }

    /**
     * Called when a stored view is reused (by the controller).
     *
     * @public
     * @param {Object.<string, *>} params Routing params.
     */
    setupReuse(params) {
        this.initShortcuts();
    }
}

export default MainView;
