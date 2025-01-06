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

class ComplexCreatedFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent =
        `{{#if hasAt}}<span data-name="{{baseName}}At" class="field">{{{atField}}}</span>{{/if}}
        {{#if hasBoth}}<span class="text-muted middle-dot"></span>{{/if}}
        {{#if hasBy}}<span data-name="{{baseName}}By" class="field">{{{byField}}}</span>{{/if}}`

    baseName = 'created'

    getAttributeList() {
        return [this.fieldAt, this.fieldBy];
    }

    init() {
        this.baseName = this.options.baseName || this.baseName;
        this.fieldAt = this.baseName + 'At';
        this.fieldBy = this.baseName + 'By';

        super.init();
    }

    setup() {
        super.setup();

        this.createField('at');
        this.createField('by');
    }

    // noinspection JSCheckFunctionSignatures
    data() {
        const hasBy = this.model.has(this.fieldBy + 'Id');
        const hasAt = this.model.has(this.fieldAt);

        return {
            baseName: this.baseName,
            hasBy: hasBy,
            hasAt: hasAt,
            hasBoth: hasAt && hasBy,
            ...super.data(),
        };
    }

    createField(part) {
        const field = this.baseName + Espo.Utils.upperCaseFirst(part);

        const type = this.model.getFieldType(field) || 'base';

        const viewName = this.model.getFieldParam(field, 'view') ||
            this.getFieldManager().getViewName(type);

        this.createView(part + 'Field', viewName, {
            name: field,
            model: this.model,
            mode: this.MODE_DETAIL,
            readOnly: true,
            readOnlyLocked: true,
            selector: '[data-name="' + field + '"]',
        });
    }

    fetch() {
        return {};
    }
}

// noinspection JSUnusedGlobalSymbols
export default ComplexCreatedFieldView;
