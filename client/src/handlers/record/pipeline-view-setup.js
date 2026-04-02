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
import {inject} from 'di';
import Metadata from 'metadata';
import AppParams from 'app-params';

// noinspection JSUnusedGlobalSymbols
export default class PipelineViewSetupHandler {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {import('model').default}
     */
    model

    /**
     * @private
     * @type {import('views/record/detail').default}
     */
    view

    /**
     * @private
     * @type {AppParams}
     */
    @inject(AppParams)
    appParams

    /**
     * @private
     * @type {{id: string, stages: {id: string, name: string, style: string|null}[]}[]}
     */
    pipelines

    /**
     * @param {import('views/record/detail').default} view
     */
    constructor(view) {
        this.view = view;
        this.model = view.model;
    }

    process() {
        const entityType = this.model.entityType;

        if (this.metadata.get(`scopes.${entityType}.pipelines`) !== true) {
            return;
        }

        this.pipelines = (this.appParams.get('pipelines') ?? {})[entityType] ?? [];

        this.model.onChange({
            owner: this.view,
            attributes: ['pipelineId'],
            callback: o => {
                if (!o.ui) {
                    return;
                }

                setTimeout(() => this.updateStage(), 1);
            },
        });
    }

    /**
     * @private
     */
    updateStage() {
        const pipelineId = this.model.attributes.pipelineId;

        if (!pipelineId) {
            this.setStageNull();

            return;
        }

        const pipeline = this.pipelines.find(it => it.id === pipelineId);

        if (!pipelineId) {
            this.setStageNull();

            return;
        }

        const stage = pipeline.stages[0];

        if (!stage) {
            this.setStageNull();

            return;
        }

        this.model.setMultiple({
            pipelineStageId: stage.id,
            pipelineStageName: stage.name,
        });

        this.model.trigger('pipeline-changed');
    }

    /**
     * @private
     */
    setStageNull() {
        this.model.setMultiple({
            pipelineStageId: null,
            pipelineStageName: null,
        });

        this.model.trigger('pipeline-changed');
    }
}
