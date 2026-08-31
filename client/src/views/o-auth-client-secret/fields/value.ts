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

import VarcharFieldView from 'views/fields/varchar';
import Model from 'model';

export default class OAuthClientSecretValueField extends VarcharFieldView<{
    model: Model<{
        value: string | null,
        name: string | null,
    }>
}> {

    // noinspection JSUnusedGlobalSymbols
    protected listLinkTemplateContent = `
        {{~#if copyToClipboard~}}
            <a
                role="button"
                data-action="copyToClipboard"
                class="pull-right text-soft"
                title="{{translate 'Copy to Clipboard'}}"
            ><span class="far fa-copy"></span></a>
        {{~/if~}}
        <a
            href="#{{scope}}/view/{{model.id}}"
            class="link"
            data-id="{{model.id}}"
            title="{{value}}"
        >{{#if value}}{{value}}{{else}}{{translate 'None'}}{{/if}}</a>
    `
    protected getAttributeList(): string[] {
        return [
            ...super.getAttributeList(),
            'name',
        ];
    }

    protected data() {
        const data = super.data();

        return {
            ...data,
            isNotEmpty: data.isNotEmpty || this.model.attributes.name != null,
            valueIsSet: data.valueIsSet || this.model.attributes.name != null,
        };
    }

    protected setup() {
        this.model.onChange({
            owner: this,
            attributes: ['hasValue'],
            callback: () => {
                this.params.copyToClipboard = !!this.model.attributes.value;
            },
        });

        this.params.copyToClipboard = !!this.model.attributes.value;

        super.setup();
    }

    protected getValueForDisplay(): string | null | undefined {
        if (this.model.attributes.value) {
            return this.model.attributes.value;
        }

        return this.model.attributes.name;
    }
}
