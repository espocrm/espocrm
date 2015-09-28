/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
Espo.define('views/email/fields/email-address', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            this.$input = this.$el.find('input');

            if (this.mode == 'search') {
                this.$input.autocomplete({
                    serviceUrl: function (q) {
                        return 'EmailAddress/action/searchInAddressBook?limit=5';
                    }.bind(this),
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    formatResult: function (suggestion) {
                        return suggestion.name + ' &#60;' + suggestion.id + '&#62;';
                    },
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
