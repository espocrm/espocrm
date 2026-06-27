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

import {h, patch, VNode} from 'bullbone';
import {ButtonComponent, DropdownItemComponent} from 'components/controls';

const shownDialogList: Dialog[] = [];

/**
 * Dialog parameters.
 */
export interface DialogParams {
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
    dropdownItemList?: (DialogButton | false)[];
    fullHeight?: boolean;
    bodyDiffHeight?: number;
    screenWidthXs?: number;
    maximizeButton?: boolean;
    onMaximize?: () => void;
    onMinimize?: () => void;
    backdropClassName?: string;
    fixedHeaderHeight?: boolean;
    /**
     * @internal
     */
    fitHeight?: boolean;
}

/**
 * A button or dropdown action item.
 */
export interface DialogButton {
    name: string;
    pullLeft?: boolean;
    position?: 'left' | 'right';
    html?: string;
    text?: string;
    disabled?: boolean;
    hidden?: boolean;
    style?: 'default' | 'danger' | 'success' | 'warning' | 'info' | 'text';
    onClick?: (dialog: Dialog, event: MouseEvent) => void;
    className?: string;
    title?: string;
    groupIndex?: number;
    iconClass?: string;
}

/**
 * A dialog.
 *
 * @since 10.0.0
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
    private readonly activeElement: Element | null
    private readonly draggable: boolean = false
    private readonly container: string = 'body';
    private options: DialogParams
    private readonly keyboard: boolean = true
    private skipRemove: boolean = false

    private _backdropElement: HTMLElement
    private $el: JQuery
    private readonly el: HTMLElement
    private $mouseDownTarget: JQuery | undefined

    private footerComponent: FooterComponent

    private readonly footerElement: HTMLElement | null = null

    private footerVNode: VNode | null = null

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

        const hasFooter = !!(this.buttonList.length || this.dropdownItemList.length);

        this.footerComponent = new FooterComponent({
            dataProvider: () => ({
                buttonList: this.buttonList,
                dropdownItemList: this.dropdownItemList,
            })
        }, this);

        if (hasFooter) {
            this.footerElement = document.createElement('footer');

            this.footerVNode = patch(this.footerElement, this.footerComponent.node());
        }

        const $header = this.getHeader();

        const $body = $('<div>')
            .addClass('modal-body body')
            .html(this.body);

        const $content = $('<div>').addClass('modal-content');

        if ($header) {
            $content.append($header);
        }

        if (this.footerElement && this.options.footerAtTheTop) {
            $content.append(this.footerElement);
        }

        $content.append($body);

        if (this.footerElement && !this.options.footerAtTheTop) {
            $content.append(this.footerElement);
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
        this.el = this.$el.get(0) as HTMLElement;

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

            if (!options.fullHeight && options.bodyDiffHeight != null) {
                diffHeight = diffHeight + options.bodyDiffHeight;
            }

            if (this.fitHeight || options.fullHeight) {
                const processResize = () => {
                    const windowHeight = window.innerHeight;
                    const windowWidth = $window.width() as number;

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
                        if (
                            options.screenWidthXs != null &&
                            windowWidth <= options.screenWidthXs
                        ) {
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

                    this.el.querySelector('.modal-dialog')?.classList.add('maximized');

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

                    this.el.querySelector('.modal-dialog')?.classList.remove('maximized');

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
     * @internal
     */
    reRenderFooter() {
        if (!this.footerElement) {
            return;
        }

        this.footerVNode = patch(this.footerVNode ?? this.footerElement, this.footerComponent.node());
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

                dialog.getElement().parentElement?.classList.add('overlaid');
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
            last.getElement().parentElement?.classList.remove('overlaid')
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
                ($button.get(0) as HTMLElement).focus({preventScroll: true});

                return $button.get(0) as Element;
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
                    const activeElement = this.activeElement;

                    if (!(activeElement instanceof HTMLElement)) {
                        return;
                    }

                    const element = this._findClosestFocusableElement(activeElement);

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

export default Dialog;


class FooterComponent {

    constructor(
        private options: {
            dataProvider: () => {
                buttonList: DialogButton[];
                dropdownItemList: (DialogButton | false)[];
            }
        },
        private dialog: Dialog,
    ) {}

    protected getPreparedData() {
        const data = this.options.dataProvider();

        const buttonList = data.buttonList.filter(it => !it.hidden);

        const dropdownItemList = data.dropdownItemList
            .filter(it => it === false || !it.hidden)
            .filter((it, i, list) => {
                if (it === false && (i === 0 || i === list.length - 1)) {
                    return false;
                }

                if (it === false && list[i - 1] === false) {
                    return false;
                }

                return true;
            });

        return {buttonList, dropdownItemList};
    }

    node() {
        const data = this.getPreparedData();

        const elements: any[] = [];

        const left: any[] = [];
        const right: any[] = [];
        const lis: any[] = [];

        const leftButtonList: DialogButton[] = [];
        const rightButtonList: DialogButton[]  = [];

        data.buttonList.forEach(it => {
            it.pullLeft || it.position === 'right' ?
                rightButtonList.push(it) :
                leftButtonList.push(it);
        });

        const prepareButton = (it: DialogButton) => {
            return new ButtonComponent({
                name: it.name,
                className: it.className ?? 'btn-xs-wide',
                style: it.style,
                title: it.title,
                hidden: it.hidden,
                disabled: it.disabled,
                text: it.text,
                html: it.html,
                iconClass: it.iconClass,
                onClick: event => {
                    if (it.onClick) {
                        it.onClick(this.dialog, event);
                    }
                },
            }).node();
        };

        const prepareButtons = (buttonList: DialogButton[], output: any[]) => {
            buttonList.forEach((it) => {
                output.push(prepareButton(it));
            });
        }

        prepareButtons(leftButtonList, left);
        prepareButtons(rightButtonList, right);

        data.dropdownItemList.forEach(it => {
            if (it === false) {
                lis.push(
                    h('li', {class: {'divider': true}})
                );

                return;
            }

            lis.push(
                new DropdownItemComponent({
                    name: it.name,
                    title: it.title,
                    hidden: it.hidden,
                    disabled: it.disabled,
                    text: it.text,
                    html: it.html,
                    iconClass: it.iconClass,
                    onClick: event => {
                        if (it.onClick) {
                            it.onClick(this.dialog, event);
                        }
                    },
                }).node()
            )
        });

        if (lis.length) {
            left.push(
                h(
                    'button',
                    {
                        key: '_menu-dropdown-button',
                        attrs: {type: 'button'},
                        props: {
                            className: 'btn btn-default dropdown-toggle',
                        },
                        dataset: {toggle: 'dropdown'},
                    },
                    h('span', {props: {className: 'fas fa-ellipsis-h'}})
                ),
                h('ul', {props: {className: 'dropdown-menu pull-right'}}, lis)
            );
        }

        elements.push(
            h('div', {props: {className: 'btn-group main-btn-group'}}, left),
        );

        elements.push(
            h('div', {props: {className: 'btn-group additional-btn-group'}}, right),
        );

        return h('footer', {props: {className: 'modal-footer'}}, elements);
    }
}
