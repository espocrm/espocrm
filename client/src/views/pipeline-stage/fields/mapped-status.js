/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

// noinspection JSUnusedGlobalSymbols
export default class PipelineStageMappedStatusFieldView extends EnumFieldView {

    setupOptions() {
        const entityType = this.model.attributes.entityType;
        const field = this.model.attributes.field;

        if (!entityType || !field) {
            return;
        }

        let sourceEntityType = entityType;
        let sourceField = field;

        const params = this.getMetadata().get(`entityDefs.${entityType}.fields.${field}`) ?? {};

        let optionsPath = params.optionsPath;
        const optionsReference = params.optionsReference;
        /** @var string[] */
        let options = params.options ?? [];
        const style = params.style;

        if (!optionsPath && optionsReference) {
            const [refEntityType, refField] = optionsReference.split('.');

            optionsPath = `entityDefs.${refEntityType}.fields.${refField}.options`;

            sourceEntityType = refEntityType;
            sourceField = refField;
        }

        if (optionsPath) {
            options = this.getMetadata().get(optionsPath);
        }

        options = options.filter(it => it);

        this.params.options = Espo.Utils.clone(options);
        this.styleMap = style ?? {};

        const pairs = this.params.options
            .map(item => [item, this.getLanguage().translateOption(item, sourceField, sourceEntityType)])

        this.translatedOptions = Object.fromEntries(pairs);
    }
}
