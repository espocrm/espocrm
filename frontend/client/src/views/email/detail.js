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

Espo.define('views/email/detail', ['views/detail', 'email-helper'], function (Dep, EmailHelper) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            var status = this.model.get('status');
            if (status == 'Draft') {
                this.backedMenu = this.menu;
                this.menu = {
                    'buttons': [
                        {
                           "label": "Send",
                           "action": "send",
                           "style": "danger",
                           "acl": "edit"
                        }
                    ],
                    'dropdown': [],
                    'actions': []
                };
            } else {
                if (status == 'Archived' || status == 'Recieved') {
                    if (!this.model.get('parentId')) {
                        this.menu.dropdown.push({
                            label: 'Create Lead',
                            action: 'createLead',
                            acl: 'edit',
                            aclScope: 'Lead'
                        });
                        this.menu.dropdown.push({
                            label: 'Create Contact',
                            action: 'createContact',
                            acl: 'edit',
                            aclScope: 'Contact'
                        });
                    }
                }

                this.menu.dropdown.push({
                    label: 'Create Task',
                    action: 'createTask',
                    acl: 'edit',
                    aclScope: 'Task'
                });

                if (this.model.get('parentType') !== 'Case' || !this.model.get('parentId')) {
                    this.menu.dropdown.push({
                        label: 'Create Case',
                        action: 'createCase',
                        acl: 'edit',
                        aclScope: 'Case'
                    });
                }
            }
        },

        actionCreateLead: function () {
            var attributes = {};

            var emailHelper = new EmailHelper(this.getLanguage(), this.getUser());

            var fromString = this.model.get('fromString') || this.model.get('fromName');
            if (fromString) {
                var fromName = emailHelper.parseNameFromStringAddress(fromString);
                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');
                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (this.model.get('replyToString')) {
                var str = this.model.get('replyToString');
                var p = (str.split(';'))[0];
                attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);
                var fromName = emailHelper.parseNameFromStringAddress(p);
                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');
                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (!attributes.emailAddress) {
                attributes.emailAddress = this.model.get('from');
            }
            attributes.emailId = this.model.id;

            var viewName = this.getMetadata().get('clientDefs.Lead.modalViews.detail') || 'Modals.Edit';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                scope: 'Lead',
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:save', function () {
                    this.model.fetch();
                    this.removeMenuItem('createContact');
                    this.removeMenuItem('createLead');
                    view.close();
                }.bind(this));
            }.bind(this));
        },

        actionCreateCase: function () {
            var attributes = {};

            if (this.model.get('parentType') == 'Account' && this.model.get('parentId')) {
                attributes.accountId = this.model.get('parentId');
                attributes.accountName = this.model.get('parentName');
                attributes.emailsIds = [this.model.id];
                attributes.emailId = this.model.id;
            }
            attributes.name = this.model.get('name');

            var viewName = this.getMetadata().get('clientDefs.Case.modalViews.detail') || 'Modals.Edit';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                scope: 'Case',
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:save', function () {
                    this.model.fetch();
                    this.removeMenuItem('createCase');
                    view.close();
                }.bind(this));
            }.bind(this));
        },


        actionCreateTask: function () {
            var attributes = {};

            attributes.parentId = this.model.get('parentId');
            attributes.parentName = this.model.get('parentName');
            attributes.parentType = this.model.get('parentType');

            attributes.description = '(' + this.model.get('name') + ')[#Email/view/' + this.model.id + ']';

            var viewName = this.getMetadata().get('clientDefs.Task.modalViews.detail') || 'Modals.Edit';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                scope: 'Task',
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:save', function () {
                    view.close();
                }.bind(this));
            }.bind(this));
        },

        actionCreateContact: function () {
            var attributes = {};

            var emailHelper = new EmailHelper(this.getLanguage(), this.getUser());

            var fromString = this.model.get('fromString') || this.model.get('fromName');
            if (fromString) {
                var fromName = emailHelper.parseNameFromStringAddress(fromString);
                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');
                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (this.model.get('replyToString')) {
                var str = this.model.get('replyToString');
                var p = (str.split(';'))[0];
                attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);
                var fromName = emailHelper.parseNameFromStringAddress(p);
                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');
                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (!attributes.emailAddress) {
                attributes.emailAddress = this.model.get('from');
            }
            attributes.emailId = this.model.id;

            var viewName = this.getMetadata().get('clientDefs.Contact.modalViews.detail') || 'Modals.Edit';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                scope: 'Contact',
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:save', function () {
                    this.model.fetch();
                    this.removeMenuItem('createContact');
                    this.removeMenuItem('createLead');
                    view.close();
                }.bind(this));
            }.bind(this));

        },

        actionSend: function () {
            var record = this.getView('body');

            var $send = this.$el.find('.header-buttons [data-action="send"]');
            $send.addClass('disabled');


            this.listenToOnce(record, 'after:send', function () {
                this.model.set('status', 'Sent');
                $send.remove();
                this.menu = this.backedMenu;
            }, this);

            this.listenToOnce(record, 'cancel:save', function () {
                $send.removeClass('disabled');
            }, this);

            record.send();
        },


        addForwardBodyAttrbutes: function (attributes) {
            if (this.model.get('isHtml')) {
                var body = this.model.get('body');
                body = '<br>' + '------' + this.translate('Forwarded message', 'labels', 'Email') + '------<br>' + body;

                attributes['body'] = body;
            } else {
                var bodyPlain = this.model.get('body') || this.model.get('bodyPlain') || '';

                bodyPlain = '\n\n' + '------' + this.translate('Forwarded message', 'labels', 'Email') + '------' + '\n' + bodyPlain;                

                attributes['body'] = bodyPlain;
                attributes['bodyPlain'] = bodyPlain;
            }
        },

        actionReply: function (data, cc) {
            var emailHelper = new EmailHelper(this.getLanguage(), this.getUser());

            var attributes = emailHelper.getReplyAttributes(this.model, data, cc);

            this.notify('Loading...');
            this.createView('quickCreate', 'Modals.ComposeEmail', {
                attributes: attributes,
            }, function (view) {
                view.render(function () {
                    view.getView('edit').hideField('selectTemplate');
                });

                view.notify(false);
            });
        },

        actionReplyToAll: function () {
            this.actionReply(null, true);
        },

        actionForward: function (data, cc) {
            var attributes = {
                status: 'Draft',
                isHtml: this.model.get('isHtml')
            };

            var subject = this.model.get('name');
            if (~!subject.toUpperCase().indexOf('FWD:') && ~!subject.toUpperCase().indexOf('FW:')) {
                attributes['name'] = 'Fwd: ' + subject;
            } else {
                attributes['name'] = subject;
            }

            if (this.model.get('parentId')) {
                attributes['parentId'] = this.model.get('parentId');
                attributes['parentName'] = this.model.get('parentName');
                attributes['parentType'] = this.model.get('parentType');
            }

            this.addForwardBodyAttrbutes(attributes);

            this.notify('Loading...');

            $.ajax({
                url: 'Email/action/getCopiedAttachments',
                type: 'GET',
                data: {
                    id: this.model.id
                }
            }).done(function (data) {
                attributes['attachmentsIds'] = data.ids;
                attributes['attachmentsNames'] = data.names;

                this.notify('Loading...');
                this.createView('quickCreate', 'Modals.ComposeEmail', {
                    attributes: attributes,
                }, function (view) {
                    view.render(function () {
                        view.getView('edit').hideField('selectTemplate');
                    });

                    view.notify(false);
                });

            }.bind(this));

        },

    });
});

