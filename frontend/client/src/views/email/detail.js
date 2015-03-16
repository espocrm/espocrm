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

Espo.define('Views.Email.Detail', 'Views.Detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (this.model.get('status') == 'Draft') {
                this.backedMenu = this.menu;
                this.menu = {
                    'buttons': [
                        {
                           "label": "Send",
                           "action": "send",
                           "style": "danger",
                           "acl": "edit"
                        }
                    ]
                };
            } else {
                if (!this.model.get('parentId')) {
                    this.menu.dropdown.push({
                        label: 'Create Lead',
                        action: 'createLead',
                        acl: 'edit',
                        aclScope: 'Lead'
                    });
                }
            }
        },

        actionCreateLead: function () {
            var attributes = {};

            var fromName = this.model.get('fromName');
            if (!fromName) {
                return '';
            }

            fromName = fromName.replace(/<(.*)>/, '').trim();

            var firstName = fromName.split(' ').slice(0, -1).join(' ');
            var lastName = fromName.split(' ').slice(-1).join(' ');

            attributes.firstName = firstName;
            attributes.lastName = lastName;
            attributes.emailAddress = this.model.get('from');
            attributes.emailId = this.model.id;

            this.notify('Loading...');

            this.createView('quickCreate', 'Modals.Edit', {
                scope: 'Lead',
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:save', function () {
                    this.model.fetch();
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


        addReplyBodyAttrbutes: function (attributes) {
            if (this.model.get('isHtml')) {
                var body = this.model.get('body');
                body = '<br><blockquote>' + '------' + this.translate('Original message', 'labels', 'Email') + '------<br>' + body + '</blockquote>';

                attributes['body'] = body;
            } else {
                var bodyPlain = this.model.get('body') || this.model.get('bodyPlain') || '';

                var b = '\n\n';
                b += '------' + this.translate('Original message', 'labels', 'Email') + '------' + '\n';

                bodyPlain.split('\n').forEach(function (line) {
                    b += '> ' + line + '\n';
                });
                bodyPlain = b;

                attributes['body'] = bodyPlain;
                attributes['bodyPlain'] = bodyPlain;
            }
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
            var attributes = {
                status: 'Draft',
                isHtml: this.model.get('isHtml')
            };

            var subject = this.model.get('name');
            if (subject.indexOf('Re:') !== 0) {
                attributes['name'] = 'Re: ' + subject;
            } else {
                attributes['name'] = subject;
            }

            if (this.model.get('from')) {
                attributes['to'] = this.model.get('from');
            }

            if (cc) {
                attributes['cc'] = this.model.get('cc');
                (this.model.get('to')).split(';').forEach(function (item) {
                   item = item.trim();
                   if (item != this.getUser().get('emailAddress')) {
                       attributes['cc'] += '; ' + item;
                   }
                }, this);
                attributes['cc'] = attributes['cc'].replace(/^(\; )/,"");
            }

            if (this.model.get('parentId')) {
                attributes['parentId'] = this.model.get('parentId');
                attributes['parentName'] = this.model.get('parentName');
                attributes['parentType'] = this.model.get('parentType');
            }

            this.addReplyBodyAttrbutes(attributes);

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
            if (subject.indexOf('Fwd:') !== 0) {
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

