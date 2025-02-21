/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import RowActionHandler from 'handlers/row-action';

class SendInEmailHandler extends RowActionHandler {

    isAvailable(model, action) {
        return this.view.getAcl().checkScope('Email', 'create');
    }

    process(model, action) {
        const parentModel = this.view.getParentView().model;
        const modelFactory = this.view.getModelFactory();
        const collectionFactory = this.view.getCollectionFactory();

        Espo.Ui.notifyWait();

        model.fetch()
            .then(() => {
                return new Promise(resolve => {
                    if (
                        parentModel.get('contactsIds') &&
                        parentModel.get('contactsIds').length
                    ) {
                        collectionFactory.create('Contact', contactList => {
                            const contactListFinal = [];
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
                            });
                        });

                        return;
                    }

                    if (parentModel.get('accountId')) {
                        modelFactory.create('Account', account => {
                            account.id = parentModel.get('accountId');

                            account.fetch()
                                .then(() => resolve([account]));
                        });

                        return;
                    }

                    if (parentModel.get('leadId')) {
                        modelFactory.create('Lead', lead => {
                            lead.id = parentModel.get('leadId');

                            lead.fetch()
                                .then(() => resolve([lead]));
                        });

                        return;
                    }

                    resolve([]);
                });
            })
            .then(list => {
                const attributes = {
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
                    const helper = new Helper(this.view.getLanguage());

                    helper.getAttributesForEmail(model, attributes, attributes => {
                        const viewName = this.view.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                            'views/modals/compose-email';

                        this.view.createView('composeEmail', viewName, {
                            attributes: attributes,
                            selectTemplateDisabled: true,
                            signatureDisabled: true,
                        }, view => {
                            Espo.Ui.notify(false);

                            view.render();

                            this.view.listenToOnce(view, 'after:send', () => {
                                parentModel.trigger('after:relate');
                            });
                        });
                    });
                });
            })
            .catch(() => {
                Espo.Ui.notify(false);
            });
    }
}

export default SendInEmailHandler;
