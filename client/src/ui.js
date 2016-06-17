/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

        var params = ['className', 'backdrop', 'keyboard', 'closeButton', 'header', 'body', 'width', 'height', 'fitHeight', 'buttons', 'removeOnClose', 'draggable', 'container', 'onRemove'];
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

        this.contents += '<div class="modal-body body">' + this.body + '</div>';

        if (this.buttons.length) {
            this.contents += '<footer class="modal-footer">';
            this.buttons.forEach(function (o) {
                this.contents +=
                    '<button type="button" ' +
                    'class="btn btn-' + (o.style || 'default') + (o.pullLeft ? ' pull-left' : '') + (o.disabled ? ' disabled' : '') + (o.hidden ? ' hidden' : '') + '" ' +
                    'data-name="' + o.name + '"' + (o.title ? ' title="'+o.title+'"' : '') + '>' +
                    (o.html || o.text) + '</button> ';
            }.bind(this));
            this.contents += '</footer>';
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
            if (this.fitHeight) {
                var processResize = function () {
                    var windowHeight = $window.height();
                    if (windowHeight < 512) {
                        this.$el.find('div.modal-body').css({
                            'maxHeight': 'none',
                            'overflow': 'auto'
                        });
                        return;
                    }
                    this.$el.find('div.modal-body').css({
                        'maxHeight': (windowHeight - 192) + 'px',
                        'overflow': 'auto'
                    });
                }.bind(this);
                $window.off('resize.modal-height');
                $window.on('resize.modal-height', processResize);
                processResize();
            }
        }.bind(this));
        this.$el.on('hidden.bs.modal', function (e) {
            if ($('.modal:visible').length > 0) {
                setTimeout(function() {
                    $(document.body).addClass('modal-open');
                }, 0);
            }
        });

    }
    Dialog.prototype.show = function () {
        this.$el.modal({
             backdrop: this.backdrop,
             keyboard: this.keyboard
        });
        this.$el.find('.modal-content').removeClass('hidden');

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
