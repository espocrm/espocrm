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

define('views/external-account/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'external-account/index',

        data: function () {
            return {
                externalAccountList: this.externalAccountList,
                id: this.id,
                externalAccountListCount: this.externalAccountList.length
            };
        },

        events: {
            'click #external-account-menu a.external-account-link': function (e) {
                var id = $(e.currentTarget).data('id') + '__' + this.userId;
                this.openExternalAccount(id);
            },
        },

        setup: function () {
            this.externalAccountList = this.collection.toJSON();

            this.userId = this.getUser().id;
            this.id = this.options.id || null;
            if (this.id) {
                this.userId = this.id.split('__')[1];
            }

            this.on('after:render', function () {
                this.renderHeader();
                if (!this.id) {
                    this.renderDefaultPage();
                } else {
                    this.openExternalAccount(this.id);
                }
            });
        },

        openExternalAccount: function (id) {
            this.id = id;

            var integration = this.integration = id.split('__')[0];
            this.userId = id.split('__')[1];

            this.getRouter().navigate('#ExternalAccount/edit/' + id, {trigger: false});

            var authMethod = this.getMetadata().get(['integrations', integration, 'authMethod']);

            var viewName =
                    this.getMetadata().get(['integrations', integration, 'userView']) ||
                    'views/external-account/' + Espo.Utils.camelCaseToHyphen(authMethod);

            this.notify('Loading...');
            this.createView('content', viewName, {
                el: '#external-account-content',
                id: id,
                integration: integration
            }, function (view) {
                this.renderHeader();
                view.render();
                this.notify(false);
                $(window).scrollTop(0);
            }.bind(this));
        },

        renderDefaultPage: function () {
            $('#external-account-header').html('').hide();
            $('#external-account-content').html('');
        },

        renderHeader: function () {
            if (!this.id) {
                $('#external-account-header').html('');
                return;
            }
            $('#external-account-header').show().html(this.integration);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.translate('ExternalAccount', 'scopeNamesPlural'));
        },
    });
});
