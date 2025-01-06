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

    setup() {
        super.setup();

        if (!this.model.isNew()) {
            this.setReadOnly(true);
        }

        this.listenTo(this.model, 'change:field', () => {
            this.manageField();
        });

        this.viewValue = this.model.get('view');
    }

    setupOptions() {
        this.listenTo(this.model, 'change:link', () => {
            this.setupOptionsByLink();
            this.reRender();
        });

        this.setupOptionsByLink();
    }

    setupOptionsByLink() {
        this.typeList = this.getMetadata().get(['fields', 'foreign', 'fieldTypeList']);

        const link = this.model.get('link');

        if (!link) {
            this.params.options = [''];

            return;
        }

        const scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);

        if (!scope) {
            this.params.options = [''];

            return;
        }

        /** @type {Record<string, Record>} */
        const fields = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

        this.params.options = Object.keys(Espo.Utils.clone(fields)).filter(item => {
            const type = fields[item].type;

            if (!~this.typeList.indexOf(type)) {
                return;
            }

            if (
                fields[item].disabled ||
                fields[item].utility ||
                fields[item].directAccessDisabled ||
                fields[item].notStorable
            ) {
                return;
            }

            return true;
        });

        this.translatedOptions = {};

        this.params.options.forEach(item => {
            this.translatedOptions[item] = this.translate(item, 'fields', scope);
        });

        this.params.options = this.params.options.sort((v1, v2) => {
            return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
        });

        this.params.options.unshift('');
    }

    manageField() {
        if (!this.model.isNew()) {
            return;
        }

        const link = this.model.get('link');
        const field = this.model.get('field');

        if (!link || !field) {
            return;
        }

        const scope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', link, 'entity']);

        if (!scope) {
            return;
        }

        const type = this.getMetadata().get(['entityDefs', scope, 'fields', field, 'type']);

        this.viewValue = this.getMetadata().get(['fields', 'foreign', 'fieldTypeViewMap', type]);
    }

    fetch() {
        const data = super.fetch();

        if (this.model.isNew()) {
            if (this.viewValue) {
                data['view'] = this.viewValue;
            }
        }

        return data;
    }
}
