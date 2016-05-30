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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/
Espo.define('views/email/fields/email-address-varchar', ['views/fields/varchar', 'views/email/fields/from-address-varchar'], function (Dep, From) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        editTemplate: 'email/fields/email-address-varchar/edit',

        events: {
            'click a[data-action="clearAddress"]': function (e) {
                var address = $(e.currentTarget).data('address').toString();
                this.deleteAddress(address);
            },
            'keyup input': function (e) {
                if (e.keyCode == 188 || e.keyCode == 186 || e.keyCode == 13) {
                    var $input = $(e.currentTarget);
                    var address = $input.val().replace(',', '').replace(';', '').trim();
                    if (~address.indexOf('@')) {
                        this.addAddress(address, '');
                        $input.val('');
                    }
                }
            },
            'change input': function (e) {
                var $input = $(e.currentTarget);
                var address = $input.val().replace(',','').replace(';','').trim();
                if (~address.indexOf('@')) {
                    this.addAddress(address, '');
                    $input.val('');
                }
            },
            'click [data-action="createContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                From.prototype.createPerson.call(this, 'Contact', address);
            }
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
            this.addressList = (this.model.get(this.name) || '').split(';').filter(function (item) {
                return item != '';
            }).map(function (item) {
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

            this.$input = this.$el.find('input');

            if (this.mode == 'edit') {
                this.addressList.forEach(function (item) {
                    this.addAddressHtml(item, this.nameHash[item] || '');
                }, this);

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
                        this.addAddress(s.emailAddress, s.entityName, s.entityType, s.entityId);
                        this.$input.val('');
                    }.bind(this)
                });
            }
        },

        addAddress: function (address, name, type, id) {
            if (this.justAddedAddress) {
                this.deleteAddress(this.justAddedAddress);
            }
            this.justAddedAddress = address;
            setTimeout(function () {
                this.justAddedAddress = null;
            }.bind(this), 100);

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
            var conteiner = this.$el.find('.link-container');
            var html =
            '<div data-address="'+address+'" class="list-group-item">' +
                '<a href="javascript:" class="pull-right" data-address="' + address + '" data-action="clearAddress"><span class="glyphicon glyphicon-remove"></a>' +
                '<span>'+ ((name) ? (name + ' <span class="text-muted">&#187;</span> ') : '') + '<span>'+address+'</span>'+'</span>' +

            '</div>';
            conteiner.append(html);
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

        getValueForDisplay: function () {
            if (this.mode == 'detail') {
                var names = [];
                this.addressList.forEach(function (address) {
                    names.push(this.getDetailAddressHtml(address));
                }, this);
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

            var addressHtml = '<span>' + address + '</span>';

            var lineHtml;
            if (id) {
                lineHtml = '<div>' + '<a href="#' + entityType + '/view/' + id + '">' + name + '</a> <span class="text-muted">&#187;</span> ' + addressHtml + '</div>';
            } else {
                lineHtml = addressHtml;
            }
            if (!id) {
                if (this.getAcl().check('Contact', 'edit')) {
                    lineHtml += From.prototype.getCreateHtml.call(this, address);
                }
            }
            lineHtml = '<div>' + lineHtml + '</div>';
            return lineHtml;
        },

    });

});
