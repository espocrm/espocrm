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

/** @module views/modal */

import View from 'view';
import {inject} from 'di';
import ModalBarProvider from 'helpers/site/modal-bar-provider';
import ShortcutManager from 'helpers/site/shortcut-manager';

/**
 * A base modal view. Can be extended or used directly.
 *
 * @see https://docs.espocrm.com/development/modal/
 */
class ModalView extends View {

    /**
     * A button or dropdown action item.
     *
     * @typedef {Object} module:views/modal~Button
     *
     * @property {string} name A name.
     * @property {string} [label] A label. To be translated
     *   (with a scope defined in the `scope` class property).
     * @property {string} [text] A text (not translated).
     * @property {string} [labelTranslation] A label translation path.
     * @property {string} [html] HTML.
     * @property {boolean} [pullLeft=false] Deprecated. Use the `position` property.
     * @property {'left'|'right'} [position='left'] A position.
     * @property {'default'|'danger'|'success'|'warning'|'info'} [style='default'] A style.
     * @property {boolean} [hidden=false] Is hidden.
     * @property {boolean} [disabled=false] Disabled.
     * @property {function(module:ui.Dialog): void} [onClick] Called on click. If not defined, then
     * the `action<Name>` class method will be called.
     * @property {string} [className] An additional class name.
     * @property {string} [title] A title text.
     * @property {'primary'|'danger'|'success'|'warning'|'text'} [style] A style.
     * @property {string} [iconHtml] An icon HTML.
     * @property {string} [iconClass] An icon class.
     * @property {number} [groupIndex] A group index. Only for the dropdown.
     */

    /**
     * @typedef {Object} module:views/modal~Options
     * @property {string} [headerText] A header text.
     * @property {HTMLElement} [headerElement] A header element.
     * @property {'static'|boolean} [backdrop] A backdrop.
     * @property {module:views/modal~Button} [buttonList] Buttons.
     * @property {module:views/modal~Button} [dropdownItemList] Buttons.
     * @property {boolean} [collapseDisabled] Not collapsible. As of v9.1.0.
     */

    /**
     * @param {module:views/modal~Options | Record} [options] Options.
     */
    constructor(options) {
        super(options);
    }

    /**
     * A CSS name.
     *
     * @protected
     */
    cssName = 'modal-dialog'

    /**
     * A class-name. Use `'dialog dialog-record'` for modals containing a record form.
     *
     * @protected
     */
    className = 'dialog'

    /**
     * @protected
     * @deprecated Use `headerHtml`
     */
    header

    /**
     * A header HTML. Beware of XSS.
     *
     * @protected
     * @type {string|null}
     */
    headerHtml

    /**
     * A header JQuery instance.
     *
     * @protected
     * @type {JQuery}
     */
    $header

    /**
     * A header element.
     *
     * @protected
     * @type {Element}
     */
    headerElement

    /**
     * A header text.
     *
     * @protected
     * @type {string}
     */
    headerText

    /**
     * A dialog instance.
     *
     * @protected
     * @type {Espo.Ui.Dialog}
     */
    dialog

    /**
     * A container selector.
     *
     * @protected
     * @type {string}
     */
    containerSelector = ''

    /**
     * A scope name. Used when translating button labels.
     *
     * @type {string|null}
     */
    scope = null

    /**
     * A backdrop.
     *
     * @protected
     * @type {'static'|boolean}
     */
    backdrop = 'static'

    /**
     * Buttons.
     *
     * @protected
     * @type {module:views/modal~Button[]}
     */
    buttonList = []

    /**
     * Dropdown action items.
     *
     * @protected
     * @type {Array<module:views/modal~Button|false>}
     */
    dropdownItemList = []

    /**
     * @deprecated Use `buttonList`.
     * @protected
     * @todo Remove.
     */
    buttons = []

    /**
     * A width. Do not use.
     * @todo Consider removing.
     *
     * @protected
     * @type {number|null}
     */
    width = null

    /**
     * Not used.
     *
     * @deprecated
     */
    fitHeight = false

    /**
     * To disable fitting to a window height.
     *
     * @protected
     * @type {boolean}
     */
    noFullHeight = false

    /**
     * Disable the ability to close by pressing the `Esc` key.
     *
     * @protected
     * @type {boolean}
     */
    escapeDisabled = false

    /**
     * Is draggable.
     *
     * @protected
     * @type {boolean}
     */
    isDraggable = false

    /**
     * Is collapsable.
     *
     * @protected
     * @type {boolean}
     */
    isCollapsible = false

    /**
     * Is maximizable.
     *
     * @protected
     * @type {boolean}
     * @since 9.1.0
     */
    isMaximizable = false

    /**
     * Is collapsed. Do not change value. Only for reading.
     *
     * @protected
     * @type {boolean}
     */
    isCollapsed = false

    /**
     * @type {HTMLElement}
     * @protected
     */
    bodyElement

    /**
     * @inheritDoc
     */
    events = {
        /** @this module:views/modal */
        'click .action': function (e) {
            Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget);
        },
    }

    /**
     * @protected
     * @type {boolean|null}
     */
    footerAtTheTop = null

    /**
     * A shortcut-key => action map.
     *
     * @protected
     * @type {?Object.<string, string|function (KeyboardEvent): void>}
     */
    shortcutKeys = null

    /**
     * @protected
     * @type {HTMLElement}
     * @since 9.0.0
     */
    containerElement

    /**
     * @private
     * @type {ModalBarProvider}
     */
    @inject(ModalBarProvider)
    modalBarProvider

    /**
     * @private
     * @type {ShortcutManager}
     */
    @inject(ShortcutManager)
    shortcutManager

    /**
     * @inheritDoc
     */
    init() {
        const id = this.cssName + '-container-' + Math.floor((Math.random() * 10000) + 1).toString();

        this.containerSelector = '#' + id;

        this.header = this.options.header || this.header;
        this.headerHtml = this.options.headerHtml || this.headerHtml;
        this.$header = this.options.$header || this.$header;
        this.headerElement = this.options.headerElement || this.headerElement;
        this.headerText = this.options.headerText || this.headerText;

        this.backdrop = this.options.backdrop || this.backdrop;

        this.setSelector(this.containerSelector);

        this.buttonList = this.options.buttonList || this.buttonList;
        this.dropdownItemList = this.options.dropdownItemList || this.dropdownItemList;

        this.buttonList = Espo.Utils.cloneDeep(this.buttonList);
        this.dropdownItemList = Espo.Utils.cloneDeep(this.dropdownItemList);

        if (this.shortcutKeys) {
            this.shortcutKeys = Espo.Utils.cloneDeep(this.shortcutKeys);
        }

        if (this.options.collapseDisabled) {
            this.isCollapsible = false;
        }

        this.on('render', () => {
            if (this.dialog) {
                this.dialog.close();
            }

            // Otherwise, re-render won't work.
            this.element = undefined;

            this.isCollapsed = false;

            $(this.containerSelector).remove();

            $('<div />').css('display', 'none')
                .attr('id', id)
                .addClass('modal-container')
                .appendTo('body');

            let modalBodyDiffHeight = 92;

            if (this.getThemeManager().getParam('modalBodyDiffHeight') !== null) {
                modalBodyDiffHeight = this.getThemeManager().getParam('modalBodyDiffHeight');
            }

            let headerHtml = this.headerHtml || this.header;

            if (this.$header && this.$header.length) {
                headerHtml = this.$header.get(0).outerHTML;
            }

            if (this.headerElement) {
                headerHtml = this.headerElement.outerHTML;
            }

            if (this.headerText) {
                headerHtml = Handlebars.Utils.escapeExpression(this.headerText);
            }

            const footerAtTheTop = (this.footerAtTheTop !== null) ? this.footerAtTheTop :
                this.getThemeManager().getParam('modalFooterAtTheTop');

            this.dialog = new Espo.Ui.Dialog({
                backdrop: this.backdrop,
                header: headerHtml,
                container: this.containerSelector,
                body: '',
                buttonList: this.getDialogButtonList(),
                dropdownItemList: this.getDialogDropdownItemList(),
                width: this.width,
                keyboard: !this.escapeDisabled,
                fitHeight: this.fitHeight,
                draggable: this.isDraggable,
                className: this.className,
                bodyDiffHeight: modalBodyDiffHeight,
                footerAtTheTop: footerAtTheTop,
                fullHeight: !this.noFullHeight && this.getThemeManager().getParam('modalFullHeight'),
                screenWidthXs: this.getThemeManager().getParam('screenWidthXs'),
                fixedHeaderHeight: this.fixedHeaderHeight,
                closeButton: !this.noCloseButton,
                collapseButton: this.isCollapsible,
                maximizeButton: this.isMaximizable && !this.getHelper().isXsScreen(),
                onRemove: () => this.onDialogClose(),
                onBackdropClick: () => this.onBackdropClick(),
                onMaximize: () => this.onMaximize(),
                onMinimize: () => this.onMinimize(),
            });

            this.containerElement = document.querySelector(this.containerSelector);

            this.setElement(this.containerSelector + ' .body');
            this.bodyElement = this.element;

            // @todo Review that the element is set back to the container afterwards.
            //     Force keeping set to the body?
        });

        this.on('after:render', () => {
            // Trick to delegate events for the whole modal.
            this.element = undefined;
            this.setElement(this.containerSelector);

            $(this.containerSelector).show();

            this.dialog.show();

            if (this.fixedHeaderHeight && this.flexibleHeaderFontSize) {
                this.adjustHeaderFontSize();
            }

            this.adjustButtons();

            if (!this.noFullHeight) {
                this.initBodyScrollListener();
            }

            if (this.getParentView()) {
                this.getParentView().trigger('modal-shown');
            }

            this.initShortcuts();
        });

        this.once('remove', () => {
            if (this.dialog) {
                this.dialog.close();
            }

            $(this.containerSelector).remove();
        });

        if (this.isCollapsible) {
            this.addActionHandler('collapseModal', () => this.collapse());
        }

        this.on('after:expand', () => this.afterExpand());
    }

    /**
     * @private
     */
    initShortcuts() {
        // Shortcuts to be added even if there's no keys set – to suppress current shortcuts.
        this.shortcutManager.add(this, this.shortcutKeys ?? {}, {stack: true});

        this.once('remove', () => {
            this.shortcutManager.remove(this);
        });
    }

    setupFinal() {
        this.initShortcuts();
    }

    /**
     * Get a button list for a dialog.
     *
     * @private
     * @return {module:ui.Dialog~Button[]}
     */
    getDialogButtonList() {
        const buttonListExt = [];

        // @todo remove it as deprecated.
        this.buttons.forEach(item => {
            const o = Espo.Utils.clone(item);

            if (!('text' in o) && ('label' in o)) {
                o.text = this.getLanguage().translate(o.label);
            }

            buttonListExt.push(o);
        });

        this.buttonList.forEach(item => {
            let o = {};

            if (typeof item === 'string') {
                o.name = /** @type string */item;
            } else if (typeof item === 'object') {
                o = item;
            } else {
                return;
            }

            if (!o.text) {
                if (o.labelTranslation) {
                    o.text = this.getLanguage().translatePath(o.labelTranslation);
                }
                else if ('label' in o) {
                    o.text = this.translate(o.label, 'labels', this.scope);
                }
                else {
                    o.text = this.translate(o.name, 'modalActions', this.scope);
                }
            }

            if (o.iconHtml && !o.html) {
                o.html = o.iconHtml + '<span>' + this.getHelper().escapeString(o.text) + '</span>';
            }
            else if (o.iconClass && !o.html) {
                o.html = `<span class="${o.iconClass}"></span>` +
                    '<span>' + this.getHelper().escapeString(o.text) + '</span>';
            }

            o.onClick = o.onClick || ((d, e) => {
                const handler = o.handler || (o.data || {}).handler;

                Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
                    action: o.name,
                    handler: handler,
                    actionFunction: o.actionFunction,
                });
            });

            buttonListExt.push(o);
        });

        return buttonListExt;
    }

    /**
     * Get a dropdown item list for a dialog.
     *
     * @private
     * @return {Array<module:ui.Dialog~Button|false>}
     */
    getDialogDropdownItemList() {
        const dropdownItemListExt = [];

        this.dropdownItemList.forEach(item => {
            let o = {};

            if (typeof item === 'string') {
                o.name = /** @type string */item;
            } else if (typeof item === 'object') {
                o = item;
            } else {
                return;
            }

            if (!o.text) {
                if (o.labelTranslation) {
                    o.text = this.getLanguage().translatePath(o.labelTranslation);
                }
                else if ('label' in o) {
                    o.text = this.translate(o.label, 'labels', this.scope)
                }
                else {
                    o.text = this.translate(o.name, 'modalActions', this.scope);
                }
            }

            o.onClick = o.onClick || ((d, e) => {
                // noinspection ES6ConvertLetToConst
                let handler = o.handler || (o.data || {}).handler;

                Espo.Utils.handleAction(this, e.originalEvent, e.currentTarget, {
                    action: o.name,
                    handler: handler,
                    actionFunction: o.actionFunction,
                });
            });

            dropdownItemListExt.push(o);
        });

        /** @type {Array<module:ui.Dialog~Button[]>} */
        const dropdownGroups = [];

        dropdownItemListExt.forEach(item => {
            // For bc.
            if (item === false) {
                return;
            }

            const index = (item.groupIndex === undefined ? 9999 : item.groupIndex) + 100;

            if (dropdownGroups[index] === undefined) {
                dropdownGroups[index] = [];
            }

            dropdownGroups[index].push(item);
        });

        const dropdownItemList = [];

        dropdownGroups.forEach(list => {
            list.forEach(it => dropdownItemList.push(it));

            dropdownItemList.push(false);
        });

        return dropdownItemList;
    }

    /** @private */
    updateDialog() {
        if (!this.dialog) {
            return;
        }

        this.dialog.setActionItems(
            this.getDialogButtonList(),
            this.getDialogDropdownItemList()
        );
    }

    /** @private */
    onDialogClose() {
        if (!this.isBeingRendered() && !this.isCollapsed) {
            this.trigger('close');
            this.remove();
        }

        this.shortcutManager.remove(this);
    }

    /**
     * @protected
     */
    onBackdropClick() {}

    /**
     * A `cancel` action.
     */
    actionCancel() {
        this.trigger('cancel');
        this.close();
    }

    /**
     * A `close` action.
     */
    actionClose() {
        this.actionCancel();
    }

    /**
     * Close a dialog.
     */
    close() {
        this.dialog.close();

        if (!this.getParentView()) {
            return;
        }

        const key = this.getParentView().getViewKey(this);

        if (key) {
            this.getParentView().clearView(key);
        }
    }

    /**
     * Disable a button.
     *
     * @param {string} name A button name.
     */
    disableButton(name) {
        this.buttonList.forEach((d) => {
            if (d.name !== name) {
                return;
            }

            d.disabled = true;
        });

        if (!this.isRendered()) {
            return;
        }

        if (!this.containerElement) {
            return;
        }

        $(this.containerElement).find(`footer button[data-name="${name}"]`)
            .addClass('disabled')
            .attr('disabled', 'disabled');
    }

    /**
     * Enable a button.
     *
     * @param {string} name A button name.
     */
    enableButton(name) {
        this.buttonList.forEach((d) => {
            if (d.name !== name) {
                return;
            }

            d.disabled = false;
        });

        if (!this.isRendered()) {
            return;
        }

        if (!this.containerElement) {
            return;
        }

        $(this.containerElement).find('footer button[data-name="'+name+'"]')
            .removeClass('disabled')
            .removeAttr('disabled');
    }

    /**
     * Add a button.
     *
     * @param {module:views/modal~Button} o Button definitions.
     * @param {boolean|string} [position=false] True prepends, false appends. If a string
     *   then will be added after a button with a corresponding name.
     * @param {boolean} [doNotReRender=false] Do not re-render.
     */
    addButton(o, position, doNotReRender) {
        let index = -1;

        this.buttonList.forEach((item, i) => {
            if (item.name === o.name) {
                index = i;
            }
        });

        if (~index) {
            return;
        }

        if (position === true) {
            this.buttonList.unshift(o);
        }
        else if (typeof position === 'string') {
            index = -1;

            this.buttonList.forEach((item, i) => {
                if (item.name === position) {
                    index = i;
                }
            });

            if (~index) {
                this.buttonList.splice(index, 0, o);
            } else {
                this.buttonList.push(o);
            }
        }
        else {
            this.buttonList.push(o);
        }

        if (!doNotReRender && this.isRendered()) {
            this.reRenderFooter();
        }
    }

    /**
     * Add a dropdown item.
     *
     * @param {module:views/modal~Button} o Button definitions.
     * @param {boolean} [toBeginning=false] To prepend.
     * @param {boolean} [doNotReRender=false] Do not re-render.
     */
    addDropdownItem(o, toBeginning, doNotReRender) {
        if (!o) {
            // For bc.
            return;
        }

        const name = o.name;

        if (!name) {
            return;
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                return;
            }
        }

        toBeginning ?
            this.dropdownItemList.unshift(o) :
            this.dropdownItemList.push(o);

        if (!doNotReRender && this.isRendered()) {
            this.reRenderFooter();
        }
    }

    /** @private */
    reRenderFooter() {
        if (!this.dialog) {
            return;
        }

        this.updateDialog();

        const $footer = this.dialog.getFooter();

        $(this.containerElement).find('footer.modal-footer')
            .empty()
            .append($footer);

        this.dialog.initButtonEvents();
    }

    /**
     * Remove a button or a dropdown action item.
     *
     * @param {string} name A name.
     * @param {boolean} [doNotReRender=false] Do not re-render.
     */
    removeButton(name, doNotReRender) {
        let index = -1;

        for (const [i, item] of this.buttonList.entries()) {
            if (item.name === name) {
                index = i;

                break;
            }
        }

        if (~index) {
            this.buttonList.splice(index, 1);
        }

        for (const [i, item] of this.dropdownItemList.entries()) {
            if (item.name === name) {
                this.dropdownItemList.splice(i, 1);

                break;
            }
        }

        if (this.isRendered()) {
            $(this.containerElement).find(`.modal-footer [data-name="${name}"]`).remove();
        }

        if (!doNotReRender && this.isRendered()) {
            this.reRender();
        }
    }

    /**
     * @deprecated Use `showActionItem`.
     *
     * @protected
     * @param {string} name
     */
    showButton(name) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        if (!this.isRendered()) {
            return;
        }

        if (!this.containerElement) {
            return;
        }

        $(this.containerElement).find(`footer button[data-name="${name}"]`).removeClass('hidden');

        this.adjustButtons();
    }

    /**
     * @deprecated Use `hideActionItem`.
     *
     * @protected
     * @param {string} name
     */
    hideButton(name) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        if (!this.isRendered()) {
            return;
        }

        if (!this.containerElement) {
            return;
        }

        $(this.containerElement).find(`footer button[data-name="${name}"]`).addClass('hidden');

        this.adjustButtons();
    }

    /**
     * Show an action item (button or dropdown item).
     *
     * @param {string} name A name.
     */
    showActionItem(name) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        if (!this.isRendered()) {
            return;
        }

        if (!this.containerElement) {
            return;
        }

        const $el = $(this.containerElement);

        $el.find(`footer button[data-name="${name}"]`).removeClass('hidden');
        $el.find(`footer li > a[data-name="${name}"]`).parent().removeClass('hidden');

        if (!this.isDropdownItemListEmpty()) {
            const $dropdownGroup = $el.find('footer .main-btn-group > .btn-group');

            $dropdownGroup.removeClass('hidden');
            $dropdownGroup.find('> button').removeClass('hidden');
        }

        this.adjustButtons();
    }

    /**
     * Hide an action item (button or dropdown item).
     *
     * @param {string} name A name.
     */
    hideActionItem(name) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        if (!this.isRendered()) {
            return;
        }

        const $el = $(this.containerElement);

        $el.find(`footer button[data-name="${name}"]`).addClass('hidden');
        $el.find(`footer li > a[data-name="${name}"]`).parent().addClass('hidden');

        if (this.isDropdownItemListEmpty()) {
            const $dropdownGroup = $el.find('footer .main-btn-group > .btn-group');

            $dropdownGroup.addClass('hidden');
            $dropdownGroup.find('> button').addClass('hidden');
        }

        this.adjustButtons();
    }

    /**
     * Whether an action item exists (hidden, disabled or not).
     *
     * @param {string} name An action item name.
     */
    hasActionItem(name) {
        const hasButton = this.buttonList
            .findIndex(item => item.name === name) !== -1;

        if (hasButton) {
            return true;
        }

        return this.dropdownItemList
            .findIndex(item => item.name === name) !== -1;
    }

    /**
     * Whether an action item is visible and not disabled.
     *
     * @param {string} name An action item name.
     */
    hasAvailableActionItem(name) {
        const hasButton = this.buttonList
            .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

        if (hasButton) {
            return true;
        }

        return this.dropdownItemList
            .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;
    }

    /**
     * @private
     * @return {boolean}
     */
    isDropdownItemListEmpty() {
        if (this.dropdownItemList.length === 0) {
            return true;
        }

        let isEmpty = true;

        this.dropdownItemList.forEach((item) => {
            if (!item.hidden) {
                isEmpty = false;
            }
        });

        return isEmpty;
    }

    /**
     * @private
     * @param {number} [step=0]
     */
    adjustHeaderFontSize(step) {
        step = step || 0;

        if (!step) {
            this.fontSizePercentage = 100;
        }

        if (!this.containerElement) {
            return;
        }

        const $titleText = $(this.containerElement).find('.modal-title > .modal-title-text');

        const containerWidth = $titleText.parent().width();
        let textWidth = 0;

        $titleText.children().each((i, el) => {
            textWidth += $(el).outerWidth(true);
        });

        if (containerWidth < textWidth) {
            if (step > 5) {
                const $title = $(this.containerElement).find('.modal-title');

                $title.attr('title', $titleText.text());
                $title.addClass('overlapped');

                $titleText.children().each((i, el) => {
                   $(el).removeAttr('title');
                });

                return;
            }

            this.fontSizePercentage -= 4;

            $(this.containerElement).find('.modal-title .font-size-flexible')
                .css('font-size', this.fontSizePercentage + '%');

            this.adjustHeaderFontSize(step + 1);
        }
    }

    /**
     * Collapse.
     */
    async collapse() {
        let data = await this.beforeCollapse();

        if (!this.getParentView()) {
            throw new Error("Can't collapse w/o parent view.");
        }

        this.isCollapsed = true;

        data = data || {};

        let title;

        if (data.title) {
            title = data.title;
        } else {
            const titleElement = this.containerElement.querySelector('.modal-header .modal-title .modal-title-text');

            if (titleElement) {
                title = titleElement.textContent;
            }
        }

        this.dialog.close();

        let masterView = this;

        while (masterView.getParentView()) {
            masterView = masterView.getParentView();
        }

        this.unchainFromParent();

        const barView = this.modalBarProvider.get();

        if (!barView) {
            return;
        }

        await barView.addModalView(this, {title: title});
    }

    unchainFromParent() {
        const key = this.getParentView().getViewKey(this);

        this.getParentView().unchainView(key);
    }

    /**
     * Called before collapse. Can be extended to execute some logic, e.g. save form data.
     *
     * @protected
     * @return {Promise}
     */
    beforeCollapse() {
        return new Promise(resolve => resolve());
    }

    /**
     * Called after expanding.
     *
     * @protected
     * @since 9.1.0
     */
    afterExpand() {}

    /** @private */
    adjustButtons() {
        this.adjustLeftButtons();
        this.adjustRightButtons();
    }

    /** @private */
    adjustLeftButtons() {
        const $buttons = $(this.containerElement)
            .find('footer.modal-footer > .main-btn-group button.btn');

        $buttons
            .removeClass('radius-left')
            .removeClass('radius-right');

        const $buttonsVisible = $buttons.filter('button:not(.hidden)');

        $buttonsVisible.first().addClass('radius-left');
        $buttonsVisible.last().addClass('radius-right');
    }

    /** @private */
    adjustRightButtons() {
        const $buttons = $(this.containerElement)
            .find('footer.modal-footer > .additional-btn-group button.btn:not(.btn-text)');

        $buttons
            .removeClass('radius-left')
            .removeClass('radius-right')
            .removeClass('margin-right');

        const $buttonsVisible = $buttons.filter('button:not(.hidden)');

        $buttonsVisible.first().addClass('radius-left');
        $buttonsVisible.last().addClass('radius-right');

        if ($buttonsVisible.last().next().hasClass('btn-text')) {
            $buttonsVisible.last().addClass('margin-right');
        }
    }

    /**
     * @private
     */
    initBodyScrollListener() {
        const $body = $(this.containerElement).find('> .dialog > .modal-dialog > .modal-content > .modal-body');
        const $footer = $body.parent().find('> .modal-footer');

        if (!$footer.length) {
            return;
        }

        $body.off('scroll.footer-shadow');

        $body.on('scroll.footer-shadow', () => {
            if ($body.scrollTop()) {
                $footer.addClass('shadowed');

                return;
            }

            $footer.removeClass('shadowed');
        });
    }

    /**
     * @protected
     * @since 9.1.0
     */
    onMaximize() {}

    /**
     * @protected
     * @since 9.1.0
     */
    onMinimize() {}
}

export default ModalView;
