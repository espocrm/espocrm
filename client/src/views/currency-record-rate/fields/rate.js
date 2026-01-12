/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import DecimalFieldView from 'views/fields/decimal';

export default class CurrencyRecordRateRateFieldView extends DecimalFieldView {

    // language=Handlebars
    listTemplateContent = `
        {{#if isNotEmpty~}}
            <span class="text-soft">{{targetCode}} = </span>
            <span class="numeric-text">{{value}}</span>
            <span class="text-soft">{{baseCode}}</span>
        {{~/if~}}
    `

    // language=Handlebars
    detailTemplateContent = `
        {{~#if isNotEmpty~}}
            <span class="text-soft">{{targetCode}} = </span>
            <span class="numeric-text">{{value}}</span>
            <span class="text-soft">{{baseCode}}</span>
        {{~else~}}
            {{~#if valueIsSet~}}
                <span class="none-value">{{translate 'None'}}</span>
            {{~else~}}<span class="loading-value"></span>
            {{~/if}}
        {{~/if~}}
    `

    // language=Handlebars
    editTemplateContent = `
            <div class="input-group">
            <span class="input-group-addon radius-left" style="width: 24%">1 {{targetCode}} = </span>
            <span class="input-group-item">
                <input
                    type="text"
                    class="main-element form-control numeric-text"
                    data-name="{{name}}"
                    value="{{value}}"
                    autocomplete="espo-{{name}}"
                    pattern="[\\-]?[0-9]*"
                    style="text-align: end;"
                >
            </span>
            <span class="input-group-addon radius-right" style="width: 21%">{{baseCode}}</span>
        </div>
    `

    getAttributeList() {
        return [
            ...super.getAttributeList(),
            'baseCode',
            'recordName',
        ];
    }

    data() {
        let baseCode = this.model.attributes.baseCode;
        let targetCode = this.model.attributes.recordName;

        if (this.model.entityType === 'CurrencyRecord') {
            baseCode = this.getConfig().get('baseCurrency');
            targetCode = this.model.attributes.code;
        }

        return {
            ...super.data(),
            baseCode,
            targetCode,
        }
    }
}
