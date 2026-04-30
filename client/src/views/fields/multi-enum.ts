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

/** @module views/fields/multi-enumeration */

import ArrayFieldView, {ArrayOptions, ArrayParams} from 'views/fields/array';
import {StyleMap} from 'views/fields/enum';
import RegExpPattern from 'helpers/reg-exp-pattern';
import MultiSelect from 'ui/multi-select';
import {BaseViewSchema} from 'views/fields/base';

export interface MultiEnumParams extends ArrayParams {
    /**
     * A translation string. E.g. `Global.scopeNames`.
     */
    translation?: string;
    /**
     * Select options.
     */
    options?: string[];
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Display as label.
     */
    displayAsLabel?: boolean;
    /**
     * Display as list (line breaks).
     */
    displayAsList?: boolean;
    /**
     * A label type.
     */
    labelType?: string | 'state';
    /**
     * A reference to options. E.g. `Account.industry`.
     */
    optionsReference?: string;
    /**
     * An options metadata path.
     */
    optionsPath?: string;
    /**
     * To sort options.
     */
    isSorted?: boolean;
    /**
     * Option translations.
     */
    translatedOptions?: Record<string, string>;
    /**
     * A style map.
     */
    style?: StyleMap;
    /**
     * A max number of items.
     */
    maxCount?: number;
    /**
     * Allow custom options.
     */
    allowCustomOptions?: boolean;
    /**
     * A regular expression pattern.
     */
    pattern?: string;
}

export interface MultiEnumOptions extends ArrayOptions {}

/**
 * A multi-enum field.
 */
class MultiEnumFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends MultiEnumOptions = MultiEnumOptions,
    P extends MultiEnumParams = MultiEnumParams,
> extends ArrayFieldView<S, O, P> {

    readonly type: string = 'multiEnum'

    protected listTemplate = 'fields/array/list'
    protected detailTemplate = 'fields/array/detail'
    protected editTemplate = 'fields/multi-enum/edit'

    readonly MAX_ITEM_LENGTH = 100

    protected restoreOnBackspace: boolean = false

    protected validationElementSelector: string = '.selectize-control'

    protected allowCustomOptions: boolean = false

    protected data() {
        // noinspection JSValidateTypes
        return {
            ...super.data(),
            optionList: this.params.options || [],
        };
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @deprecated As of v8.3.0.
     * @todo Remove in v11.0.
     */
    getTranslatedOptions() {
        return (this.params.options ?? []).map((item: string) => {
            if (this.translatedOptions !== null && item in this.translatedOptions) {
                return this.translatedOptions[item];
            }

            return item;
        });
    }

    private translateValueToEditLabel(value: string): string {
        let label = value;

        const options: string[] = this.params.options ?? [];

        if (options.includes(value)) {
            label = this.getLanguage().translateOption(value, this.name, this.entityType);
        }

        if (this.translatedOptions) {
            if (value in this.translatedOptions) {
                label = this.translatedOptions[value];
            }
        }

        if (label === '') {
            label = this.translate('None');
        }

        return label;
    }

    protected setupFieldEvents() {}

    protected afterRender() {
        if (this.isSearchMode()) {
            this.renderSearch();

            return;
        }

        if (this.isEditMode()) {
            this.$element = this.$el.find('[data-name="' + this.name + '"]');

            const items = [];
            const valueList = Espo.Utils.clone(this.selected);

            for (const i in valueList) {
                let value = valueList[i];
                const originalValue = value;

                if (value === '') {
                    value = valueList[i] = '__emptystring__';
                }

                const options: string[] = this.params.options ?? [];

                if (!~options.indexOf(value)) {
                    items.push({
                        value: value,
                        text: this.translateValueToEditLabel(originalValue),
                    });
                }
            }

            this.$element?.val(valueList.join(this.itemDelimiter));

            (this.params.options ?? []).forEach(value => {
                const originalValue = value;

                if (value === '') {
                    value = '__emptystring__';
                }

                items.push({
                    value: value,
                    text: this.translateValueToEditLabel(originalValue),
                    style: this.styleMap[value] || undefined,
                });
            });

            const multiSelectOptions = {
                items: items,
                delimiter: this.itemDelimiter,
                matchAnyWord: this.matchAnyWord,
                draggable: true,
                allowCustomOptions: this.allowCustomOptions,
                restoreOnBackspace: this.restoreOnBackspace,
                create: (input: string) => this.createCustomOptionCallback(input),
            };

            MultiSelect.init(this.$element as any, multiSelectOptions);

            this.$element?.on('change', () => {
                this.trigger('change');
            });
        }
    }

    protected createCustomOptionCallback(input: string): {text: string, value: string} | null {
        if (input.length > this.MAX_ITEM_LENGTH) {
            const message = this.translate('arrayItemMaxLength', 'messages')
                .replace('{max}', this.MAX_ITEM_LENGTH.toString());

            this.showValidationMessage(message, '.selectize-control')

            return null;
        }

        if (this.params.pattern) {
            const helper = new RegExpPattern();

            const result = helper.validate(this.params.pattern, input, this.name, this.entityType);

            if (result) {
                this.showValidationMessage(result.message, '.selectize-control')

                return null;
            }
        }

        return {
            value: input,
            text: input,
        };
    }

    protected focusOnInlineEdit() {
        MultiSelect.focus(this.$element as any);
    }

    fetch(): Record<string, any> {
        let list = ((this.$element?.val() ?? '') as string).split(this.itemDelimiter);

        if (list.length === 1 && list[0] === '') {
            list = [];
        }

        for (const i in list) {
            if (list[i] === '__emptystring__') {
                list[i] = '';
            }
        }

        const translatedOptions = this.translatedOptions;

        if (this.params.isSorted && translatedOptions) {
            list = list.sort((v1, v2) => {
                 return (translatedOptions[v1] || v1)
                     .localeCompare(translatedOptions[v2] || v2);
            });
        }

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

            this.showValidationMessage(msg, '.selectize-control');

            return true;
        }

        return false;
    }

    validateMaxCount() {
        if (!this.params.maxCount) {
            return false;
        }

        const itemList = this.model.get(this.name) || [];

        if (itemList.length > this.params.maxCount) {
            const msg =
                this.translate('fieldExceedsMaxCount', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{maxCount}', this.params.maxCount.toString());

            this.showValidationMessage(msg, '.selectize-control');

            return true;
        }

        return false;
    }
}

export default MultiEnumFieldView;
