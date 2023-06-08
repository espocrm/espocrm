/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('crm:views/knowledge-base-article/record/list-for-case', ['views/record/list'], function (Dep) {

    return Dep.extend({

        actionSendInEmail: function (data) {
            let model = this.collection.get(data.id);
            let parentModel = this.getParentView().model;

            Espo.Ui.notify(' ... ');

            new Promise(resolve => {
                model.fetch().then(() => resolve());
            })
            .then(() => {
                return new Promise(resolve => {
                    if (
                        parentModel.get('contactsIds') &&
                        parentModel.get('contactsIds').length
                    ) {
                        this.getCollectionFactory().create('Contact', contactList => {
                            let contactListFinal = [];
                            contactList.url = 'Case/' + parentModel.id + '/contacts';

                            contactList.fetch().then(() => {
                                contactList.forEach(contact => {
                                    if (contact.id === parentModel.get('contactId')) {
                                        contactListFinal.unshift(contact);
                                    } else {
                                        contactListFinal.push(contact);
                                    }
                                });

                                resolve(contactListFinal);
                            }, () => {
                                resolve([]);
                            });
                        });
                    }
                    else if (parentModel.get('accountId')) {
                        this.getModelFactory().create('Account', account => {
                            account.id = parentModel.get('accountId');

                            account.fetch().then(() => {
                                resolve([account]);
                            }, () => {
                                resolve([]);
                            });
                        });
                    }
                    else if (parentModel.get('leadId')) {
                        this.getModelFactory().create('Lead', lead => {
                            lead.id = parentModel.get('leadId');

                            lead.fetch().then(() => {
                                resolve([lead]);
                            }, () => {
                                resolve([]);
                            });
                        });
                    }
                    else {
                        resolve([]);
                    }
                })
            })
            .then(list => {
                let attributes = {
                    parentType: 'Case',
                    parentId: parentModel.id,
                    parentName: parentModel.get('name'),
                    name: '[#' + parentModel.get('number') + ']',
                };

                attributes.to = '';
                attributes.cc = '';
                attributes.nameHash = {};

                list.forEach((model, i) => {
                    if (model.get('emailAddress')) {
                        if (i === 0) {
                            attributes.to += model.get('emailAddress') + ';';
                        } else {
                            attributes.cc += model.get('emailAddress') + ';';
                        }

                        attributes.nameHash[model.get('emailAddress')] = model.get('name');
                    }
                });

                Espo.loader.require('crm:knowledge-base-helper', Helper => {
                    const helper = new Helper(this.getLanguage());

                    helper.getAttributesForEmail(model, attributes, attributes => {
                        var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                            'views/modals/compose-email';

                        this.createView('composeEmail', viewName, {
                            attributes: attributes,
                            selectTemplateDisabled: true,
                            signatureDisabled: true,
                        }, view => {
                            Espo.Ui.notify(false);

                            view.render();

                            this.listenToOnce(view, 'after:send', () => {
                                parentModel.trigger('after:relate');
                            });
                        });
                    });
                });
            })
            .catch(() => {
                Espo.Ui.notify(false);
            });
        },
    });
});
