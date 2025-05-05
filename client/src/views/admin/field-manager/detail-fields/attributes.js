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

export default class FieldAttributesFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#if dataList.length}}
            <table class="table table-bordered" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <th
                        >{{translate 'name' category='fields' scope='Admin'}}</th>
                        <th style="width: 33%"
                        >{{translate 'type' category='fields' scope='Admin'}}</th>
                        <th style="width: 16%"
                        >{{translate 'notStorable' category='fields' scope='Admin'}}</th>
                        <th style="width: 16%"
                        >{{translate 'readOnly' category='fields' scope='Admin'}}</th>
                    </tr>
                </thead>
                <tbody>
                    {{#each dataList}}
                        <tr>
                            <td>{{name}}</td>
                            <td>{{type}}</td>
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-checkbox form-checkbox-simple"
                                    disabled
                                    {{#if notStorable}} checked {{/if}}
                                >
                            </td>
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-checkbox form-checkbox-simple"
                                    disabled
                                    {{#if readOnly}} checked {{/if}}
                                >
                            </td>
                        </tr>
                    {{/each}}
                </tbody>
            </table>
        {{else}}
            <span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    `

    data() {
        return {
            dataList: this.model.attributes.attributes || [],
        };
    }
}
