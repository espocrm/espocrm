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
import Autocomplete from 'ui/autocomplete';

class EmailEmailAddressFieldView extends BaseFieldView {

    getAutocompleteMaxCount() {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage');
    }

    afterRender() {
        super.afterRender();

        this.$input = this.$el.find('input');

        if (this.mode === this.MODE_SEARCH && this.getAcl().check('Email', 'create')) {
            this.initSearchAutocomplete();
        }

        if (this.mode === this.MODE_SEARCH) {
            this.$input.on('input', () => {
                this.trigger('change');
            });
        }
    }

    initSearchAutocomplete() {
        this.$input = this.$input || this.$el.find('input');

        /** @type {module:ajax.AjaxPromise & Promise<any>} */
        let lastAjaxPromise;

        const autocomplete = new Autocomplete(this.$input.get(0), {
            name: this.name,
            autoSelectFirst: true,
            triggerSelectOnValidInput: true,
            focusOnSelect: true,
            minChars: 1,
            forceHide: true,
            handleFocusMode: 2,
            onSelect: item => {
                this.$input.val(item.emailAddress);
            },
            formatResult: item => {
                return this.getHelper().escapeString(item.name) + ' &#60;' +
                    this.getHelper().escapeString(item.id) + '&#62;';
            },
            lookupFunction: query => {
                if (lastAjaxPromise && lastAjaxPromise.getReadyState() < 4) {
                    lastAjaxPromise.abort();
                }

                lastAjaxPromise = Espo.Ajax
                    .getRequest('EmailAddress/search', {
                        q: query,
                        maxSize: this.getAutocompleteMaxCount(),
                    });

                return lastAjaxPromise.then(/** Record[] */response => {
                    let result = response.map(item => {
                        return {
                            id: item.emailAddress,
                            name: item.entityName,
                            emailAddress: item.emailAddress,
                            entityId: item.entityId,
                            entityName: item.entityName,
                            entityType: item.entityType,
                            data: item.emailAddress,
                            value: item.emailAddress,
                        };
                    });

                    if (this.skipCurrentInAutocomplete) {
                        const current = this.$input.val();

                        result = result.filter(item => item.emailAddress !== current)
                    }

                    return result;
                });
            },
        });

        this.once('render remove', () => autocomplete.dispose());
    }

    fetchSearch() {
        let value = this.$element.val();

        if (typeof value.trim === 'function') {
            value = value.trim();
        }

        if (value) {
            return {
                type: 'equals',
                value: value,
            };
        }

        return null;
    }
}

export default EmailEmailAddressFieldView;
