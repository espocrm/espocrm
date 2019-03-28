/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/email/fields/email-address', ['views/fields/base'], function (Dep) {

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

            if (this.mode == 'search') {
                this.$input.autocomplete({
                    serviceUrl: function (q) {
                        return 'EmailAddress/action/searchInAddressBook?maxSize=' + this.getAutocompleteMaxCount();
                    }.bind(this),
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    formatResult: function (suggestion) {
                        return this.getHelper().escapeString(suggestion.name) + ' &#60;' + this.getHelper().escapeString(suggestion.id) + '&#62;';
                    }.bind(this),
                    transformResult: function (response) {
                        var response = JSON.parse(response);
                        var list = [];
                        response.forEach(function(item) {
                            list.push({
                                id: item.emailAddress,
                                name: item.entityName,
                                emailAddress: item.emailAddress,
                                entityId: item.entityId,
                                entityName: item.entityName,
                                entityType: item.entityType,
                                data: item.emailAddress,
                                value: item.emailAddress
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        this.$input.val(s.emailAddress);
                    }.bind(this)
                });

                this.once('render', function () {
                    this.$input.autocomplete('dispose');
                }, this);
                this.once('remove', function () {
                    this.$input.autocomplete('dispose');
                }, this);
            }
        },


        fetchSearch: function () {
            var value = this.$element.val();
            if (typeof value.trim === 'function') {
                value = value.trim();
            }
            if (value) {
                var data = {
                    type: 'equals',
                    value: value
                }
                return data;
            }
            return false;
        }

    });

});
