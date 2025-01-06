/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import ModalView from 'views/modal';
import Model from 'model';

export default class extends ModalView {

    templateContent = `<div class="field" data-name="field">{{{field}}}</div>`


    setup() {
        this.addActionHandler('addField', (e, target) => {
            this.trigger('add-field', target.dataset.name);
        })

        this.headerText = this.translate('Add Field');
        this.scope = this.options.scope;

        const model = new Model();

        this.createView('field', 'views/admin/dynamic-logic/fields/field', {
            selector: '[data-name="field"]',
            model: model,
            mode: 'edit',
            scope: this.scope,
            defs: {
                name: 'field',
                params: {},
            },
        }, view => {
            this.listenTo(view, 'change', () => {
                const list = model.get('field') || [];

                if (!list.length) {
                    return;
                }

                this.trigger('add-field', list[0]);
            });
        });
    }
}
