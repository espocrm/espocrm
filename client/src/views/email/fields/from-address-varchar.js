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

define(
    'views/email/fields/from-address-varchar',
    ['views/fields/base', 'views/email/fields/email-address', 'helpers/record-modal'],
    function (Dep, EmailAddress, RecordModal) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        validations: ['required', 'email'],

        skipCurrentInAutocomplete: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.erasedPlaceholder = 'ERASED:';

            this.on('render', () => {
                if (this.mode === this.MODE_SEARCH) {
                    return;
                }

                this.initAddressList();
            });
        },

        events: {
            'click [data-action="createContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                this.createPerson('Contact', address);
            },
            'click [data-action="createLead"]': function (e) {
                var address = $(e.currentTarget).data('address');
                this.createPerson('Lead', address);
            },
            'click [data-action="addToContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                this.addToPerson('Contact', address);
            },
            'click [data-action="addToLead"]': function (e) {
                var address = $(e.currentTarget).data('address');
                this.addToPerson('Lead', address);
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

                this.quickView({
                    id: id,
                    scope: scope,
                });
            },
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            var address = this.model.get(this.name);
            if (address && !(address in this.idHash) && this.model.get('parentId')) {
                if (this.getAcl().check('Contact', 'edit')) {
                    data.showCreate = true;
                }
            }

            data.valueIsSet = this.model.has(this.name);

            return data;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === this.MODE_SEARCH && this.getAcl().check('Email', 'create')) {
                EmailAddress.prototype.initSearchAutocomplete.call(this);
            }

            if (this.mode === this.MODE_EDIT && this.getAcl().check('Email', 'create')) {
                EmailAddress.prototype.initSearchAutocomplete.call(this);
            }

            if (this.mode === this.MODE_SEARCH) {
                this.$input.on('input', () => {
                    this.trigger('change');
                });
            }
        },

        getAutocompleteMaxCount: function () {
            return EmailAddress.prototype.getAutocompleteMaxCount.call(this);
        },

        initAddressList: function () {
            this.nameHash = {};
            this.typeHash = this.model.get('typeHash') || {};
            this.idHash = this.model.get('idHash') || {};

            _.extend(this.nameHash, this.model.get('nameHash') || {});
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);

            list.push('nameHash');
            list.push('idHash');
            list.push('accountId');

            return list;
        },

        getValueForDisplay: function () {
            if (this.mode === this.MODE_DETAIL) {
                var address = this.model.get(this.name);

                return this.getDetailAddressHtml(address);
            }

            return Dep.prototype.getValueForDisplay.call(this);
        },

        getDetailAddressHtml: function (address) {
            if (!address) {
                return '';
            }

            let fromString = this.model.get('fromString') || this.model.get('fromName');

            let name = this.nameHash[address] || this.parseNameFromStringAddress(fromString) || null;

            let entityType = this.typeHash[address] || null;
            let id = this.idHash[address] || null;

            if (id) {
                return $('<div>')
                    .append(
                        $('<a>')
                            .attr('href', `#${entityType}/view/${id}`)
                            .attr('data-scope', entityType)
                            .attr('data-id', id)
                            .text(name),
                        ' ',
                        $('<span>').addClass('text-muted chevron-right'),
                        ' ',
                        $('<span>').text(address)
                    )
                    .get(0).outerHTML;
            }

            let $div = $('<div>');

            if (this.getAcl().check('Contact', 'create') || this.getAcl().check('Lead', 'create')) {
                $div.append(
                    this.getCreateHtml(address)
                );
            }

            if (name) {
                $div.append(
                    $('<span>')
                        .addClass('email-address-line')
                        .text(name)
                        .append(
                            ' ',
                            $('<span>').addClass('text-muted chevron-right'),
                            ' ',
                            $('<span>').text(address)
                        )
                );

                return $div.get(0).outerHTML;
            }

            $div.append(
                $('<span>')
                    .addClass('email-address-line')
                    .text(address)
            )


            return $div.get(0).outerHTML;
        },

        getCreateHtml: function (address) {
            let $ul = $('<ul>')
                .addClass('dropdown-menu')
                .attr('role', 'menu');

            let $container = $('<span>')
                .addClass('dropdown email-address-create-dropdown pull-right')
                .append(
                    $('<button>')
                        .addClass('dropdown-toggle btn btn-link btn-sm')
                        .attr('data-toggle', 'dropdown')
                        .append(
                            $('<span>').addClass('caret text-muted')
                        ),
                    $ul
                );

            if (this.getAcl().check('Contact', 'create')) {
                $ul.append(
                    $('<li>')
                        .append(
                            $('<a>')
                                .attr('role', 'button')
                                .attr('tabindex', '0')
                                .attr('data-action', 'createContact')
                                .attr('data-address', address)
                                .text(this.translate('Create Contact', 'labels', 'Email'))
                        )
                );
            }

            if (this.getAcl().check('Lead', 'create')) {
                $ul.append(
                    $('<li>')
                        .append(
                            $('<a>')
                                .attr('role', 'button')
                                .attr('tabindex', '0')
                                .attr('data-action', 'createLead')
                                .attr('data-address', address)
                                .text(this.translate('Create Lead', 'labels', 'Email'))
                        )
                );
            }

            if (this.getAcl().check('Contact', 'edit')) {
                $ul.append(
                    $('<li>')
                        .append(
                            $('<a>')
                                .attr('role', 'button')
                                .attr('tabindex', '0')
                                .attr('data-action', 'addToContact')
                                .attr('data-address', address)
                                .text(this.translate('Add to Contact', 'labels', 'Email'))
                        )
                );
            }

            if (this.getAcl().check('Lead', 'edit')) {
                $ul.append(
                    $('<li>')
                        .append(
                            $('<a>')
                                .attr('role', 'button')
                                .attr('tabindex', '0')
                                .attr('data-action', 'addToLead')
                                .attr('data-address', address)
                                .text(this.translate('Add to Lead', 'labels', 'Email'))
                        )
                );
            }

            return $container.get(0).outerHTML;
        },

        parseNameFromStringAddress: function (value) {
            value = value || '';

            if (~value.indexOf('<')) {
                var name = value.replace(/<(.*)>/, '').trim();

                if (name.charAt(0) === '"' && name.charAt(name.length - 1) === '"') {
                    name = name.substr(1, name.length - 2);
                }

                return name;
            }

            return null;
        },

        createPerson: function (scope, address) {
            var fromString = this.model.get('fromString') || this.model.get('fromName');
            var name = this.nameHash[address] || null;

            if (!name) {
                if (this.name === 'from') {
                    name = this.parseNameFromStringAddress(fromString) || null;
                }
            }

            if (name) {
                name = this.getHelper().escapeString(name);
            }

            var attributes = {
                emailAddress: address
            };

            if (this.model.get('accountId') && scope === 'Contact') {
                attributes.accountId = this.model.get('accountId');
                attributes.accountName = this.model.get('accountName');
            }

            if (name) {
                var firstName = name.split(' ').slice(0, -1).join(' ');
                var lastName = name.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') ||
                'views/modals/edit';

            this.createView('create', viewName, {
                scope: scope,
                attributes: attributes
            }, (view) => {
                view.render();

                this.listenTo(view, 'after:save', (model) => {
                    var nameHash = Espo.Utils.clone(this.model.get('nameHash') || {});
                    var typeHash = Espo.Utils.clone(this.model.get('typeHash') || {});
                    var idHash = Espo.Utils.clone(this.model.get('idHash') || {});

                    idHash[address] = model.id;
                    nameHash[address] = model.get('name');
                    typeHash[address] = scope;

                    this.idHash = idHash;
                    this.nameHash = nameHash;
                    this.typeHash = typeHash;

                    var attributes = {
                        nameHash: nameHash,
                        idHash: idHash,
                        typeHash: typeHash
                    };

                    setTimeout(() => {
                        this.model.set(attributes);

                        if (this.model.get('icsContents')) {
                            this.model.fetch();
                        }
                    }, 50);
                });
            });
        },

        addToPerson: function (scope, address) {
            var fromString = this.model.get('fromString') || this.model.get('fromName');
            var name = this.nameHash[address] || null;

            if (!name) {
                if (this.name === 'from') {
                    name = this.parseNameFromStringAddress(fromString) || null;
                }
            }

            if (name) {
                name = this.getHelper().escapeString(name);
            }

            var attributes = {
                emailAddress: address,
            };

            if (this.model.get('accountId') && scope === 'Contact') {
                attributes.accountId = this.model.get('accountId');
                attributes.accountName = this.model.get('accountName');
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.select') ||
                'views/modals/select-records';

            Espo.Ui.notify(' ... ');

            var filters = {};

            if (name) {
                filters['name'] = {
                    type: 'equals',
                    field: 'name',
                    value: name,
                };
            }

            this.createView('dialog', viewName, {
                scope: scope,
                createButton: false,
                filters: filters,
            }, (view) => {
                view.render();

                Espo.Ui.notify(false);

                this.listenToOnce(view, 'select', (model) => {
                    var afterSave = () => {
                        var nameHash = Espo.Utils.clone(this.model.get('nameHash') || {});
                        var typeHash = Espo.Utils.clone(this.model.get('typeHash') || {});
                        var idHash = Espo.Utils.clone(this.model.get('idHash') || {});

                        idHash[address] = model.id;
                        nameHash[address] = model.get('name');
                        typeHash[address] = scope;

                        this.idHash = idHash;
                        this.nameHash = nameHash;
                        this.typeHash = typeHash;

                        var attributes = {
                            nameHash: nameHash,
                            idHash: idHash,
                            typeHash: typeHash
                        };

                        setTimeout(() => {
                            this.model.set(attributes);

                            if (this.model.get('icsContents')) {
                                this.model.fetch();
                            }
                        }, 50);
                    };

                    if (!model.get('emailAddress')) {
                        model.save({
                            'emailAddress': address
                        }, {patch: true}).then(afterSave);
                    }
                    else {
                        model.fetch().then(() => {
                            var emailAddressData = model.get('emailAddressData') || [];

                            var item = {
                                emailAddress: address,
                                primary: emailAddressData.length === 0
                            };

                            emailAddressData.push(item);

                            model.save({
                                'emailAddressData': emailAddressData
                            }, {patch: true}).then(afterSave);
                        });
                    }
                });
            });
        },

        fetchSearch: function () {
            var value = this.$element.val().trim();

            if (value) {
                return {
                    type: 'equals',
                    value: value,
                }
            }

            return null;
        },

        validateEmail: function () {
            var address = this.model.get(this.name);

            if (!address) {
                return;
            }

            var addressLowerCase = String(address).toLowerCase();

            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            if (!re.test(addressLowerCase) && address.indexOf(this.erasedPlaceholder) !== 0) {
                var msg = this.translate('fieldShouldBeEmail', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        },

        quickView: function (data) {
            let helper = new RecordModal(this.getMetadata(), this.getAcl());

            helper.showDetail(this, {
                id: data.id,
                scope: data.scope,
            });
        },
    });
});
