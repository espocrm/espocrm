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

import VarcharFieldView from 'views/fields/varchar';

class AddressCountryFieldView extends VarcharFieldView {

    setupOptions() {
        const countryList = this.getCountryList();

        if (countryList.length) {
            this.params.options = Espo.Utils.clone(countryList);
        }
    }

    /**
     * @private
     * @return {string[]}
     */
    getCountryList() {
        const list = (this.getHelper().getAppParam('addressCountryData') || {}).list || [];

        if (list.length) {
            return list;
        }

        return [];
    }

    getAutocompleteLookupFunction() {
        // noinspection JSUnresolvedReference
        const list = (this.getHelper().getAppParam('addressCountryData') || {}).preferredList || [];

        if (!list.length) {
            return undefined;
        }

        const fullList = (this.params.options || []);

        return query => {
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
}

export default AddressCountryFieldView;
