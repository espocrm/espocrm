/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/array-field-add', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'add-modal',

        template: 'modals/array-field-add',

        backdrop: true,

        fitHeight: true,

        data: function () {
            return {
                optionList: this.options.options,
                translatedOptions: this.options.translatedOptions,
            };
        },

        events: {
            'click .add': function (e) {
                var value = $(e.currentTarget).attr('data-value');
                this.trigger('add', value);
            },
            'click input[type="checkbox"]': function (e) {
                var value = $(e.currentTarget).attr('data-value');
                if (e.target.checked) {
                    this.checkedList.push(value);
                } else {
                    var index = this.checkedList.indexOf(value);

                    if (index !== -1) {
                        this.checkedList.splice(index, 1);
                    }
                }

                if (this.checkedList.length) {
                    this.enableButton('select');
                } else {
                    this.disableButton('select');
                }
            },
        },

        setup: function () {
            this.header = this.translate('Add Item');
            this.checkedList = [];

            this.buttonList = [
                {
                    name: 'select',
                    style: 'danger',
                    label: 'Select',
                    disabled: true,
                    onClick: function (dialog) {
                        this.trigger('add-mass', this.checkedList);
                    }.bind(this),
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                },
            ];

        },

    });
});
