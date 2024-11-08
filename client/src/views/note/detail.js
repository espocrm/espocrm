/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
        const parentType = this.model.get('parentType');
        const parentId = this.model.get('parentId');
        const parentName = this.model.get('parentName');
        const type = this.model.get('type');

        const $type = $('<span>')
            .text(this.getLanguage().translateOption(type, 'type', 'Note'));

        if (this.model.get('deleted') || this.isDeleted) {
            $type.css('text-decoration', 'line-through');
        }

        if (parentType && parentId) {
            return this.buildHeaderHtml([
                $('<a>')
                    .attr('href', `#${parentType}`)
                    .text(this.translate(parentType, 'scopeNamesPlural')),
                $('<a>')
                    .attr('href', `#${parentType}/view/${parentId}`)
                    .text(parentName || parentId),
                $('<span>')
                    .text(this.translate('Stream', 'scopeNames')),
                $type,
            ]);
        }

        return this.buildHeaderHtml([
            $('<span>')
                .text(this.translate('Stream', 'scopeNames')),
            $type,
        ]);
    }
}

export default NoteDetailView;
