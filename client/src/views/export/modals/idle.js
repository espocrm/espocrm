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

import ModalView from 'views/modal';
import Model from 'model';

class ExportIdleModalView extends ModalView {

    template = 'export/modals/idle'

    className = 'dialog dialog-record'
    checkInterval = 4000

    data() {
        return {
            infoText: this.translate('infoText', 'messages', 'Export'),
        };
    }

    setup() {
        this.addActionHandler('download', () => this.actionDownload());

        this.action = this.options.action;
        this.id = this.options.id;
        this.status = 'Pending';

        this.headerText = this.translate('Export');

        this.model = new Model();
        this.model.name = 'Export';

        this.model.setDefs({
            fields: {
                'status': {
                    type: 'enum',
                    readOnly: true,
                    options: [
                        'Pending',
                        'Running',
                        'Success',
                        'Failed',
                    ],
                    style: {
                        'Success': 'success',
                        'Failed': 'danger',
                    },
                },
                'attachmentId': {
                    type: 'varchar',
                },
            }
        });

        this.model.set({
            status: this.status,
            processedCount: null,
        });

        this.createView('record', 'views/record/edit-for-modal', {
            scope: 'None',
            model: this.model,
            selector: '.record',
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                name: 'status',
                                labelText: this.translate('status', 'fields', 'Export'),
                            }
                        ]
                    ]
                }
            ],
        });

        this.on('close', () => {
            const status = this.model.get('status');

            if (
                status !== 'Pending' &&
                status !== 'Running'
            ) {
                return;
            }

            Espo.Ajax.postRequest(`Export/${this.id}/subscribe`);
        });

        this.checkStatus();
    }

    /**
     * @private
     */
    checkStatus() {
        Espo.Ajax
            .getRequest(`Export/${this.id}/status`)
            .then(response => {
                const status = response.status;

                this.model.set('status', status);

                if (status === 'Pending' || status === 'Running') {
                    setTimeout(() => this.checkStatus(), this.checkInterval);

                    return;
                }

                this.model.set({
                    attachmentId: response.attachmentId,
                });

                if (status === 'Success') {
                    this.trigger('success', {
                        attachmentId: response.attachmentId,
                    });

                    this.showDownload();
                }

                if (this.$el) {
                    this.$el.find('.info-text').addClass('hidden');
                }
            });
    }

    /**
     * @private
     */
    showDownload() {
        this.$el.find('.download-container').removeClass('hidden');

        const $download = this.$el.find('[data-action="download"]');

        $download.removeClass('hidden');
    }

    /**
     * @private
     */
    actionDownload() {
        this.trigger('download', this.model.get('attachmentId'));

        this.close();
    }
}

export default ExportIdleModalView;
