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

import LinkFieldView from 'views/fields/link';
import {inject} from 'di';
import AppParams from 'app-params';
import EnumFieldView from 'views/fields/enum';
import Model from 'model';

export default class PipelineFieldView extends LinkFieldView {

    // language=Handlebars
    listTemplateContent = `
        {{#if stringValue}}
            <span
                class=""
                title="{{stringValue}}"
            >{{stringValue}}</span>
        {{/if}}
    `

    // language=Handlebars
    detailTemplateContent = `
        {{#if stringValue}}
            <span
                class=""
            >{{stringValue}}</span>
        {{else}}
            {{#if valueIsSet}}
                <span class="none-value">{{translate 'None'}}</span>
            {{else}}
                <span class="loading-value"></span>
            {{/if}}
        {{/if}}
    `

    // language=Handlebars
    editTemplateContent = `
        <div data-sub-field="pipeline">{{{pipelineField}}}</div>
    `

    /**
     * @private
     * @type {Model}
     */
    subModel

    /**
     * @private
     * @type {AppParams}
     */
    @inject(AppParams)
    appParams

    /**
     * @private
     * @type {EnumFieldView}
     */
    enumView

    /**
     * @private
     * @type {{id: string, name: string}[]}
     */
    pipelines

    /**
     * @private
     * @type {Record<string, string>}
     */
    translatedOptions

    data() {
        const data = super.data();

        data.stringValue = data.nameValue;

        // noinspection JSValidateTypes
        return data;
    }

    setup() {
        super.setup();

        this.pipelines = (this.appParams.get('pipelines') ?? {})[this.entityType] ?? [];

        const translatedOptions = {};

        for (const it of this.pipelines) {
            translatedOptions[it.id] = it.name;
        }

        this.translatedOptions = translatedOptions;

        this.setupSub();
    }

    /**
     * @private
     */
    setupSub() {
        this.subModel = new Model();

        const syncModels = () => {
            this.subModel.setMultiple({
                pipeline: this.model.attributes[this.idName],
            });
        };

        syncModels();

        this.listenTo(this.model, `change:${this.idName}`, (m, v, o) => {
            if (!o.ui || o.fromView && o.fromView !== this) {
                syncModels();
            }
        });

        this.listenTo(this.subModel, 'change', (m, o) => o.ui ? this.trigger('change') : null);
    }

    onEditModeSet() {
        const options = this.pipelines.map(it => it.id);

        const currentId = this.model.attributes[this.idName];

        if (!currentId) {
            options.unshift('');
        }

        if (currentId && !options.includes(currentId)) {
            options.push(currentId);
        }

        const translatedOptions = {...this.translatedOptions};

        if (currentId && !translatedOptions[currentId]) {
            translatedOptions[currentId] = this.model.attributes[this.idName] ?? currentId;
        }

        this.enumView = new EnumFieldView({
            name: 'pipeline',
            model: this.subModel,
            mode: 'edit',
            params: {
                options,
                translatedOptions,
            },
        });

        return this.assignView('pipelineField', this.enumView, '[data-sub-field="pipeline"]');
    }

    fetch() {
        const id = this.subModel.attributes.pipeline;

        // noinspection JSValidateTypes
        return {
            [this.idName]: id,
            [this.nameName]: id ? (this.translatedOptions[id] ?? id) : null,
        };
    }
}
