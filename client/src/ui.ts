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

/** @module ui */

import {marked} from 'marked';
import DOMPurify from 'dompurify';
import JQuery from 'jquery';

const $ = JQuery;

/**
 * Dialog parameters.
 */
interface DialogParams {
    className?: string;
    backdrop?: 'static' | true | false;
    closeButton?: boolean;
    collapseButton?: boolean;
    header?: string | null;
    body?: string;
    width?: number | null;
    removeOnClose?: boolean;
    draggable?: boolean;
    onRemove?: () => void;
    onClose?: () => void;
    onBackdropClick?: () => void;
    container?: string;
    keyboard?: boolean;
    footerAtTheTop?: boolean;
    buttonList?: DialogButton[];
    fullHeight?: boolean;
    bodyDiffHeight?: number;
    screenWidthXs?: number;
    maximizeButton?: boolean;
    onMaximize?: () => void;
    onMinimize?: () => void;
    backdropClassName?: string;
    fixedHeaderHeight?: boolean;
}

/**
 * A button or dropdown action item.
 */
interface DialogButton {
    name: string;
    pullLeft?: boolean;
    position?: 'left' | 'right';
    html?: string;
    text?: string;
    disabled?: boolean;
    hidden?: boolean;
    style?: 'default' | 'danger' | 'success' | 'warning';
    onClick?: (dialog: Dialog, event: MouseEvent, target: HTMLElement) => void;
    className?: string;
    title?: string;
}

/**
 * Popover options.
 */
interface PopoverOptions {
    placement?: 'bottom' | 'top' | 'left' | 'right';
    container?: string | JQuery;
    content?: string;
    text?: string;
    trigger?: 'manual' | 'click' | 'hover' | 'focus';
    noToggleInit?: boolean;
    preventDestroyOnRender?: boolean;
    noHideOnOutsideClick?: boolean;
    onShow?: () => void;
    onHide?: () => void;
    title?: string | (() => string);
    keepElementTitle?: boolean;
}

/**
 * Notify options.
 */
interface NotifyOptions {
    closeButton?: boolean;
    suppress?: boolean;
}

const shownDialogList: Dialog[] = [];

/**
 * @alias Espo.Ui.Dialog
 */
class Dialog {

    private fitHeight: boolean
    private onRemove: () => void
    private onClose: () => void
    private onBackdropClick: () => void
    private buttonList: DialogButton[]
    private dropdownItemList: (DialogButton | false)[]
    private backdropClassName: string
    private readonly removeOnClose = true
    private className: string = 'dialog-confirm'
    private readonly backdrop: string = 'static'
    private maximizeButton: boolean = false
    private maximizeButtonElement: HTMLAnchorElement
    private minimizeButtonElement: HTMLAnchorElement
    private readonly onMaximize: () => void
    private readonly onMinimize: () => void
    private closeButton: boolean = true
    private collapseButton: boolean = false
    private readonly header: string | null = null
    private readonly body: string = ''
    private readonly width: string | null = null
    private onCloseIsCalled: boolean = false
    private readonly id: string
    private readonly activeElement: Element
    private readonly draggable: boolean = false
    private readonly container: string = 'body';
    private options: DialogParams
    private readonly keyboard: boolean = true
    private skipRemove: boolean = false

    private _backdropElement: HTMLElement
    private $el: JQuery
    private readonly el: Element
    private $mouseDownTarget: JQuery | undefined

    /**
     * @param options Options.
     */
    constructor(options: DialogParams) {
        options = options ?? {};

        this.buttonList = [];
        this.dropdownItemList = [];
        this.options = options;

        this.activeElement = document.activeElement;

        const params = [
            'className',
            'backdrop',
            'keyboard',
            'closeButton',
            'collapseButton',
            'header',
            'body',
            'width',
            'height',
            'fitHeight',
            'buttons',
            'buttonList',
            'dropdownItemList',
            'removeOnClose',
            'draggable',
            'container',
            'onRemove',
            'onClose',
            'onBackdropClick',
            'maximizeButton',
            'backdropClassName',
        ];

        params.forEach(param => {
            if (!(param in options)) {
                return;
            }

            (this as Record<string, any>)[param] = (options as Record<string, any>)[param];
        });

        if (options.onMaximize) {
            this.onMaximize = options.onMaximize;
        }

        if (options.onMinimize) {
            this.onMinimize = options.onMinimize;
        }

        if ((options as Record<string, any>).buttons) {
            this.buttonList = ((options as any).buttons) as DialogButton[];
        }

        this.id = 'dialog-' + Math.floor((Math.random() * 100000));

        if (typeof this.backdrop === 'undefined') {
            this.backdrop = 'static';
        }

        const $header = this.getHeader();
        const $footer = this.getFooter();

        const $body = $('<div>')
            .addClass('modal-body body')
            .html(this.body);

        const $content = $('<div>').addClass('modal-content');

        if ($header) {
            $content.append($header);
        }

        if ($footer && this.options.footerAtTheTop) {
            $content.append($footer);
        }

        $content.append($body);

        if ($footer && !this.options.footerAtTheTop) {
            $content.append($footer);
        }

        const $dialog = $('<div>')
            .addClass('modal-dialog')
            .append($content);

        const $container = $(this.container);

        $('<div>')
            .attr('id', this.id)
            .attr('class', this.className + ' modal')
            .attr('role', 'dialog')
            .attr('tabindex', '-1')
            .append($dialog)
            .appendTo($container);

        this.$el = $('#' + this.id);
        this.el = this.$el.get(0);

        /*this.$el.find('header a.close').on('click', () => {
            //this.close();
        });*/

        this.initButtonEvents();

        if (this.draggable) {
            this.$el.find('header').css('cursor', 'pointer');

            // noinspection JSUnresolvedReference
            // @ts-ignore
            this.$el.draggable({handle: 'header'});
        }

        const modalContentEl = this.$el.find('.modal-content');

        if (this.width) {
            modalContentEl.css('width', this.width);
            modalContentEl.css('margin-left', '-' + (parseInt(this.width.replace('px', '')) / 5) + 'px');
        }

        if (this.removeOnClose) {
            this.$el.on('hidden.bs.modal', e => {
                if (this.$el.get(0) === e.target) {
                    if (!this.onCloseIsCalled) {
                        this.close();
                    }

                    if (this.skipRemove) {
                        return;
                    }

                    this.remove();
                }
            });
        }

        const $window = $(window);

        this.$el.on('shown.bs.modal', () => {
            $('.modal-backdrop').not('.stacked').addClass('stacked');

            const headerHeight = this.$el.find('.modal-header').outerHeight() || 0;
            const footerHeight = this.$el.find('.modal-footer').outerHeight() || 0;

            let diffHeight = headerHeight + footerHeight;

            if (!options.fullHeight) {
                diffHeight = diffHeight + options.bodyDiffHeight;
            }

            if (this.fitHeight || options.fullHeight) {
                const processResize = () => {
                    const windowHeight = window.innerHeight;
                    const windowWidth = $window.width();

                    if (!options.fullHeight && windowHeight < 512) {
                        this.$el.find('div.modal-body').css({
                            maxHeight: 'none',
                            overflow: 'auto',
                            height: 'none',
                        });

                        return;
                    }

                    const cssParams = {
                        overflow: 'auto',
                    } as Record<string, any>;

                    if (options.fullHeight) {
                        cssParams.height = (windowHeight - diffHeight) + 'px';

                        this.$el.css('paddingRight', 0);
                    } else {
                        if (windowWidth <= options.screenWidthXs) {
                            cssParams.maxHeight = 'none';
                        } else {
                            cssParams.maxHeight = (windowHeight - diffHeight) + 'px';
                        }
                    }

                    this.$el.find('div.modal-body').css(cssParams);
                };

                $window.off('resize.modal-height');
                $window.on('resize.modal-height', processResize);

                processResize();
            }
        });

        const $documentBody = $(document.body);

        this.$el.on('hidden.bs.modal', () => {
            if ($('.modal:visible').length > 0) {
                $documentBody.addClass('modal-open');
            }
        });
    }

    /**
     * Get a general container element.
     * @since 9.1.0
     */
    getElement(): HTMLDivElement {
        return this.el as HTMLDivElement;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Update the header text.
     *
     * @param {string} text
     * @since 9.1.0
     */
    setHeaderText(text: string) {
        const element = this.el.querySelector('.modal-header .modal-title');

        if (!element) {
            return;
        }

        element.textContent = text;
    }
    private callOnClose() {
        if (this.onClose) {
            this.onClose()
        }
    }

    private callOnBackdropClick() {
        if (this.onBackdropClick) {
            this.onBackdropClick()
        }
    }

    private callOnRemove() {
        if (this.onRemove) {
            this.onRemove()
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set action items.
     *
     * @param buttonList Buttons
     * @param dropdownItemList Dropdown items.
     */
    setActionItems(buttonList: DialogButton[], dropdownItemList: (DialogButton | false)[]) {
        this.buttonList = buttonList;
        this.dropdownItemList = dropdownItemList;
    }

    /**
     * Init button events.
     */
    initButtonEvents() {
        this.buttonList.forEach(o => {
            if (typeof o.onClick !== 'function') {
                return;
            }

            const $button = $(`#${this.id} .modal-footer button[data-name="${o.name}"]`);

            $button.on('click', e => o.onClick(this, e.originalEvent, e.currentTarget));
        });

        this.dropdownItemList.forEach(o => {
            if (o === false) {
                return;
            }

            if (typeof o.onClick !== 'function') {
                return;
            }

            const $button = $(`#${this.id} .modal-footer a[data-name="${o.name}"]`);

            $button.on('click', e => o.onClick(this, e.originalEvent, e.currentTarget));
        });
    }

    private getHeader(): JQuery | null {
        if (!this.header) {
            return null;
        }

        const $header = $('<header />')
            .addClass('modal-header')
            .addClass(this.options.fixedHeaderHeight ? 'fixed-height' : '')
            .append(
                $('<h4 />')
                    .addClass('modal-title')
                    .append(
                        $('<span />')
                            .addClass('modal-title-text')
                            .html(this.header)
                    )
            );


        if (this.collapseButton) {
            $header.prepend(
                $('<a>')
                    .addClass('collapse-button')
                    .attr('role', 'button')
                    .attr('tabindex', '-1')
                    .attr('data-action', 'collapseModal')
                    .append(
                        $('<span />')
                            .addClass('fas fa-minus')
                    )
            );
        }

        if (this.maximizeButton) {
            {
                const a = document.createElement('a');
                a.classList.add('maximize-button');
                a.role = 'button';
                a.tabIndex = -1;
                a.setAttribute('data-action', 'maximizeModal');

                const icon = document.createElement('span');
                icon.classList.add('far', 'fa-window-maximize')

                a.append(icon);
                $header.prepend(a);

                this.maximizeButtonElement = a;

                a.addEventListener('click', () => {
                    this.maximizeButtonElement.classList.add('hidden');
                    this.minimizeButtonElement.classList.remove('hidden');

                    this.el.querySelector('.modal-dialog').classList.add('maximized');

                    if (this.onMaximize) {
                        this.onMaximize();
                    }

                    this.getElement().focus();
                });
            }

            {
                const a = document.createElement('a');
                a.classList.add('minimize-button', 'hidden');
                a.role = 'button';
                a.tabIndex = -1;
                a.setAttribute('data-action', 'minimizeModal');

                const icon = document.createElement('span');
                icon.classList.add('far', 'fa-window-minimize')

                a.append(icon);
                $header.prepend(a);

                this.minimizeButtonElement = a;

                a.addEventListener('click', () => {
                    this.minimizeButtonElement.classList.add('hidden');
                    this.maximizeButtonElement.classList.remove('hidden');

                    this.el.querySelector('.modal-dialog').classList.remove('maximized');

                    if (this.onMinimize) {
                        this.onMinimize();
                    }

                    this.getElement().focus();
                });
            }
        }

        if (this.closeButton) {
            $header.prepend(
                $('<a>')
                    .addClass('close')
                    .attr('data-dismiss', 'modal')
                    .attr('role', 'button')
                    .attr('tabindex', '-1')
                    .append(
                        $('<span />')
                            .attr('aria-hidden', 'true')
                            .html('&times;')
                    )
            );
        }

        return $header;
    }

    /**
     * Get a footer.
     */
    private getFooter(): JQuery | null {
        if (!this.buttonList.length && !this.dropdownItemList.length) {
            return null;
        }

        const $footer = $('<footer>').addClass('modal-footer');

        const $main = $('<div>')
            .addClass('btn-group')
            .addClass('main-btn-group');

        const $additional = $('<div>')
            .addClass('btn-group')
            .addClass('additional-btn-group');

        this.buttonList.forEach(/** module:ui.Dialog~Button */o => {
            const style = o.style || 'default';

            const $button =
                $('<button>')
                    .attr('type', 'button')
                    .attr('data-name', o.name)
                    .addClass('btn')
                    .addClass('btn-' + style)
                    .addClass(o.className || 'btn-xs-wide');

            if (o.disabled) {
                $button.attr('disabled', 'disabled');
                $button.addClass('disabled');
            }

            if (o.hidden) {
                $button.addClass('hidden');
            }

            if (o.title) {
                $button.attr('title', o.title);
            }

            if (o.text) {
                $button.text(o.text);
            }

            if (o.html) {
                $button.html(o.html);
            }

            if (o.pullLeft || o.position === 'right') {
                $additional.append($button);

                return;
            }

            $main.append($button);
        });

        const allDdItemsHidden = this.dropdownItemList.filter(o => o && !o.hidden).length === 0;

        const $dropdown = $('<div>')
            .addClass('btn-group')
            .addClass(allDdItemsHidden ? 'hidden' : '')
            .append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-default dropdown-toggle')
                    .addClass(allDdItemsHidden ? 'hidden' : '')
                    .attr('data-toggle', 'dropdown')
                    .append(
                        $('<span>').addClass('fas fa-ellipsis-h')
                    )
            );

        const $ul = $('<ul>').addClass('dropdown-menu pull-right');

        $dropdown.append($ul);

        this.dropdownItemList.forEach((o, i) => {
            if (o === false) {
                if (i === this.dropdownItemList.length - 1) {
                    return;
                }

                $ul.append(`<li class="divider"></li>`);

                return;
            }

            const $a = $('<a>')
                .attr('role', 'button')
                .attr('tabindex', '0')
                .attr('data-name', o.name);

            if (o.text) {
                $a.text(o.text);
            }

            if (o.title) {
                $a.attr('title', o.title);
            }

            if (o.html) {
                $a.html(o.html);
            }

            const $li = $('<li>')
                .addClass(o.hidden ? ' hidden' : '')
                .append($a);

            $ul.append($li);
        });

        if ($ul.children().length) {
            $main.append($dropdown);
        }

        if ($additional.children().length) {
            $footer.append($additional);
        }

        $footer.append($main);

        return $footer;
    }

    /**
     * Show.
     */
    show() {
        shownDialogList.push(this);

        // noinspection JSUnresolvedReference,JSUnusedGlobalSymbols
        // @ts-ignore
        // noinspection JSUnusedGlobalSymbols
        this.$el.modal({
            backdrop: this.backdrop,
            keyboard: this.keyboard,
            onBackdropCreate: (element: HTMLElement) => {
                this._backdropElement = element;

                if (this.backdropClassName) {
                    this._backdropElement.classList.add(this.backdropClassName);
                }
            },
        });

        this.$el.find('.modal-content').removeClass('hidden');

        for (const [i, dialog] of shownDialogList.entries()) {
            if (
                i < shownDialogList.length - 1 &&
                dialog.getElement() &&
                dialog.getElement().parentElement
            ) {
                if (dialog._backdropElement) {
                    dialog._backdropElement.classList.add('hidden');
                }

                dialog.getElement().parentElement.classList.add('overlaid');
            }
        }

        this.$el.off('click.dismiss.bs.modal');

        this.$el.on(
            'click.dismiss.bs.modal',
            '> div.modal-dialog > div.modal-content > header [data-dismiss="modal"]',
            () => this.close()
        );

        this.$el.on('mousedown', e => {
            this.$mouseDownTarget = $(e.target);
        });

        this.$el.on('click.dismiss.bs.modal', (e) => {
            if (e.target !== e.currentTarget) {
                return;
            }

            if (
                this.$mouseDownTarget &&
                this.$mouseDownTarget.closest('.modal-content').length
            ) {
                return;
            }

            this.callOnBackdropClick();

            if (this.backdrop === 'static') {
                return;
            }

            this.close();
        });

        $('body > .popover').addClass('hidden');
    }

    /**
     * Hide.
     *
     * @todo Check usage. Deprecate?
     */
    hide() {
        this.$el.find('.modal-content').addClass('hidden');
    }

    /**
     * Hide with a backdrop.
     */
    hideWithBackdrop() {
        if (this._backdropElement) {
            this._backdropElement.classList.add('hidden');
        }

        this._hideInternal();

        this.skipRemove = true;

        setTimeout(() => this.skipRemove = false, 50);

        // noinspection JSUnresolvedReference
        // @ts-ignore
        this.$el.modal('hide');
        this.$el.find('.modal-content').addClass('hidden');
    }

    /**
     * @private
     */
    _hideInternal() {
        const findIndex = shownDialogList.findIndex(it => it === this);

        if (findIndex >= 0) {
            shownDialogList.splice(findIndex, 1);
        }

        const last = shownDialogList[shownDialogList.length - 1];

        if (last && last._backdropElement) {
            last._backdropElement.classList.remove('hidden')
        }

        if (last && last.getElement() && last.getElement().parentElement) {
            last.getElement().parentElement.classList.remove('overlaid')
        }
    }

    /**
     * @private
     */
    _close() {
        this._hideInternal();
    }

    private _findClosestFocusableElement(element: Element): Element | null {
        // noinspection JSUnresolvedReference
        const isVisible = !!(
            element instanceof HTMLElement && element.offsetWidth ||
            element instanceof HTMLElement && element.offsetHeight ||
            element.getClientRects().length
        );

        if (isVisible &&  element instanceof HTMLElement) {
            // noinspection JSUnresolvedReference
            element.focus({preventScroll: true});

            return element;
        }

        const $element = $(element);

        if ($element.closest('.dropdown-menu').length) {
            const $button = $element.closest('.btn-group').find(`[data-toggle="dropdown"]`);

            if ($button.length) {
                // noinspection JSUnresolvedReference
                $button.get(0).focus({preventScroll: true});

                return $button.get(0);
            }
        }

        return null;
    }

    /**
     * Close.
     */
    close() {
        if (!this.onCloseIsCalled) {
            this.callOnClose();
            this.onCloseIsCalled = true;

            if (this.activeElement) {
                setTimeout(() => {
                    const element = this._findClosestFocusableElement(this.activeElement);

                    if (element && element instanceof HTMLElement) {
                        // noinspection JSUnresolvedReference
                        element.focus({preventScroll: true});
                    }
                }, 50);
            }
        }

        this._close();
        // noinspection JSUnresolvedReference
        // @ts-ignore
        this.$el.modal('hide');
        $(this).trigger('dialog:close');
    }

    /**
     * Remove.
     */
    remove() {
        this.callOnRemove();

        // Hack allowing multiple backdrops.
        // `close` function may be called twice.
        this._close();
        this.$el.remove();

        $(this).off();
        $(window).off('resize.modal-height');
    }
}

interface ConfirmOptions {
    confirmText: string;
    cancelText: string;
    confirmStyle?: 'danger' | 'success' | 'warning' | 'default';
    backdrop?: 'static' | boolean;
    cancelCallback?: () => void;
    isHtml?: boolean;
}

/**
 * UI utils.
 */
const Ui = {

    Dialog: Dialog,

    /**
     * Show a confirmation dialog.
     *
     * @param message A message.
     * @param options Options.
     * @param [callback] Deprecated. Use a promise.
     * @param [context] Deprecated.
     * @returns Resolves if confirmed.
     */
    confirm: function (
        message: string,
        options: ConfirmOptions,
        callback?: () => void,
        context?: object,
    ): Promise<void> {

        options = {...options};

        const confirmText = options.confirmText;
        const cancelText = options.cancelText;
        const confirmStyle = options.confirmStyle || 'danger';
        let backdrop = options.backdrop;

        if (typeof backdrop === 'undefined') {
            backdrop = false;
        }

        let isResolved = false;

        const processCancel = () => {
            if (!options.cancelCallback) {
                return;
            }

            if (context) {
                options.cancelCallback.call(context);

                return;
            }

            options.cancelCallback();
        };

        if (!options.isHtml) {
            message = Handlebars.Utils.escapeExpression(message);
        }

        confirmCount ++;

        return new Promise(resolve => {
            const dialog = new Dialog({
                backdrop: backdrop,
                header: null,
                className: 'dialog-confirm',
                backdropClassName: 'backdrop-confirm',
                body: `<span class="confirm-message">${message}</a>`,
                buttonList: [
                    {
                        text: ` ${confirmText} `,
                        name: 'confirm',
                        className: 'btn-s-wide',
                        onClick: () => {
                            isResolved = true;

                            if (callback) {
                                if (context) {
                                    callback.call(context);
                                } else {
                                    callback();
                                }
                            }

                            resolve();

                            dialog.close();
                        },
                        style: confirmStyle,
                        position: 'right',
                    },
                    {
                        text: cancelText,
                        name: 'cancel',
                        className: 'btn-s-wide',
                        onClick: () => {
                            isResolved = true;

                            dialog.close();
                            processCancel();
                        },
                        position: 'left',
                    }
                ],
                onClose: () => {
                    confirmCount --;

                    if (isResolved) {
                        return;
                    }

                    processCancel();
                },
            });

            dialog.show();
            $(dialog.getElement()).find('button[data-name="confirm"]').trigger('focus');
        });
    },

    /**
     * Create a dialog.
     *
     * @param options Options.
     * @returns A dialog instance.
     */
    dialog: function (options: DialogParams): Dialog {
        return new Dialog(options);
    },

    /**
     * Init a popover.
     *
     * @param element An element.
     * @param options Options.
     * @param [view] A view.
     * @return Manipulator object.
     */
    popover: function (
        element: Element,
        options: PopoverOptions,
        view?: import('view').default
    ): {
        hide: () => void;
        destroy: () => void;
        show: () => string;
        detach: () => void;
    } {

        const $el = $(element);
        const $body = $('body');
        const content = options.content || Handlebars.Utils.escapeExpression(options.text || '');
        let isShown = false;

        let container = options.container;

        if (!container) {
            const $modalBody = $el.closest('.modal-body');

            container = $modalBody.length ? $modalBody : 'body';
        }

        // noinspection JSUnresolvedReference
        // @ts-ignore
        $el.popover({
            placement: options.placement || 'bottom',
            container: container,
            viewport: container,
            html: true,
            content: content,
            trigger: options.trigger || 'manual',
            title: options.title,
            keepElementTitle: options.keepElementTitle,
        })
            .on('shown.bs.popover', () => {
                isShown = true;

                if (!view) {
                    return;
                }

                if (view && !options.noHideOnOutsideClick) {
                    $body.off(`click.popover-${view.cid}`);

                    $body.on(`click.popover-${view.cid}`, e => {
                        if ($(e.target).closest('.popover').get(0)) {
                            return;
                        }

                        if ($.contains($el.get(0), e.target)) {
                            return;
                        }

                        if ($el.get(0) === e.target) {
                            return;
                        }

                        $body.off(`click.popover-${view.cid}`);
                        // noinspection JSUnresolvedReference
                        // @ts-ignore
                        $el.popover('hide');
                    });
                }

                if (options.onShow) {
                    options.onShow();
                }
            })
            .on('hidden.bs.popover', () => {
                isShown = false;

                if (options.onHide) {
                    options.onHide();
                }
            });

        if (!options.noToggleInit) {
            $el.on('click', () => {
                // noinspection JSUnresolvedReference
                // @ts-ignore
                $el.popover('toggle');
            });
        }

        let isDetached = false;

        const detach = () => {
            if (view) {
                $body.off(`click.popover-${view.cid}`);

                view.off('remove', destroy);
                view.off('render', destroy);
                view.off('render', hide);
            }

            isDetached = true;
        };

        const destroy = () => {
            if (isDetached) {
                return;
            }

            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('destroy');

            detach();
        };

        const hide = () => {
            if (!isShown) {
                return;
            }

            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('hide');
        };

        const show = () => {
            // noinspection JSUnresolvedReference
            // @ts-ignore
            $el.popover('show');

            return $el.attr('aria-describedby');
        };

        if (view) {
            view.once('remove', destroy);

            if (!options.preventDestroyOnRender) {
                view.once('render', destroy);
            }

            if (options.preventDestroyOnRender) {
                view.on('render', hide);
            }
        }

        return {
            hide: () => hide(),
            destroy: () => destroy(),
            show: () => show(),
            detach: () => detach(),
        };
    },

    /**
     * Show the spinner.
     *
     * @since 9.1.0
     */
    notifyWait: function () {
        Ui.notify(' ... ');
    },

    /**
     * Show a notify-message.
     *
     * @param [message] A message. False removes an already displayed message.
     * @param [type='warning'] A type.
     * @param [timeout] Microseconds. If empty, then won't be hidden.
     *   Should be hidden manually or by displaying another message.
     * @param [options] Options.
     */
    notify: function (
        message: string | false = false,
        type?: 'warning' | 'danger' | 'success' | 'info',
        timeout?: number,
        options?: NotifyOptions,
    ) {

        type = type ?? 'warning';
        options = {...options};

        if (type === 'warning' && notifySuppressed) {
            return;
        }

        $('#notification').remove();

        if (!message) {
            return;
        }

        if (options.suppress && timeout) {
            notifySuppressed = true;

            setTimeout(() => notifySuppressed = false, timeout)
        }

        const parsedMessage = message.indexOf('\n') !== -1 ?
            marked.parse(message) :
            marked.parseInline(message);

        let sanitizedMessage = DOMPurify.sanitize(parsedMessage, {}).toString();

        const closeButton = options.closeButton || false;

        // @ts-ignore
        if (type === 'error') {
            // For bc.
            type = 'danger';
        }

        if (sanitizedMessage === ' ... ') {
            sanitizedMessage = ' <span class="fas fa-spinner fa-spin"> ';
        }

        const additionalClassName = closeButton ? ' alert-closable' : '';

        const $el = $('<div>')
            .addClass('alert alert-' + type + additionalClassName + ' fade in')
            .attr('id', 'notification')
            .css({
                'position': 'fixed',
                'top': '0',
                'left': '50vw',
                'transform': 'translate(-50%, 0)',
                'z-index': 2000,
            })
            .append(
                $('<div>')
                    .addClass('message')
                    .html(sanitizedMessage)
            );

        if (closeButton) {
            const $close = $('<button>')
                .attr('type', 'button')
                .attr('data-dismiss', 'modal')
                .attr('aria-hidden', 'true')
                .addClass('close')
                .html(`<span class="fas fa-times"></span>`);

            $el.append(
                $('<div>')
                    .addClass('close-container')
                    .append($close)
            );

            // @ts-ignore
            $close.on('click', () => $el.alert('close'));
        }

        if (timeout) {
            // @ts-ignore
            setTimeout(() => $el.alert('close'),
                timeout);
        }

        $el.appendTo('body')
    },

    /**
     * Show a warning message.
     *
     * @param message message.
     * @param [options] Options.
     */
    warning: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'warning', 2000, options);
    },

    /**
     * Show a success message.
     *
     * @param message A message.
     * @param [options] Options.
     */
    success: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'success', 2000, options);
    },

    /**
     * Show an error message.
     *
     * @param message A message.
     * @param [options] Options. If true, then only closeButton option will be applied.
     */
    error: function (message: string, options?: NotifyOptions | true) {
        options = typeof options === 'boolean' ?
            {closeButton: options} :
            {...options};

        const timeout = options.closeButton ? 0 : 4000;

        Ui.notify(message, 'danger', timeout, options);
    },

    /**
     * Show an info message.
     *
     * @param message A message.
     * @param [options] Options.
     */
    info: function (message: string, options?: NotifyOptions) {
        Ui.notify(message, 'info', 2000, options);
    },

    /**
     * Get the number of opened confirmation dialogues.
     * @internal
     */
    getConfirmCount(): number {
        return confirmCount;
    }
};

let confirmCount = 0;
let notifySuppressed = false;

Espo.Ui = Ui;

/**
 * @deprecated Use `Espo.Ui`.
 */
Espo.ui = Ui;

export default Ui;
