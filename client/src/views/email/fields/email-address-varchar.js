/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define(
    'views/email/fields/email-address-varchar',
    ['views/fields/base', 'views/email/fields/from-address-varchar', 'views/email/fields/email-address'],
    function (Dep, From, EmailAddress) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        editTemplate: 'email/fields/email-address-varchar/edit',

        emailAddressRegExp: /[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/gi,

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.valueIsSet = this.model.has(this.name);

            return data;
        },

        events: {
            'click a[data-action="clearAddress"]': function (e) {
                var address = $(e.currentTarget).data('address').toString();
                this.deleteAddress(address);
            },
            'keyup input': function (e) {
                if (this.mode === 'search') {
                    return;
                }

                if (e.keyCode == 188 || e.keyCode == 186 || e.keyCode == 13) {
                    var $input = $(e.currentTarget);
                    var address = $input.val().replace(',', '').replace(';', '').trim();

                    if (~address.indexOf('@')) {
                        if (this.checkEmailAddressInString(address)) {
                            this.addAddress(address, '');
                            $input.val('');
                        }
                    }
                }
            },
            'change input': function (e) {
                if (this.mode === 'search') {
                    return;
                }

                var $input = $(e.currentTarget);
                var address = $input.val().replace(',','').replace(';','').trim();

                if (~address.indexOf('@')) {
                    if (this.checkEmailAddressInString(address)) {
                        this.addAddress(address, '');

                        $input.val('');
                    }
                }
            },
            'click [data-action="createContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                From.prototype.createPerson.call(this, 'Contact', address);
            },
            'click [data-action="createLead"]': function (e) {
                var address = $(e.currentTarget).data('address');
                From.prototype.createPerson.call(this, 'Lead', address);
            },
            'click [data-action="addToContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                From.prototype.addToPerson.call(this, 'Contact', address);
            },
            'click [data-action="addToLead"]': function (e) {
                var address = $(e.currentTarget).data('address');
                From.prototype.addToPerson.call(this, 'Lead', address);
            }
        },

        getAutocompleteMaxCount: function () {
            if (this.autocompleteMaxCount) {
                return this.autocompleteMaxCount;
            }

            return this.getConfig().get('recordsPerPage');
        },

        parseNameFromStringAddress: function (s) {
            return From.prototype.parseNameFromStringAddress.call(this, s);
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push('nameHash');
            list.push('typeHash');
            list.push('idHash');
            list.push('accountId');
            list.push(this.name + 'EmailAddressesNames');
            list.push(this.name + 'EmailAddressesIds');

            return list;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.on('render', function () {
                this.initAddressList();
            }, this);
        },

        initAddressList: function () {
            this.nameHash = {};

            this.addressList = (this.model.get(this.name) || '')
                .split(';')
                .filter((item) => {
                    return item != '';
                }).map((item) =>{
                    return item.trim();
                });

            this.idHash = this.idHash || {};
            this.typeHash = this.typeHash || {};
            this.nameHash = this.nameHash || {};

            _.extend(this.typeHash, this.model.get('typeHash') || {});
            _.extend(this.nameHash, this.model.get('nameHash') || {});
            _.extend(this.idHash, this.model.get('idHash') || {});

            this.nameHash = _.clone(this.nameHash);
            this.typeHash = _.clone(this.typeHash);
            this.idHash = _.clone(this.idHash);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                this.$input = this.$element = this.$el.find('input');

                this.addressList.forEach((item) => {
                    this.addAddressHtml(item, this.nameHash[item] || '');
                });

                this.$input.autocomplete({
                    serviceUrl: (q) => {
                        return 'EmailAddress/action/searchInAddressBook?onlyActual=true&maxSize=' +
                            this.getAutocompleteMaxCount();
                    },
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    formatResult: (suggestion) => {
                        return this.getHelper().escapeString(suggestion.name) + ' &#60;' +
                            this.getHelper().escapeString(suggestion.id) + '&#62;';
                    },
                    transformResult: (response) => {
                        var response = JSON.parse(response);
                        var list = [];

                        response.forEach((item) => {
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
                        });

                        return {
                            suggestions: list
                        };
                    },
                    onSelect: (s) => {
                        this.addAddress(s.emailAddress, s.entityName, s.entityType, s.entityId);

                        this.$input.val('');
                    },
                });

                this.once('render', function () {
                    this.$input.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    this.$input.autocomplete('dispose');
                }, this);
            }

            if (this.mode === 'search' && this.getAcl().check('Email', 'create')) {
                EmailAddress.prototype.initSearchAutocomplete.call(this);
            }

            if (this.mode == 'search') {
                this.$input.on('input', function () {
                    this.trigger('change');
                }.bind(this));
            }
        },

        checkEmailAddressInString: function (string) {
            var arr = string.match(this.emailAddressRegExp);

            if (!arr || !arr.length) {
                return;
            }

            return true;
        },

        addAddress: function (address, name, type, id) {
            if (this.justAddedAddress) {
                this.deleteAddress(this.justAddedAddress);
            }

            this.justAddedAddress = address;

            setTimeout(() => {
                this.justAddedAddress = null;
            }, 100);

            address = address.trim();

            if (!type) {
                var arr = address.match(this.emailAddressRegExp);

                if (!arr || !arr.length) {
                    return;
                }

                address = arr[0];
            }

            if (!~this.addressList.indexOf(address)) {
                this.addressList.push(address);
                this.nameHash[address] = name;

                if (type) {
                    this.typeHash[address] = type;
                }
                if (id) {
                    this.idHash[address] = id;
                }

                this.addAddressHtml(address, name);
                this.trigger('change');
            }
        },

        addAddressHtml: function (address, name) {
            if (name) {
                name = this.getHelper().escapeString(name);
            }

            if (address) {
                address = this.getHelper().escapeString(address);
            }

            var container = this.$el.find('.link-container');

            var html =
            '<div data-address="'+address+'" class="list-group-item">' +
                '<a href="javascript:" class="pull-right" data-address="' + address + '" ' +
                'data-action="clearAddress"><span class="fas fa-times"></a>' +
                '<span>'+ ((name) ? (name + ' <span class="text-muted">&#187;</span> ') : '') +
                '<span>'+address+'</span>'+'</span>' +
            '</div>';

            container.append(html);
        },

        deleteAddress: function (address) {
            this.deleteAddressHtml(address);

            var index = this.addressList.indexOf(address);

            if (index > -1) {
                this.addressList.splice(index, 1);
            }

            delete this.nameHash[address];

            this.trigger('change');
        },

        deleteAddressHtml: function (address) {
            this.$el.find('.list-group-item[data-address="' + address + '"]').remove();
        },

        fetch: function () {
            var data = {};

            data[this.name] = this.addressList.join(';');

            return data;
        },

        fetchSearch: function () {
            var value = this.$element.val().trim();

            if (value) {
                var data = {
                    type: 'equals',
                    value: value,
                }
                return data;
            }

            return false;
        },

        getValueForDisplay: function () {
            if (this.mode === 'detail') {
                var names = [];

                this.addressList.forEach((address) => {
                    names.push(this.getDetailAddressHtml(address));
                });

                return names.join('');
            }
        },

        getDetailAddressHtml: function (address) {
            if (!address) {
                return '';
            }

            var name = this.nameHash[address] || null;
            var entityType = this.typeHash[address] || null;
            var id = this.idHash[address] || null;

            var addressHtml = this.getHelper().escapeString(address);

            if (name) {
                name = this.getHelper().escapeString(name);
            }

            var lineHtml;

            if (id) {
                lineHtml = '<div>' + '<a href="#' + entityType + '/view/' + id + '">' +
                    name + '</a> <span class="text-muted">&#187;</span> ' + addressHtml + '</div>';
            }
            else {
                if (name) {
                    lineHtml = '<span class="email-address-line">' + name +
                        ' <span class="text-muted">&#187;</span> <span>' +
                        addressHtml + '</span></span>';
                }
                else {
                    lineHtml = '<span class="email-address-line">' + addressHtml + '</span>';
                }
            }

            if (!id) {
                if (this.getAcl().check('Contact', 'edit')) {
                    lineHtml = From.prototype.getCreateHtml.call(this, address) + lineHtml;
                }
            }

            lineHtml = '<div>' + lineHtml + '</div>';

            return lineHtml;
        },

    });
});
