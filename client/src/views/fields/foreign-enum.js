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

class ForeignEnumFieldView extends EnumFieldView {

    type = 'foreign'

    setupOptions() {
        this.params.options = [];

        const field = this.params.field;
        const link = this.params.link;

        if (!field || !link) {
            return;
        }

        const entityType = this.getMetadata().get(`entityDefs.${this.model.entityType}.links.${link}.entity`);

        if (!entityType) {
            return;
        }

        /**
         * @type {{
         *     optionsPath?: string|null,
         *     optionsReference?: string|null,
         *     translation?: string|null,
         *     options?: string[],
         *     isSorted?: boolean,
         *     displayAsLabel?: boolean,
         *     style?: Record,
         *     labelType?: string,
         * }}
         */
        const fieldDefs = this.getMetadata().get(`entityDefs.${entityType}.fields.${field}`);

        if (!fieldDefs) {
            return;
        }

        let {
            optionsPath,
            optionsReference,
            translation,
            options,
            isSorted,
            displayAsLabel,
            style,
            labelType,
        } = fieldDefs;

        if (!optionsPath && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;

            style = this.getMetadata().get(`entityDefs.${refEntityType}.fields.${refField}.style`) ?? {};
        }

        if (optionsPath) {
            options = this.getMetadata().get(optionsPath);
        }

        this.params.options = Espo.Utils.clone(options) ?? [];
        this.params.translation = translation;
        this.params.isSorted = isSorted ?? false;
        this.params.displayAsLabel = displayAsLabel ?? false;
        this.params.labelType = labelType;
        this.styleMap = style ?? {};

        const pairs = this.params.options
            .map(item => [item, this.getLanguage().translateOption(item, field, entityType)])

        this.translatedOptions = Object.fromEntries(pairs);
    }
}

export default ForeignEnumFieldView;
