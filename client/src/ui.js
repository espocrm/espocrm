/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/**
 * @module ui
 */
define('ui', ['lib!marked', 'lib!dompurify'],
function (/** marked~ */marked, /** DOMPurify~ */ DOMPurify) {

    /**
     * Dialog parameters.
     *
     * @typedef {Object} module:ui.Dialog~Params
     *
     * @property {string} [className='dialog'] A class-name or multiple space separated.
     * @property {'static'|true|false} [static='static'] A backdrop.
     * @property {boolean} [closeButton=true] A close button.
     * @property {boolean} [collapseButton=false] A collapse button.
     * @property {string|null} [header] A header HTML.
     * @property {string} [body] A body HTML.
     * @property {number|null} [width] A width.
     * @property {boolean} [removeOnClose=true] To remove on close.
     * @property {boolean} [draggable=false] Is draggable.
     * @property {function (): void} [onRemove] An on-remove callback.
     * @property {function (): void} [onClose] An on-close callback.
     * @property {function (): void} [onBackdropClick] An on-backdrop-click callback.
     * @property {string} [container='body'] A container selector.
     * @property {boolean} [keyboard=true] Enable a keyboard control. The `Esc` key closes a dialog.
     * @property {boolean} [footerAtTheTop=false] To display a footer at the top.
     * @property {module:ui.Dialog~Button[]} [buttonList] Buttons.
     * @property {module:ui.Dialog~Button[]} [dropdownItemList] Dropdown action items.
     */

    /**
     * A button or dropdown action item.
     *
     * @typedef {Object} module:ui.Dialog~Button
     *
     * @property {string} name A name.
     * @property {boolean} [pullLeft=false] Deprecated. Use the `position` property.
     * @property {'left'|'right'} [position='left'] A position.
     * @property {string} [html] HTML.
     * @property {string} [text] A text.
     * @property {boolean} [disabled=false] Disabled.
     * @property {boolean} [hidden=false] Hidden.
     * @property {'default'|'danger'|'success'|'warning'} [style='default'] A style.
     * @property {function():void} [onClick] An on-click callback.
     * @property {string} [className] An additional class name.
     * @property {string} [title] A title.
     */

    /**
     * @class
     * @name Espo.Ui.Dialog
     *
     * @param {module:ui.Dialog~Params} options Options.
     */
    let Dialog = function (options) {
        options = options || {};

        /** @private */
        this.className = 'dialog';
        /** @private */
        this.backdrop = 'static';
        /** @private */
        this.closeButton = true;
        /** @private */
        this.collapseButton = false;
        /** @private */
        this.header = null;
        /** @private */
        this.body = '';
        /** @private */
        this.width = null;
        /**
         * @private
         * @type {module:ui.Dialog~Button[]}
         */
        this.buttonList = [];
        /**
         * @private
         * @type {module:ui.Dialog~Button[]}
         */
        this.dropdownItemList = [];
        /** @private */
        this.removeOnClose = true;
        /** @private */
        this.draggable = false;
        /** @private */
        this.container = 'body';
        /** @private */
        this.onRemove = function () {};
        /** @private */
        this.onClose = function () {};
        /** @private */
        this.options = options;
        /** @private */
        this.onBackdropClick = function () {};
        /** @private */
        this.keyboard = true;

        this.activeElement = document.activeElement;

        let params = [
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
        ];

        params.forEach(param => {
            if (param in options) {
                this[param] = options[param];
            }
        });

        /** @private */
        this.onCloseIsCalled = false;

        if (this.buttons && this.buttons.length) {
            /**
             * @private
             * @type {module:ui.Dialog~Button[]}
             */
            this.buttonList = this.buttons;
        }

        this.id = 'dialog-' + Math.floor((Math.random() * 100000));

        if (typeof this.backdrop === 'undefined') {
            /** @private */
            this.backdrop = 'static';
        }

        let $header = this.getHeader();
        let $footer = this.getFooter();

        let $body = $('<div>')
            .addClass('modal-body body')
            .html(this.body);

        let $content = $('<div>').addClass('modal-content');

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

        let $dialog = $('<div>')
            .addClass('modal-dialog')
            .append($content);

        let $container = $(this.container);

        $('<div>')
            .attr('id', this.id)
            .attr('class', this.className + ' modal')
            .attr('role', 'dialog')
            .attr('tabindex', '-1')
            .append($dialog)
            .appendTo($container);

        /**
         * An element.
         *
         * @type {JQuery}
         */
        this.$el = $('#' + this.id);

        /**
         * @private
         * @type {Element}
         */
        this.el = this.$el.get(0);

        this.$el.find('header a.close').on('click', () => {
            //this.close();
        });

        this.initButtonEvents();

        if (this.draggable) {
            this.$el.find('header').css('cursor', 'pointer');

            this.$el.draggable({
                handle: 'header',
            });
        }

        let modalContentEl = this.$el.find('.modal-content');

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

        let $window = $(window);

        this.$el.on('shown.bs.modal', (e, r) => {
            $('.modal-backdrop').not('.stacked').addClass('stacked');

            let headerHeight = this.$el.find('.modal-header').outerHeight() || 0;
            let footerHeight = this.$el.find('.modal-footer').outerHeight() || 0;

            let diffHeight = headerHeight + footerHeight;

            if (!options.fullHeight) {
                diffHeight = diffHeight + options.bodyDiffHeight;
            }

            if (this.fitHeight || options.fullHeight) {
                let processResize = () => {
                    let windowHeight = window.innerHeight;
                    let windowWidth = $window.width();

                    if (!options.fullHeight && windowHeight < 512) {
                        this.$el.find('div.modal-body').css({
                            maxHeight: 'none',
                            overflow: 'auto',
                            height: 'none',
                        });

                        return;
                    }

                    let cssParams = {
                        overflow: 'auto',
                    };

                    if (options.fullHeight) {
                        cssParams.height = (windowHeight - diffHeight) + 'px';

                        this.$el.css('paddingRight', 0);
                    }
                    else {
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

        let $documentBody = $(document.body);

        this.$el.on('hidden.bs.modal', e => {
            if ($('.modal:visible').length > 0) {
                $documentBody.addClass('modal-open');
            }
        });
    };

    /**
     * @private
     */
    Dialog.prototype.initButtonEvents = function () {
        this.buttonList.forEach(o => {
            if (typeof o.onClick === 'function') {
                $('#' + this.id + ' .modal-footer button[data-name="' + o.name + '"]')
                    .on('click', (e) => {
                        o.onClick(this, e);
                    });
            }
        });

        this.dropdownItemList.forEach(o => {
            if (typeof o.onClick === 'function') {
                $('#' + this.id + ' .modal-footer a[data-name="' + o.name + '"]')
                    .on('click', (e) => {
                        o.onClick(this, e);
                    });
            }
        });
    };

    /**
     * @private
     * @return {JQuery|null}
     */
    Dialog.prototype.getHeader = function () {
        if (!this.header) {
            return null;
        }

        let $header = $('<header />')
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
     * @private
     * @return {JQuery|null}
     */
    Dialog.prototype.getFooter = function () {
        if (!this.buttonList.length && !this.dropdownItemList.length) {
            return null;
        }

        let $footer = $('<footer>').addClass('modal-footer');

        let $main = $('<div>')
            .addClass('btn-group')
            .addClass('main-btn-group');

        let $additional = $('<div>')
            .addClass('btn-group')
            .addClass('additional-btn-group');

        this.buttonList.forEach(/** module:ui.Dialog~Button */o => {
            let style = o.style || 'default';

            let $button =
                $('<button>')
                    .attr('type', 'button')
                    .attr('data-name', o.name)
                    .addClass('btn')
                    .addClass('btn-' + style)
                    .addClass(o.className || 'btn-xs-wide')

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

        let allDdItemsHidden = this.dropdownItemList.filter(o => !o.hidden).length === 0;

        let $dropdown = $('<div>')
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

        let $ul = $('<ul>').addClass('dropdown-menu pull-right');

        $dropdown.append($ul);

        this.dropdownItemList.forEach(/** module:ui.Dialog~Button */o => {
            let $a = $('<a>')
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

            let $li = $('<li>')
                .addClass(o.hidden ? ' hidden' : '')
                .append($a)

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
    Dialog.prototype.show = function () {
        this.$el.modal({
             backdrop: this.backdrop,
             keyboard: this.keyboard,
        });

        this.$el.find('.modal-content').removeClass('hidden');

        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.each((i, el) => {
            if (i < $modalBackdrop.length - 1) {
                $(el).addClass('hidden');
            }
        });

        let $modalContainer = $('.modal-container');

        $modalContainer.each((i, el) => {
            if (i < $modalContainer.length - 1) {
                $(el).addClass('overlaid');
            }
        });

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

            this.onBackdropClick();

            if (this.backdrop === 'static') {
                return;
            }

            this.close();
        });

        $('body > .popover').addClass('hidden');
    };

    /**
     * Hide.
     */
    Dialog.prototype.hide = function () {
        this.$el.find('.modal-content').addClass('hidden');
    };

    /**
     * Hide with a backdrop.
     */
    Dialog.prototype.hideWithBackdrop = function () {
        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.last().addClass('hidden');
        $($modalBackdrop.get($modalBackdrop.length - 2)).removeClass('hidden');

        let $modalContainer = $('.modal-container');

        $($modalContainer.get($modalContainer.length - 2)).removeClass('overlaid');

        this.skipRemove = true;

        setTimeout(() => {
            this.skipRemove = false;
        }, 50);

        this.$el.modal('hide');
        this.$el.find('.modal-content').addClass('hidden');
    };

    /**
     * @private
     */
    Dialog.prototype._close = function () {
        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.last().removeClass('hidden');

        let $modalContainer = $('.modal-container');

        $($modalContainer.get($modalContainer.length - 2)).removeClass('overlaid');
    };

    /**
     * @private
     * @param {Element} element
     * @return {Element|null}
     */
    Dialog.prototype._findClosestFocusableElement = function (element) {
        let isVisible = !!(
            element.offsetWidth ||
            element.offsetHeight ||
            element.getClientRects().length
        );

        if (isVisible) {
            element.focus({preventScroll: true});

            return element;
        }

        let $element = $(element);

        if ($element.closest('.dropdown-menu').length) {
            let $button = $element.closest('.btn-group').find(`[data-toggle="dropdown"]`);

            if ($button.length) {
                $button.get(0).focus({preventScroll: true});

                return $button.get(0);
            }
        }

        return null;
    };

    /**
     * Close.
     */
    Dialog.prototype.close = function () {
        if (!this.onCloseIsCalled) {
            this.onClose();
            this.onCloseIsCalled = true;

            if (this.activeElement) {
                setTimeout(() => {
                    let element = this._findClosestFocusableElement(this.activeElement);

                    if (element) {
                        element.focus({preventScroll: true});
                    }
                }, 50);
            }
        }

        this._close();
        this.$el.modal('hide');
        $(this).trigger('dialog:close');
    };

    /**
     * Remove.
     */
    Dialog.prototype.remove = function () {
        this.onRemove();

        // Hack allowing multiple backdrops.
        // `close` function may be called twice.
        this._close();
        this.$el.remove();

        $(this).off();
        $(window).off('resize.modal-height');
    };

    /**
     * UI utils.
     */
    Espo.Ui = {

        Dialog: Dialog,

        /**
         * @typedef {Object} Espo.Ui~ConfirmOptions
         *
         * @property {string} confirmText A confirm-button text.
         * @property {string} cancelText A cancel-button text.
         * @property {'danger'|'success'|'warning'|'default'} [confirmStyle='danger']
         *   A confirm-button style.
         * @property {'static'|boolean} [backdrop=false] A backdrop.
         * @property {function():void} [cancelCallback] A cancel-callback.
         * @property {boolean} [isHtml=false] Whether the message is HTML.
         */

        /**
         * Show a confirmation dialog.
         *
         * @param {string} message A message.
         * @param {Espo.Ui~ConfirmOptions|{}} o Options.
         * @param {function} [callback] Deprecated. Use a promise.
         * @param {Object} [context] Deprecated.
         * @returns {Promise} Resolves if confirmed.
         */
        confirm: function (message, o, callback, context) {
            o = o || {};

            let confirmText = o.confirmText;
            let cancelText = o.cancelText;
            let confirmStyle = o.confirmStyle || 'danger';
            let backdrop = o.backdrop;

            if (typeof backdrop === 'undefined') {
                backdrop = false;
            }

            let isResolved = false;

            let processCancel = () => {
                if (!o.cancelCallback) {
                    return;
                }

                if (context) {
                    o.cancelCallback.call(context);

                    return;
                }

                o.cancelCallback();
            };

            if (!o.isHtml) {
                message = Handlebars.Utils.escapeExpression(message);
            }

            return new Promise(resolve => {
                let dialog = new Dialog({
                    backdrop: backdrop,
                    header: false,
                    className: 'dialog-confirm',
                    body: '<span class="confirm-message">' + message + '</a>',
                    buttonList: [
                        {
                            text: ' ' + confirmText + ' ',
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
                        if (isResolved) {
                            return;
                        }

                        processCancel();
                    },
                });

                dialog.show();
                dialog.$el.find('button[data-name="confirm"]').focus();
            });
        },

        /**
         * Create a dialog.
         *
         * @param {module:ui.Dialog~Params} options Options.
         * @returns {Dialog}
         */
        dialog: function (options) {
            return new Dialog(options);
        },


        /**
         * Popover options.
         *
         * @typedef {Object} Espo.Ui~PopoverOptions
         *
         * @property {'bottom'|'top'} [placement='bottom'] A placement.
         * @property {string|JQuery} [container] A container selector.
         * @property {string} [content] An HTML content.
         * @property {string} [text] A text.
         * @property {'manual'|'click'|'hover'|'focus'} [trigger='manual'] A trigger type.
         * @property {boolean} [noToggleInit=false] Skip init toggle on click.
         * @property {boolean} [preventDestroyOnRender=false] Don't destroy on re-render.
         * @property {function(): void} [onShow] On-show callback.
         * @property {function(): void} [onHide] On-hide callback.
         */

        /**
         * Init a popover.
         *
         * @param {JQuery} $el An element.
         * @param {Espo.Ui~PopoverOptions} o Options.
         * @param {module:view} [view] A view.
         */
        popover: function ($el, o, view) {
            let $body = $('body')
            let content = o.content || Handlebars.Utils.escapeExpression(o.text || '');
            let isShown = false;

            let container = o.container;

            if (!container) {
                let $modalBody = $el.closest('.modal-body');

                container = $modalBody.length ? $modalBody : 'body';
            }

            $el
                .popover({
                    placement: o.placement || 'bottom',
                    container: container,
                    viewport: container,
                    html: true,
                    content: content,
                    trigger: o.trigger || 'manual',
                })
                .on('shown.bs.popover', () => {
                    isShown = true;

                    if (!view) {
                        return;
                    }

                    $body.off('click.popover-' + view.cid);

                    $body.on('click.popover-' + view.cid, e => {
                        if ($(e.target).closest('.popover-content').get(0)) {
                            return;
                        }

                        if ($.contains($el.get(0), e.target)) {
                            return;
                        }

                        if ($el.get(0) === e.target) {
                            return;
                        }

                        $body.off('click.popover-' + view.cid);
                        $el.popover('hide');
                    });

                    if (o.onShow) {
                        o.onShow();
                    }
                })
                .on('hidden.bs.popover', () => {
                    isShown = false;

                    if (o.onHide) {
                        o.onHide();
                    }
                });

            if (!o.noToggleInit) {
                $el.on('click', () => {
                    $el.popover('toggle');
                });
            }

            if (view) {
                let hide = () => {
                    if (!isShown) {
                        return;
                    }

                    $el.popover('hide');
                };

                let destroy = () => {
                    $el.popover('destroy');
                    $body.off('click.popover-' + view.cid);

                    view.off('remove', destroy);
                    view.off('render', destroy);
                    view.off('render', hide);
                };

                view.once('remove', destroy);

                if (!o.preventDestroyOnRender) {
                    view.once('render', destroy);
                }

                if (o.preventDestroyOnRender) {
                    view.on('render', hide);
                }
            }
        },

        /**
         * Show a notify-message.
         *
         * @param {string|false} message A message. False removes an already displayed message.
         * @param {'warning'|'danger'|'success'|'info'} [type='warning'] A type.
         * @param {number} [timeout] Microseconds. If empty, then won't be hidden.
         *   Should be hidden manually or by displaying another message.
         * @param {boolean} [closeButton] A close button.
         */
        notify: function (message, type, timeout, closeButton) {
            $('#notification').remove();

            if (!message) {
                return;
            }

            let parsedMessage = message.indexOf('\n') !== -1 ?
                marked.parse(message) :
                marked.parseInline(message);

            let sanitizedMessage = DOMPurify.sanitize(parsedMessage).toString();

            type = type || 'warning';
            closeButton = closeButton || false;

            if (type === 'error') {
                // For bc.
                type = 'danger';
            }

            let additionalClassName = closeButton ? ' alert-closable' : '';

            let $el = $('<div>')
                .addClass('alert alert-' + type + additionalClassName + ' fade in')
                .attr('id', 'notification')
                .css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '50%',
                    'transform': 'translate(-50%, 0)',
                    'z-index': 2000,
                })
                .append(
                    $('<div>')
                        .addClass('message')
                        .html(sanitizedMessage)
                );

            if (closeButton) {
                let $close = $('<button>')
                    .attr('type', 'button')
                    .attr('data-dismiss', 'modal')
                    .attr('aria-hidden', 'true')
                    .addClass('close')
                    .html('&times;');

                $el.append(
                    $('<div>')
                        .addClass('close-container')
                        .append($close)
                );

                $close.on('click', () => $el.alert('close'));
            }

            if (timeout) {
                setTimeout(() => $el.alert('close'), timeout);
            }

            $el.appendTo('body')
        },

        /**
         * Show a warning message.
         *
         * @param {string} message A message.
         */
        warning: function (message) {
            Espo.Ui.notify(message, 'warning', 2000);
        },

        /**
         * Show a success message.
         *
         * @param {string} message A message.
         */
        success: function (message) {
            Espo.Ui.notify(message, 'success', 2000);
        },

        /**
         * Show an error message.
         *
         * @param {string} message A message.
         * @param {boolean} [closeButton] A close button.
         */
        error: function (message, closeButton) {
            closeButton = closeButton || false;
            let timeout = closeButton ? 0 : 4000;

            Espo.Ui.notify(message, 'danger', timeout, closeButton);
        },

        /**
         * Show an info message.
         *
         * @param {string} message A message.
         */
        info: function (message) {
            Espo.Ui.notify(message, 'info', 2000);
        },
    };

    /**
     * @deprecated Use `Espo.Ui`.
     */
    Espo.ui = Espo.Ui;

    return Espo.Ui;
});
