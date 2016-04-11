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
Espo.define('views/email/fields/from-address-varchar', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        detailTemplate: 'email/fields/email-address-varchar/detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.on('render', function () {
                this.initAddressList();
            }, this);
        },

        events: {
            'click [data-action="createContact"]': function (e) {
                var address = $(e.currentTarget).data('address');
                this.createPerson('Contact', address);
            }
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            var address = this.model.get(this.name);
            if (!(address in this.idHash) && this.model.get('parentId')) {
                if (this.getAcl().check('Contact', 'edit')) {
                    data.showCreate = true;
                }
            }

            return data;
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
            if (!id) {
                if (this.getAcl().check('Contact', 'edit')) {
                    lineHtml += this.getCreateHtml(address);
                }
            }
            lineHtml = '<div>' + lineHtml + '</div>';
            return lineHtml;
        },

        getCreateHtml: function (address) {
            var html = '<span class="dropdown email-address-create-dropdown">' +
                '<button class="dropdown-toggle btn btn-link btn-sm" data-toggle="dropdown">' +
                    '<span class="caret text-muted"></span>' +
                '</button>' +
                '<ul class="dropdown-menu" role="menu">' +
                    '<li><a href="javascript:" data-action="createContact" data-address="'+address+'">'+this.translate('Create Contact', 'labels', 'Email')+'</a></li>' +
                '</ul>' +
            '</span>';
            return html;
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
            var address = address;

            var fromString = this.model.get('fromString') || this.model.get('fromName');
            var name = this.nameHash[address] || null;

            if (!name) {
                if (this.name == 'from') {
                    name = this.parseNameFromStringAddress(fromString) || null;
                }
            }

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

            var viewName = this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit') || 'views/modals/edit';

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

                    var attributes = {
                        nameHash: nameHash,
                        idHash: idHash,
                        typeHash: typeHash
                    };

                    setTimeout(function () {
                        this.model.set(attributes);
                    }.bind(this), 50);
                }, this);
            }.bind(this));
        },

    });

});
