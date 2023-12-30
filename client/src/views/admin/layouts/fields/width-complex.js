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

import BaseFieldView from 'views/fields/base';
import EnumFieldView from 'views/fields/enum';
import Model from 'model';
import FloatFieldView from 'views/fields/float';

class LayoutWidthComplexFieldView extends BaseFieldView {

    editTemplateContent = `
        <div class="row">
            <div data-name="value" class="col-sm-6">{{{value}}}</div>
            <div data-name="unit" class="col-sm-6">{{{unit}}}</div>
        </div>

    `
    getAttributeList() {
        return ['width', 'widthPx'];
    }

    setup() {
        this.auxModel = new Model();

        this.syncAuxModel();
        this.listenTo(this.model, 'change', () => this.syncAuxModel());

        const unitView = new EnumFieldView({
            name: 'unit',
            mode: 'edit',
            model: this.auxModel,
            params: {
                options: [
                    '%',
                    'px',
                ],
            },
        });

        const valueView = new FloatFieldView({
            name: 'value',
            mode: 'edit',
            model: this.auxModel,
        });

        this.assignView('unit', unitView, '[data-name="unit"]');
        this.assignView('value', valueView, '[data-name="value"]');

        this.listenTo(this.auxModel, 'change', (m, o) => {
            if (!o.ui) {
                return;
            }

            this.model.set(this.fetch(), {ui: true});
        });
    }

    fetch() {
        if (this.auxModel.get('unit') === 'px') {
            return {
                width: null,
                widthPx: this.auxModel.get('value'),
            }
        }

        return {
            width: this.auxModel.get('value'),
            widthPx: null,
        };
    }

    syncAuxModel() {
        const width = this.model.get('width');
        const widthPx = this.model.get('widthPx');

        const unit = width || !widthPx ? '%' : 'px';

        this.auxModel.set({
            unit: unit,
            value: unit === 'px' ? widthPx : width,
        });
    }
}

// noinspection JSUnusedGlobalSymbols
export default LayoutWidthComplexFieldView;
