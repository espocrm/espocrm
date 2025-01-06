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

import NoteStreamView from 'views/stream/note';

class RelateNoteStreamView extends NoteStreamView {

    template = 'stream/notes/create-related'
    messageName = 'relate'

    data() {
        return {
            ...super.data(),
            relatedTypeString: this.translateEntityType(this.entityType),
            iconHtml: this.getIconHtml(this.entityType, this.entityId),
        };
    }

    init() {
        if (this.getUser().isAdmin()) {
            this.isRemovable = true;
        }

        super.init();
    }

    setup() {
        const data = this.model.get('data') || {};

        this.entityType = this.model.get('relatedType') || data.entityType || null;
        this.entityId = this.model.get('relatedId') || data.entityId || null;
        this.entityName = this.model.get('relatedName') ||  data.entityName || null;

        this.messageData['relatedEntityType'] = this.translateEntityType(this.entityType);

        this.messageData['relatedEntity'] =
            $('<a>')
                .attr('href', `#${this.entityType}/view/${this.entityId}`)
                .text(this.entityName)
                .attr('data-scope', this.entityType)
                .attr('data-id', this.entityId);

        this.createMessage();
    }
}

export default RelateNoteStreamView;
