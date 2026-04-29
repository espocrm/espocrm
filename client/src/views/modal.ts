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

/** @module views/modal */

import View from 'view';
import {inject} from 'di';
import ModalBarProvider from 'helpers/site/modal-bar-provider';
import ShortcutManager from 'helpers/site/shortcut-manager';
import Model from 'model';
import Collection from 'collection';
import {Dialog, DialogButton} from 'ui';
import JQuery from 'jquery';

const $ = JQuery;

/**
 * A button or dropdown action item.
 */
interface ActionItem {
    /**
     * A name.
     */
    name: string;
    /**
     * A label. To be translated (with a scope defined in the `scope` class property).
     */
    label?: string;
    /**
     *  A text (not translated).
     */
    text?: string;
    /**
     * A label translation path.
     */
    labelTranslation?: string;
    /**
     * HTML.
     */
    html?: string;
    /**
     * A style.
     */
    position?: 'left' | 'right';
    /**
     * Is hidden.
     */
    hidden?: boolean;
    /**
     * Disabled.
     */
    disabled?: boolean;
    /**
     * Click handler.
     */
    onClick: (dialog: Dialog) => void;
    /**
     * An additional class name.
     */
    className?: string;
    /**
     * A title text.
     */
    title?: string;
    /**
     * A style.
     */
    style?: 'primary' | 'danger' | 'success' | 'warning' | 'text';
    /**
     * An icon HTML.
     */
    iconHtml?: string;
    /**
     * An icon class.
     */
    iconClass?: string;
    /**
     * A group index. Only for the dropdown.
     */
    groupIndex?: number;
    /**
     * @deprecated Use the `position` property.
     */
    pullLeft?: boolean;
}

interface Options {
    /**
     * A header text.
     */
    headerText?: string;
    /**
     * A header element.
     */
    headerElement?: HTMLElement;
    /**
     * A backdrop.
     */
    backdrop?: 'static' | boolean;
    /**
     * Buttons.
     */
    buttonList?: ActionItem[];
    /**
     * Dropdown items.
     */
    dropdownItemList?: ActionItem[];
    /**
     * Not collapsible.
     *
     * @since 9.1.0
     */
    collapseDisabled?: boolean;
}

interface ViewSchema {
    model?: Model;
    collection?: Collection;
    options?: Record<string, any> & Options;
}

// noinspection JSUnusedGlobalSymbols
export {
    Options as ModalOptions,
    ActionItem as ModalActionItem,
};

/**
 * A base modal view. Can be extended or used directly.
 *
 * @see https://docs.espocrm.com/development/modal/
 */
class ModalView<S extends ViewSchema = ViewSchema> extends View<S> {

    @inject(ModalBarProvider)
    private modalBarProvider: ModalBarProvider

    @inject(ShortcutManager)
    private shortcutManager: ShortcutManager

    /**
     * A CSS name.
     */
    protected cssName: string = 'modal-dialog'

    /**
     * A class-name. Use `'dialog dialog-record'` for modals containing a record form.
     */
    protected className: string = 'dialog'

    /**
     * @deprecated Use `headerHtml`.
     */
    protected header: string

    /**
     * A header HTML. Beware of XSS.
     */
    protected headerHtml: string | null = null

    /**
     * A header JQuery instance.
     * @deprecated
     */
    protected $header: JQuery

    /**
     * A header element.
     */
    protected headerElement: HTMLElement

    /**
     * A header text.
     */
    protected headerText: string | null = null

    /**
     * A dialog instance.
     */
    protected dialog: Dialog

    /**
     * A container selector.
     */
    protected containerSelector: string = ''

    /**
     * A scope name. Used when translating button labels.
     */
    protected scope: string | null = null

    /**
     * A backdrop.
     */
    protected backdrop: 'static' | boolean = 'static'

    /**
     * Buttons.
     */
    protected buttonList: ActionItem[] = []

    /**
     * Dropdown action items.
     */
    protected dropdownItemList: (ActionItem | false)[] = []

    /**
     * Not used.
     */
    protected fitHeight: boolean = false

    /**
     * To disable fitting to a window height.
     */
    protected noFullHeight: boolean = false

    /**
     * Disable the ability to close by pressing the `Esc` key.
     */
    protected escapeDisabled: boolean = false

    /**
     * Is draggable.
     */
    protected isDraggable: boolean = false

    /**
     * Is collapsable.
     */
    protected isCollapsible: boolean = false

    /**
     * Is maximizable.
     *
     * @since 9.1.0
     */
    protected isMaximizable: boolean = false

    /**
     * Is collapsed. Do not change value. Only for reading.
     *
     * @since 9.1.0
     */
    protected isCollapsed: boolean = false

    /**
     * Body element.
     */
    protected bodyElement: HTMLElement

    /**
     * @internal
     */
    protected footerAtTheTop: boolean | null = null

    /**
     * A shortcut-key => action map.
     */
    protected shortcutKeys: Record<string, string | ((event: KeyboardEvent) => void)> | null = null

    /**
     * Use a fixed header height.
     */
    protected fixedHeaderHeight: boolean = false

    /**
     * Disable the close button.
     */
    protected noCloseButton: boolean = false

    /**
     * Container element.
     *
     * @since 9.0.0
     */
    protected containerElement: HTMLElement

    /**
     * Use a flexible header font size.
     */
    protected flexibleHeaderFontSize: boolean;

    private fontSizePercentage: number;

    protected init() {
        this.addHandler('click', '.action', (e, target) => {
            Espo.Utils.handleAction(this, e as MouseEvent, target);
        });

        const id = this.cssName + '-container-' + Math.floor((Math.random() * 10000) + 1).toString();

        this.containerSelector = '#' + id;

        this.headerHtml = this.options.headerHtml || this.headerHtml;
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
            // @ts-ignore
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

            let headerHtml = this.headerHtml;

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

            this.containerElement = document.querySelector(this.containerSelector) as HTMLElement;

            this.setElement(this.containerSelector + ' .body');
            this.bodyElement = this.element;

            // @todo Review that the element is set back to the container afterwards.
            //     Force keeping set to the body?
        });

        this.on('after:render', () => {
            // Trick to delegate events for the whole modal.
            // @ts-ignore
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

    private initShortcuts() {
        // Shortcuts to be added even if there's no keys set – to suppress current shortcuts.
        this.shortcutManager.add(this, this.shortcutKeys ?? {}, {stack: true});

        this.once('remove', () => {
            this.shortcutManager.remove(this);
        });
    }

    protected setupFinal() {
        this.initShortcuts();
    }

    /**
     * Get a button list for a dialog.
     */
    private getDialogButtonList(): DialogButton[] {
        const buttonListExt: DialogButton[] = [];

        this.buttonList.forEach(item => {
            let o = {} as DialogButton & Record<string, any>;

            if (typeof item === 'string') {
                o.name = item;
            } else if (typeof item === 'object') {
                o = item as DialogButton;
            } else {
                return;
            }

            if (!o.text) {
                if (o.labelTranslation) {
                    o.text = this.getLanguage().translatePath(o.labelTranslation);
                } else if ('label' in o) {
                    o.text = this.translate(o.label, 'labels', this.scope);
                } else {
                    o.text = this.translate(o.name, 'modalActions', this.scope);
                }
            }

            if (o.iconHtml && !o.html) {
                o.html = o.iconHtml + '<span>' + this.getHelper().escapeString(o.text as string) + '</span>';
            } else if (o.iconClass && !o.html) {
                o.html = `<span class="${o.iconClass}"></span>` +
                    '<span>' + this.getHelper().escapeString(o.text as string) + '</span>';
            }

            o.onClick = o.onClick ?? ((_d, event, target) => {
                const handler = o.handler || (o.data || {}).handler;

                Espo.Utils.handleAction(this, event, target, {
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
     */
    private getDialogDropdownItemList(): (DialogButton | false)[] {
        const dropdownItemListExt: (DialogButton | false)[] = [];

        this.dropdownItemList.forEach(item => {
            let o = {} as DialogButton & Record<string, any>;

            if (typeof item === 'string') {
                o.name = item;
            } else if (typeof item === 'object') {
                o = item as DialogButton;
            } else {
                return;
            }

            if (!o.text) {
                if (o.labelTranslation) {
                    o.text = this.getLanguage().translatePath(o.labelTranslation);
                } else if ('label' in o) {
                    o.text = this.translate(o.label, 'labels', this.scope)
                } else {
                    o.text = this.translate(o.name, 'modalActions', this.scope);
                }
            }

            o.onClick = o.onClick ?? ((_d, e: any) => {
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

        const dropdownGroups = [] as DialogButton[][];

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

        const dropdownItemList: (DialogButton | false)[] = [];

        dropdownGroups.forEach(list => {
            list.forEach(it => dropdownItemList.push(it));

            dropdownItemList.push(false);
        });

        return dropdownItemList;
    }

    private updateDialog() {
        if (!this.dialog) {
            return;
        }

        this.dialog.setActionItems(
            this.getDialogButtonList(),
            this.getDialogDropdownItemList()
        );
    }

    private onDialogClose() {
        if (!this.isBeingRendered() && !this.isCollapsed) {
            this.trigger('close');
            this.remove();
        }

        this.shortcutManager.remove(this);
    }

    protected onBackdropClick() {}

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
     * @param name A button name.
     */
    disableButton(name: string) {
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
     * @param name A button name.
     */
    enableButton(name: string) {
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
     * @param o Button definitions.
     * @param [position=false] True prepends, false appends. If a string
     *   then will be added after a button with a corresponding name.
     * @param [doNotReRender=false] Do not re-render.
     */
    addButton(o: ActionItem, position: boolean | string, doNotReRender: boolean = false) {
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
        } else {
            this.buttonList.push(o);
        }

        if (!doNotReRender && this.isRendered()) {
            this.reRenderFooter();
        }
    }

    /**
     * Add a dropdown item.
     *
     * @param o Button definitions.
     * @param [toBeginning=false] To prepend.
     * @param [doNotReRender=false] Do not re-render.
     */
    addDropdownItem(o: ActionItem, toBeginning: boolean, doNotReRender: boolean = false) {
        if (!o) {
            // For bc.
            return;
        }

        const name = o.name;

        if (!name) {
            return;
        }

        for (const item of this.dropdownItemList) {
            if (item && item.name === name) {
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

    private reRenderFooter() {
        if (!this.dialog) {
            return;
        }

        this.updateDialog();

        const $footer = this.dialog.getFooter();

        $(this.containerElement).find('footer.modal-footer')
            .empty()
            .append($footer as JQuery);

        this.dialog.initButtonEvents();
    }

    /**
     * Remove a button or a dropdown action item.
     *
     * @param name A name.
     * @param [doNotReRender=false] Do not re-render.
     */
    removeButton(name: string, doNotReRender: boolean = false) {
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
            if (item && item.name === name) {
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
     */
    protected showButton(name: string) {
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
     */
    protected hideButton(name: string) {
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
     * @param name A name.
     */
    showActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = false;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item && item.name === name) {
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
     * @param name A name.
     */
    hideActionItem(name: string) {
        for (const item of this.buttonList) {
            if (item.name === name) {
                item.hidden = true;

                break;
            }
        }

        for (const item of this.dropdownItemList) {
            if (item && item.name === name) {
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

    // noinspection JSUnusedGlobalSymbols
    /**
     * Whether an action item exists (hidden, disabled or not).
     *
     * @param name An action item name.
     */
    hasActionItem(name: string): boolean {
        const hasButton = this.buttonList
            .findIndex(item => item.name === name) !== -1;

        if (hasButton) {
            return true;
        }

        return this.dropdownItemList
            .findIndex(item => item && item.name === name) !== -1;
    }

    /**
     * Whether an action item is visible and not disabled.
     *
     * @param name An action item name.
     */
    hasAvailableActionItem(name: string): boolean {
        const hasButton = this.buttonList
            .findIndex(item => item.name === name && !item.disabled && !item.hidden) !== -1;

        if (hasButton) {
            return true;
        }

        return this.dropdownItemList
            .findIndex(item => item && item.name === name && !item.disabled && !item.hidden) !== -1;
    }

    private isDropdownItemListEmpty(): boolean {
        if (this.dropdownItemList.length === 0) {
            return true;
        }

        let isEmpty = true;

        this.dropdownItemList.forEach((item) => {
            if (item && !item.hidden) {
                isEmpty = false;
            }
        });

        return isEmpty;
    }

    private adjustHeaderFontSize(step: number = 0) {
        step = step || 0;

        if (!step) {
            this.fontSizePercentage = 100;
        }

        if (!this.containerElement) {
            return;
        }

        const $titleText = $(this.containerElement).find('.modal-title > .modal-title-text');

        const containerWidth = $titleText.parent().width() as number;
        let textWidth = 0;

        $titleText.children().each((_i, el) => {
            textWidth += $(el).outerWidth(true) as number;
        });

        if (containerWidth < textWidth) {
            if (step > 5) {
                const $title = $(this.containerElement).find('.modal-title');

                $title.attr('title', $titleText.text());
                $title.addClass('overlapped');

                $titleText.children().each((_i, el) => {
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

        data = data ?? {};

        let title: string | null = null;

        if (data.title) {
            title = data.title;
        } else {
            const titleElement = this.containerElement.querySelector('.modal-header .modal-title .modal-title-text');

            if (titleElement) {
                title = titleElement.textContent;
            }
        }

        this.dialog.close();

        let masterView: View = this;

        while (masterView.getParentView()) {
            masterView = masterView.getParentView() as View;
        }

        this.unchainFromParent();

        const barView = this.modalBarProvider.get();

        if (!barView) {
            return;
        }

        await barView.addModalView(this, {title: title ?? null});
    }

    private unchainFromParent() {
        const key = this.getParentView().getViewKey(this);

        if (!key) {
            return;
        }

        this.getParentView().unchainView(key);
    }

    /**
     * Called before collapse. Can be extended to execute some logic, e.g. save form data.
     */
    protected beforeCollapse(): Promise<undefined | {title?: string}> {
        return new Promise(resolve => resolve(undefined));
    }

    /**
     * Called after expanding.
     *
     * @since 9.1.0
     */
    protected afterExpand() {}

    private adjustButtons() {
        this.adjustLeftButtons();
        this.adjustRightButtons();
    }

    private adjustLeftButtons() {
        const $buttons = $(this.containerElement)
            .find('footer.modal-footer > .main-btn-group button.btn');

        $buttons
            .removeClass('radius-left')
            .removeClass('radius-right');

        const $buttonsVisible = $buttons.filter('button:not(.hidden)');

        $buttonsVisible.first().addClass('radius-left');
        $buttonsVisible.last().addClass('radius-right');
    }

    private adjustRightButtons() {
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

    private initBodyScrollListener() {
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
     * @since 9.1.0
     */
    protected onMaximize() {}

    /**
     * @since 9.1.0
     */
    protected onMinimize() {}
}

export default ModalView;
