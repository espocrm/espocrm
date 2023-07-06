/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/main */

import View from 'view';

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
     * Handled by a class method `action{Action}`.
     *
     * @typedef {Object} module:views/main~MenuItem
     *
     * @property {string} name A name.
     * @property {string} [action] An action.
     * @property {string} [link] A link.
     * @property {string} [label] A translatable label.
     * @property {string} [labelTranslation] A label translation path.
     * @property {'default'|'danger'|'success'|'warning'} [style] A style. Only for buttons.
     * @property {boolean} [hidden]
     * @property {Object.<string,string|number|boolean>} [data] Data attribute values.
     * @property {string} [title] A title.
     * @property {string} [iconHtml] An icon HTML.
     * @property {string} [html] An HTML.
     * @property {string} [text] A text.
     * @property {string} [className] An additional class name. Only for buttons.
     * @property {'read'|'edit'|'stream'|'delete'} [acl] Access to a record (or a scope if `aclScope` specified)
     *   required for a menu item.
     * @property {string} [aclScope] A scope to check access to with the `acl` parameter.
     * @property {string} [configCheck] A config parameter defining a menu item availability.
     *   If starts with `!`, then the result is negated.
     * @property {module:utils~AccessDefs[]} [accessDataList] Access definitions.
     * @property {string} [initFunction] An init function.
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
     * @type {?Object.<string,string|function (JQueryKeyEventObject): void>}
     */
    shortcutKeys = null

    /** @inheritDoc */
    events = {
        /** @this MainView */
        'click .action': function (e) {
            Espo.Utils.handleAction(this, e);
        },
    }

    lastUrl

    /** @inheritDoc */
    init() {
        this.scope = this.options.scope || this.scope;
        this.menu = {};

        this.options.params = this.options.params || {};

        if (this.name && this.scope) {
            let key = this.name.charAt(0).toLowerCase() + this.name.slice(1);

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

        this.headerActionItemTypeList.forEach(type => {
            this.menu[type] = this.menu[type] || [];
            this.menu[type] = this.menu[type].concat(globalMenu[type] || []);

            let itemList = this.menu[type];

            itemList.forEach(item => {
                let viewObject = this;

                if (item.initFunction && item.data.handler) {
                    this.wait(new Promise(resolve => {
                        Espo.loader.require(item.data.handler, Handler => {
                            let handler = new Handler(viewObject);

                            handler[item.initFunction].call(handler);

                            resolve();
                        });
                    }));
                }
            });
        });

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

    setupFinal() {
        if (this.shortcutKeys) {
            this.events['keydown.main'] = e => {
                let key = Espo.Utils.getKeyFromKeyEvent(e);

                if (typeof this.shortcutKeys[key] === 'function') {
                    this.shortcutKeys[key].call(this, e.originalEvent);

                    return;
                }

                let actionName = this.shortcutKeys[key];

                if (!actionName) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                let methodName = 'action' + Espo.Utils.upperCaseFirst(actionName);

                this[methodName]();
            };
        }
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

        let menu = {};

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
        let $itemList = itemList.map(item => {
            return $('<div>')
                .addClass('breadcrumb-item')
                .append(item);
        });

        let $div = $('<div>')
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
                        $('<span>').addClass('chevron-right')
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
        let view = data.view;

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
     * Add a menu item.
     *
     * @param {'buttons'|'dropdown'|'actions'} type A type.
     * @param {module:views/main~MenuItem|false} item Item definitions.
     * @param {boolean} [toBeginning=false] To beginning.
     * @param {boolean} [doNotReRender=false] Skip re-render.
     */
    addMenuItem(type, item, toBeginning, doNotReRender) {
        if (item) {
            item.name = item.name || item.action;

            let name = item.name;

            if (name) {
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
        this.$headerActionsContainer
            .find('[data-name="' + name + '"]')
            .addClass('disabled')
            .attr('disabled');
    }

    /**
     * Enable a menu item.
     *
     * @param {string} name A name.
     */
    enableMenuItem(name) {
        this.$headerActionsContainer
            .find('[data-name="' + name + '"]')
            .removeClass('disabled')
            .removeAttr('disabled');
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'navigateToRoot'.
     *
     * @param {Object} data
     * @param {jQuery.Event} e
     */
    actionNavigateToRoot(data, e) {
        e.stopPropagation();

        this.getRouter().checkConfirmLeaveOut(() => {
            let options = {
                isReturn: true,
            };

            let rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            this.getRouter().navigate(rootUrl, {trigger: false});
            this.getRouter().dispatch(this.scope, null, options);
        });
    }

    /**
     * Hide a menu item.
     *
     * @param {string} name A name.
     */
    hideHeaderActionItem(name) {
        this.headerActionItemTypeList.forEach(t => {
            (this.menu[t] || []).forEach(item => {
                item = item || {};

                if (item.name === name) {
                    item.hidden = true;
                }
            });
        });

        if (!this.isRendered()) {
            return;
        }

        this.$headerActionsContainer.find('li > .action[data-name="'+name+'"]').parent().addClass('hidden');
        this.$headerActionsContainer.find('a.action[data-name="'+name+'"]').addClass('hidden');

        this.controlMenuDropdownVisibility();
        this.adjustButtons();
    }

    /**
     * Show a hidden menu item.
     *
     * @param {string} name A name.
     */
    showHeaderActionItem(name) {
        this.headerActionItemTypeList.forEach(t => {
            (this.menu[t] || []).forEach(item => {
                item = item || {};

                if (item.name === name) {
                    item.hidden = false;
                }
            });
        });

        let processUi = () => {
            this.$headerActionsContainer.find('li > .action[data-name="'+name+'"]').parent().removeClass('hidden');
            this.$headerActionsContainer.find('a.action[data-name="'+name+'"]').removeClass('hidden');

            this.controlMenuDropdownVisibility();
            this.adjustButtons();
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
        let $group = this.$headerActionsContainer.find('.dropdown-group');

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
        let $buttons = this.$headerActionsContainer.find('.btn');

        $buttons
            .removeClass('radius-left')
            .removeClass('radius-right');

        let $buttonsVisible = $buttons.filter(':not(.hidden)');

        $buttonsVisible.first().addClass('radius-left');
        $buttonsVisible.last().addClass('radius-right');
    }

    /**
     * Called when a stored view is reused (by the controller).
     *
     * @public
     * @param {Object.<string, *>} params Routing params.
     */
    setupReuse(params) {}
}

export default MainView;
