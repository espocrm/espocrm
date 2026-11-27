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

import VarcharFieldView, {AutocompleteReturn} from 'views/fields/varchar';
import Utils from 'utils';

interface AddressCountryData {
    list?: string[];
    preferredList?: string[];
}

type AutocompleteItem = import('ui/autocomplete').AutocompleteItem;

export default class AddressCountryFieldView extends VarcharFieldView {

    protected setup() {
        super.setup();

        this.validations.push(() => this.validateCountryValid());
    }

    setupOptions(): void {
        const countryList = this.getCountryList();

        if (countryList.length) {
            this.params.options = Utils.clone(countryList);
        }
    }

    private getAddressCountryData(): AddressCountryData {
        return (this.getHelper().getAppParam('addressCountryData') as AddressCountryData | null) ?? {};
    }

    private getCountryList(): string[] {
        const list = this.getAddressCountryData().list ?? [];

        if (list.length) {
            return list;
        }

        return [];
    }

    protected getAutocompleteLookupFunction(): ((query: string) => AutocompleteReturn) | undefined {
        const list = this.getAddressCountryData().preferredList ?? [];

        if (!list.length) {
            return undefined;
        }

        const fullList: string[] = this.params.options ?? [];

        return (query: string): Promise<AutocompleteItem[]> => {
            if (query.length === 0) {
                const result: AutocompleteItem[] = list.map(item => ({value: item}));

                return Promise.resolve(result);
            }

            const queryLowerCase = query.toLowerCase();

            const result: AutocompleteItem[] = fullList
                .filter(item =>
                    item.toLowerCase().indexOf(queryLowerCase) === 0 &&
                    item.length !== queryLowerCase.length
                )
                .map(item => ({value: item}));

            return Promise.resolve(result);
        };
    }

    private validateCountryValid(): boolean {
        if (!this.getConfig().get('validateAddressCountry')) {
            return false;
        }

        const value = this.model.attributes[this.name];

        if (value == null) {
            return false;
        }

        if (this.getCountryList().includes(value)) {
            return false;
        }

        const msg = this.translate('fieldAddressCountryIsNotValid', 'messages')
            .replace('{field}', this.translate(this.name, 'fields', this.entityType));

        this.showValidationMessage(msg);

        return true;
    }
}
