/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/collapsed-modal-bar', ['view'], function (Dep) {

    return Dep.extend({

        maxNumberToDisplay: 3,

        templateContent: `
            {{#each dataList}}
                <div class="collapsed-modal" data-number="{{number}}">{{var key ../this}}</div>
            {{/each}}
        `,

        data: function () {
            return {
                dataList: this.getDataList(),
            };
        },

        init: function () {
            this.on('render', () => {
                if ($('.collapsed-modal-bar').length === 0) {
                    $('<div />')
                        .addClass('collapsed-modal-bar')
                        .appendTo('body');
                }
            });
        },

        setup: function () {
            this.lastNumber = 0;
            this.numberList = [];
        },

        getDataList: function () {
            let list = [];

            let numberList = Espo.Utils.clone(this.numberList);

            if (this.numberList.length > this.maxNumberToDisplay) {
                numberList = numberList.slice(this.numberList.length - this.maxNumberToDisplay);
            }

            numberList
                .reverse()
                .forEach((number, i) => {
                    list.push({
                        number: number.toString(),
                        key: 'key-' + number,
                        index: i,
                    });
                });

            return list;
        },

        calculateDuplicateNumber: function (title) {
            let duplicateNumber = 0;

            this.numberList.forEach(number => {
                let view = this.getModalViewByNumber(number);

                if (!view) {
                    return;
                }

                if (view.title === title) {
                    duplicateNumber++;
                }
            });

            if (duplicateNumber === 0) {
                return null;
            }

            return duplicateNumber;
        },

        getModalViewByNumber: function (number) {
            let key = 'key-' + number;

            return this.getView(key);
        },

        addModalView: function (modalView, options) {
            let number = this.lastNumber;

            this.numberList.push(this.lastNumber);

            let key = 'key-' + number;

            this.createView(key, 'views/collapsed-modal', {
                title: options.title,
                duplicateNumber: this.calculateDuplicateNumber(options.title),
                el: this.getSelector() + ' [data-number="' + number + '"]',
            })
            .then(view => {
                this.listenToOnce(view, 'close', () => {
                    this.removeModalView(number);
                });

                this.listenToOnce(view, 'expand', () => {
                    this.removeModalView(number, true);

                    // Use timeout to prevent DOM being updated after modal is re-rendered.
                    setTimeout(() => {
                        let key = 'dialog-' + number;

                        this.setView(key, modalView);

                        modalView.setSelector(modalView.containerSelector);

                        this.getView(key).render();
                    }, 5);
                });

                this.reRender(true);
            });

            this.lastNumber++;
        },

        removeModalView: function (number, noReRender) {
            let key = 'key-' + number;

            let index = this.numberList.indexOf(number);

            if (~index) {
                this.numberList.splice(index, 1);
            }

            if (this.isRendered()) {
                this.$el.find('.collapsed-modal[data-number="' + number + '"]').remove();
            }

            if (!noReRender) {
                this.reRender();
            }

            this.clearView(key);
        },
    });
});
