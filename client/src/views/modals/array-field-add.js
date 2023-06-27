/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import ModalView from 'views/modal';

class ArrayFieldAddModalView extends ModalView {

    template = 'modals/array-field-add'

    cssName = 'add-modal'
    backdrop = true

    data() {
        return {
            optionList: this.optionList,
            translatedOptions: this.translations,
        };
    }

    events = {
        /** @this ArrayFieldAddModalView */
        'click .add': function (e) {
            let value = $(e.currentTarget).attr('data-value');

            this.trigger('add', value);
        },
        /** @this ArrayFieldAddModalView */
        'click input[type="checkbox"]': function (e) {
            let value = $(e.currentTarget).attr('data-value');

            if (e.target.checked) {
                this.checkedList.push(value);
            } else {
                let index = this.checkedList.indexOf(value);

                if (index !== -1) {
                    this.checkedList.splice(index, 1);
                }
            }

            this.checkedList.length ?
                this.enableButton('select') :
                this.disableButton('select');
        },
        /** @this ArrayFieldAddModalView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
    }

    setup() {
        this.headerText = this.translate('Add Item');
        this.checkedList = [];
        this.translations = this.options.translatedOptions || {};
        this.optionList = this.options.options || [];

        this.buttonList = [
            {
                name: 'select',
                style: 'danger',
                label: 'Select',
                disabled: true,
                onClick: () => {
                    this.trigger('add-mass', this.checkedList);
                },
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];
    }

    afterRender() {
        this.$noData = this.$el.find('.no-data');

        setTimeout(() => {
            this.$el.find('input[data-name="quick-search"]').focus();
        }, 100);
    }

    processQuickSearch(text) {
        text = text.trim();

        let $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('ul .list-group-item').removeClass('hidden');

            return;
        }

        let matchedList = [];

        let lowerCaseText = text.toLowerCase();

        this.optionList.forEach(item => {
            let label = this.translations[item].toLowerCase();

            for (let word of label.split(' ')) {
                let matched = word.indexOf(lowerCaseText) === 0;

                if (matched) {
                    matchedList.push(item);

                    return;
                }
            }
        });

        if (matchedList.length === 0) {
            this.$el.find('ul .list-group-item').addClass('hidden');

            $noData.removeClass('hidden');

            return;
        }

        this.optionList.forEach(item => {
            let $row = this.$el.find(`ul .list-group-item[data-name="${item}"]`);

            if (!~matchedList.indexOf(item)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}

export default ArrayFieldAddModalView;
