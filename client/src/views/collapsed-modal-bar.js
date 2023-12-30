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

import View from 'view';

class CollapsedModalBar extends View {

    maxNumberToDisplay = 3

    // language=Handlebars
    templateContent = `
        {{#each dataList}}
        <div class="collapsed-modal" data-number="{{number}}">{{var key ../this}}</div>
        {{/each}}
    `

    data() {
        return {
            dataList: this.getDataList(),
        };
    }

    init() {
        this.on('render', () => {
            if ($('.collapsed-modal-bar').length === 0) {
                $('<div />')
                    .addClass('collapsed-modal-bar')
                    .appendTo('body');
            }
        });
    }

    setup() {
        this.lastNumber = 0;
        this.numberList = [];
    }

    getDataList() {
        const list = [];

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
    }

    calculateDuplicateNumber(title) {
        let duplicateNumber = 0;

        this.numberList.forEach(number => {
            const view = this.getModalViewByNumber(number);

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
    }

    getModalViewByNumber(number) {
        const key = 'key-' + number;

        return this.getView(key);
    }

    addModalView(modalView, options) {
        const number = this.lastNumber;

        this.numberList.push(this.lastNumber);

        const key = 'key-' + number;

        this.createView(key, 'views/collapsed-modal', {
            title: options.title,
            duplicateNumber: this.calculateDuplicateNumber(options.title),
            selector: '[data-number="' + number + '"]',
        })
        .then(view => {
            this.listenToOnce(view, 'close', () => {
                this.removeModalView(number);
            });

            this.listenToOnce(view, 'expand', () => {
                this.removeModalView(number, true);

                // Use timeout to prevent DOM being updated after modal is re-rendered.
                setTimeout(() => {
                    const key = 'dialog-' + number;

                    this.setView(key, modalView);

                    modalView.setSelector(modalView.containerSelector);

                    this.getView(key).render();
                }, 5);
            });

            this.reRender(true);
        });

        this.lastNumber++;
    }

    removeModalView(number, noReRender) {
        const key = 'key-' + number;

        const index = this.numberList.indexOf(number);

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
    }
}

// noinspection JSUnusedGlobalSymbols
export default CollapsedModalBar;
