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

define('ui', [], function () {

    var Dialog = function (options) {
        options = options || {};

        this.className = 'dialog';
        this.backdrop = 'static';
        this.closeButton = true;
        this.collapseButton = false;
        this.header = false;
        this.body = '';
        this.width = false;
        this.height = false;
        this.buttonList = [];
        this.dropdownItemList = [];
        this.removeOnClose = true;
        this.draggable = false;
        this.container = 'body';
        this.onRemove = function () {};
        this.onClose = function () {};

        this.options = options;

        var params = [
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
        ];

        params.forEach(param => {
            if (param in options) {
                this[param] = options[param];
            }
        });

        this.onCloseIsCalled = false;

        if (this.buttons && this.buttons.length) {
            this.buttonList = this.buttons;
        }

        this.id = 'dialog-' + Math.floor((Math.random() * 100000));

        if (typeof this.backdrop === 'undefined') {
            this.backdrop = 'static';
        }

        let contentsHtml = '';

        if (this.header) {
            let headerClassName = '';

            if (this.options.fixedHeaderHeight) {
                headerClassName = ' fixed-height';
            }

            let $header = $('<header />').addClass('modal-header ' + headerClassName);

            $header.append(
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
                    $('<a />')
                        .addClass('collapse-button')
                        .attr('href', 'javascript:')
                        .attr('data-action', 'collapseModal')
                        .append(
                            $('<span />')
                                .addClass('fas fa-minus')
                        )
                );
            }

            if (this.closeButton) {
                $header.prepend(
                    $('<a />')
                        .addClass('close')
                        .attr('data-dismiss', 'modal')
                        .attr('href', 'javascript:')
                        .append(
                            $('<span />')
                                .attr('aria-hidden', 'true')
                                .html('&times;')
                        )
                );
            }

            contentsHtml += $header.wrap('<div />').parent().html();
        }

        let bodyHtml = '<div class="modal-body body">' + this.body + '</div>';

        let footerHtml = this.getFooterHtml();

        if (footerHtml !== '') {
            footerHtml = '<footer class="modal-footer">' + footerHtml + '</footer>';
        }

        if (this.options.footerAtTheTop) {
            contentsHtml += footerHtml + bodyHtml;
        }
        else {
            contentsHtml += bodyHtml + footerHtml;
        }

        contentsHtml = '<div class="modal-dialog"><div class="modal-content">' + contentsHtml + '</div></div>';

        $('<div />')
            .attr('id', this.id)
            .attr('class', this.className + ' modal')
            .attr('role', 'dialog')
            .attr('tabindex', '-1')
            .html(contentsHtml)
            .appendTo($(this.container));

        this.$el = $('#' + this.id);
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

        var modalContentEl = this.$el.find('.modal-content');

        if (this.width) {
            modalContentEl.css('width', this.width);
            modalContentEl.css('margin-left', '-' + (parseInt(this.width.replace('px', '')) / 5) + 'px');
        }

        if (this.removeOnClose) {
            this.$el.on('hidden.bs.modal', e => {
                if (this.$el.get(0) === e.target) {

                    if (this.skipRemove) {
                        return;
                    }

                    this.remove();
                }
            });
        }

        $window = $(window);

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

        let $body = $(document.body);

        this.$el.on('hidden.bs.modal', e => {
            if ($('.modal:visible').length > 0) {
                $body.addClass('modal-open');
            }
        });
    };

    Dialog.prototype.initButtonEvents = function () {
        this.buttonList.forEach(o => {
            if (typeof o.onClick === 'function') {
                $('#' + this.id + ' .modal-footer button[data-name="' + o.name + '"]')
                    .on('click', () => {
                        o.onClick(this);
                    });
            }
        });

        this.dropdownItemList.forEach(o => {
            if (typeof o.onClick === 'function') {
                $('#' + this.id + ' .modal-footer a[data-name="' + o.name + '"]')
                    .on('click', () => {
                        o.onClick(this);
                    });
            }
        });
    };

    Dialog.prototype.getFooterHtml = function () {
        let footer = '';

        if (this.buttonList.length || this.dropdownItemList.length) {
            let rightPart = '';

            this.buttonList.forEach(o => {
                if (o.pullLeft) {
                    return;
                }

                let className = ' btn-xs-wide';

                if (o.className) {
                    className = ' ' + o.className;
                }

                rightPart +=
                    '<button type="button" ' + (o.disabled ? 'disabled="disabled" ' : '') +
                    'class="btn btn-' + (o.style || 'default') + (o.disabled ? ' disabled' : '') +
                    (o.hidden ? ' hidden' : '') + className+'" ' +
                    'data-name="' + o.name + '"' + (o.title ? ' title="'+o.title+'"' : '') + '>' +
                    (o.html || o.text) + '</button> ';
            });

            let leftPart = '';

            this.buttonList.forEach(o => {
                if (!o.pullLeft) {
                    return;
                }

                let className = ' btn-xs-wide';

                if (o.className) {
                    className = ' ' + o.className;
                }

                leftPart +=
                    '<button type="button" ' + (o.disabled ? 'disabled="disabled" ' : '') +
                    'class="btn btn-' + (o.style || 'default') + (o.disabled ? ' disabled' : '') +
                    (o.hidden ? ' hidden' : '') + className+'" ' +
                    'data-name="' + o.name + '"' + (o.title ? ' title="'+o.title+'"' : '') + '>' +
                    (o.html || o.text) + '</button> ';
            });

            if (leftPart !== '') {
                leftPart = '<div class="btn-group additional-btn-group">'+leftPart+'</div>';

                footer += leftPart;
            }

            if (this.dropdownItemList.length) {
                let visibleCount = 0;

                this.dropdownItemList.forEach(function (o) {
                    if (!o.hidden) {
                        visibleCount++;
                    }
                });

                rightPart += '<div class="btn-group'+ ((visibleCount === 0) ? ' hidden' : '') +'">';
                rightPart += '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">';
                rightPart += '<span class="fas fa-ellipsis-h"></span>';
                rightPart += '</button>';

                rightPart += '<ul class="dropdown-menu pull-right">';

                this.dropdownItemList.forEach(o => {
                    rightPart +=
                        '<li class="'+(o.hidden ? ' hidden' : '')+'">' +
                        '<a href="javascript:" data-name="'+o.name+'">'+(o.html || o.text)+'</a></li>';
                });

                rightPart += '</ul>';
                rightPart += '</div>';
            }

            if (rightPart !== '') {
                rightPart = '<div class="btn-group main-btn-group">'+rightPart+'</div>';
                footer += rightPart;
            }
        }

        return footer;
    };

    Dialog.prototype.show = function () {
        this.$el.modal({
             backdrop: this.backdrop,
             keyboard: this.keyboard
        });

        this.$el.find('.modal-content').removeClass('hidden');

        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.each((i, el) => {
            if (i < $modalBackdrop.length - 1) {
                $(el).addClass('hidden');
            }
        });

        let $modalConainer = $('.modal-container');

        $modalConainer.each((i, el) => {
            if (i < $modalConainer.length - 1) {
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

            if (this.backdrop === 'static') {
                return;
            }

            if (
                this.$mouseDownTarget &&
                this.$mouseDownTarget.closest('.modal-content').length
            ) {
                return;
            }

            this.close();
        });

        $('body > .popover').addClass('hidden');
    };

    Dialog.prototype.hide = function () {
        this.$el.find('.modal-content').addClass('hidden');
    };

    Dialog.prototype.hideWithBackdrop = function () {
        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.last().addClass('hidden');

        $($modalBackdrop.get($modalBackdrop.length - 2)).removeClass('hidden');

        let $modalConainer = $('.modal-container');

        $($modalConainer.get($modalConainer.length - 2)).removeClass('overlaid');

        this.skipRemove = true;

        setTimeout(() => {
            this.skipRemove = false;
        }, 50);

        this.$el.modal('hide');

        this.$el.find('.modal-content').addClass('hidden');
    };

    Dialog.prototype._close = function () {
        let $modalBackdrop = $('.modal-backdrop');

        $modalBackdrop.last().removeClass('hidden');

        let $modalConainer = $('.modal-container');

        $($modalConainer.get($modalConainer.length - 2)).removeClass('overlaid');
    };

    Dialog.prototype.close = function () {
        if (!this.onCloseIsCalled) {
            this.onClose();

            this.onCloseIsCalled = true;
        }

        this._close();

        this.$el.modal('hide');

        $(this).trigger('dialog:close');
    };

    Dialog.prototype.remove = function () {
        this.onRemove();

        // Hack allowing multiple backdrops.
        // `close` function may be called twice.
        this._close();

        this.$el.remove();

        $(this).off();
        $(window).off('resize.modal-height');
    };

    let Ui = Espo.Ui = Espo.ui = {

        Dialog: Dialog,

        confirm: function (message, o, callback, context) {
            o = o || {};

            var confirmText = o.confirmText;
            var cancelText = o.cancelText;
            var confirmStyle = o.confirmStyle || 'danger';

            var backdrop = o.backdrop;

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
                            pullLeft: true,
                        },
                        {
                            text: cancelText,
                            name: 'cancel',
                            onClick: () => {
                                isResolved = true;

                                dialog.close();

                                processCancel();
                            },
                            pullRight: true,
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

        dialog: function (options) {
            return new Dialog(options);
        },

        popover: function ($el, o, view) {
            $el.popover({
                placement: o.placement || 'bottom',
                container: o.container || 'body',
                html: true,
                content: o.content || o.text,
                trigger: o.trigger || 'manual',
            }).on('shown.bs.popover', function () {
                if (view) {
                    $('body').off('click.popover-' + view.cid);

                    $('body').on('click.popover-' + view.cid, function (e) {
                        if ($(e.target).closest('.popover-content').get(0)) {
                            return;
                        }

                        if ($.contains($el.get(0), e.target)) {
                            return;
                        }
                        if ($el.get(0) === e.target) {
                            return;
                        }

                        $('body').off('click.popover-' + view.cid);

                        $el.popover('hide');
                    });
                }
            });

            if (!o.noToggleInit) {
                $el.on('click', function () {
                    $(this).popover('toggle');
                });
            }

            if (view) {
                view.on('remove', function () {
                    $el.popover('destroy');
                    $('body').off('click.popover-' + view.cid);
                });

                view.on('render', function () {
                    $el.popover('destroy');
                    $('body').off('click.popover-' + view.cid);
                });
            }
        },

        notify: function (message, type, timeout, closeButton) {
            $('#nofitication').remove();

            if (!message) {
                return;
            }

            message = message.replace(/\n|\r\n|\r/g, '<br/>');

            type = type || 'warning';
            closeButton = closeButton || false;

            if (type === 'error') {
                type = 'danger';
            }

            let additionalClassName = closeButton ? ' alert-closable' : '';

            let $el = $(
                    '<div class="alert alert-' + type + '' + additionalClassName + ' fade in" id="nofitication" />'
                )
                .css({
                    position: 'fixed',
                    top: '0px',
                    'z-index': 2000,
                })
                .html('<div class="message">' + message + '</div>');

            if (closeButton) {
                $close = $(
                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
                );

                $btnContainer = $('<div class="close-container">').append($close);

                $el.append($btnContainer);

                $close.on('click', () => $el.alert('close'));
            }

            if (timeout) {
                setTimeout(() => $el.alert('close'), timeout);
            }

            $el
                .appendTo('body')
                .css(
                    'left',
                    ($(window).width() - $el.width()) / 2 + $(window).scrollLeft()  + "px"
                );
        },

        warning: function (message) {
            Espo.Ui.notify(message, 'warning', 2000);
        },

        success: function (message) {
            Espo.Ui.notify(message, 'success', 2000);
        },

        error: function (message, closeButton) {
            closeButton = closeButton || false;
            timeout = closeButton ? 0 : 4000;

            Espo.Ui.notify(message, 'error', timeout, closeButton);
        },

        info: function (message) {
            Espo.Ui.notify(message, 'info', 2000);
        },
    };

    return Ui;
});
