/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import ArrayFieldView, {ArrayOptions, ArrayParams} from 'views/fields/array';
import {BaseViewSchema} from 'views/fields/base';

export interface ChecklistParams extends ArrayParams {}

export interface ChecklistOptions extends ArrayOptions {
    /**
     * Is inversed.
     */
    isInversed?: boolean
}

class ChecklistFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends ChecklistOptions = ChecklistOptions,
    P extends ChecklistParams = ChecklistParams,
> extends ArrayFieldView<S, O, P> {

    readonly type: string = 'checklist'

    protected listTemplate = 'fields/array/list'
    protected detailTemplate = 'fields/checklist/detail'
    protected editTemplate = 'fields/checklist/edit'

    protected isInversed = false

    data() {
        return {
            optionDataList: this.getOptionDataList(),
            ...super.data(),
        };
    }

    protected setup() {
        super.setup();

        this.params.options = this.params.options ?? [];

        this.isInversed = this.options.isInversed ?? this.isInversed;
    }

    protected setupFieldEvents() {}

    protected afterRender() {
        if (this.isSearchMode()) {
            this.renderSearch();
        }

        if (this.isEditMode()) {
            this.$el.find('input').on('change', () => {
                this.trigger('change');
            });
        }
    }

    protected getOptionDataList(): Record<string, any> {
        const valueList: string[] = (this.model.get(this.name) ?? []) as string[];
        const list: Record<string, any>[] = [];

        (this.params.options ?? []).forEach(item => {
            let isChecked = valueList.includes(item);
            const dataName = item;
            const id = this.cid + '-' + Espo.Utils.camelCaseToHyphen(item.replace(/\s+/g, '-'));

            if (this.isInversed) {
                isChecked = !isChecked;
            }

            list.push({
                name: item,
                isChecked: isChecked,
                dataName: dataName,
                id: id,
                label: this.translatedOptions?.[item] || item,
            });
        });

        return list;
    }

    fetch(): Record<string, any> {
        const list: string[] = [];

        (this.params.options ?? []).forEach(item => {
            const $item = this.$el.find(`input[data-name="${item}"]`);

            let isChecked = $item.get(0) && $item.get(0).checked;

            if (this.isInversed) {
                isChecked = !isChecked;
            }

            if (isChecked) {
                list.push(item);
            }
        });

        return {[this.name]: list};
    }

    validateRequired() {
        if (!this.isRequired()) {
            return false;
        }

        const value = this.model.get(this.name);

        if (!value || value.length === 0) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, '.checklist-item-container:last-child input');

            return true;
        }

        return false;
    }

    validateMaxCount() {
        if (!this.params.maxCount) {
            return false;
        }

        const itemList = this.model.get(this.name) ?? [];

        if (itemList.length > this.params.maxCount) {
            const msg =
                this.translate('fieldExceedsMaxCount', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{maxCount}', this.params.maxCount.toString());

            this.showValidationMessage(msg, '.checklist-item-container:last-child input');

            return true;
        }

        return false;
    }
}

export default ChecklistFieldView;
