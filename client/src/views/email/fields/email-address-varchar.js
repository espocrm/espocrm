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

define('views/email/fields/email-address-varchar',
['views/fields/base', 'views/email/fields/from-address-varchar', 'views/email/fields/email-address'],
function (Dep, From, EmailAddress) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',
        editTemplate: 'email/fields/email-address-varchar/edit',

        emailAddressRegExp: new RegExp(
            /^[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*/.source +
            /@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/.source
        ),

        data: function () {
            let data = Dep.prototype.data.call(this);

            data.valueIsSet = this.model.has(this.name);
            data.maxLength = 254;

            return data;
        },

        events: {
            'click a[data-action="clearAddress"]': function (e) {
                let address = $(e.currentTarget).data('address').toString();

                this.deleteAddress(address);
            },
            'keyup input': function (e) {
                if (!this.isEditMode()) {
                    return;
                }

                let key = Espo.Utils.getKeyFromKeyEvent(e);

                if (
                    key === 'Comma' ||
                    key === 'Semicolon' ||
                    key === 'Enter'
                ) {
                    let $input = $(e.currentTarget);
                    let address = $input.val().replace(',', '').replace(';', '').trim();

                    if (address.indexOf('@') === -1) {
                        return;
                    }

                    if (this.checkEmailAddressInString(address)) {
                        this.addAddress(address, '');
                        $input.val('');
                    }
                }
            },
            'change input': function (e) {
                if (!this.isEditMode()) {
                    return;
                }

                let $input = $(e.currentTarget);
                let address = $input.val().replace(',','').replace(';','').trim();

                if (address.indexOf('@') === -1) {
                    return;
                }

                if (this.checkEmailAddressInString(address)) {
                    this.addAddress(address, '');

                    $input.val('');
                }
            },
            'click [data-action="createContact"]': function (e) {
                let address = $(e.currentTarget).data('address');

                From.prototype.createPerson.call(this, 'Contact', address);
            },
            'click [data-action="createLead"]': function (e) {
                let address = $(e.currentTarget).data('address');

                From.prototype.createPerson.call(this, 'Lead', address);
            },
            'click [data-action="addToContact"]': function (e) {
                let address = $(e.currentTarget).data('address');

                From.prototype.addToPerson.call(this, 'Contact', address);
            },
            'click [data-action="addToLead"]': function (e) {
                let address = $(e.currentTarget).data('address');

                From.prototype.addToPerson.call(this, 'Lead', address);
            },
            'auxclick a[href][data-scope][data-id]': function (e) {
                let isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

                if (!isCombination) {
                    return;
                }

                let $target = $(e.currentTarget);

                let id = $target.attr('data-id');
                let scope = $target.attr('data-scope');

                e.preventDefault();
                e.stopPropagation();

                From.prototype.quickView.call(this, {
                    id: id,
                    scope: scope,
                });
            },
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

            this.on('render', () => {
                this.initAddressList();
            });
        },

        initAddressList: function () {
            this.nameHash = {};

            this.addressList = (this.model.get(this.name) || '')
                .split(';')
                .filter((item) => {
                    return item !== '';
                })
                .map(item => {
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

            if (this.isEditMode()) {
                this.$input = this.$element = this.$el.find('input');

                this.addressList.forEach(item => {
                    this.addAddressHtml(item, this.nameHash[item] || '');
                });

                this.$input.autocomplete({
                    serviceUrl: () => {
                        return `EmailAddress/search` +
                            `?maxSize=${this.getAutocompleteMaxCount()}` +
                            `&onlyActual=true`;
                    },
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    noCache: true,
                    triggerSelectOnValidInput: false,
                    beforeRender: () => {
                        // Prevent an issue that suggestions are shown and not hidden
                        // when clicking outside the window and then focusing back on the document.
                        if (this.$input.get(0) !== document.activeElement) {
                            setTimeout(() => this.$input.autocomplete('hide'), 30);
                        }
                    },
                    formatResult: (suggestion) => {
                        return this.getHelper().escapeString(suggestion.name) + ' &#60;' +
                            this.getHelper().escapeString(suggestion.id) + '&#62;';
                    },
                    transformResult: (response) => {
                        response = JSON.parse(response);
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
                                value: item.emailAddress,
                            });
                        });

                        return {
                            suggestions: list
                        };
                    },
                    onSelect: (s) => {
                        this.addAddress(s.emailAddress, s.entityName, s.entityType, s.entityId);

                        this.$input.val('');
                        this.$input.focus();
                    },
                });

                this.once('render', () => {
                    this.$input.autocomplete('dispose');
                });

                this.once('remove', () => {
                    this.$input.autocomplete('dispose');
                });
            }

            if (this.mode === 'search' && this.getAcl().check('Email', 'create')) {
                EmailAddress.prototype.initSearchAutocomplete.call(this);
            }

            if (this.mode === 'search') {
                this.$input.on('input', () => {
                    this.trigger('change');
                });
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
            let $container = this.$el.find('.link-container');

            let $text = $('<span>');

            if (name) {
                $text.append(
                    $('<span>').text(name),
                    ' ',
                    $('<span>').addClass('text-muted chevron-right'),
                    ' '
                );
            }

            $text.append(
                $('<span>').text(address)
            );

            let $div = $('<div>')
                .attr('data-address', address)
                .addClass('list-group-item')
                .append(
                    $('<a>')
                        .attr('data-address', address)
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('data-action', 'clearAddress')
                        .addClass('pull-right')
                        .append(
                            $('<span>').addClass('fas fa-times')
                        ),
                    $text
                );

            $container.append($div);
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
            let data = {};

            data[this.name] = this.addressList.join(';');

            return data;
        },

        fetchSearch: function () {
            let value = this.$element.val().trim();

            if (value) {
                return {
                    type: 'equals',
                    value: value,
                };
            }

            return null;
        },

        getValueForDisplay: function () {
            if (this.isDetailMode()) {
                let names = [];

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

            let name = this.nameHash[address] || null;
            let entityType = this.typeHash[address] || null;
            let id = this.idHash[address] || null;

            if (id) {
                return $('<div>')
                    .append(
                        $('<a>')
                            .attr('href', '#' + entityType + '/view/' + id)
                            .attr('data-scope', entityType)
                            .attr('data-id', id)
                            .text(name),
                        ' <span class="text-muted chevron-right"></span> ',
                        $('<span>').text(address)
                    )
                    .get(0).outerHTML;
            }

            let $div = $('<div>');

            if (name) {
                $div.append(
                    $('<span>')
                        .addClass('email-address-line')
                        .text(name)
                        .append(' <span class="text-muted chevron-right"></span> ')
                        .append(
                            $('<span>').text(address)
                        )
                );
            }
            else {
                $div.append(
                    $('<span>')
                        .addClass('email-address-line')
                        .text(address)
                );
            }

            if (this.getAcl().check('Contact', 'create') || this.getAcl().check('Lead', 'create')) {
                $div.prepend(
                    From.prototype.getCreateHtml.call(this, address)
                );
            }

            return $div.get(0).outerHTML;
        },

        validateRequired: function () {
            if (this.model.get('status') === 'Draft') {
                return false;
            }

            return Dep.prototype.validateRequired.call(this);
        },
    });
});
