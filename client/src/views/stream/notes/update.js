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

import NoteStreamView from 'views/stream/note';

class UpdateNoteStreamView extends NoteStreamView {

    template = 'stream/notes/update'
    messageName = 'update'
    rowActionsView = 'views/stream/record/row-actions/update'

    data() {
        const fieldsString = this.fieldDataList
            .map(it => it.label)
            .join(', ');

        return {
            ...super.data(),
            fieldDataList: this.fieldDataList,
            parentType: this.model.get('parentType'),
            iconHtml: this.getIconHtml(),
            fieldsString: fieldsString,
        };
    }

    init() {
        if (this.getUser().isAdmin()) {
            this.isRemovable = true;
        }

        super.init();
    }

    setup() {
        this.addActionHandler('expandDetails', (e, target) => this.toggleDetails(e, target));

        this.createMessage();

        this.wait(true);

        this.getModelFactory().create(this.model.get('parentType'), model => {
            const modelWas = model;
            const modelBecame = model.clone();

            const data = this.model.get('data');

            data.attributes = data.attributes || {};

            modelWas.set(data.attributes.was);
            modelBecame.set(data.attributes.became);

            this.fieldDataList = [];

            const fields = this.fieldList = data.fields;

            fields.forEach(field => {
                const type = model.getFieldType(field) || 'base';
                const viewName = this.getMetadata().get(['entityDefs', model.entityType, 'fields', field, 'view']) ||
                    this.getFieldManager().getViewName(type);

                const attributeList = this.getFieldManager().getEntityTypeFieldAttributeList(model.entityType, field);

                let hasValue = false;

                for (const attribute of attributeList) {
                    if (attribute in data.attributes.was) {
                        hasValue = true;

                        break;
                    }
                }

                if (!hasValue) {
                    this.fieldDataList.push({
                        field: field,
                        noValues: true,
                        label: this.translate(field, 'fields',  this.model.attributes.parentType),
                    });

                    return;
                }

                this.createView(field + 'Was', viewName, {
                    model: modelWas,
                    readOnly: true,
                    defs: {
                        name: field
                    },
                    mode: 'detail',
                    inlineEditDisabled: true,
                    selector: `.row[data-name="${field}"] .cell-was`,
                });

                this.createView(field + 'Became', viewName, {
                    model: modelBecame,
                    readOnly: true,
                    defs: {
                        name: field,
                    },
                    mode: 'detail',
                    inlineEditDisabled: true,
                    selector: `.row[data-name="${field}"] .cell-became`,
                });

                this.fieldDataList.push({
                    field: field,
                    was: field + 'Was',
                    became: field + 'Became',
                    label: this.translate(field, 'fields',  this.model.attributes.parentType),
                });
            });

            this.wait(false);
        });
    }

    /**
     * @param {MouseEvent} event
     * @param {HTMLElement} target
     */
    toggleDetails(event, target) {
        const $details = this.$el.find('> .details');
        const $fields = this.$el.find('> .fields');

        if ($details.hasClass('hidden')) {
            $details.removeClass('hidden');
            $fields.addClass('hidden');

            this.fieldList.forEach(field => {
                const wasField = this.getView(field + 'Was');
                const becomeField = this.getView(field + 'Became');

                if (wasField && becomeField) {
                    wasField.trigger('panel-show-propagated');
                    becomeField.trigger('panel-show-propagated');
                }
            });

            $(target).find('span')
                .removeClass('fa-chevron-down')
                .addClass('fa-chevron-up');

            return;
        }

        $details.addClass('hidden');
        $fields.removeClass('hidden');

        $(target).find('span')
            .addClass('fa-chevron-down')
            .removeClass('fa-chevron-up');
    }
}

export default UpdateNoteStreamView;
