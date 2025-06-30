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

import MainView from 'views/main';

class NoteDetailView extends MainView {

    templateContent = `
        <div class="header page-header">{{{header}}}</div>
        <div class="record list-container list-container-panel block-center">{{{record}}}</div>
    `

    /**
     * @private
     */
    isDeleted = false

    setup() {
        this.scope = this.model.entityType;

        this.setupHeader();
        this.setupRecord();

        this.listenToOnce(this.model, 'remove', () => {
            this.clearView('record');
            this.isDeleted = true;
            this.getHeaderView().reRender();
        });

        this.addActionHandler('fullRefresh', () => this.actionFullRefresh());
    }

    /**
     * @private
     */
    setupHeader() {
        this.createView('header', 'views/header', {
            selector: '> .header',
            scope: this.scope,
            fontSizeFlexible: true,
        });
    }

    /**
     * @private
     */
    setupRecord() {
        this.wait(
            this.getCollectionFactory().create(this.scope)
                .then(collection => {
                    this.collection = collection;
                    this.collection.add(this.model);

                    this.createView('record', 'views/stream/record/list', {
                        selector: '> .record',
                        collection: this.collection,
                        isUserStream: true,
                    });
                })
        );
    }

    getHeader() {
        const parentType = this.model.attributes.parentType;
        const parentId = this.model.attributes.parentId;

        const typeText = document.createElement('span');
        typeText.textContent = this.getLanguage().translateOption(this.model.attributes.type, 'type', 'Note');

        if (this.model.attributes.deleted || this.isDeleted) {
            typeText.style.textDecoration = 'line-through';
        }

        typeText.title = this.translate('clickToRefresh', 'messages');
        typeText.dataset.action = 'fullRefresh';
        typeText.style.cursor = 'pointer';

        if (parentType && parentId) {
            return this.buildHeaderHtml([
                (() => {
                    const a = document.createElement('a');
                    a.href = `#${parentType}`;
                    a.textContent = this.translate(parentType, 'scopeNamesPlural');

                    return a;
                })(),
                (() => {
                    const a = document.createElement('a');
                    a.href = `#${parentType}/view/${parentId}`;
                    a.textContent = this.model.attributes.parentName || parentId;

                    return a;
                })(),
                (() => {
                    const span = document.createElement('span');
                    span.textContent = this.translate('Stream', 'scopeNames');

                    return span;
                })(),
                typeText,
            ]);
        }

        return this.buildHeaderHtml([
            (() => {
                const span = document.createElement('span');
                span.textContent = this.translate('Stream', 'scopeNames');

                return span;
            })(),
            typeText,
        ]);
    }

    /**
     * @private
     */
    async actionFullRefresh() {
        Espo.Ui.notifyWait();

        await this.model.fetch();

        Espo.Ui.notify();
    }
}

export default NoteDetailView;
