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

export default class IntegrationsIndexView extends View {

    template = 'admin/integrations/index'

    /**
     * @private
     * @type {string[]}
     */
    integrationList

    integration = null

    data() {
        return {
            integrationDataList: this.getIntegrationDataList(),
            integration: this.integration,
        };
    }

    setup () {
        this.addHandler('click', 'a.integration-link', (e, target) => {
            this.openIntegration(target.dataset.name);
        });

        this.integrationList = Object.keys(this.getMetadata().get('integrations') || {})
            .sort((v1, v2) => this.translate(v1, 'titles', 'Integration')
                .localeCompare(this.translate(v2, 'titles', 'Integration'))
            );

        this.integration = this.options.integration || null;

        if (this.integration) {
            this.createIntegrationView(this.integration);
        }

        this.on('after:render', () => {
            this.renderHeader();

            if (!this.integration) {
                this.renderDefaultPage();
            }
        });
    }

    /**
     * @return {{name: string, active: boolean}[]}
     */
    getIntegrationDataList() {
        return this.integrationList.map(it => {
            return {
                name: it,
                active: this.integration === it,
            };
        })
    }

    /**
     * @param {string} integration
     * @return {Promise<Bull.View>}
     */
    createIntegrationView(integration) {
        const viewName = this.getMetadata().get(`integrations.${integration}.view`) ||
            'views/admin/integrations/' +
            Espo.Utils.camelCaseToHyphen(this.getMetadata().get(`integrations.${integration}.authMethod`));

        return this.createView('content', viewName, {
            fullSelector: '#integration-content',
            integration: integration,
        });
    }

    /**
     * @param {string} integration
     */
    async openIntegration(integration) {
        this.integration = integration;

        this.getRouter().navigate(`#Admin/integrations/name=${integration}`, {trigger: false});

        Espo.Ui.notifyWait();

        await this.createIntegrationView(integration);

        this.renderHeader();
        await this.reRender();

        Espo.Ui.notify(false);
        $(window).scrollTop(0);
    }

    afterRender() {
        this.$header = $('#integration-header');
    }

    renderDefaultPage() {
        this.$header.html('').hide();

        let msg;

        if (this.integrationList.length) {
            msg = this.translate('selectIntegration', 'messages', 'Integration');
        } else {
            msg = '<p class="lead">' + this.translate('noIntegrations', 'messages', 'Integration') + '</p>';
        }

        $('#integration-content').html(msg);
    }

    renderHeader() {
        if (!this.integration) {
            this.$header.html('');

            return;
        }

        this.$header.show().html(this.translate(this.integration, 'titles', 'Integration'));
    }

    updatePageTitle() {
        this.setPageTitle(this.getLanguage().translate('Integrations', 'labels', 'Admin'));
    }
}
