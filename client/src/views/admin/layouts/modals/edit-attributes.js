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

define('views/admin/layouts/modals/edit-attributes', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        _template: '<div class="edit-container">{{{edit}}}</div>',

        setup: function () {
            this.buttonList = [
                {
                    name: 'save',
                    text: this.translate('Apply'),
                    style: 'primary'
                },
                {
                    name: 'cancel',
                    text: 'Cancel'
                }
            ];

            var model = new Model();

            model.name = 'LayoutManager';

            model.set(this.options.attributes || {});

            if (this.options.languageCategory) {
                this.header = this.translate(
                    this.options.name,
                    this.options.languageCategory || 'fields',
                    this.options.scope
                );
            }
            else {
                this.header = false;
            }

            var attributeList = Espo.Utils.clone(this.options.attributeList || []);

            var filteredAttributeList = [];

            attributeList.forEach(function (item) {
                if ((this.options.attributeDefs[item] || {}).readOnly) {
                    return;
                }

                filteredAttributeList.push(item);
            }, this);

            attributeList = filteredAttributeList;

            this.createView('edit', 'views/admin/layouts/record/edit-attributes', {
                el: this.options.el + ' .edit-container',
                attributeList: attributeList,
                attributeDefs: this.options.attributeDefs,
                model: model,
            });
        },

        actionSave: function () {
            var editView = this.getView('edit');

            var attrs = editView.fetch();

            editView.model.set(attrs, {silent: true});

            if (editView.validate()) {
                return;
            }

            var attributes = {};
            attributes = editView.model.attributes;

            this.trigger('after:save', attributes);

            return true;
        },
    });
});
