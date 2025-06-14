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

import EnumFieldView from 'views/fields/enum';

export default class extends EnumFieldView {

    targetEntityType = 'Contact'

    setupOptions() {
        const entityType = this.model.attributes.name;

        /** @type {Record.<string, {entity?: string, entityList?: string[], type: string}>} */
        const defs = this.getMetadata().get(`entityDefs.${entityType}.links`) || {};

        const options = Object.keys(defs)
            .filter(link => {
                const linkDefs = defs[link];

                if (
                    linkDefs.type === 'belongsToParent' &&
                    linkDefs.entityList &&
                    linkDefs.entityList.includes(this.targetEntityType)
                ) {
                    return true;
                }

                return linkDefs.entity === this.targetEntityType;
            })
            .sort((a, b) => {
                return this.getLanguage().translate(a, 'links', entityType)
                    .localeCompare(this.getLanguage().translate(b, 'links', entityType));
            });

        options.unshift('');

        this.translatedOptions =
            options.reduce((p, it) => {
                p[it] = this.translate(it, 'links', entityType);

                return p;
            }, {});

        this.params.options = options;
    }
}
