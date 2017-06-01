/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/fields/person-name', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        type: 'personName',

        detailTemplate: 'fields/person-name/detail',

        editTemplate: 'fields/person-name/edit',

        generateDuplicateRow: function(item) {
            let html = '<div class="autocomplete-duplicate-container">';
            html += '<span style="float: left; margin-right: 10px;" class="glyphicon glyphicon-user"></span>';
            let address = typeof item.billingAddressCity == 'string' ? (', ' + item.billingAddressCity) : '';
            if (typeof item.billingAddressCity == 'string') {
                address = typeof item.billingAddressPostalCode == 'string' ? address + ', ' + item.billingAddressPostalCode : address;
            }
            html += '<span style="width: 65%; overflow: hidden; text-overflow: ellipsis"><span style="border-bottom: 1px dotted;">' + item.name + '</span>' + address + '</span><span style="float: right; color: #999">' + item.entityType + '</span>';

            if (typeof item.accountName == 'string') {
                account = item.accountName;
            }

            html += '<div style="font-size: 0.85em; color: #666"><span style="float: left; margin-right: 10px; color: #AAA" class="glyphicon glyphicon-home"></span>';
            html += item.accountName + '</div></div><!-- autocomplete-duplicate-container -->';
            return html;
        },

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.ucName = Espo.Utils.upperCaseFirst(this.name);
            data.salutationValue = this.model.get(this.salutationField);
            data.firstValue = this.model.get(this.firstField) || '';
            data.lastValue = this.model.get(this.lastField) || '';
            data.nameValue = data.firstValue + ' ' + data.lastValue;
            data.salutationOptions = this.model.getFieldParam(this.salutationField, 'options');
            return data;
        },

        init: function () {
            var ucName = Espo.Utils.upperCaseFirst(this.options.defs.name)
            this.salutationField = 'salutation' + ucName;
            this.firstField = 'first' + ucName;
            this.lastField = 'last' + ucName;
            this.nameField = 'person' + ucName;
            Dep.prototype.init.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit' && typeof this.model.id == 'undefined') {
                // Create Entity mode
                var ucName = Espo.Utils.upperCaseFirst(this.options.defs.name)
                let $personName = $('[name = person' + ucName + ']');
                $personName.autocomplete({
                    serviceUrl: [
                        'Contact?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT,
                        'Lead?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT
                    ],
                    width: '350px',
                    paramName: 'q',
                    minChars: 3,
                    autoSelectFirst: false,
                    transformResult: function (response) {
                        var list = [];
                        response.list.sort(function(a,b) {
                            return a.entityType < b.entityType;
                        });
                        response.list.forEach(function(item) {
                            item.entityType = typeof item.accountId == 'undefined' ? 'Lead' : 'Contact';
                            list.push({
                                data: item.id,
                                item: item,
                                value: this.generateDuplicateRow(item)
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        this.$element.val('');
                        $('.modal-header > a')[0].click();
                        window.location.href = this.getConfig().get('siteUrl') + '#' + s.item.entityType + '/view/' + s.data;
                    }.bind(this)
                });

                this.once('render', function () {
                    this.$element.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    this.$element.autocomplete('dispose');
                }, this);
            }

            if (this.mode == 'edit') {
                this.$salutation = this.$el.find('[name="' + this.salutationField + '"]');
                this.$first = this.$el.find('[name="' + this.firstField + '"]');
                this.$last = this.$el.find('[name="' + this.lastField + '"]');
                this.$name = this.$el.find('[name="' + this.nameField + '"]');

                this.$salutation.on('change', function () {
                    this.trigger('change');
                }.bind(this));
                this.$first.on('change', function () {
                    this.trigger('change');
                }.bind(this));
                this.$last.on('change', function () {
                    this.trigger('change');
                }.bind(this));
                this.$name.on('change', function () {
                    this.trigger('change');
                }.bind(this));
            }
        },

        validateRequired: function () {
            var validate = function (name) {
                if (this.model.isRequired(name)) {
                    if (this.model.get(name) === '') {
                        var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                        this.showValidationMessage(msg, '[name="'+name+'"]');
                        return true;
                    }
                }
            }.bind(this);

            var result = false;
            result = validate(this.salutationField) || result;
            result = validate(this.firstField) || result;
            result = validate(this.lastField) || result;
            return result;
        },

        isRequired: function () {
            return this.model.getFieldParam(this.salutationField, 'required') ||
                   this.model.getFieldParam(this.firstField, 'required') ||
                   this.model.getFieldParam(this.lastField, 'required');
        },

        fetch: function (form) {
            var data = {};
            data[this.salutationField] = this.$salutation.val();

            var firstName = '';
            var lastName = '';

            if (typeof this.model.id == 'undefined') {
                let nameParts = this.$name.val().trim().split(/\s+/);
                if (nameParts.length) {
                    if (nameParts.length > 1) {
                        firstName = nameParts.shift()
                        lastName = nameParts.join(" ");
                    } else {
                        lastName = nameParts.shift();
                    }
                }
            } else {
                firstName = this.$first.val() || '';
                lastName = this.$last.val() || '';
            }

            data[this.firstField] = firstName.trim();
            data[this.lastField] = lastName.trim();

            return data;
        },
    });
});

