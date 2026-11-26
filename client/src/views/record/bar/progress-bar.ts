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

import View from 'view';
import type Model from 'model';
import {fragment, h, VNode} from 'bullbone';
import OptionsProvider from 'helpers/field/options-provider';
import PipelinesHelper from 'helpers/misc/pipelines';
import Ui from 'ui';
import type ViewRecordHelper from 'view-record-helper';

type ModelSchema = {
    pipelineId?: string | null,
} & Record<string, any>

type Options = {
    model: Model<ModelSchema>,
    scope: string,
    recordHelper: ViewRecordHelper,
    onItemClick?: (item: {id: string, label: string}) => void,
    readOnly: boolean,
};

export default class ProgressBarView extends View<{
    options: Options,
    model: Model<ModelSchema>,
}> {

    constructor(options: Options) {
        super(options);

        this.scope = this.options.scope;
        this.entityType = this.options.model.entityType!;
        this.recordHelper = options.recordHelper;
    }

    readonly useVirtualDom = true;

    private readonly scope: string;

    private readonly entityType: string;

    private readonly recordHelper: ViewRecordHelper;

    private attribute: string;

    private field: string;

    private editable: boolean;

    private usePipeline: boolean;

    private blockEdit: boolean;

    private statusDataList: {
        id: string,
        label: string,
        style: string | null,
    }[];

    protected setup() {
        const field = this.getMetadata().get(`scopes.${this.scope}.statusField`) as string | null;

        if (!field) {
            throw new Error("No status field.");
        }

        this.attribute = field;
        this.field = field;

        this.usePipeline = this.getMetadata().get(`scopes.${this.scope}.pipelines`) === true;

        if (this.usePipeline) {
            this.attribute = 'pipelineStageId';
            this.field = 'pipelineStage';

            this.setupPipeline();

            let pipelineId = this.model.attributes.pipelineId;

            this.model.onSync({
                owner: this,
                callback: () => {
                    if (this.model.attributes.pipelineId === pipelineId) {
                        return;
                    }

                    pipelineId = this.model.attributes.pipelineId;

                    this.setupPipeline();
                    this.reRender({buffer: true});
                },
            })
        } else {
            this.setupStatusField();
        }

        this.controlEditable();

        this.listenTo(this.recordHelper, 'field-change', () => {
            if (!this.controlEditable()) {
                return;
            }

            this.reRender({buffer: true});
        });

        let value = this.model.get(this.attribute);

        this.model.onSync({
            owner: this,
            callback: () => {
                const editableChanged = this.controlEditable();

                const valueChanged = value !== this.model.get(this.attribute);

                if (!editableChanged && !valueChanged) {
                    return;
                }

                value = this.model.get(this.attribute);

                this.reRender({buffer: true});
            },
        });
    }

    protected setupStatusField() {
        const options = new OptionsProvider().get(this.entityType, this.attribute);

        const ignoreMap = this.getIgnoreMap();

        this.statusDataList = options.filter(it => !ignoreMap[it.name])
            .map(it => {
                return {
                    id: it.name,
                    label: it.label,
                    style: it.style,
                };
            });
    }

    private getIgnoreMap(): Record<string, true> {
        return Object.fromEntries(this.getIgnoreStatuses().map(it => [it, true]));
    }

    private getIgnoreStatuses(): string[] {
        return this.getMetadata().get(`scopes.${this.scope}.kanbanStatusIgnoreList`, []) as string[];
    }

    private setupPipeline() {
        const pipelines = new PipelinesHelper().get(this.entityType);

        const pipelineId = this.model.attributes.pipelineId;

        if (!pipelineId) {
            this.setEmpty();

            return;
        }

        const pipeline = pipelines.find(it => it.id === pipelineId);

        if (!pipeline) {
            this.setEmpty();

            return;
        }

        const ignoreMap = this.getIgnoreMap();

        this.statusDataList = pipeline.stages.filter(it => it.mappedStatus && !ignoreMap[it.mappedStatus])
            .map(it => {
                return {
                    id: it.id,
                    label: it.name,
                    style: it.style,
                };
            });
    }

    private setEmpty() {
        this.statusDataList = [];
    }

    content(): VNode {
        const id = this.model.get(this.attribute);

        if (
            !id ||
            this.statusDataList.length === 0 ||
            !this.statusDataList.find(it => it.id === id)
        ) {
            return fragment([]);
        }

        let met = false;

        const itemElements = this.statusDataList.map(it => {
            if (it.id === id) {
                met = true;
            }

            const style = it.style ?? 'default';

            const a = h('a', {
                attrs: {
                    role: 'button',
                },
                dataset: {
                    style: style,
                },
                on: {
                    click: () => this.onItemClick({
                        id: it.id,
                        label: it.label,
                    }),
                },
            }, it.label);

            return h('li', {
                class: {
                    'stage-bar-item': true,
                    'done': !met,
                    'current': it.id === id,
                    'read-only': !this.editable,
                },
            }, a);
        });

        return h('ol', {
            attrs: {
                class: 'stage-bar',
            },
        }, itemElements);
    }

    private controlEditable(): boolean {
        const editable =
            !this.options.readOnly &&
            !this.blockEdit &&
            !this.recordHelper.getFieldStateParam(this.field, 'readOnly') &&
            this.getAcl().checkModel(this.model, 'edit') === true &&
            this.getAcl().checkField(this.entityType, this.field, 'edit');

        const isChanged = this.editable !== editable;

        this.editable = editable;

        return isChanged;
    }

    private async onItemClick(item: {id: string; label: string}) {
        if (this.options.onItemClick) {
            this.options.onItemClick(item);

            return;
        }
        const message = this.prepareConfirmMessage(item);

        await this.confirm({message});

        Ui.notifyWait();

        const attributes = {
            [this.attribute]: item.id,
        };

        if (this.usePipeline) {
            attributes[this.field + 'Name'] = item.label;
        }

        const previousAttributes: Record<string, any> = {};

        Object.keys(attributes).forEach(it => previousAttributes[it] = this.model.get(it));

        try {
            await this.model.save(attributes, {patch: true});
        } catch (e) {
            this.model.setMultiple(previousAttributes);

            return;
        }

        Ui.notify();
    }

    private prepareConfirmMessage(item: {id: string, label: string}): string {
        let messageKey = 'confirmProgressChange';
        let from = '';

        const currentId = this.model.get(this.attribute);

        if (currentId) {
            const currentItem = this.statusDataList.find(it => it.id === currentId);

            if (currentItem) {
                const isBack =
                    this.statusDataList.findIndex(it => it.id === item.id) <
                    this.statusDataList.findIndex(it => it.id === currentId);

                if (isBack) {
                    messageKey = 'confirmProgressBackChange';
                }

                from = currentItem.label;
            }
        }

        return this.translate(messageKey, 'messages')
            .replace('{to}', item.label)
            .replace('{from}', from);
    }

    setBlockEdit(blockEdit: boolean) {
        this.blockEdit = blockEdit;

        this.controlEditable();

        this.reRender({buffer: true});
    }
}
