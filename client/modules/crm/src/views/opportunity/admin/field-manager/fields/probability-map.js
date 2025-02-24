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

import BaseFieldView from 'views/fields/base';
import $ from 'jquery';

// noinspection JSUnusedGlobalSymbols
export default class extends BaseFieldView {

    editTemplateContent = `
        <div class="list-group link-container no-input">
            {{#each stageList}}
                <div class="list-group-item form-inline">
                    <div style="display: inline-block; width: 100%;">
                        <input
                            class="role form-control input-sm pull-right"
                            data-name="{{./this}}" value="{{prop ../values this}}"
                        >
                        <div>{{./this}}</div>
                    </div>
                    <br class="clear: both;">
                </div>
            {{/each}}
        </div>
    `

    setup() {
        super.setup();

        this.listenTo(this.model, 'change:options', function (m, v, o) {
            const probabilityMap = this.model.get('probabilityMap') || {};

            if (o.ui) {
                (this.model.get('options') || []).forEach(item => {
                    if (!(item in probabilityMap)) {
                        probabilityMap[item] = 50;
                    }
                });

                this.model.set('probabilityMap', probabilityMap);
            }

            this.reRender();
        });
    }

    data() {
        const data = {};

        const values = this.model.get('probabilityMap') || {};

        data.stageList = this.model.get('options') || [];
        data.values = values;

        return data;
    }

    fetch() {
        const data = {
            probabilityMap: {},
        };

        (this.model.get('options') || []).forEach(item => {
            data.probabilityMap[item] = parseInt($(this.element).find(`input[data-name="${item}"]`).val());
        });

        return data;
    }

    afterRender() {
        $(this.element).find('input').on('change', () => {
            this.trigger('change')
        });
    }
}
