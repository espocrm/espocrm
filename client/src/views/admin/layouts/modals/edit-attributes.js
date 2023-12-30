/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/layouts/modals/edit-attributes', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        templateContent: `
            <div class="panel panel-default no-side-margin">
                <div class="panel-body">
                    <div class="edit-container">{{{edit}}}</div>
                </div>
            </div>
        `,

        className: 'dialog dialog-record',

        shortcutKeys: {
            'Control+Enter': function (e) {
                this.actionSave();

                e.preventDefault();
                e.stopPropagation();
            },
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'save',
                    text: this.translate('Apply'),
                    style: 'primary',
                },
                {
                    name: 'cancel',
                    text: this.translate('Cancel'),
                },
            ];

            const model = new Model();

            model.name = 'LayoutManager';

            model.set(this.options.attributes || {});

            this.headerText = null;

            if (this.options.languageCategory) {
                this.headerText = this.translate(
                    this.options.name,
                    this.options.languageCategory,
                    this.options.scope
                );
            }

            let attributeList = Espo.Utils.clone(this.options.attributeList || []);

            const filteredAttributeList = [];

            attributeList.forEach(item => {
                const defs = this.options.attributeDefs[item] || {};

                if (defs.readOnly || defs.hidden) {
                    return;
                }

                filteredAttributeList.push(item);
            });

            attributeList = filteredAttributeList;

            this.createView('edit', 'views/admin/layouts/record/edit-attributes', {
                selector: '.edit-container',
                attributeList: attributeList,
                attributeDefs: this.options.attributeDefs,
                dynamicLogicDefs: this.options.dynamicLogicDefs,
                model: model,
            });
        },

        actionSave: function () {
            const editView = this.getView('edit');

            const attrs = editView.fetch();

            editView.model.set(attrs, {silent: true});

            if (editView.validate()) {
                return;
            }

            const attributes = editView.model.attributes;

            this.trigger('after:save', attributes);

            return true;
        },
    });
});
