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

import LinkFieldView from 'views/fields/link';

export default class extends LinkFieldView {

    data() {
        const defaultAttributes = this.model.get('defaultAttributes') || {};
        const nameValue = defaultAttributes[this.options.field + 'Name'] || null;
        const idValue = defaultAttributes[this.options.field + 'Id'] || null;

        const data = super.data();

        data.nameValue = nameValue;
        data.idValue = idValue;

        return data;
    }

    setup() {
        super.setup();

        this.foreignScope = this.getMetadata()
            .get(['entityDefs', this.options.scope, 'links', this.options.field, 'entity']);
    }

    fetch() {
        const data = super.fetch();

        let defaultAttributes = {};
        defaultAttributes[this.options.field + 'Id'] = data[this.idName];
        defaultAttributes[this.options.field + 'Name'] = data[this.nameName];

        if (data[this.idName] === null) {
            defaultAttributes = null;
        }

        return {
            defaultAttributes: defaultAttributes
        };
    }
}
