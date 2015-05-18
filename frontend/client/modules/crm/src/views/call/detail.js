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

 Espo.define('Crm:Views.Call.Detail', 'Views.Detail', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (['Held', 'Not Held'].indexOf(this.model.get('status')) == -1) {
                if (this.getAcl().checkModel(this.model, 'edit')) {
                    this.menu.buttons.push({
                        'label': 'Send Invitations',
                        'action': 'sendInvitations',
                        icon: 'glyphicon glyphicon-send',
                        'acl': 'edit',
                    });
                }
            }
        },

        actionSendInvitations: function () {
            if (confirm(this.translate('confirmation', 'messages'))) {
                this.$el.find('[data-action="sendInvitations"]').addClass('disabled');
                this.notify('Sending...');
                $.ajax({
                    url: 'Call/action/sendInvitations',
                    type: 'POST',
                    data: JSON.stringify({
                        id: this.model.id
                    }),
                    success: function () {
                        this.notify('Sent', 'success');
                        this.$el.find('[data-action="sendInvitations"]').removeClass('disabled');
                    }.bind(this),
                    error: function () {
                        this.$el.find('[data-action="sendInvitations"]').removeClass('disabled');
                    }.bind(this),
                });
            }
        }

    });
});

