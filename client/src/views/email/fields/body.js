/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/fields/body', 'views/fields/wysiwyg', function (Dep) {

    return Dep.extend({

        useIframe: true,

        getAttributeList: function () {
            return ['body', 'bodyPlain'];
        },

        setupToolbar: function () {
            Dep.prototype.setupToolbar.call(this);

            this.toolbar.unshift([
                'insert-field',
                ['insert-field']
            ]);

            this.buttons['insert-field'] = function (context) {
                var ui = $.summernote.ui;
                var button = ui.button({
                    contents: '<i class="fas fa-plus"></i>',
                    tooltip: this.translate('Insert Field', 'labels', 'Email'),
                    click: function () {
                        this.showInsertFieldModal();
                    }.bind(this)
                });
                return button.render();
            }.bind(this);

            this.listenTo(this.model, 'change', function (m) {
                if (!this.isRendered()) return;
                if (m.hasChanged('parentId') || m.hasChanged('to')) {
                    this.controInsertFieldButton();
                }
            }, this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.controInsertFieldButton();
        },

        controInsertFieldButton: function () {
            var $b = this.$el.find('.note-insert-field > button');

            if (this.model.get('to') && this.model.get('to').length || this.model.get('parentId')) {
                $b.removeAttr('disabled').removeClass('disabled');
            } else {
                $b.attr('disabled', 'disabled').addClass('disabled');
            }
        },

        showInsertFieldModal: function () {
            var to = this.model.get('to');
            if (to) {
                to = to.split(';')[0].trim();
            }
            var parentId = this.model.get('parentId');
            var parentType = this.model.get('parentType');

            Espo.Ui.notify(this.translate('loading', 'messages'));

            this.createView('insertFieldDialog', 'views/email/modals/insert-field', {
                parentId: parentId,
                parentType: parentType,
                to: to,
            }, function (view) {
                view.render();
                Espo.Ui.notify();

                this.listenToOnce(view, 'insert', function (string) {
                    if (this.$summernote) {
                        if (~string.indexOf('\n')) {
                            string = string.replace(/(?:\r\n|\r|\n)/g, '<br>');
                            var html = '<p>' + string + '</p>';
                            this.$summernote.summernote('editor.pasteHTML', html);
                        } else {
                            this.$summernote.summernote('editor.insertText', string);
                        }
                    }
                    this.clearView('insertFieldDialog');
                }, this);
            });
        },

    });
});
