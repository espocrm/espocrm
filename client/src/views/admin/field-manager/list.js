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

import View from 'view';
import ViewDetailsModalView from 'views/admin/field-manager/modals/view-details';

class FieldManagerListView extends View {

    template = 'admin/field-manager/list'

    data() {
        return {
            scope: this.scope,
            fieldDefsArray: this.fieldDefsArray,
            typeList: this.typeList,
            hasAddField: this.hasAddField,
        };
    }

    events = {
        /** @this FieldManagerListView */
        'click [data-action="removeField"]': function (e) {
            const field = $(e.currentTarget).data('name');

            this.removeField(field);
        },
        /** @this FieldManagerListView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
    }

    setup() {
        this.addActionHandler('viewDetails', (e, target) => this.viewDetails(target.dataset.name));

        this.scope = this.options.scope;

        this.isCustomizable =
            !!this.getMetadata().get(`scopes.${this.scope}.customizable`) &&
            this.getMetadata().get(`scopes.${this.scope}.entityManager.fields`) !== false;

        this.hasAddField = true;

        const entityManagerData = this.getMetadata().get(['scopes', this.scope, 'entityManager']) || {};

        if ('addField' in entityManagerData) {
            this.hasAddField = entityManagerData.addField;
        }

        this.wait(
            this.buildFieldDefs()
        );
    }

    afterRender() {
        this.$noData = this.$el.find('.no-data');

        this.$el.find('input[data-name="quick-search"]').focus();
    }

    buildFieldDefs() {
        return this.getModelFactory().create(this.scope).then(model => {
            this.fields = model.defs.fields;

            this.fieldList = Object.keys(this.fields).sort();
            this.fieldDefsArray = [];

            this.fieldList.forEach(field => {
                const defs = /** @type {Record} */this.fields[field];

                this.fieldDefsArray.push({
                    name: field,
                    isCustom: defs.isCustom || false,
                    type: defs.type,
                    label: this.translate(field, 'fields', this.scope),
                    isEditable: !defs.customizationDisabled && this.isCustomizable,
                });
            });
        });
    }

    removeField(field) {
        const msg = this.translate('confirmRemove', 'messages', 'FieldManager')
            .replace('{field}', field);

        this.confirm(msg, () => {
            Espo.Ui.notifyWait();

            Espo.Ajax.deleteRequest('Admin/fieldManager/' + this.scope + '/' + field).then(() => {
                Espo.Ui.success(this.translate('Removed'));

                this.$el.find(`tr[data-name="${field}"]`).remove();

                this.getMetadata()
                    .loadSkipCache()
                    .then(() => {
                        this.buildFieldDefs()
                            .then(() => {
                                this.broadcastUpdate();

                                return this.reRender();
                            })
                            .then(() => Espo.Ui.success(this.translate('Removed')))
                    });
            });
        });
    }

    broadcastUpdate() {
        this.getHelper().broadcastChannel.postMessage('update:metadata');
        this.getHelper().broadcastChannel.postMessage('update:language');
    }

    processQuickSearch(text) {
        text = text.trim();

        const $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('table tr.field-row').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.fieldDefsArray.forEach(item => {
            let matched = false;

            if (
                item.label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                item.name.toLowerCase().indexOf(lowerCaseText) === 0
            ) {
                matched = true;
            }

            if (!matched) {
                const wordList = item.label.split(' ')
                    .concat(
                        item.label.split(' ')
                    );

                wordList.forEach((word) => {
                    if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                        matched = true;
                    }
                });
            }

            if (matched) {
                matchedList.push(item.name);
            }
        });

        if (matchedList.length === 0) {
            this.$el.find('table tr.field-row').addClass('hidden');

            $noData.removeClass('hidden');

            return;
        }

        this.fieldDefsArray
            .map(item => item.name)
            .forEach(field => {
                const $row = this.$el.find(`table tr.field-row[data-name="${field}"]`);

                if (!~matchedList.indexOf(field)) {
                    $row.addClass('hidden');

                    return;
                }

                $row.removeClass('hidden');
            });
    }

    /**
     * @private
     * @param {string} name
     */
    async viewDetails(name) {
        const view = new ViewDetailsModalView({
            field: name,
            entityType: this.scope,
        });

        await this.assignView('modal', view);

        await view.render();
    }
}

export default FieldManagerListView;
