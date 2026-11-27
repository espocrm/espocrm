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

/** @module views/fields/address */

import BaseFieldView, {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';
import Varchar from 'views/fields/varchar';
import Autocomplete from 'ui/autocomplete';

/**
 * Parameters.
 */
export interface AddressParams extends BaseParams {
    /**
     * Show the 'view map' button.
     */
    viewMap?: boolean;
}

/**
 * Options.
 */
export interface AddressOptions extends BaseOptions {}

/**
 * An address field.
 */
class AddressFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends AddressOptions = AddressOptions,
    P extends AddressParams = AddressParams,
> extends BaseFieldView<S, O, P> {

    readonly type: string = 'address'

    protected listTemplate = 'fields/address/detail'
    protected detailTemplate = 'fields/address/detail'
    protected editTemplate = 'fields/address/edit'
    // noinspection JSUnusedGlobalSymbols
    protected editTemplate1 = 'fields/address/edit-1'
    // noinspection JSUnusedGlobalSymbols
    protected editTemplate2 = 'fields/address/edit-2'
    // noinspection JSUnusedGlobalSymbols
    protected editTemplate3 = 'fields/address/edit-3'
    // noinspection JSUnusedGlobalSymbols
    protected editTemplate4 = 'fields/address/edit-4'
    protected searchTemplate = 'fields/address/search'
    protected listLinkTemplate = 'fields/address/list-link'

    protected postalCodeField: string
    protected streetField: string
    protected cityField: string
    protected stateField: string
    protected countryField: string

    private addressPartList: string[]

    protected validations: (FieldValidator | string)[] = [
        'required',
        'pattern',
    ]

    private maxLengthMap: Record<string, number | null>

    private subFieldMap: Record<string, string>

    private addressAttributeList: string[]

    private $street: JQuery
    private $city: JQuery
    private $country: JQuery
    private $postalCode: JQuery
    private $state: JQuery

    protected data(): Record<string, any> {
        const data = super.data();

        data.ucName = Espo.Utils.upperCaseFirst(this.name);

        this.addressPartList.forEach(item => {
            const field = this.subFieldMap[item] as string;

            data[`${item}Value`] = this.model.get(field);
        });

        if (this.isReadMode()) {
            data.formattedAddress = this.getFormattedAddress();

            data.isNone = data.formattedAddress === null;

            if (data.formattedAddress === -1) {
                data.formattedAddress = null;
                data.isLoading = true;
            }

            if (this.params.viewMap && this.canBeDisplayedOnMap()) {
                data.viewMap = true;

                data.viewMapLink = `#AddressMap/view/${this.model.entityType}/${this.model.id}/${this.name}`;
            }
        }

        if (this.isEditMode()) {
            data.stateMaxLength = this.maxLengthMap.state;
            data.streetMaxLength = this.maxLengthMap.street;
            data.postalCodeMaxLength = this.maxLengthMap.postalCode;
            data.cityMaxLength = this.maxLengthMap.city;
            data.countryMaxLength = this.maxLengthMap.country;
        }

        return data;
    }

    protected setupSearch() {
        this.searchData.value = this.getSearchParamsData().value ?? this.searchParams?.additionalValue;
    }

    protected canBeDisplayedOnMap() {
        return !!this.model.get(this.name + 'City') || !!this.model.get(this.name + 'PostalCode');
    }

    private getFormattedAddress(): string | null | -1 {
        let isNotEmpty = false;
        let isSet = false;

        this.addressAttributeList.forEach(attribute => {
            isNotEmpty = isNotEmpty || this.model.get(attribute);
            isSet = isSet || this.model.has(attribute);
        });

        const isEmpty = !isNotEmpty;

        if (isEmpty) {
            if (this.mode === this.MODE_LIST) {
                return '';
            }

            if (!isSet) {
                return -1;
            }

            return null;
        }

        const format = this.getAddressFormat();

        if (format === 1) {
            return this.getFormattedAddress1();
        }

        if (format === 2) {
            return this.getFormattedAddress2();
        }

        if (format === 3) {
            return this.getFormattedAddress3();
        }

        if (format === 4) {
            return this.getFormattedAddress4();
        }

        return null;
    }

    private getFormattedAddress1(): string {
        const postalCodeValue = this.model.get(this.postalCodeField);
        const streetValue = this.model.get(this.streetField);
        const cityValue = this.model.get(this.cityField);
        const stateValue = this.model.get(this.stateField);
        const countryValue = this.model.get(this.countryField);

        let html = '';

        if (streetValue) {
            html += streetValue;
        }

        if (cityValue || stateValue || postalCodeValue) {
            if (html !== '') {
                html += '\n';
            }

            if (cityValue) {
                html += cityValue;
            }

            if (stateValue) {
                if (cityValue) {
                    html += ', ';
                }
                html += stateValue;
            }

            if (postalCodeValue) {
                if (cityValue || stateValue) {
                    html += ' ';
                }
                html += postalCodeValue;
            }
        }
        if (countryValue) {
            if (html !== '') {
                html += '\n';
            }

            html += countryValue;
        }

        return html;
    }

    private getFormattedAddress2(): string {
        const postalCodeValue = this.model.get(this.postalCodeField);
        const streetValue = this.model.get(this.streetField);
        const cityValue = this.model.get(this.cityField);
        const stateValue = this.model.get(this.stateField);
        const countryValue = this.model.get(this.countryField);

        let html = '';

        if (streetValue) {
            html += streetValue;
        }

        if (cityValue || postalCodeValue) {
            if (html !== '') {
                html += '\n';
            }

            if (postalCodeValue) {
                html += postalCodeValue;

                if (cityValue) {
                    html += ' ';
                }
            }

            if (cityValue) {
                html += cityValue;
            }
        }

        if (stateValue || countryValue) {
            if (html !== '') {
                html += '\n';
            }

            if (stateValue) {
                html += stateValue;

                if (countryValue) {
                    html += ' ';
                }
            }

            if (countryValue) {
                html += countryValue;
            }
        }

        return html;
    }

    private getFormattedAddress3(): string {
        const postalCodeValue = this.model.get(this.postalCodeField);
        const streetValue = this.model.get(this.streetField);
        const cityValue = this.model.get(this.cityField);
        const stateValue = this.model.get(this.stateField);
        const countryValue = this.model.get(this.countryField);

        let html = '';

        if (countryValue) {
            html += countryValue;
        }

        if (cityValue || stateValue || postalCodeValue) {
            if (html !== '') {
                html += '\n';
            }

            if (postalCodeValue) {
                html += postalCodeValue;
            }

            if (stateValue) {
                if (postalCodeValue) {
                    html += ' ';
                }
                html += stateValue;
            }

            if (cityValue) {
                if (postalCodeValue || stateValue) {
                    html += ' ';
                }
                html += cityValue;
            }
        }
        if (streetValue) {
            if (html !== '') {
                html += '\n';
            }

            html += streetValue;
        }

        return html;
    }

    private getFormattedAddress4(): string {
        const postalCodeValue = this.model.get(this.postalCodeField);
        const streetValue = this.model.get(this.streetField);
        const cityValue = this.model.get(this.cityField);
        const stateValue = this.model.get(this.stateField);
        const countryValue = this.model.get(this.countryField);

        let html = '';

        if (streetValue) {
            html += streetValue;
        }

        if (cityValue) {
            if (html !== '') {
                html += '\n';
            }

            html += cityValue;
        }

        if (countryValue || stateValue || postalCodeValue) {
            if (html !== '') {
                html += '\n';
            }

            if (countryValue) {
                html += countryValue;
            }

            if (stateValue) {
                if (countryValue) {
                    html += ' - ';
                }

                html += stateValue;
            }

            if (postalCodeValue) {
                if (countryValue || stateValue) {
                    html += ' ';
                }

                html += postalCodeValue;
            }
        }

        return html;
    }

    /**
     * @internal
     */
    protected _getTemplateName(): string | null {
        if (this.mode === this.MODE_EDIT) {
            const prop = 'editTemplate' + this.getAddressFormat().toString();

            if (prop in this) {
                return (this as any)[prop];
            }
        }

        // @todo
        return super._getTemplateName();
    }

    protected getAddressFormat(): number {
        return this.getConfig().get('addressFormat') || 1;
    }

    protected afterRender() {
        if (this.mode === this.MODE_EDIT) {
            this.$street = this.$el.find(`[data-name="${this.streetField}"]`);
            this.$postalCode = this.$el.find(`[data-name="${this.postalCodeField}"]`);
            this.$state = this.$el.find(`[data-name="${this.stateField}"]`);
            this.$city = this.$el.find(`[data-name="${this.cityField}"]`);
            this.$country = this.$el.find(`[data-name="${this.countryField}"]`);

            this.$street.on('change', () => this.trigger('change'));
            this.$postalCode.on('change', () => this.trigger('change'));
            this.$state.on('change', () => this.trigger('change'));
            this.$city.on('change', () => this.trigger('change'));
            this.$country.on('change', () => this.trigger('change'));

            const countryList = this.getCountryList();
            const cityList = this.getConfig().get('addressCityList') || [];
            const stateList = this.getConfig().get('addressStateList') || [];

            if (countryList.length) {
                const autocomplete = new Autocomplete(this.$country.get(0) as HTMLInputElement, {
                    name: this.name + 'Country',
                    triggerSelectOnValidInput: true,
                    autoSelectFirst: true,
                    handleFocusMode: 1,
                    focusOnSelect: true,
                    lookup: countryList,
                    lookupFunction: this.getCountryAutocompleteLookupFunction(countryList),
                    onSelect: () => this.trigger('change'),
                });

                this.once('render remove', () => autocomplete.dispose());
            }

            if (cityList.length) {
                const autocomplete = new Autocomplete(this.$city.get(0) as HTMLInputElement, {
                    name: this.name + 'City',
                    triggerSelectOnValidInput: true,
                    autoSelectFirst: true,
                    handleFocusMode: 1,
                    focusOnSelect: true,
                    lookup: cityList,
                    onSelect: () => this.trigger('change'),
                });

                this.once('render remove', () => autocomplete.dispose());
            }

            if (stateList.length) {
                const autocomplete = new Autocomplete(this.$state.get(0) as HTMLInputElement, {
                    name: this.name + 'State',
                    triggerSelectOnValidInput: true,
                    autoSelectFirst: true,
                    handleFocusMode: 1,
                    focusOnSelect: true,
                    lookup: stateList,
                    onSelect: () => this.trigger('change'),
                });

                this.once('render remove', () => autocomplete.dispose());
            }

            this.controlStreetTextareaHeight();
            this.$street.on('input', () => this.controlStreetTextareaHeight());
        }
    }

    private controlStreetTextareaHeight(lastHeight?: number) {
        const scrollHeight = this.$street.prop('scrollHeight');
        const clientHeight = this.$street.prop('clientHeight');

        if (typeof lastHeight === 'undefined' && clientHeight === 0) {
            setTimeout(this.controlStreetTextareaHeight.bind(this), 10);

            return;
        }

        if (clientHeight === lastHeight) return;

        if (scrollHeight > clientHeight + 1) {
            const rows = this.$street.prop('rows');
            this.$street.attr('rows', rows + 1);

            this.controlStreetTextareaHeight(clientHeight);
        }

        if (((this.$street.val() ?? '') as string).length === 0) {
            this.$street.attr('rows', 1);
        }
    }

    protected setup() {
        super.setup();

        this.addActionHandler('viewMap', (e) => this.viewMapHandler(e));

        this.validations.push(() => this.validateCountryValid());

        this.maxLengthMap = {};

        const actualAttributePartList: string[] = this.getMetadata().get(['fields', this.type, 'actualFields']) ??
            [
                'street',
                'city',
                'state',
                'country',
                'postalCode',
            ];

        this.addressAttributeList = [];
        this.addressPartList = [];
        this.subFieldMap = {};

        actualAttributePartList.forEach(item => {
            const attribute = this.name + Espo.Utils.upperCaseFirst(item);

            this.addressAttributeList.push(attribute);
            this.addressPartList.push(item);

            // @ts-ignore
            this[item + 'Field'] = attribute;

            this.subFieldMap[item] = attribute;

            if (this.entityType) {
                this.maxLengthMap[item] =
                    this.getMetadata().get(['entityDefs', this.entityType, 'fields', attribute, 'maxLength']);
            }
        });
    }

    private viewMapHandler(e: MouseEvent) {
        e.preventDefault();
        e.stopPropagation();

        this.viewMapAction();
    }

    validateRequired(): boolean {
        const validate = (name: string) => {
            if (!this.model.isRequired(name)) {
                return false;
            }

            if (this.model.get(name) !== null) {
                return false;
            }

            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.translate(name, 'fields', this.entityType));

            this.showValidationMessage(msg, `[data-name="${name}"]`);

            return true;
        };

        let result = false;

        this.getSubItems().forEach(item => {
            result = validate(item.field) || result;
        });

        return result;
    }

    private getSubItems(): {item: string, field: string}[] {
        return this.addressPartList.map(it => {
            return {
                item: it,
                field: this.subFieldMap[it] as string,
            };
        })
    }

    isRequired(): boolean {
        for (const item of this.getSubItems()) {
            if (this.model.getFieldParam(item.field, 'required')) {
                return true;
            }
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    protected validatePattern() {
        const fieldList = [
            this.postalCodeField,
            this.stateField,
            this.cityField,
            this.countryField,
        ];

        let result = false;

        for (const field of fieldList) {
            result = Varchar.prototype.fieldValidatePattern.call(this, field) || result;
        }

        return result;
    }

    fetch(): Record<string, unknown> {
        const data = {} as any;

        data[this.postalCodeField] = this.$postalCode.val()?.toString().trim();
        data[this.streetField] = this.$street.val()?.toString().trim();
        data[this.stateField] = this.$state.val()?.toString().trim();
        data[this.cityField] = this.$city.val()?.toString().trim();
        data[this.countryField] = this.$country.val()?.toString().trim();

        const attributeList = [
            this.postalCodeField,
            this.streetField,
            this.stateField,
            this.cityField,
            this.countryField,
        ];

        attributeList.forEach(attribute => {
            if (data[attribute] === '') {
                data[attribute] = null;
            }
        });

        return data;
    }

    fetchSearch(): Record<string, any> | null {
        const value = this.$el.find('input.main-element')
            .val()
            .toString()
            .trim();

        if (!value) {
            return null;
        }

        return {
            type: 'or',
            value: [
                {
                    type: 'like',
                    field: this.postalCodeField,
                    value: value + '%'
                },
                {
                    type: 'like',
                    field: this.streetField,
                    value: value + '%'
                },
                {
                    type: 'like',
                    field: this.cityField,
                    value: value + '%'
                },
                {
                    type: 'like',
                    field: this.stateField,
                    value: value + '%'
                },
                {
                    type: 'like',
                    field: this.countryField,
                    value: value + '%'
                }
            ],
            data: {
                value: value
            }
        };
    }

    private async viewMapAction() {
        const view = await this.createView('mapDialog', 'views/modals/view-map', {
            model: this.model,
            field: this.name,
        });

        await view.render();
    }

    private getCountryList(): string[] {
        const list = (this.getHelper().getAppParam('addressCountryData') || {}).list || [];

        if (list.length) {
            return list;
        }

        return [];
    }

    private getCountryAutocompleteLookupFunction(
        fullList: string[]
    ): ((query: string) => Promise<{value: string}[]>) | undefined {
        const list: string[] = (this.getHelper().getAppParam('addressCountryData') || {}).preferredList || [];

        if (!list.length) {
            return undefined;
        }

        return (query: string) => {
            if (query.length === 0) {
                const result = list.map(item => ({value: item}));

                return Promise.resolve(result);
            }

            const queryLowerCase = query.toLowerCase();

            const result = fullList
                .filter(item => {
                    if (item.toLowerCase().indexOf(queryLowerCase) === 0) {
                        return item.length !== queryLowerCase.length;
                    }
                })
                .map(item => ({value: item}));

            return Promise.resolve(result);
        };
    }

    private validateCountryValid(): boolean {
        if (!this.getConfig().get('validateAddressCountry')) {
            return false;
        }

        const value = this.model.attributes[this.countryField];

        if (value == null) {
            return false;
        }

        if (this.getCountryList().includes(value)) {
            return false;
        }

        const msg = this.translate('fieldAddressCountryIsNotValid', 'messages')
            .replace('{field}', this.translate(this.countryField, 'fields', this.entityType));

        this.showValidationMessage(msg, `[data-name="${this.countryField}"]`);

        return true;
    }
}

export default AddressFieldView;
