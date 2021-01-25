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

define('views/admin/field-manager/modals/add-field', 'views/modal', function (Dep) {

    return Dep.extend({

        backdrop: true,

        template: 'admin/field-manager/modals/add-field',


        events: {
            'click a[data-action="addField"]': function (e) {
                var type = $(e.currentTarget).data('type');

                this.addField(type);
            }
        },

        data: function () {
            return {
                typeList: this.typeList,
            };
        },

        setup: function () {
            this.headerHtml = this.translate('Add Field', 'labels', 'Admin');

            this.typeList = [];

            var fieldDefs = this.getMetadata().get('fields');

            Object.keys(this.getMetadata().get('fields')).forEach(function (type) {
                if (type in fieldDefs) {
                    if (!fieldDefs[type].notCreatable) {
                        this.typeList.push(type);
                    }
                }
            }, this);

            this.typeList.sort(function (v1, v2) {
                return this.translate(v1, 'fieldTypes', 'Admin').localeCompare(this.translate(v2, 'fieldTypes', 'Admin'));
            }.bind(this));
        },


        addField: function (type) {
            this.trigger('add-field', type);
            this.remove();
        },

        afterRender: function () {
            this.typeList.forEach(function (type) {
                var text = this.translate(type, 'fieldInfo', 'FieldManager');

                var $el = this.$el.find('a.info[data-name="'+type+'"]');

                if (text === type) {
                    $el.addClass('hidden');
                    return;
                }

                var text = this.getHelper().transfromMarkdownText(text, {linksInNewTab: true}).toString();

                Espo.Ui.popover($el, {
                    content: text,
                    placement: 'left',
                    container: this.$el.find('.modal-body'),
                }, this);
            }, this);
        },

    });
});
