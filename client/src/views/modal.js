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

Espo.define('views/modal', 'view', function (Dep) {

    return Dep.extend({

        cssName: 'modal-dialog',

        header: false,

        dialog: null,

        containerSelector: null,

        scope: null,

        backdrop: 'static',

        buttonList: [],

        // TODO remove it as depricated
        buttons: [],

        width: false,

        fitHeight: false,

        escapeDisabled: false,

        isDraggable: false,

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    if (typeof this[method] == 'function') {
                        e.preventDefault();
                        this[method].call(this, data);
                    }
                }
            }
        },

        init: function () {
            var id = this.cssName + '-container-' + Math.floor((Math.random() * 10000) + 1).toString();
            var containerSelector = this.containerSelector = '#' + id;

            this.header = this.options.header || this.header;

            this.options = this.options || {};

            this.setSelector(this.containerSelector);

            this.buttonList = Espo.Utils.cloneDeep(this.buttonList);

            // TODO remove it as depricated
            this.buttons = Espo.Utils.cloneDeep(this.buttons);

            this.on('render', function () {
                if (this.dialog) {
                    this.dialog.close();
                }

                $(containerSelector).remove();
                $('<div />').css('display', 'none').attr('id', id).appendTo('body');

                var buttonListExt = [];

                // TODO remove it as depricated
                this.buttons.forEach(function (item) {
                    var o = Espo.Utils.clone(item);
                    if (!('text' in o) && ('label' in o)) {
                        o.text = this.getLanguage().translate(o.label);
                    }
                    buttonListExt.push(o);
                }, this);


                this.buttonList.forEach(function (item) {
                    var o = {};

                    if (typeof item === 'string') {
                        o.name = item;
                    } else if (typeof item === 'object') {
                        o = item;
                    } else {
                        return;
                    }

                    var text = o.text;
                    if (!o.text) {
                        if ('label' in o) {
                            o.text = this.translate(o.label, 'labels', this.scope)
                        } else {
                            o.text = this.translate(o.name, 'modalActions', this.scope);
                        }
                    }
                    o.onClick = o.onClick || (this['action' + Espo.Utils.upperCaseFirst(o.name)] || function () {}).bind(this);

                    buttonListExt.push(o);
                }, this);

                this.dialog = new Espo.Ui.Dialog({
                    backdrop: this.backdrop,
                    header: this.header,
                    container: containerSelector,
                    body: '',
                    buttons: buttonListExt,
                    width: this.width,
                    keyboard: !this.escapeDisabled,
                    fitHeight: this.fitHeight,
                    draggable: this.isDraggable,
                    onRemove: function () {
                        this.onDialogClose();
                    }.bind(this)
                });
                this.setElement(containerSelector + ' .body');
            }, this);

            this.on('after:render', function () {
                $(containerSelector).show();
                this.dialog.show();
            });

            this.once('remove', function () {
                if (this.dialog) {
                    this.dialog.close();
                }
                $(containerSelector).remove();
            });
        },

        onDialogClose: function () {
            if (!this.isBeingRendered()) {
                this.trigger('close');
                this.remove();
            }
        },

        actionCancel: function () {
            this.trigger('cancel');
            this.dialog.close();
        },

        actionClose: function () {
            this.trigger('cancel');
            this.dialog.close();
        },

        close: function () {
            this.dialog.close();
        },

        disableButton: function (name) {
            this.buttonList.forEach(function (d) {
                if (d.name !== name) return;
                d.disabled = true;
            }, this);
            if (!this.isRendered()) return;
            this.$el.find('footer button[data-name="'+name+'"]').addClass('disabled');
        },

        enableButton: function (name) {
            this.buttonList.forEach(function (d) {
                if (d.name !== name) return;
                d.disabled = false;
            }, this);
            if (!this.isRendered()) return;
            this.$el.find('footer button[data-name="'+name+'"]').removeClass('disabled');
        },

        addButton: function (o, toBeginnig, doNotReRender) {
            var index = -1;
            this.buttonList.forEach(function (item, i) {
                if (item.name === o.name) {
                    index = i;
                }
            }, this);
            if (~index) return;

            if (toBeginnig) {
                this.buttonList.unshift(o);
            } else {
                this.buttonList.push(o);
            }

            if (!doNotReRender && this.isRendered()) {
                this.reRender();
            }
        },

        removeButton: function (name, doNotReRender) {
            var index = -1;
            this.buttonList.forEach(function (item, i) {
                if (item.name === name) {
                    index = i;
                }
            }, this);
            if (~index) {
                this.buttonList.splice(index, 1);
            }

            if (!doNotReRender && this.isRendered()) {
                this.reRender();
            }
        },

        showButton: function (name) {
            this.buttonList.forEach(function (d) {
                if (d.name !== name) return;
                d.hidden = false;
            }, this);
            if (!this.isRendered()) return;
            this.$el.find('footer button[data-name="'+name+'"]').removeClass('hidden');
        },

        hideButton: function (name) {
            this.buttonList.forEach(function (d) {
                if (d.name !== name) return;
                d.hidden = true;
            }, this);
            if (!this.isRendered()) return;
            this.$el.find('footer button[data-name="'+name+'"]').addClass('hidden');
        },
    });
});

