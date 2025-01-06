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

import BaseFieldView from 'views/fields/base';

class JsonObjectFieldView extends BaseFieldView {

    type = 'jsonObject'

    listTemplate = 'fields/json-object/detail'
    detailTemplate = 'fields/json-object/detail'

    data() {
        const data = super.data();

        data.valueIsSet = this.model.has(this.name);
        data.isNotEmpty = !!this.model.get(this.name);

        return data;
    }

    getValueForDisplay() {
        const value = this.model.get(this.name);

        if (!value) {
            return null;
        }

        return JSON.stringify(value, null, 2)
            .replace(/(\r\n|\n|\r)/gm, '<br>').replace(/\s/g, '&nbsp;');
    }
}

export default JsonObjectFieldView;

