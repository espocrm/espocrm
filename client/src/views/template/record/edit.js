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

import EditRecordView from 'views/record/edit';

export default class extends EditRecordView {

    saveAndContinueEditingAction = true

    setup() {
        super.setup();

        if (!this.model.isNew()) {
            this.setFieldReadOnly('entityType');
        }

        if (this.model.get('entityType')) {
            this.showField('variables');
        } else {
            this.hideField('variables');
        }

        if (this.model.isNew()) {
            const storedData = {};

            this.listenTo(this.model, 'change:entityType', () => {
                const entityType = this.model.get('entityType');

                if (!entityType) {
                    this.model.set('header', null);
                    this.model.set('body', null);
                    this.model.set('footer', null);

                    this.hideField('variables');

                    return;
                }

                this.showField('variables');

                if (entityType in storedData) {
                    this.model.set('header', storedData[entityType].header);
                    this.model.set('body', storedData[entityType].body);
                    this.model.set('footer', storedData[entityType].footer);
                    this.model.set('style', storedData[entityType].style);

                    return;
                }

                let header, body, footer;

                let sourceType = null;
                let style = null;

                if (
                    this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType])
                ) {
                    sourceType = entityType;
                } else {
                    const scopeType = this.getMetadata().get(['scopes', entityType, 'type']);

                    if (
                        scopeType &&
                        this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType])
                    ) {
                        sourceType = scopeType;
                    }
                }

                if (sourceType) {
                    header = this.getMetadata().get(
                        ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'header']
                    );

                    body = this.getMetadata().get(
                        ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'body']
                    );

                    footer = this.getMetadata().get(
                        ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'footer']
                    );

                    style = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', sourceType, 'style']);
                }

                body = body || null;
                header = header || null;
                footer = footer || null;

                this.model.set('body', body);
                this.model.set('header', header);
                this.model.set('footer', footer);
                this.model.set('style', style);
            });

            this.listenTo(this.model, 'change', (e, o) => {
                if (!o.ui) {
                    return;
                }

                if (
                    !this.model.hasChanged('header') &&
                    !this.model.hasChanged('body') &&
                    !this.model.hasChanged('footer') &&
                    !this.model.hasChanged('style')
                ) {
                    return;
                }

                const entityType = this.model.get('entityType');

                if (!entityType) {
                    return;
                }

                storedData[entityType] = {
                    header: this.model.get('header'),
                    body: this.model.get('body'),
                    footer: this.model.get('footer'),
                    style: this.model.get('style'),
                };
            });
        }
    }
}
