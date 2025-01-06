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

export default class extends ModalView {

    backdrop = true

    template = 'admin/field-manager/modals/add-field'

    data() {
        return {
            typeList: this.typeList,
        };
    }

    setup() {
        this.addActionHandler('addField', (e, target) => this.addField(target.dataset.type));

        this.addHandler('keyup', 'input[data-name="quick-search"]', (e, /** HTMLInputElement */target) => {
            this.processQuickSearch(target.value);
        });

        this.headerText = this.translate('Add Field', 'labels', 'Admin');

        this.typeList = [];

        /** @type {Record<string, Record>} */
        const fieldDefs = this.getMetadata().get('fields');

        Object.keys(this.getMetadata().get('fields')).forEach(type => {
            if (type in fieldDefs && !fieldDefs[type].notCreatable) {
                this.typeList.push(type);
            }
        });

        this.typeDataList = this.typeList.map(type => {
            return {
                type: type,
                label: this.translate(type, 'fieldTypes', 'Admin'),
            };
        });

        this.typeList.sort((v1, v2) => {
            return this.translate(v1, 'fieldTypes', 'Admin')
                .localeCompare(this.translate(v2, 'fieldTypes', 'Admin'));
        });
    }

    addField(type) {
        this.trigger('add-field', type);
        this.remove();
    }

    afterRender() {
        this.$noData = this.$el.find('.no-data');

        this.typeList.forEach(type => {
            let text = this.translate(type, 'fieldInfo', 'FieldManager');

            const $el = this.$el.find('a.info[data-name="' + type + '"]');

            if (text === type) {
                $el.addClass('hidden');

                return;
            }

            text = this.getHelper().transformMarkdownText(text, {linksInNewTab: true}).toString();

            Espo.Ui.popover($el, {
                content: text,
                placement: 'left',
            }, this);
        });

        setTimeout(() => this.$el.find('input[data-name="quick-search"]').focus(), 50);
    }

    processQuickSearch(text) {
        text = text.trim();

        const $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('ul .list-group-item').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.typeDataList.forEach(item => {
            const matched =
                item.label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                item.type.toLowerCase().indexOf(lowerCaseText) === 0;

            if (matched) {
                matchedList.push(item.type);
            }
        });

        if (matchedList.length === 0) {
            this.$el.find('ul .list-group-item').addClass('hidden');

            $noData.removeClass('hidden');

            return;
        }

        this.typeDataList.forEach(item => {
            const $row = this.$el.find(`ul .list-group-item[data-name="${item.type}"]`);

            if (!~matchedList.indexOf(item.type)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}
