/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('ui', [], function () {

    var Dialog = function (options) {
        options = options || {};

        this.className = 'dialog';
        this.backdrop = 'static';
        this.closeButton = true;
        this.header = false;
        this.body = '';
        this.width = false;
        this.height = false;
        this.buttons = [];
        this.removeOnClose = true;
        this.draggable = false;
        this.container = 'body'
        this.onRemove = function () {};

        this.options = options;

        var params = [
            'className',
            'backdrop',
            'keyboard',
            'closeButton',
            'header',
            'body',
            'width',
            'height',
            'fitHeight',
            'buttons',
            'removeOnClose',
            'draggable',
            'container',
            'onRemove'
        ];
        params.forEach(function (param) {
            if (param in options) {
                this[param] = options[param];
            }
        }.bind(this));

        this.id = 'dialog-' + Math.floor((Math.random() * 100000));

        this.contents = '';
        if (this.header) {
            this.contents += '<header class="modal-header">' +
                             ((this.closeButton) ? '<a href="javascript:" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></a>' : '') +
                             '<h4 class="modal-title">' + this.header + '</h4>' +
                             '</header>';
        }

        var body = '<div class="modal-body body">' + this.body + '</div>';

        var footer = '';

        if (this.buttons.length) {
            footer += '<footer class="modal-footer">';

            var rightPart = '';
            this.buttons.forEach(function (o) {
                if (o.pullLeft) return;
                var className = '';
                if (o.className) {
                    className = ' ' + o.className;
                }
                rightPart +=
                    '<button type="button" ' + (o.disabled ? 'disabled="disabled" ' : '') +
                    'class="btn btn-' + (o.style || 'default') + (o.disabled ? ' disabled' : '') + (o.hidden ? ' hidden' : '') + className+'" ' +
                    'data-name="' + o.name + '"' + (o.title ? ' title="'+o.title+'"' : '') + '>' +
                    (o.html || o.text) + '</button> ';
            }, this);
            var leftPart = '';
            this.buttons.forEach(function (o) {
                if (!o.pullLeft) return;
                var className = '';
                if (o.className) {
                    className = ' ' + o.className;
                }
                leftPart +=
                    '<button type="button" ' + (o.disabled ? 'disabled="disabled" ' : '') +
                    'class="btn btn-' + (o.style || 'default') + (o.disabled ? ' disabled' : '') + (o.hidden ? ' hidden' : '') + className+'" ' +
                    'data-name="' + o.name + '"' + (o.title ? ' title="'+o.title+'"' : '') + '>' +
                    (o.html || o.text) + '</button> ';
            }, this);
            if (leftPart !== '') {
                leftPart = '<div class="btn-group additional-btn-group">'+leftPart+'</div>';
                footer += leftPart;
            }
            if (rightPart !== '') {
                rightPart = '<div class="btn-group main-btn-group">'+rightPart+'</div>';
                footer += rightPart;
            }
            footer += '</footer>';
        }

        if (this.options.footerAtTheTop) {
            this.contents += footer + body;
        } else {
            this.contents += body + footer;
        }

        this.contents = '<div class="modal-dialog"><div class="modal-content">' + this.contents + '</div></div>'

        $('<div />').attr('id', this.id)
          .attr('class', this.className + ' modal')
          .attr('role', 'dialog')
          .attr('tabindex', '-1')
          .html(this.contents)
          .appendTo($(this.container));

        this.$el = $('#' + this.id);
        this.el = this.$el.get(0);

        this.$el.find('header a.close').on('click', function () {
            //this.close();
        }.bind(this));

        this.buttons.forEach(function (o) {
            if (typeof o.onClick == 'function') {
                $('#' + this.id + ' button[data-name="' + o.name + '"]').on('click', function () {
                    o.onClick(this);
                }.bind(this));
            }
        }.bind(this));

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
            this.$el.on('hidden.bs.modal', function (e) {
                if (this.$el.get(0) == e.target) {
                    this.remove();
                }
            }.bind(this));
        }

        $window = $(window);

        this.$el.on('shown.bs.modal', function (e) {
            $('.modal-backdrop').not('.stacked').addClass('stacked');
            var headerHeight = this.$el.find('header.modal-header').outerHeight();
            var footerHeight = this.$el.find('footer.modal-footer').outerHeight();

            var diffHeight = headerHeight + footerHeight;

            if (!options.fullHeight) {
                diffHeight = diffHeight + options.bodyDiffHeight;
            }

            if (this.fitHeight || options.fullHeight) {
                var processResize = function () {
                    var windowHeight = $window.height();
                    var windowWidth = $window.width();

                    if (!options.fullHeight && windowHeight < 512) {
                        this.$el.find('div.modal-body').css({
                            maxHeight: 'none',
                            overflow: 'auto',
                            height: 'none'
                        });
                        return;
                    }
                    var cssParams = {
                        overflow: 'auto'
                    };
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
                }.bind(this);
                $window.off('resize.modal-height');
                $window.on('resize.modal-height', processResize);
                processResize();
            }
        }.bind(this));

        var $body = $(document.body);

        this.$el.on('hidden.bs.modal', function (e) {
            if ($('.modal:visible').size() > 0) {
                $body.addClass('modal-open');
            }
        });

    }
    Dialog.prototype.show = function () {
        this.$el.modal({
             backdrop: this.backdrop,
             keyboard: this.keyboard
        });
        this.$el.find('.modal-content').removeClass('hidden');

        var $modalBackdrop = $('.modal-backdrop');
        $modalBackdrop.each(function (i, el) {
            if (i < $modalBackdrop.size() - 1) {
                $(el).addClass('hidden');
            }
        }.bind(this));

        var $modalConainer = $('.modal-container');
        $modalConainer.each(function (i, el) {
            if (i < $modalConainer.size() - 1) {
                $(el).addClass('overlaid');
            }
        }.bind(this));

        this.$el.off('click.dismiss.bs.modal');
        this.$el.on('click.dismiss.bs.modal', '> div.modal-dialog > div.modal-content > header [data-dismiss="modal"]', function () {
            this.close();
        }.bind(this));
        this.$el.on('click.dismiss.bs.modal', function (e) {
            if (e.target === e.currentTarget) {
                return this.backdrop == 'static' ? this.$el[0].focus() : this.close();
            }
        }.bind(this));
    };
    Dialog.prototype.hide = function () {
        this.$el.find('.modal-content').addClass('hidden');
    };
    Dialog.prototype.close = function () {
        var $modalBackdrop = $('.modal-backdrop');
        $modalBackdrop.last().removeClass('hidden');

        var $modalConainer = $('.modal-container');
        $($modalConainer.get($modalConainer.size() - 2)).removeClass('overlaid');

        this.$el.modal('hide');
        $(this).trigger('dialog:close');
    };
    Dialog.prototype.remove = function () {
        this.onRemove();
        this.$el.remove();
        $(this).off();
    };

    var Ui = Espo.Ui = Espo.ui = {

        Dialog: Dialog,

        confirm: function (message, o, callback, context) {
            var confirmText = o.confirmText;
            var cancelText = o.cancelText;
            var confirmStyle = o.confirmStyle || 'danger';

            var dialog = new Dialog({
                backdrop: false,
                header: false,
                className: 'dialog-confirm',
                body: '<span class="confirm-message">' + message + '</a>',
                buttons: [
                    {
                        text: ' ' + confirmText + ' ',
                        name: 'confirm',
                        onClick: function () {
                            if (context) {
                                callback.call(context);
                            } else {
                                callback();
                            }
                            dialog.close();
                        },
                        style: confirmStyle,
                        pullLeft: true
                    },
                    {
                        text: cancelText,
                        name: 'cancel',
                        onClick: function () {
                            dialog.close();
                            if (o.cancelCallback) {
                                if (context) {
                                    o.cancelCallback.call(context);
                                } else {
                                    o.cancelCallback();
                                }
                            }
                        },
                        pullRight: true
                    }
                ]
            });

            dialog.show();
        },

        dialog: function (options) {
            return new Dialog(options);
        },

        notify: function (message, type, timeout, closeButton) {
            $('#nofitication').remove();

            if (message) {
                type = type || 'warning';
                if (typeof closeButton == 'undefined') {
                    closeButton = false;
                }

                if (type == 'error') {
                    type = 'danger';
                }

                var el = $('<div class="alert alert-' + type + ' fade in" id="nofitication" />').css({
                    position: 'fixed',
                    top: '0px',
                    'z-index': 2000,
                }).html(message);

                if (closeButton) {
                    el.append('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
                }

                if (timeout) {
                    setTimeout(function () {
                        el.alert('close');
                    }, timeout);
                }

                el.appendTo('body');
                el.css("left", ($(window).width() - el.width()) / 2 + $(window).scrollLeft()  + "px");
            }
        },

        warning: function (message) {
            Espo.Ui.notify(message, 'warning', 2000);
        },

        success: function (message) {
            Espo.Ui.notify(message, 'success', 2000);
        },

        error: function (message) {
            Espo.Ui.notify(message, 'error', 2000);
        },

        info: function (message) {
            Espo.Ui.notify(message, 'info', 2000);
        },
    }

    return Ui;

});
