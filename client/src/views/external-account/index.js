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

import View from 'view';

class ExternalAccountIndex extends View {

    template = 'external-account/index'

    data() {
        return {
            externalAccountList: this.externalAccountList,
            id: this.id,
            externalAccountListCount: this.externalAccountList.length
        };
    }

    setup() {
        this.addHandler('click', '#external-account-menu a.external-account-link', (e, target) => {
            const id = `${target.dataset.id}__${this.userId}`;

            this.openExternalAccount(id);
        });

        this.externalAccountList = this.collection.models.map(model => model.getClonedAttributes());

        this.userId = this.getUser().id;
        this.id = this.options.id || null;

        if (this.id) {
            this.userId = this.id.split('__')[1];
        }

        this.on('after:render', () => {
            this.renderHeader();

            if (!this.id) {
                this.renderDefaultPage();
            } else {
                this.openExternalAccount(this.id);
            }
        });
    }

    openExternalAccount(id) {
        this.id = id;

        const integration = this.integration = id.split('__')[0];

        this.userId = id.split('__')[1];

        this.getRouter().navigate(`#ExternalAccount/edit/${id}`, {trigger: false});

        const authMethod = this.getMetadata().get(['integrations', integration, 'authMethod']);

        const viewName =
            this.getMetadata().get(['integrations', integration, 'userView']) ||
            'views/external-account/' + Espo.Utils.camelCaseToHyphen(authMethod);

        Espo.Ui.notifyWait();

        this.createView('content', viewName, {
            fullSelector: '#external-account-content',
            id: id,
            integration: integration
        }, view => {
            this.renderHeader();
            view.render();
            Espo.Ui.notify(false);

            $(window).scrollTop(0);

            this.controlCurrentLink(id);
        });
    }

    controlCurrentLink() {
        const id = this.integration;

        this.element.querySelectorAll('.external-account-link').forEach(element => {
            element.classList.remove('disabled', 'text-muted');
        });

        const currentLink = this.element.querySelector(`.external-account-link[data-id="${id}"]`);

        if (currentLink) {
            currentLink.classList.add('disabled', 'text-muted');
        }
    }

    renderDefaultPage() {
        $('#external-account-header').html('').hide();
        $('#external-account-content').html('');
    }

    renderHeader() {
        const $header = $('#external-account-header');

        if (!this.id) {
            $header.html('');

            return;
        }

        $header.show().text(this.integration);
    }

    updatePageTitle() {
        this.setPageTitle(this.translate('ExternalAccount', 'scopeNamesPlural'));
    }
}

export default ExternalAccountIndex;
