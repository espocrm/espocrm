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

import ModalView from 'views/modal';

class RoleAddFieldModalView extends ModalView {

    template = 'role/modals/add-field'

    backdrop = true

    events = {
        /** @this RoleAddFieldModalView */
        'click a[data-action="addField"]': function (e) {
            this.trigger('add-fields', [$(e.currentTarget).data().name]);
        }
    }

    data() {
        return {
            dataList: this.dataList,
            scope: this.scope,
        };
    }

    setup() {
        this.addHandler('keyup', 'input[data-name="quick-search"]', (e, /** HTMLInputElement */target) => {
            this.processQuickSearch(target.value);
        });

        this.addHandler('click', 'input[type="checkbox"]', (e, /** HTMLInputElement */target) => {
            const name = target.dataset.name;

            if (target.checked) {
                this.checkedList.push(name);
            } else {
                const index = this.checkedList.indexOf(name);

                if (index !== -1) {
                    this.checkedList.splice(index, 1);
                }
            }

            this.checkedList.length ?
                this.enableButton('select') :
                this.disableButton('select');
        });

        this.buttonList = [
            {
                name: 'select',
                label: 'Select',
                style: 'danger',
                disabled: true,
                onClick: () => {
                    this.trigger('add-fields', this.checkedList);
                },
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionCancel(),
            },
        ]

        /** @type {string[]} */
        this.checkedList = [];

        const scope = this.scope = this.options.scope;

        this.headerText = this.translate(scope, 'scopeNamesPlural') + ' · ' + this.translate('Add Field');

        const fields = this.getMetadata().get(`entityDefs.${scope}.fields`) || {};
        const fieldList = [];

        const ignoreFieldList = this.options.ignoreFieldList || [];

        Object.keys(fields).filter(field => !ignoreFieldList.includes(field)).forEach(field => {
            if (!this.getFieldManager().isEntityTypeFieldAvailable(scope, field)) {
                return;
            }

            const mandatoryLevel = this.getMetadata()
                .get(['app', this.options.type, 'mandatory', 'scopeFieldLevel', this.scope, field]);

            if (mandatoryLevel != null) {
                return;
            }

            fieldList.push(field);
        });

        this.fieldList = this.getLanguage().sortFieldList(scope, fieldList);

        /** @type {{name: string, label: string}[]} */
        this.dataList = this.fieldList.map(field => {
            return {
                name: field,
                label: this.translate(field, 'fields', this.scope),
            };
        });
    }

    afterRender() {
        this.$table = this.$el.find('table.fields-table');

        setTimeout(() => {
            this.element.querySelector('input[data-name="quick-search"]').focus();
        }, 0);
    }

    processQuickSearch(text) {
        text = text.trim();

        if (!text) {
            this.$table.find('tr').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.dataList.forEach(item => {
            let matched = false;

            const field = item.name;
            const label = item.label;

            if (
                label.indexOf(lowerCaseText) === 0 ||
                field.toLowerCase().indexOf(lowerCaseText) === 0
            ) {
                matched = true;
            }

            if (!matched) {
                const wordList = label.split(' ').concat(label.split(' '));

                wordList.forEach((word) => {
                    if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                        matched = true;
                    }
                });
            }

            if (matched) {
                matchedList.push(item);
            }
        });

        if (matchedList.length === 0) {
            this.$table.find('tr').addClass('hidden');

            return;
        }

        this.dataList.forEach(item => {
            const $row = this.$table.find(`tr[data-name="${item.name}"]`);

            if (!matchedList.includes(item)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}

export default RoleAddFieldModalView;
