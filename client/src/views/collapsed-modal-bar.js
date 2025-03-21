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
import CollapsedModalView from 'views/collapsed-modal';

class CollapsedModalBarView extends View {

    // language=Handlebars
    templateContent = `
        {{#each dataList}}
            <div class="collapsed-modal" data-number="{{number}}">{{var key ../this}}</div>
        {{/each}}
    `

    /**
     * @private
     * @type {number}
     */
    maxNumberToDisplay = 3

    /**
     * @private
     * @type {number[]}
     */
    numberList

    /**
     * @private
     * @type {number}
     */
    lastNumber

    data() {
        return {
            dataList: this.getDataList(),
        };
    }

    init() {
        this.on('render', () => {
            if (document.querySelector('.collapsed-modal-bar')) {
                return;
            }

            const div = document.createElement('div');
            div.classList.add('collapsed-modal-bar');

            document.body.append(div);
        });
    }

    setup() {
        this.lastNumber = 0;
        this.numberList = [];
    }

    /**
     * @private
     * @return {Record[]}
     */
    getDataList() {
        const list = [];

        let numberList = [...this.numberList];

        if (this.numberList.length > this.maxNumberToDisplay) {
            numberList = numberList.slice(this.numberList.length - this.maxNumberToDisplay);
        }

        numberList
            .reverse()
            .forEach((number, i) => {
                list.push({
                    number: number.toString(),
                    key: this.composeKey(number),
                    index: i,
                });
            });

        return list;
    }

    /**
     * @private
     * @param {string} title
     * @return {number|null}
     */
    calculateDuplicateNumber(title) {
        let duplicateNumber = 0;

        for (const number of this.numberList) {
            const view = this.getCollapsedModalViewByNumber(number);

            if (!view) {
                continue;
            }

            if (view.title === title) {
                duplicateNumber++;
            }
        }

        if (duplicateNumber === 0) {
            return null;
        }

        return duplicateNumber;
    }

    /**
     * @param {number} number
     * @return {import('views/collapsed-modal').default|null}
     */
    getCollapsedModalViewByNumber(number) {
        const key = this.composeKey(number);

        return this.getView(key);
    }

    /**
     * @type {import('views/modal').default[]}
     */
    getModalViewList() {
        return this.numberList
            .map(number => this.getCollapsedModalViewByNumber(number))
            .filter(it => it)
            .map(it => it.modalView);
    }

    /**
     * @param {import('views/modal').default} modalView
     * @param {{title: string}} options
     */
    async addModalView(modalView, options) {
        const number = this.lastNumber;

        this.numberList.push(this.lastNumber);

        const key = this.composeKey(number);

        this.lastNumber++;

        const view = new CollapsedModalView({
            modalView: modalView,
            title: options.title,
            duplicateNumber: this.calculateDuplicateNumber(options.title),
            onClose: () => this.removeModalView(number),
            onExpand: () => {
                this.removeModalView(number, true);

                // Use timeout to prevent DOM being updated after modal is re-rendered.
                setTimeout(async () => {
                    const key = `dialog-${number}`;

                    this.setView(key, modalView);
                    modalView.setSelector(modalView.containerSelector);

                    await this.getView(key).render();

                    modalView.trigger('after:expand');
                }, 5);
            },
        });

        await this.assignView(key, view, `[data-number="${number}"]`);

        await this.reRender(true);
    }

    /**
     * @param {number} number
     * @param {boolean} [noReRender]
     */
    removeModalView(number, noReRender = false) {
        const key = this.composeKey(number);

        const index = this.numberList.indexOf(number);

        if (~index) {
            this.numberList.splice(index, 1);
        }

        if (this.isRendered()) {
            const element = this.element.querySelector(`.collapsed-modal[data-number="${number}"]`);

            if (element) {
                element.remove();
            }
        }

        if (!noReRender) {
            this.reRender();
        }

        this.clearView(key);
    }

    /**
     * @private
     * @param {number} number
     * @return {string}
     */
    composeKey(number) {
        return `key-${number}`;
    }
}

export default CollapsedModalBarView;
