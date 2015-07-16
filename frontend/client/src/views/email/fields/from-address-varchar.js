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
Espo.define('views/email/fields/from-address-varchar', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.nameHash = {};

            this.initAddressList();
            this.listenTo(this.model, 'change:' + this.name, function () {
                this.initAddressList();
            }, this);
            this.listenTo(this.model, 'change:idHash', function () {
                this.initAddressList();
            }, this);
        },

        events: {
            'click [data-action="createContact"]': function () {
                this.createPerson('Contact');
            },
            'click [data-action="createLead"]': function () {
                this.createPerson('Lead');
            },
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            var address = this.model.get(this.name);
            if (!(address in this.idHash)) {
                if (this.getAcl().check('Contact', 'edit')) {
                    data.showCreate = true;
                }
            }

            return data;
        },

        initAddressList: function () {
            this.typeHash = this.model.get('typeHash') || {};
            this.idHash = this.model.get('idHash') || {};

            _.extend(this.nameHash, this.model.get('nameHash') || {});
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push('nameHash');
            return list;
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail') {
                var address = this.model.get(this.name);
                return this.getDetailAddressHtml(address);
            }
            return Dep.prototype.getValueForDisplay.call(this);
        },

        getDetailAddressHtml: function (address) {
            if (!address) {
                return '';
            }

            var fromString = this.model.get('fromString') || this.model.get('fromName');

            var name = this.nameHash[address] || this.parseNameFromStringAddress(fromString) || null;
            var entityType = this.typeHash[address] || null;
            var id = this.idHash[address] || null;

            var addressHtml = '<span>' + address + '</span>';

            var lineHtml;
            if (id) {
                lineHtml = '<div>' + '<a href="#' + entityType + '/view/' + id + '">' + name + '</a> <span class="text-muted">&#187;</span> ' + addressHtml + '</div>';
            } else if (name) {
                lineHtml = '<span>' + name + ' <span class="text-muted">&#187;</span> ' + addressHtml + '</span>';
            } else {
                lineHtml = addressHtml;
            }
            return lineHtml;
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

        createPerson: function (scope) {
            var address = this.model.get(this.name);

            var fromString = this.model.get('fromString') || this.model.get('fromName');
            var name = this.nameHash[address] || this.parseNameFromStringAddress(fromString) || null;

            var attributes = {
                emailAddress: address
            };

            if (this.model.get('accountId') && scope == 'Contact') {
                attributes.accountId = this.model.get('accountId');
                attributes.accountName = this.model.get('accountName');
            }

            if (name) {
                var firstName = name.split(' ').slice(0, -1).join(' ');
                var lastName = name.split(' ').slice(-1).join(' ');
                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'Modals.Edit';

            this.createView('create', viewName, {
                scope: scope,
                attributes: attributes
            }, function (view) {
                view.render();
                this.listenTo(view, 'after:save', function (model) {
                    var nameHash = Espo.Utils.clone(this.model.get('nameHash') || {});
                    var typeHash = Espo.Utils.clone(this.model.get('typeHash') || {});
                    var idHash = Espo.Utils.clone(this.model.get('idHash') || {});

                    idHash[address] = model.id;
                    nameHash[address] = model.get('name');
                    typeHash[address] = scope;

                    this.idHash = idHash;
                    this.nameHash = nameHash;
                    this.typeHash = typeHash;

                    setTimeout(function () {
                        this.model.set({
                            nameHash: nameHash,
                            idHash: idHash,
                            typeHash: typeHash
                        });
                    }.bind(this), 50);
                }, this);
            }.bind(this));
        },

    });

});
