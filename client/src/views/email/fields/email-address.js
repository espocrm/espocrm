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

define('views/email/fields/email-address', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        getAutocompleteMaxCount: function () {
            if (this.autocompleteMaxCount) {
                return this.autocompleteMaxCount;
            }

            return this.getConfig().get('recordsPerPage');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.$input = this.$el.find('input');

            if (this.mode === this.MODE_SEARCH && this.getAcl().check('Email', 'create')) {
                this.initSearchAutocomplete();
            }

            if (this.mode === this.MODE_SEARCH) {
                this.$input.on('input', () => {
                    this.trigger('change');
                });
            }
        },

        initSearchAutocomplete: function () {
            this.$input = this.$input || this.$el.find('input');

            this.$input.autocomplete({
                serviceUrl: () => {
                    return `EmailAddress/search` +
                        `?maxSize=${this.getAutocompleteMaxCount()}`
                },
                paramName: 'q',
                minChars: 1,
                autoSelectFirst: true,
                triggerSelectOnValidInput: false,
                noCache: true,
                formatResult: suggestion => {
                    return this.getHelper().escapeString(suggestion.name) + ' &#60;' +
                        this.getHelper().escapeString(suggestion.id) + '&#62;';
                },
                transformResult: response => {
                    response = JSON.parse(response);

                    let list = response.map(item => {
                        return {
                            id: item.emailAddress,
                            name: item.entityName,
                            emailAddress: item.emailAddress,
                            entityId: item.entityId,
                            entityName: item.entityName,
                            entityType: item.entityType,
                            data: item.emailAddress,
                            value: item.emailAddress,
                        }
                    });

                    if (this.skipCurrentInAutocomplete) {
                        let current = this.$input.val();

                        list = list.filter(item => item.emailAddress !== current)
                    }

                    return {suggestions: list};
                },
                onSelect: (s) => {
                    this.$input.val(s.emailAddress);
                    this.$input.focus();
                },
            });

            this.once('render', () => {
                this.$input.autocomplete('dispose');
            });

            this.once('remove', () => {
                this.$input.autocomplete('dispose');
            });
        },

        fetchSearch: function () {
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
        },
    });
});
