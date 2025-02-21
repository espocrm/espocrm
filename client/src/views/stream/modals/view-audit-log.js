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
import ListStreamRecordView from 'views/stream/record/list';
import $ from 'jquery';

class StreamViewAuditLogModalView extends ModalView {

    templateContent = '<div class="record list-container">{{{record}}}</div>'

    backdrop = true

    /**
     * @param {{model: import('model').default}} options
     */
    constructor(options) {
        super(options);
    }

    setup() {
        const name = this.model.get('name') || this.model.id;

        this.$header = $('<span>')
            .append(
                $('<span>').text(name),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.translate('Audit Log'))
            );

        this.buttonList = [
            {
                name: 'close',
                label: 'Close',
                onClick: dialog => {
                    dialog.close();
                },
            }
        ];

        this.wait(
            this.getCollectionFactory().create('Note').then(collection => {
                collection.url = `${this.model.entityType}/${this.model.id}/updateStream`;
                collection.maxSize = this.getConfig().get('recordsPerPage');

                const listView = new ListStreamRecordView({
                    collection: collection,
                    model: this.model,
                    // Prevents 'No Data' being displayed.
                    skipBuildRows: true,
                    type: 'listAuditLog',
                });

                Espo.Ui.notifyWait();

                return this.assignView('record', listView, '.record')
                    .then(() => {
                        collection.fetch()
                            .then(() => Espo.Ui.notify(false));
                    });
            })
        );
    }
}

export default StreamViewAuditLogModalView;
