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

import BaseFieldView from 'views/fields/base';

class ValidationFailuresFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#if itemList.length}}
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 50%;">{{translate 'Field'}}</th>
                    <th>{{translateOption 'Validation' scope='ImportError' field='type'}}</th>
                </tr>
            </thead>
            <tbody>
                {{#each itemList}}
                <tr>
                    <td>{{translate field category='fields' scope=entityType}}</td>
                    <td>
                        {{translate type category='fieldValidations'}}
                        {{#if popoverText}}
                        <a
                            role="button"
                            tabindex="-1"
                            class="text-danger popover-anchor"
                            data-text="{{popoverText}}"
                        ><span class="fas fa-info-circle"></span></a>
                        {{/if}}
                    </td>
                </tr>
                {{/each}}
            </tbody>
        </table>
        {{else}}
        <span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    `

    data() {
        const data = super.data();

        data.itemList = this.getDataList();

        return data;
    }

    afterRenderDetail() {
        this.$el.find('.popover-anchor').each((i, /** HTMLElement */el) => {
            const text = this.getHelper().transformMarkdownText(el.dataset.text).toString();

            Espo.Ui.popover($(el), {content: text}, this);
        });
    }

    /**
     * @return {Object[]}
     */
    getDataList() {
        const itemList = Espo.Utils.cloneDeep(this.model.get(this.name)) || [];

        const entityType = this.model.get('entityType');

        if (Array.isArray(itemList)) {
            itemList.forEach(item => {
                const fieldManager = this.getFieldManager();
                const language = this.getLanguage();

                const fieldType = fieldManager.getEntityTypeFieldParam(entityType, item.field, 'type');

                if (!fieldType) {
                    return;
                }

                const key = fieldType + '_' + item.type;

                if (!language.has(key, 'fieldValidationExplanations', 'Global')) {
                    if (!language.has(item.type, 'fieldValidationExplanations', 'Global')) {
                        return;
                    }

                    item.popoverText = language.translate(item.type, 'fieldValidationExplanations');

                    return;
                }

                item.popoverText = language.translate(key, 'fieldValidationExplanations');
            });
        }

        return itemList;
    }
}

// noinspection JSUnusedGlobalSymbols
export default ValidationFailuresFieldView;
