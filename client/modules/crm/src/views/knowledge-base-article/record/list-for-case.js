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

Espo.define('crm:views/knowledge-base-article/record/list-for-case', 'views/record/list', function (Dep) {

    return Dep.extend({

        actionSendInEmail: function (data) {
            var model = this.collection.get(data.id);

            var parentModel = this.getParentView().model;

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            new Promise(
                function (resolve, reject) {
                    model.fetch().then(function () {
                        resolve();
                    });
                }
            ).then(function () {
                return new Promise(function (resolve, reject) {
                    if (parentModel.get('contactsIds') && parentModel.get('contactsIds').length) {
                        this.getCollectionFactory().create('Contact', function (contactList) {
                            var contactListFinal = [];
                            contactList.url = 'Case/' + parentModel.id + '/contacts';
                            contactList.fetch().then(function () {
                                contactList.forEach(function (contact) {
                                    if (contact.id == parentModel.get('contactId')) {
                                        contactListFinal.unshift(contact);
                                    } else {
                                        contactListFinal.push(contact);
                                    }
                                });
                                resolve(contactListFinal);
                            }, function () {resolve([])});
                        }, this);
                    } else if (parentModel.get('accountId')) {
                        this.getModelFactory().create('Account', function (account) {
                            account.id = parentModel.get('accountId');
                            account.fetch().then(function () {
                                resolve([account]);
                            }, function () {resolve([])});
                        }, this);
                    } else if (parentModel.get('leadId')) {
                        this.getModelFactory().create('Lead', function (account) {
                            lead.id = parentModel.get('leadId');
                            lead.fetch().then(function () {
                                resolve([lead]);
                            }, function () {resolve([])});
                        }, this);
                    } else {
                        resolve([]);
                    }
                }.bind(this))
            }.bind(this)).then(function (list) {
                var attributes = {
                    parentType: 'Case',
                    parentId: parentModel.id,
                    parentName: parentModel.get('name'),
                    name: '[#' + parentModel.get('number') + ']'
                };

                attributes.to = '';
                attributes.cc = '';
                attributes.nameHash = {};

                list.forEach(function (model, i) {
                    if (model.get('emailAddress')) {
                        if (i === 0) {
                            attributes.to += model.get('emailAddress') + ';';
                        } else {
                            attributes.cc += model.get('emailAddress') + ';';
                        }
                        attributes.nameHash[model.get('emailAddress')] = model.get('name');
                    }
                });

                Espo.require('crm:knowledge-base-helper', function (Helper) {
                    (new Helper(this.getLanguage())).getAttributesForEmail(model, attributes, function (attributes) {
                        var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') || 'views/modals/compose-email';
                        this.createView('composeEmail', viewName, {
                            attributes: attributes,
                            selectTemplateDisabled: true,
                            signatureDisabled: true
                        }, function (view) {
                            Espo.Ui.notify(false);
                            view.render();

                            this.listenToOnce(view, 'after:send', function () {
                                parentModel.trigger('after:relate');
                            }, this);
                        }, this);
                    }.bind(this));
                }, this);
            }.bind(this)).catch(function () {
                Espo.Ui.notify(false);
            });
        }

    });
});
