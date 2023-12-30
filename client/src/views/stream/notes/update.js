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

    data() {
        return {
            ...super.data(),
            fieldsArr: this.fieldsArr,
            parentType: this.model.get('parentType'),
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
            let modelWas = model;
            let modelBecame = model.clone();

            let data = this.model.get('data');

            data.attributes = data.attributes || {};

            modelWas.set(data.attributes.was);
            modelBecame.set(data.attributes.became);

            this.fieldsArr = [];

            let fields = data.fields;

            fields.forEach(field => {
                let type = model.getFieldType(field) || 'base';
                let viewName = this.getMetadata().get(['entityDefs', model.entityType, 'fields', field, 'view']) ||
                    this.getFieldManager().getViewName(type);

                let attributeList = this.getFieldManager().getEntityTypeFieldAttributeList(model.entityType, field);

                let hasValue = false;

                for (let attribute of attributeList) {
                    if (attribute in data.attributes.was) {
                        hasValue = true;

                        break;
                    }
                }

                if (!hasValue) {
                    this.fieldsArr.push({
                        field: field,
                        noValues: true,
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
                });

                this.createView(field + 'Became', viewName, {
                    model: modelBecame,
                    readOnly: true,
                    defs: {
                        name: field,
                    },
                    mode: 'detail',
                    inlineEditDisabled: true,
                });

                this.fieldsArr.push({
                    field: field,
                    was: field + 'Was',
                    became: field + 'Became',
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
        if (this.$el.find('.details').hasClass('hidden')) {
            this.$el.find('.details').removeClass('hidden');

            $(target).find('span')
                .removeClass('fa-chevron-down')
                .addClass('fa-chevron-up');

            return;
        }

        this.$el.find('.details').addClass('hidden');

        $(target).find('span')
            .addClass('fa-chevron-down')
            .removeClass('fa-chevron-up');
    }
}

export default UpdateNoteStreamView;
